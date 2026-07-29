<?php

declare(strict_types=1);

namespace App\Services;

use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

/**
 * Registers a third-party OAuth CLIENT (Wave D · Passport · Phase 1) — the ONE Vyrobel seam for creating an
 * authorization-code app, wrapping Passport's {@see ClientRepository}. Clients are PLATFORM-ADMIN registered
 * (no open self-serve — an abuse surface we avoid); the demo seeds a clearly-labelled EXAMPLE client.
 *
 * A client is either CONFIDENTIAL (a server app — issued a secret, shown once, for the token exchange) or
 * PUBLIC (an SPA/mobile app — NO secret; it MUST use PKCE). Every client uses the authorization-code grant
 * only; there is no password/client-credentials/implicit path (least privilege). Redirect URIs are the exact
 * allow-list the OAuth server will honour.
 *
 * A client can request any catalog scope, but the owner's CONSENT + the API's scope→capability gate are the
 * authoritative limits — a client can never obtain data the owner didn't approve or the owner's role can't reach.
 */
class OAuthClientRegistrar
{
    public function __construct(private ClientRepository $clients) {}

    /**
     * Register an authorization-code client. `confidential=false` → a PUBLIC client (PKCE, no secret).
     *
     * @param  list<string>  $redirectUris  the exact redirect allow-list
     */
    public function register(string $name, array $redirectUris, bool $confidential = true): Client
    {
        return $this->clients->createAuthorizationCodeGrantClient(
            name: $name,
            redirectUris: $redirectUris,
            confidential: $confidential,
            user: null,            // platform-level third-party app — not owned by any tenant user
        );
    }
}
