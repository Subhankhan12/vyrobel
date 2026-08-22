<?php

use App\Exceptions\Folio\RefundExceedsPaymentException;
use App\Models\Folio;
use App\Models\OwnerLedgerEntry;
use App\Models\Payment;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #100 GATE 2 — Refund: the guest refund reverses a settled charge (#27, test-mode) AND adjusts the OWNER
// statement (a debit `adjustment` reconciling from what the owner actually earned), records the reason, and
// stays cumulative-bounded. (ownUnit/postRoom/ownerNet/lineFor live in tests/Support/owner_helpers.php.)

/** An owner-attributed reservation with room revenue attributed + a settled card payment on its folio. */
function refundScenario(float $commissionPct = 20.0): array
{
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], ['commission_pct' => $commissionPct]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);   // €200 room revenue
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $folio = Folio::query()->where('reservation_id', $reservation->id)->firstOrFail();
    $payment = app(FolioService::class)->recordPayment($folio, Money::of(20000, 'EUR'));

    return compact('s', 'owner', 'reservation', 'folio', 'payment');
}

// ── ⚠️ THE OWNER STATEMENT ADJUSTS — a refund posts an owner debit reconciling from their net ────────
it('adjusts the owner ledger on a guest refund, reconciling from the owner net', function () {
    $c = refundScenario(20.0);
    // €200 room − 20% commission = €160 net; net/gross = 0.8.
    $netBefore = ownerNet($c['owner']->id);
    expect($netBefore)->toBe(16000);

    // A €50 refund → the payment.refunded listener posts an owner adjustment = 5000 × (16000/20000) = 4000.
    app(FolioService::class)->refundPayment($c['payment'], Money::of(5000, 'EUR'), 'Overcharge');

    $adjustment = lineFor($c['owner']->id, 'adjustment');
    expect($adjustment)->not->toBeNull()
        ->and((string) $adjustment->direction)->toBe('debit')
        ->and((int) $adjustment->amount_minor)->toBe(4000)
        ->and(ownerNet($c['owner']->id))->toBe($netBefore - 4000);   // owner net drops by exactly the adjustment
});

// ── The owner adjustment is IDEMPOTENT (the same refund never double-posts) ─────────────────────────
it('never double-posts the owner adjustment for the same refund', function () {
    $c = refundScenario();
    $refund = app(FolioService::class)->refundPayment($c['payment'], Money::of(5000, 'EUR'));

    // Re-run the attribution for the same refund payment → firstOrCreate no-ops.
    app(OwnerAttributionService::class)->attributeRefund($c['reservation']->fresh(), Money::of(5000, 'EUR'), (string) $refund->getKey());

    expect(OwnerLedgerEntry::query()->where('owner_id', $c['owner']->id)->where('entry_type', 'adjustment')->count())->toBe(1);
});

// ── An OPERATOR-ONLY reservation (no owner) posts NO owner adjustment ────────────────────────────────
it('posts no owner adjustment for an operator-only reservation', function () {
    $s = seedScenario(1);   // no ownUnit → operator-only
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    $folio = Folio::query()->where('reservation_id', $reservation->id)->firstOrFail();
    $payment = app(FolioService::class)->recordPayment($folio, Money::of(20000, 'EUR'));

    app(FolioService::class)->refundPayment($payment, Money::of(5000, 'EUR'));

    expect(OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->where('entry_type', 'adjustment')->count())->toBe(0);
});

// ── The REASON is recorded on the refund metadata (F2) ──────────────────────────────────────────────
it('records the refund reason on the refund payment metadata', function () {
    $c = refundScenario();
    $refund = app(FolioService::class)->refundPayment($c['payment'], Money::of(5000, 'EUR'), 'Service issue · noisy AC');

    expect($refund->metadata['reason'] ?? null)->toBe('Service issue · noisy AC')
        ->and($refund->metadata['refunds_payment_id'] ?? null)->toBe((string) $c['payment']->getKey());
});

// ── The refund via the CONTROLLER adjusts the owner (end-to-end) + is bounded (#27) ─────────────────
it('issues a refund through the operator route and adjusts the owner', function () {
    $c = refundScenario();

    $this->actingAs(operatorUser($c['s'], [], 'Property Owner'))->post(route('app.reservations.refund', $c['reservation']->id), [
        'payment' => $c['payment']->id,
        'amount' => '50',
        'reason' => 'overcharge',
        'destination' => 'original',
    ])->assertRedirect()->assertSessionHas('folio_notice', 'Refund issued.');

    $refund = Payment::query()->where('reservation_id', $c['reservation']->id)->where('type', 'refund')->firstOrFail();
    expect((int) $refund->amount_minor)->toBe(5000)
        ->and($refund->metadata['reason'] ?? null)->toContain('Overcharge')
        ->and(lineFor($c['owner']->id, 'adjustment'))->not->toBeNull();
});

// ── Over-refund is STILL bounded (#27) — the reason param didn't break the guard ────────────────────
it('still rejects an over-refund (#27 bound intact)', function () {
    $c = refundScenario();
    app(FolioService::class)->refundPayment($c['payment'], Money::of(20000, 'EUR'));   // full

    expect(fn () => app(FolioService::class)->refundPayment($c['payment'], Money::of(100, 'EUR'), 'again'))
        ->toThrow(RefundExceedsPaymentException::class);
});
