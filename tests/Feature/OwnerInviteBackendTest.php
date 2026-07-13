<?php

use App\Mail\OwnerInviteMail;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\User;
use App\Services\OwnerInviteService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// Auth Phase B · Step 1 — the invite backend. An operator invites an owner into THEIR org: a pending
// (inactive) owner + an invited (inactive) login + a hashed, single-use, expiring token, all org-scoped.

function invite(string $email = 'marisol@example.test', array $args = []): array
{
    return app(OwnerInviteService::class)->invite(
        $args['name'] ?? 'Marisol Vega',
        $email,
        $args['units'] ?? [],
        $args['commission'] ?? 18.0,
        $args['role'] ?? 'owner',
    );
}

// ── 1. Creates a pending owner + invited login + a hashed, expiring, org-scoped token ────────────
it('creates a pending owner, an invited login, and a hashed expiring token', function () {
    Mail::fake();
    $s = seedScenario(1);

    ['invite' => $inv, 'token' => $token] = invite('marisol@example.test', ['units' => [$s['units'][0]->id], 'commission' => 18.0]);

    // Invited login — inactive (Phase A login is active-only), no usable password.
    $user = User::query()->where('email', 'marisol@example.test')->first();
    expect($user)->not->toBeNull()->and($user->status)->toBe('invited');

    // Pending owner, linked + org-scoped.
    $owner = Owner::query()->where('user_id', $user->id)->first();
    expect($owner->status)->toBe('inactive')
        ->and($owner->organization_id)->toBe($s['org']->id);

    // Membership so the tenant context resolves after activation.
    expect(Membership::where(['organization_id' => $s['org']->id, 'user_id' => $user->id])->exists())->toBeTrue();

    // Token: hashed (sha256), never the plaintext; expiring ~7 days; carries the operator's choices.
    expect($inv->token_hash)->toBe(hash('sha256', $token))
        ->and($inv->token_hash)->not->toBe($token)
        ->and($inv->status)->toBe('pending')
        ->and($inv->isRedeemable())->toBeTrue()
        ->and($inv->expires_at->isFuture())->toBeTrue()
        ->and($inv->assigned_unit_ids)->toBe([$s['units'][0]->id])
        ->and((float) $inv->commission_pct)->toBe(18.0);

    // The email was dispatched to the invitee with the tokened link.
    Mail::assertSent(OwnerInviteMail::class, fn (OwnerInviteMail $m) => $m->hasTo('marisol@example.test') && $m->token === $token);
});

// ── 2. Re-inviting refreshes the pending invite instead of duplicating ──────────────────────────
it('refreshes the token on re-invite rather than creating a duplicate', function () {
    Mail::fake();
    $s = seedScenario(1);

    ['invite' => $first, 'token' => $t1] = invite('repeat@example.test');
    ['invite' => $second, 'token' => $t2] = invite('repeat@example.test');

    expect(OwnerInvite::query()->where('email', 'repeat@example.test')->count())->toBe(1)   // one row, refreshed
        ->and($second->id)->toBe($first->id)
        ->and($t2)->not->toBe($t1)                                                          // fresh token
        ->and($second->token_hash)->toBe(hash('sha256', $t2));
    // The same owner + login are reused, not duplicated. (Owner.email is encrypted → query by user_id.)
    $user = User::query()->where('email', 'repeat@example.test')->firstOrFail();
    expect(User::where('email', 'repeat@example.test')->count())->toBe(1)
        ->and(Owner::query()->where('user_id', $user->id)->count())->toBe(1);
});

// ── 3. Tenant isolation — one org's invite is invisible to another org ──────────────────────────
it('scopes the invite to the operator org — another org cannot see it', function () {
    Mail::fake();
    $sA = seedScenario(1);
    invite('scoped@example.test');
    $user = User::query()->where('email', 'scoped@example.test')->firstOrFail();

    // A second org's context sees none of org A's owner-invite / pending owner (tenant-scoped).
    seedScenario(1);   // switches tenant context to a fresh org
    expect(OwnerInvite::query()->where('email', 'scoped@example.test')->count())->toBe(0)
        ->and(Owner::query()->where('user_id', $user->id)->count())->toBe(0);

    // Org A still has exactly its one invite + owner.
    app(TenantContext::class)->set($sA['org']->id);
    expect(OwnerInvite::query()->where('email', 'scoped@example.test')->count())->toBe(1)
        ->and(Owner::query()->where('user_id', $user->id)->count())->toBe(1);
});

// ── 4. Guards: an existing ACTIVE user, and unsupported roles, are rejected ──────────────────────
it('rejects inviting an email that already belongs to an active user', function () {
    $s = seedScenario(1);
    User::factory()->create(['email' => 'taken@example.test', 'status' => 'active']);

    expect(fn () => invite('taken@example.test'))->toThrow(RuntimeException::class);
});

it('rejects an unknown role that is not in the supported set (fail-safe)', function () {
    seedScenario(1);
    // owner/manager/accountant are the wired roles; anything else must throw (never invent a role).
    expect(fn () => invite('wizard@example.test', ['role' => 'wizard']))->toThrow(RuntimeException::class);
});
