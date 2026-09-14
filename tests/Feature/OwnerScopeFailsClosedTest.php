<?php

use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Models\Owner;
use App\Models\Unit;
use App\Models\User;
use App\Support\Owner\OwnerContext;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;

uses(RefreshDatabase::class);

// ══ AUDIT 2026-08-29 · FINDING E-1 — THE OWNER BOUNDARY MUST NOT DEPEND ON THE ROUTE ═════════════════
//
// ⚠️ WHAT HAPPENED. OwnerScope executed `return;` (adding NO constraint) whenever OwnerContext was
// inactive. Correct for an operator — but `/livewire/update` does not re-run route-group middleware, so
// SetOwnerScopeForPortal never ran there, and one `$refresh` returned all 8 units in the organisation to
// an owner entitled to 2. Reproduced over HTTP before the fix, and again after it (2 on both paths).
//
// ⚠️ WHY 2,871 GREEN TESTS SAT OVER IT — AND WHAT THIS FILE CHANGES.
// OwnerCapabilityMutationBoundaryTest proved owner components expose no MUTATING action. That was TRUE,
// and it is why nobody worried. But `$refresh` is a framework MAGIC action needing no declared method,
// so it sat entirely outside that test's premise: THE BOUNDARY WAS HELD BY THE ABSENCE OF BUTTONS, NOT
// BY THE SCOPE. Reinforcing it: all 293 owner tests drive the portal through HTTP get('/owner/...') —
// the path where the middleware ALWAYS ran, i.e. the path that was always correct. NOTHING exercised an
// owner read with the context absent.
//
// ⚠️ FAILURE CLASS, RECORDED: **a test proving something ADJACENT to what you think it proves.**
//
// These tests therefore assert THE SCOPE ITSELF with the context deliberately absent — independent of
// which routes, middleware or wire actions happen to exist.

/** Two owners in ONE org, each owning one unit. The audit could only INFER owner-B leakage (the demo has
 *  a single owner); with two, the isolation is DEMONSTRATED. */
function twoOwnerScenario(): array
{
    $s = seedScenario(2);
    $userA = ownerRoleUser($s, 0);
    $userB = ownerRoleUser($s, 1);

    app(TenantContext::class)->set($s['org']->id);

    return [
        's' => $s,
        'userA' => $userA,
        'ownerA' => Owner::query()->where('user_id', $userA->id)->firstOrFail(),
        'unitA' => $s['units'][0]->fresh(),
        'unitB' => $s['units'][1]->fresh(),
    ];
}

/** The exact E-1 condition: an owner-linked actor, authenticated, with NO owner context established. */
function unscopedOwnerActor(): array
{
    $c = twoOwnerScenario();
    app(OwnerContext::class)->deactivate();       // the path that forgot the middleware
    app(TenantContext::class)->set($c['s']['org']->id);
    Auth::login($c['userA']);

    return $c;
}

// ⚠️ There is deliberately NO "throws when unresolvable" test: that state is UNREACHABLE (the scope only
// self-heals for an authenticated User), so such a test would assert nothing — the vacuous shape this
// project keeps finding. The guarantee below is the real one.

// ── 1. ⛔ THE CORE GUARANTEE — an owner reading with NO context sees ONLY their own rows ─────────────
// WITHOUT the fix the scope added no constraint and this returned BOTH owners' units (over HTTP: 8, not
// 2). It asserts the SCOPE, not a route, so no future surface can reintroduce the hole unnoticed.
it('scopes an owner-linked actor to their own units even when no owner context was established', function () {
    $c = unscopedOwnerActor();

    $codes = Unit::query()->pluck('code')->all();

    expect($codes)->toContain((string) $c['unitA']->code);
    expect($codes)->not->toContain((string) $c['unitB']->code);   // ← the leak, asserted absent
});

// ── 3. ✅ THE OPERATOR SIDE IS UNAFFECTED — "inactive" stays legitimate for a non-owner actor ────────
// This is why OwnerScope canNOT simply mirror OrganizationScope's unconditional throw: Unit is
// owner-scoped and read from 115 call sites across the operator app, where no owner context exists.
it('adds no constraint for an operator actor who is linked to no owner', function () {
    $c = twoOwnerScenario();
    app(OwnerContext::class)->deactivate();
    Auth::login(User::factory()->create());       // staff — NO owner links

    expect(Unit::query()->count())->toBe(2);      // sees the whole org, as the operator app must
});

// ── 4. ✅ CLI / QUEUE — no authenticated actor, no owner boundary, no throw ──────────────────────────
it('adds no constraint and does not throw when there is no authenticated actor at all', function () {
    $c = twoOwnerScenario();
    app(OwnerContext::class)->deactivate();
    Auth::logout();

    expect(Unit::query()->count())->toBe(2);
});

// ── 5. 🔒 THE ESTABLISHED PATH STILL SCOPES — owner A never sees owner B ─────────────────────────────
it('scopes an owner to their own units only when the context IS established', function () {
    $c = twoOwnerScenario();

    $codes = app(OwnerContext::class)->forOwners(
        [(string) $c['ownerA']->id],
        fn () => Unit::query()->pluck('code')->all()
    );

    expect($codes)->toContain((string) $c['unitA']->code);
    expect($codes)->not->toContain((string) $c['unitB']->code);
});

// ── 6. 🔒 DEFENCE IN DEPTH — the re-render path re-establishes the owner scope ───────────────────────
// The scope self-healing is the FIX; this registration keeps /livewire/update on the SAME code path as a
// normal request. Asserted so removing it is a visible, deliberate act rather than a silent regression.
it('re-applies the owner-scope middleware on the livewire update path', function () {
    expect(app(PersistentMiddleware::class)->getPersistentMiddleware())
        ->toContain(SetOwnerScopeForPortal::class);
});
