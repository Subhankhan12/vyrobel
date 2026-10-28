<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\User;
use App\Services\FolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// THE OPERATOR REFUND SCREEN (Wave 2 · sub-gate 3) — the styled surface over the ALREADY-GUARDED
// FolioService::refundPayment (invariant #27, docs/DECISIONS.md 2026-07-16).
//
// The backend was certified in the guard gate; RefundGuardTest locks the SERVICE. This file locks the
// thing the screen is actually for: **the UI respecting the guard rather than inviting a rejection.**
// Specifically — the screen shows the real REMAINING refundable (never the original), caps the input at
// it, offers no control once it hits zero, and the amount-aware controller refuses a bad amount cleanly
// instead of 500ing or silently clamping.

/** A settled €300.00 payment on a real folio, through the certified path (RefundGuardTest's idiom). */
function refundScreen(int $paidMinor = 30000): array
{
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur($paidMinor));
    $payment = $folios->recordPayment($folio, moneyEur($paidMinor));

    return [
        'folios' => $folios,
        'folio' => $folio,
        'payment' => $payment,
        'reservation' => $reservation,
        'scenario' => $s,
        // Accountant, not the Hotel Manager default: the refund route + screen are gated on
        // `payments.refund` (invariant #30), which the catalog DELIBERATELY withholds from a manager.
        // The fixture moves to a role that genuinely holds it — the check is not weakened to suit it.
        'staff' => operatorUser($s, [], 'Accountant'),
        'screen' => "/app/reservations/{$reservation->id}/refund",
        'post' => "/app/reservations/{$reservation->id}/refunds",
    ];
}

/** Count of refund rows recorded against a payment — the real source the guard sums. */
function refundRowCount(Payment $original): int
{
    return Payment::withTrashed()
        ->where('type', 'refund')
        ->where('metadata->refunds_payment_id', $original->getKey())
        ->count();
}

// ── 1. A real PARTIAL refund through the styled screen PERSISTS (the feature this gate unblocks) ──
it('persists a real partial refund posted from the refund screen, append-only, and reconciles the folio', function () {
    $c = refundScreen(30000);   // €300.00 charged and paid

    $this->actingAs($c['staff'])
        ->post($c['post'], ['payment' => $c['payment']->id, 'amount' => '100.00'])
        ->assertRedirect("/app/reservations/{$c['reservation']->id}")
        ->assertSessionHas('folio_notice', 'Refund issued.');

    // A REAL new offsetting row — €100.00, linked to the original.
    $refund = Payment::query()->where('type', 'refund')->sole();
    expect((int) $refund->amount_minor)->toBe(10000)
        ->and($refund->status)->toBe('succeeded')
        ->and($refund->metadata['refunds_payment_id'])->toBe($c['payment']->getKey());

    // APPEND-ONLY: the original is untouched (not edited down, not restatused).
    $original = $c['payment']->fresh();
    expect((int) $original->amount_minor)->toBe(30000)
        ->and($original->type)->toBe('payment')
        ->and($original->status)->toBe('succeeded');

    // THE RECONCILIATION IDENTITY: paid = received − refunded; balance = charges − paid. Exact minor units.
    $folio = $c['folio']->refresh();
    expect((int) $folio->total_minor)->toBe(30000)
        ->and((int) $folio->paid_minor)->toBe(20000)     // 30000 − 10000
        ->and((int) $folio->balance_minor)->toBe(10000); // 30000 − 20000 — a refund puts the balance BACK
});

// ── 2. The screen shows the REAL remaining, and it SHRINKS after a refund (never the original) ────
it('shows the real remaining refundable and shrinks it after a partial refund', function () {
    $c = refundScreen(30000);

    // Before: the whole €300.00 is refundable.
    $this->actingAs($c['staff'])->get($c['screen'])->assertOk()->assertSee('€300.00');

    $c['folios']->refundPayment($c['payment']->fresh(), moneyEur(10000));

    $after = $this->actingAs($c['staff'])->get($c['screen'])->assertOk();

    // The remaining (€200.00) and the already-refunded (€100.00) are both the service's own arithmetic.
    $after->assertSee('€200.00')->assertSee('€100.00')->assertSee('Partly refunded');
})->group('security');

// ── 3. The input is CAPPED at the remaining — not the original ────────────────────────────────────
it('caps the partial-refund input at the remaining refundable, not the original amount', function () {
    $c = refundScreen(30000);
    $c['folios']->refundPayment($c['payment']->fresh(), moneyEur(10000));   // remaining = €200.00

    $body = $this->actingAs($c['staff'])->get($c['screen'])->assertOk()->getContent();

    // THE LOAD-BEARING ASSERTION: the cap is the REMAINING. If the screen wrongly capped at the original
    // it would read max="300.00" — the exact over-refund the guard exists to refuse.
    // (D-MONEY-COMMA, 2026-10-28: the field is text so "150,50" can be typed; the cap moved from the number field's `max` to the
    //  amount readout, which refuses to send more — the same remaining figure, and the server's own #27 bound behind it.)
    expect($body)->toContain('data-max-minor="20000"')
        ->and($body)->not->toContain('data-max-minor="30000"')
        ->toMatch('~At most [^\'"]{0,12}200\.00~');   // the readout's own words carry the remaining (the € is escaped in the script)
})->group('security');

// ── 4. Over the remaining → cleanly REFUSED through the screen. Never clamped, never a 500 ────────
it('refuses a refund above the remaining cleanly, writes no row, and says why', function () {
    $c = refundScreen(30000);
    $c['folios']->refundPayment($c['payment']->fresh(), moneyEur(10000));   // remaining = €200.00

    // €250.00 > €200.00 remaining. The SERVICE rejects it — the screen never pre-clamps to what fits,
    // because silently refunding less than was asked for is its own money bug.
    $this->actingAs($c['staff'])
        ->post($c['post'], ['payment' => $c['payment']->id, 'amount' => '250.00'])
        ->assertRedirect("/app/reservations/{$c['reservation']->id}")
        ->assertSessionHas('folio_notice', 'That refund was refused — only 200.00 EUR is still refundable against that payment.');

    // NOTHING was written, and nothing partial was applied.
    expect(refundRowCount($c['payment']))->toBe(1)
        ->and((int) $c['folio']->refresh()->paid_minor)->toBe(20000);
})->group('security');

// ── 5. €0 is cleanly rejected — the InvalidArgumentException hole the amount input made reachable ──
it('rejects a zero refund cleanly instead of 500ing', function () {
    $c = refundScreen(30000);

    // refundPayment() throws InvalidArgumentException on amount <= 0 — which does NOT extend
    // RuntimeException and is NOT caught by the controller's RefundExceedsPaymentException handler. The
    // controller must refuse 0 BEFORE calling the service, or this is a 500.
    foreach (['0', '0.00', '-5.00', 'abc'] as $bad) {
        $this->actingAs($c['staff'])
            ->post($c['post'], ['payment' => $c['payment']->id, 'amount' => $bad])
            ->assertRedirect("/app/reservations/{$c['reservation']->id}")
            ->assertSessionHas('folio_notice', 'Enter a refund amount greater than zero.');
    }

    expect(refundRowCount($c['payment']))->toBe(0)
        ->and((int) $c['folio']->refresh()->paid_minor)->toBe(30000);   // untouched
})->group('security');

// ── 6. Fully refunded → the screen offers NO refund control at all ────────────────────────────────
it('offers no refund control once a payment is fully refunded', function () {
    $c = refundScreen(30000);

    // Partially refunded → the control REMAINS (there is still headroom).
    $c['folios']->refundPayment($c['payment']->fresh(), moneyEur(10000));
    $partial = $this->actingAs($c['staff'])->get($c['screen'])->assertOk();
    expect($partial->getContent())->toContain($c['post'])
        ->and($partial->getContent())->toContain('name="amount"');

    // Fully refunded → GONE. A refund button surviving a full refund is exactly what made the
    // cumulative over-refund reachable in the first place.
    $c['folios']->refundPayment($c['payment']->fresh(), moneyEur(20000));

    $full = $this->actingAs($c['staff'])->get($c['screen'])->assertOk();
    expect($full->getContent())->not->toContain($c['post'])          // no form to submit
        ->and($full->getContent())->not->toContain('name="amount"'); // no amount input
    $full->assertSee('Fully refunded')->assertSee('This payment is fully refunded');
})->group('security');

// ── 7. …and the WRITE refuses it too — the guard is the service, not the missing button ───────────
it('refuses a further refund POST when the payment is already fully refunded', function () {
    $c = refundScreen(30000);
    $c['folios']->refundPayment($c['payment']->fresh(), moneyEur(30000));

    // Defence beyond the UI: a hand-crafted POST (or a stale tab) hits the same wall.
    $this->actingAs($c['staff'])
        ->post($c['post'], ['payment' => $c['payment']->id, 'amount' => '50.00'])
        ->assertRedirect("/app/reservations/{$c['reservation']->id}")
        ->assertSessionHas('folio_notice', 'That payment is already fully refunded.');

    expect(refundRowCount($c['payment']))->toBe(1)
        ->and((int) $c['folio']->refresh()->paid_minor)->toBe(0);
})->group('security');

// ── 8a. ISOLATION — a cross-org reservation 404s on BOTH the screen and the write ─────────────────
it('404s a cross-org reservation on the refund screen and the refund POST', function () {
    $a = refundScreen(30000);
    $b = refundScreen(42500);   // a SECOND org (seedScenario switches the tenant context)

    // Org A's staff, reaching for org B's reservation: the tenant-scoped findOrFail cannot see it.
    $this->actingAs($a['staff'])->get($b['screen'])->assertNotFound();

    $this->actingAs($a['staff'])
        ->post($b['post'], ['payment' => $b['payment']->id, 'amount' => '10.00'])
        ->assertNotFound();

    // Not one cent moved in the other org.
    expect(refundRowCount($b['payment']))->toBe(0)
        ->and((int) $b['folio']->refresh()->paid_minor)->toBe(42500);
})->group('security');

// ── 8b. ISOLATION — an owner-role user is refused the screen entirely (BackOfficeAccessTest idiom) ─
it('refuses an owner-role user the refund screen', function () {
    $c = refundScreen(30000);

    // A linked owner: an active Membership (so their org resolves) + an Owner row, and NO back-office
    // role — exactly what OwnerInviteService produces in production.
    $user = User::factory()->create(['name' => 'Amara Okafor', 'status' => 'active']);
    Membership::create(['organization_id' => $c['scenario']['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $response = $this->actingAs($user)->get($c['screen']);
    $response->assertRedirect(route('owner.dashboard'));

    // The rejection body carries no operator money and no trace of the folio.
    expect($response->getContent())->not->toContain('€300.00');
})->group('security');

// ── 9. NO FICTION — nothing the schema cannot back is rendered ─────────────────────────────────────
it('renders no fabricated card or ETA (#28 — the honest gateway, never a fake ····4242)', function () {
    $c = refundScreen(30000);

    $response = $this->actingAs($c['staff'])->get($c['screen'])->assertOk();

    // `payments` has card_token ONLY — no brand, no last4 column exists anywhere in the schema (#100 F3).
    $response->assertDontSee('Visa');
    // Read past the page's random carriers (a Livewire checksum can hold "4242" — 🔬 once in 300 renders, 2026-10-10).
    expect(withoutRandomTokens((string) $response->getContent()))->not->toContain('4242');
    // No settlement-ETA field exists (a GatewayResult carries {id, status}) — the design's "2–5 days" is refused.
    $response->assertDontSee('2–5 business days');

    // The real gateway is shown instead of the fabricated card.
    $response->assertSee('Manual / offline');
    // #100 — the refund reason + the owner-statement adjustment are now REAL (mutation-proven elsewhere).
    $response->assertSee('Reason')->assertSee('The owner statement adjusts');
});

// ── 10. The raw-Blade net (a real Wave 1 bug: `word@directive` ships uncompiled source) ────────────
it('renders the refund screen with no unrendered Blade directives', function () {
    $c = refundScreen(30000);

    assertNoRawBlade($this->actingAs($c['staff'])->get($c['screen'])->assertOk());

    // …in the fully-refunded state too (a different branch of the template).
    $c['folios']->refundPayment($c['payment']->fresh(), moneyEur(30000));
    assertNoRawBlade($this->actingAs($c['staff'])->get($c['screen'])->assertOk());
});
