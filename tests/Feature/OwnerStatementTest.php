<?php

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Services\OwnerAttributionService;
use App\Services\OwnerExpenseService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Phase 3 · Module 1 Step 4 — owner statements: a period-locked, append-only PROJECTION of
// owner_ledger. (ownUnit/postRoom/ownerNet/lineFor from tests/Support/owner_helpers.php.)

/** Ledger net for an owner across a set of lines (all claimed by a statement, say). */
function ledgerNet(string $ownerId): int
{
    return OwnerLedgerEntry::where('owner_id', $ownerId)->get()
        ->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);
}

function componentLinesSum(OwnerStatement $s): int
{
    return (int) $s->lines()->where('line_type', '!=', 'net')->sum('amount_minor');
}

// ── 1. Projection correctness: net == sum of lines == ledger net; unverified surfaces ───────────
it('projects the period ledger into gross → commission → expenses → net, reconciling to the cent', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]); // 15% commission, direct booking → OTA cost 0 unverified
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only collected revenue (②+accrual)
    app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => $s['units'][0]->id, 'category' => 'cleaning', 'description' => 'Clean',
        'amount_minor' => 5000, 'currency' => 'EUR', 'expense_date' => '2026-09-03',
        'approve_now' => true,
    ]);

    $statement = app(OwnerStatementService::class)->generateDraft($owner->id, '2026-09-01', '2026-09-30');

    expect($statement->gross_minor)->toBe(20000)
        ->and($statement->commission_minor)->toBe(-3000)
        ->and($statement->expenses_minor)->toBe(-5000)
        ->and($statement->ota_cost_minor)->toBe(0)
        ->and($statement->net_source_minor)->toBe(12000)          // 20000 − 3000 − 5000
        ->and($statement->net_payable_minor)->toBe(12000)         // EUR == EUR, no conversion
        ->and(componentLinesSum($statement))->toBe(12000)         // sum of component lines == net
        ->and($statement->net_source_minor)->toBe(ledgerNet($owner->id)) // == ledger net, to the cent
        ->and($statement->has_unverified)->toBeTrue();

    // The unverified OTA line surfaces as pending even though it is 0.
    $otaLine = $statement->lines()->where('line_type', 'ota_cost')->first();
    expect($otaLine)->not->toBeNull()->and($otaLine->unverified)->toBeTrue();
});

// ── 1b. WAVE C · GATE 4 — a real payment/ops fee projects into its bucket, reconciling to the cent ─
it('projects a real payment & ops fee into the "Payment & ops fees" bucket, reconciling to the cent', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]); // 15% commission
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only collected revenue (②+accrual) // gross 20000, commission −3000

    // A REAL payment-processing fee posted to the owner ledger — what a live Stripe / channel fee writer
    // emits. It is an ordinary signed ledger line, so it flows through the projection like every bucket.
    OwnerLedgerEntry::create([
        'organization_id' => $owner->organization_id,
        'owner_id' => $owner->id,
        'unit_id' => $s['units'][0]->id,
        'entry_type' => 'payment_fee',
        'direction' => 'debit',
        'amount_minor' => 450,
        'currency' => 'EUR',
        'description' => 'Card processing fee',
        'service_date' => '2026-09-10',
    ]);

    $statement = app(OwnerStatementService::class)->generateDraft($owner->id, '2026-09-01', '2026-09-30');

    expect($statement->payment_fee_minor)->toBe(-450)
        ->and($statement->net_source_minor)->toBe(16550)                  // 20000 − 3000 − 450
        ->and($statement->net_source_minor)->toBe(ledgerNet($owner->id))  // == ledger net, to the cent
        ->and(componentLinesSum($statement))->toBe(16550);                // component lines sum to net

    // The real "Payment & ops fees" line surfaces with the fee.
    $feeLine = $statement->lines()->where('line_type', 'payment_fee')->first();
    expect($feeLine)->not->toBeNull()
        ->and($feeLine->description)->toBe('Payment & ops fees')
        ->and($feeLine->amount_minor)->toBe(-450);
});

// ── 1c. WAVE C · GATE 4 — no fee → the line is OMITTED (never a fabricated €0.00) ──────────────────
it('omits the "Payment & ops fees" line when there is no real fee', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only collected revenue (②+accrual)

    $statement = app(OwnerStatementService::class)->generateDraft($owner->id, '2026-09-01', '2026-09-30');

    expect($statement->payment_fee_minor)->toBe(0)
        ->and($statement->lines()->where('line_type', 'payment_fee')->exists())->toBeFalse();
});

// ── 2. Draft is idempotent (identical numbers); finalizing locks + stamps ───────────────────────
it('regenerates a draft idempotently and locks + stamps on finalize', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only collected revenue (②+accrual)

    $svc = app(OwnerStatementService::class);
    $d1 = $svc->generateDraft($owner->id, '2026-09-01', '2026-09-30');
    $net1 = $d1->net_source_minor;
    $lines1 = $d1->lines()->count();
    $d2 = $svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'); // re-run

    expect($d2->id)->toBe($d1->id)
        ->and($d2->net_source_minor)->toBe($net1)
        ->and($d2->lines()->count())->toBe($lines1);

    $final = $svc->finalize($d2->fresh());
    expect($final->status)->toBe('finalized')
        ->and($final->locked)->toBeTrue()
        ->and($final->sequence_no)->toBe(1)
        ->and(OwnerLedgerEntry::where('owner_id', $owner->id)->whereNull('statement_id')->count())->toBe(0); // all claimed

    // A locked statement can never be regenerated.
    expect(fn () => $svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'))
        ->toThrow(RuntimeException::class);
});

// ── 3. POST-LOCK ADJUSTMENT carries forward; locked statement never moves; gap-free ─────────────
it('carries a post-lock refund onto the next statement as a prior-period adjustment, gap-free', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    $attr = app(OwnerAttributionService::class);
    $attr->attributeReservation($reservation);

    $svc = app(OwnerStatementService::class);
    // Period 1 (September) — finalize + lock.
    $sept = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
    $septNet = $sept->net_source_minor; // 17000 (20000 − 3000 commission)

    // AFTER lock: the booking is cancelled/refunded → whole-transaction reversal, dated in September.
    $attr->reverseReservation($reservation);

    // The reversal lines are flagged prior-period and NOT yet claimed.
    $reversals = OwnerLedgerEntry::where('owner_id', $owner->id)->whereNotNull('reverses_id')->get();
    expect($reversals)->not->toBeEmpty()
        ->and($reversals->every(fn ($e) => $e->prior_period_adjustment === true))->toBeTrue()
        ->and($reversals->every(fn ($e) => $e->statement_id === null))->toBeTrue();

    // The LOCKED September statement did not move.
    expect($sept->fresh()->net_source_minor)->toBe($septNet)
        ->and($sept->fresh()->locked)->toBeTrue();

    // Period 2 (October) — the reversal surfaces as a labeled prior-period adjustment.
    $oct = $svc->finalize($svc->generateDraft($owner->id, '2026-10-01', '2026-10-31'));
    $adjLine = $oct->lines()->where('line_type', 'prior_period_adjustment')->first();

    expect($oct->adjustments_minor)->toBe(-$septNet)             // reverses the prior period's net
        ->and($oct->net_source_minor)->toBe(-$septNet)
        ->and($adjLine)->not->toBeNull()
        ->and($adjLine->amount_minor)->toBe(-$septNet)
        ->and($oct->sequence_no)->toBe(2);

    // Gap-free: every ledger line is now claimed by exactly one statement.
    expect(OwnerLedgerEntry::where('owner_id', $owner->id)->whereNull('statement_id')->count())->toBe(0);
    $septIds = OwnerLedgerEntry::where('statement_id', $sept->id)->pluck('id');
    $octIds = OwnerLedgerEntry::where('statement_id', $oct->id)->pluck('id');
    expect($septIds->intersect($octIds))->toBeEmpty(); // no line claimed twice
});

// ── 4. Cross-currency: fx_rate/fx_source recorded, converted net correct; same-currency unaffected
it('records fx and converts the net when payout currency differs from the ledger currency', function () {
    $s = seedScenario(1);
    $owner = Owner::factory()->create(['payout_currency' => 'USD']); // paid in USD; bookings are EUR
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only collected revenue (②+accrual)

    $statement = app(OwnerStatementService::class)->generateDraft($owner->id, '2026-09-01', '2026-09-30', '1.1000000000', 'ECB 2026-09-30');

    expect($statement->source_currency)->toBe('EUR')
        ->and($statement->currency)->toBe('USD')
        ->and($statement->net_source_minor)->toBe(17000)                 // in EUR (== ledger net)
        ->and((int) round((float) $statement->fx_rate * 10000000000))->toBe(11000000000) // 1.10 recorded
        ->and($statement->fx_source)->toBe('ECB 2026-09-30')
        ->and($statement->net_payable_minor)->toBe(18700);              // round(17000 * 1.10)
});

it('does not convert when payout currency matches the ledger currency', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]); // EUR owner, EUR booking
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only collected revenue (②+accrual)

    $statement = app(OwnerStatementService::class)->generateDraft($owner->id, '2026-09-01', '2026-09-30');

    expect($statement->fx_rate)->toBeNull()
        ->and($statement->net_payable_minor)->toBe($statement->net_source_minor);
});

// ── 5. Owner isolation: a statement reflects only its owner's ledger ────────────────────────────
it('projects only the statement owner’s ledger, never another owner’s', function () {
    $s = seedScenario(2);
    $ownerA = ownUnit($s['units'][0]);
    $ownerB = ownUnit($s['units'][1]);
    $resA = book($s, ['unit_id' => $s['units'][0]->id]);
    $resB = book($s, ['unit_id' => $s['units'][1]->id]);
    postRoom($resA, 20000);
    postRoom($resB, 30000);
    app(OwnerAttributionService::class)->attributeReservation($resA);
    app(OwnerAttributionService::class)->attributeReservation($resB);
    collectFolio($resA);   // ⚠️ both guests PAID — this test is about owner ISOLATION, not collection
    collectFolio($resB);

    $stmtA = app(OwnerStatementService::class)->finalize(
        app(OwnerStatementService::class)->generateDraft($ownerA->id, '2026-09-01', '2026-09-30')
    );

    // A's statement reflects only A's ledger; B's lines are untouched (unclaimed).
    expect($stmtA->net_source_minor)->toBe(ledgerNet($ownerA->id))
        ->and($stmtA->owner_id)->toBe($ownerA->id)
        ->and(OwnerLedgerEntry::where('statement_id', $stmtA->id)->where('owner_id', $ownerB->id)->count())->toBe(0)
        ->and(OwnerLedgerEntry::where('owner_id', $ownerB->id)->whereNull('statement_id')->count())
        ->toBe(OwnerLedgerEntry::where('owner_id', $ownerB->id)->count()); // none of B's claimed by A
});
