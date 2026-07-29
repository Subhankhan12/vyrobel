<?php

use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\Payout;
use App\Models\PayoutRun;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Services\PayoutRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// THE AUTOMATED PAYOUT ENGINE — Phase 1 (run model + state machine, no Stripe; the manual rail).
// ⚠️ TEST-MODE: nothing here moves real money (the manual rail records; PayoutEngineMode = 'test').
// The engine ORCHESTRATES the certified OwnerPayoutService; it never reimplements amount/state logic.
// (ownUnit / postRoom / ownerNet from tests/Support/owner_helpers.php.)

/** A finalized statement with a known positive net for an owner (engine fixtures). */
function enginePayable(array $s, Owner $owner, int $unitIndex = 0, int $roomMinor = 20000): void
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    $svc = app(OwnerStatementService::class);
    $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
}

/** Σ amount_minor of an owner's payouts in the given statuses. */
function payoutSum(string $ownerId, array $statuses): int
{
    return (int) Payout::query()->where('owner_id', $ownerId)->whereIn('status', $statuses)->sum('amount_minor');
}

// ── 1. Creates + disburses from a finalized statement for the exact reconciling net (#17) ──────────
it('runs the engine: creates a payout for the reconciling net and disburses it, tallying the run', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);          // 15% commission, tax_id set (factory) → not held
    enginePayable($s, $owner);                 // net 17000 (20000 − 15%)

    $run = app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL);

    $payout = Payout::query()->where('owner_id', $owner->id)->firstOrFail();

    // Amount == the reconciling statement net, to the cent (#17); manual rail → paid; statement flipped.
    expect($payout->amount_minor)->toBe(17000)
        ->and($payout->amount_minor)->toBe(ownerNet($owner->id))       // == ledger net
        ->and($payout->status)->toBe('paid')
        ->and($payout->payout_run_id)->toBe($run->id)                  // linked to the run
        ->and($payout->statement->status)->toBe('paid');

    // The run tallies reconcile to the payouts, and it ran in TEST MODE.
    expect($run->status)->toBe(PayoutRun::STATUS_COMPLETED)
        ->and($run->mode)->toBe('test')
        ->and($run->owners_selected)->toBe(1)
        ->and($run->payouts_created)->toBe(1)
        ->and($run->payouts_paid)->toBe(1)
        ->and($run->payouts_held)->toBe(0)
        ->and($run->totals)->toBe(['EUR' => 17000]);
});

// ── 2. A tax-incomplete owner is GENUINELY HELD (no money moves), then auto-releases on a later run ─
it('holds a payout when the owner has no tax details, then releases + pays it once they do', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    enginePayable($s, $owner);
    $owner->update(['tax_id' => null]);        // no tax details on file → the tax-hold

    $run1 = app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL, null, 'test:hold-1');

    $payout = Payout::query()->where('owner_id', $owner->id)->firstOrFail();

    // Held — a REAL persisted state; nothing disbursed; the statement is NOT paid.
    expect($payout->status)->toBe('held')
        ->and($payout->held_at)->not->toBeNull()
        ->and($payout->paid_at)->toBeNull()
        ->and($payout->statement->status)->toBe('finalized')
        ->and($run1->payouts_held)->toBe(1)
        ->and($run1->payouts_paid)->toBe(0)
        ->and($run1->totals)->toBe([]);        // no money moved

    // Owner completes their tax details → a later run auto-releases + pays it (no re-creation).
    $owner->update(['tax_id' => '999888777']);
    $run2 = app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL, null, 'test:hold-2');

    expect($payout->fresh()->status)->toBe('paid')
        ->and($payout->fresh()->statement->status)->toBe('paid')
        ->and($run2->payouts_created)->toBe(0)                          // released, not re-created
        ->and($run2->payouts_paid)->toBe(1)
        ->and(Payout::query()->where('owner_id', $owner->id)->count())->toBe(1); // exactly one payout, ever
});

// ── 3. Idempotent — a same-key re-run never double-pays; a paid statement is never re-selected ─────
it('never double-pays: a same-key re-run is a no-op and a paid statement is not re-created', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    enginePayable($s, $owner);

    $engine = app(PayoutRunService::class);
    $run1 = $engine->run(PayoutRun::TRIGGER_SCHEDULED, null, 'scheduled:2026-09-03');
    $run2 = $engine->run(PayoutRun::TRIGGER_SCHEDULED, null, 'scheduled:2026-09-03'); // same key → resume, no-op

    expect($run2->id)->toBe($run1->id)
        ->and(Payout::query()->where('owner_id', $owner->id)->count())->toBe(1);

    // A fresh run on a different day: the statement is already paid → not re-selected, no second payout.
    $engine->run(PayoutRun::TRIGGER_SCHEDULED, null, 'scheduled:2026-10-03');
    expect(Payout::query()->where('owner_id', $owner->id)->count())->toBe(1);
});

// ── 4. Owner isolation — each owner gets their own payout for their own net ────────────────────────
it('creates a correctly-scoped payout per owner across owners in one run', function () {
    $s = seedScenario(2);
    $ownerA = ownUnit($s['units'][0]);
    $ownerB = ownUnit($s['units'][1]);
    enginePayable($s, $ownerA, 0, 20000);      // net 17000
    enginePayable($s, $ownerB, 1, 30000);      // net 25500

    $run = app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL);

    $a = Payout::query()->where('owner_id', $ownerA->id)->firstOrFail();
    $b = Payout::query()->where('owner_id', $ownerB->id)->firstOrFail();

    expect($a->amount_minor)->toBe(ownerNet($ownerA->id))
        ->and($b->amount_minor)->toBe(ownerNet($ownerB->id))
        ->and($a->owner_id)->not->toBe($b->owner_id)
        ->and($run->owners_selected)->toBe(2)
        ->and($run->totals)->toBe(['EUR' => 17000 + 25500]);
});

// ── 5. The clearing identity holds after a run (#17): Available + Held + In transit + Paid == Net ──
it('preserves the clearing identity per owner after a run', function () {
    $s = seedScenario(2);
    $paidOwner = ownUnit($s['units'][0]);
    $heldOwner = ownUnit($s['units'][1]);
    enginePayable($s, $paidOwner, 0);
    enginePayable($s, $heldOwner, 1);
    $heldOwner->update(['tax_id' => null]);    // this one holds

    app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL);

    foreach ([$paidOwner, $heldOwner] as $owner) {
        $available = (int) OwnerLedgerEntry::query()->where('owner_id', $owner->id)
            ->whereNull('statement_id')->get()->sum(fn (OwnerLedgerEntry $e) => $e->signedMinor());
        $held = payoutSum($owner->id, ['held']);
        $inTransit = payoutSum($owner->id, ['pending', 'processing']);
        $paid = payoutSum($owner->id, ['paid']);

        // Available + On hold + In transit + Paid == Net position, to the cent (#17).
        expect($available + $held + $inTransit + $paid)->toBe(ownerNet($owner->id));
    }

    // And the shape is what we expect: one paid, one held.
    expect(payoutSum($paidOwner->id, ['paid']))->toBe(ownerNet($paidOwner->id))
        ->and(payoutSum($heldOwner->id, ['held']))->toBe(ownerNet($heldOwner->id));
});
