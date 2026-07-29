<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Help\AskResult;
use App\Support\Help\HelpRetriever;
use App\Support\Llm\LlmClientInterface;
use App\Support\Llm\LlmCostLedger;
use App\Support\Llm\LlmUnavailableException;
use App\Support\Llm\Prompts;
use Illuminate\Support\Facades\Cache;

/**
 * "Ask Vyrobel" — the grounded, guard-railed support assistant orchestrator (Wave D · support AI · Phase 2).
 *
 * The honesty guarantee lives in THIS control flow, not in trusting the model:
 *   ① retrieve over the real Help KB → EMPTY ⇒ escalate deterministically, with NO LLM call (out-of-scope /
 *      account-specific questions can never reach the model, so no figure is ever fabricated);
 *   ② cost cap reached ⇒ grounded fallback (show the real help + relay) — no call;
 *   ③ circuit breaker open (provider recently failed) ⇒ grounded fallback — no call;
 *   ④ else call the LLM with the strict grounded prompt + the retrieved content; on ANY failure trip the breaker
 *      and fall back; on success record the token spend and return the answer + citations.
 *
 * Reads NO owner data (#28 trivial) — a help assistant over generic content. The only per-owner state is the
 * cost counter. Every path leaves the surface's always-present escalate button + honest note intact.
 */
final class AskVyrobelService
{
    /** Cache key + cooldown for the circuit breaker (a provider failure pauses calls briefly, then retries). */
    private const CIRCUIT_KEY = 'llm:circuit-open';

    private const CIRCUIT_COOLDOWN_SECONDS = 120;

    public function __construct(
        private readonly HelpRetriever $retriever,
        private readonly LlmClientInterface $client,
        private readonly LlmCostLedger $ledger,
    ) {}

    public function ask(?string $ownerId, string $question): AskResult
    {
        $question = trim($question);

        if ($question === '') {
            return AskResult::escalate('empty');
        }

        // ① The architectural grounding gate — no relevant help ⇒ escalate, NO LLM call.
        $articles = $this->retriever->retrieve($question);

        if ($articles === []) {
            return AskResult::escalate('no_match');
        }

        // ② Cost cap — over the per-owner/day token budget ⇒ show the real help + relay (no call).
        if ($this->ledger->capReached($ownerId)) {
            return AskResult::fallback($articles, 'cost_capped');
        }

        // ③ Circuit breaker — the provider failed recently ⇒ don't hammer it; grounded fallback.
        if (Cache::get(self::CIRCUIT_KEY) === true) {
            return AskResult::fallback($articles, 'provider_down');
        }

        // ④ The grounded call.
        try {
            $response = $this->client->complete(
                Prompts::supportSystem(),
                Prompts::supportUserContent($question, $articles),
                (int) config('services.llm.max_output_tokens', 400),
            );
        } catch (LlmUnavailableException) {
            Cache::put(self::CIRCUIT_KEY, true, self::CIRCUIT_COOLDOWN_SECONDS);

            return AskResult::fallback($articles, 'provider_down');
        }

        $this->ledger->record($ownerId, $response->totalTokens());

        return AskResult::answered($response->text, $articles);
    }
}
