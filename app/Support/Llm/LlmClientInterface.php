<?php

declare(strict_types=1);

namespace App\Support\Llm;

use App\Providers\AppServiceProvider;

/**
 * The ONE LLM seam (Wave D · Ask Vyrobel · Phase 2) — nothing depends on a concrete provider. Bound in
 * {@see AppServiceProvider} to {@see FakeLlmClient} (dev/CI/demo, default) or
 * {@see AnthropicLlmClient} (production, account-gated), exactly like the Stripe/Channex client seams.
 */
interface LlmClientInterface
{
    /**
     * Complete a single grounded request. $system is the strict grounded instruction; $userContent carries the
     * owner's question + the retrieved real help content. Returns the generated text + token usage.
     *
     * @throws LlmUnavailableException on any failure (→ the caller trips the circuit breaker).
     */
    public function complete(string $system, string $userContent, int $maxTokens): LlmResponse;
}
