<?php

use App\Livewire\Owner\OwnerPayoutMethod;
use App\Livewire\Owner\OwnerTeam;
use App\Models\OwnerUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// AUDIT FOLLOW-UP (2026-07-22) — close the caveat docs/AUDIT-REPORT.md left open: the owner capability gates
// were confirmed at the ROUTE layer, but the /livewire/update mutation layer was not exercised. Livewire's
// update endpoint does NOT run route middleware (the same 6a boundary that ResolvesCurrentProperty addresses),
// so a capability gated ONLY on a Livewire route would be bypassable if the mutation were a Livewire action.
//
// This test proves the boundary holds for the two SENSITIVE owner mutations (payout account + team management)
// two ways: (1) the real client mutation path (HTTP POST/PATCH/DELETE to the controller routes) is gated, and
// (2) structurally, the owner Livewire components expose NO mutating action — every owner write is a controller
// route (a plain <form>), never a wire action — so /livewire/update carries no owner mutation to bypass a gate.
// See tests/Feature/OwnerTeamCapabilityTest.php for the payout-mutation + read-gate coverage this complements.

// ── 1. 🔒 TEAM MANAGEMENT MUTATIONS — PRIMARY-OWNER ONLY on the real HTTP mutation path ───────────────
// EnsurePrimaryOwner gates team writes; a sub-user (even a co-owner) may not invite / re-role / remove.
it('refuses team-management mutations to every sub-user via the real mutation path, but not the primary owner', function () {
    $c = ownerTeamCast();
    // A real member row to target the {member} routes, so route-model-binding resolves and the GATE is what fires.
    $memberId = (string) OwnerUser::where('user_id', $c['view']->id)->value('id');

    // Every SUB-user — accountant, view-only, AND co-owner (full data access, still not the account holder) —
    // is refused every team mutation. Self-escalation via the mutation path is structurally impossible.
    foreach (['accountant', 'view', 'co'] as $role) {
        $this->actingAs($c[$role])->post('/owner/team/invite', [])->assertForbidden();
        $this->actingAs($c[$role])->patch("/owner/team/members/{$memberId}", [])->assertForbidden();
        $this->actingAs($c[$role])->delete("/owner/team/members/{$memberId}")->assertForbidden();
    }

    // The PRIMARY OWNER passes the gate on the same routes — the mutation is reachable for the account holder
    // (empty payload → a validation redirect / not-forbidden, proving the gate itself did not refuse them).
    $this->actingAs($c['owner'])->post('/owner/team/invite', [])->assertStatus(302);
    expect($this->actingAs($c['owner'])->post('/owner/team/invite', [])->status())->not->toBe(403);
});

// ── 2. 🔒 THE PAYOUT ACCOUNT MUTATIONS — CO-OWNER ONLY on the real HTTP mutation path ─────────────────
// (Complements OwnerTeamCapabilityTest test 1: assert ALL three write verbs, for accountant AND view-only.)
it('refuses every payout-account mutation verb to accountant and view-only sub-users', function () {
    $c = ownerTeamCast();

    foreach (['accountant', 'view'] as $role) {
        $this->actingAs($c[$role])->post('/owner/payout-method', [])->assertForbidden();
        $this->actingAs($c[$role])->post('/owner/payout-method/anything/default', [])->assertForbidden();
        $this->actingAs($c[$role])->delete('/owner/payout-method/anything')->assertForbidden();
        $this->actingAs($c[$role])->post('/owner/payout-method/connect', [])->assertForbidden();
    }
});

// ── 3. 🔒 STRUCTURAL — the sensitive owner Livewire components expose NO mutating action ──────────────
// The reason the route gates are SUFFICIENT: these owner mutations are NOT Livewire actions. The payout and
// team Livewire components render only — every write is a controller <form>. So /livewire/update (which skips
// route middleware) has nothing to call: there is no wire action to reach the gated mutation behind the gate.
it('exposes no mutating Livewire action on the payout or team components — every write is a gated controller route', function () {
    // Only render/mount may be declared directly on these components (Livewire lifecycle). ANY other
    // public method declared here would be a wire-callable action reachable via /livewire/update.
    $allowed = ['render', 'mount', 'boot', 'booted', 'hydrate', 'dehydrate', 'rendering', 'rendered'];

    foreach ([OwnerPayoutMethod::class, OwnerTeam::class] as $component) {
        $declared = collect((new ReflectionClass($component))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(fn (ReflectionMethod $m) => $m->getDeclaringClass()->getName() === $component)
            ->map(fn (ReflectionMethod $m) => $m->getName())
            ->reject(fn (string $name) => str_starts_with($name, '__')) // magic/constructor
            ->values();

        expect($declared->diff($allowed)->all())
            ->toBe([], "{$component} must expose no mutating Livewire action (found: ".$declared->implode(', ').')');
    }
});
