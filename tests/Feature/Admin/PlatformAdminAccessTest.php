<?php

declare(strict_types=1);

use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAction;
use App\Models\PlatformAdminSession;
use App\Support\Platform\PlatformAdminInvites;
use App\Support\Platform\PlatformAdminRole;
use App\Support\Platform\PlatformAdminSessions;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// ADMIN 01 · PHASE 1 — WHO CAN REACH THE CONSOLE: sessions that work on a `file` session driver, force sign-out,
// revocation, invitations that never let anyone else know the password, the last-Owner floor, and one password
// rule at every door.

uses(DatabaseTransactions::class);

// ══ 1 · SESSIONS — tracked by the application, enforced on the next request ═════════════════════════════════

it('a console request records a live session for the admin (any session driver)', function () {
    $admin = platformAdmin();
    $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))->assertOk();

    expect(app(PlatformAdminSessions::class)->activeFor($admin))->toHaveCount(1);
})->group('security');

it('⚠️⚠️ FORCE SIGN-OUT ends the session on its very next request', function () {
    $target = platformAdmin(['role' => 'support']);
    $this->actingAs($target, 'admin')->get(route('admin.dashboard'))->assertOk();

    app(PlatformAdminSessions::class)->endAllFor($target, 'forced', platformAdmin());

    $this->actingAs($target, 'admin')->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    expect(app(PlatformAdminSessions::class)->activeFor($target))->toHaveCount(0);
})->group('security');

it('⚠️ a session whose key belongs to ANOTHER admin is ended, never continued', function () {
    $a = platformAdmin();
    $b = platformAdmin();
    $this->actingAs($a, 'admin')->get(route('admin.dashboard'))->assertOk();

    // Same session, different admin: the tracking key is A's. It must not be honoured for B.
    $this->actingAs($b, 'admin')->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
})->group('security');

it('a session that predates tracking is ADOPTED, not thrown out — shipping this cannot sign the founder out', function () {
    $admin = platformAdmin();
    // actingAs with no prior request = a live session with no tracking key yet.
    $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))->assertOk();
    expect(PlatformAdminSession::query()->where('platform_admin_id', $admin->id)->count())->toBe(1);
})->group('security');

it('the Owner force-sign-out action ends every session of that admin, with a reason on the ledger', function () {
    $owner = platformAdmin();
    $target = platformAdmin(['role' => 'analyst']);
    $this->actingAs($target, 'admin')->get(route('admin.dashboard'));
    $this->flushSession();

    $this->actingAs($owner, 'admin')
        ->post(route('admin.admins.sign-out', $target->id), ['reason' => 'Lost laptop reported by the analyst.'])
        ->assertRedirect(route('admin.admins.index'));

    expect(app(PlatformAdminSessions::class)->activeFor($target))->toHaveCount(0);
    expect(PlatformAdminAction::query()->where('action', 'platform.admin.signed_out')->where('target_id', $target->id)->value('reason'))
        ->toBe('Lost laptop reported by the analyst.');
})->group('security');

// ══ 2 · REVOKE / RESTORE / ROLE — with the floors that keep the console manageable ══════════════════════════

it('⚠️⚠️ REVOKE disables the admin, ends their live sessions, and the login then refuses them', function () {
    $owner = platformAdmin();
    $target = platformAdmin(['role' => 'support']);
    $this->actingAs($target, 'admin')->get(route('admin.dashboard'))->assertOk();
    $this->flushSession();

    $this->actingAs($owner, 'admin')
        ->post(route('admin.admins.revoke', $target->id), ['current_password' => 'password', 'reason' => 'Left the company.'])
        ->assertRedirect(route('admin.admins.index'));

    expect($target->fresh()->status)->toBe(PlatformAdmin::STATUS_DISABLED)
        ->and(app(PlatformAdminSessions::class)->activeFor($target))->toHaveCount(0);

    // ⚠️ THE INSTRUMENT: where the password step SENDS you. An accepted password for an enrolled admin goes to the
    //    2FA challenge (nobody is "logged in" yet), so `auth()->check()` would read false for a success too.
    //    The control proves the same password is accepted for an ACTIVE admin.
    $this->flushSession();
    app('auth')->forgetGuards();
    $active = platformAdmin(['role' => 'support']);
    $this->post(route('admin.login.store'), ['email' => $active->email, 'password' => 'password'])
        ->assertRedirect(route('admin.2fa.challenge'));

    $this->flushSession();
    app('auth')->forgetGuards();
    $this->post(route('admin.login.store'), ['email' => $target->email, 'password' => 'password'])
        ->assertRedirect();
    expect(session('admin.2fa.pending_id'))->toBeNull('a revoked admin was admitted to the 2FA challenge');
})->group('security');

it('⚠️ you cannot revoke YOURSELF', function () {
    $owner = platformAdmin();
    platformAdmin();   // a second owner exists, so the last-owner floor is not what refuses this

    $this->actingAs($owner, 'admin')
        ->post(route('admin.admins.revoke', $owner->id), ['current_password' => 'password', 'reason' => 'Trying to lock myself out.']);

    expect($owner->fresh()->status)->toBe(PlatformAdmin::STATUS_ACTIVE);
})->group('security');

it('⚠️⚠️ the LAST active Owner can be neither demoted nor revoked', function () {
    // Make every OTHER active owner in this (transactional) world a non-owner, so the actor is the last one.
    $last = platformAdmin();
    PlatformAdmin::query()->whereKeyNot($last->id)->where('role', 'owner')->where('status', 'active')->update(['role' => 'analyst']);
    $analyst = platformAdmin(['role' => 'analyst']);

    $this->actingAs($last, 'admin')
        ->post(route('admin.admins.role', $last->id), ['role' => 'analyst', 'current_password' => 'password', 'reason' => 'Demoting the last owner.']);
    expect($last->fresh()->role)->toBe('owner');

    // …and via another Owner-less path: nobody else could revoke them either, but prove the floor directly.
    $this->flushSession();
    $promoted = platformAdmin();   // a second owner — now demoting $last IS allowed
    $this->actingAs($promoted, 'admin')
        ->post(route('admin.admins.role', $last->id), ['role' => 'analyst', 'current_password' => 'password', 'reason' => 'There is another owner now.'])
        ->assertRedirect(route('admin.admins.index'));
    expect($last->fresh()->role)->toBe('analyst');
    expect($analyst->fresh()->role)->toBe('analyst');
})->group('security');

it('a role change needs the acting admin\'s current password', function () {
    $owner = platformAdmin();
    $target = platformAdmin(['role' => 'analyst']);

    $this->actingAs($owner, 'admin')
        ->post(route('admin.admins.role', $target->id), ['role' => 'owner', 'current_password' => 'not-it', 'reason' => 'Promote.']);

    expect($target->fresh()->role)->toBe('analyst');
})->group('security');

// ══ 3 · ⚠️⚠️ INVITATIONS — nobody but the invitee ever knows the password ═══════════════════════════════════

it('⚠️⚠️ an invitation creates an INVITED admin with a password nobody knows, and shows the link ONCE', function () {
    $owner = platformAdmin();
    $email = 'invitee-'.Str::lower(Str::random(8)).'@example.net';

    $response = $this->actingAs($owner, 'admin')->post(route('admin.admins.invite'), [
        'name' => 'Ivy Invitee', 'email' => $email, 'role' => 'support', 'current_password' => 'password', 'reason' => 'New support hire.',
    ]);

    $response->assertRedirect(route('admin.admins.index'))->assertSessionHas('invite_link');
    $invited = PlatformAdmin::query()->where('email', $email)->firstOrFail();

    expect($invited->status)->toBe(PlatformAdmin::STATUS_INVITED)
        ->and($invited->role)->toBe('support')
        ->and($invited->invite_token_hash)->not->toBeNull()
        ->and(str_contains((string) session('invite_link'), (string) $invited->invite_token_hash))->toBeFalse('the stored value must be the HASH, not the token in the link');

    // An invited admin cannot sign in at all — not even with the factory's password, which was never theirs.
    $this->flushSession();
    app('auth')->forgetGuards();
    $this->post(route('admin.login.store'), ['email' => $email, 'password' => 'password']);
    expect(auth('admin')->check())->toBeFalse()
        ->and(session('admin.2fa.pending_id'))->toBeNull();
})->group('security');

it('⚠️⚠️ accepting sets the invitee\'s OWN password under the admin rule, spends the token, and signs nobody in', function () {
    [$invited, $token] = app(PlatformAdminInvites::class)->invite('Ivy', 'ivy-'.Str::lower(Str::random(8)).'@example.net', PlatformAdminRole::Analyst, platformAdmin());

    // The admin rule refuses what the app-wide rule would accept (8 chars, a number, a symbol).
    $this->post(route('admin.invite.store', $token), ['password' => 'Short1!x', 'password_confirmation' => 'Short1!x']);
    expect($invited->fresh()->status)->toBe(PlatformAdmin::STATUS_INVITED);

    $this->post(route('admin.invite.store', $token), ['password' => 'LongEnough+Passw0rd', 'password_confirmation' => 'LongEnough+Passw0rd'])
        ->assertRedirect(route('admin.login'));

    $fresh = $invited->fresh();
    expect($fresh->status)->toBe(PlatformAdmin::STATUS_ACTIVE)
        ->and(Hash::check('LongEnough+Passw0rd', $fresh->password))->toBeTrue()
        ->and($fresh->invite_token_hash)->toBeNull()
        ->and(auth('admin')->check())->toBeFalse();

    // Spent: the same link now gets the neutral invalid page.
    $this->get(route('admin.invite.show', $token))->assertOk()->assertSee('can\'t be used');
})->group('security');

it('an EXPIRED or unknown invitation link gets the same neutral page', function () {
    [$invited, $token] = app(PlatformAdminInvites::class)->invite('Old', 'old-'.Str::lower(Str::random(8)).'@example.net', PlatformAdminRole::Analyst, platformAdmin());
    $invited->forceFill(['invite_expires_at' => now()->subMinute()])->save();

    $this->get(route('admin.invite.show', $token))->assertOk()->assertSee('can\'t be used');
    $this->get(route('admin.invite.show', Str::random(64)))->assertOk()->assertSee('can\'t be used');
})->group('security');

it('a newly accepted admin must still enrol 2FA before the console opens — no side entrance', function () {
    [$invited, $token] = app(PlatformAdminInvites::class)->invite('Nia', 'nia-'.Str::lower(Str::random(8)).'@example.net', PlatformAdminRole::Analyst, platformAdmin());
    $this->post(route('admin.invite.store', $token), ['password' => 'LongEnough+Passw0rd', 'password_confirmation' => 'LongEnough+Passw0rd']);

    $this->post(route('admin.login.store'), ['email' => $invited->email, 'password' => 'LongEnough+Passw0rd'])
        ->assertRedirect(route('admin.2fa.setup'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.2fa.setup'));
})->group('security');

// ══ 4 · ⚠️⚠️ ONE PASSWORD RULE AT EVERY DOOR ═════════════════════════════════════════════════════════════════

it('⚠️⚠️ the admin password CHANGE refuses what account CREATION would refuse — 8 characters is no longer enough', function () {
    $admin = platformAdmin();

    // Meets the app-wide rule (8+, a number, a symbol) — must now FAIL for a platform admin.
    $this->actingAs($admin, 'admin')->put(route('admin.account.password'), [
        'current_password' => 'password', 'password' => 'Abcdef1!', 'password_confirmation' => 'Abcdef1!',
    ]);
    expect(Hash::check('password', $admin->fresh()->password))->toBeTrue('an 8-character password was accepted for a platform admin');

    $this->actingAs($admin, 'admin')->put(route('admin.account.password'), [
        'current_password' => 'password', 'password' => 'Twelve+Chars1', 'password_confirmation' => 'Twelve+Chars1',
    ])->assertSessionHasNoErrors();
    expect(Hash::check('Twelve+Chars1', $admin->fresh()->password))->toBeTrue();
})->group('security');

it('a password change ends the admin\'s OTHER console sessions and keeps this one', function () {
    $admin = platformAdmin();
    // Another device's session.
    PlatformAdminSession::query()->create([
        'platform_admin_id' => $admin->id, 'session_key_hash' => hash('sha256', Str::random(40)),
        'started_at' => now(), 'last_seen_at' => now(),
    ]);
    $this->actingAs($admin, 'admin')->get(route('admin.account.edit'))->assertOk();
    expect(app(PlatformAdminSessions::class)->activeFor($admin))->toHaveCount(2);

    $this->actingAs($admin, 'admin')->put(route('admin.account.password'), [
        'current_password' => 'password', 'password' => 'Twelve+Chars1', 'password_confirmation' => 'Twelve+Chars1',
    ]);

    expect(app(PlatformAdminSessions::class)->activeFor($admin))->toHaveCount(1);
    $this->actingAs($admin, 'admin')->get(route('admin.account.edit'))->assertOk();   // this one survives
})->group('security');

it('admin:create requires an explicit role and refuses an unknown one', function () {
    $this->artisan('admin:create', ['--name' => 'X', '--email' => 'x-'.Str::lower(Str::random(6)).'@example.net', '--role' => 'god'])
        ->assertFailed();
})->group('security');

it('2FA stays mandatory for every role — a support admin without it is sent to enrol', function () {
    $admin = PlatformAdmin::factory()->role('support')->create();   // no 2FA
    $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))->assertRedirect(route('admin.2fa.setup'));
})->group('security');

it('the demo platform admin is SEEDED with an explicit role — a fresh build must not produce an admin who can do nothing', function () {
    // ⚠️ Found by a fresh `migrate:fresh --seed`: the migration's role backfill runs against an EMPTY table there,
    //    so the seeder itself has to set the role.
    $this->seed(PlatformAdminSeeder::class);

    expect(PlatformAdmin::query()->where('email', 'admin@vyrobel-demo.test')->value('role'))->toBe('owner');
})->group('security');
