<?php

declare(strict_types=1);

namespace App\Support\Time;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Every IANA time zone as a choice a person can find: the identifier (the value the forms post and the `timezone` rule
 * validates), a readable label ("America/New York"), and its offset from UTC NOW ("UTC−4", "UTC+5:30") — so "Baku" and
 * "UTC+4" both find it. The offset is read at the moment of rendering, so it follows daylight saving.
 */
final class TimezoneOptions
{
    /** @return list<array{value: string, label: string, hint: string}> */
    public static function all(): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return array_map(fn (string $tz): array => [
            'value' => $tz,
            'label' => str_replace('_', ' ', $tz),
            'hint' => self::offsetLabel((new DateTimeZone($tz))->getOffset($now)),
        ], DateTimeZone::listIdentifiers());
    }

    /** "UTC+4", "UTC−3:30", "UTC+0" — a true minus sign, and minutes only when there are some. */
    public static function offsetLabel(int $seconds): string
    {
        $abs = abs($seconds);
        $minutes = intdiv($abs % 3600, 60);

        return 'UTC'.($seconds < 0 ? '−' : '+').intdiv($abs, 3600).($minutes > 0 ? ':'.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : '');
    }
}
