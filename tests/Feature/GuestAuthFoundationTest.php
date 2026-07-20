<?php

use App\Auth\GuestUserProvider;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// GUEST AUTH FOUNDATION (Phase 4a · Sub-gate 1) — Guest is Authenticatable on a SEPARATE 'guest' realm, alongside
// the operator/owner `web` realm WITHOUT disturbing it. Proven: password hashed + nullable-until-claimed +
// email_verified_at; a blind-index email lookup (the email is encrypted); REALM SEPARATION (a guest is not a web
// user and vice versa, at the provider level); GDPR erase also destroys the portal account.

/** A CLAIMED guest account (password + verified) on the seeded org. */
function claimedGuest(array $s, string $email = 'ada@example.test'): Guest
{
    $guest = $s['guest'];
    $guest->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $guest->password = 'correct horse battery';   // hashed by the cast
    $guest->email_verified_at = now();
    $guest->save();

    return $guest->fresh();
}

// ── Authenticatable: password hashed, nullable-until-claimed, verified, hasPortalAccount ───────────
it('makes a claimed guest a hashed, verified portal account', function () {
    $s = seedScenario(1);
    $guest = claimedGuest($s);

    expect($guest->password)->not->toBe('correct horse battery');   // hashed, never plaintext
    expect(Hash::check('correct horse battery', (string) $guest->password))->toBeTrue();
    expect($guest->email_verified_at)->not->toBeNull();
    expect($guest->hasPortalAccount())->toBeTrue();
    expect($guest)->toBeInstanceOf(Authenticatable::class);

    // An unclaimed booking-row (email, no password) is NOT a portal account.
    $unclaimed = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'X']);
    expect($unclaimed->fresh()->hasPortalAccount())->toBeFalse();
});

// ── Blind index: deterministic + normalised + keyed; auth lookup uses it ───────────────────────────
it('looks a guest up by a keyed, normalised blind index — never the encrypted email', function () {
    $s = seedScenario(1);
    $guest = claimedGuest($s);

    // Deterministic + normalised (case + surrounding whitespace) + sha256-hex length.
    expect(Guest::emailHash('  ADA@Example.test '))->toBe(Guest::emailHash('ada@example.test'));
    expect(strlen(Guest::emailHash('x@y.z')))->toBe(64);
    // The row carries the hash, and it matches the email.
    expect($guest->email_hash)->toBe(Guest::emailHash('ada@example.test'));

    /** @var GuestUserProvider $provider */
    $provider = Auth::createUserProvider('guests');
    expect($provider->retrieveByCredentials(['email' => 'ada@example.test'])?->getKey())->toBe($guest->getKey());
    expect($provider->retrieveByCredentials(['email' => 'nobody@example.test']))->toBeNull();     // unknown email
    // An unclaimed email (booking exists, no account) is NOT retrievable for login.
    Guest::create(['email' => 'carol@example.test', 'first_name' => 'Carol', 'last_name' => 'X']);
    expect($provider->retrieveByCredentials(['email' => 'carol@example.test']))->toBeNull();
});

// ── REALM SEPARATION: a guest is not a web user, and a user is not a guest (provider level) ────────
it('keeps the guest and web realms separate — neither provider resolves the other’s identity', function () {
    $s = seedScenario(1);
    $guest = claimedGuest($s);
    $user = User::factory()->create();

    $guestProvider = Auth::createUserProvider('guests');
    $webProvider = Auth::createUserProvider('users');

    expect($guestProvider)->toBeInstanceOf(GuestUserProvider::class);
    // A guest's id is NOT a web user…
    expect($webProvider->retrieveById($guest->getKey()))->toBeNull();
    // …and a user's id is NOT a guest.
    expect($guestProvider->retrieveById($user->getKey()))->toBeNull();
});

// ── REALM SEPARATION at the guard level: guest guard ⟂ web guard ──────────────────────────────────
it('authenticates a guest on the guest guard only — never the web guard, and vice versa', function () {
    $s = seedScenario(1);
    $guest = claimedGuest($s);
    $user = User::factory()->create();

    Auth::guard('guest')->loginUsingId($guest->getKey());
    expect(Auth::guard('guest')->check())->toBeTrue();
    expect(Auth::guard('guest')->user())->toBeInstanceOf(Guest::class);
    expect(Auth::guard('web')->check())->toBeFalse();   // a guest is NOT authenticated on web (→ /app, /owner reject)

    Auth::guard('guest')->logout();

    Auth::guard('web')->loginUsingId($user->getKey());
    expect(Auth::guard('web')->check())->toBeTrue();
    expect(Auth::guard('guest')->check())->toBeFalse();   // an operator/owner is NOT a guest
});

// ── GDPR erase also destroys the portal account (a guest can never log in again) ──────────────────
it('destroys the portal account when the guest is anonymized', function () {
    $s = seedScenario(1);
    $guest = claimedGuest($s);
    $provider = Auth::createUserProvider('guests');
    expect($provider->retrieveByCredentials(['email' => 'ada@example.test']))->not->toBeNull();

    $guest->anonymize();

    $fresh = Guest::withoutOrganizationScope()->findOrFail($guest->getKey());
    expect($fresh->password)->toBeNull();
    expect($fresh->email_verified_at)->toBeNull();
    expect($fresh->email_hash)->toBeNull();
    expect($fresh->hasPortalAccount())->toBeFalse();
    expect($provider->retrieveByCredentials(['email' => 'ada@example.test']))->toBeNull();   // erased → unreachable
});
