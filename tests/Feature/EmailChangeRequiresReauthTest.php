<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// ══ AUDIT 2026-08-29 · H-3 — CHANGING THE LOGIN EMAIL MUST RE-AUTHENTICATE ═══════════════════════════
//
// ⚠️ The owner path validated ONLY the address. That made a hijacked session PERMANENT: park an attacker's
// address, open the confirmation from THAT inbox — the verify route is public + `signed`, so it needs no
// session at all — and users.email MOVES. A password reset then goes to the attacker.
// ⚠️ REPRODUCED end to end before the fix: users.email became attacker-inbox@evil.test, and the OLD address
// was never notified (one message was sent, to the new address).
//
// ⚠️ WHY THE OWNER WAS THE OUTLIER — chronology, not a different template. The owner email change shipped
// 2026-07-25 (bc33f33); the platform-admin one 2026-07-27 (065b554) and it carried `current_password:admin`
// from its FIRST commit. The requirement arrived two days later and was never back-applied.
//
// ⚠️ GUARD NAME VERIFIED, NOT ASSUMED: config/auth.php defines web · guest · admin — there is NO `owner`
// guard, and the owner portal runs on plain `auth` (the default `web` guard), so the rule is the BARE
// `current_password`. `current_password:owner` would have silently never matched a guard.

/**
 * A real portal owner (built on the shared helper the rest of the suite uses) with a KNOWN password.
 *
 * ⚠️ NAMED `reauthOwnerActor`, NOT `ownerPortalUser`. Pest loads every test file's top-level functions into
 * ONE global namespace, and NotificationWiringTest:42 already defines `ownerPortalUser(array, int, string)`.
 * A duplicate name loads first alphabetically and silently HIJACKS the other file's helper — three
 * NotificationWiring tests died with "Argument #2 must be of type array, User given" while both files passed
 * ALONE. That is the documented "green as an artefact of file load order" trap, authored by hand.
 */
function reauthOwnerActor(): User
{
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $user->changePasswordTo('secret-Passw0rd!');   // the ONE shared hash+rotate path

    return $user->fresh();
}

// ── 1. ⛔ REFUSED without the current password — and nothing is parked ───────────────────────────────
it('refuses an owner email change with no password, and parks nothing', function () {
    Mail::fake();
    $user = reauthOwnerActor();

    $this->actingAs($user)
        ->from(route('owner.settings'))
        ->post(route('owner.account.email'), ['email' => 'attacker@evil.test'])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->pending_email)->toBeNull();
    Mail::assertNothingSent();
});

// ── 2. ⛔ REFUSED with the WRONG password ───────────────────────────────────────────────────────────
it('refuses an owner email change with the wrong password', function () {
    Mail::fake();
    $user = reauthOwnerActor();

    $this->actingAs($user)
        ->from(route('owner.settings'))
        ->post(route('owner.account.email'), [
            'current_password' => 'not-the-password',
            'email' => 'attacker@evil.test',
        ])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->pending_email)->toBeNull();
});

// ── 3. ✅ THE OTHER HALF — it still WORKS with the correct password ─────────────────────────────────
// ⚠️ A refusal-only test cannot tell a working rule from a broken form. This is why the password input was
// added to the owner settings view in the same change: a rule with no field is a feature that always fails.
it('accepts an owner email change with the correct password', function () {
    Mail::fake();
    $user = reauthOwnerActor();

    $this->actingAs($user)
        ->from(route('owner.settings'))
        ->post(route('owner.account.email'), [
            'current_password' => 'secret-Passw0rd!',
            'email' => 'new@example.test',
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->pending_email)->toBe('new@example.test');
    expect($user->fresh()->email)->toBe($user->email);   // the live login does NOT move yet
});

// ── 4. ⛔ THE CLASS — EVERY email-change implementation re-authenticates ─────────────────────────────
// ⚠️ DISCOVERED, NOT LISTED. A hand-written list is how H-5 happened (a suite naming five of ninety-seven
// models). Any controller that parks a `pending_email` must re-authenticate, including one added tomorrow.
it('requires re-authentication in every email-change implementation', function () {
    $implementations = [];

    foreach (File::allFiles(app_path('Http/Controllers')) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $source = File::get($file->getPathname());

        // A controller that PARKS a pending email is an email-change implementation.
        if (! str_contains($source, "'pending_email' =>")) {
            continue;
        }

        $implementations[str_replace(base_path().'/', '', $file->getPathname())] =
            str_contains($source, "'current_password'");
    }

    expect(count($implementations))->toBeGreaterThanOrEqual(2);   // not vacuous: it found the real ones

    $missing = array_keys(array_filter($implementations, fn (bool $ok): bool => ! $ok));

    expect($missing)->toBe([],
        'These controllers park a pending_email without re-authenticating — a hijacked session can move the '
        .'login identity permanently: '.implode(', ', $missing));
});
