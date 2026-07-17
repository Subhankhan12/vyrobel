<?php

use App\Models\User;
use App\Support\TwoFactor\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

// TWO-FACTOR LOGIN CHALLENGE (Step 2) — the challenge slots AFTER the password, BEFORE full auth. The security
// contract this file proves:
//   • ⚠️ THE CRUX — a correct password on a 2FA account grants NO authenticated access: the user is put in a
//     PENDING state (a plain session key, NOT the auth guard), so /app + /owner reject them by construction
//     until a valid code (mutation-proven — this is the whole point of splitting Auth::validate from login);
//   • a valid TOTP code completes login;
//   • a valid recovery code completes login AND is consumed — it cannot be used twice (single-use);
//   • an invalid code is rejected and the attempt is RATE-LIMITED (a 6-digit code is brute-forceable);
//   • a NON-2FA user logs in exactly as before — no regression from the Auth::validate/Auth::login split.

/** The current valid TOTP for a secret (what the authenticator app shows) — inlined, no cross-file helper. */
function challengeOtp(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

/** Activate real 2FA on a user (enroll → confirm) and return [decrypted secret, plaintext recovery codes]. */
function activate2faFor(User $user): array
{
    $svc = app(TwoFactorService::class);
    $svc->startEnrollment($user);
    $secret = (string) $user->two_factor_secret;
    $codes = $svc->confirm($user, challengeOtp($secret));

    return [$secret, $codes];
}

// ── 1. ⚠️ THE CRUX — a 2FA user with the RIGHT password is CHALLENGED, not logged in, and reaches nothing ──
it('challenges a 2FA user after a correct password and grants NO access until a valid code', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Tara Otp', 'password' => 'pw-correct-1'], 'Front Desk');
    activate2faFor($user);

    // Correct password → sent to the CHALLENGE, and NOT authenticated.
    $this->post('/login', ['email' => $user->email, 'password' => 'pw-correct-1'])
        ->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();

    // The pending state is a session key, NOT the auth guard — so the back office rejects it by construction.
    // (Break the split — e.g. Auth::login before the challenge — and this GET would 200 instead of bounce.)
    $this->get('/app/dashboard')->assertRedirect(route('login'));
});

// ── 2. A valid TOTP code completes login ─────────────────────────────────────────────────────────
it('completes login with a valid TOTP code', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Ivan Totp', 'password' => 'pw-correct-2'], 'Front Desk');
    [$secret] = activate2faFor($user);

    $this->post('/login', ['email' => $user->email, 'password' => 'pw-correct-2']);   // → pending

    $this->post('/two-factor-challenge', ['code' => challengeOtp($secret)])
        ->assertRedirect(route('app.dashboard'));

    $this->assertAuthenticatedAs($user->fresh());
    $this->get('/app/dashboard')->assertOk();   // now genuinely in the back office
});

// ── 3. A valid RECOVERY code completes login AND is consumed (single-use) ─────────────────────────
it('completes login with a recovery code and consumes it so it cannot be reused', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Rhea Recov', 'password' => 'pw-correct-3'], 'Front Desk');
    [, $codes] = activate2faFor($user);
    $recovery = $codes[0];

    $this->post('/login', ['email' => $user->email, 'password' => 'pw-correct-3']);
    $this->post('/two-factor-challenge', ['code' => $recovery])->assertRedirect(route('app.dashboard'));
    $this->assertAuthenticatedAs($user->fresh());

    // The code is now marked used at rest.
    $used = collect($user->fresh()->two_factor_recovery_codes)->firstWhere('code', $recovery);
    expect($used['used_at'])->not->toBeNull();

    // Log out, log back in, and try the SAME recovery code → rejected, still a guest (single-use held).
    $this->post('/logout');
    $this->post('/login', ['email' => $user->email, 'password' => 'pw-correct-3']);
    $this->post('/two-factor-challenge', ['code' => $recovery])->assertSessionHasErrors('code');
    $this->assertGuest();
});

// ── 4. An invalid code is rejected, and brute force is RATE-LIMITED (blocks even a valid code) ────
it('rejects an invalid code and throttles brute force', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Bram Brute', 'password' => 'pw-correct-4'], 'Front Desk');
    [$secret] = activate2faFor($user);

    $this->post('/login', ['email' => $user->email, 'password' => 'pw-correct-4']);   // → pending

    // Five wrong attempts — each rejected, none logs in.
    for ($i = 0; $i < 5; $i++) {
        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    // The sixth — even with the CORRECT code — is throttled: the limiter beats the valid code, proving the
    // brute-force guard fires (remove the throttle and this would complete login).
    $this->post('/two-factor-challenge', ['code' => challengeOtp($secret)])->assertSessionHasErrors('code');
    $this->assertGuest();
});

// ── 5. NO REGRESSION — a user without 2FA logs in normally, with no challenge ─────────────────────
it('logs a non-2FA user straight in, with no challenge (no regression)', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Nina Normal', 'password' => 'pw-correct-5'], 'Front Desk');

    $this->post('/login', ['email' => $user->email, 'password' => 'pw-correct-5'])
        ->assertRedirect(route('app.dashboard'));

    $this->assertAuthenticatedAs($user->fresh());
});

// ── 6. The challenge PAGE is unreachable without a live pending state ─────────────────────────────
it('keeps the challenge unreachable without a pending state', function () {
    $this->get('/two-factor-challenge')->assertRedirect(route('login'));
    $this->post('/two-factor-challenge', ['code' => '123456'])->assertRedirect(route('login'));
});

// ── 7. Owner side — a 2FA owner is challenged, reaches nothing while pending, then completes to /owner ──
it('challenges a 2FA owner and completes to the owner portal', function () {
    $s = seedScenario(1);
    $owner = inboxOwner($s, 'Amara Okafor', 0);
    $user = $owner['user'];
    $user->update(['password' => 'owner-pw-7']);   // 'hashed' cast hashes on set
    [$secret] = activate2faFor($user);

    $this->post('/login', ['email' => $user->email, 'password' => 'owner-pw-7'])
        ->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
    $this->get('/owner')->assertRedirect(route('login'));   // pending grants nothing on the owner side either

    $this->post('/two-factor-challenge', ['code' => challengeOtp($secret)])
        ->assertRedirect(route('owner.dashboard'));
    $this->assertAuthenticatedAs($user->fresh());
});

// ── 8. A WRONG password never reaches the challenge (the split didn't weaken password verification) ──
it('does not enter the pending state on a wrong password', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Wade Wrong', 'password' => 'pw-correct-8'], 'Front Desk');
    activate2faFor($user);

    $this->from(route('login'))
        ->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    // No pending state was created, so the challenge stays closed.
    $this->get('/two-factor-challenge')->assertRedirect(route('login'));
});
