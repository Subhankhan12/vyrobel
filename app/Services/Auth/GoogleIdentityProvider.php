<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\Auth\SocialIdentityProvider;
use App\Support\Auth\SocialIdentity;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google, via the OpenID Connect authorization-code flow.
 *
 * ⚠️⚠️ WHY THERE IS NO NEW DEPENDENCY. `laravel/socialite` is the usual route, but this project's standing
 *    rule is to ASK before installing, and the whole exchange is one server-side POST plus one GET. The
 *    flow is implemented directly so the fail-closed binding, the scope list and the `state` handling are
 *    all visible in one readable file rather than configured into a package. Socialite remains an option
 *    the founder can choose later; nothing above this class would change.
 *
 * ⚠️ WHY THE ID TOKEN'S SIGNATURE IS NOT VERIFIED HERE, AND WHY THAT IS CORRECT. The identity is read from
 *    the response to a direct, server-to-server, TLS-protected POST to Google's token endpoint, authenticated
 *    with the client secret. OpenID Connect Core §3.1.3.7 explicitly permits a client to skip id_token
 *    signature validation when the token is received this way. ⚠️ It would NOT be acceptable to read an
 *    id_token handed to us by the browser — that is a different flow, and this class never accepts one.
 *
 * ⚠️ SCOPES ARE HELD TO `openid email profile`. Anything beyond these triggers Google's verification review
 *    and asks users to grant access this product has no use for.
 */
final class GoogleIdentityProvider implements SocialIdentityProvider
{
    public const KEY = 'google';

    /** ⚠️ The complete scope list. Do not extend it without a reason that survives being explained to a user. */
    public const SCOPES = 'openid email profile';

    private const AUTHORIZE_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    public function key(): string
    {
        return self::KEY;
    }

    /**
     * ⚠️⚠️ HOW ABSENCE IS DETECTED — and it is deliberately the credentials themselves, not a separate
     *    on/off flag. A boolean toggle can be true while the secret is missing, which produces exactly the
     *    thing this feature must never show: a sign-in button that leads to an error page. With no flag to
     *    disagree with, "configured" and "enabled" cannot drift apart.
     */
    public function enabled(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    public function authorizationUrl(string $state): string
    {
        return self::AUTHORIZE_ENDPOINT.'?'.http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'state' => $state,
            // Ask for a fresh account choice rather than silently reusing whichever Google session the
            // browser happens to hold — signing in as the wrong person is a confusing failure to recover from.
            'prompt' => 'select_account',
        ]);
    }

    public function identityFromCode(string $code): SocialIdentity
    {
        $response = Http::asForm()->post(self::TOKEN_ENDPOINT, [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Google rejected the authorization code.');
        }

        $idToken = (string) $response->json('id_token', '');
        $claims = $this->claims($idToken);

        $sub = (string) ($claims['sub'] ?? '');
        $email = (string) ($claims['email'] ?? '');

        if ($sub === '' || $email === '') {
            // ⚠️ Never guess. An identity without a subject or an address is not an identity, and inventing
            //    either would be inventing a person.
            throw new RuntimeException('Google returned an incomplete identity.');
        }

        return new SocialIdentity(
            provider: self::KEY,
            providerUserId: $sub,
            email: mb_strtolower(trim($email)),
            // ⚠️ Absent means NOT verified. The default is the safe answer, never the convenient one.
            emailVerified: ($claims['email_verified'] ?? false) === true,
            name: trim((string) ($claims['name'] ?? '')) ?: $email,
        );
    }

    /**
     * Decode the id_token payload. See the class note: this token came from the token endpoint over TLS,
     * so its signature is not re-verified — and a token from anywhere else is never accepted.
     *
     * @return array<string, mixed>
     */
    private function claims(string $idToken): array
    {
        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            throw new RuntimeException('Google returned a malformed id_token.');
        }

        $payload = json_decode(
            (string) base64_decode(strtr($parts[1], '-_', '+/'), true),
            true,
        );

        if (! is_array($payload)) {
            throw new RuntimeException('Google returned an unreadable id_token.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    private function clientId(): string
    {
        return trim((string) config('services.google.client_id', ''));
    }

    private function clientSecret(): string
    {
        return trim((string) config('services.google.client_secret', ''));
    }

    private function redirectUri(): string
    {
        $configured = trim((string) config('services.google.redirect', ''));

        return $configured !== '' ? $configured : url('/auth/google/callback');
    }
}
