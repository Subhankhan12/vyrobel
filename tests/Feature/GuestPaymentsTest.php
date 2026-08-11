<?php

use App\Models\Folio;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Tax;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Pdf\GuestInvoiceDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GUEST PAYMENTS + FOLIO RECEIPT + MONEY PDFs (Phase 4c · Sub-gate 1). Proven: the guest sees their OWN real
// balance/history (reconciling to the folio via FolioTotals); the money PDFs render the guest's real data and are
// owned-booking-only (cross-guest → 404, mutation-proven); the card charge is deferred (no faked payment); the
// refund/credit documents exist only when a real refund does.

if (! function_exists('payGuest')) {
    function payGuest(Guest $g, string $email): Guest
    {
        $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $g->password = 'guest-password-1234';
        $g->email_verified_at = now();
        $g->save();

        return $g->fresh();
    }
}

if (! function_exists('payBook')) {
    /** Book (in seedScenario's availability window) + open a folio + post a charge + record a payment. */
    function payBook(array $s, Guest $guest, Unit $unit, int $chargeMinor = 30000, int $payMinor = 20000): Reservation
    {
        $res = app(AssignmentService::class)->createBooking(
            ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
            [['room_type_id' => $s['roomType']->id, 'unit_id' => $unit->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'price_minor' => $chargeMinor, 'currency' => 'EUR']],
        );
        $folio = app(FolioService::class)->openFolio($res);
        app(FolioService::class)->postCharge($folio, 'room', 'Room stay', Money::of($chargeMinor, 'EUR'));
        if ($payMinor > 0) {
            app(FolioService::class)->recordPayment($folio, Money::of($payMinor, 'EUR'));
        }

        return $res;
    }
}

// ── The hub shows the guest's own real balance + paid, reconciling to the folio ───────────────────
it('shows the guest’s real balance and paid total across their bookings', function () {
    $s = seedScenario(1);
    $a = payGuest($s['guest'], 'ada@example.test');
    payBook($s, $a, $s['units'][0], chargeMinor: 30000, payMinor: 20000);   // balance €100.00, paid €200.00

    $this->actingAs($a, 'guest')->get(route('guest.payments'))
        ->assertOk()
        ->assertSee('€100.00')   // balance = charges − paid
        ->assertSee('€200.00');  // paid to date
});

// ── The receipt screen renders with the document links (owned booking) ────────────────────────────
it('shows a folio receipt with the money-document links', function () {
    $s = seedScenario(1);
    $a = payGuest($s['guest'], 'ada@example.test');
    $res = payBook($s, $a, $s['units'][0]);

    $this->actingAs($a, 'guest')->get(route('guest.receipt', $res->getKey()))
        ->assertOk()
        ->assertSee('Folio receipt')
        ->assertSee('VAT invoice')
        ->assertSee('Statement of account');
});

// ── The money PDFs render as real PDFs for an owned booking ───────────────────────────────────────
it('renders the folio-receipt, invoice and statement PDFs for an owned booking', function () {
    $s = seedScenario(1);
    $a = payGuest($s['guest'], 'ada@example.test');
    $res = payBook($s, $a, $s['units'][0]);

    foreach (['guest.doc.receipt', 'guest.doc.invoice', 'guest.doc.statement'] as $route) {
        $response = $this->actingAs($a, 'guest')->get(route($route, $res->getKey()));
        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('application/pdf');
        expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
    }
});

// ── ISOLATION (mutation-proven): a guest can't open another guest's receipt or money PDFs ─────────
it('404s when a guest opens another guest’s receipt or money PDFs', function () {
    $s = seedScenario(2);
    $a = payGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $bRes = payBook($s, $b, $s['units'][1]);

    $this->actingAs($a, 'guest')->get(route('guest.receipt', $bRes->getKey()))->assertNotFound();
    foreach (['guest.doc.receipt', 'guest.doc.invoice', 'guest.doc.statement'] as $route) {
        $this->actingAs($a, 'guest')->get(route($route, $bRes->getKey()))->assertNotFound();
    }
});

// ── The payments hub never surfaces another guest's booking ───────────────────────────────────────
it('never shows another guest’s booking on the payments hub', function () {
    $s = seedScenario(2);
    $a = payGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $aRes = payBook($s, $a, $s['units'][0]);
    $bRes = payBook($s, $b, $s['units'][1]);

    $this->actingAs($a, 'guest')->get(route('guest.payments'))
        ->assertOk()
        ->assertSee($aRes->reference)          // own booking shown
        ->assertDontSee($bRes->reference);     // the other guest's never appears
});

// ── Phase 8 — the card charge is LIVE (Stripe hosted Checkout); a real "Pay by card" on a balance ────
it('offers a real Stripe card payment on a balance (SAQ A — no card field on our page)', function () {
    $s = seedScenario(1);
    $a = payGuest($s['guest'], 'ada@example.test');
    $res = payBook($s, $a, $s['units'][0], chargeMinor: 30000, payMinor: 0);   // full balance due

    $this->actingAs($a, 'guest')->get(route('guest.payments'))
        ->assertOk()
        ->assertSee('Pay by card')                                  // a real payment path (hosted Stripe Checkout)
        ->assertSee(route('guest.payments.pay', $res->id), false)   // POSTs to the guest pay route
        ->assertDontSee('Card number');                             // 🪤 no card field on our page (SAQ A)
});

// ── Refund receipt + credit note exist ONLY when a real refund does ───────────────────────────────
it('offers a refund receipt only once a real refund exists', function () {
    $s = seedScenario(1);
    $a = payGuest($s['guest'], 'ada@example.test');
    $res = payBook($s, $a, $s['units'][0], chargeMinor: 30000, payMinor: 30000);

    // No refund yet → the reversal documents 404 (nothing to document).
    $this->actingAs($a, 'guest')->get(route('guest.doc.refund', $res->getKey()))->assertNotFound();
    $this->actingAs($a, 'guest')->get(route('guest.doc.credit-note', $res->getKey()))->assertNotFound();

    // Issue a REAL refund on the booking's payment, then the documents render.
    $folio = Folio::query()->where('reservation_id', $res->getKey())->with('payments')->first();
    $payment = $folio->payments->firstWhere('type', 'payment');
    app(FolioService::class)->refundPayment($payment, Money::of(10000, 'EUR'));

    $refund = $this->actingAs($a, 'guest')->get(route('guest.doc.refund', $res->getKey()));
    $refund->assertOk();
    expect(substr($refund->getContent(), 0, 4))->toBe('%PDF');
    $this->actingAs($a, 'guest')->get(route('guest.doc.credit-note', $res->getKey()))->assertOk();
});

// ── The VAT invoice reconciles: net + VAT = gross = the folio's charges ───────────────────────────
it('reconciles the VAT invoice net + vat to the folio charges', function () {
    $s = seedScenario(1);
    $a = payGuest($s['guest'], 'ada@example.test');
    $res = payBook($s, $a, $s['units'][0], chargeMinor: 30000, payMinor: 0);

    // Add a real VAT line to the folio.
    $folio = Folio::query()->where('reservation_id', $res->getKey())->first();
    $vat = Tax::create(['property_id' => $s['property']->id, 'name' => 'VAT', 'kind' => 'vat', 'mode' => 'percentage', 'rate' => 23.0]);
    app(FolioService::class)->applyTax($folio, $vat, Money::of(30000, 'EUR'));

    $data = GuestInvoiceDocument::data($res->fresh());
    expect($data['net'])->toBe('€300.00');
    expect($data['vat'])->toBe('€69.00');    // 23% of 300
    expect($data['gross'])->toBe('€369.00'); // net + vat, = the folio charges
});

// ── G2 pixel-diff HONESTY: the hero's real "Total charged" tile + NO fabricated saved-card vault / Apple Pay ──
it('shows the real total-charged hero tile and refuses the fabricated saved-card + Apple Pay (#39/#41)', function () {
    $s = seedScenario(1);
    $a = payGuest($s['guest'], 'ada@example.test');
    payBook($s, $a, $s['units'][0], 30000, 20000);   // €300 charged, €200 paid → €100 due

    $res = $this->actingAs($a, 'guest')->get(route('guest.payments'))->assertOk();

    // The 3 hero tiles are REAL (charged/paid/balance reconcile to the folio, #17).
    $res->assertSee('Total charged')
        ->assertSee('€300.00')   // charged
        ->assertSee('Paid to date')
        ->assertSee('Pay by card');   // the real hosted-Stripe pay flow for the outstanding balance

    // #39/#41/#28: NO fabricated saved card / Apple Pay — an honest no-stored-card state instead.
    $res->assertDontSee('Apple Pay')
        ->assertDontSee('Card on file')
        ->assertDontSee('Expires 04')
        ->assertSee('never touch Vyrobel');
});

// ── 🔒 THE WORST LEAK ON THIS SURFACE: paying against a FOREIGN folio ─────────────────────────────
// The hub and the receipt were already guarded, but the /pay POST itself was only asserted to APPEAR in the
// HTML — never that a foreign booking id is refused. A guest opening a Checkout session against another
// guest's folio is the worst outcome here (they would see that booking's balance on Stripe, and pay it).
// Resolved through the guest's own reservations relation, so a foreign id simply does not resolve.
it('404s when a guest tries to pay another guest’s folio', function () {
    $s = seedScenario(2);
    $a = payGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $aRes = payBook($s, $a, $s['units'][0]);
    $bRes = payBook($s, $b, $s['units'][1]);

    $this->actingAs($a, 'guest')->post(route('guest.payments.pay', $bRes->getKey()))->assertNotFound();

    // …and B's folio is untouched — no Payment row was created against it.
    $bFolio = Folio::query()->where('reservation_id', $bRes->getKey())->first();
    expect($bFolio?->payments()->where('status', 'pending')->count() ?? 0)->toBe(0);

    // A's own booking still resolves (so this can never pass because the route is simply broken).
    $this->actingAs($a, 'guest')->post(route('guest.payments.pay', $aRes->getKey()))->assertRedirect();
});

// ── §N.8d PENDING ≠ PAID — a pre-webhook payment is not money received ────────────────────────────
// The folio settles ONLY on `payment_intent.succeeded`. Until then the Payment row is `pending`, and the
// guest must not see it as received nor watch their balance drop: the money has not arrived. Overstating
// here is the fabricated-settled trap on the surface where it matters most.
it('never counts a pending payment as received, in the figures or the history (§N.8d)', function () {
    $s = seedScenario(1);
    $a = payGuest($s['guest'], 'ada@example.test');
    $reservation = payBook($s, $a, $s['units'][0], chargeMinor: 30000, payMinor: 10000);
    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();

    // A pre-webhook payment for the whole outstanding balance.
    $folio->payments()->create([
        'organization_id' => $reservation->organization_id, 'type' => 'payment', 'status' => 'pending',
        'amount_minor' => 20000, 'currency' => 'EUR', 'method' => 'card',
    ]);

    $html = $this->actingAs($a, 'guest')->get(route('guest.payments'))->assertOk()
        ->assertSee('€200.00')            // the balance is STILL due — the money has not arrived
        ->getContent();

    // …and the pending charge is not dressed up as history.
    expect(substr_count($html, 'Payment received'))->toBe(1);   // only the REAL €100 settled payment
});

// ── §N.8d, the UNDERSTATING direction — the return from hosted Checkout ───────────────────────────
// Stripe returns the guest to `?paid=1` on success, but the folio settles on the webhook, so the balance is
// still legitimately due at that instant. The page said nothing at all, so a guest back from a completed
// checkout saw an unchanged balance and could reasonably pay again. It must acknowledge — WITHOUT claiming
// the payment succeeded, because `?paid=1` is just a URL anyone can type.
it('acknowledges the return from checkout without ever claiming the payment succeeded', function () {
    $s = seedScenario(1);
    $a = payGuest($s['guest'], 'ada@example.test');
    payBook($s, $a, $s['units'][0]);

    $html = $this->actingAs($a, 'guest')->get(route('guest.payments', ['paid' => 1]))->assertOk()
        ->assertSee('take a few moments to confirm')     // the honest acknowledgement
        ->getContent();

    foreach (['Payment successful', 'Thank you for your payment', 'successfully paid'] as $overclaim) {
        expect($html)->not->toContain($overclaim);
    }
});
