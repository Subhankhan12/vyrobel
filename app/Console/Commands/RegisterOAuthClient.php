<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OAuthClientRegistrar;
use Illuminate\Console\Command;

/**
 * Platform-admin mechanism to register a third-party OAuth client (Wave D · Passport · Phase 1). Run on the
 * server by an operator/platform admin — NOT open self-serve. Prints the client id (+ the secret ONCE for a
 * confidential client; a public client gets none and must use PKCE).
 */
class RegisterOAuthClient extends Command
{
    protected $signature = 'oauth:register-client
        {name : A human name for the third-party app (shown on the consent screen)}
        {redirect : The redirect URI (the exact allow-listed callback)}
        {--public : Register a PUBLIC client (SPA/mobile — no secret, PKCE required)}';

    protected $description = 'Register a third-party OAuth authorization-code client (platform-admin).';

    public function handle(OAuthClientRegistrar $registrar): int
    {
        $confidential = ! $this->option('public');
        $client = $registrar->register((string) $this->argument('name'), [(string) $this->argument('redirect')], $confidential);

        $this->info('OAuth client registered.');
        $this->line('  Client ID:     '.$client->getKey());
        $this->line('  Type:          '.($confidential ? 'confidential (has a secret)' : 'public (PKCE, no secret)'));
        if ($confidential && $client->plainSecret !== null) {
            $this->line('  Client secret: '.$client->plainSecret.'  ← shown ONCE; store it securely.');
        }
        $this->line('  Redirect URI:  '.(string) $this->argument('redirect'));

        return self::SUCCESS;
    }
}
