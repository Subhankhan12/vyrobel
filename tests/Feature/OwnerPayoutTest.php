<?php

use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Reservation;
use App\Payouts\Contracts\PayoutRail;
use App\Payouts\PayoutResult;
use App\Payouts\Rails\ManualPayoutRail;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Phase 3 · Module 1 Step 5 — payouts (calculation + lifecycle; money rail deferred).
// (ownUnit/postRoom/ownerNet from tests/Support/owner_helpers.php.)

/** A rail that always fails — used to prove append-only retry. */
class FailingTestRail implements PayoutRail
{
    public function name(): string
    {
        return 'manual';
    }

    public function send(Payout $payout): PayoutResult
    {
        return new PayoutResult(successful: false, failureReason: 'bank rejected');
    }
}

/** Build a finalized statement with a known positive net for an owner. */
function finalizedStatement(array $s, $owner, int $roomMinor = 20000, string $start = '2026-09-01', string $end = '2026-09-30'): OwnerStatement
{
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    $svc = app(OwnerStatementService::class);

    return $svc->finalize($svc->generateDraft($owner->id, $start, $end));
}

// ── 1. Created from a finalized statement for the exact net; never from a draft ─────────────────
it('creates a payout from a finalized statement for the exact net payable, and refuses a draft', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $statement = finalizedStatement($s, $owner); // net 17000 (20000 − 15%)

    $payout = app(OwnerPayoutService::class)->createFromStatement($statement);

    expect($payout->amount_minor)->toBe(17000)
        ->and($payout->currency)->toBe($statement->currency)   // owner payout currency
        ->and($payout->status)->toBe('pending')
        ->and($payout->owner_statement_id)->toBe($statement->id)
        ->and($payout->rail)->toBe('manual');

    // A draft (unlocked) statement cannot produce a payout.
    $draft = app(OwnerStatementService::class)->generateDraft($owner->id, '2026-10-01', '2026-10-31');
    expect(fn () => app(OwnerPayoutService::class)->createFromStatement($draft))
        ->toThrow(RuntimeException::class);
});

// ── 2. ManualPayoutRail marks the payout paid AND flips the statement to paid ────────────────────
it('marks the payout paid via the manual rail and flips the statement to paid', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $statement = finalizedStatement($s, $owner);
    $svc = app(OwnerPayoutService::class);

    $payout = $svc->pay($svc->createFromStatement($statement));

    expect($payout->status)->toBe('paid')
        ->and($payout->paid_at)->not->toBeNull()
        ->and($payout->rail)->toBe('manual')
        ->and($statement->fresh()->status)->toBe('paid');
});

// ── 3. Negative/zero net → no positive payout; negative carries forward ─────────────────────────
it('creates no payout for a zero-net statement and carries a negative net forward', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $svc = app(OwnerStatementService::class);
    $pay = app(OwnerPayoutService::class);

    // Period 1: positive, finalize + pay.
    $sept = finalizedStatement($s, $owner);
    $pay->pay($pay->createFromStatement($sept));
    $septNet = $sept->fresh()->net_source_minor; // 17000

    // Post-lock refund → October nets negative.
    app(OwnerAttributionService::class)->reverseReservation(Reservation::first());
    $oct = $svc->finalize($svc->generateDraft($owner->id, '2026-10-01', '2026-10-31'));
    expect($oct->net_payable_minor)->toBe(-$septNet); // negative

    $result = $pay->createFromStatement($oct);
    expect($result)->toBeNull()                                       // no positive payout
        ->and(Payout::where('owner_statement_id', $oct->id)->count())->toBe(0);

    // Carry-forward: a single adjustment line dated into November offsets a future payout.
    $carry = OwnerLedgerEntry::where('owner_id', $owner->id)->where('note', 'balance_carried_forward')->first();
    expect($carry)->not->toBeNull()
        ->and($carry->signedMinor())->toBe(-$septNet)                // debit carries the negative
        ->and($carry->service_date->toDateString())->toBe('2026-11-01')
        ->and($carry->prior_period_adjustment)->toBeTrue();

    // A genuinely zero-net (activity-free) owner produces neither a payout nor a carry line.
    $zeroOwner = Owner::factory()->create(['payout_currency' => 'EUR']);
    $empty = $svc->finalize($svc->generateDraft($zeroOwner->id, '2027-01-01', '2027-01-31'));
    expect($empty->net_payable_minor)->toBe(0)
        ->and($pay->createFromStatement($empty))->toBeNull()
        ->and(OwnerLedgerEntry::where('note', 'balance_carried_forward')->where('source_id', $empty->id)->count())->toBe(0);
});

// ── 4. Append-only: a failed payout + retry is two records; history reconstructable ─────────────
it('keeps a failed payout and creates a new record on retry (append-only)', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $statement = finalizedStatement($s, $owner);
    $svc = app(OwnerPayoutService::class);

    $first = $svc->createFromStatement($statement);
    $svc->pay($first, new FailingTestRail);         // fails
    expect($first->fresh()->status)->toBe('failed')
        ->and($first->fresh()->failure_reason)->toBe('bank rejected')
        ->and($statement->fresh()->status)->toBe('finalized'); // not paid

    $retry = $svc->retry($first->fresh());          // NEW record
    $paid = $svc->pay($retry);                       // succeeds via manual rail

    expect(Payout::where('owner_statement_id', $statement->id)->count())->toBe(2) // both records kept
        ->and($retry->supersedes_id)->toBe($first->id)                            // history chain
        ->and($first->fresh()->status)->toBe('failed')                            // original NOT edited away
        ->and($paid->status)->toBe('paid')
        ->and($statement->fresh()->status)->toBe('paid');
});

// ── 5. Stripe Connect rail is config-gated and NOT the active rail in tests ─────────────────────
it('binds the manual rail by default and never the deferred Stripe Connect skeleton', function () {
    expect(config('services.payouts.rail'))->toBe('manual')
        ->and(app(PayoutRail::class))->toBeInstanceOf(ManualPayoutRail::class)
        ->and(app(PayoutRail::class)->name())->toBe('manual');
});

// ── 6. Owner isolation on payouts ───────────────────────────────────────────────────────────────
it('scopes payouts to their own owner', function () {
    $s = seedScenario(2);
    $ownerA = ownUnit($s['units'][0]);
    $ownerB = ownUnit($s['units'][1]);
    $svc = app(OwnerStatementService::class);
    $pay = app(OwnerPayoutService::class);

    $resA = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($resA, 20000);
    app(OwnerAttributionService::class)->attributeReservation($resA);
    $resB = book($s, ['unit_id' => $s['units'][1]->id]);
    postRoom($resB, 30000);
    app(OwnerAttributionService::class)->attributeReservation($resB);

    $stmtA = $svc->finalize($svc->generateDraft($ownerA->id, '2026-09-01', '2026-09-30'));
    $stmtB = $svc->finalize($svc->generateDraft($ownerB->id, '2026-09-01', '2026-09-30'));
    $payoutA = $pay->createFromStatement($stmtA);
    $payoutB = $pay->createFromStatement($stmtB);

    expect(Payout::where('owner_id', $ownerA->id)->pluck('id')->all())->toBe([$payoutA->id])
        ->and(Payout::where('owner_id', $ownerA->id)->pluck('id')->contains($payoutB->id))->toBeFalse()
        ->and($payoutA->amount_minor)->not->toBe($payoutB->amount_minor); // 17000 vs 25500
});
