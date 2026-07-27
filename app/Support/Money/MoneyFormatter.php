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

    /**
     * A COMPACT display of a real figure for tight chart/ring labels — €7,352.00 → "€7.4k", €6,000 → "€6k",
     * €920 → "€920". Presentation only: it abbreviates the SAME real minor amount {@see display()} shows in
     * full elsewhere (never a different or invented number). Thousands use one decimal, trailing ".0" trimmed;
     * millions use "m". The U+2212 minus / optional "+" match {@see display()}.
     */
    public static function compact(int $minor, string $currency, bool $signed = false): string
    {
        $symbol = self::symbol($currency);
        $sign = $minor < 0 ? '−' : ($signed ? '+' : '');
        $units = abs($minor) / 100;

        if ($units >= 1_000_000) {
            $body = rtrim(rtrim(number_format($units / 1_000_000, 1, '.', ''), '0'), '.').'m';
        } elseif ($units >= 1_000) {
            $body = rtrim(rtrim(number_format($units / 1_000, 1, '.', ''), '0'), '.').'k';
        } else {
            // Sub-thousand: whole euros (no cents) to stay tight, e.g. "€920".
            $body = number_format($units, 0, '.', ',');
        }

        return "{$sign}{$symbol}{$body}";
    }
}
