<?php

use App\Models\MaintenanceTicket;
use App\Models\OwnerExpense;
use App\Models\OwnerLedgerEntry;
use App\Services\MaintenanceService;
use App\Services\OwnerStatementService;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Maintenance module · Step 3 — cost → owner expense through the CERTIFIED path. Resolving a costed ticket
// on an OWNED unit posts a deductible owner_expense (source = the ticket) via OwnerExpenseService →
// owner_ledger deduction line → onto the statement. Operator-owned = cost on the ticket only, NO expense.
// Corrections reverse append-only; a cost in a locked period lands as a next-period adjustment.

function maintTicket(array $s, array $overrides = []): MaintenanceTicket
{
    return app(MaintenanceService::class)->open(array_merge([
        'unit_id' => $s['units'][0]->id,
        'title' => 'Boiler repair',
        'category' => 'appliance',
    ], $overrides));
}

// ── OWNED unit: costed resolve → one owner_expense (source=ticket) → deduction line, reconciles ──
it('posts a deductible owner expense via the certified path for an owned unit', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);          // owner + 15% agreement; unit now owned
    $ticket = maintTicket($s);

    app(MaintenanceService::class)->resolve($ticket, Money::of(12000, 'EUR'));

    app(TenantContext::class)->set($s['org']->id);
    $expense = OwnerExpense::query()->where('source_type', 'maintenance_ticket')->where('source_id', $ticket->id)->firstOrFail();
    expect(OwnerExpense::query()->where('source_id', $ticket->id)->count())->toBe(1)   // exactly one
        ->and($expense->owner_id)->toBe($owner->id)
        ->and($expense->category)->toBe('maintenance')
        ->and($expense->amount_minor)->toBe(12000)
        ->and($expense->deductible)->toBeTrue();

    // A real owner_ledger deduction line (the certified path — never a parallel write).
    $line = lineFor($owner->id, 'expense');
    expect($line)->not->toBeNull()
        ->and($line->amount_minor)->toBe(12000)
        ->and($line->direction)->toBe('debit')
        ->and(ownerNet($owner->id))->toBe(-12000);   // signed net = the deduction, to the cent

    // It reconciles onto the owner's statement (the cost reduces the payable by exactly 12000). The
    // expense is dated on resolution (today), so the statement covering today claims it.
    $today = now();
    $statement = app(OwnerStatementService::class)->generateDraft(
        $owner->id, $today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString(),
    );
    expect(abs((int) $statement->expenses_minor))->toBe(12000);
});

// ── OPERATOR-owned unit: cost recorded on the ticket, NO owner expense ────────────────────────────
it('records the cost on the ticket but creates no owner expense for an operator-owned unit', function () {
    $s = seedScenario(1);   // unit has no owner_id → operator-owned
    $ticket = maintTicket($s);

    app(MaintenanceService::class)->resolve($ticket, Money::of(9000, 'EUR'));

    expect($ticket->refresh()->cost_minor)->toBe(9000)   // the operator's cost is recorded, explicitly
        ->and($ticket->currency)->toBe('EUR');
    app(TenantContext::class)->set($s['org']->id);
    expect(OwnerExpense::query()->where('source_id', $ticket->id)->count())->toBe(0)   // NO owner expense
        ->and(OwnerLedgerEntry::query()->count())->toBe(0);                            // nothing in owner_ledger
});

// ── Append-only correction: reverse + re-post (original ledger line untouched) ────────────────────
it('reverses append-only and re-posts when a resolved ticket cost is corrected', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $ticket = app(MaintenanceService::class)->resolve(maintTicket($s), Money::of(12000, 'EUR'));

    app(TenantContext::class)->set($s['org']->id);
    $original = lineFor($owner->id, 'expense');
    expect(ownerNet($owner->id))->toBe(-12000);

    app(MaintenanceService::class)->correctCost($ticket, Money::of(15000, 'EUR'));

    expect(OwnerLedgerEntry::query()->find($original->id)->amount_minor)->toBe(12000)          // original UNTOUCHED
        ->and(OwnerLedgerEntry::query()->where('reverses_id', $original->id)->exists())->toBeTrue()   // reversing line
        ->and(ownerNet($owner->id))->toBe(-15000)                                              // −12000 +12000 −15000
        ->and($ticket->refresh()->cost_minor)->toBe(15000)
        ->and(OwnerExpense::query()->where('source_id', $ticket->id)->whereNull('voided_at')->count())->toBe(1);   // one live expense
});

// ── Void the charge (correct to none) → reversal only, net back to zero ──────────────────────────
it('reverses the expense append-only when a resolved ticket cost is voided', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $ticket = app(MaintenanceService::class)->resolve(maintTicket($s), Money::of(12000, 'EUR'));

    app(MaintenanceService::class)->correctCost($ticket, null);

    app(TenantContext::class)->set($s['org']->id);
    expect(ownerNet($owner->id))->toBe(0)                                                       // −12000 + 12000 reversal
        ->and(OwnerExpense::query()->where('source_id', $ticket->id)->whereNull('voided_at')->count())->toBe(0)
        ->and($ticket->refresh()->cost_minor)->toBeNull();
});

// ── Period-lock: a cost dated in a sealed period lands as a next-period adjustment ───────────────
it('lands a cost in a locked period as a prior-period adjustment via the lock guard', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);

    // Seal the CURRENT period (contains today = the resolve/expense date) by finalizing its statement.
    $svc = app(OwnerStatementService::class);
    $today = now();
    $svc->finalize($svc->generateDraft($owner->id, $today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()));

    // Now a maintenance cost dated today — its period is already locked.
    app(MaintenanceService::class)->resolve(maintTicket($s), Money::of(12000, 'EUR'));

    app(TenantContext::class)->set($s['org']->id);
    $line = OwnerLedgerEntry::query()->where('owner_id', $owner->id)->where('entry_type', 'expense')->whereNull('reverses_id')->firstOrFail();
    expect($line->prior_period_adjustment)->toBeTrue();   // carried to the next statement, sealed one untouched
});

// ── Owner isolation: the expense lands on the ticket's owner only ────────────────────────────────
it('attributes the expense to the correct owner only', function () {
    $s = seedScenario(2);
    $ownerA = ownUnit($s['units'][0]);
    $ownerB = ownUnit($s['units'][1]);

    app(MaintenanceService::class)->resolve(maintTicket($s, ['unit_id' => $s['units'][0]->id]), Money::of(7000, 'EUR'));

    app(TenantContext::class)->set($s['org']->id);
    expect(ownerNet($ownerA->id))->toBe(-7000)   // owner A charged
        ->and(ownerNet($ownerB->id))->toBe(0);    // owner B untouched
});
