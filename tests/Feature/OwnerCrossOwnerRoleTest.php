<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\PayoutMethod;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerExpenseService;
use App\Services\RateProposalService;
use App\Support\Payouts\Iban;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

// ⛔ P1-1 (owner-realm audit) — CROSS-OWNER UNION-ROLE OVER-GRANT.
// SetOwnerScopeForPortal resolved a portal user's role as the single HIGHEST role across the UNION of all their
// owner memberships, and isPrimaryOwner as a GLOBAL boolean. So a user who is co-owner/primary of Owner A AND a
// lesser role on Owner B (same tenant) acted with co-owner powers on B: approve B's expenses/rate-proposals,
// edit B's rates, read B's masked IBAN, and (if primary of A) manage B's team; the OAuth API inherited the same
// union role. These tests set up EXACTLY that arrangement and assert the SECURE per-owner behaviour — so they
// FAIL on the pre-fix code (proving + bounding the blast radius) and HOLD after the per-owner-role fix.
// 🔒 #28 owner-scoped + PER-OWNER role · #42 primary gates per-owner · the OAuth isolation per-owner.

/**
 * Build the cross-owner arrangement in one org:
 *   - userX is the PRIMARY owner of Owner A (→ co_owner + isPrimaryOwner) …
 *   - … AND a VIEW-ONLY sub-user of Owner B (a lesser role) — the over-grant vector.
 * Returns everything the assertions target on Owner B (whose data userX must NOT be able to act on).
 */
function crossOwner(): array
{
    $s = seedScenario(1);   // property + one unit in room type "Std" (exclusively Owner A's)

    $userX = User::factory()->create(['name' => 'X Owner']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $userX->id, 'status' => 'active']);

    // Owner A — userX is the PRIMARY (account holder → implicit co_owner + team). Std is exclusively A's.
    $ownerA = ownUnit($s['units'][0]);
    $ownerA->update(['user_id' => $userX->id]);

    // Owner B — a DIFFERENT primary, on a DEDICATED (exclusively-B) room type + unit; userX is only VIEW-ONLY of B.
    $userY = User::factory()->create(['name' => 'Y Owner']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $userY->id, 'status' => 'active']);
    $rtB = RoomType::create(['property_id' => $s['property']->id, 'name' => 'StdB', 'kind' => 'room']);
    RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $rtB->id, 'name' => 'FlexB', 'currency' => 'EUR']);
    $unitB = Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $rtB->id, 'code' => 'UB']);
    $ownerB = Owner::factory()->create(['user_id' => $userY->id, 'payout_currency' => 'EUR']);
    $unitB->update(['owner_id' => $ownerB->id]);
    ManagementAgreement::factory()->create(['owner_id' => $ownerB->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);
    OwnerUser::create([
        'organization_id' => $s['org']->id, 'owner_id' => $ownerB->id,
        'user_id' => $userX->id, 'role' => 'view_only', 'status' => 'active',
    ]);

    // A third user is a real team member of Owner B (the target for a team-management over-grant).
    $userZ = User::factory()->create(['name' => 'Z Member']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $userZ->id, 'status' => 'active']);
    $bMember = OwnerUser::create([
        'organization_id' => $s['org']->id, 'owner_id' => $ownerB->id,
        'user_id' => $userZ->id, 'role' => 'accountant', 'status' => 'active',
    ]);

    // Owner B's pending, money-affecting fixtures.
    $bExpense = app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => (string) $unitB->id, 'category' => 'cleaning', 'description' => 'B turnover clean',
        'amount_minor' => 6000, 'currency' => 'EUR', 'expense_date' => '2026-09-03',
    ]);   // PENDING (deductible, not yet charged)

    $operator = User::factory()->create(['name' => 'Op']);
    $bRoomTypeId = (string) $rtB->id;
    $bProposal = app(RateProposalService::class)->propose(
        $operator, (string) $ownerB->id, $bRoomTypeId, 18500, null, null, '2026-10-01', '2026-10-31', null,
    );   // PENDING

    // Owner B's payout method (the sensitive masked-IBAN read).
    $bMethod = PayoutMethod::create([
        'organization_id' => $s['org']->id, 'owner_id' => $ownerB->id,
        'label' => 'B Bank', 'account_holder' => 'Owner B',
        'iban' => 'LT121000011101007777', 'iban_last4' => '7777', 'iban_country' => 'LT',
        'currency' => 'EUR', 'is_default' => true,
    ]);

    // Owner A's own pending expense — to prove LEGITIMATE access is preserved on A.
    $aExpense = app(OwnerExpenseService::class)->recordExpense([
        'unit_id' => (string) $s['units'][0]->id, 'category' => 'cleaning', 'description' => 'A turnover clean',
        'amount_minor' => 5000, 'currency' => 'EUR', 'expense_date' => '2026-09-03',
    ]);

    return compact('s', 'userX', 'ownerA', 'ownerB', 'bMember', 'bExpense', 'bProposal', 'bMethod', 'bRoomTypeId', 'aExpense');
}

// ── 1. Cannot APPROVE Owner B's expense (CAP_ACTIONS is per-owner; view-only on B lacks it) ─────────
it('refuses userX approving Owner B’s expense (view-only on B), and B stays pending', function () {
    $c = crossOwner();

    $this->actingAs($c['userX'])->post(route('owner.expenses.approve', $c['bExpense']->id))->assertForbidden();

    expect($c['bExpense']->fresh()->approval_state)->toBe('pending');   // never charged to B's payout
});

// ── 2. Cannot APPROVE/DECLINE Owner B's rate-proposal ───────────────────────────────────────────────
it('refuses userX approving Owner B’s rate-proposal, and it stays pending', function () {
    $c = crossOwner();

    $this->actingAs($c['userX'])->post(route('owner.rate-proposals.approve', $c['bProposal']->id))->assertForbidden();

    expect($c['bProposal']->fresh()->state)->toBe('pending');
});

// ── 3. Cannot EDIT Owner B's rates (B's room type is not in userX's editable set) ──────────────────
it('refuses userX applying a rate on Owner B’s room type', function () {
    $c = crossOwner();

    $this->actingAs($c['userX'])->post(route('owner.rates.apply'), [
        'room_type_id' => $c['bRoomTypeId'], 'start_date' => '2026-10-01', 'end_date' => '2026-10-05', 'rate' => '999',
    ])->assertForbidden();
});

// ── 4. Cannot READ Owner B's masked payout IBAN (CAP_PAYOUT is per-owner) ──────────────────────────
it('never shows Owner B’s masked IBAN to userX (view-only on B)', function () {
    $c = crossOwner();

    $masked = Iban::masked('7777');   // e.g. "···· 7777"
    $this->actingAs($c['userX'])->get(route('owner.dashboard'))->assertOk()->assertDontSee($masked);
});

// ── 5. Cannot MANAGE Owner B's team (isPrimaryOwner is per-owner; userX is primary of A, not B) ─────
it('refuses userX managing Owner B’s team (not primary of B)', function () {
    $c = crossOwner();

    // Remove + role-change on B's member — userX is primary of A only, so B's member is out of reach.
    $this->actingAs($c['userX'])->delete(route('owner.team.members.remove', $c['bMember']->id))->assertNotFound();
    $this->actingAs($c['userX'])->patch(route('owner.team.members.role', $c['bMember']->id), ['role' => 'co_owner'])->assertNotFound();

    expect($c['bMember']->fresh()->role)->toBe('accountant');   // unchanged
});

// ── 6. LEGITIMATE access on Owner A is preserved (userX IS co_owner + primary of A) ────────────────
it('still lets userX act on Owner A (its own primary/co-owner powers intact)', function () {
    $c = crossOwner();

    // Approve A's own expense — allowed (not 403); it charges A.
    $this->actingAs($c['userX'])->post(route('owner.expenses.approve', $c['aExpense']->id))->assertRedirect();
    expect($c['aExpense']->fresh()->approval_state)->toBe('approved');
});

// ── 7. OAuth API inherits the PER-OWNER role — a token never reads an owner the user's role can't back ──
it('scopes the OAuth API to per-owner capability, not the union role', function () {
    $s = seedScenario(2);

    // userX2 is PRIMARY (co_owner) of A2, and an ACCOUNTANT of B2 (accountant lacks calendar.view).
    $userX2 = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $userX2->id, 'status' => 'active']);
    $a2 = ownUnit($s['units'][0]);
    $a2->update(['user_id' => $userX2->id]);

    $userY2 = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $userY2->id, 'status' => 'active']);
    $b2 = ownUnit($s['units'][1]);
    $b2->update(['user_id' => $userY2->id]);
    OwnerUser::create([
        'organization_id' => $s['org']->id, 'owner_id' => $b2->id,
        'user_id' => $userX2->id, 'role' => 'accountant', 'status' => 'active',
    ]);

    $resA = book($s, ['unit_id' => $s['units'][0]->id]);   // A2's booking
    $resB = book($s, ['unit_id' => $s['units'][1]->id]);   // B2's booking

    // read:bookings needs calendar.view; the union role (co_owner via A2) once granted it for BOTH owners.
    // Per-owner, userX2 is only an accountant on B2 (no calendar) → B2's bookings must be excluded.
    Passport::actingAs($userX2, ['read:bookings'], 'api');
    $refs = collect($this->getJson('/api/owner/bookings')->assertOk()->json('data'))->pluck('reference');

    expect($refs)->toContain($resA->reference)          // 🔒 A2 (co_owner → calendar) is readable…
        ->and($refs)->not->toContain($resB->reference); // 🔒 …B2 (accountant → no calendar) is NOT
});
