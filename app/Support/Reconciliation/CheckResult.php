<?php

declare(strict_types=1);

namespace App\Support\Reconciliation;

/**
 * One check's outcome — ⚠️⚠️ THREE STATES, NEVER TWO.
 *
 * ⚠️ `unchecked` is not a kind of `agrees`. If Stripe could not be reached, nothing was compared, and
 * reporting "no drift" would be the confident-€0 shape this project has now refused twice: an answer given
 * with certainty about a question nobody asked. "0 discrepancies across 40 subscriptions" and
 * "0 discrepancies across 3, 37 unchecked" must never render the same.
 *
 * ⚠️ `examined` is the NON-VACUITY FLOOR. A check that silently stopped matching would report no findings
 * and read exactly like success; the count of what it looked at is what separates those.
 */
final class CheckResult
{
    /**
     * @param  list<array{subject: string, detail: string}>  $disagreements
     * @param  list<array{subject: string, detail: string}>  $unchecked
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $source,
        public readonly bool $needsStripe,
        public readonly int $examined,
        public readonly array $disagreements = [],
        public readonly array $unchecked = [],
    ) {}

    public function agreed(): int
    {
        return max(0, $this->examined - count($this->disagreements) - count($this->unchecked));
    }

    /** ⚠️ CLEAR means examined-and-agreed. A check that examined nothing is not clear; it is empty. */
    public function isClear(): bool
    {
        return $this->disagreements === [] && $this->unchecked === [];
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'source' => $this->source,
            'needs_stripe' => $this->needsStripe,
            'examined' => $this->examined,
            'agreed' => $this->agreed(),
            'disagreed' => count($this->disagreements),
            'unchecked' => count($this->unchecked),
            'disagreements' => $this->disagreements,
            'unchecked_reasons' => $this->unchecked,
        ];
    }
}
