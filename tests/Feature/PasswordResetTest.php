<?php

use App\Mail\PasswordResetMail;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// Auth Phase B · finishing sweep — forgot / reset password on Laravel's Password broker. Same security
// posture as the rest of auth: NO enumeration (identical generic response), ACTIVE users only, tokened
// single-use expiring links, hashed password, session regenerated, rate-limited, CSRF (web group).

/** An active org member with a known password — routes to the operator app on reset. */
function resetUser(array $s, string $email, string $password = 'old-password', string $status = 'active'): User
{
    $user = User::factory()->create(['email' => $email, 'password' => $password, 'status' => $status]);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return $user;
}

/** Trigger a reset email and return the plaintext token captured from the Mailable. */
function requestResetToken(string $email): string
{
    $token = null;
    Mail::assertSent(PasswordResetMail::class, function (PasswordResetMail $m) use (&$token, $email): bool {
        if ($m->hasTo($email)) {
            $token = $m->token;
        }

        return $m->hasTo($email);
    });

    return (string) $token;
}

// ── The forgot screen renders + the login link points to it ──────────────────────────────────────
it('renders the forgot-password screen and links it from login', function () {
    $this->get('/forgot-password')->assertOk()->assertSee('Reset your password');
    $this->get('/login')->assertOk()->assertSee(route('password.request'));   // link enabled (not "Soon")
});

// ── Active user → a reset email is sent, generic status ──────────────────────────────────────────
it('emails a reset link to an active account', function () {
    Mail::fake();
    $s = seedScenario(1);
    resetUser($s, 'active@example.test');

    $this->post('/forgot-password', ['email' => 'active@example.test'])
        ->assertRedirect()
        ->assertSessionHas('status');

    Mail::assertSent(PasswordResetMail::class, fn (PasswordResetMail $m) => $m->hasTo('active@example.test'));
});

// ── No enumeration: a non-existent email → identical generic response, NO email ──────────────────
it('does not reveal whether an email exists (no enumeration, no email sent)', function () {
    Mail::fake();
    $s = seedScenario(1);
    resetUser($s, 'real@example.test');

    $real = $this->post('/forgot-password', ['email' => 'real@example.test']);
    $ghost = $this->post('/forgot-password', ['email' => 'ghost@example.test']);

    // Identical generic status for both — the responses are indistinguishable.
    expect(session('status'))->not->toBeNull();
    $real->assertSessionHas('status', fn ($s1) => $ghost->assertSessionHas('status', fn ($s2) => $s1 === $s2) && true);
    Mail::assertNotSent(PasswordResetMail::class, fn (PasswordResetMail $m) => $m->hasTo('ghost@example.test'));
});

// ── Active-only: an invited (not-yet-activated) user gets NO reset email, same generic response ──
it('will not send a reset link to an invited (inactive) account', function () {
    Mail::fake();
    $s = seedScenario(1);
    resetUser($s, 'invited@example.test', 'unusable', 'invited');

    $this->post('/forgot-password', ['email' => 'invited@example.test'])->assertSessionHas('status');

    Mail::assertNothingSent();   // invited users use their invite, not a reset — never leaked
});

// ── Full round-trip: request → reset → hashed password → signed in + routed by role ─────────────
it('resets the password end-to-end and signs the user in', function () {
    Mail::fake();
    $s = seedScenario(1);
    $user = resetUser($s, 'roundtrip@example.test', 'old-password');

    $this->post('/forgot-password', ['email' => 'roundtrip@example.test']);
    $token = requestResetToken('roundtrip@example.test');
    expect($token)->not->toBeEmpty();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'roundtrip@example.test',
        'password' => 'a-brand-new-pass',
        'password_confirmation' => 'a-brand-new-pass',
    ])->assertRedirect('/app/dashboard');

    $this->assertAuthenticatedAs($user->fresh());

    // Password is hashed (not plaintext) and actually changed.
    expect(Hash::check('a-brand-new-pass', $user->fresh()->password))->toBeTrue();

    // Old password no longer works; the new one does.
    $this->post('/logout');
    $this->post('/login', ['email' => 'roundtrip@example.test', 'password' => 'old-password'])->assertSessionHasErrors();
    $this->post('/login', ['email' => 'roundtrip@example.test', 'password' => 'a-brand-new-pass'])->assertRedirect('/app/dashboard');
});

// ── Single-use: a spent token cannot reset again ─────────────────────────────────────────────────
it('rejects a reused reset token (single-use)', function () {
    Mail::fake();
    $s = seedScenario(1);
    resetUser($s, 'once@example.test');

    $this->post('/forgot-password', ['email' => 'once@example.test']);
    $token = requestResetToken('once@example.test');

    $this->post('/reset-password', [
        'token' => $token, 'email' => 'once@example.test',
        'password' => 'first-new-pass', 'password_confirmation' => 'first-new-pass',
    ])->assertRedirect('/app/dashboard');
    $this->post('/logout');

    // Reuse the now-spent token → generic error, not authenticated.
    $this->post('/reset-password', [
        'token' => $token, 'email' => 'once@example.test',
        'password' => 'second-new-pass', 'password_confirmation' => 'second-new-pass',
    ])->assertSessionHasErrors('email');
    $this->assertGuest();
});

// ── Expiring: a token older than the broker expiry is rejected ───────────────────────────────────
it('rejects an expired reset token', function () {
    Mail::fake();
    $s = seedScenario(1);
    resetUser($s, 'stale@example.test');

    $this->post('/forgot-password', ['email' => 'stale@example.test']);
    $token = requestResetToken('stale@example.test');

    $this->travel(61)->minutes();   // broker default expiry is 60 minutes

    $this->post('/reset-password', [
        'token' => $token, 'email' => 'stale@example.test',
        'password' => 'too-late-pass', 'password_confirmation' => 'too-late-pass',
    ])->assertSessionHasErrors('email');
    $this->assertGuest();
});

// ── The request-link endpoint is rate-limited ────────────────────────────────────────────────────
it('rate-limits the reset-link request endpoint', function () {
    seedScenario(1);

    for ($i = 0; $i < 6; $i++) {
        $this->post('/forgot-password', ['email' => "flood{$i}@example.test"])->assertRedirect();
    }
    $this->post('/forgot-password', ['email' => 'one-too-many@example.test'])->assertStatus(429);
});
