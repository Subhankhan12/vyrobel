<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// Auth Phase A — the shared Vyrobel login (operator + owner). Real session auth: hashed passwords,
// rate limiting, generic (non-enumerating) errors, session regeneration, role-based redirect, and the
// guard on /app + /owner (with /book staying public).

// The operator fixture is the SHARED operatorUser() (tests/Support/auth_helpers.php): an active member
// holding a REAL back-office role. This file's local copy — a Membership and nothing else — was never a
// realistic operator (an owner has a Membership too) and would now be locked out of /app by
// EnsureBackOfficeAccess. The shared helper takes attributes as an array: operatorUser($s, ['email' => …]).

/**
 * An active org member ALSO linked to an Owner — routes to the owner portal.
 *
 * Deliberately does NOT build on operatorUser(): an owner must hold NO back-office role. That is the
 * boundary EnsureBackOfficeAccess enforces, so granting one here would erase the distinction this file
 * asserts (owner → /owner, operator → /app).
 */
function ownerUser(array $scenario, string $email, string $password = 'password'): User
{
    $user = User::factory()->create(['email' => $email, 'password' => $password, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    Owner::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    return $user;
}

// ── The form renders ─────────────────────────────────────────────────────────────────────────────
it('renders the styled login form', function () {
    $this->get('/login')->assertOk()->assertSee('Welcome back')->assertSee('Sign in');
});

// ── Success → role routing + real context downstream ─────────────────────────────────────────────
it('logs an operator in and routes to the app with tenant context set', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['email' => 'operator@vyrobel.test', 'password' => 'password']);

    $this->post('/login', ['email' => 'operator@vyrobel.test', 'password' => 'password'])
        ->assertRedirect('/app/dashboard');
    $this->assertAuthenticatedAs($user);

    // The now-authenticated request resolves the org (no shim) — /app renders, not a guard 500.
    $this->get('/app/dashboard')->assertOk();
});

it('logs an owner in and routes to the owner portal with owner scope active', function () {
    $s = seedScenario(1);
    $user = ownerUser($s, 'owner@vyrobel.test');

    $this->post('/login', ['email' => 'owner@vyrobel.test', 'password' => 'password'])
        ->assertRedirect('/owner');
    $this->assertAuthenticatedAs($user);

    $this->get('/owner')->assertOk();   // owner scope resolves from the authed user (fail-closed if none)
});

it('updates last_login_at on success', function () {
    $s = seedScenario(1);
    operatorUser($s, ['email' => 'stamp@vyrobel.test', 'password' => 'password']);

    $this->post('/login', ['email' => 'stamp@vyrobel.test', 'password' => 'password']);
    $this->assertDatabaseMissing('users', ['email' => 'stamp@vyrobel.test', 'last_login_at' => null]);
});

// ── Failure → generic error, NO user enumeration, not authenticated ──────────────────────────────
it('rejects a wrong password with a generic error and no session', function () {
    $s = seedScenario(1);
    operatorUser($s, ['email' => 'real@vyrobel.test', 'password' => 'password']);

    $this->post('/login', ['email' => 'real@vyrobel.test', 'password' => 'WRONG'])
        ->assertSessionHasErrors(['email' => trans('auth.failed')]);
    $this->assertGuest();
});

it('gives the SAME generic error for an unknown email (no enumeration)', function () {
    $s = seedScenario(1);
    operatorUser($s, ['email' => 'exists@vyrobel.test', 'password' => 'password']);

    // Identical message → an attacker can't tell a real account from a fake one.
    $this->post('/login', ['email' => 'exists@vyrobel.test', 'password' => 'WRONG'])
        ->assertSessionHasErrors(['email' => trans('auth.failed')]);
    $this->post('/login', ['email' => 'nobody@vyrobel.test', 'password' => 'whatever'])
        ->assertSessionHasErrors(['email' => trans('auth.failed')]);
    $this->assertGuest();
});

it('refuses an inactive account with the same generic error', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['email' => 'suspended@vyrobel.test', 'password' => 'password']);
    $user->update(['status' => 'suspended']);

    $this->post('/login', ['email' => 'suspended@vyrobel.test', 'password' => 'password'])
        ->assertSessionHasErrors(['email' => trans('auth.failed')]);
    $this->assertGuest();
});

// ── Brute-force rate limiting ────────────────────────────────────────────────────────────────────
it('rate-limits after 5 failed attempts', function () {
    $s = seedScenario(1);
    operatorUser($s, ['email' => 'target@vyrobel.test', 'password' => 'password']);
    RateLimiter::clear(Str::lower('target@vyrobel.test').'|127.0.0.1');

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'target@vyrobel.test', 'password' => 'WRONG'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }

    // 6th attempt — even with the CORRECT password — is throttled (blocked), not authenticated.
    // (A would-be-successful login rejected == the limiter is doing its job.)
    $this->post('/login', ['email' => 'target@vyrobel.test', 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

// ── Logout → session torn down ───────────────────────────────────────────────────────────────────
it('logs out, invalidates the session, and blocks the app afterwards', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['email' => 'bye@vyrobel.test', 'password' => 'password']);

    $this->actingAs($user)->get('/app/dashboard')->assertOk();
    $this->post('/logout')->assertRedirect('/login');
    $this->assertGuest();

    // No lingering session — the app now bounces to login.
    $this->get('/app/dashboard')->assertRedirect('/login');
});

// ── The guard: unauthenticated app/owner → login; /book stays public ─────────────────────────────
it('guards /app and /owner but keeps /book public', function () {
    $this->get('/app/dashboard')->assertRedirect('/login');
    $this->get('/owner')->assertRedirect('/login');
    $this->get('/app/units')->assertRedirect('/login');

    $pub = publicPropertyScenario();
    $this->get("/book/{$pub['property']->slug}")->assertOk();   // public — no auth, no redirect
});

// ── Already-authenticated visitors are bounced off the login form ────────────────────────────────
it('redirects an already-authenticated user away from the login form', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['email' => 'here@vyrobel.test', 'password' => 'password']);

    $this->actingAs($user)->get('/login')->assertRedirect('/app/dashboard');
});

// ══════════════════════════════════════════════════════════════════════════════════════════════════
// AUTH THROTTLE (audit 2026-08-07 · F-2a-04) — none of the four login endpoints had ANY rate limit.
// Password-reset and signup were throttled, and the Lockout event was already being recorded, so the
// design expected this; only the middleware was missing. 30 attempts across DISTINCT emails from one IP
// were all accepted.
//
// TWO limits, because either alone leaves a hole — and this file pins BOTH, since a single-limit fix
// would pass a naive test while leaving credential stuffing wide open.
// ══════════════════════════════════════════════════════════════════════════════════════════════════

it('keeps the per-credential throttle in the FormRequest, with its friendly error', function () {
    $s = seedScenario(1);
    operatorUser($s, ['email' => 'spray@vyrobel.test', 'password' => 'password']);
    RateLimiter::clear(Str::lower('spray@vyrobel.test').'|127.0.0.1');

    // The PRE-EXISTING per-credential limit (LoginRequest::ensureIsNotRateLimited) answers with a validation
    // error, not a 429 — deliberately better UX. The new per-IP limiter must not swallow that.
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'spray@vyrobel.test', 'password' => 'WRONG'])
            ->assertSessionHasErrors('email');
    }

    $this->post('/login', ['email' => 'spray@vyrobel.test', 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
})->group('throttle');

it('throttles CREDENTIAL STUFFING, where every attempt uses a different email', function () {
    // THE HOLE THE PER-CREDENTIAL LIMIT NEVER CLOSES: each attempt uses a DIFFERENT email, so it fills a
    // different bucket and the per-credential limiter never trips. The audit ran 30 such attempts from one
    // IP and every one was accepted. The per-IP limiter is what catches it.
    $status = null;

    for ($i = 1; $i <= 30; $i++) {
        $status = $this->post('/login', ['email' => "user{$i}@example.test", 'password' => 'x'])->getStatusCode();

        if ($status === 429) {
            break;
        }
    }

    expect($status)->toBe(429);
})->group('throttle');

it('applies the per-IP throttle to the guest and platform-admin logins too', function () {
    foreach (['/guest/login', '/admin/login'] as $endpoint) {
        $status = null;

        for ($i = 1; $i <= 30; $i++) {
            $status = $this->post($endpoint, ['email' => "u{$i}@example.test", 'password' => 'x'])->getStatusCode();

            if ($status === 429) {
                break;
            }
        }

        expect($status)->toBe(429, "no per-IP throttle on {$endpoint}");
    }
})->group('throttle');
