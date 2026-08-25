<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use RuntimeException;

/**
 * ⚠️⚠️ A booking refused by ONE OR MORE restrictions — carrying ALL of them, never just the first.
 *
 * ⚠️ A first-failure-wins refusal is a blank cheque: the operator is shown a minimum stay, overrides it,
 * and a stop-sell they never saw is waived silently. Every breach is listed, so the operator confirms
 * against a complete picture and the audit can record the same complete picture.
 */
class RestrictionsViolationException extends RuntimeException
{
    /** @param  list<RestrictionViolationException>  $breaches */
    public function __construct(public readonly array $breaches, string $message)
    {
        parent::__construct($message);
    }

    /** @param  list<RestrictionViolationException>  $breaches */
    public static function fromBreaches(array $breaches): self
    {
        $lines = array_map(static fn (RestrictionViolationException $e): string => $e->getMessage(), $breaches);

        return new self($breaches, count($lines) === 1
            ? $lines[0]
            : 'This booking breaches '.count($lines).' restrictions: '.implode(' ', $lines));
    }

    /** @return list<array{restriction: string, date: string, value: int|null}> */
    public function asRecords(): array
    {
        return array_map(static fn (RestrictionViolationException $e): array => [
            'restriction' => $e->restriction,
            'date' => $e->date,
            'value' => $e->value,
        ], $this->breaches);
    }
}
