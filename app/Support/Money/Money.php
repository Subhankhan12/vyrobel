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
