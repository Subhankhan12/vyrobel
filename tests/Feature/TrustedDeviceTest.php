<?php

use App\Models\Owner;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Support\Auth\TrustedDeviceService;
use App\Support\TwoFactor\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

// TRUSTED DEVICES (Wave B · GATE 4) — "remember this device, skip 2FA here". The security contract:
//   • ticking "trust this device" at the challenge persists a hashed token + issues the vy_td cookie;
//   • a browser holding a LIVE trust SKIPS the 2FA challenge (mutation-proven — logs straight in);
//   • without the cookie the user is STILL challenged (the trust is the only thing that skips it);
//   • the token is stored ONLY as a hash — never the plaintext;
//   • a trust is self-only (user A's cookie never trusts user B) + revocable (remove → challenged again).

/**
 * ⚠️⚠️ R-GATE (founder, 2026-09-19) — THE ACTOR IS NOW OWNER-LINKED, AND THAT IS A CORRECTION. Trusted
 * devices are an OWNER-PORTAL security feature and these routes live under `/owner`, but this fixture used a
 * plain Front Desk operator — which worked only because the portal had no positive "is an owner" gate. With
 * the gate wired, a back-office user is sent to their own home, so the fixture now builds the actor the
 * feature is actually for. ⚠️ Fixing the FIXTURE, never the guard.
 */
function trust2faUser(array $s): array
{
    $user = operatorUser($s, ['name' => 'Tess Trust', 'password' => 'pw-correct-1'], 'Front Desk');
    Owner::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    $svc = app(TwoFactorService::class);
    $svc->startEnrollment($user);
    $secret = (string) $user->two_factor_secret;
    $svc->confirm($user, app(Google2FA::class)->getCurrentOtp($secret));

    return [$user, $secret];
}

// ── 1. Ticking "trust this device" persists a hashed token + issues the cookie ────────────────────
it('remembers the device when the user opts in at the 2FA challenge', function () {
    $s = seedScenario(1);
    [$user, $secret] = trust2faUser($s);

    $this->post('/login', ['email' => $user->email, 'password' => 'pw-correct-1'])->assertRedirect(route('two-factor.challenge'));

    $this->post('/two-factor-challenge', [
        'code' => app(Google2FA::class)->getCurrentOtp($secret),
        'trust_device' => '1',
    ])->assertRedirect();

    // A real trusted-device row exists for the user — with the HASH, never a plaintext token.
    $device = TrustedDevice::query()->where('user_id', $user->getKey())->firstOrFail();
    expect($device->token_hash)->toHaveLength(64)          // sha256 hex
        ->and($device->revoked_at)->toBeNull()
        ->and($device->expires_at->isFuture())->toBeTrue();
});

// ── 2. ⚠️ THE CRUX — a browser with a LIVE trust SKIPS the 2FA challenge (mutation-proven) ─────────
it('skips the 2FA challenge for a browser holding a live trust', function () {
    $s = seedScenario(1);
    [$user] = trust2faUser($s);

    // Mint a real trust for a token, then present that token as the cookie on the next login.
    $token = 'tok_'.Str::random(40);
    TrustedDevice::create([
        'user_id' => (string) $user->getKey(),
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDays(30),
    ]);

    // With the trust cookie, a correct password logs straight in — NO challenge redirect.
    $this->withUnencryptedCookie(TrustedDeviceService::COOKIE, $token)
        ->post('/login', ['email' => $user->email, 'password' => 'pw-correct-1'])
        ->assertRedirect();
    $this->assertAuthenticatedAs($user->fresh());

    // Break the skip (e.g. remove the recognizes() gate) and this would bounce to the challenge instead.
    $this->get('/app/dashboard')->assertOk();
});

// ── 3. Without the trust cookie, the SAME user is still challenged ────────────────────────────────
it('still challenges the user without a trust cookie', function () {
    $s = seedScenario(1);
    [$user] = trust2faUser($s);

    $this->post('/login', ['email' => $user->email, 'password' => 'pw-correct-1'])
        ->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
});

// ── 4. Self-only — user A's trust cookie never skips 2FA for user B ───────────────────────────────
it('never lets one user\'s trust skip 2FA for another user', function () {
    $s = seedScenario(1);
    [$userA] = trust2faUser($s);
    $userB = operatorUser($s, ['name' => 'Bob Other', 'password' => 'pw-b-2'], 'Front Desk');
    $svcB = app(TwoFactorService::class);
    $svcB->startEnrollment($userB);
    $svcB->confirm($userB, app(Google2FA::class)->getCurrentOtp((string) $userB->two_factor_secret));

    // A's real trust token.
    $token = 'tok_'.Str::random(40);
    TrustedDevice::create(['user_id' => (string) $userA->getKey(), 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(30)]);

    // B presents A's cookie → B is STILL challenged (the trust is bound to A's user_id).
    $this->withUnencryptedCookie(TrustedDeviceService::COOKIE, $token)
        ->post('/login', ['email' => $userB->email, 'password' => 'pw-b-2'])
        ->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
});

// ── 5. Revoke (re-auth) removes the trust → the device is challenged again next time ──────────────
it('revokes a trusted device after re-auth so it must pass 2FA again', function () {
    $s = seedScenario(1);
    [$user] = trust2faUser($s);   // the revoke route is auth-only + self-only ($request->user())

    $token = 'tok_'.Str::random(40);
    $device = TrustedDevice::create(['user_id' => (string) $user->getKey(), 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(30)]);

    // Wrong password → refused (re-auth gate); the trust survives.
    $this->actingAs($user)->post(route('owner.security.trusted-devices.revoke'), [
        'device_id' => $device->id, 'current_password' => 'wrong',
    ])->assertSessionHasErrors('current_password');
    expect($device->fresh()->revoked_at)->toBeNull();

    // Correct password → revoked.
    $this->actingAs($user)->post(route('owner.security.trusted-devices.revoke'), [
        'device_id' => $device->id, 'current_password' => 'pw-correct-1',
    ])->assertRedirect();
    expect($device->fresh()->revoked_at)->not->toBeNull();

    // The now-revoked device is no longer recognised — recognizes() filters whereNull(revoked_at), so a fresh
    // login with the same cookie would be challenged again (proven directly via the service).
    $req = Request::create('/login', 'POST', cookies: [TrustedDeviceService::COOKIE => $token]);
    expect(app(TrustedDeviceService::class)->recognizes($user->fresh(), $req))->toBeFalse();
});
