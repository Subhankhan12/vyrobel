<?php

use App\Actions\Authorization\OrganizationRoleProvisioner;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\User;
use App\Services\OwnerInviteService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// Tier 1 finishing sweep · Step 3 — `invites:prune-expired`. CONSERVATIVE cleanup: only genuinely
// EXPIRED + NEVER-ACCEPTED invites are marked expired and their never-activated artifacts cleaned
// (invited login → status 'expired'; pending inactive owner → soft-removed). Accepted invites, active
// users/owners, still-valid invites, and logins shared with a live invite are NEVER touched. Idempotent.

/** Create a real invite (owner or staff) via the service; returns ['invite'=>, 'token'=>]. */
function seedInvite(array $s, string $email, string $role = 'owner'): array
{
    Mail::fake();

    return app(OwnerInviteService::class)->invite(
        'Ghost '.Str::substr(md5($email), 0, 4),
        $email,
        $role === 'owner' ? [$s['units'][0]->id] : [],
        $role === 'owner' ? 18.0 : null,
        $role,
    );
}

/** Force an invite past its expiry (still pending, never accepted). */
function expireInvite(OwnerInvite $invite): void
{
    app(TenantContext::class)->runAsSuperAdmin(
        fn () => OwnerInvite::query()->whereKey($invite->id)->update(['expires_at' => now()->subDay()]),
    );
}

/** Provision the org's catalog roles so a staff invite can be issued. */
function provisionInviteRoles(array $s): void
{
    app(OrganizationRoleProvisioner::class)->provision($s['org']);
}

// ── PRUNED: an expired owner invite + its never-activated login + owner ──────────────────────────
it('expires an owner invite and cleans up its never-activated login + owner', function () {
    $s = seedScenario(1);
    ['invite' => $inv] = seedInvite($s, 'ghost@example.test', 'owner');
    expireInvite($inv);

    $user = User::query()->where('email', 'ghost@example.test')->firstOrFail();
    app(TenantContext::class)->set($s['org']->id);
    $owner = Owner::query()->where('user_id', $user->id)->firstOrFail();
    expect($user->status)->toBe('invited')->and($owner->status)->toBe('inactive');   // never activated

    $this->artisan('invites:prune-expired')->assertSuccessful();

    app(TenantContext::class)->runAsSuperAdmin(function () use ($inv, $user, $owner) {
        expect(OwnerInvite::query()->find($inv->id)->status)->toBe('expired')
            ->and(User::query()->find($user->id)->status)->toBe('expired')
            ->and(Owner::query()->find($owner->id))->toBeNull()                       // soft-removed
            ->and(Owner::withTrashed()->find($owner->id)->trashed())->toBeTrue();
    });
});

// ── UNTOUCHED: an accepted invite + its ACTIVE owner + login ─────────────────────────────────────
it('never touches an accepted invite or its active owner and login', function () {
    $s = seedScenario(1);
    ['invite' => $inv] = seedInvite($s, 'active-owner@example.test', 'owner');
    app(OwnerInviteService::class)->accept($inv, 'a-strong-password');   // activate
    expireInvite($inv);   // even force the clock past expiry — accepted_at protects it

    $this->artisan('invites:prune-expired')->assertSuccessful();

    app(TenantContext::class)->runAsSuperAdmin(function () use ($inv) {
        expect(OwnerInvite::query()->find($inv->id)->status)->toBe('accepted');
        $user = User::query()->where('email', 'active-owner@example.test')->firstOrFail();
        expect($user->status)->toBe('active')
            ->and(Owner::query()->where('user_id', $user->id)->firstOrFail()->status)->toBe('active');
    });
});

// ── UNTOUCHED: a still-valid (unexpired) pending invite and its pending artifacts ────────────────
it('leaves a still-valid pending invite and its pending artifacts alone', function () {
    $s = seedScenario(1);
    ['invite' => $inv] = seedInvite($s, 'pending@example.test', 'owner');   // not expired (now + 7d)

    $this->artisan('invites:prune-expired')->assertSuccessful();

    $user = User::query()->where('email', 'pending@example.test')->firstOrFail();
    app(TenantContext::class)->runAsSuperAdmin(function () use ($inv, $user) {
        expect(OwnerInvite::query()->find($inv->id)->status)->toBe('pending')
            ->and(User::query()->find($user->id)->status)->toBe('invited')
            ->and(Owner::query()->where('user_id', $user->id)->firstOrFail()->status)->toBe('inactive');
    });
});

// ── UNTOUCHED: an activated user who came from an invite (belt-and-suspenders) ───────────────────
it('never marks an activated login even if a stray expired invite shares the email', function () {
    $s = seedScenario(1);
    ['invite' => $inv] = seedInvite($s, 'activated@example.test', 'owner');
    app(OwnerInviteService::class)->accept($inv, 'a-strong-password');   // user is now active

    // A second, stale, never-accepted pending invite row for the same email (edge case).
    app(TenantContext::class)->runAsSuperAdmin(function () use ($s) {
        OwnerInvite::query()->create([
            'organization_id' => $s['org']->id, 'owner_id' => null, 'email' => 'activated@example.test',
            'token_hash' => hash('sha256', Str::random(40)), 'role' => 'manager',
            'status' => 'pending', 'expires_at' => now()->subDay(), 'accepted_at' => null,
        ]);
    });

    $this->artisan('invites:prune-expired')->assertSuccessful();

    // The stale invite is expired, but the ACTIVE login is left alone (status guard).
    expect(User::query()->where('email', 'activated@example.test')->firstOrFail()->status)->toBe('active');
});

// ── PRUNED: a staff invite (no owner) — invite + login expired, no owner error ───────────────────
it('expires a staff invite and its login with no owner to remove', function () {
    $s = seedScenario(1);
    provisionInviteRoles($s);
    ['invite' => $inv] = seedInvite($s, 'mgr-ghost@example.test', 'manager');
    expireInvite($inv);

    $this->artisan('invites:prune-expired')->assertSuccessful();

    $user = User::query()->where('email', 'mgr-ghost@example.test')->firstOrFail();
    app(TenantContext::class)->runAsSuperAdmin(function () use ($inv, $user) {
        expect(OwnerInvite::query()->find($inv->id)->status)->toBe('expired')
            ->and(OwnerInvite::query()->find($inv->id)->owner_id)->toBeNull()
            ->and(User::query()->find($user->id)->status)->toBe('expired');
    });
});

// ── IDEMPOTENT: a second run changes nothing ─────────────────────────────────────────────────────
it('is idempotent — a second run reports and changes nothing', function () {
    $s = seedScenario(1);
    ['invite' => $inv] = seedInvite($s, 'twice@example.test', 'owner');
    expireInvite($inv);

    $this->artisan('invites:prune-expired')->assertSuccessful();
    // Second run: nothing left to prune.
    $this->artisan('invites:prune-expired')
        ->expectsOutputToContain('Expired 0 invite(s); soft-removed 0 never-activated owner(s); marked 0 never-activated login(s) expired.')
        ->assertSuccessful();

    app(TenantContext::class)->runAsSuperAdmin(function () use ($inv) {
        expect(OwnerInvite::query()->find($inv->id)->status)->toBe('expired');   // still just expired-once
    });
});

// ── FAIL-SAFE + TENANT-SAFE: a login shared with a live invite in another org is NOT expired ─────
it('does not expire a login shared with a still-valid invite in another org', function () {
    $email = 'shared@example.test';

    // Org A: an EXPIRED owner invite for the email.
    $sA = seedScenario(1);
    ['invite' => $invA] = seedInvite($sA, $email, 'owner');
    expireInvite($invA);
    $ownerAId = $invA->owner_id;

    // Org B: a STILL-VALID pending owner invite for the SAME email (reuses the shared login).
    $sB = seedScenario(1);
    ['invite' => $invB] = seedInvite($sB, $email, 'owner');
    $ownerBId = $invB->owner_id;

    $this->artisan('invites:prune-expired')->assertSuccessful();

    app(TenantContext::class)->runAsSuperAdmin(function () use ($invA, $invB, $ownerAId, $ownerBId, $email) {
        // Org A: invite expired + its owner soft-removed (independent, correct).
        expect(OwnerInvite::query()->find($invA->id)->status)->toBe('expired')
            ->and(Owner::query()->find($ownerAId))->toBeNull();
        // Org B: still valid + its owner intact (tenant-safe — the other org is untouched).
        expect(OwnerInvite::query()->find($invB->id)->status)->toBe('pending')
            ->and(Owner::query()->find($ownerBId)->status)->toBe('inactive');
        // The SHARED login is LEFT 'invited' — org B still needs it (fail-safe on shared state).
        expect(User::query()->where('email', $email)->firstOrFail()->status)->toBe('invited');
    });
});
