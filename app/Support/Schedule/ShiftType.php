<?php

declare(strict_types=1);

namespace App\Support\Schedule;

/**
 * THE fixed shift-pattern set (Phase 7d · Team Schedule) — MODERATE rostering: a small, fixed set of shift
 * patterns with default hours, NOT arbitrary time entry. ONE definition (labels + hours), read by the grid,
 * the assign form and the schedule PDF, so they can never disagree.
 *
 * `off` is the ABSENCE state (no working shift that day — stored as a row or simply no row). `leave` is NOT a
 * shift type — it is a separate time-off dimension (Phase 7d · SG2) that OVERLAYS the grid; a shift and leave
 * on the same day are a real computed conflict.
 */
final class ShiftType
{
    public const EARLY = 'early';

    public const DAY = 'day';

    public const LATE = 'late';

    public const OFF = 'off';

    /** Every storable shift type (an off day is a real, explicit choice as well as the default absence). */
    public const ALL = [self::EARLY, self::DAY, self::LATE, self::OFF];

    /** The WORKING shifts (an actual on-shift assignment), in day order. */
    public const WORKING = [self::EARLY, self::DAY, self::LATE];

    public static function isValid(string $type): bool
    {
        return in_array($type, self::ALL, true);
    }

    public static function isWorking(string $type): bool
    {
        return in_array($type, self::WORKING, true);
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::EARLY => 'Early',
            self::DAY => 'Day',
            self::LATE => 'Late',
            self::OFF => 'Off',
            default => ucfirst($type),
        };
    }

    /** Default hours for a shift pattern (display only — the moderate model does not store per-shift times). */
    public static function hours(string $type): string
    {
        return match ($type) {
            self::EARLY => '07:00–15:00',
            self::DAY => '09:00–17:00',
            self::LATE => '15:00–23:00',
            default => '—',
        };
    }
}
