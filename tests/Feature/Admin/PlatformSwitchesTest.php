<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\PlatformAdminAction;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\UserIdentity;
use App\Services\Auth\TwitterIdentityProvider;
use App\Support\Auth\SocialIdentity;
use App\Support\Platform\PlatformSwitches;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\FakeSocialIdentityProvider;

// ADMIN 01 · PHASE 1 — THE KILL SWITCHES. Database-backed (never .env — L14), flipped without a deploy, ON by
// default, never able to touch /admin, and — for sign-in — never flipped without seeing who it locks out.

uses(DatabaseTransactions::class);

beforeEach(function () {
    // Every test starts from "no rows" — today's behaviour — and a clean cache.
    PlatformSetting::query()->delete();
    Cache::forget('platform:switches:v1');
});

function switchesFakeX(): FakeSocialIdentityProvider
{
    config([
        'services.twitter.client_id' => 'test-x-client-id',
        'services.twitter.client_secret' => 'test-x-client-secret',
        'services.twitter.redirect' => 'http://localhost/auth/twitter/callback',
    ]);
    $fake = new FakeSocialIdentityProvider(true, 'twitter');
    app()->instance(TwitterIdentityProvider::class, $fake);

    return $fake;
}

function switchesXCallback(object $test, SocialIdentity $identity, FakeSocialIdentityProvider $fake): TestResponse
{
    $location = (string) $test->get(route('social.twitter.redirect'))->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
    $fake->next = $identity;

    return $test->get(route('social.twitter.callback', ['code' => 'auth-code', 'state' => (string) ($query['state'] ?? '')]));
}

function switchesSignupPayload(string $slugSeed): array
{
    return [
        'org_name' => 'Switch Probe '.$slugSeed, 'location' => 'Lisbon', 'property_type' => 'hotel', 'currency' => 'EUR',
        'property_name' => 'Probe House '.$slugSeed, 'name' => 'Probe Person',
        'email' => 'switch-probe-'.$slugSeed.'@example.net', 'password' => 'Str0ng!Passw0rd', 'password_confirmation' => 'Str0ng!Passw0rd',
    ];
}

// ══ 1 · ⚠️⚠️ DEFAULT ON — a missing row, and even an UNREADABLE table, mean today's behaviour ══════════════════

it('⚠️⚠️ with NO rows every switch is ON — an empty table after a deploy changes nothing', function () {
    foreach (array_keys(PlatformSwitches::catalogue()) as $key) {
        expect(PlatformSwitches::enabled($key))->toBeTrue("{$key} was not on by default");
    }

    $this->get(route('signup'))->assertOk()->assertSee('id="signup"', false)->assertDontSee('New sign-ups are paused');
})->group('security');

it('⚠️⚠️ an UNREADABLE switch table is treated as ON — a failed read is never a closed front door', function () {
    Cache::shouldReceive('remember')->andThrow(new RuntimeException('simulated read failure'));

    expect(PlatformSwitches::enabled(PlatformSwitches::SIGNUP))->toBeTrue()
        ->and(PlatformSwitches::signupAllowed('twitter'))->toBeTrue();
})->group('security');

it('an unknown switch name is an error, never a silent "on"', function () {
    expect(fn () => PlatformSwitches::enabled('signup.typo'))->toThrow(InvalidArgumentException::class);
})->group('security');

// ══ 2 · SIGN-UP OFF — said plainly, refused at the write, no deploy needed ═══════════════════════════════════

it('⚠️⚠️ with sign-ups OFF the page SAYS so (200, no form) and a posted signup creates NOTHING', function () {
    PlatformSwitches::set(PlatformSwitches::SIGNUP, false, null);
    $seed = Str::lower(Str::random(8));

    $this->get(route('signup'))->assertOk()->assertSee('New sign-ups are paused')->assertDontSee('id="signup"', false);

    $this->post('/signup', switchesSignupPayload($seed))->assertRedirect(route('signup'));

    expect(Organization::query()->where('name', 'Switch Probe '.$seed)->exists())->toBeFalse()
        ->and(User::query()->where('email', 'switch-probe-'.$seed.'@example.net')->exists())->toBeFalse();
})->group('security');

it('⚠️ a flip takes effect on the very next request — no deploy, no config cache', function () {
    PlatformSwitches::set(PlatformSwitches::SIGNUP, false, null);
    $this->get(route('signup'))->assertSee('New sign-ups are paused');

    PlatformSwitches::set(PlatformSwitches::SIGNUP, true, null);
    $this->get(route('signup'))->assertSee('id="signup"', false)->assertDontSee('New sign-ups are paused');
})->group('security');

it('with sign-ups ON the same post DOES create the organisation (the control)', function () {
    $seed = Str::lower(Str::random(8));
    $this->post('/signup', switchesSignupPayload($seed));

    expect(Organization::query()->where('name', 'Switch Probe '.$seed)->exists())->toBeTrue();
})->group('security');

// ══ 3 · THE SOCIAL SWITCHES — at the point the outcome is known ═════════════════════════════════════════════

it('⚠️⚠️ sign-up with X OFF: a new person continuing with X is refused, and no signup is started', function () {
    $fake = switchesFakeX();
    $newPerson = fn () => new SocialIdentity('twitter', 'x-new-'.Str::random(10), 'x-new-'.Str::lower(Str::random(6)).'@example.net', false, 'New Person');

    // ⚠️ THE CONTROL — switch ON, the very same flow starts a signup. Without it, a refusal below could be
    //    caused by anything and still be credited to the switch.
    switchesXCallback($this, $newPerson(), $fake)->assertRedirect(route('signup'));
    expect(session('social.signup'))->not->toBeNull();
    $this->flushSession();

    PlatformSwitches::set(PlatformSwitches::SIGNUP_TWITTER, false, null);

    switchesXCallback($this, $newPerson(), $fake)->assertRedirect(route('login'));

    expect(session('social.signup'))->toBeNull()
        ->and(Auth::guard('web')->check())->toBeFalse();
})->group('security');

it('⚠️⚠️ sign-in with X OFF: a LINKED user is refused sign-in — and the lock-out count names exactly who that strands', function () {
    $fake = switchesFakeX();
    $s = seedScenario(1);
    $baseline = PlatformSwitches::lockoutCount('twitter');   // ⚠️ measured BEFORE the fixtures exist
    $withPassword = operatorUser($s, ['email' => 'x-pw-'.Str::lower(Str::random(6)).'@example.net']);
    $passwordless = operatorUser($s, ['email' => 'x-nopw-'.Str::lower(Str::random(6)).'@example.net']);
    $passwordless->forceFill(['password' => null])->save();
    $twoDoors = operatorUser($s, ['email' => 'x-two-'.Str::lower(Str::random(6)).'@example.net']);
    $twoDoors->forceFill(['password' => null])->save();

    $pid = 'x-linked-'.Str::random(10);
    foreach ([[$withPassword, $pid], [$passwordless, 'x-np-'.Str::random(10)], [$twoDoors, 'x-2d-'.Str::random(10)]] as [$u, $id]) {
        UserIdentity::query()->create(['user_id' => $u->id, 'provider' => 'twitter', 'provider_user_id' => $id, 'provider_email' => $u->email]);
    }
    UserIdentity::query()->create(['user_id' => $twoDoors->id, 'provider' => 'google', 'provider_user_id' => 'g-'.Str::random(10), 'provider_email' => $twoDoors->email]);

    // Of the three, ONLY the passwordless, X-only account is stranded: one has a password, one has Google too.
    expect(PlatformSwitches::lockoutCount('twitter') - $baseline)->toBe(1);

    // ⚠️ THE CONTROL — switch ON, this linked user DOES sign in with X.
    switchesXCallback($this, new SocialIdentity('twitter', $pid, $withPassword->email, false, 'Linked'), $fake);
    expect(Auth::guard('web')->id())->toBe((string) $withPassword->getKey(), 'the control failed: X sign-in did not work even with the switch on');
    Auth::guard('web')->logout();
    $this->flushSession();

    PlatformSwitches::set(PlatformSwitches::SIGNIN_TWITTER, false, null);
    switchesXCallback($this, new SocialIdentity('twitter', $pid, $withPassword->email, false, 'Linked'), $fake);

    expect(Auth::guard('web')->check())->toBeFalse('sign-in with X was paused, yet it signed someone in');
})->group('security');

it('a signup stashed BEFORE the switch went off cannot be completed after it', function () {
    PlatformSwitches::set(PlatformSwitches::SIGNUP_TWITTER, false, null);
    $seed = Str::lower(Str::random(8));
    $payload = switchesSignupPayload($seed);
    unset($payload['password'], $payload['password_confirmation']);

    $this->withSession(['social.signup' => [
        'provider' => 'twitter', 'provider_user_id' => 'x-stash-'.$seed, 'email' => $payload['email'], 'name' => 'Stashed', 'issued_at' => now()->toIso8601String(),
    ]])->post('/signup', $payload)->assertRedirect(route('signup'));

    expect(Organization::query()->where('name', 'Switch Probe '.$seed)->exists())->toBeFalse();
})->group('security');

it('the X button on /login disappears only when BOTH X sign-in and X sign-up are off', function () {
    switchesFakeX();
    $this->get(route('login'))->assertSee('Continue with X');

    PlatformSwitches::set(PlatformSwitches::SIGNIN_TWITTER, false, null);
    $this->get(route('login'))->assertSee('Continue with X');   // sign-up still open → button stays

    PlatformSwitches::set(PlatformSwitches::SIGNUP_TWITTER, false, null);
    $this->get(route('login'))->assertDontSee('Continue with X');
})->group('security');

// ══ 4 · ⚠️⚠️ NO SWITCH TOUCHES /admin ═══════════════════════════════════════════════════════════════════════

it('⚠️⚠️ with EVERY switch OFF the console still opens — a switch can never lock the admin out of the place that unflips it', function () {
    foreach (array_keys(PlatformSwitches::catalogue()) as $key) {
        PlatformSwitches::set($key, false, null);
    }

    $this->get(route('admin.login'))->assertOk();
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.switches.index'))->assertOk()->assertSee('Kill switches');
})->group('security');

// ══ 5 · FLIPPING — reason, second confirmation for sign-in, audited ═════════════════════════════════════════

it('⚠️⚠️ turning a SIGN-IN switch off without the second confirmation is refused — the switch stays on', function () {
    $owner = platformAdmin();

    $this->actingAs($owner, 'admin')
        ->post(route('admin.switches.update', PlatformSwitches::SIGNIN_TWITTER), ['on' => '0', 'reason' => 'Testing the confirmation gate.'])
        ->assertRedirect();

    expect(PlatformSwitches::enabled(PlatformSwitches::SIGNIN_TWITTER))->toBeTrue();
})->group('security');

it('with the confirmation it goes off, and the audit row carries the reason and the lock-out count', function () {
    $owner = platformAdmin();

    $this->actingAs($owner, 'admin')
        ->post(route('admin.switches.update', PlatformSwitches::SIGNIN_TWITTER), ['on' => '0', 'confirm_lockout' => '1', 'reason' => 'X is having an outage.'])
        ->assertRedirect(route('admin.switches.index'));

    expect(PlatformSwitches::enabled(PlatformSwitches::SIGNIN_TWITTER))->toBeFalse();

    $row = PlatformAdminAction::query()->where('action', 'platform.switch.off')->where('target_id', PlatformSwitches::SIGNIN_TWITTER)->latest('created_at')->first();
    expect($row?->reason)->toBe('X is having an outage.')
        ->and($row?->platform_admin_id)->toBe($owner->id)
        ->and($row?->meta)->toHaveKey('lockout_count');
})->group('security');

it('a sign-UP switch needs no lock-out confirmation (it strands nobody)', function () {
    $this->actingAs(platformAdmin(), 'admin')
        ->post(route('admin.switches.update', PlatformSwitches::SIGNUP), ['on' => '0', 'reason' => 'Pausing sign-ups for maintenance.'])
        ->assertRedirect(route('admin.switches.index'));

    expect(PlatformSwitches::enabled(PlatformSwitches::SIGNUP))->toBeFalse();
})->group('security');
