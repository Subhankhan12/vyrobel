<?php

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerInviteService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// Auth Phase B · Step 2 — accept-invite / set-password activation. A valid tokened link → set password
// → activate login + owner → spend the token → auto-login → /owner. Any bad/expired/spent token gives a
// generic response (no enumeration). Single-use, rate-limited, password hashed, session regenerated.

/** Invite an owner in the scenario's org and return the plaintext token. */
function invited(array $s, string $email = 'nova@example.test'): array
{
    Mail::fake();
    $r = app(OwnerInviteService::class)->invite('Nova Marsh', $email, [$s['units'][0]->id], 18.0, 'owner');

    return ['invite' => $r['invite'], 'token' => $r['token']];
}

// ── 1. Valid token → set password → activate → auto-login → owner-scoped portal ──────────────────
it('activates the owner and logs them in on a valid token', function () {
    $s = seedScenario(1);
    ['invite' => $inv, 'token' => $token] = invited($s);

    test()->get("/invite/{$token}")->assertOk()->assertSee('Set your password');

    // ⚠️⚠️ `/owner/welcome`, NOT `/owner` — CHANGED 2026-09-22 (APPLY 04 · item 2). This is the invite path,
    //    the ONE place a brand-new owner is created, and it is the door `owner.welcome` never had: 🔬 that
    //    page was built, tested and honest, and nothing anywhere linked to it. Its own terminal CTA is
    //    already "Go to your dashboard", so it is a pre-dashboard step by design and hands off immediately.
    //    ⚠️ The ORDINARY LOGIN below still lands on `/owner` — a returning owner is not re-onboarded.
    //    ⚠️ No ruling or pin named this landing (checked before changing it); the subject of this test is
    //    activation + auto-login + owner scoping, all unchanged and all still asserted below.
    test()->post("/invite/{$token}", ['password' => 'Sup3r-secret-pw!', 'password_confirmation' => 'Sup3r-secret-pw!'])
        ->assertRedirect('/owner/welcome');

    // Login + owner activated; token spent; unit assigned; agreement created.
    $user = User::query()->where('email', 'nova@example.test')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->status)->toBe('active');

    app(TenantContext::class)->set($s['org']->id);
    $owner = Owner::query()->where('user_id', $user->id)->firstOrFail();
    expect($owner->status)->toBe('active')
        ->and(OwnerInvite::query()->find($inv->id)->status)->toBe('accepted')
        ->and((string) Unit::query()->find($s['units'][0]->id)->owner_id)->toBe((string) $owner->id)
        ->and(ManagementAgreement::query()->where('owner_id', $owner->id)->where('status', 'active')->exists())->toBeTrue();

    // The owner portal renders THIS owner's scoped data (scope active, not fail-closed).
    $s['units'][0]->update(['code' => 'NOVASUITE']);
    test()->get('/owner/units')->assertOk()->assertSee('NOVASUITE');
});

// ── 2. The activated owner can now log in via the Phase A login (round-trip) ─────────────────────
it('lets the activated owner sign in through the normal login afterwards', function () {
    $s = seedScenario(1);
    ['token' => $token] = invited($s, 'roundtrip@example.test');

    test()->post("/invite/{$token}", ['password' => 'My-strong-pass1!', 'password_confirmation' => 'My-strong-pass1!']);
    test()->post('/logout');
    test()->assertGuest();

    test()->post('/login', ['email' => 'roundtrip@example.test', 'password' => 'My-strong-pass1!'])
        ->assertRedirect('/owner');
    test()->assertAuthenticated();
});

// ── 3. A SPENT token cannot activate again (single-use) ──────────────────────────────────────────
it('rejects a reused token with a generic page and no re-activation', function () {
    $s = seedScenario(1);
    ['invite' => $inv, 'token' => $token] = invited($s, 'once@example.test');

    test()->post("/invite/{$token}", ['password' => 'First-password-x1!', 'password_confirmation' => 'First-password-x1!'])->assertRedirect('/owner/welcome');
    test()->post('/logout');

    // Reuse the now-spent token → generic invalid page, no second activation.
    test()->get("/invite/{$token}")->assertOk()->assertSee('isn’t valid');
    test()->post("/invite/{$token}", ['password' => 'Attacker-repass1!', 'password_confirmation' => 'Attacker-repass1!'])
        ->assertOk()->assertSee('isn’t valid');
    test()->assertGuest();
    app(TenantContext::class)->set($s['org']->id);
    expect(OwnerInvite::query()->find($inv->id)->status)->toBe('accepted');   // unchanged, still spent once
});

// ── 4. Expired / invalid / garbage tokens all give the SAME generic response (no enumeration) ────
it('shows a generic page for an expired token', function () {
    $s = seedScenario(1);
    ['invite' => $inv, 'token' => $token] = invited($s, 'stale@example.test');
    app(TenantContext::class)->set($s['org']->id);
    OwnerInvite::query()->whereKey($inv->id)->update(['expires_at' => now()->subDay()]);

    test()->get("/invite/{$token}")->assertOk()->assertSee('isn’t valid');
    test()->post("/invite/{$token}", ['password' => 'Whatever-longpw1!', 'password_confirmation' => 'Whatever-longpw1!'])
        ->assertOk()->assertSee('isn’t valid');
    // No activation.
    expect(User::query()->where('email', 'stale@example.test')->firstOrFail()->status)->toBe('invited');
});

it('shows the SAME generic page for a garbage token (no enumeration)', function () {
    seedScenario(1);
    test()->get('/invite/totally-made-up-token')->assertOk()->assertSee('isn’t valid');
});

// ── 5. Server-side password rules (min length + confirm match) ───────────────────────────────────
it('rejects a too-short or mismatched password without activating', function () {
    $s = seedScenario(1);
    ['token' => $token] = invited($s, 'weak@example.test');

    test()->post("/invite/{$token}", ['password' => 'short', 'password_confirmation' => 'short'])
        ->assertSessionHasErrors('password');
    test()->post("/invite/{$token}", ['password' => 'Long-enough-1!', 'password_confirmation' => 'different-2'])
        ->assertSessionHasErrors('password');

    test()->assertGuest();
    expect(User::query()->where('email', 'weak@example.test')->firstOrFail()->status)->toBe('invited');
});

// ── 6. The endpoint is rate-limited ──────────────────────────────────────────────────────────────
it('rate-limits the invite endpoint', function () {
    seedScenario(1);
    app(TenantContext::class)->runAsSuperAdmin(fn () => cache()->flush());   // clear any prior throttle counters

    for ($i = 0; $i < 10; $i++) {
        test()->get('/invite/guessing-token-'.$i)->assertOk();   // generic page, but counts toward the limit
    }
    test()->get('/invite/one-too-many')->assertStatus(429);
});
