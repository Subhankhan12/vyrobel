<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerExpense;
use App\Models\User;
use App\Services\MaintenanceService;
use App\Services\OwnerExpenseService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Owner Expenses (exact-match pass · invariant #59) — the owner-scoped view of the CERTIFIED owner-expense
// backend. Locks its PRESENTATION guarantees: live expense data renders; the `deductible` TRI-STATE
// (true / false / NULL) renders as three honest states and NULL is never coerced to false; a voided
// expense is shown but excluded from the totals; a maintenance-sourced expense shows its real source; the
// REAL unit column + filter render (vendor now a REAL column too); the KPIs ("Spent this year" / "Pending
// review" / "Charged to payouts") are Σ of the real rows ("Spent" year-scoped); the DERIVED category total
// reconciles TO THE CENT; the CSV export is owner-scoped + CAP_EXPORTS-gated; "Need something fixed?" is a
// REAL relay into the owner↔operator channel (never an expense write); the design's owner approve/query
// workflow (NO backend) is never rendered; owner isolation holds (another owner's expenses invisible, a
// non-linked user fails closed).

/**
 * A linked owner on a unit, with the agreement that GOVERNS deductibility:
 *   • it marks the 'other' category as the operator's cost  → deductible = FALSE
 *   • it only takes effect on 2026-01-01                    → anything earlier is un-attributable
 *                                                             (flag=no_agreement, deductible = NULL)
 */
function expensesScreenOwner(array $s, int $unitIndex, string $name): array
{
    $user = User::factory()->create(['name' => $name]);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);

    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id,
        'commission_pct' => 15.00,
        'effective_from' => '2026-01-01',
        'effective_to' => null,
        'attribution_rules' => ['non_deductible_categories' => ['other']],
    ]);

    return compact('user', 'owner', 'unit');
}

/** Record through the CERTIFIED service (called, never modified) — it resolves deductibility itself. */
function recordExpenseVia(string $unitId, string $category, string $description, int $minor, string $date): OwnerExpense
{
    return app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => $unitId,
        'category' => $category,
        'description' => $description,
        'amount_minor' => $minor,
        'currency' => 'EUR',
        'expense_date' => $date,
    ]);
}

// ── 1. Real rows · the deductible TRI-STATE · voided excluded · maintenance source · reconciliation ──
it('renders real expenses with the deductible tri-state, badges voided rows out of the totals, and invents no approval workflow', function () {
    $s = seedScenario(1);
    $a = expensesScreenOwner($s, 0, 'Amara Okafor');
    $unitId = (string) $a['unit']->id;

    // Every fixture goes through the certified service — the AGREEMENT decides deductibility, not us.
    $deductible = recordExpenseVia($unitId, 'cleaning', 'Turnover clean', 5000, '2026-09-03');   // → TRUE
    $operatorCost = recordExpenseVia($unitId, 'other', 'Welcome basket', 3300, '2026-09-05');    // → FALSE
    $unresolved = recordExpenseVia($unitId, 'cleaning', 'Deep clean', 2500, '2025-06-01');       // → NULL

    // A void is an append-only REVERSAL — the row survives, its money does not.
    $voided = recordExpenseVia($unitId, 'cleaning', 'Duplicate clean', 9900, '2026-09-07');
    app(OwnerExpenseService::class)->voidExpense($voided);

    // A maintenance-sourced expense via the certified MaintenanceService → OwnerExpenseService hook
    // (a non-OOS ticket, so nothing touches availability).
    $ticket = app(MaintenanceService::class)->open(['unit_id' => $unitId, 'title' => 'Boiler repair']);
    app(MaintenanceService::class)->resolve($ticket, moneyEur(12000));                            // → TRUE, source = ticket

    // Finalize a statement — `owner_ledger.statement_id` is stamped at FINALIZE only, so this is what
    // turns "Not yet on a statement" into a real period link.
    $svc = app(OwnerStatementService::class);
    $svc->finalize($svc->generateDraft((string) $a['owner']->id, '2026-09-01', '2026-09-30'));

    // The fixture really exercises the TRI-STATE: ===true / ===false / ===null, never coerced.
    expect($deductible->deductible)->toBeTrue()
        ->and($operatorCost->deductible)->toBeFalse()
        ->and($unresolved->deductible)->toBeNull()
        ->and($unresolved->flag)->toBe('no_agreement')
        ->and($voided->refresh()->voided_at)->not->toBeNull();

    $response = $this->actingAs($a['user'])->get('/owner/expenses')
        ->assertOk()
        ->assertSee('Expense history')
        // REAL rows — description + amount straight off `owner_expenses`
        ->assertSee('Turnover clean')
        ->assertSee('€50.00')
        ->assertSee('Welcome basket')
        ->assertSee('€33.00')
        ->assertSee('Deep clean')
        ->assertSee('€25.00')
        ->assertSee('Boiler repair')
        ->assertSee('€120.00')
        // the REAL tri-state — three distinct rendered states
        ->assertSee('data-deductible="yes"', false)
        ->assertSee('data-deductible="no"', false)
        ->assertSee('data-deductible="pending"', false)
        ->assertSee('Deductible')
        ->assertSee('Not deductible')
        ->assertSee('Pending review')
        // the REAL `flag` says WHY the null one is unresolved — surfaced, not swallowed
        ->assertSee('No management agreement covers this date')
        // the voided row is SHOWN and badged (append-only), while its €99.00 leaves every total
        ->assertSee('Duplicate clean')
        ->assertSee('€99.00')
        ->assertSee('data-voided="1"', false)
        ->assertSee('Voided')
        // the maintenance-sourced expense shows its REAL polymorphic source
        ->assertSee('Maintenance ticket')
        // the statement an attributed line landed on + the honest "not there yet" for an unattributed one
        ->assertSee('September 2026')
        ->assertSee('Not yet on a statement')
        // the REAL unit column + filter (owner_expenses.unit_id → name ?? code)
        ->assertSee('Unit')
        ->assertSee('All units')
        ->assertSee((string) ($a['unit']->name ?? $a['unit']->code))
        // the exact-match KPIs — "Spent this year" + "Charged to payouts" (no "Total expenses")
        ->assertSee('Spent this year')
        ->assertSee('Charged to payouts')
        // ⚠️ the design's owner APPROVE / QUERY workflow has NO backend (no approval state machine, no
        // approval columns, no query channel) — REFUSED, never rendered. "Awaiting your approval" too.
        ->assertDontSee('Approve')
        ->assertDontSee('Needs your approval')
        ->assertDontSee('Awaiting your approval')
        ->assertDontSee('Query sent')
        ->assertDontSee('awaiting reply')
        ->assertDontSee('charged to next payout');

    // ── Reconciliation TO THE CENT against the source rows ────────────────────────────────────────
    // The DERIVED "Cleaning" total = Σ the owner's real non-voided cleaning rows (5000 + 2500), with
    // the reversed €99.00 excluded.
    $cleaningMinor = (int) OwnerExpense::query()
        ->where('owner_id', $a['owner']->id)
        ->where('category', 'cleaning')
        ->whereNull('voided_at')
        ->sum('amount_minor');

    // The DERIVED "Deductible" KPI = Σ the real rows with deductible === true, voided excluded.
    $deductibleMinor = (int) OwnerExpense::query()
        ->where('owner_id', $a['owner']->id)
        ->where('deductible', true)
        ->whereNull('voided_at')
        ->sum('amount_minor');

    // And the "Total expenses" KPI = Σ every non-voided row (all EUR here — never a cross-currency sum).
    $totalMinor = (int) OwnerExpense::query()
        ->where('owner_id', $a['owner']->id)
        ->whereNull('voided_at')
        ->sum('amount_minor');

    // "Spent this year" scopes to the current calendar year → the 2025 Deep clean (€25.00) is excluded:
    // Turnover €50 + Welcome basket €33 + Boiler €120 = €203.00 (Duplicate €99 voided is out of every total).
    $spentThisYearMinor = (int) OwnerExpense::query()
        ->where('owner_id', $a['owner']->id)
        ->whereNull('voided_at')
        ->whereYear('expense_date', (int) now()->year)
        ->sum('amount_minor');

    expect($cleaningMinor)->toBe(7500)
        ->and($deductibleMinor)->toBe(17000)
        ->and($totalMinor)->toBe(22800)
        ->and($spentThisYearMinor)->toBe(20300);

    $response
        ->assertSee('€75.00')     // Cleaning by-category total — 9900 voided excluded to the cent
        ->assertSee('€170.00')    // "Charged to payouts" KPI = Σ deductible this year
        ->assertSee('€203.00')    // "Spent this year" KPI = Σ live 2026 rows (2025 Deep clean excluded)
        ->assertSee('€228.00')    // by-category block TOTAL (all-time, not year-scoped)
        // The per-currency note renders as prose, never as raw Blade (see assertNoRawBlade).
        ->assertSee('Totals exclude voided expenses.');

    // ── Polish Batch 6 — the "By category" PROPORTIONAL BARS (shared .vy-track). Each category's bar width is
    // its SHARE of the block total (÷total, the same basis as the statement by-unit split), over the REAL rows:
    // cleaning €75 + other €33 + maintenance €120 = €228 → 33% + 14% + 53%. Deterministic + reconciles to 100%.
    $response
        ->assertSee('vy-track')             // the shared proportional-bar component, not a one-off
        ->assertSee('width:53%', false)     // Maintenance €120 / €228 — the largest share → widest bar
        ->assertSee('width:33%', false)     // Cleaning €75 / €228
        ->assertSee('width:14%', false)     // Other €33 / €228
        ->assertSee('53% of total');        // the human-readable proportion note

    assertNoRawBlade($response);
});

// ── Polish Batch 6 — a category with no real expense does NOT appear (#39), and a lone category is a full bar ──
it('shows only categories with real expenses (no €0.00 filler) and renders a lone category as a full-width bar', function () {
    $s = seedScenario(1);
    $a = expensesScreenOwner($s, 0, 'Amara Okafor');

    // ONE real category only — maintenance. Cleaning and Other have NO rows for this owner.
    recordExpenseVia((string) $a['unit']->id, 'maintenance', 'Boiler service', 10000, '2026-09-03');

    $this->actingAs($a['user'])->get('/owner/expenses')
        ->assertOk()
        ->assertSee('width:100%', false)        // the lone category is the whole total → full bar (reconciles)
        ->assertSee('100% of total')
        ->assertDontSee('0 expenses');          // #39 — the empty categories (cleaning/other) render no "0 expenses" filler row
});

// ── 1c. VENDOR (GATE 3) is a REAL column — shown when captured, honest "—" when not, exported too ──
it('renders the real expense vendor when captured and an honest dash when not, and exports it', function () {
    $s = seedScenario(1);
    $a = expensesScreenOwner($s, 0, 'Amara Okafor');
    $unitId = (string) $a['unit']->id;

    // One cost WITH a vendor, one WITHOUT (vendor stays null → honest "—", never fabricated #39).
    app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => $unitId, 'category' => 'cleaning', 'description' => 'Turnover clean',
        'vendor' => 'SparkleCo Cleaning', 'amount_minor' => 5000, 'currency' => 'EUR', 'expense_date' => '2026-09-03',
    ]);
    recordExpenseVia($unitId, 'other', 'Welcome basket', 3300, '2026-09-05');   // no vendor

    $this->actingAs($a['user'])->get('/owner/expenses')
        ->assertOk()
        ->assertSee('Vendor')                 // the real column header
        ->assertSee('SparkleCo Cleaning');    // the captured supplier

    // The CSV carries a Vendor column with the captured value (owner-scoped, EXPORTS-gated elsewhere).
    $csv = $this->actingAs($a['user'])->get(route('owner.expenses.export'))->assertOk()->streamedContent();
    expect($csv)->toContain('Vendor')->toContain('SparkleCo Cleaning');
});

// ── 2. Owner isolation — A sees only A's expenses; a non-linked user fails closed ─────────────────
it('scopes expenses to the owner, hides other owners, and fails closed for a non-linked user', function () {
    $s = seedScenario(2);
    $a = expensesScreenOwner($s, 0, 'Amara Okafor');
    $b = expensesScreenOwner($s, 1, 'Bruno Castellani');

    recordExpenseVia((string) $a['unit']->id, 'cleaning', 'Turnover clean', 5000, '2026-09-03');
    recordExpenseVia((string) $b['unit']->id, 'cleaning', 'Chimney sweep', 44400, '2026-09-04');   // €444.00 — distinctive

    $this->actingAs($a['user'])->get('/owner/expenses')
        ->assertOk()
        ->assertSee('Turnover clean')
        ->assertSee('€50.00')
        ->assertDontSee('Chimney sweep')
        ->assertDontSee('€444.00');

    // A non-linked authenticated member resolves to an EMPTY owner scope → nothing, never all owners.
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get('/owner/expenses')
        ->assertOk()
        ->assertSee('No expenses')
        ->assertDontSee('Turnover clean')
        ->assertDontSee('Chimney sweep')
        ->assertDontSee('€50.00')
        ->assertDontSee('€444.00');
});

// ── 3. Expense CSV — owner-scoped, EXPORTS-gated, never another owner's rows ───────────────────────
it('exports the expense CSV owner-scoped and EXPORTS-gated', function () {
    $s = seedScenario(2);
    $a = expensesScreenOwner($s, 0, 'Amara Okafor');
    $b = expensesScreenOwner($s, 1, 'Bruno Castellani');

    recordExpenseVia((string) $a['unit']->id, 'cleaning', 'Turnover clean', 5000, '2026-09-03');    // A
    recordExpenseVia((string) $b['unit']->id, 'cleaning', 'Chimney sweep', 44400, '2026-09-04');    // B (distinctive)

    $csv = $this->actingAs($a['user'])->get(route('owner.expenses.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=utf-8')
        ->streamedContent();

    expect($csv)->toContain('Deductible')       // header column (the tri-state, exported honestly)
        ->toContain('Turnover clean')           // A's real row
        ->not->toContain('Chimney sweep');      // never B's

    // EXPORTS capability: accountant may download; view-only may NOT.
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'accountant'))->get(route('owner.expenses.export'))->assertOk();
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'view_only'))->get(route('owner.expenses.export'))->assertForbidden();
});

// ── 4. "Need something fixed?" is a REAL relay (owner→operator message), never a fake expense write ──
it('offers a real repair relay into the owner→operator channel and writes no expense', function () {
    $s = seedScenario(1);
    $a = expensesScreenOwner($s, 0, 'Amara Okafor');

    // The page exposes a REAL relay endpoint (not a disabled "Soon" stub).
    $this->actingAs($a['user'])->get('/owner/expenses')
        ->assertOk()
        ->assertSee(route('owner.messages.start'))
        ->assertSee('Request a repair');

    $before = OwnerExpense::query()->count();

    // Posting it opens a real owner↔operator thread and writes NO owner expense (#39 — never a fake charge).
    $this->actingAs($a['user'])->post(route('owner.messages.start'), [
        'subject' => 'Repair request',
        'body' => 'The dishwasher is leaking in the apartment.',
    ])->assertRedirect();

    expect(OwnerExpense::query()->count())->toBe($before);
});
