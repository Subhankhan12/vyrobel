<?php

use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 7c · SG1 — the owner-sub-user model + NESTED isolation. A sub-user ⊆ their owner ⊆ their org: they
// see ONLY their owner's units/data (never another owner's), can't reach the operator app, and stay on the
// owner realm. The nesting falls out of the existing owner scope (OwnerScope) + tenant scope, composed.
// The `ownerSubUser()` helper lives in tests/Support/owner_helpers.php (shared with the capability suite).

// ── 1. 🔒 NESTED ISOLATION — a sub-user of owner A sees A's units, never owner B's ─────────────────
it('scopes a sub-user to their owner only — A\'s sub-user never sees B\'s units', function () {
    $s = seedScenario(2);
    ownerRoleUser($s, 0);   // owner A ← units[0]
    ownerRoleUser($s, 1);   // owner B ← units[1]
    $s['units'][0]->update(['code' => 'AONLYUNIT']);
    $s['units'][1]->update(['code' => 'BONLYUNIT']);
    $ownerAId = (string) $s['units'][0]->fresh()->owner_id;

    $sub = ownerSubUser($s, $ownerAId, 'view_only');

    $this->actingAs($sub)->get('/owner/units')
        ->assertOk()
        ->assertSee('AONLYUNIT')          // their owner's unit…
        ->assertDontSee('BONLYUNIT');     // …never the other owner's (the owner boundary holds for sub-users)
});

// ── 2. 🔒 REALM — a sub-user is on the OWNER realm; refused from /app and the guest portal ──────────
it('keeps a sub-user out of the operator app and the guest portal', function () {
    $s = seedScenario(1);
    ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;
    $sub = ownerSubUser($s, $ownerId, 'view_only');

    // Refused from /app (#26 — no back-office role, bounced to /owner), and from the guest realm.
    $this->actingAs($sub)->get('/app/dashboard')->assertRedirect();
    $this->actingAs($sub)->get('/guest/trips')->assertRedirect();   // guest guard, not this user

    // …but the owner portal works.
    $this->actingAs($sub)->get('/owner/units')->assertOk();
});

// ── 3. 🔒 A DISABLED / non-member sub-user fails CLOSED (empty owner scope → sees nothing) ──────────
it('fails closed for a disabled sub-user and a plain member with no owner link', function () {
    $s = seedScenario(1);
    ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;
    $s['units'][0]->update(['code' => 'SECRETUNIT']);

    // A disabled membership resolves NO owner (status filter) → EMPTY owner scope. The capability gate targets
    // only users WITH owner access, so this falls through to the scope's own fail-closed: the page renders but
    // shows nothing. Either way the sub-user never sees a unit.
    $disabled = ownerSubUser($s, $ownerId, 'view_only', 'disabled');
    $this->actingAs($disabled)->get('/owner/units')->assertOk()->assertDontSee('SECRETUNIT');

    // A plain org member with no owner link at all — same empty scope, same fail-closed.
    $stranger = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);
    $this->actingAs($stranger)->get('/owner/units')->assertOk()->assertDontSee('SECRETUNIT');
});

// ── 4. ✅ UNREGRESSED — the primary owner still sees their own units ────────────────────────────────
it('leaves the primary owner unregressed', function () {
    $s = seedScenario(1);
    $ownerUser = ownerRoleUser($s, 0);
    $s['units'][0]->update(['code' => 'PRIMARYUNIT']);

    $this->actingAs($ownerUser)->get('/owner/units')->assertOk()->assertSee('PRIMARYUNIT');
});
