<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// IN-APP CHANGE PASSWORD (Auth · Wave 4 follow-on) — a logged-in operator or owner changes their OWN
// password. The security contract, asserted:
//   • the CURRENT password is verified before any change (anti-hijack guard) — a wrong one changes nothing;
//   • the user is resolved from auth() ONLY — one account's session can never change another's password;
//   • the new password meets the reset rules (min 8, confirmed);
//   • the hash + remember-token rotation is the SHARED reset path (User::changePasswordTo) — other remembered
//     sessions are invalidated, this session stays valid;
//   • the routes are behind auth; the operator page is reachable by EVERY back-office role, not just managers.

/** An operator with a KNOWN current password (the `current_password` rule checks against the real hash). */
function operatorWithPassword(array $s, string $password, string $role = 'Front Desk', string $name = 'Uma User'): User
{
    return operatorUser($s, ['name' => $name, 'password' => $password], $role);
}

// ── 1. THE HAPPY PATH — correct current password → changed, hashed, remember-token rotated ────────
it('changes an operator’s password with the correct current password and rotates the remember token', function () {
    $s = seedScenario(1);
    $user = operatorWithPassword($s, 'current-secret-1');
    $user->forceFill(['remember_token' => 'OLD-REMEMBER-TOKEN'])->save();

    $this->actingAs($user)->post('/app/security/password', [
        'current_password' => 'current-secret-1',
        'password' => 'new-strong-pass-9',
        'password_confirmation' => 'new-strong-pass-9',
    ])->assertRedirect(route('app.security'))->assertSessionHas('password_status');

    $fresh = $user->fresh();
    expect(Hash::check('new-strong-pass-9', $fresh->password))->toBeTrue()   // the new password works…
        ->and(Hash::check('current-secret-1', $fresh->password))->toBeFalse() // …the old one no longer does
        ->and($fresh->remember_token)->not->toBe('OLD-REMEMBER-TOKEN')        // other remembered sessions killed
        ->and(strlen((string) $fresh->remember_token))->toBe(60);             // rotated to a fresh 60-char token
});

// ── 2. 🔒 THE ANTI-HIJACK GUARD — a WRONG current password changes NOTHING ───────────────────────
it('rejects the change when the current password is wrong, and leaves the password untouched', function () {
    $s = seedScenario(1);
    $user = operatorWithPassword($s, 'current-secret-2');

    $this->actingAs($user)->post('/app/security/password', [
        'current_password' => 'not-the-current-password',
        'password' => 'attacker-chosen-pw-9',
        'password_confirmation' => 'attacker-chosen-pw-9',
    ])->assertSessionHasErrors('current_password');

    // The password is EXACTLY as it was — a left-open/hijacked session cannot change it without the current one.
    $fresh = $user->fresh();
    expect(Hash::check('current-secret-2', $fresh->password))->toBeTrue()
        ->and(Hash::check('attacker-chosen-pw-9', $fresh->password))->toBeFalse();
});

// ── 3. THE NEW PASSWORD MUST BE CONFIRMED + MEET THE RULES ────────────────────────────────────────
it('rejects a mismatched confirmation and a too-short new password, changing nothing', function () {
    $s = seedScenario(1);
    $user = operatorWithPassword($s, 'current-secret-3');

    // Mismatched confirmation.
    $this->actingAs($user)->post('/app/security/password', [
        'current_password' => 'current-secret-3',
        'password' => 'a-good-new-pass-9',
        'password_confirmation' => 'different-pass-9',
    ])->assertSessionHasErrors('password');

    // Too short (< 8) — the same rule the reset flow enforces.
    $this->actingAs($user)->post('/app/security/password', [
        'current_password' => 'current-secret-3',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('current-secret-3', $user->fresh()->password))->toBeTrue();   // unchanged
});

// ── 4. ⚠️ auth()-RESOLVED — one session can ONLY change its OWN password, never another user's ────
it('changes only the acting user’s password, never another user’s (no user id in the request)', function () {
    $s = seedScenario(1);
    $actor = operatorWithPassword($s, 'actor-secret', 'Front Desk', 'Actor One');
    $other = operatorWithPassword($s, 'other-secret', 'Hotel Manager', 'Other Two');

    // The actor changes a password. There is NO user id anywhere in the request — the controller resolves
    // the target from auth() alone, so it can only be the actor.
    $this->actingAs($actor)->post('/app/security/password', [
        'current_password' => 'actor-secret',
        'password' => 'actor-new-pass-9',
        'password_confirmation' => 'actor-new-pass-9',
    ])->assertRedirect();

    expect(Hash::check('actor-new-pass-9', $actor->fresh()->password))->toBeTrue()
        // The OTHER user is completely untouched — their password still verifies against their own original.
        ->and(Hash::check('other-secret', $other->fresh()->password))->toBeTrue();
});

// ── 5. THE OWNER SIDE — same guard, same shared path ─────────────────────────────────────────────
it('lets an owner change their own password with the current password verified', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $a['user']->forceFill(['password' => 'owner-current', 'remember_token' => 'OWNER-OLD'])->save();

    $this->actingAs($a['user'])->post('/owner/security/password', [
        'current_password' => 'owner-current',
        'password' => 'owner-new-pass-9',
        'password_confirmation' => 'owner-new-pass-9',
    ])->assertRedirect(route('owner.security'))->assertSessionHas('password_status');

    $fresh = $a['user']->fresh();
    expect(Hash::check('owner-new-pass-9', $fresh->password))->toBeTrue()
        ->and($fresh->remember_token)->not->toBe('OWNER-OLD');
});

it('rejects an owner’s change with a wrong current password', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $a['user']->forceFill(['password' => 'owner-current-2'])->save();

    $this->actingAs($a['user'])->post('/owner/security/password', [
        'current_password' => 'wrong',
        'password' => 'owner-new-pass-9',
        'password_confirmation' => 'owner-new-pass-9',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('owner-current-2', $a['user']->fresh()->password))->toBeTrue();   // unchanged
});

// ── 6. THE ROUTES ARE BEHIND auth; the operator page is reachable by ANY back-office role ────────
it('requires auth for the change-password write and the security page', function () {
    $this->post('/app/security/password', [])->assertRedirect(route('login'));
    $this->post('/owner/security/password', [])->assertRedirect(route('login'));
    $this->get('/app/security')->assertRedirect(route('login'));
});

it('shows the operator Security page to a non-manager role (not gated like /app/settings)', function () {
    $s = seedScenario(1);

    // Housekeeping holds neither properties.manage nor settings.manage — it cannot reach /app/settings, but
    // it MUST be able to change its own password, so /app/security is behind #26 only.
    $this->actingAs(operatorUser($s, ['name' => 'Hattie'], 'Housekeeping'))
        ->get('/app/security')->assertOk()->assertSee('Change password');

    // …while the manager-gated settings page still 403s that role (the two are deliberately different gates).
    $this->actingAs(operatorUser($s, ['name' => 'Hattie'], 'Housekeeping'))
        ->get('/app/settings')->assertForbidden();
});

// ── 7. THE SHARED PATH — reset still works (unregressed) and rotates the remember token ───────────
it('keeps the shared hash+rotate path working for a direct changePasswordTo (what reset also calls)', function () {
    $s = seedScenario(1);
    $user = operatorWithPassword($s, 'before-secret');
    $user->forceFill(['remember_token' => 'BEFORE-TOKEN'])->save();

    $user->changePasswordTo('after-secret-9');

    $fresh = $user->fresh();
    expect(Hash::check('after-secret-9', $fresh->password))->toBeTrue()
        ->and(Hash::check('before-secret', $fresh->password))->toBeFalse()
        ->and($fresh->remember_token)->not->toBe('BEFORE-TOKEN')
        ->and(strlen((string) $fresh->remember_token))->toBe(60);
});
