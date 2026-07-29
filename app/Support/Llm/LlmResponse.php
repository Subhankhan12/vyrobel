<?php

declare(strict_types=1);

namespace App\Support\Llm;

/**
 * The result of one {@see LlmClientInterface::complete()} call (Wave D · Ask Vyrobel · Phase 2) — the generated
 * text plus its token usage, so the cost ledger can record real spend. Immutable value object.
 */
final class LlmResponse
{
    public function __construct(
        public readonly string $text,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
    ) {}

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }
}
