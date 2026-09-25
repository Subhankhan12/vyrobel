<?php

declare(strict_types=1);

use App\Models\Guest;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminSession;
use App\Models\User;
use App\Support\Platform\PlatformAdminSessions;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/*
 * ⚠️⚠️ SESSIONS 01 (L16) — SIGN-OUT MEANS SIGNED OUT, proven the way an intruder would meet it.
 *
 * Two contexts, one account. "A" is the owner of the account at their own device; "B" is someone else holding a
 * session of the same account (a stolen cookie, a shared computer). A test cannot hold two browsers, so B is modelled
 * exactly as a real second session looks to the server: the same account, carrying the password hash it signed in
 * with (`password_hash_<guard>`, in the form Laravel itself stores it). Measured on production 2026-09-25: the session
 * driver was `file`, so every "end the other sessions" path deleted rows from an empty table — and nothing else could
 * reach those sessions. Each scenario below failed there.
 *
 * ⚠️ AND THE ORDINARY CASE: the session that changed the password stays signed in. A fix that signs you out of the
 * tab you are using has swapped one bug for another — it is asserted in every scenario, not assumed.
 */

uses(DatabaseTransactions::class);

const SM_OLD = 'Str0ng-Old-Pass!A';
const SM_NEW = 'Str0ng-New-Pass!B';

/** What a session signed in on `$guard` carries: the password hash it signed in with, as Laravel stores it. */
function smSessionOf(string $guard, object $account): array
{
    return ['password_hash_'.$guard => Auth::guard($guard)->hashPasswordForCookie($account->getAuthPassword())];
}

function smOperator(): User
{
    return operatorUser(seedScenario(1), ['password' => SM_OLD]);
}

function smGuest(): Guest
{
    seedScenario(1);   // sets the tenant context a guest is created in
    $g = Guest::create(['email' => 'sm-'.Str::lower(Str::random(10)).'@example.test', 'first_name' => 'Sam', 'last_name' => 'Session', 'language' => 'en']);
    $g->password = SM_OLD;
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

// ══ 1 · A CHANGES THE PASSWORD → B IS SIGNED OUT, A STAYS ══════════════════════════════════════════════════════

it('⚠️⚠️ web: A changes the password → B\'s next request is signed out, and A stays signed in', function () {
    $user = smOperator();
    $b = smSessionOf('web', $user);

    // A — changes it from their own, signed-in session.
    $this->actingAs($user)->withSession(smSessionOf('web', $user))
        ->post('/app/security/password', ['current_password' => SM_OLD, 'password' => SM_NEW, 'password_confirmation' => SM_NEW])
        ->assertRedirect(route('app.security'));
    $this->get('/app/profile')->assertOk();   // ⚠️ the ordinary case — A is still in

    // B — someone else's session of the same account, still carrying the OLD hash.
    $this->flushSession();
    Auth::forgetGuards();
    $this->actingAs($user->fresh())->withSession($b)->get('/app/profile')->assertRedirect('/login');
    $this->assertGuest('web');
})->group('security');

it('⚠️⚠️ guest: A changes the password → B is signed out, and A stays signed in', function () {
    $guest = smGuest();
    $b = smSessionOf('guest', $guest);

    $this->actingAs($guest, 'guest')->withSession(smSessionOf('guest', $guest))
        ->post(route('guest.account.password'), ['current_password' => SM_OLD, 'password' => SM_NEW, 'password_confirmation' => SM_NEW])
        ->assertRedirect(route('guest.account'));
    $this->get(route('guest.account'))->assertOk();

    $this->flushSession();
    Auth::forgetGuards();
    $this->actingAs($guest->fresh(), 'guest')->withSession($b)->get(route('guest.account'))->assertRedirect();
    $this->assertGuest('guest');
})->group('security');

it('⚠️⚠️ admin: A changes the password → B is signed out, and A stays signed in', function () {
    $admin = platformAdmin(['password' => 'Admin-Old-Pass-2026']);
    $b = smSessionOf('admin', $admin);

    $this->actingAs($admin, 'admin')->withSession(smSessionOf('admin', $admin))
        ->put(route('admin.account.password'), ['current_password' => 'Admin-Old-Pass-2026', 'password' => 'Admin-New-Pass-2026', 'password_confirmation' => 'Admin-New-Pass-2026'])
        ->assertRedirect(route('admin.account.edit'));
    $this->get(route('admin.dashboard'))->assertOk();

    $this->flushSession();
    Auth::forgetGuards();
    $this->actingAs($admin->fresh(), 'admin')->withSession($b)->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
})->group('security');

// ══ 2 · A RESETS THE PASSWORD → B IS SIGNED OUT ════════════════════════════════════════════════════════════════

it('⚠️⚠️ web: a password RESET signs B out — the one thing someone who thinks they are hacked does', function () {
    $user = smOperator();
    $b = smSessionOf('web', $user);
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => SM_NEW, 'password_confirmation' => SM_NEW])
        ->assertSessionHasNoErrors();

    $this->flushSession();
    Auth::forgetGuards();
    $this->actingAs($user->fresh())->withSession($b)->get('/app/profile')->assertRedirect('/login');
    $this->assertGuest('web');
})->group('security');

it('⚠️⚠️ guest: a password RESET signs B out', function () {
    $guest = smGuest();
    $b = smSessionOf('guest', $guest);
    $token = Password::broker('guests')->createToken($guest);

    $this->post(route('guest.password.update'), ['token' => $token, 'email' => $guest->email, 'password' => SM_NEW, 'password_confirmation' => SM_NEW])
        ->assertSessionHasNoErrors();

    $this->flushSession();
    Auth::forgetGuards();
    $this->actingAs($guest->fresh(), 'guest')->withSession($b)->get(route('guest.account'))->assertRedirect();
    $this->assertGuest('guest');
})->group('security');

// ══ 3 · A PRESSES "SIGN OUT OTHER SESSIONS" → B IS SIGNED OUT, A STAYS ═════════════════════════════════════════

it('⚠️⚠️ operator: "sign out other sessions" signs B out and keeps A — whatever the session driver', function () {
    $user = smOperator();
    $b = smSessionOf('web', $user);

    $this->actingAs($user)->withSession(smSessionOf('web', $user))
        ->post(route('app.profile.sign-out-others'), ['current_password' => SM_OLD])->assertRedirect(route('app.profile'));
    $this->get('/app/profile')->assertOk();   // A stays

    $this->flushSession();
    Auth::forgetGuards();
    $this->actingAs($user->fresh())->withSession($b)->get('/app/profile')->assertRedirect('/login');
    $this->assertGuest('web');

    // …and the account's password is still the one the user knows — it was re-hashed, not changed.
    expect(Hash::check(SM_OLD, (string) $user->fresh()->password))->toBeTrue();
})->group('security');

it('⚠️⚠️ owner: "sign out all other sessions" signs B out and keeps A', function () {
    $user = ownerRoleUser(seedScenario(1));
    $user->forceFill(['password' => SM_OLD])->save();
    $b = smSessionOf('web', $user);

    $this->actingAs($user)->withSession(smSessionOf('web', $user))
        ->post(route('owner.security.sessions.signout'), ['current_password' => SM_OLD])->assertRedirect();
    $this->get('/owner/security')->assertOk();

    $this->flushSession();
    Auth::forgetGuards();
    $this->actingAs($user->fresh())->withSession($b)->get('/owner/security')->assertRedirect('/login');
})->group('security');

it('⚠️ operator: "sign out other sessions" also deletes the other session ROWS, and never the current one', function () {
    $user = smOperator();
    foreach (['sm-other-a', 'sm-other-b'] as $id) {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'ip_address' => '203.0.113.9', 'user_agent' => 'Chrome', 'payload' => 'x', 'last_activity' => time()]);
    }

    $this->actingAs($user)->post(route('app.profile.sign-out-others'), ['current_password' => SM_OLD])->assertRedirect();
    $current = session()->getId();

    expect(DB::table('sessions')->whereIn('id', ['sm-other-a', 'sm-other-b'])->count())->toBe(0)
        ->and($current)->not->toBeIn(['sm-other-a', 'sm-other-b']);
})->group('security');

// ══ 4 · REMEMBER ME — the cookie no longer signs B back in ═════════════════════════════════════════════════════

it('⚠️⚠️ guest: after A changes the password, B\'s "remember me" cookie no longer signs it back in', function () {
    $guest = smGuest();
    $guest->forceFill(['remember_token' => Str::random(60)])->save();
    $guard = Auth::guard('guest');
    $oldToken = (string) $guest->remember_token;
    $cookie = [$guard->getRecallerName(), $guest->getAuthIdentifier().'|'.$oldToken.'|'.$guest->getAuthPassword()];

    // CONTROL — the cookie alone really does sign in before anything changes (otherwise the test proves nothing).
    $this->withCookie(...$cookie)->get(route('guest.account'))->assertOk();
    $this->assertAuthenticatedAs($guest, 'guest');

    $this->flushSession();
    Auth::forgetGuards();
    $this->actingAs($guest, 'guest')->withSession(smSessionOf('guest', $guest))
        ->post(route('guest.account.password'), ['current_password' => SM_OLD, 'password' => SM_NEW, 'password_confirmation' => SM_NEW])
        ->assertRedirect(route('guest.account'));

    // The token was cycled…
    expect((string) $guest->fresh()->remember_token)->not->toBe($oldToken);

    // …so B, holding only the old cookie, is not signed back in.
    $this->flushSession();
    Auth::forgetGuards();
    $this->withCookie(...$cookie)->get(route('guest.account'))->assertRedirect();
    $this->assertGuest('guest');
})->group('security');

it('web and admin issue no "remember me" cookie at all — there is nothing for B to keep', function () {
    $user = smOperator();
    $res = $this->post('/login', ['email' => $user->email, 'password' => SM_OLD, 'remember' => '1']);

    $names = array_map(fn ($c) => $c->getName(), $res->headers->getCookies());
    expect(array_filter($names, fn (string $n) => str_starts_with($n, 'remember_')))->toBe([]);
})->group('security');

// ══ 5 · ADMIN FORCE SIGN-OUT CAN NEVER END THE CALLER'S OWN SESSION ════════════════════════════════════════════

it('⚠️⚠️ force sign-out on YOURSELF ends your other sessions and never this one', function () {
    /** @var PlatformAdmin $admin */
    $admin = platformAdmin();

    $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))->assertOk();   // this session is now tracked
    $mine = hash('sha256', (string) session(PlatformAdminSessions::SESSION_KEY));
    $other = PlatformAdminSession::query()->create([
        'platform_admin_id' => $admin->id, 'session_key_hash' => hash('sha256', Str::random(64)),
        'ip' => '203.0.113.7', 'user_agent' => 'a lost laptop', 'started_at' => now(), 'last_seen_at' => now(),
    ]);

    $this->post(route('admin.admins.sign-out', $admin->id), ['reason' => 'SESSIONS 01 — my laptop was stolen'])
        ->assertRedirect(route('admin.admins.index'));

    expect($other->fresh()->ended_at)->not->toBeNull()
        ->and(PlatformAdminSession::query()->where('session_key_hash', $mine)->value('ended_at'))->toBeNull();
    $this->get(route('admin.dashboard'))->assertOk();   // still in
})->group('security');

it('⚠️ guest: a password change also deletes the other session ROWS of that guest, never the current one', function () {
    $guest = smGuest();
    foreach (['sm-guest-a', 'sm-guest-b'] as $id) {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $guest->id, 'ip_address' => '203.0.113.9', 'user_agent' => 'Safari', 'payload' => 'x', 'last_activity' => time()]);
    }

    $this->actingAs($guest, 'guest')
        ->post(route('guest.account.password'), ['current_password' => SM_OLD, 'password' => SM_NEW, 'password_confirmation' => SM_NEW])
        ->assertRedirect(route('guest.account'));

    expect(DB::table('sessions')->whereIn('id', ['sm-guest-a', 'sm-guest-b'])->count())->toBe(0);
})->group('security');
