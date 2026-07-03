<?php

declare(strict_types=1);

namespace App\Support\Money;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable money value object — convention #3. Money is ALWAYS integer minor units
 * (e.g. cents) plus an ISO-4217 currency. Never a float. Arithmetic across mismatched
 * currencies throws rather than silently corrupting folios/payouts/commissions.
 */
final class Money implements JsonSerializable, Stringable
{
    public function __construct(
        public readonly int $amountMinor,
        public readonly string $currency,
    ) {
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException("Currency must be a 3-letter ISO-4217 code, got [{$currency}].");
        }
    }

    public static function of(int $amountMinor, string $currency): self
    {
        return new self($amountMinor, strtoupper($currency));
    }

    public static function zero(string $currency): self
    {
        return new self(0, strtoupper($currency));
    }

    public function amountMinor(): int
    {
        return $this->amountMinor;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function isZero(): bool
    {
        return $this->amountMinor === 0;
    }

    public function isNegative(): bool
    {
        return $this->amountMinor < 0;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinor + $other->amountMinor, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinor - $other->amountMinor, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amountMinor * $factor, $this->currency);
    }

    /**
     * A percentage of this amount, rounded half-up to the nearest minor unit. For an EXACT split
     * where the parts must sum back to the whole (e.g. commission + owner-net == room revenue), use
     * {@see allocate()} instead — this returns a single rounded portion with no complement guarantee.
     */
    public function percentage(int|float|string $percent): self
    {
        $portion = (int) round($this->amountMinor * ((float) $percent) / 100.0);

        return new self($portion, $this->currency);
    }

    /**
     * Split this amount into N parts proportional to non-negative integer weights, using
     * largest-remainder distribution so the parts ALWAYS sum back to the original EXACTLY — no minor
     * unit is created or lost (convention #3: money is never floated, and cents never leak). Handles
     * negative totals (the sign is preserved and the leftover distributed on the magnitude).
     *
     * @param  array<int, int>  $weights  non-negative integer weights, at least one positive
     * @return array<int, self> one Money per weight, index-aligned to $weights
     */
    public function allocate(array $weights): array
    {
        if ($weights === []) {
            throw new InvalidArgumentException('Money::allocate needs at least one weight.');
        }

        $total = 0;
        foreach ($weights as $weight) {
            if ($weight < 0) {
                throw new InvalidArgumentException('Money::allocate weights must be non-negative.');
            }
            $total += $weight;
        }

        if ($total === 0) {
            throw new InvalidArgumentException('Money::allocate needs at least one positive weight.');
        }

        $sign = $this->amountMinor < 0 ? -1 : 1;
        $magnitude = abs($this->amountMinor);

        // Floor share per weight; track the fractional remainders to hand out the leftover units.
        $shares = [];
        $remainders = [];
        $distributed = 0;
        foreach ($weights as $i => $weight) {
            $numerator = $magnitude * $weight;
            $shares[$i] = intdiv($numerator, $total);
            $remainders[$i] = $numerator % $total;
            $distributed += $shares[$i];
        }

        // Largest-remainder: give the remaining units to the largest fractional remainders first,
        // breaking ties by lowest index so the distribution is deterministic.
        $leftover = $magnitude - $distributed;
        $order = array_keys($remainders);
        usort($order, fn (int $a, int $b): int => $remainders[$b] <=> $remainders[$a] ?: $a <=> $b);
        for ($k = 0; $k < $leftover; $k++) {
            $shares[$order[$k]]++;
        }

        $result = [];
        foreach ($weights as $i => $weight) {
            $result[$i] = new self($sign * $shares[$i], $this->currency);
        }

        return $result;
    }

    public function equals(self $other): bool
    {
        return $this->amountMinor === $other->amountMinor
            && $this->currency === $other->currency;
    }

    /**
     * Decimal-string representation for display only (never for arithmetic).
     * Assumes 2 minor-unit digits; per-currency exponents can be layered in later.
     */
    public function toDecimalString(): string
    {
        $negative = $this->amountMinor < 0;
        $abs = abs($this->amountMinor);
        $major = intdiv($abs, 100);
        $minor = str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '')."{$major}.{$minor}";
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot operate on Money of different currencies [{$this->currency}] and [{$other->currency}]."
            );
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'amount_minor' => $this->amountMinor,
            'currency' => $this->currency,
        ];
    }

    public function __toString(): string
    {
        return "{$this->toDecimalString()} {$this->currency}";
    }
}
