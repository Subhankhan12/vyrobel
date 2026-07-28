<?php

use App\Models\HousekeepingTask;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Services\OwnerExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Phase 3 · Module 1 Step 3 — owner-attributable expenses. Deductible expenses post immutable
// deduction lines to owner_ledger; non-deductible ones are the operator's cost (recorded, not
// ledgered); unattributable ones are flagged, never silently attributed. (ownUnit/ownerNet/lineFor
// are shared helpers from OwnerAttributionTest.php.)

// ── 1. Deductible cleaning expense linked to a housekeeping_task → one ledger deduction ─────────
it('posts a deductible cleaning expense (from a housekeeping task) to the right owner and drops net', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $task = HousekeepingTask::create(['property_id' => $s['property']->id, 'unit_id' => $s['units'][0]->id, 'date' => '2026-09-03', 'type' => 'departure']);

    $expense = app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => $s['units'][0]->id, 'category' => 'cleaning', 'description' => 'Turnover clean',
        'amount_minor' => 5000, 'currency' => 'EUR', 'expense_date' => '2026-09-03', 'source' => $task,
        'approve_now' => true,
    ]);

    $line = lineFor($owner->id, 'expense');
    expect($expense->deductible)->toBeTrue()
        ->and($expense->source_type)->toBe('housekeeping_task')  // reuse, not a parallel cost log
        ->and($expense->source_id)->toBe($task->id)
        ->and($line)->not->toBeNull()
        ->and($line->direction)->toBe('debit')
        ->and($line->amount_minor)->toBe(5000)
        ->and($line->source_type)->toBe('owner_expense')
        ->and(ownerNet($owner->id))->toBe(-5000); // owner net drops by exactly the expense
});

// ── 2. Maintenance expense linked to a source record attributes to the correct owner/unit ───────
it('attributes a maintenance expense to the correct owner and unit via its source record', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);

    // No maintenance-ticket module exists yet (forward hook) — the unit is the existing source record.
    $expense = app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => $s['units'][0]->id, 'category' => 'maintenance', 'description' => 'Boiler repair',
        'amount_minor' => 12000, 'currency' => 'EUR', 'expense_date' => '2026-09-05', 'source' => $s['units'][0],
        'approve_now' => true,
    ]);

    $line = lineFor($owner->id, 'expense');
    expect($expense->owner_id)->toBe($owner->id)
        ->and($expense->unit_id)->toBe($s['units'][0]->id)
        ->and($expense->source_type)->toBe('unit')
        ->and($line->unit_id)->toBe($s['units'][0]->id)
        ->and($line->amount_minor)->toBe(12000)
        ->and(ownerNet($owner->id))->toBe(-12000);
});

// ── 3. Non-deductible expense is recorded but does NOT hit the owner ledger (operator cost) ─────
it('records a non-deductible expense without any owner-ledger line', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], ['attribution_rules' => ['non_deductible_categories' => ['maintenance']]]);

    $expense = app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => $s['units'][0]->id, 'category' => 'maintenance', 'description' => 'Owner-excluded repair',
        'amount_minor' => 9000, 'currency' => 'EUR', 'expense_date' => '2026-09-05',
        'approve_now' => true,
    ]);

    expect($expense->deductible)->toBeFalse()      // explicit operator cost, not omission
        ->and($expense->flag)->toBeNull()          // not a flag — it IS attributable, just not deductible
        ->and(OwnerLedgerEntry::where('owner_id', $owner->id)->where('entry_type', 'expense')->count())->toBe(0)
        ->and(ownerNet($owner->id))->toBe(0);
});

// ── 4. Voiding an expense reverses its ledger line (append-only, never edited); nets to zero ────
it('voids an expense by reversing its ledger line, append-only and idempotent', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $svc = app(OwnerExpenseService::class);

    $expense = $svc->recordExpense([
        'unit_id' => $s['units'][0]->id, 'category' => 'cleaning', 'description' => 'Clean',
        'amount_minor' => 5000, 'currency' => 'EUR', 'expense_date' => '2026-09-03',
        'approve_now' => true,
    ]);
    $original = lineFor($owner->id, 'expense');

    $svc->voidExpense($expense->refresh());

    $reversal = OwnerLedgerEntry::where('reverses_id', $original->id)->first();
    expect($reversal)->not->toBeNull()
        ->and($reversal->direction)->toBe('credit')      // mirror of the debit
        ->and($reversal->amount_minor)->toBe(5000)
        ->and($original->refresh()->amount_minor)->toBe(5000) // original untouched
        ->and($expense->refresh()->voided_at)->not->toBeNull()
        ->and(ownerNet($owner->id))->toBe(0);             // nets to zero

    // Idempotent: re-voiding creates nothing new.
    $before = OwnerLedgerEntry::where('owner_id', $owner->id)->count();
    $svc->voidExpense($expense->refresh());
    expect(OwnerLedgerEntry::where('owner_id', $owner->id)->count())->toBe($before);
});

// ── 5. Owner-less unit / no governing agreement → recorded + flagged, not attributed ────────────
it('records and flags (never attributes) an expense on an owner-less unit', function () {
    $s = seedScenario(1); // unit has no owner assigned

    $expense = app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => $s['units'][0]->id, 'category' => 'cleaning', 'description' => 'Clean',
        'amount_minor' => 5000, 'currency' => 'EUR', 'expense_date' => '2026-09-03',
        'approve_now' => true,
    ]);

    expect($expense->flag)->toBe('no_owner')
        ->and($expense->deductible)->toBeNull()
        ->and($expense->owner_id)->toBeNull()
        ->and(OwnerLedgerEntry::count())->toBe(0);
});

it('records and flags an expense when no governing agreement covers its date', function () {
    $s = seedScenario(1);
    $owner = Owner::factory()->create();
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'effective_from' => '2027-01-01']); // future

    $expense = app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => $s['units'][0]->id, 'category' => 'cleaning', 'description' => 'Clean',
        'amount_minor' => 5000, 'currency' => 'EUR', 'expense_date' => '2026-09-03',
        'approve_now' => true,
    ]);

    expect($expense->flag)->toBe('no_agreement')
        ->and($expense->owner_id)->toBe($owner->id)   // owner known, but no terms to attribute under
        ->and($expense->deductible)->toBeNull()
        ->and(OwnerLedgerEntry::where('owner_id', $owner->id)->count())->toBe(0);
});

// ── 6. Posting is idempotent (re-run creates no duplicate ledger line) ──────────────────────────
it('posts an expense idempotently', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $svc = app(OwnerExpenseService::class);

    $expense = $svc->recordExpense([
        'unit_id' => $s['units'][0]->id, 'category' => 'cleaning', 'description' => 'Clean',
        'amount_minor' => 5000, 'currency' => 'EUR', 'expense_date' => '2026-09-03',
        'approve_now' => true,
    ]);

    $countAfterRecord = OwnerLedgerEntry::where('owner_id', $owner->id)->count();
    $svc->postExpense($expense->refresh()); // re-post
    $svc->postExpense($expense->refresh());

    expect(OwnerLedgerEntry::where('owner_id', $owner->id)->count())->toBe($countAfterRecord)
        ->and($countAfterRecord)->toBe(1);
});
