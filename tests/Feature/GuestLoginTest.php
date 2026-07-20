<?php

use App\Mail\GuestPasswordResetMail;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

// GUEST LOGIN + PASSWORD RESET (Phase 4a · Sub-gate 3) — on the GUEST guard/broker, hardened like the operator
// chapter. Proven: a claimed+verified guest logs in (rate-limited, session regenerated); an unclaimed/unverified
// account can't; REALM SEPARATION (a logged-in guest can't reach /app or /owner, and is never on the web guard);
// reset works with NO enumeration and a token keyed on the BLIND INDEX (never the plaintext email · #28).

/** A claimed + verified guest account on the seeded org. */
function loginGuest(array $s, string $email = 'ada@example.test', string $pw = 'correct horse battery'): Guest
{
    $g = $s['guest'];
    $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $g->password = $pw;   // hashed by the cast
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

// ── A claimed guest logs in on the GUEST guard ─────────────────────────────────────────────────────
it('logs a claimed guest in on the guest guard and lands them in the guest area', function () {
    $s = seedScenario(1);
    $guest = loginGuest($s);

    $this->post('/guest/login', ['email' => 'ada@example.test', 'password' => 'correct horse battery'])
        ->assertRedirect(route('guest.home'));

    $this->assertAuthenticatedAs($guest, 'guest');
    $this->assertGuest('web');   // NOT authenticated on the operator/owner realm
});

// ── An unclaimed OR unverified account cannot log in ───────────────────────────────────────────────
it('refuses an unclaimed or unverified account', function () {
    $s = seedScenario(1);
    $g = $s['guest'];
    $g->update(['email' => 'bob@example.test']);   // unclaimed: no password, not verified

    $this->post('/guest/login', ['email' => 'bob@example.test', 'password' => 'anything'])->assertSessionHasErrors('email');
    $this->assertGuest('guest');

    // Password set but NOT verified — still can't log in (the provider requires both).
    $g->password = 'has a password';
    $g->email_verified_at = null;
    $g->save();
    $this->post('/guest/login', ['email' => 'bob@example.test', 'password' => 'has a password'])->assertSessionHasErrors('email');
    $this->assertGuest('guest');
});

// ── Wrong password → generic error, not signed in ──────────────────────────────────────────────────
it('rejects a wrong password with a generic error', function () {
    $s = seedScenario(1);
    loginGuest($s);

    $this->post('/guest/login', ['email' => 'ada@example.test', 'password' => 'WRONG'])->assertSessionHasErrors('email');
    $this->assertGuest('guest');
});

// ── Rate-limited after 5 failed attempts ───────────────────────────────────────────────────────────
it('rate-limits guest login after 5 failed attempts', function () {
    $s = seedScenario(1);
    loginGuest($s);

    foreach (range(1, 5) as $ignored) {
        $this->post('/guest/login', ['email' => 'ada@example.test', 'password' => 'WRONG']);
    }
    $response = $this->post('/guest/login', ['email' => 'ada@example.test', 'password' => 'correct horse battery']);

    $response->assertSessionHasErrors('email');
    expect((string) session('errors')->first('email'))->toContain('Too many');
    $this->assertGuest('guest');   // even the correct password is locked out
});

// ── REALM SEPARATION: a logged-in guest cannot reach /app or /owner ───────────────────────────────
it('keeps a logged-in guest out of /app and /owner', function () {
    $s = seedScenario(1);
    $guest = loginGuest($s);

    $this->post('/guest/login', ['email' => 'ada@example.test', 'password' => 'correct horse battery']);
    $this->assertAuthenticatedAs($guest, 'guest');

    // The web-guard middleware bounces a guest — /app and /owner are unreachable.
    $this->get('/app/dashboard')->assertRedirect();
    $this->get('/owner')->assertRedirect();
    $this->assertGuest('web');
});

// ── Password reset — NO enumeration, on the guest broker, token keyed on the blind index ──────────
it('resets a guest password with no enumeration and a blind-index token', function () {
    Mail::fake();
    $s = seedScenario(1);
    $guest = loginGuest($s, 'ada@example.test', 'old password');

    // A claimed email → neutral status + a link…
    $this->post('/guest/forgot-password', ['email' => 'ada@example.test'])->assertSessionHas('status');
    Mail::assertSent(GuestPasswordResetMail::class, fn (GuestPasswordResetMail $m): bool => $m->hasTo('ada@example.test'));
    // …an unknown email → the IDENTICAL neutral status, no link (no enumeration).
    $this->post('/guest/forgot-password', ['email' => 'nobody@example.test'])->assertSessionHas('status');
    Mail::assertNotSent(GuestPasswordResetMail::class, fn (GuestPasswordResetMail $m): bool => $m->hasTo('nobody@example.test'));

    // The token is stored keyed on the BLIND INDEX, never the plaintext email (#28).
    $token = Password::broker('guests')->createToken($guest);
    expect(DB::table('guest_password_reset_tokens')->where('email', Guest::emailHash('ada@example.test'))->exists())->toBeTrue();
    expect(DB::table('guest_password_reset_tokens')->where('email', 'ada@example.test')->exists())->toBeFalse();

    // Reset with the token → the password changes → the guest can log in with the new one.
    $this->post('/guest/reset-password', ['token' => $token, 'email' => 'ada@example.test', 'password' => 'brand new pw 123', 'password_confirmation' => 'brand new pw 123'])
        ->assertRedirect(route('guest.login'));
    expect(Hash::check('brand new pw 123', (string) $guest->fresh()->password))->toBeTrue();
    $this->post('/guest/login', ['email' => 'ada@example.test', 'password' => 'brand new pw 123'])->assertRedirect(route('guest.home'));
});
