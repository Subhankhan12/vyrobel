<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Contracts\Auth\SocialIdentityProvider;
use App\Http\Controllers\Auth\Concerns\CompletesSignIn;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIdentity;
use App\Services\Auth\GoogleIdentityProvider;
use App\Support\Auth\SignInCredentials;
use App\Support\Auth\SocialIdentity;
use App\Support\Auth\SocialLinking;
use App\Support\Auth\SocialLinkOutcome;
use App\Support\Auth\SocialProviders;
use App\Support\Platform\PlatformSwitches;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * "Continue with Google" — sign in, sign up, link and unlink, on the OPERATOR realm.
 *
 * ⚠️⚠️ THE DECISIONS ARE NOT MADE HERE. {@see SocialLinking} owns the matching rule and
 *    {@see SignInCredentials} owns the unlink floor; this controller carries out what they return. That
 *    split is deliberate — the rule that must not be wrong is testable without a browser, a session or a
 *    route, and it cannot differ from the one that actually runs.
 *
 * ⚠️⚠️ THERE IS NO STUB AND NO BYPASS. The provider is resolved from the container against a real
 *    implementation; the only fake is bound inside the test suite. Nothing in this file is reachable at all
 *    unless the founder's console credentials are configured — see {@see self::provider()}.
 */
final class SocialAuthController extends Controller
{
    use CompletesSignIn;

    /** The session key holding the single-use `state`, its intent, and when it was issued. */
    private const STATE_KEY = 'social.oauth';

    /** The session key holding a verified identity waiting for the signup form to be completed. */
    public const SIGNUP_KEY = 'social.signup';

    /** ⚠️ A flow that is not finished within this window is abandoned, not resumed. */
    private const TTL_MINUTES = 15;

    /** Begin sign-in / sign-up. Public — the user has no account yet, or is not signed in. */
    public function redirectToProvider(Request $request): RedirectResponse
    {
        return $this->begin($request, intent: 'login');
    }

    /** Begin LINKING from the account page. Authenticated: being signed in IS the proof that a match on the address could never be. */
    public function redirectToLink(Request $request): RedirectResponse
    {
        return $this->begin($request, intent: 'link');
    }

    private function begin(Request $request, string $intent): RedirectResponse
    {
        $provider = $this->provider($request);

        // ⚠️ ADMIN 01 · PHASE 1 — with BOTH sign-in and sign-up paused for this provider there is nothing the
        //    round trip could lead to, so say so here instead of sending the person to the provider and back.
        //    Linking an identity to a signed-in account is governed by neither switch.
        if ($intent === 'login'
            && ! PlatformSwitches::signinAllowed($provider->key())
            && ! PlatformSwitches::signupAllowed($provider->key())) {
            return $this->refuse('login', SocialProviders::label($provider->key()).' sign-in is paused right now. Sign in with your password instead.');
        }

        // ⚠️ Generated per attempt, never reused. This is the CSRF defence of the whole flow: without it, an
        //    attacker can complete an authorization they started and have the victim's browser deliver it.
        $state = Str::random(48);

        $request->session()->put(self::STATE_KEY, [
            'state' => $state,
            'intent' => $intent,
            'issued_at' => now()->toIso8601String(),
        ]);

        return redirect()->away($provider->authorizationUrl($state));
    }

    /** The provider sends the browser back here. Everything that follows is a refusal until proven otherwise. */
    public function callback(Request $request): RedirectResponse
    {
        $provider = $this->provider($request);

        // ⚠️⚠️ STATE FIRST, AND CONSUMED WHATEVER HAPPENS NEXT. Pulling it before any other work makes the
        //    value SINGLE-USE by construction: a replayed callback finds nothing in the session and is
        //    refused, exactly as a forged one is.
        $stored = $request->session()->pull(self::STATE_KEY);
        $intent = is_array($stored) ? (string) ($stored['intent'] ?? 'login') : 'login';

        if (! $this->stateIsValid($stored, (string) $request->query('state', ''))) {
            return $this->refuse($intent, 'That sign-in link has expired or did not come from us. Please try again.');
        }

        // The user pressed Cancel, or Google refused. Not an error to shout about — just nothing happened.
        if ($request->query('error') !== null || (string) $request->query('code', '') === '') {
            return $this->refuse($intent, 'Sign-in with Google was cancelled.');
        }

        try {
            $identity = $provider->identityFromCode((string) $request->query('code'));
        } catch (RuntimeException) {
            // ⚠️ A failed exchange yields NO identity. Never a partial one, and never a guess from the query
            //    string — the whole point of the server-side exchange is that the browser cannot supply this.
            return $this->refuse($intent, 'We could not complete sign-in with Google. Please try again.');
        }

        return $intent === 'link'
            ? $this->finishLink($request, $identity)
            : $this->finishSignIn($request, $identity);
    }

    /** ⚠️ The unauthenticated outcomes. Only ONE of them signs anybody in. */
    private function finishSignIn(Request $request, SocialIdentity $identity): RedirectResponse
    {
        ['outcome' => $outcome, 'user' => $user] = SocialLinking::forSignIn($identity);

        $label = SocialProviders::label($identity->provider);

        // ⚠️⚠️ ADMIN 01 · PHASE 1 — THE KILL SWITCHES, AT THE POINT THE OUTCOME IS KNOWN. Sign-in and sign-up are
        //    separate switches because they fail differently: pausing sign-in locks out passwordless accounts
        //    (the console counts them first); pausing sign-up only stops new organisations being created.
        if ($outcome === SocialLinkOutcome::SignIn && ! PlatformSwitches::signinAllowed($identity->provider)) {
            return $this->refuse('login', "Sign-in with {$label} is paused right now. Sign in with your password instead, or try again later.");
        }

        if ($outcome === SocialLinkOutcome::SignUp && ! PlatformSwitches::signupAllowed($identity->provider)) {
            return $this->refuse('login', "New sign-ups with {$label} are paused right now. Please try again later.");
        }

        return match ($outcome) {
            SocialLinkOutcome::SignIn => $this->signIn($request, $user, $identity),

            SocialLinkOutcome::RefuseUnverified => $this->refuse('login',
                "{$label} has not verified that email address, so we cannot use it to sign in."),

            // ⚠️⚠️ THE TAKEOVER REFUSAL. Deliberately says what to do instead, because the user is very
            //    probably the right person — they just have not proved it yet, and an address is not proof.
            SocialLinkOutcome::RefuseEmailTaken => $this->refuse('login',
                "An account already uses that email address. Sign in with your password, then connect {$label} from your security page."),

            SocialLinkOutcome::SignUp => $this->beginSignUp($request, $identity),

            default => $this->refuse('login', "We could not complete sign-in with {$label}."),
        };
    }

    /**
     * ⚠️⚠️ SIGNING UP LEAVES NOTHING BEHIND UNTIL THE FORM IS SUBMITTED.
     *
     * Provisioning needs seven business facts — organisation name, property type, currency, property name,
     * plus the person's name and email — and a callback supplies two of them. So this is not one click: it
     * is "authenticate, then complete the organisation form, pre-filled and with the password field gone".
     *
     * ⚠️ The half-finished state lives in THE SESSION AND NOWHERE ELSE. No user, no organisation, no
     *    membership, no property, no trial exists until {@see OnboardingController::store()} runs — so a
     *    person who closes the tab here leaves no orphan account and, more importantly, no organisation. A
     *    half-provisioned organisation is a money object with no owner.
     */
    private function beginSignUp(Request $request, SocialIdentity $identity): RedirectResponse
    {
        $request->session()->put(self::SIGNUP_KEY, [
            'provider' => $identity->provider,
            'provider_user_id' => $identity->providerUserId,
            'email' => $identity->email,
            'name' => $identity->name,
            'issued_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('signup');
    }

    private function signIn(Request $request, ?User $user, SocialIdentity $identity): RedirectResponse
    {
        if ($user === null || $user->status !== User::SIGN_IN_STATUS) {
            // A link pointing at a suspended account is not a way around the suspension.
            return $this->refuse('login', 'That account cannot sign in at the moment.');
        }

        UserIdentity::query()
            ->where('provider', $identity->provider)
            ->where('provider_user_id', $identity->providerUserId)
            ->update(['provider_email' => $identity->email, 'last_used_at' => now()]);

        // ⚠️ THE SHARED POST-CREDENTIAL PATH — so a 2FA-enabled account is still challenged. Signing in with
        //    Google must never be a way to skip a factor the user turned on.
        return $this->completeSignInOrNull($request, $user)
            ?? $this->refuse('login', 'This account does not have access to any workspace yet.');
    }

    /** ⚠️ The authenticated outcomes. Linking is only ever reached from inside a session. */
    private function finishLink(Request $request, SocialIdentity $identity): RedirectResponse
    {
        $actor = $request->user();
        // ⚠️ 2026-10-05 — every message here said "Google", whichever provider was being linked (X too).
        $label = SocialProviders::label($identity->provider);

        if (! $actor instanceof User) {
            return $this->refuse('login', "Please sign in before connecting a {$label} account.");
        }

        return match (SocialLinking::forLink($actor, $identity)) {
            SocialLinkOutcome::Link => $this->createLink($actor, $identity),

            SocialLinkOutcome::AlreadyLinked => redirect()->route('app.security')
                ->with('connections_status', "That {$label} account is already connected."),

            SocialLinkOutcome::RefuseUnverified => $this->refuse('link',
                "{$label} has not verified that email address, so we cannot connect it."),

            // ⚠️ Never move a link. One provider account signs in as one Vyrobel user, and re-pointing it here
            //    would let whoever holds it take over a second account.
            SocialLinkOutcome::RefuseIdentityTaken => $this->refuse('link',
                "That {$label} account is already connected to a different Vyrobel account."),

            default => $this->refuse('link', "We could not connect that {$label} account."),
        };
    }

    private function createLink(User $actor, SocialIdentity $identity): RedirectResponse
    {
        UserIdentity::create([
            'user_id' => $actor->getKey(),
            'provider' => $identity->provider,
            'provider_user_id' => $identity->providerUserId,
            'provider_email' => $identity->email,
            'last_used_at' => now(),
        ]);

        return redirect()->route('app.security')
            ->with('connections_status', SocialProviders::label($identity->provider).' connected. You can now sign in with it.');
    }

    /**
     * ⚠️⚠️ THE LOCKOUT FLOOR, CHECKED HERE AND NOT IN THE VIEW. The screen also disables the control and says
     *    why, but a disabled button is presentation — a direct POST goes straight around it, and the person
     *    it would lock out is the one who owns the account.
     */
    public function unlink(Request $request, string $identity): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        // Self-only: an identity is found within the actor's own rows or not at all.
        $row = UserIdentity::query()
            ->where('user_id', $actor->getKey())
            ->whereKey($identity)
            ->first();

        if ($row === null) {
            return redirect()->route('app.security')
                ->with('connections_error', 'That connection no longer exists.');
        }

        if (! SignInCredentials::mayUnlink($actor, (string) $row->getKey())) {
            return redirect()->route('app.security')
                ->with('connections_error', SignInCredentials::unlinkRefusalReason());
        }

        $row->delete();

        // ⚠️ 2026-10-05 — this said "Google disconnected." for X too
        return redirect()->route('app.security')
            ->with('connections_status', SocialProviders::label((string) $row->provider).' disconnected.');
    }

    /**
     * ⚠️⚠️ THE FEATURE IS OFF WITHOUT CREDENTIALS — no button, no route, no error page. A 404 is the honest
     *    answer: with nothing configured, this endpoint genuinely does not exist. ⚠️ It is checked HERE, in
     *    every entry point, rather than only when registering routes, so that a route cached while the
     *    credentials were present cannot outlive them.
     */
    private function provider(Request $request): SocialIdentityProvider
    {
        // ⚠️⚠️ THE KEY COMES FROM THE ROUTE'S OWN DEFAULT, NOT FROM A URL SEGMENT. Each route hardcodes the
        //    provider it serves (`->defaults('provider', 'google')`), so there is no wildcard for a caller to
        //    put anything into — the tightest possible allow-list, and nothing to validate at runtime.
        // ⚠️ The Google fallback keeps a route-cache built before this change working, rather than 500-ing.
        $key = (string) ($request->route('provider') ?? GoogleIdentityProvider::KEY);

        $provider = SocialProviders::resolve($key);

        if ($provider === null || ! $provider->enabled()) {
            throw new NotFoundHttpException;
        }

        return $provider;
    }

    /** @param  mixed  $stored */
    private function stateIsValid($stored, string $received): bool
    {
        if (! is_array($stored) || $received === '') {
            return false;
        }

        $expected = (string) ($stored['state'] ?? '');
        $issued = (string) ($stored['issued_at'] ?? '');

        if ($expected === '' || $issued === '') {
            return false;
        }

        if (now()->parse($issued)->addMinutes(self::TTL_MINUTES)->isPast()) {
            return false;
        }

        // ⚠️ Constant-time. The value is a secret for the length of one round trip.
        return hash_equals($expected, $received);
    }

    private function refuse(string $intent, string $message): RedirectResponse
    {
        return $intent === 'link'
            ? redirect()->route('app.security')->with('connections_error', $message)
            : redirect()->route('login')->withErrors(['email' => $message]);
    }
}
