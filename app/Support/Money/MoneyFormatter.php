<?php

declare(strict_types=1);

namespace App\Support\Money;

/**
 * Single source of truth for money DISPLAY strings (integer minor units → e.g. "€10,370.00" / "−€…" /
 * "+€…"). Used by both the on-screen owner formatting (FormatsOwnerData) and the statement/folio PDFs,
 * so a document reconciles to the on-screen figure to the character — not just to the cent.
 */
final class MoneyFormatter
{
    public static function display(int $minor, string $currency, bool $signed = false): string
    {
        $symbol = self::symbol($currency);
        $abs = number_format(abs($minor) / 100, 2, '.', ',');

        if ($minor < 0) {
            return "−{$symbol}{$abs}";   // U+2212 minus, matching the design
        }

        return ($signed ? '+' : '')."{$symbol}{$abs}";
    }

    public static function symbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'EUR' => '€',
            'GBP' => '£',
            'USD' => '$',
            default => strtoupper($currency).' ',
        };
    }
}
