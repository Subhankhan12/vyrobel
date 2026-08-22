<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerExpense;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Models\Payout;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerExpenseService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Owner\OwnerContext;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Phase 3 · Module 1 Step 6 — OWNER ISOLATION is a SECURITY boundary, tested like TenantIsolationTest.
// An owner sees ONLY their own rows; an empty scope FAILS CLOSED (nothing, never all owners); the
// operator side is unaffected; owner scope composes with tenant scope.

/** Build one linked owner (user + membership + unit + agreement + booking + statement + payout + expense). */
function linkedOwner(array $s, int $unitIndex, int $roomMinor): array
{
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $unit = $s['units'][$unitIndex];
    // Distinctive code: a 2-char seed code ("U1"/"U2") can collide with a substring of Livewire's random
    // component id and false-fail assertDontSee($code). Robustness only — the isolation coverage is unchanged.
    $unit->update(['owner_id' => $owner->id, 'code' => 'PORTALUNIT'.$unitIndex]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
    app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => $unit->id, 'category' => 'cleaning', 'description' => 'Clean',
        'amount_minor' => 3000, 'currency' => 'EUR', 'expense_date' => '2026-09-03',
        'approve_now' => true,
    ]);

    $stmtSvc = app(OwnerStatementService::class);
    $statement = $stmtSvc->finalize($stmtSvc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
    $payout = app(OwnerPayoutService::class)->createFromStatement($statement);

    return ['user' => $user, 'owner' => $owner, 'unit' => $unit, 'statement' => $statement, 'payout' => $payout];
}

function portalScenario(): array
{
    $s = seedScenario(2);
    $a = linkedOwner($s, 0, 20000); // unit U1
    $b = linkedOwner($s, 1, 30000); // unit U2

    return ['s' => $s, 'a' => $a, 'b' => $b];
}

// ── 1. Owner A sees only A's rows on EVERY owner-facing surface; never B's ──────────────────────
it('scopes every owner-facing model to the linked owner and hides other owners', function () {
    ['a' => $a, 'b' => $b] = portalScenario();
    $ctx = app(OwnerContext::class);

    $ctx->forOwners([$a['owner']->id], function () use ($a, $b) {
        // units, statements, lines, payouts, ledger, expenses — all A only, never B.
        expect(Unit::pluck('owner_id')->unique()->all())->toBe([$a['owner']->id])
            ->and(OwnerStatement::pluck('owner_id')->unique()->all())->toBe([$a['owner']->id])
            ->and(OwnerStatementLine::pluck('owner_id')->unique()->all())->toBe([$a['owner']->id])
            ->and(Payout::pluck('owner_id')->unique()->all())->toBe([$a['owner']->id])
            ->and(OwnerLedgerEntry::pluck('owner_id')->unique()->all())->toBe([$a['owner']->id])
            ->and(OwnerExpense::pluck('owner_id')->unique()->all())->toBe([$a['owner']->id]);

        // B's specific rows are invisible (not just absent from the top of a list).
        expect(OwnerStatement::find($b['statement']->id))->toBeNull()
            ->and(Payout::find($b['payout']->id))->toBeNull()
            ->and(Unit::find($b['unit']->id))->toBeNull();
    });
});

// ── 2. FAIL CLOSED: an empty owner scope returns nothing, NOT everything ────────────────────────
it('fails closed on an empty owner scope — sees nothing, never all owners', function () {
    portalScenario();
    $ctx = app(OwnerContext::class);

    $ctx->forOwners([], function () {
        expect(Unit::count())->toBe(0)
            ->and(OwnerStatement::count())->toBe(0)
            ->and(OwnerStatementLine::count())->toBe(0)
            ->and(Payout::count())->toBe(0)
            ->and(OwnerLedgerEntry::count())->toBe(0)
            ->and(OwnerExpense::count())->toBe(0);
    });
});

// ── 3. A non-linked authenticated user gets NO owner-portal data (fail closed via the middleware) ─
it('shows a non-linked user nothing on the portal (not everything)', function () {
    ['s' => $s, 'a' => $a, 'b' => $b] = portalScenario();
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get('/owner')
        ->assertOk()
        ->assertSee('No units')          // fail closed — empty, not all owners
        ->assertSee('No statements')
        ->assertDontSee($a['unit']->code)
        ->assertDontSee($b['unit']->code);
});

// ── 4. Portal renders A's real data; a cross-owner id 404s ──────────────────────────────────────
it('renders the owner’s real data and 404s a cross-owner statement', function () {
    ['a' => $a, 'b' => $b] = portalScenario();

    $this->actingAs($a['user'])->get('/owner')
        ->assertOk()
        ->assertSee($a['unit']->code)         // U1
        ->assertDontSee($b['unit']->code);    // U2 — B's unit hidden

    // A can open A's statement…
    $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}")->assertOk();
    // …but B's statement 404s for A (owner boundary), like a cross-tenant id.
    $this->actingAs($a['user'])->get("/owner/statements/{$b['statement']->id}")->assertNotFound();
});

// ── 4b. The dashboard is a ROLLUP — the thing that leaks MANY rows at once. The unit-code assertion
// above cannot catch a leaked FIGURE, and the dashboard's most sensitive content is money: the hero
// net, Earned YTD, the recent-statement rows, the next payout. This pins the MONEY perimeter, and
// pins #17 on the same page: what the owner sees is A's own net, and Earned YTD is the SUM of the
// statement rows shown — never a total that quietly includes B.
// (A's room is 20000 minor and B's 30000, so the two nets are distinct by construction.)
it('never renders another owner’s money on the dashboard rollup, and reconciles its own', function () {
    ['a' => $a, 'b' => $b] = portalScenario();

    $aNet = (int) $a['statement']->net_payable_minor;
    $bNet = (int) $b['statement']->net_payable_minor;
    expect($aNet)->not->toBe($bNet);   // guard against a vacuous assertion if the fixture ever equalises

    $money = fn (int $minor): string => number_format($minor / 100, 2);

    $this->actingAs($a['user'])->get('/owner')
        ->assertOk()
        ->assertSee($money($aNet))          // A's own net — hero + statement row + next payout
        ->assertDontSee($money($bNet));     // B's net never reaches A's rollup

    // #17 — Earned YTD is Σ of A's OWN statement nets, to the cent, and B contributes nothing.
    $ytd = OwnerStatement::withoutGlobalScopes()
        ->where('owner_id', $a['owner']->id)
        ->whereYear('period_start', (int) $a['statement']->period_start->format('Y'))
        ->sum('net_payable_minor');

    expect((int) $ytd)->toBe($aNet);
    $this->actingAs($a['user'])->get('/owner')->assertSee($money((int) $ytd));
});

// ── 5. An owner cannot read the operator's aggregate financials ─────────────────────────────────
it('never exposes the operator-wide aggregate to an owner', function () {
    ['a' => $a] = portalScenario();
    $ctx = app(OwnerContext::class);

    // Operator view (no owner scope): the whole org's payouts + statements.
    $operatorPayouts = Payout::query()->sum('amount_minor');
    $operatorStatements = OwnerStatement::query()->count();

    // Owner A view: strictly a subset — A can never see the org-wide totals.
    $ctx->forOwners([$a['owner']->id], function () use ($operatorPayouts, $operatorStatements) {
        expect((int) Payout::query()->sum('amount_minor'))->toBeLessThan((int) $operatorPayouts)
            ->and(OwnerStatement::query()->count())->toBeLessThan($operatorStatements)
            ->and(OwnerStatement::query()->count())->toBe(1); // only A's
    });
});

// ── 6. Operator/manager side is unaffected — sees all owners ────────────────────────────────────
it('leaves the operator side seeing all owners (owner scope inactive off-portal)', function () {
    ['s' => $s, 'a' => $a, 'b' => $b] = portalScenario();
    $operator = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $operator->id, 'status' => 'active']);
    // A real operator (not an owner): holds a back-office role, so the /app allow-list gate lets them in.
    $operator = grantBackOfficeRole($operator, $s['org']);

    // Operator hits the operator units page (no owner middleware) → sees BOTH owners' units.
    $this->actingAs($operator)->get('/app/units')
        ->assertOk()
        ->assertSee($a['unit']->code)
        ->assertSee($b['unit']->code);

    // Programmatically, with no owner scope active, all org units/statements are visible.
    app(TenantContext::class)->set($s['org']->id);
    expect(Unit::count())->toBe(2)
        ->and(OwnerStatement::count())->toBe(2);
    app(TenantContext::class)->forget();
});

// ── 7. Owner scope composes WITH tenant scope (org X owner can never see org Y, and vice-versa) ──
it('composes owner scope with tenant scope across organizations', function () {
    ['a' => $a] = portalScenario();

    // A brand-new org Y with its own linked owner + statement.
    $sY = seedScenario(1);
    $y = linkedOwner($sY, 0, 15000);

    // Neither owner can open the other's statement — 404 across the tenant AND owner boundary
    // together (ids are unique, so this is unambiguous where unit codes would collide).
    $this->actingAs($a['user'])->get("/owner/statements/{$y['statement']->id}")->assertNotFound();
    $this->actingAs($y['user'])->get("/owner/statements/{$a['statement']->id}")->assertNotFound();

    // Each owner's own portal renders in their own org (200 proves tenant+owner context resolved).
    $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}")->assertOk();
    $this->actingAs($y['user'])->get("/owner/statements/{$y['statement']->id}")->assertOk();

    // Programmatic composition: owner A's scope under org Y's tenant sees nothing (A is not in Y).
    app(TenantContext::class)->set($sY['org']->id);
    app(OwnerContext::class)->forOwners([$a['owner']->id], function () {
        expect(OwnerStatement::count())->toBe(0); // tenant Y ∧ owner A → empty
    });
    app(TenantContext::class)->forget();
});
