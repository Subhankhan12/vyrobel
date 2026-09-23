<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\Auth\SocialIdentityProvider;
use App\Support\Auth\SocialIdentity;
use App\Support\Auth\SocialLinking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * X (formerly Twitter) as an identity provider — OAuth 2.0, authorization code + PKCE.
 *
 * ⚠️⚠️ `emailVerified` IS ALWAYS FALSE, AND THAT IS THE HONEST VALUE, NOT A PLACEHOLDER.
 * 🔬 Measured against the live API 2026-09-24 with scope `users.email` granted: `/2/users/me` returns a
 *    POPULATED `confirmed_email`, and **no verification signal anywhere in the response**. `verified` and
 *    `verified_type` describe the paid blue check (`false`, `none`) — they say nothing about the mailbox.
 * ⚠️ The field is NAMED `confirmed_email`, which implies X confirmed it. An implication in a field name is
 *    not a provider assertion, and {@see SocialLinking} spends this flag on exactly one
 *    decision: whether an address may CREATE an account. Sign-in and linking never read it.
 * ⚠️ Treating the name as a promise is a founder ruling, not a build step. Until it is made, X can sign a
 *    person in and can be linked to an account, and cannot mint one.
 *
 * ⚠️⚠️ WHY PKCE LIVES IN THE SESSION AND NOT IN THE CONTRACT. X requires a `code_verifier` created when the
 *    browser leaves and replayed when the code is exchanged; Google requires none. The contract passes only
 *    `$state` to the first call and only `$code` to the second, so there is nowhere to carry it.
 * ⚠️ Widening the interface was the obvious fix and is the wrong one: `FakeSocialIdentityProvider` implements
 *    it, so the signature change would break the fake and MOVE THE 30 GOOGLE TESTS. This provider therefore
 *    keeps its verifier under a session key it owns alone. Google never learns that PKCE exists.
 */
final class TwitterIdentityProvider implements SocialIdentityProvider
{
    public const KEY = 'twitter';

    /** ⚠️ `users.email` is what makes an address available at all; the other two are required to call /2/users/me. */
    public const SCOPES = 'users.read tweet.read users.email';

    /** ⚠️ Owned by this class alone. The controller's own state key is separate and untouched. */
    private const VERIFIER_KEY = 'social.twitter.pkce_verifier';

    private const AUTHORIZE_ENDPOINT = 'https://x.com/i/oauth2/authorize';

    private const TOKEN_ENDPOINT = 'https://api.x.com/2/oauth2/token';

    private const ME_ENDPOINT = 'https://api.x.com/2/users/me';

    public function key(): string
    {
        return self::KEY;
    }

    /**
     * ⚠️ The credentials themselves are the on/off switch — the same rule Google ships under. A separate
     *    boolean could be true while a secret was missing, which produces a button that leads to an error.
     */
    public function enabled(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    public function authorizationUrl(string $state): string
    {
        // ⚠️ A fresh verifier per attempt. Stored, never derived from the state: a verifier computable from
        //    a value that travels in the URL would defeat the point of PKCE.
        $verifier = Str::random(96);
        session()->put(self::VERIFIER_KEY, $verifier);

        return self::AUTHORIZE_ENDPOINT.'?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'scope' => self::SCOPES,
            'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]);
    }

    public function identityFromCode(string $code): SocialIdentity
    {
        // ⚠️ PULLED, not read: a verifier is single-use, and leaving it behind would let a replayed callback
        //    be exchanged a second time.
        $verifier = (string) session()->pull(self::VERIFIER_KEY, '');

        if ($verifier === '') {
            throw new RuntimeException('The X sign-in did not start here, or it has already been used.');
        }

        $token = Http::asForm()
            ->withBasicAuth($this->clientId(), $this->clientSecret())
            ->post(self::TOKEN_ENDPOINT, [
                'code' => $code,
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId(),
                'redirect_uri' => $this->redirectUri(),
                'code_verifier' => $verifier,
            ]);

        if ($token->failed()) {
            throw new RuntimeException('X rejected the authorization code.');
        }

        $accessToken = (string) $token->json('access_token', '');

        if ($accessToken === '') {
            throw new RuntimeException('X returned no access token.');
        }

        $me = Http::withToken($accessToken)->get(self::ME_ENDPOINT, ['user.fields' => 'confirmed_email']);

        if ($me->failed()) {
            throw new RuntimeException('X rejected the profile request.');
        }

        $id = (string) $me->json('data.id', '');
        $email = mb_strtolower(trim((string) $me->json('data.confirmed_email', '')));

        // ⚠️ Never guess. An identity with no subject is not an identity; an identity with no address cannot
        //    be linked or matched here, and inventing a placeholder would be inventing a person.
        if ($id === '' || $email === '') {
            throw new RuntimeException('X returned an incomplete identity.');
        }

        return new SocialIdentity(
            provider: self::KEY,
            providerUserId: $id,
            email: $email,
            // ⚠️⚠️ ALWAYS FALSE — see the class note. X asserts nothing about the mailbox.
            emailVerified: false,
            name: trim((string) $me->json('data.name', '')) ?: (string) $me->json('data.username', '') ?: $email,
        );
    }

    private function clientId(): string
    {
        return trim((string) config('services.twitter.client_id', ''));
    }

    private function clientSecret(): string
    {
        return trim((string) config('services.twitter.client_secret', ''));
    }

    private function redirectUri(): string
    {
        $configured = trim((string) config('services.twitter.redirect', ''));

        return $configured !== '' ? $configured : url('/auth/twitter/callback');
    }
}
