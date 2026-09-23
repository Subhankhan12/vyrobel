<?php

declare(strict_types=1);

use App\Models\UserIdentity;
use App\Services\Auth\TwitterIdentityProvider;
use App\Support\Auth\SocialIdentity;
use App\Support\Auth\SocialLinking;
use App\Support\Auth\SocialLinkOutcome;
use App\Support\Auth\SocialProviders;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\Support\FakeSocialIdentityProvider;

/**
 * ⚠️⚠️ X SIGN-IN AND LINKING — AND DELIBERATELY NOT SIGNUP.
 *
 * 🔬 MEASURED AGAINST THE LIVE X API 2026-09-24, with scope `users.email` granted on a Pay Per Use account:
 *    `/2/users/me` returns a POPULATED `confirmed_email` and **no verification signal at all**. `verified`
 *    and `verified_type` describe the paid blue check (`false`, `none`), not the mailbox.
 * ⚠️ So `emailVerified` is FALSE for X, honestly rather than defensively. {@see SocialLinking} reads that
 *    flag for exactly one decision — may this address CREATE an account — and never on sign-in or linking.
 *    A person can therefore sign in with a linked X account and attach X from their account page today,
 *    and cannot mint a new account until the founder rules on it.
 */
function fakeTwitter(bool $enabled = true): FakeSocialIdentityProvider
{
    config([
        'services.twitter.client_id' => 'test-x-client-id',
        'services.twitter.client_secret' => 'test-x-client-secret',
        'services.twitter.redirect' => 'http://localhost/auth/twitter/callback',
    ]);

    $fake = new FakeSocialIdentityProvider($enabled, 'twitter');
    // ⚠️ Bound by CONCRETE CLASS, because that is how SocialProviders resolves anything that is not Google.
    //    Google alone resolves through the contract, to preserve the seam its own 30 tests swap.
    app()->instance(TwitterIdentityProvider::class, $fake);

    return $fake;
}

function xIdentity(string $id, string $email, string $name = 'Ada Okafor'): SocialIdentity
{
    // ⚠️ verified is FALSE, always — that is what X asserts, which is nothing.
    return new SocialIdentity('twitter', $id, $email, false, $name);
}

function beginXFlow(object $test): string
{
    $location = (string) $test->get(route('social.twitter.redirect'))->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    return (string) ($query['state'] ?? '');
}

// ══ #1 · SIGN IN WITH AN ALREADY-LINKED IDENTITY — the case that needs no address at all ═════════════

it('⚠️ signs in a user whose X identity is already linked — matched on the provider id, never the email', function () {
    $fake = fakeTwitter();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('x-linked')]);

    $providerUserId = 'x-'.Str::random(12);
    UserIdentity::query()->create([
        'user_id' => $user->getKey(),
        'provider' => 'twitter',
        'provider_user_id' => $providerUserId,
        'provider_email' => 'whatever@example.test',
    ]);

    // ⚠️ A COMPLETELY DIFFERENT ADDRESS, and it must not matter: the link is on the provider id.
    $fake->next = xIdentity($providerUserId, uniqueEmail('totally-different'));

    $state = beginXFlow($this);
    $this->get(route('social.twitter.callback', ['code' => 'auth-code', 'state' => $state]));

    expect(Auth::guard('web')->id())->toBe((string) $user->getKey());
});

it('⚠️ the decision for a linked identity is SignIn, and it is reached BEFORE any email check', function () {
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('x-order')]);
    $pid = 'x-'.Str::random(12);

    UserIdentity::query()->create([
        'user_id' => $user->getKey(), 'provider' => 'twitter',
        'provider_user_id' => $pid, 'provider_email' => null,
    ]);

    // Unverified AND an address that already belongs to somebody else would both refuse — if either were
    // consulted. Neither is, because the link resolves first.
    $result = SocialLinking::forSignIn(xIdentity($pid, $user->email));

    expect($result['outcome'])->toBe(SocialLinkOutcome::SignIn)
        ->and((string) $result['user']->getKey())->toBe((string) $user->getKey());
});

// ══ #2 · ⚠️⚠️ THE REFUSAL — an identity owned by someone else must never move ════════════════════════

it('⚠️⚠️ REFUSES to move an identity that already belongs to a different user', function () {
    $scenario = seedScenario(1);
    $owner = operatorUser($scenario, ['email' => uniqueEmail('x-owner')]);
    $actor = operatorUser($scenario, ['email' => uniqueEmail('x-actor')]);
    $pid = 'x-'.Str::random(12);

    UserIdentity::query()->create([
        'user_id' => $owner->getKey(), 'provider' => 'twitter',
        'provider_user_id' => $pid, 'provider_email' => null,
    ]);

    // ⚠️ Asserted with a VERIFIED identity on purpose. An UNVERIFIED one is refused earlier, by the
    //    emailVerified gate, so it would never reach this check — and a test that passes for the wrong
    //    reason proves nothing. This proves the ownership guard itself.
    $verified = new SocialIdentity('twitter', $pid, uniqueEmail('x-actor-mail'), true, 'Ada');
    $outcome = SocialLinking::forLink($actor, $verified);

    expect($outcome)->toBe(SocialLinkOutcome::RefuseIdentityTaken)
        ->and((string) UserIdentity::query()->where('provider_user_id', $pid)->value('user_id'))
        ->toBe((string) $owner->getKey(), 'the row must still point at its original owner');
});

// ══ #3 · LINKING FROM A SESSION — no verified address needed, because the session IS the proof ═══════

it('⚠️⚠️ X CAN NOW BE LINKED from an authenticated session — the 2026-09-24 ruling', function () {
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('x-link')]);

    // ⚠️ THE RULING THIS PINS. The emailVerified check was MOVED out of forLink() and left unweakened in
    //    forSignIn(). An X identity is honestly emailVerified=false — X asserts nothing about the mailbox —
    //    and linking it is nonetheless correct: the session proves the Vyrobel account, the provider flow
    //    proves the X account, and the address resolves nothing on this path.
    expect(SocialLinking::forLink($user, xIdentity('x-'.Str::random(12), uniqueEmail('x-other'))))
        ->toBe(SocialLinkOutcome::Link);
});

it('⚠️⚠️ X MAY NOW CREATE AN ACCOUNT — the 2026-09-24 signup ruling, named and pinned', function () {
    // ⚠️ X still reports emailVerified=false, honestly. What permits creation is an explicit ruled list,
    //    not a forged flag — so the data never claims X verified something it did not.
    $result = SocialLinking::forSignIn(xIdentity('x-'.Str::random(12), uniqueEmail('x-create')));

    expect($result['outcome'])->toBe(SocialLinkOutcome::SignUp)
        ->and($result['user'])->toBeNull('a signup decision names no user yet');
});

it('⚠️⚠️ AND GOOGLE IS UNTOUCHED — an unverified Google address still may NOT create', function () {
    // ⚠️⚠️ THE HALF THAT MUST NEVER MOVE. Google asserts verification itself, so it is deliberately absent
    //    from the trusted list and its guard is exactly as strict as before the X ruling existed.
    $result = SocialLinking::forSignIn(
        new SocialIdentity('google', 'g-'.Str::random(12), uniqueEmail('g-unverified'), false, 'Ada')
    );

    expect($result['outcome'])->toBe(SocialLinkOutcome::RefuseUnverified);
});

it('⚠️⚠️ PINS THE TRUSTED-FOR-SIGNUP LIST — it may not grow without a ruling', function () {
    // ⚠️ A list that can grow silently is not a ruling, it is a habit. Every provider is checked, so
    //    adding one to the registry without deciding this question makes THIS test red, not production.
    $trusted = array_values(array_filter(
        SocialProviders::keys(),
        fn (string $k) => SocialProviders::trustsAddressForSignup($k)
    ));

    expect($trusted)->toBe(['twitter'],
        'exactly one provider is trusted to create accounts from an unasserted address, by the '
        .'2026-09-24 ruling. If this is red, a provider was added without that decision being made.');
});

it('⚠️ RefuseEmailTaken still refuses X when the address already belongs to an account', function () {
    $scenario = seedScenario(1);
    $email = uniqueEmail('x-taken');
    operatorUser($scenario, ['email' => $email]);

    // ⚠️⚠️ THE TAKEOVER CASE, AND THE SIGNUP RULING DOES NOT REOPEN IT. Creating is permitted; ATTACHING
    //    to an account that already exists at that address is still refused, which is what keeps the live
    //    risk to squatting an unclaimed address rather than stealing a claimed one.
    $result = SocialLinking::forSignIn(xIdentity('x-'.Str::random(12), $email));

    expect($result['outcome'])->toBe(SocialLinkOutcome::RefuseEmailTaken)
        ->and($result['user'])->toBeNull();
});

it('⚠️ a signed-in user can end up holding BOTH providers — one account, two doors', function () {
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('x-both')]);

    UserIdentity::query()->create([
        'user_id' => $user->getKey(), 'provider' => 'google',
        'provider_user_id' => 'g-'.Str::random(10), 'provider_email' => $user->email,
    ]);
    $xId = 'x-'.Str::random(12);
    expect(SocialLinking::forLink($user, xIdentity($xId, uniqueEmail('x-both-alt'))))->toBe(SocialLinkOutcome::Link);
    UserIdentity::query()->create([
        'user_id' => $user->getKey(), 'provider' => 'twitter',
        'provider_user_id' => $xId, 'provider_email' => 'x@example.test',
    ]);

    // ⚠️ Read back, never assumed — two rows, two providers, one user.
    $rows = UserIdentity::query()->where('user_id', $user->getKey())->pluck('provider')->sort()->values()->all();
    expect($rows)->toBe(['google', 'twitter']);

    // And signing in through EITHER resolves the same person.
    foreach ([['google', UserIdentity::query()->where('user_id', $user->getKey())->where('provider', 'google')->value('provider_user_id')],
        ['twitter', $xId]] as [$provider, $pid]) {
        $r = SocialLinking::forSignIn(new SocialIdentity($provider, $pid, uniqueEmail('irrelevant'), false, 'A'));
        expect($r['outcome'])->toBe(SocialLinkOutcome::SignIn)
            ->and((string) $r['user']->getKey())->toBe((string) $user->getKey());
    }
});

// ══ #4 · STATE — a guard never seen to fire is not a guard ═══════════════════════════════════════════

it('⚠️ REFUSES a callback whose state does not match the one this browser began with', function () {
    $fake = fakeTwitter();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('x-state')]);
    $pid = 'x-'.Str::random(12);

    UserIdentity::query()->create([
        'user_id' => $user->getKey(), 'provider' => 'twitter',
        'provider_user_id' => $pid, 'provider_email' => null,
    ]);
    $fake->next = xIdentity($pid, uniqueEmail('x-state-mail'));

    beginXFlow($this);
    $this->get(route('social.twitter.callback', ['code' => 'auth-code', 'state' => 'not-the-state']));

    expect(Auth::guard('web')->check())->toBeFalse('a mismatched state must sign nobody in');
});

it('⚠️ REFUSES a callback with no state at all', function () {
    $fake = fakeTwitter();
    $scenario = seedScenario(1);
    $user = operatorUser($scenario, ['email' => uniqueEmail('x-nostate')]);
    $pid = 'x-'.Str::random(12);

    UserIdentity::query()->create([
        'user_id' => $user->getKey(), 'provider' => 'twitter',
        'provider_user_id' => $pid, 'provider_email' => null,
    ]);
    $fake->next = xIdentity($pid, uniqueEmail('x-nostate-mail'));

    beginXFlow($this);
    $this->get(route('social.twitter.callback', ['code' => 'auth-code']));

    expect(Auth::guard('web')->check())->toBeFalse();
});

// ══ #5 · THE DORMANT-FEATURE RULE — absent credentials mean absent feature ═══════════════════════════

it('⚠️ 404s both X routes when no credentials are configured — no button, no route, no error page', function () {
    config(['services.twitter.client_id' => '', 'services.twitter.client_secret' => '']);
    app()->instance(TwitterIdentityProvider::class, new TwitterIdentityProvider);

    $this->get(route('social.twitter.redirect'))->assertNotFound();
    $this->get(route('social.twitter.callback', ['code' => 'c', 'state' => 's']))->assertNotFound();
});

it('⚠️ the provider reports disabled with empty credentials and enabled with both', function () {
    config(['services.twitter.client_id' => '', 'services.twitter.client_secret' => '']);
    expect((new TwitterIdentityProvider)->enabled())->toBeFalse();

    config(['services.twitter.client_id' => 'id', 'services.twitter.client_secret' => '']);
    expect((new TwitterIdentityProvider)->enabled())->toBeFalse('one credential is not configuration');

    config(['services.twitter.client_id' => 'id', 'services.twitter.client_secret' => 'secret']);
    expect((new TwitterIdentityProvider)->enabled())->toBeTrue();
});

// ══ #6 · THE REGISTRY — a closed list, and an unknown key is not a 500 ═══════════════════════════════

it('⚠️ resolves only the keys it knows, and returns null for anything else', function () {
    expect(SocialProviders::keys())->toContain('google')->toContain('twitter')
        ->and(SocialProviders::resolve('twitter'))->toBeInstanceOf(TwitterIdentityProvider::class)
        ->and(SocialProviders::resolve('facebook'))->toBeNull()
        ->and(SocialProviders::resolve(''))->toBeNull()
        ->and(SocialProviders::resolve('../../etc/passwd'))->toBeNull();
});

it('⚠️⚠️ the X provider asserts emailVerified FALSE — X supplies no verification signal', function () {
    $identity = xIdentity('x-1', 'someone@example.test');

    expect($identity->emailVerified)->toBeFalse()
        ->and(TwitterIdentityProvider::SCOPES)->toContain('users.email');

    // ⚠️⚠️ AND THE CONSEQUENCE, WHICH CHANGED ON 2026-09-24 AND IS THE POINT OF THE WHOLE DESIGN: the
    //    provider still asserts nothing — emailVerified stays FALSE above — and creation is permitted
    //    anyway, by an explicit ruled list rather than by forging the flag. The data never claims X
    //    verified an address it did not. ⚠️ If someone ever "fixes" this by setting emailVerified true in
    //    the provider, the assertion above goes red first, which is exactly the order that should happen.
    $result = SocialLinking::forSignIn(xIdentity('x-unlinked-'.Str::random(8), uniqueEmail('x-new')));
    expect($result['outcome'])->toBe(SocialLinkOutcome::SignUp);
});
