<?php

declare(strict_types=1);

namespace App\Support\I18n;

/**
 * Which of the four ordinal words (`st`, `nd`, `rd`, `th`) a number takes (I18N-01). English: 1st, 2nd, 3rd, 4th, 11th–13th.
 * Georgian has its own rule — 1 is "N-ლი", every number below 20 and every multiple of 20 up to 100 (and of 100) takes the
 * "მე-N" PREFIX, anything else ends "N-ე" — so it maps onto the same four keys as: `st` = N-ლი, `nd` = მე-N, `th` = N-ე.
 * A language's file writes the three it needs; the choice of WHICH is made here, in one place.
 */
final class Ordinal
{
    /** @return 'st'|'nd'|'rd'|'th' */
    public static function form(int $n, ?string $locale = null): string
    {
        if (($locale ?? (string) app()->getLocale()) === 'ka') {
            return match (true) {
                $n === 1 => 'st',
                $n < 20 || ($n <= 100 && $n % 20 === 0) || $n % 100 === 0 => 'nd',
                default => 'th',
            };
        }

        return match (true) {
            $n % 100 >= 11 && $n % 100 <= 13 => 'th',
            $n % 10 === 1 => 'st',
            $n % 10 === 2 => 'nd',
            $n % 10 === 3 => 'rd',
            default => 'th',
        };
    }
}
