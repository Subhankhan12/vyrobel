<?php

declare(strict_types=1);

use App\Contracts\Auth\SocialIdentityProvider;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserIdentity;
use App\Services\Auth\GoogleIdentityProvider;
use App\Support\Auth\SignInCredentials;
use App\Support\Auth\SocialIdentity;
use App\Support\Auth\SocialLinking;
use App\Support\Auth\SocialLinkOutcome;
use App\Support\Authorization\RolePermissions;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\Support\FakeSocialIdentityProvider;

/**
 * ⚠️⚠️ SIGN IN AND SIGN UP WITH GOOGLE — AND THE SECURITY CORE IS THE LINKING RULE, NOT THE BUTTON.
 *
 * A returning user is matched on `(provider, provider_user_id)` and NEVER on their email address. If an
 * identity were matched by address alone, anyone who controls a Google account at that address would own
 * the Vyrobel account — including an address the user once held at a domain they no longer control.
 *
 * ⚠️ WHAT THESE TESTS PROVE AND WHAT THEY CANNOT. The provider is faked in-process, so everything here is
 *    the APPLICATION'S half: the redirect's parameters, that `state` is validated, what the callback
 *    decides, and what it writes. They do NOT prove that Google accepts the client, that the consent screen
 *    is right, or that a real account completes the flow — a real OAuth round trip cannot be automated, and
 *    those three sit in the go-live checklist instead. A faked provider must never be reported as a round
 *    trip.
 */
uses(DatabaseTransactions::class);

// ══ fixtures ═════════════════════════════════════════════════════════════════════════════════════════

function fakeGoogle(bool $enabled = true): FakeSocialIdentityProvider
{
    config([
        'services.google.client_id' => 'test-client-id.apps.googleusercontent.test',
        'services.google.client_secret' => 'test-client-secret',
        'services.google.redirect' => 'http://localhost/auth/google/callback',
    ]);

    $fake = new FakeSocialIdentityProvider($enabled);
    app()->instance(SocialIdentityProvider::class, $fake);

    return $fake;
}

function googleIdentity(string $sub, string $email, bool $verified = true, string $name = 'Ada Okafor'): SocialIdentity
{
    return new SocialIdentity('google', $sub, $email, $verified, $name);
}

/** A unique-per-run address, because the test database persists between runs (RefreshDatabase is disabled). */
function uniqueEmail(string $prefix): string
{
    return $prefix.'.'.Str::lower(Str::random(12)).'@example.test';
}

/** Begin a real flow so the session carries a real `state`, and hand back that state. */
function beginGoogleFlow(object $test, string $route = 'social.google.redirect'): string
{
    $response = $route === 'social.google.redirect'
        ? $test->get(route($route))
        : $test->post(route($route));

    $location = (string) $response->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    return (string) ($query['state'] ?? '');
}

// ══ #1 · ⚠️⚠️ THE LINKING RULE — EVERY CASE ══════════════════════════════════════════════════════════

it('⚠️⚠️ REFUSES to link when only the EMAIL matches an existing account — the takeover case', function () {
    $fake = fakeGoogle();
    $scenario = seedScenario(1);
    $email = uniqueEmail('victim');
    $victim = operatorUser($scenario, ['email' => $email]);

    // Someone who controls a Google account at the victim's address. Verified, even — and it changes nothing.
    $fake->next = googleIdentity('google-sub-attacker-'.Str::random(8), $email, verified: true);

    $state = beginGoogleFlow($this);
    $response = $this->get(route('social.google.callback', ['code' => 'auth-code', 'state' => $state]));

    // ⚠️ NOBODY IS SIGNED IN, and no link was created behind the scenes.
    expect(Auth::guard('web')->check())->toBeFalse()
        ->and(UserIdentity::query()->where('user_id', $victim->getKey())->exists())->toBeFalse();

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['email']);
    // ⚠️ The bag arrives as a plain array here, not a ViewErrorBag — normalised rather than assumed.
    expect(session('errors')->getBag('default')->first('email'))->toContain('Sign in with your password');
});

it('⚠️ the same refusal at the rule itself — email is never a way to resolve a user', function () {
    $scenario = seedScenario(1);
    $email = uniqueEmail('victim2');
    operatorUser($scenario, ['email' => $email]);

    $result = SocialLinking::forSignIn(googleIdentity('sub-'.Str::random(10), $email, verified: true));

    expect($result['outcome'])->toBe(SocialLinkOutcome::RefuseEmailTaken)
        ->and($result['user'])->toBeNull();   // ⚠️ never a user — the refusal cannot leak one
});

it('signs in an ALREADY LINKED identity — the only silent path', function () {
    $fake = fakeGoogle();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('linked')]);
    $sub = 'google-sub-'.Str::random(10);

    UserIdentity::create([
        'user_id' => $user->getKey(), 'provider' => 'google',
        'provider_user_id' => $sub, 'provider_email' => $user->email,
    ]);

    $fake->next = googleIdentity($sub, (string) $user->email);
    $state = beginGoogleFlow($this);
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));

    expect(Auth::guard('web')->id())->toBe((string) $user->getKey());
});

it('⚠️ matches on the provider id even after the provider EMAIL changes', function () {
    $fake = fakeGoogle();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('moved')]);
    $sub = 'google-sub-'.Str::random(10);

    UserIdentity::create([
        'user_id' => $user->getKey(), 'provider' => 'google',
        'provider_user_id' => $sub, 'provider_email' => 'old.address@example.test',
    ]);

    // The person changed their Google address. The link is on the identifier, so this is a non-event.
    $fake->next = googleIdentity($sub, uniqueEmail('brand.new'));
    $state = beginGoogleFlow($this);
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));

    expect(Auth::guard('web')->id())->toBe((string) $user->getKey())
        ->and(UserIdentity::query()->where('provider_user_id', $sub)->value('provider_email'))
        ->toBe($fake->next->email);   // updated as a display value only
});

it('⚠️⚠️ REFUSES an unverified address — both to link AND to create', function () {
    $fake = fakeGoogle();
    $email = uniqueEmail('unverified');
    $fake->next = googleIdentity('sub-'.Str::random(10), $email, verified: false);

    $before = User::query()->count();
    $state = beginGoogleFlow($this);
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));

    // Refused to CREATE: no account, and no signup stash to complete one with.
    expect(User::query()->count())->toBe($before)
        ->and(session()->has('social.signup'))->toBeFalse()
        ->and(Auth::guard('web')->check())->toBeFalse();

    // Refused to LINK: the authenticated path says no as well.
    $scenario = seedScenario(1);
    $actor = operatorUser($scenario, ['email' => uniqueEmail('actor')]);
    expect(SocialLinking::forLink($actor, $fake->next))->toBe(SocialLinkOutcome::RefuseUnverified);
});

it('⚠️ allows TWO providers for ONE person — two rows, one user', function () {
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('multi')]);

    UserIdentity::create(['user_id' => $user->getKey(), 'provider' => 'google', 'provider_user_id' => 'g-'.Str::random(8)]);
    UserIdentity::create(['user_id' => $user->getKey(), 'provider' => 'testprov', 'provider_user_id' => 't-'.Str::random(8)]);

    expect($user->identities()->count())->toBe(2);

    // ⚠️ And a second identity from the SAME provider is refused by the database, not by a validator.
    expect(fn () => UserIdentity::create([
        'user_id' => $user->getKey(), 'provider' => 'google', 'provider_user_id' => 'g-other-'.Str::random(8),
    ]))->toThrow(QueryException::class);
});

it('⚠️⚠️ enforces one identity per provider with a DATABASE constraint, not a validator', function () {
    $scenario = seedScenario(1);
    $a = operatorUser($scenario, ['email' => uniqueEmail('a')]);
    $b = operatorUser($scenario, ['email' => uniqueEmail('b')]);
    $sub = 'shared-sub-'.Str::random(10);

    UserIdentity::create(['user_id' => $a->getKey(), 'provider' => 'google', 'provider_user_id' => $sub]);

    // ⚠️ A RAW INSERT — deliberately going around every model, observer and form request, because that is
    //    exactly what a seeder, a console command or a queued job would do. A validator would not be here.
    expect(fn () => DB::table('user_identities')->insert([
        'id' => (string) Str::ulid(), 'user_id' => $b->getKey(), 'provider' => 'google',
        'provider_user_id' => $sub, 'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('⚠️ REFUSES to link an identity that already belongs to someone else', function () {
    $scenario = seedScenario(1);
    $owner = operatorUser($scenario, ['email' => uniqueEmail('owner')]);
    $other = operatorUser($scenario, ['email' => uniqueEmail('other')]);
    $sub = 'sub-'.Str::random(10);

    UserIdentity::create(['user_id' => $owner->getKey(), 'provider' => 'google', 'provider_user_id' => $sub]);

    expect(SocialLinking::forLink($other, googleIdentity($sub, uniqueEmail('x'))))
        ->toBe(SocialLinkOutcome::RefuseIdentityTaken);
});

it('⚠️ a link pointing at a SUSPENDED account is not a way around the suspension', function () {
    $fake = fakeGoogle();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('suspended')]);
    $sub = 'sub-'.Str::random(10);
    UserIdentity::create(['user_id' => $user->getKey(), 'provider' => 'google', 'provider_user_id' => $sub]);
    $user->forceFill(['status' => 'disabled'])->save();

    $fake->next = googleIdentity($sub, (string) $user->email);
    $state = beginGoogleFlow($this);
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));

    expect(Auth::guard('web')->check())->toBeFalse();
});

// ══ #5 · ⚠️⚠️ STATE — THE CSRF DEFENCE OF THE WHOLE FLOW ═════════════════════════════════════════════

it('⚠️ the redirect carries the client id, the scopes, the redirect uri and a state', function () {
    config([
        'services.google.client_id' => 'real-client-id',
        'services.google.client_secret' => 'real-secret',
        'services.google.redirect' => 'https://vyrobel.test/auth/google/callback',
    ]);
    // ⚠️ The REAL provider here, not the fake — the URL it builds is part of what ships.
    $url = app(SocialIdentityProvider::class)->authorizationUrl('the-state-value');
    parse_str((string) parse_url($url, PHP_URL_QUERY), $q);

    expect($q['client_id'])->toBe('real-client-id')
        ->and($q['redirect_uri'])->toBe('https://vyrobel.test/auth/google/callback')
        ->and($q['response_type'])->toBe('code')
        ->and($q['state'])->toBe('the-state-value')
        // ⚠️ Exactly these three. More would trigger Google's verification review and ask the user to grant
        //    access this product has no use for.
        ->and($q['scope'])->toBe('openid email profile');
});

it('⚠️⚠️ REFUSES a callback whose state does not match', function () {
    $fake = fakeGoogle();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('state')]);
    $sub = 'sub-'.Str::random(10);
    UserIdentity::create(['user_id' => $user->getKey(), 'provider' => 'google', 'provider_user_id' => $sub]);
    $fake->next = googleIdentity($sub, (string) $user->email);

    beginGoogleFlow($this);   // a real state is issued and stored…
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => 'not-the-state-we-issued']));

    expect(Auth::guard('web')->check())->toBeFalse();
});

it('⚠️⚠️ REFUSES a callback with NO state at all', function () {
    $fake = fakeGoogle();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('nostate')]);
    $sub = 'sub-'.Str::random(10);
    UserIdentity::create(['user_id' => $user->getKey(), 'provider' => 'google', 'provider_user_id' => $sub]);
    $fake->next = googleIdentity($sub, (string) $user->email);

    beginGoogleFlow($this);
    $this->get(route('social.google.callback', ['code' => 'c']));

    expect(Auth::guard('web')->check())->toBeFalse();
});

it('⚠️⚠️ REFUSES a REPLAYED state — it is single-use', function () {
    $fake = fakeGoogle();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('replay')]);
    $sub = 'sub-'.Str::random(10);
    UserIdentity::create(['user_id' => $user->getKey(), 'provider' => 'google', 'provider_user_id' => $sub]);
    $fake->next = googleIdentity($sub, (string) $user->email);

    $state = beginGoogleFlow($this);
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));
    expect(Auth::guard('web')->id())->toBe((string) $user->getKey());   // the first use works…

    Auth::guard('web')->logout();
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));

    // ⚠️ …and the SAME state a second time is refused, because it was consumed on first use.
    expect(Auth::guard('web')->check())->toBeFalse();
});

// ══ #3 · ⚠️⚠️ SIGNUP IS AUTHENTICATE-THEN-FORM, AND ABANDONING IT LEAVES NOTHING ═════════════════════

it('⚠️⚠️ an ABANDONED social signup leaves NOTHING behind — no user, no org, no trial', function () {
    $fake = fakeGoogle();
    $email = uniqueEmail('abandoner');
    $fake->next = googleIdentity('sub-'.Str::random(10), $email);

    $before = [
        'users' => User::query()->count(),
        'orgs' => Organization::query()->withoutGlobalScopes()->count(),
        'memberships' => DB::table('organization_user')->count(),
        'subscriptions' => DB::table('subscriptions')->count(),
        'properties' => DB::table('properties')->count(),
        'identities' => DB::table('user_identities')->count(),
    ];

    $state = beginGoogleFlow($this);
    $response = $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));

    // They are sent to the org form — and then they close the tab. Nothing further happens.
    $response->assertRedirect(route('signup'));

    $after = [
        'users' => User::query()->count(),
        'orgs' => Organization::query()->withoutGlobalScopes()->count(),
        'memberships' => DB::table('organization_user')->count(),
        'subscriptions' => DB::table('subscriptions')->count(),
        'properties' => DB::table('properties')->count(),
        'identities' => DB::table('user_identities')->count(),
    ];

    // ⚠️⚠️ A half-provisioned organisation is a MONEY OBJECT WITH NO OWNER. The half-finished state lives in
    //    the session and nowhere else, so there is nothing to clean up.
    expect($after)->toBe($before)
        ->and(Auth::guard('web')->check())->toBeFalse();
});

it('⚠️ the signup form is PRE-FILLED and the password field is GONE', function () {
    $fake = fakeGoogle();
    $email = uniqueEmail('newfounder');
    $fake->next = googleIdentity('sub-'.Str::random(10), $email, name: 'Ada Okafor');

    $state = beginGoogleFlow($this);
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));

    $page = $this->get(route('signup'));
    $html = $page->getContent();

    expect($html)->toContain($email)->toContain('Ada Okafor');
    // ⚠️ Asked for at signup is exactly what the founder ruled against: a password is requested later, once,
    //    when a sensitive action first needs confirming.
    $this->assertStringNotContainsString('name="password"', $html);
    $this->assertStringNotContainsString('name="password_confirmation"', $html);
});

it('⚠️ completes the social signup: one org, a founder with NO password, and the link — all provisioned', function () {
    $fake = fakeGoogle();
    $email = uniqueEmail('provisioned');
    $sub = 'sub-'.Str::random(10);
    $fake->next = googleIdentity($sub, $email, name: 'Ada Okafor');

    $state = beginGoogleFlow($this);
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));

    $this->post(route('signup'), [
        'org_name' => 'Okafor Stays '.Str::random(6),
        'property_type' => 'villa',
        'currency' => 'EUR',
        'property_name' => 'Casa Azul',
        'name' => 'Ada Okafor',
        'email' => $email,
    ])->assertRedirect('/app/portfolio');

    $user = User::query()->where('email', $email)->firstOrFail();

    // ⚠️⚠️ WHICH HASH SHAPE A GOOGLE USER ACTUALLY GETS — measured, because the last pass found that the
    //    EMPTY-string case was the one its fixtures missed. It is NULL, and `hasPassword()` agrees.
    expect(DB::table('users')->where('id', $user->getKey())->value('password'))->toBeNull()
        ->and($user->hasPassword())->toBeFalse()
        ->and($user->identities()->where('provider_user_id', $sub)->exists())->toBeTrue()
        ->and(Auth::guard('web')->id())->toBe((string) $user->getKey());

    // The link was written inside the SAME transaction as the org — never an org nobody can sign in to.
    $orgId = DB::table('organization_user')->where('user_id', $user->getKey())->value('organization_id');
    expect($orgId)->not->toBeNull()
        ->and(DB::table('subscriptions')->where('organization_id', $orgId)->exists())->toBeTrue();
});

it('⚠️ the posted email is IGNORED — the provider assertion wins', function () {
    $fake = fakeGoogle();
    $real = uniqueEmail('real');
    $fake->next = googleIdentity('sub-'.Str::random(10), $real);

    $state = beginGoogleFlow($this);
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));

    // ⚠️ An edited field would otherwise provision an organisation under somebody else's address with the
    //    attacker's own Google account attached to it — a takeover assembled out of a pre-filled input.
    $this->post(route('signup'), [
        'org_name' => 'Edited Co '.Str::random(6), 'property_type' => 'villa', 'currency' => 'EUR',
        'property_name' => 'Casa', 'name' => 'Ada', 'email' => 'someone.else@example.test',
    ]);

    expect(User::query()->where('email', $real)->exists())->toBeTrue()
        ->and(User::query()->where('email', 'someone.else@example.test')->exists())->toBeFalse();
});

it('⚠️ /signup still works exactly as before WITH a password and no provider', function () {
    $email = uniqueEmail('classic');

    $this->post(route('signup'), [
        'org_name' => 'Classic Co '.Str::random(6), 'property_type' => 'hotel', 'currency' => 'EUR',
        'property_name' => 'The Classic', 'name' => 'Bo Chen', 'email' => $email,
        'password' => 'Str0ng-Passw0rd!x', 'password_confirmation' => 'Str0ng-Passw0rd!x',
    ])->assertRedirect('/app/portfolio');

    $user = User::query()->where('email', $email)->firstOrFail();
    expect($user->hasPassword())->toBeTrue()
        ->and(Auth::validate(['email' => $email, 'password' => 'Str0ng-Passw0rd!x', 'status' => User::SIGN_IN_STATUS]))->toBeTrue();
});

// ══ #4 · ⚠️⚠️ UNLINKING IS A LOCKOUT ═════════════════════════════════════════════════════════════════

it('⚠️⚠️ REFUSES to unlink the ONLY credential — and says why', function () {
    fakeGoogle();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('onlycred')]);
    User::withoutEvents(fn () => User::query()->whereKey($user->getKey())->update(['password' => null]));
    $user->refresh();

    $identity = UserIdentity::create([
        'user_id' => $user->getKey(), 'provider' => 'google', 'provider_user_id' => 'sub-'.Str::random(10),
    ]);

    expect(SignInCredentials::mayUnlink($user, (string) $identity->getKey()))->toBeFalse();

    $this->actingAs($user)->post(route('app.security.connections.unlink', $identity->getKey()));

    // ⚠️ The row SURVIVES. Anything else is a permanent lockout of an active account that still holds roles.
    expect(UserIdentity::query()->whereKey($identity->getKey())->exists())->toBeTrue()
        ->and(session('connections_error'))->toContain('Set a password first');
});

it('allows the unlink once another credential exists — a password, or a second provider', function () {
    fakeGoogle();
    $scenario = seedScenario(1);

    // (a) has a password
    $withPassword = operatorUser($scenario, ['email' => uniqueEmail('haspw')]);
    $idA = UserIdentity::create(['user_id' => $withPassword->getKey(), 'provider' => 'google', 'provider_user_id' => 'sub-'.Str::random(10)]);
    expect(SignInCredentials::mayUnlink($withPassword, (string) $idA->getKey()))->toBeTrue();
    $this->actingAs($withPassword)->post(route('app.security.connections.unlink', $idA->getKey()));
    expect(UserIdentity::query()->whereKey($idA->getKey())->exists())->toBeFalse();

    // (b) no password, but a second provider
    $twoLinks = operatorUser($scenario, ['email' => uniqueEmail('twolinks')]);
    User::withoutEvents(fn () => User::query()->whereKey($twoLinks->getKey())->update(['password' => null]));
    $twoLinks->refresh();
    $idB = UserIdentity::create(['user_id' => $twoLinks->getKey(), 'provider' => 'google', 'provider_user_id' => 'sub-'.Str::random(10)]);
    UserIdentity::create(['user_id' => $twoLinks->getKey(), 'provider' => 'testprov', 'provider_user_id' => 'sub-'.Str::random(10)]);

    expect(SignInCredentials::mayUnlink($twoLinks, (string) $idB->getKey()))->toBeTrue();
});

it('⚠️ unlink is SELF-ONLY — another user\'s link is not reachable', function () {
    fakeGoogle();
    $scenario = seedScenario(1);
    $mine = operatorUser($scenario, ['email' => uniqueEmail('mine')]);
    $theirs = operatorUser($scenario, ['email' => uniqueEmail('theirs')]);
    $identity = UserIdentity::create(['user_id' => $theirs->getKey(), 'provider' => 'google', 'provider_user_id' => 'sub-'.Str::random(10)]);

    $this->actingAs($mine)->post(route('app.security.connections.unlink', $identity->getKey()));

    expect(UserIdentity::query()->whereKey($identity->getKey())->exists())->toBeTrue();
});

// ══ #2 · ⚠️⚠️ THE SIGN-IN DEFINITION, SETTLED ════════════════════════════════════════════════════════

it('⚠️⚠️ a passwordless ACTIVE user is RECOVERABLE — which is why status alone is the right definition', function () {
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('recoverable')]);
    User::withoutEvents(fn () => User::query()->whereKey($user->getKey())->update(['password' => null]));
    $user->refresh();

    // They cannot sign in with a password — there is none, and it fails CLOSED.
    expect(Auth::validate(['email' => $user->email, 'password' => 'anything', 'status' => User::SIGN_IN_STATUS]))->toBeFalse();

    // ⚠️⚠️ BUT THE PASSWORD-RESET BROKER'S LOOKUP IS SCOPED BY STATUS, NOT BY PASSWORD. This is the fact the
    //    whole definition rests on: an ACTIVE account with no password and no provider link can still ask for
    //    a reset link and set one. It is therefore NOT locked out, and counting it as able to sign in is
    //    correct rather than merely convenient.
    $token = Password::broker()->createToken($user);
    $status = Password::reset(
        ['email' => $user->email, 'password' => 'Str0ng-Reset-Pass!1', 'password_confirmation' => 'Str0ng-Reset-Pass!1',
            'token' => $token, 'status' => User::SIGN_IN_STATUS],
        fn (User $u, string $p) => $u->changePasswordTo($p),
    );

    expect($status)->toBe(Password::PASSWORD_RESET)
        ->and(Auth::validate(['email' => $user->email, 'password' => 'Str0ng-Reset-Pass!1', 'status' => User::SIGN_IN_STATUS]))->toBeTrue()
        ->and(SignInCredentials::canSignIn($user->fresh()))->toBeTrue();
});

it('⚠️⚠️ the last-admin floor COUNTS a linked passwordless administrator — it reasons from status, not a password', function () {
    $scenario = seedScenario(1);
    setPermissionsTeamId($scenario['org']->id);

    $admin = operatorUser($scenario, ['email' => uniqueEmail('floor')], 'Property Owner');
    User::withoutEvents(fn () => User::query()->whereKey($admin->getKey())->update(['password' => null]));
    $admin->refresh();
    UserIdentity::create(['user_id' => $admin->getKey(), 'provider' => 'google', 'provider_user_id' => 'sub-'.Str::random(10)]);

    $survivors = RolePermissions::roleManagersAfter(RolePermissions::mapFor($scenario['org']->id));

    // ⚠️ COUNTING THEM AS UNABLE WOULD BE THE DANGEROUS DIRECTION: the floor would think the organisation had
    //    one fewer administrator than it does, and could allow the LAST real one to be removed.
    expect($survivors)->toContain((string) $admin->getKey())
        ->and(SignInCredentials::canSignIn($admin))->toBeTrue();
});

it('⚠️⚠️ unlinking cannot reduce an organisation\'s administrators — the two floors do not interact', function () {
    fakeGoogle();
    $scenario = seedScenario(1);
    setPermissionsTeamId($scenario['org']->id);

    $admin = operatorUser($scenario, ['email' => uniqueEmail('nointeract')], 'Property Owner');
    $identity = UserIdentity::create(['user_id' => $admin->getKey(), 'provider' => 'google', 'provider_user_id' => 'sub-'.Str::random(10)]);

    $before = RolePermissions::roleManagersAfter(RolePermissions::mapFor($scenario['org']->id));
    $this->actingAs($admin)->post(route('app.security.connections.unlink', $identity->getKey()));
    $after = RolePermissions::roleManagersAfter(RolePermissions::mapFor($scenario['org']->id));

    // ⚠️ An unlink writes to `user_identities` ONLY — never `users.status`, never roles, never memberships.
    //    So the two guards cannot combine into a state neither of them saw.
    expect(UserIdentity::query()->whereKey($identity->getKey())->exists())->toBeFalse()
        ->and($after)->toBe($before)
        ->and($admin->fresh()->status)->toBe(User::SIGN_IN_STATUS);
});

// ══ #6 · ⚠️⚠️ THE PASSWORDLESS GATE'S FIRST REAL USER ════════════════════════════════════════════════

it('⚠️⚠️ a Google user browses UNTOUCHED, is interrupted ONCE at a sensitive action, sets a password, and completes it', function () {
    fakeGoogle();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('googleuser')]);
    User::withoutEvents(fn () => User::query()->whereKey($user->getKey())->update(['password' => null]));
    $user->refresh();
    setPermissionsTeamId($scenario['org']->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');
    UserIdentity::create(['user_id' => $user->getKey(), 'provider' => 'google', 'provider_user_id' => 'sub-'.Str::random(10)]);

    expect($user->hasPassword())->toBeFalse();   // no password BY CONSTRUCTION

    // ⚠️ THE NEGATIVE HALF, AND IT IS THE WHOLE POINT OF LAZY. Ordinary work is never interrupted — a gate
    //    proven only in the deny direction may simply be denying everyone.
    $this->actingAs($user)->get('/app/dashboard')->assertOk();
    $this->actingAs($user)->get(route('app.security'))->assertOk();

    // A sensitive action: it asks for the password this account does not have, so it is INTERRUPTED.
    // ⚠️ The DISCRIMINATOR IS THE MIDDLEWARE'S OWN FLASH, not the redirect target. Both outcomes can land on
    //    /security — the failing rule redirects "back", which is wherever the user came from — so asserting
    //    on the URL would pass for the wrong reason and keep passing if the gate were removed.
    $interrupted = $this->actingAs($user)->post(route('app.profile.sign-out-others'), ['current_password' => 'x']);
    $interrupted->assertRedirect();
    expect(session('password_status'))->toContain('does not have one yet');

    // They set one, from the same card the interruption points at.
    $this->actingAs($user)->post(route('app.security.password'), [
        'password' => 'Str0ng-First-Pass!1', 'password_confirmation' => 'Str0ng-First-Pass!1',
    ]);

    expect($user->fresh()->hasPassword())->toBeTrue();

    // ⚠️ And the original action now behaves EXACTLY as it does for everyone else: the real rule bites.
    $wrong = $this->actingAs($user->fresh())->post(route('app.profile.sign-out-others'), ['current_password' => 'not-it']);
    $wrong->assertSessionHasErrors(['current_password']);   // the REAL rule, rejecting a wrong password
    expect(session('password_status'))->toBeNull();          // and the gate no longer interrupts at all
});

// ══ #7 · ⚠️⚠️ OFF WITHOUT CREDENTIALS — THE STATE THAT SHIPS TODAY ═══════════════════════════════════

it('⚠️⚠️ with NO credentials configured there is no button, no route and no error page', function () {
    config(['services.google.client_id' => '', 'services.google.client_secret' => '', 'services.google.redirect' => '']);
    app()->forgetInstance(SocialIdentityProvider::class);

    expect(app(SocialIdentityProvider::class)->enabled())->toBeFalse();

    // ⚠️ 404, not a 500 and not a friendly error screen: with nothing configured the endpoint genuinely
    //    does not exist.
    $this->get(route('social.google.redirect'))->assertNotFound();
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => 's']))->assertNotFound();

    // And the login page carries no control that would lead there.
    $login = $this->get(route('login'));
    $login->assertOk();
    $this->assertStringNotContainsString('Continue with Google', $login->getContent());
});

it('⚠️ the site works entirely without them — every existing account still signs in with its password', function () {
    config(['services.google.client_id' => '', 'services.google.client_secret' => '']);
    $scenario = seedScenario(1);
    $email = uniqueEmail('classicsignin');
    operatorUser($scenario, ['email' => $email, 'password' => bcrypt('Str0ng-Existing!1')]);

    expect(Auth::validate(['email' => $email, 'password' => 'Str0ng-Existing!1', 'status' => User::SIGN_IN_STATUS]))->toBeTrue();
});

// ══ ⚠️ GOOGLE SIGN-IN MUST NOT BE A WAY AROUND A SECOND FACTOR ═══════════════════════════════════════

it('⚠️⚠️ does NOT skip 2FA — a Google sign-in for a 2FA account is CHALLENGED, not admitted', function () {
    $fake = fakeGoogle();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('twofactor')]);
    $user->forceFill([
        'two_factor_enabled' => true,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $sub = 'sub-'.Str::random(10);
    UserIdentity::create(['user_id' => $user->getKey(), 'provider' => 'google', 'provider_user_id' => $sub]);
    $fake->next = googleIdentity($sub, (string) $user->email);

    $state = beginGoogleFlow($this);
    $response = $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));

    // ⚠️ NOT SIGNED IN. Using the newer button must never make an account less safe than the older one.
    expect(Auth::guard('web')->check())->toBeFalse();
    $response->assertRedirect(route('two-factor.challenge'));
});

// ══ ⚠️ NO STUB IS REACHABLE ══════════════════════════════════════════════════════════════════════════

it('⚠️⚠️ the application resolves a REAL provider — the fake exists only in tests', function () {
    // The container's own binding, with nothing swapped in.
    config(['services.google.client_id' => 'x', 'services.google.client_secret' => 'y']);
    app()->forgetInstance(SocialIdentityProvider::class);

    expect(app(SocialIdentityProvider::class))->toBeInstanceOf(GoogleIdentityProvider::class);

    // ⚠️ And no route anywhere reaches a fake or a stub — the failure mode would be an authentication bypass.
    $routes = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($r): string => (string) $r->getActionName())
        ->filter(fn (string $a): bool => str_contains(strtolower($a), 'fake') || str_contains(strtolower($a), 'stub'));

    expect($routes->all())->toBe([]);
});

it('⚠️ a failed provider exchange yields NO identity and NO account', function () {
    $fake = fakeGoogle();
    $fake->failExchange = true;
    $before = User::query()->count();

    $state = beginGoogleFlow($this);
    $this->get(route('social.google.callback', ['code' => 'c', 'state' => $state]));

    expect(User::query()->count())->toBe($before)
        ->and(Auth::guard('web')->check())->toBeFalse()
        ->and(session()->has('social.signup'))->toBeFalse();
});
