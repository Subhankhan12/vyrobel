<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * A property's real check-in / check-out TIME, rendered for display (audit 2026-08-07 · GATE 6).
 *
 * ⚠️ THE OWNER BOOKING DETAIL WAS HARDCODING "3:00 PM" — on the page and in the timeline — while
 * `properties.check_in_time` holds the real value. It is right for the demo properties (both 15:00) and
 * silently wrong for any property that opens at a different hour: a fabricated fact that happens to
 * match, which is the kind that survives review (#39).
 *
 * ⚠️ AND THE TIMELINE'S FORMAT STRING WAS BROKEN. `format('D j M · 3:00 PM')` ran the literal through
 * Carbon, where `P` is the UTC offset and `M` is the month:
 *
 *     'D j M · 3:00 PM'  →  "Sat 5 Sep · 3:00 +00:00Sep"
 *
 * Passing a real time through a real format removes both problems at once — there is no literal left to
 * escape. An unset column returns NULL so the caller can OMIT the line rather than invent an hour.
 */
final class StayTime
{
    /**
     * A stored `HH:MM:SS` (or `HH:MM`) time as a display string, or NULL when unset/unparseable.
     *
     * @param  string  $format  a Carbon time format — 'g:i A' (3:00 PM) or 'H:i' (15:00).
     */
    public static function display(mixed $time, string $format = 'g:i A'): ?string
    {
        if (! is_string($time) && ! $time instanceof \DateTimeInterface) {
            return null;
        }

        $raw = $time instanceof \DateTimeInterface ? $time->format('H:i:s') : trim($time);
        if ($raw === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('H:i:s', str_pad($raw, 8, ':00'))->format($format);
        } catch (\Throwable) {
            return null;
        }
    }

    /** The same, prefixed ("from 3:00 PM") — or NULL, so an unset time renders nothing at all. */
    public static function labelled(mixed $time, string $prefix = 'from ', string $format = 'g:i A'): ?string
    {
        $display = self::display($time, $format);

        return $display === null ? null : $prefix.$display;
    }
}
