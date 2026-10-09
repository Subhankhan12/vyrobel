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

    /**
     * The same zones for `<x-select>` (2026-10-09, the profile's new select): city first ("Buenos Aires, Argentina"), grouped
     * by region, the offset as the hint, a `clock` (the select shows the live time there) and the words a person types —
     * the identifier, the city, and the offset with an ASCII minus too, so "-3" finds a UTC−3 zone.
     *
     * ⚠️ A SAVED ZONE THE LIST LACKS IS KEPT (`$keep`): the profile offered five zones, so a person in Baku saw — and could
     *    save — Lisbon. An option list must never be the thing that changes a stored value.
     *
     * @return list<array{value: string, label: string, hint: string, group: string, clock: string, keywords: string}>
     */
    public static function choices(?string $keep = null, ?DateTimeImmutable $at = null): array
    {
        $at ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $ids = DateTimeZone::listIdentifiers();

        if ($keep !== null && $keep !== '' && ! in_array($keep, $ids, true)) {
            $ids[] = $keep;
        }

        $choices = [];
        foreach ($ids as $id) {
            try {
                $hint = self::offsetLabel((new DateTimeZone($id))->getOffset($at));
            } catch (\Throwable) {
                continue;   // a stored value that is not a zone at all: nothing to offer
            }

            $parts = explode('/', $id);
            $city = str_replace('_', ' ', (string) end($parts));
            $label = count($parts) > 2 ? $city.', '.str_replace('_', ' ', $parts[1]) : $city;

            $choices[] = [
                'value' => $id,
                'label' => $label,
                'hint' => $hint,
                'group' => count($parts) > 1 ? str_replace('_', ' ', $parts[0]) : 'UTC',
                'clock' => $id,
                'keywords' => mb_strtolower($label.' '.$id.' '.str_replace('_', ' ', $id).' '.$hint.' '.str_replace('−', '-', $hint)),
            ];
        }

        usort($choices, fn (array $a, array $b): int => [$a['group'], $a['label']] <=> [$b['group'], $b['label']]);

        return $choices;
    }

    /** "UTC+4", "UTC−3:30", "UTC+0" — a true minus sign, and minutes only when there are some. */
    public static function offsetLabel(int $seconds): string
    {
        $abs = abs($seconds);
        $minutes = intdiv($abs % 3600, 60);

        return 'UTC'.($seconds < 0 ? '−' : '+').intdiv($abs, 3600).($minutes > 0 ? ':'.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : '');
    }
}
