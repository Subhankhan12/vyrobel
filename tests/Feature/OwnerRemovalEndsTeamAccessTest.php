<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerPayoutPreference;
use App\Models\OwnerUser;
use App\Models\User;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ══ REMOVAL NOW ENDS THE TEAM'S ACCESS — AND THE MODAL NO LONGER LIES — 2026-08-31 ══════════════════
//
// ⚠️ MEASURED BEFORE THE FIX: after `app.owners.remove`, a co-owner sub-user's `owner_users` row was still
// `active`, `GET /owner` still returned 200, and a payout-preference WRITE still LANDED (0 → 1 rows) on the
// removed owner. Reads happened to come back empty — the data models' scope excludes a trashed owner — but
// `OwnerContext` resolves sub-memberships from `owner_users` ALONE (OwnerContext:247, no join to `owners`),
// so a write needing only the owner id went straight through.
//
// ⚠️ AND THE MODAL SAID "Portal access ends immediately." The false copy was the worse half: a gap someone
// knows about gets worked around; a gap the product denies does not get looked for.
//
// ⚠️ WHY A CASCADE RATHER THAN COPY-ONLY. Sub-users have exactly ONE creator — OwnerTeamInviteService, behind
// `EnsurePrimaryOwner`. They are the owner's own people; no operator path writes `owner_users`. And the only
// route that removes one is ALSO primary-owner-only, so once the owner is removed NOBODY could close the
// access. "Require a second explicit action" was not an available option.

function orerSetup(string $role = OwnerTeamRole::CO_OWNER, array $subAttrs = []): array
{
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Ada Admin'], 'Property Owner');
    $admin->changePasswordTo('secret-Passw0rd!');
    ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    $sub = User::factory()->create(array_merge(['status' => 'active'], $subAttrs));
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $sub->id, 'status' => 'active']);
    OwnerUser::create(['owner_id' => $ownerId, 'user_id' => $sub->id, 'role' => $role, 'status' => 'active']);

    return ['s' => $s, 'admin' => $admin->fresh(), 'ownerId' => $ownerId, 'sub' => $sub->fresh()];
}

function orerRemove(array $c): void
{
    test()->actingAs($c['admin'])->post(route('app.owners.remove', $c['ownerId']), [
        'current_password' => 'secret-Passw0rd!',
        'confirm_name' => (string) Owner::withoutGlobalScopes()->findOrFail($c['ownerId'])->name,
    ])->assertRedirect();
}

// ── 1. ⚠️⚠️ THE WRITE THAT USED TO LAND — the half a read-only check would have missed ──────────────
it('stops a sub-user writing to the removed owner', function () {
    $c = orerSetup();
    orerRemove($c);

    expect(OwnerPayoutPreference::withoutGlobalScopes()->where('owner_id', $c['ownerId'])->count())->toBe(0);

    $this->actingAs($c['sub']->fresh())->post(route('owner.payouts.preferences'), [
        'current_password' => 'password', 'frequency' => 'weekly', 'payout_day' => 1,
        'minimum' => '0', 'paused' => '1',
    ]);

    // ⚠️ Asserted on the DATA, not the status code — a 302 was what the old behaviour returned too.
    expect(OwnerPayoutPreference::withoutGlobalScopes()->where('owner_id', $c['ownerId'])->count())->toBe(0);
});

// ── 2. ⚠️ THE MEMBERSHIP IS GONE, and the login follows the shared rule ─────────────────────────────
// ⚠️ `withTrashed()`, because `OwnerUser` soft-deletes: a plain query would report the row absent whether
// the cascade ran or not, and `withoutGlobalScopes()` would report it present either way.
it('soft-removes the owner’s team and disables a login that has nothing left', function () {
    $c = orerSetup();
    orerRemove($c);

    $row = OwnerUser::withTrashed()->where('user_id', $c['sub']->id)->firstOrFail();
    expect($row->trashed())->toBeTrue()
        ->and($c['sub']->fresh()->status)->toBe('disabled');
});

// ── 3. ⚠️⚠️ AND IT DOES NOT CUT OFF SOMEONE WHO WORKS FOR THE OPERATOR ─────────────────────────────
// ⚠️ The whole risk of a cascade. A person holding a back-office role keeps their login — only their access
// to this owner ends. That is what makes the cascade safe rather than a surprise.
it('leaves a back-office user’s own login intact when their owner is removed', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Ada Admin'], 'Property Owner');
    $admin->changePasswordTo('secret-Passw0rd!');
    ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    // A real staff member who ALSO holds owner-portal access.
    $staff = operatorUser($s, ['name' => 'Sam Staff'], 'Hotel Manager');
    OwnerUser::create(['owner_id' => $ownerId, 'user_id' => $staff->id, 'role' => OwnerTeamRole::ACCOUNTANT, 'status' => 'active']);

    orerRemove(['admin' => $admin->fresh(), 'ownerId' => $ownerId]);

    expect(OwnerUser::withTrashed()->where('user_id', $staff->id)->firstOrFail()->trashed())->toBeTrue()
        ->and($staff->fresh()->status)->toBe('active');   // ⚠️ their working account survives
});

// ── 4. 🖥️ THE MODAL NO LONGER CLAIMS WHAT WAS FALSE — read from the RENDERED page ──────────────────
it('renders removal copy that no longer claims access simply ends', function () {
    $c = orerSetup();

    $html = $this->actingAs($c['admin'])->get(route('app.owners.show', $c['ownerId']))->assertOk()->getContent();

    expect(str_contains($html, 'and everyone on their team'))
        ->toBeTrue('the modal must say the team goes too');
    expect(str_contains($html, 'keeps their own login'))
        ->toBeTrue('…and that an operator’s own staff keep their account');

    // ⚠️ The bare old claim, standing alone, must not be what an operator reads.
    expect(str_contains($html, 'Portal access ends immediately</li>'))
        ->toBeFalse('the unqualified "Portal access ends immediately" was false and must not return');
});
