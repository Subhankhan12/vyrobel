<?php

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PASSKEYS / WebAuthn (Wave B · GATE 4). The full cryptographic register + sign-in ceremony is proven END-TO-END
// in a real browser with a CDP virtual authenticator (documented in DECISIONS.md) — a PHP unit test cannot mint a
// valid authenticator signature. THESE tests lock the parts that ARE unit-testable + the security boundaries:
//   • the ceremony OPTIONS are real (a random challenge, stashed in session for the verify step);
//   • a garbage/forged response NEVER authenticates (the library validator rejects it → no login);
//   • remove is self-only + re-auth-gated; a cross-user id cannot delete another user's passkey;
//   • the Security page lists the user's own passkeys.

// ── 1. Registration options — authed, real challenge, stashed in session ──────────────────────────
it('mints registration options with a challenge for an authenticated user', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);

    $res = $this->actingAs($user)->postJson(route('owner.security.passkeys.options'));
    $res->assertOk()->assertJsonStructure(['challenge', 'rp', 'user', 'pubKeyCredParams']);
    expect(session()->has('passkey.creation'))->toBeTrue();
});

// ── 2. Sign-in options — guest-accessible, real challenge ─────────────────────────────────────────
it('mints passwordless sign-in options with a challenge', function () {
    seedScenario(1);

    $this->postJson(route('passkeys.login.options'))
        ->assertOk()->assertJsonStructure(['challenge', 'rpId']);
    expect(session()->has('passkey.request'))->toBeTrue();
});

// ── 3. ⚠️ A forged/garbage assertion NEVER authenticates ──────────────────────────────────────────
it('never logs in on a forged assertion', function () {
    seedScenario(1);

    // Prime the request options, then post a nonsense credential — the validator rejects it.
    $this->postJson(route('passkeys.login.options'))->assertOk();
    $this->postJson(route('passkeys.login'), [
        'id' => 'AAAA', 'rawId' => 'AAAA', 'type' => 'public-key',
        'response' => ['clientDataJSON' => 'AAAA', 'authenticatorData' => 'AAAA', 'signature' => 'AAAA'],
    ])->assertStatus(422);
    $this->assertGuest();
});

// ── 4. Remove — self-only + re-auth; a cross-user id cannot delete another user's passkey ─────────
it('removes a passkey only with the correct password, and never another user\'s', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $user->forceFill(['password' => 'pk-pass-1'])->save();

    $mine = Passkey::create(['user_id' => (string) $user->getKey(), 'credential_id' => 'cred-mine', 'public_key' => '{}', 'label' => 'My device']);
    $others = Passkey::create(['user_id' => (string) User::factory()->create()->getKey(), 'credential_id' => 'cred-other', 'public_key' => '{}']);

    // wrong password → refused, nothing deleted
    $this->actingAs($user)->post(route('owner.security.passkeys.remove', $mine->id), ['current_password' => 'nope'])
        ->assertSessionHasErrors('current_password');
    expect(Passkey::query()->whereKey($mine->id)->exists())->toBeTrue();

    // another user's passkey id → a no-op (self-only where(user_id)); it survives
    $this->actingAs($user)->post(route('owner.security.passkeys.remove', $others->id), ['current_password' => 'pk-pass-1'])
        ->assertRedirect();
    expect(Passkey::query()->whereKey($others->id)->exists())->toBeTrue();

    // own passkey + correct password → removed
    $this->actingAs($user)->post(route('owner.security.passkeys.remove', $mine->id), ['current_password' => 'pk-pass-1'])
        ->assertRedirect();
    expect(Passkey::query()->whereKey($mine->id)->exists())->toBeFalse();
});

// ── 5. The Security page lists the user's own passkeys ────────────────────────────────────────────
it('lists the user\'s passkeys on the Security page', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    Passkey::create(['user_id' => (string) $user->getKey(), 'credential_id' => 'cred-x', 'public_key' => '{}', 'label' => 'iPhone passkey', 'last_used_at' => now()]);

    $this->actingAs($user)->get('/owner/security')->assertOk()
        ->assertSee('Passkeys')
        ->assertSee('iPhone passkey');
});
