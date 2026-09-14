<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

use App\Services\Channex\FakeChannexClient;
use App\Support\Auth\SocialIdentity;
use App\Support\Stripe\StripeSafetyGuard;

/**
 * The seam between Vyrobel and an external sign-in provider.
 *
 * ⚠️⚠️ THERE IS NO STUB IMPLEMENTATION IN THE APPLICATION, AND THERE MUST NEVER BE ONE. A fake provider
 *    reachable over HTTP is an authentication bypass — whoever reaches it signs in as anyone — which is the
 *    single most severe defect this product could ship. The only fake lives INSIDE the test suite, bound
 *    into the container by a test, where no route exists and nothing is reachable.
 *
 * ⚠️ Compare {@see FakeChannexClient}, which is bound by a config default of TRUE.
 *    That is right for a channel manager (worst case: no sync) and catastrophic here. The model this seam
 *    follows is {@see StripeSafetyGuard}: fail CLOSED in the dangerous direction.
 */
interface SocialIdentityProvider
{
    /** The provider key stored on `user_identities.provider` (e.g. `google`). */
    public function key(): string;

    /**
     * ⚠️ FALSE WHEN THE FEATURE IS NOT CONFIGURED. Everything — the routes and the buttons — is off when
     *    this is false, which is the state that ships until the founder enters the console credentials.
     */
    public function enabled(): bool;

    /** Where to send the browser to begin authorisation, carrying the single-use `state`. */
    public function authorizationUrl(string $state): string;

    /**
     * Exchange the authorization code server-side and return what the provider asserts.
     *
     * @throws \RuntimeException when the exchange fails — never a partial or guessed identity.
     */
    public function identityFromCode(string $code): SocialIdentity;
}
