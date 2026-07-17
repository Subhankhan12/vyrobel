<?php

use App\Exceptions\Folio\RefundExceedsPaymentException;
use App\Models\Payment;
use App\Services\FolioService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// THE CUMULATIVE OVER-REFUND GUARD — the regression net for the live financial bug found during the
// Wave 2 inventory (docs/DECISIONS.md 2026-07-16).
//
// THE BUG: FolioService::refundPayment() checked `amount <= original->amount_minor` PER CALL and nothing
// summed prior refunds, so the same payment could be refunded without limit. Proven on a dev DB: a €369.00
// payment accepted THREE full refunds = €1,107.00.
//
// THE DISTINCTION THAT MATTERS: invariant #7 (append-only, correct-by-reversal) was NEVER violated — each
// refund is a legitimate new offsetting row and the original is untouched. Append-only guarantees you cannot
// EDIT a refund; it never bounded the SUM. This file locks the missing invariant: **the cumulative total
// refunded against a payment may equal, but never exceed, that payment's original amount.**
//
// These tests were written BEFORE the fix and verified to FAIL against the unguarded code.

/** A settled payment of $paidMinor on a real folio, through the certified path. */
function guardedPayment(int $paidMinor = 36900): array
{
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur($paidMinor));
    $payment = $folios->recordPayment($folio, moneyEur($paidMinor));

    return ['folios' => $folios, 'folio' => $folio, 'payment' => $payment, 'scenario' => $s];
}

/** Σ succeeded refunds recorded against a payment — the real source the guard sums. */
function refundedTotal(Payment $original): int
{
    return (int) Payment::withTrashed()
        ->where('type', 'refund')
        ->where('status', 'succeeded')
        ->where('metadata->refunds_payment_id', $original->getKey())
        ->sum('amount_minor');
}

// ── 1. THE HEADLINE — the exact bug: a full refund, then another, then another ────────────────────
it('rejects a second full refund of an already fully-refunded payment', function () {
    ['folios' => $folios, 'payment' => $payment] = guardedPayment(36900);   // €369.00

    // First FULL refund — legitimate, must succeed.
    $folios->refundPayment($payment->fresh(), moneyEur(36900));
    expect(refundedTotal($payment))->toBe(36900);

    // Second + third full refunds — each individually satisfies the OLD per-call check (36900 <= 36900).
    // The cumulative bound must reject them.
    // NB Pest's toThrow() second arg is an expected MESSAGE substring, not a description — passing a note
    // there silently asserts the wrong thing (the same trap as toContain()).
    foreach ([2, 3] as $attempt) {
        expect(fn () => $folios->refundPayment($payment->fresh(), moneyEur(36900)))
            ->toThrow(RefundExceedsPaymentException::class);
    }

    // The money is BOUNDED: €369.00 refunded against €369.00 — not €1,107.00.
    expect(refundedTotal($payment))->toBe(36900)
        ->and(Payment::query()->where('type', 'refund')->count())->toBe(1);   // no extra offsetting rows
});

// ── 2. Legitimate PARTIAL refunds still work (the certified behaviour must not regress) ───────────
it('still allows a partial refund below the original', function () {
    ['folios' => $folios, 'folio' => $folio, 'payment' => $payment] = guardedPayment(30000);

    $refund = $folios->refundPayment($payment->fresh(), moneyEur(10000));

    expect($refund->type)->toBe('refund')
        ->and((int) $refund->amount_minor)->toBe(10000)
        ->and(refundedTotal($payment))->toBe(10000);

    // The folio still reconciles to the cent through the real recompute.
    expect($folio->refresh()->paid_minor)->toBe(20000)
        ->and($folio->balance_minor)->toBe(10000);
});

// ── 3. Cumulative partials that sum to EXACTLY the original are allowed (equal, never exceed) ─────
it('allows cumulative partial refunds that sum to exactly the original, then rejects the next cent', function () {
    ['folios' => $folios, 'payment' => $payment] = guardedPayment(30000);

    $folios->refundPayment($payment->fresh(), moneyEur(10000));
    $folios->refundPayment($payment->fresh(), moneyEur(15000));
    $folios->refundPayment($payment->fresh(), moneyEur(5000));    // → exactly 30000

    expect(refundedTotal($payment))->toBe(30000);

    // Fully refunded: even ONE more cent is refused.
    expect(fn () => $folios->refundPayment($payment->fresh(), moneyEur(1)))
        ->toThrow(RefundExceedsPaymentException::class);

    expect(refundedTotal($payment))->toBe(30000);
});

// ── 4. The partial that would TIP OVER the original is rejected — and nothing partial is applied ──
it('rejects the refund that would tip cumulative refunds over the original, and applies nothing', function () {
    ['folios' => $folios, 'payment' => $payment] = guardedPayment(30000);

    $folios->refundPayment($payment->fresh(), moneyEur(25000));   // remaining = 5000

    // 6000 > 5000 remaining → rejected OUTRIGHT. Never silently clamped to 5000, never partially applied.
    expect(fn () => $folios->refundPayment($payment->fresh(), moneyEur(6000)))
        ->toThrow(RefundExceedsPaymentException::class);

    expect(refundedTotal($payment))->toBe(25000)                               // unchanged
        ->and(Payment::query()->where('type', 'refund')->count())->toBe(1);    // no row written

    // …but the exact remaining amount IS still refundable.
    $folios->refundPayment($payment->fresh(), moneyEur(5000));
    expect(refundedTotal($payment))->toBe(30000);
});

// ── 5. A FAILED refund must not consume the headroom ──────────────────────────────────────────────
it('does not count a failed refund toward the cumulative bound', function () {
    ['folios' => $folios, 'payment' => $payment] = guardedPayment(30000);

    // A failed refund row moved no money — it must not reduce what can still be refunded.
    Payment::create([
        'folio_id' => $payment->folio_id,
        'reservation_id' => $payment->reservation_id,
        'gateway' => 'manual',
        'type' => 'refund',
        'amount' => moneyEur(30000),
        'currency' => 'EUR',
        'status' => 'failed',
        'metadata' => ['refunds_payment_id' => $payment->getKey()],
        'processed_at' => now(),
    ]);

    $folios->refundPayment($payment->fresh(), moneyEur(30000));   // full refund still available
    expect(refundedTotal($payment))->toBe(30000);
});

// ── 6. A soft-deleted refund STILL bounds (a delete must not unlock more money) ───────────────────
it('counts a soft-deleted refund toward the cumulative bound', function () {
    ['folios' => $folios, 'payment' => $payment] = guardedPayment(30000);

    $refund = $folios->refundPayment($payment->fresh(), moneyEur(30000));
    $refund->delete();   // soft delete — posted financial rows should never be deleted (invariant #7)

    // Soft-deleting the refund must NOT hand back the headroom.
    expect(fn () => $folios->refundPayment($payment->fresh(), moneyEur(30000)))
        ->toThrow(RefundExceedsPaymentException::class);
});

// ── 7. CURRENCY — a refund in another currency is refused (minor units are not comparable) ────────
it('rejects a refund whose currency differs from the original payment', function () {
    ['folios' => $folios, 'payment' => $payment] = guardedPayment(30000);   // EUR

    // Before the fix this PASSED: the guard compared raw minor units across currencies.
    expect(fn () => $folios->refundPayment($payment->fresh(), Money::of(30000, 'USD')))
        ->toThrow(RefundExceedsPaymentException::class);

    expect(Payment::query()->where('type', 'refund')->count())->toBe(0);
});

// ── 8. APPEND-ONLY still holds — the guard is a pre-check, it changes nothing about a valid refund ─
it('keeps a valid refund append-only: a new offsetting row, the original untouched', function () {
    ['folios' => $folios, 'payment' => $payment] = guardedPayment(30000);
    $originalMinor = (int) $payment->amount_minor;

    $refund = $folios->refundPayment($payment->fresh(), moneyEur(12000));
    $payment->refresh();

    expect((int) $payment->amount_minor)->toBe($originalMinor)       // original untouched
        ->and($payment->type)->toBe('payment')
        ->and($payment->status)->toBe('succeeded')
        ->and($refund->getKey())->not->toBe($payment->getKey())      // a NEW row
        ->and($refund->type)->toBe('refund')
        ->and($refund->metadata['refunds_payment_id'])->toBe($payment->getKey());
});

// ── 9. THE GUARD IS IN THE SERVICE — so the HTTP path is covered too, and rejects CLEANLY ─────────
it('blocks an over-refund through the operator HTTP path with a clean error, not a 500', function () {
    ['folios' => $folios, 'payment' => $payment, 'scenario' => $s] = guardedPayment(30000);
    // Accountant — the role the catalog actually trusts with `payments.refund` (now enforced, #30).
    // This file is about the #27 MONEY bound, so its actor must get past authorization to reach it.
    $staff = operatorUser($s, [], 'Accountant');

    $reservationId = $payment->reservation_id;

    // First full refund via the real route — succeeds.
    $this->actingAs($staff)
        ->post("/app/reservations/{$reservationId}/refunds", ['payment' => $payment->id])
        ->assertRedirect("/app/reservations/{$reservationId}");
    expect(refundedTotal($payment))->toBe(30000);

    // Clicking Refund AGAIN — the shipped path that made this reachable. Clean rejection, NOT a 500.
    // `folio_notice` is this controller's existing flash key (the one the view actually renders).
    $this->actingAs($staff)
        ->post("/app/reservations/{$reservationId}/refunds", ['payment' => $payment->id])
        ->assertRedirect("/app/reservations/{$reservationId}")
        ->assertSessionHas('folio_notice', 'That payment is already fully refunded.');

    // The money is bounded and no second offsetting row exists.
    expect(refundedTotal($payment))->toBe(30000)
        ->and(Payment::query()->where('type', 'refund')->count())->toBe(1);
})->group('security');

// ── 10. The UI stops inviting the over-refund: a fully-refunded payment is not refundable ─────────
it('marks a fully-refunded payment as no longer refundable and exposes the remaining amount', function () {
    ['folios' => $folios, 'payment' => $payment, 'scenario' => $s] = guardedPayment(30000);
    // Accountant — the role the catalog actually trusts with `payments.refund` (now enforced, #30).
    // This file is about the #27 MONEY bound, so its actor must get past authorization to reach it.
    $staff = operatorUser($s, [], 'Accountant');
    $reservationId = $payment->reservation_id;

    // NB assert on the RENDERED form action (a URL), not the route NAME — `route()` renders the URL, so
    // asserting on 'app.reservations.refund' could never fail. A vacuous assertion is worse than none.
    $refundForm = "/app/reservations/{$reservationId}/refunds";

    // Partially refunded → the button must REMAIN (there is still headroom).
    $folios->refundPayment($payment->fresh(), moneyEur(12000));
    $partial = $this->actingAs($staff)->get("/app/reservations/{$reservationId}")->assertOk();
    expect($partial->getContent())->toContain($refundForm);

    // Fully refunded → the button must be GONE. Its persistence is what made the bug reachable.
    $folios->refundPayment($payment->fresh(), moneyEur(18000));
    expect(refundedTotal($payment))->toBe(30000);

    $full = $this->actingAs($staff)->get("/app/reservations/{$reservationId}")->assertOk();
    expect($full->getContent())->not->toContain($refundForm);
})->group('security');
