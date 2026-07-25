<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

// TWO-FACTOR ENROLLMENT (Step 1) — real TOTP (google2fa) + one-time recovery codes. The security contract:
//   • enrollment REQUIRES a valid confirm code before 2FA activates (anti-lockout) — an invalid code does
//     NOT enable it (mutation-proven: the guard is what stops activation);
//   • recovery codes are generated on confirm, shown ONCE, single-use, and ENCRYPTED at rest (ciphertext in
//     the raw column) — regenerate invalidates the old set;
//   • disabling ACTIVE 2FA requires re-auth (current password) — an open session can't silently remove it;
//   • self-only: everything is resolved from auth(), so a session can only touch its OWN 2FA;
//   • the secret is ENCRYPTED at rest (fixing the pre-existing plaintext column).

/** The current valid TOTP code for a secret (what the user's authenticator app would show). */
function currentOtp(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

/** An operator who has started enrollment; returns [user, decrypted secret]. */
function enrolledPending(array $s, string $password = 'pw-current-1', string $role = 'Front Desk'): array
{
    $user = operatorUser($s, ['name' => 'Uma User', 'password' => $password], $role);
    test()->actingAs($user)->post('/app/security/2fa/enable');

    return [$user, (string) $user->fresh()->two_factor_secret];
}

// ── 1. ENABLE → a PENDING state that does NOT yet gate login ─────────────────────────────────────
it('generates a secret on enable but does not activate 2FA until confirmed', function () {
    $s = seedScenario(1);
    [$user] = enrolledPending($s);

    $fresh = $user->fresh();
    expect($fresh->two_factor_secret)->not->toBeNull()          // a secret exists…
        ->and($fresh->two_factor_confirmed_at)->toBeNull()      // …but it is UNCONFIRMED…
        ->and($fresh->hasTwoFactorEnabled())->toBeFalse();      // …so 2FA is NOT active (anti-lockout)
});

// ── 2. CONFIRM with a VALID code activates + issues one-time recovery codes ───────────────────────
it('activates 2FA and issues recovery codes when confirmed with a valid code', function () {
    $s = seedScenario(1);
    [$user, $secret] = enrolledPending($s);

    $response = $this->actingAs($user)->post('/app/security/2fa/confirm', ['code' => currentOtp($secret)]);

    $response->assertRedirect()->assertSessionHas('two_factor_recovery_codes');
    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue()
        ->and($response->getSession()->get('two_factor_recovery_codes'))->toHaveCount(8);   // shown once
});

// ── 3. ⚠️ THE ANTI-LOCKOUT GUARD — an INVALID confirm code does NOT activate 2FA ─────────────────
it('does not activate 2FA when the confirm code is wrong', function () {
    $s = seedScenario(1);
    [$user] = enrolledPending($s);

    $this->actingAs($user)->post('/app/security/2fa/confirm', ['code' => '000000'])
        ->assertSessionHasErrors('code');

    // The guard held: no confirmation, no activation, no recovery codes. (Remove the verify() check and this
    // would wrongly enable.)
    $fresh = $user->fresh();
    expect($fresh->hasTwoFactorEnabled())->toBeFalse()
        ->and($fresh->two_factor_confirmed_at)->toBeNull()
        ->and($fresh->two_factor_recovery_codes)->toBeNull();
});

// ── 4. ENCRYPTED AT REST — the raw columns hold ciphertext, never the plaintext secret/codes ─────
it('stores the secret and recovery codes encrypted at rest', function () {
    $s = seedScenario(1);
    [$user, $secret] = enrolledPending($s);
    $codes = $this->actingAs($user)->post('/app/security/2fa/confirm', ['code' => currentOtp($secret)])
        ->getSession()->get('two_factor_recovery_codes');

    $raw = DB::table('users')->where('id', $user->id)->first();

    // The raw secret column is NOT the plaintext secret (Laravel encrypted payload), and the raw recovery
    // column contains none of the plaintext codes.
    expect($raw->two_factor_secret)->not->toBe($secret)
        ->and($raw->two_factor_secret)->not->toBeNull()
        ->and($raw->two_factor_recovery_codes)->not->toContain($codes[0])
        ->and($raw->two_factor_recovery_codes)->not->toContain($codes[7]);
});

// ── 5. REGENERATE invalidates the old set ────────────────────────────────────────────────────────
it('regenerates recovery codes, invalidating the old set', function () {
    $s = seedScenario(1);
    [$user, $secret] = enrolledPending($s);
    $first = $this->actingAs($user)->post('/app/security/2fa/confirm', ['code' => currentOtp($secret)])
        ->getSession()->get('two_factor_recovery_codes');

    $second = $this->actingAs($user)->post('/app/security/2fa/recovery-codes')
        ->assertSessionHas('two_factor_recovery_codes')
        ->getSession()->get('two_factor_recovery_codes');

    expect($second)->toHaveCount(8)->and($second)->not->toEqual($first);
    // The stored codes are the NEW set — the old ones are gone.
    $stored = collect($user->fresh()->two_factor_recovery_codes)->pluck('code')->all();
    expect($stored)->toEqual($second)->and($stored)->not->toContain($first[0]);
});

// ── 6. 🔒 DISABLE requires re-auth — an open session can't silently remove active 2FA ────────────
it('refuses to disable active 2FA without the current password, and removes it with it', function () {
    $s = seedScenario(1);
    [$user, $secret] = enrolledPending($s, 'disable-secret-1');
    $this->actingAs($user)->post('/app/security/2fa/confirm', ['code' => currentOtp($secret)]);
    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();

    // No current password → rejected, 2FA STILL on.
    $this->actingAs($user)->post('/app/security/2fa/disable', [])->assertSessionHasErrors('current_password');
    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();

    // Wrong current password → rejected, still on.
    $this->actingAs($user)->post('/app/security/2fa/disable', ['current_password' => 'nope'])
        ->assertSessionHasErrors('current_password');
    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();

    // Correct current password → off (secret + codes + confirmation cleared).
    $this->actingAs($user)->post('/app/security/2fa/disable', ['current_password' => 'disable-secret-1'])
        ->assertRedirect();
    $fresh = $user->fresh();
    expect($fresh->hasTwoFactorEnabled())->toBeFalse()
        ->and($fresh->two_factor_secret)->toBeNull()
        ->and($fresh->two_factor_recovery_codes)->toBeNull();
});

it('lets a user cancel an UNCONFIRMED enrollment without a password (nothing is protected yet)', function () {
    $s = seedScenario(1);
    [$user] = enrolledPending($s);

    // Pending (unconfirmed) → cancel needs no password.
    $this->actingAs($user)->post('/app/security/2fa/disable', [])->assertRedirect();
    expect($user->fresh()->two_factor_secret)->toBeNull();
});

// ── 7. SELF-ONLY — a session can only manage its OWN 2FA (no user id in the request) ─────────────
it('touches only the acting user’s 2FA, never another user’s', function () {
    $s = seedScenario(1);
    [$actor, $secret] = enrolledPending($s, 'actor-pw', 'Front Desk');
    $other = operatorUser($s, ['name' => 'Other User', 'password' => 'other-pw'], 'Hotel Manager');

    $this->actingAs($actor)->post('/app/security/2fa/confirm', ['code' => currentOtp($secret)]);

    expect($actor->fresh()->hasTwoFactorEnabled())->toBeTrue()
        // The other user is entirely unaffected — there is no user id to target.
        ->and($other->fresh()->two_factor_secret)->toBeNull()
        ->and($other->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

// ── 8. THE OWNER SIDE — same flow, owner routes ──────────────────────────────────────────────────
it('lets an owner enroll and confirm 2FA on the owner side', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);

    $this->actingAs($a['user'])->post('/owner/security/2fa/enable');
    $secret = (string) $a['user']->fresh()->two_factor_secret;

    // from() sets the referer so the controller's back() PRG returns to the owner settings page.
    $this->actingAs($a['user'])->from(route('owner.security'))->post('/owner/security/2fa/confirm', ['code' => currentOtp($secret)])
        ->assertRedirect(route('owner.security'))->assertSessionHas('two_factor_recovery_codes');

    expect($a['user']->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

// ── 9. The security page shows the panel; the actions require auth ────────────────────────────────
it('renders the two-factor panel on the operator security page', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s, ['name' => 'Ana'], 'Front Desk'))->get('/app/security')
        ->assertOk()->assertSee('Two-factor authentication')->assertSee('Enable two-factor');
});

it('requires auth for the 2FA enrollment actions', function () {
    // A fresh GUEST request (no actingAs) — the auth middleware bounces it to login.
    $this->post('/app/security/2fa/enable')->assertRedirect(route('login'));
});

it('renders the QR + confirm step while enrollment is pending', function () {
    $s = seedScenario(1);
    [$user, $secret] = enrolledPending($s);

    // assertOk proves the server-side QR render (bacon SVG) executes without error; the manual key + confirm
    // control are present in the pending state.
    $this->actingAs($user)->get('/app/security')
        ->assertOk()
        ->assertSee('Confirm')
        ->assertSee($secret);
});
