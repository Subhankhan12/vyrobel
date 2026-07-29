<?php

declare(strict_types=1);

namespace App\Support\Llm;

use App\Services\Channex\ChannexClient;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * The real Anthropic Messages API client (Wave D · Ask Vyrobel · Phase 2) — bound ONLY in production with a
 * configured key ({@see LlmSafetyGuard}). Mirrors {@see ChannexClient}: outbound via the
 * `Http` facade, a tight timeout, and any failure normalised to {@see LlmUnavailableException} so the caller's
 * circuit breaker handles it (the owner never sees a provider error). ACCOUNT-GATED — never exercised in CI.
 */
final class AnthropicLlmClient implements LlmClientInterface
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    private const API_VERSION = '2023-06-01';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function complete(string $system, string $userContent, int $maxTokens): LlmResponse
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type' => 'application/json',
            ])
                ->timeout(20)
                ->post(self::ENDPOINT, [
                    'model' => $this->model,
                    'max_tokens' => $maxTokens,
                    'system' => $system,
                    'messages' => [
                        ['role' => 'user', 'content' => $userContent],
                    ],
                ]);
        } catch (Throwable $e) {
            throw new LlmUnavailableException('Anthropic request failed: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            throw new LlmUnavailableException('Anthropic returned HTTP '.$response->status().'.');
        }

        $body = $response->json();

        $text = $body['content'][0]['text'] ?? null;

        if (! is_string($text) || trim($text) === '') {
            throw new LlmUnavailableException('Anthropic returned an empty or malformed body.');
        }

        return new LlmResponse(
            $text,
            (int) ($body['usage']['input_tokens'] ?? 0),
            (int) ($body['usage']['output_tokens'] ?? 0),
        );
    }
}
