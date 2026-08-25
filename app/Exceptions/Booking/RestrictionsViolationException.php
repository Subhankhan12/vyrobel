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

    /**
     * ⚠️ The restrictions changed between the refusal and the resubmit, so the operator would have waived a
     * set they never read. Refuse again, showing the NEW list rather than proceeding on a stale one.
     *
     * @param  list<array{restriction: string, date: string, value: int|null}>  $current
     */
    public static function changedSince(array $current): self
    {
        $breaches = array_map(
            static fn (array $b): RestrictionViolationException => new RestrictionViolationException(
                (string) $b['restriction'], (string) $b['date'], $b['value'], ''
            ),
            $current,
        );

        return new self($breaches, 'The restrictions on these dates changed while you were reviewing them — '
            .'check the list again before overriding.');
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
