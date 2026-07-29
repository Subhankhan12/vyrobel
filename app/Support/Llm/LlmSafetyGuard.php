<?php

declare(strict_types=1);

namespace App\Support\Llm;

use App\Payments\Stripe\StripeSafetyGuard;
use App\Providers\AppServiceProvider;

/**
 * The LLM-spend guard (Wave D · Ask Vyrobel · Phase 2) — mirrors {@see StripeSafetyGuard}.
 * A real Anthropic API call (which costs real money) must NEVER happen outside `production`. Consulted at the
 * client binding ({@see AppServiceProvider}): unless we are in production AND configured live AND a key is
 * present, the {@see FakeLlmClient} is bound — so a stray `LLM_FAKE=false` in a dev `.env` can never spend a cent.
 *
 * Default `LLM_FAKE=true` → every dev/CI/demo run uses the fake; the live flip is the founder's documented go-live.
 */
final class LlmSafetyGuard
{
    /**
     * Whether the fake client MUST be used given the environment + config. Force the fake when configured to
     * fake, OR outside production, OR no API key is present. Only `production` + `fake=false` + a key uses the
     * real client. (Unlike Stripe there is no "test-mode key" tier — any real key spends money — so the rule is
     * strictly production-only.)
     */
    public static function forceFakeOutsideProduction(string $environment, bool $configuredFake, ?string $apiKey): bool
    {
        if ($configuredFake) {
            return true;
        }

        if ($environment !== 'production') {
            return true;
        }

        return ! is_string($apiKey) || trim($apiKey) === '';
    }
}
