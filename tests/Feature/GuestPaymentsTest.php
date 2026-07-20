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

// ── The card charge is DEFERRED — the balance shows an honest "Soon", never a faked payment ───────
it('defers the card charge honestly on the payments hub', function () {
    $s = seedScenario(1);
    $a = payGuest($s['guest'], 'ada@example.test');
    payBook($s, $a, $s['units'][0], chargeMinor: 30000, payMinor: 0);   // full balance due

    $this->actingAs($a, 'guest')->get(route('guest.payments'))
        ->assertOk()
        ->assertSee('Pay by card · Soon');   // deferred, honest — no real payment path for the guest
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
