<?php

declare(strict_types=1);

use App\Exceptions\Folio\RefundExceedsPaymentException;
use App\Models\Folio;
use App\Models\OwnerLedgerEntry;
use App\Models\Payment;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️ MONEY PATH — WHO BEARS A REFUND. The certified owner ledger is append-only, so the whole guarantee here
 * is that the DEFAULT moves nothing: an unset override must behave byte-identically to the product before
 * this column existed. NULL means OWNER-BORNE, which is both the ruling and the fail-safe direction — a
 * caller that forgets to pass anything debits the owner exactly as it always did.
 *
 * ⚠️ These tests are written to fail if the default ever moves, which is the failure that would matter: the
 * opposite default would silently STOP charging owners, a far larger change wearing a much smaller diff.
 */
function borneByScenario(): array
{
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], ['commission_pct' => 18]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    $folios = app(FolioService::class);
    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    $payment = $folios->recordPayment($folio, Money::of(20000, 'EUR'));

    return [$s, $owner, $reservation, $payment, $folios];
}

it('DEFAULT — an unset override debits the owner exactly as before', function () {
    [, $owner, $reservation, $payment, $folios] = borneByScenario();

    $before = OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->count();

    $refund = $folios->refundPayment($payment, Money::of(5000, 'EUR'), 'goodwill');

    expect($refund->refund_borne_by)->toBeNull();

    $adj = OwnerLedgerEntry::query()
        ->where('reservation_id', $reservation->id)
        ->where('entry_type', 'adjustment')
        ->get();

    expect($adj)->toHaveCount(1)
        ->and($adj->first()->idempotency_key)->toBe('refund:'.$refund->getKey().':'.$owner->id)
        ->and(OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->count())->toBe($before + 1);
});

it('DEFAULT — an explicit "owner" behaves identically to an unset override', function () {
    [, $owner, $reservation, $payment, $folios] = borneByScenario();

    $refund = $folios->refundPayment($payment, Money::of(5000, 'EUR'), 'goodwill', null, Payment::BORNE_BY_OWNER);

    expect(OwnerLedgerEntry::query()
        ->where('reservation_id', $reservation->id)
        ->where('entry_type', 'adjustment')
        ->count())->toBe(1);
});

it('OVERRIDE — an operator-borne refund creates NO owner ledger row at all', function () {
    [, , $reservation, $payment, $folios] = borneByScenario();

    $before = OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->get();

    $refund = $folios->refundPayment($payment, Money::of(5000, 'EUR'), 'goodwill', null, Payment::BORNE_BY_OPERATOR);

    expect($refund->refund_borne_by)->toBe('operator')
        ->and($refund->isOperatorBorne())->toBeTrue();

    // ⚠️ NOT a zero row — NO row. The ledger must be indistinguishable from one where no refund happened.
    expect(OwnerLedgerEntry::query()
        ->where('reservation_id', $reservation->id)
        ->where('entry_type', 'adjustment')
        ->count())->toBe(0)
        ->and(OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->count())
        ->toBe($before->count());
});

it('IDEMPOTENCY holds in both states — a redelivered event never double-posts', function () {
    [, , $reservation, $payment, $folios] = borneByScenario();

    $refund = $folios->refundPayment($payment, Money::of(5000, 'EUR'), 'goodwill');
    $attribution = app(OwnerAttributionService::class);

    // Re-attributing the same refund key must be a no-op, not a second debit.
    $attribution->attributeRefund($reservation->refresh(), Money::of(5000, 'EUR'), (string) $refund->getKey());

    expect(OwnerLedgerEntry::query()
        ->where('reservation_id', $reservation->id)
        ->where('entry_type', 'adjustment')
        ->count())->toBe(1);
});

it('#27 — the cumulative bound still refuses an over-refund with the override set', function () {
    [, , , $payment, $folios] = borneByScenario();

    $folios->refundPayment($payment, Money::of(15000, 'EUR'), 'partial', null, Payment::BORNE_BY_OPERATOR);

    // 20000 paid, 15000 already refunded → 6000 must be refused, override or not.
    expect(fn () => $folios->refundPayment($payment, Money::of(6000, 'EUR'), 'over', null, Payment::BORNE_BY_OPERATOR))
        ->toThrow(RefundExceedsPaymentException::class);
});

it('the owner-attributed test is the SAME one the refund path uses', function () {
    [, , $reservation] = borneByScenario();
    $attribution = app(OwnerAttributionService::class);

    expect($attribution->isOwnerAttributed((string) $reservation->id, 'EUR'))->toBeTrue()
        ->and($attribution->isOwnerAttributed((string) $reservation->id, 'USD'))->toBeFalse();
});

it('BOUNDARY — an unrecognised borne_by resolves to NULL, so the owner is debited', function () {
    [$s, , $reservation, $payment] = borneByScenario();

    // ⚠️ A hand-posted value must never reach the ledger decision. Anything outside the closed set is not a
    //    choice, so it falls back to the default — and the default charges the owner.
    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->post(route('app.reservations.refund', $reservation->id), [
            'payment' => $payment->id,
            'amount' => '50',
            'reason' => 'overcharge',
            'destination' => 'original',
            'borne_by' => 'nobody',
        ])->assertRedirect();

    $refund = Payment::query()->where('type', 'refund')->where('reservation_id', $reservation->id)->first();

    expect($refund)->not->toBeNull()
        ->and($refund->refund_borne_by)->toBeNull()
        ->and(OwnerLedgerEntry::query()
            ->where('reservation_id', $reservation->id)
            ->where('entry_type', 'adjustment')
            ->count())->toBe(1);
});
