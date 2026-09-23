<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\Auth\SocialIdentityProvider;
use App\Services\Channex\FakeChannexClient;
use App\Support\Auth\SocialIdentity;
use RuntimeException;

/**
 * ⚠️⚠️ THE ONLY FAKE PROVIDER THAT EXISTS, AND IT LIVES HERE ON PURPOSE.
 *
 * It is in `tests/`, is never registered by the application, and is bound only by a test into an in-process
 * container. There is no route to it, no config flag that selects it, and no environment in which the
 * application would resolve it.
 *
 * ⚠️ WHY THAT MATTERS MORE HERE THAN ANYWHERE ELSE IN THIS CODEBASE. A stub payment client that leaks into
 *    production fails to take money. A stub IDENTITY provider that leaks into production is an
 *    authentication bypass: whoever reaches it signs in as anybody. That asymmetry is why this class cannot
 *    follow the {@see FakeChannexClient} pattern, which is bound by a config default
 *    of TRUE — correct for a channel manager, catastrophic for a login.
 */
final class FakeSocialIdentityProvider implements SocialIdentityProvider
{
    public ?SocialIdentity $next = null;

    public bool $failExchange = false;

    /**
     * ⚠️ `$providerKey` DEFAULTS TO google SO EVERY EXISTING CALL IS UNCHANGED. The 30 Google tests
     *    construct this with one argument and must keep proving exactly what they proved before.
     */
    public function __construct(public bool $enabled = true, public string $providerKey = 'google') {}

    public function key(): string
    {
        return $this->providerKey;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function authorizationUrl(string $state): string
    {
        return 'https://accounts.google.test/authorize?state='.$state;
    }

    public function identityFromCode(string $code): SocialIdentity
    {
        if ($this->failExchange) {
            throw new RuntimeException('exchange failed');
        }

        return $this->next ?? throw new RuntimeException('no identity configured');
    }
}
