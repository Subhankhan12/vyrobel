<?php

declare(strict_types=1);

namespace App\Support\Llm;

use App\Payments\Stripe\FakeStripeClient;

/**
 * The deterministic LLM test double (Wave D · Ask Vyrobel · Phase 2) — no network, no cost. Bound everywhere
 * outside production (default `LLM_FAKE=true`), exactly like {@see FakeStripeClient}.
 *
 * - In TESTS: scripted via {@see willReturn()} / {@see willThrow()} to prove each behaviour (grounded answer,
 *   provider failure → circuit breaker).
 * - In DEV/DEMO: {@see groundedFromContent()} reflects back ONLY the retrieved help content it was handed —
 *   never open knowledge — so the demo is honest (it degrades to the real help, never invents a conversation).
 */
final class FakeLlmClient implements LlmClientInterface
{
    private ?string $forcedText = null;

    private bool $shouldThrow = false;

    public int $calls = 0;

    /** Script a fixed response (tests). */
    public function willReturn(string $text): static
    {
        $this->forcedText = $text;

        return $this;
    }

    /** Script a provider failure (tests) → the caller's circuit breaker. */
    public function willThrow(): static
    {
        $this->shouldThrow = true;

        return $this;
    }

    public function complete(string $system, string $userContent, int $maxTokens): LlmResponse
    {
        $this->calls++;

        if ($this->shouldThrow) {
            throw new LlmUnavailableException('FakeLlmClient scripted failure.');
        }

        $text = $this->forcedText ?? $this->groundedFromContent($userContent);

        // Deterministic, plausible token counts (no randomness — reproducible in tests/ledger).
        $inputTokens = (int) ceil(mb_strlen($userContent) / 4);
        $outputTokens = (int) ceil(mb_strlen($text) / 4);

        return new LlmResponse($text, $inputTokens, $outputTokens);
    }

    /**
     * An honest dev/demo answer built ONLY from the provided help content — extracts the first help article's
     * answer from the delimited block and reflects it back. Never open knowledge. Falls back to an honest defer.
     */
    private function groundedFromContent(string $userContent): string
    {
        if (preg_match('/\[1\][^\n]*\n(.+?)(\n\n|\z)/s', $userContent, $m) === 1) {
            $answer = trim($m[1]);

            if ($answer !== '') {
                return $answer.' If you need anything specific to your account, message your management team.';
            }
        }

        return "I don't have that in the help material — message your management team and they'll help.";
    }
}
