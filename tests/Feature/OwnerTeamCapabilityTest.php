<?php

use App\Models\User;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 7c · SG2 — owner-team CAPABILITY gating, mutation-proven per area. The three fixed roles gate the
// owner portal by the design matrix; the SENSITIVE payout account is co-owner ONLY (the money-security gate);
// view-only is genuinely read (no actions, no exports); role integrity holds (only the primary owner assigns).
// Sub-users are built by ownerSubUser() (tests/Support/owner_helpers.php); the primary owner by ownerRoleUser().

/**
 * A primary owner (real Owner.user_id) + one sub-user at each role, all acting for the SAME owner (units[0]).
 *
 * @return array{owner: User, co: User, accountant: User, view: User, scenario: array}
 */
function ownerTeamCast(): array
{
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;
    $s['units'][0]->update(['code' => 'TEAMUNIT']);

    return [
        'owner' => $owner,                                              // primary owner → co_owner + team
        'co' => ownerSubUser($s, $ownerId, OwnerTeamRole::CO_OWNER),
        'accountant' => ownerSubUser($s, $ownerId, OwnerTeamRole::ACCOUNTANT),
        'view' => ownerSubUser($s, $ownerId, OwnerTeamRole::VIEW_ONLY),
        'scenario' => $s,
    ];
}

// ── 1. 🔒 THE PAYOUT GATE — the encrypted-IBAN account is CO-OWNER ONLY (money-security) ─────────────
it('refuses the payout account to an accountant and a view-only sub-user, allows the owner', function () {
    $c = ownerTeamCast();

    // Accountant + view-only are REFUSED the read page AND every write — a non-co-owner reaching the IBAN
    // is a money-security hole (invariant #28 on the column).
    $this->actingAs($c['accountant'])->get('/owner/payout-method')->assertForbidden();
    $this->actingAs($c['view'])->get('/owner/payout-method')->assertForbidden();
    $this->actingAs($c['accountant'])->post('/owner/payout-method', [])->assertForbidden();
    $this->actingAs($c['view'])->delete('/owner/payout-method/anything')->assertForbidden();

    // The primary owner (co_owner) reaches it — unregressed.
    $this->actingAs($c['owner'])->get('/owner/payout-method')->assertOk();
});

// ── 2. 🔒 VIEW-ONLY IS GENUINELY READ — no actions, no exports (but can read its surfaces) ───────────
it('lets view-only read but blocks every action and export', function () {
    $c = ownerTeamCast();
    $view = $c['view'];

    // READS it is allowed (Dashboards, calendar & bookings) — genuinely usable, not locked out.
    $this->actingAs($view)->get('/owner')->assertOk();
    $this->actingAs($view)->get('/owner/units')->assertOk()->assertSee('TEAMUNIT');
    $this->actingAs($view)->get('/owner/calendar')->assertOk();
    $this->actingAs($view)->get('/owner/bookings')->assertOk();

    // NO ACTIONS — approvals (block calendar, rates, add-unit) are refused.
    $this->actingAs($view)->post('/owner/calendar/block', [])->assertForbidden();
    $this->actingAs($view)->get('/owner/rates')->assertForbidden();
    $this->actingAs($view)->get('/owner/add-unit')->assertForbidden();

    // NO EXPORTS — a PDF download is refused (the gate fires before the controller; a co-owner/accountant is 404).
    $this->actingAs($view)->get('/owner/statements/anything/pdf')->assertForbidden();
});

// ── 3. 🔒 ACCOUNTANT — documents/exports, but NO calendar and NO actions ────────────────────────────
it('gives an accountant documents and exports but not calendar or actions', function () {
    $c = ownerTeamCast();
    $acct = $c['accountant'];

    // Documents & tax — allowed (Documents renders the owner-scoped library; the tax center renders
    // gracefully — self-only-by-user_id means a sub-user sees an empty tax profile, never a 500, never
    // another owner's tax data).
    $this->actingAs($acct)->get('/owner/documents')->assertOk();
    $this->actingAs($acct)->get('/owner/tax')->assertOk();

    // Exports — allowed: the gate passes, so a bogus statement id 404s (NOT 403).
    $this->actingAs($acct)->get('/owner/statements/anything/pdf')->assertNotFound();

    // Calendar & bookings — REFUSED (the matrix dashes accountant there).
    $this->actingAs($acct)->get('/owner/calendar')->assertForbidden();
    $this->actingAs($acct)->get('/owner/units')->assertForbidden();

    // Actions — REFUSED (no approvals; can't SAVE tax though it may read it).
    $this->actingAs($acct)->post('/owner/calendar/block', [])->assertForbidden();
    $this->actingAs($acct)->post('/owner/tax', [])->assertForbidden();
});

// ── 4. 🔒 DOCUMENTS ARE CO-OWNER + ACCOUNTANT — a view-only sub-user is refused ──────────────────────
it('refuses documents and tax to a view-only sub-user', function () {
    $c = ownerTeamCast();

    $this->actingAs($c['view'])->get('/owner/documents')->assertForbidden();
    $this->actingAs($c['view'])->get('/owner/tax')->assertForbidden();
});

// ── 5. ✅ THE PRIMARY OWNER (co_owner) IS UNREGRESSED — reaches every capability ─────────────────────
it('leaves the primary owner with full access to every capability', function () {
    $c = ownerTeamCast();
    $owner = $c['owner'];

    $this->actingAs($owner)->get('/owner/payout-method')->assertOk();  // payout
    $this->actingAs($owner)->get('/owner/rates')->assertOk();          // actions
    $this->actingAs($owner)->get('/owner/calendar')->assertOk();       // calendar
    $this->actingAs($owner)->get('/owner/documents')->assertOk();      // documents
    $this->actingAs($owner)->get('/owner/units')->assertOk();          // calendar
});

// ── 6. 🔒 ROLE INTEGRITY — only the PRIMARY OWNER manages the team; no role self-escalates ───────────
it('lets only the primary owner assign roles — no sub-user role can', function () {
    // The primary owner may assign any of the three fixed roles.
    expect(OwnerTeamRole::canAssign(true, OwnerTeamRole::CO_OWNER))->toBeTrue();
    expect(OwnerTeamRole::canAssign(true, OwnerTeamRole::ACCOUNTANT))->toBeTrue();
    expect(OwnerTeamRole::canAssign(true, OwnerTeamRole::VIEW_ONLY))->toBeTrue();

    // A NON-primary-owner (even a co-owner sub-user) may assign NOTHING — self-escalation is impossible.
    expect(OwnerTeamRole::canAssign(false, OwnerTeamRole::CO_OWNER))->toBeFalse();
    expect(OwnerTeamRole::canAssign(false, OwnerTeamRole::VIEW_ONLY))->toBeFalse();

    // An unknown target role is rejected even for the owner.
    expect(OwnerTeamRole::canAssign(true, 'super_admin'))->toBeFalse();

    // Team management is NO role's capability — it is the primary owner's alone, so no role grants it.
    foreach (OwnerTeamRole::ALL as $role) {
        expect(OwnerTeamRole::allows($role, OwnerTeamRole::CAP_TEAM))->toBeFalse();
    }
});

// ── 7. 🔒 ROLE INTEGRITY (end-to-end) — the Team control shows for the owner, hidden from a co-owner ─
it('shows team management to the primary owner but not to a co-owner sub-user', function () {
    $c = ownerTeamCast();

    // The primary owner sees the team-management nav entry…
    $this->actingAs($c['owner'])->get('/owner')->assertOk()->assertSee('Team & access');

    // …but a co-owner SUB-user (full data access, still not the account holder) does not — they can't manage the team.
    $this->actingAs($c['co'])->get('/owner')->assertOk()->assertDontSee('Team & access');
});
