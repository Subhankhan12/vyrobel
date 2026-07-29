<?php

declare(strict_types=1);

namespace App\Support\Help;

/**
 * The outcome of one "Ask Vyrobel" question (Wave D · support AI · Phase 2). Immutable. Three shapes:
 *
 *  - ANSWERED           — a grounded LLM answer + citations (the real help articles it used).
 *  - GROUNDED_FALLBACK  — no LLM answer (cost cap hit / provider down), but relevant help WAS found → show that
 *                         real content directly + the relay. Honest degradation, never a fabricated answer.
 *  - ESCALATE           — nothing relevant in the KB (out-of-scope / account-specific) → straight to the relay,
 *                         with NO LLM call (the architectural grounding gate).
 *
 * On EVERY shape the surface also shows the always-present escalate-to-team button + the "verify important
 * things; message the team for account help" note — those are the UI's job, not carried here.
 *
 * @phpstan-type Citation array{id: string, question: string, answer: string, route: string, label: string, score: int}
 */
final class AskResult
{
    public const ANSWERED = 'answered';

    public const GROUNDED_FALLBACK = 'grounded_fallback';

    public const ESCALATE = 'escalate';

    /**
     * @param  list<Citation>  $citations
     */
    public function __construct(
        public readonly string $outcome,
        public readonly ?string $text,
        public readonly array $citations,
        public readonly string $reason,
    ) {}

    /**
     * @param  list<Citation>  $citations
     */
    public static function answered(string $text, array $citations): self
    {
        return new self(self::ANSWERED, $text, $citations, '');
    }

    /**
     * @param  list<Citation>  $citations
     */
    public static function fallback(array $citations, string $reason): self
    {
        return new self(self::GROUNDED_FALLBACK, null, $citations, $reason);
    }

    public static function escalate(string $reason): self
    {
        return new self(self::ESCALATE, null, [], $reason);
    }

    public function isAnswered(): bool
    {
        return $this->outcome === self::ANSWERED;
    }

    public function isEscalate(): bool
    {
        return $this->outcome === self::ESCALATE;
    }

    public function hasCitations(): bool
    {
        return $this->citations !== [];
    }

    /**
     * Primitive shape for flashing to the session / passing to a view — never flash the object itself, because
     * some session drivers (database) round-trip it back as a plain array. Blade reads these keys.
     *
     * @return array{outcome: string, text: string|null, citations: list<Citation>, reason: string}
     */
    public function toArray(): array
    {
        return [
            'outcome' => $this->outcome,
            'text' => $this->text,
            'citations' => $this->citations,
            'reason' => $this->reason,
        ];
    }
}
