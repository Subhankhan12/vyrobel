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

    /**
     * A spreadsheet-safe amount: a BARE DECIMAL in MAJOR units — no symbol, no thousands grouping, and a
     * '.' decimal separator regardless of locale.
     *
     * ⚠️⚠️ WHY NOT {@see display()} IN A CSV. `€1,234.00` is text to every spreadsheet, so a money column
     * cannot be summed. ⚠️ AND A LOCALE-FORMATTED DECIMAL IS NOT THE FIX: `1.234,00` and `1,234.00` are each
     * correct somewhere and wrong elsewhere, and a CSV carries no way to say which it means — so the same
     * file totals differently depending on which country opens it.
     *
     * ⚠️ THE CURRENCY IS NOT IN THIS STRING. It belongs in its own column: the amount alone is not the money,
     * and a symbol glued to a number is exactly what makes the number unusable. Callers MUST emit an ISO
     * 4217 code alongside.
     *
     * ⚠️ MAJOR UNITS, NOT MINOR. Minor units are exact and never round, but a column of `44280` reads as
     * cents to a human and invites a 100× error in whatever they build on top. `442.80` is what a
     * spreadsheet expects. It is exact here too: the value is integer minor units divided by 100 and
     * printed to exactly two places, so no float rounding can move it.
     */
    public static function csvAmount(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
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
