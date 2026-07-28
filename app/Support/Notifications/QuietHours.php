<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Models\User;
use App\Notifications\FeedNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * NOTIFICATION QUIET HOURS (Wave B · GATE 6) — the ONE place the "is now inside the user's quiet window?" rule
 * lives, read by the notification channel gate ({@see FeedNotification::via}) and the Settings
 * UI so they can't disagree.
 *
 * During quiet hours, routine EMAIL notifications are held back; the in-app feed still records everything (nothing
 * is lost), and always-on security notifications ignore quiet hours entirely. The window is two "HH:MM" strings on
 * the user; an overnight window (start > end, e.g. 22:00→07:00) is handled. Interpreted in the app timezone.
 */
class QuietHours
{
    /** Whether the user has quiet hours configured (both endpoints set). */
    public static function configured(User $user): bool
    {
        return self::valid((string) $user->quiet_hours_start) && self::valid((string) $user->quiet_hours_end);
    }

    /** Whether `now` falls inside the user's quiet window. False when quiet hours are not configured. */
    public static function active(User $user, ?CarbonInterface $now = null): bool
    {
        if (! self::configured($user)) {
            return false;
        }

        $now ??= Carbon::now();
        $minutes = $now->hour * 60 + $now->minute;
        $start = self::toMinutes((string) $user->quiet_hours_start);
        $end = self::toMinutes((string) $user->quiet_hours_end);

        // Same-day window (start < end): inside when start <= now < end.
        // Overnight window (start >= end, e.g. 22:00→07:00): inside when now >= start OR now < end.
        return $start < $end
            ? ($minutes >= $start && $minutes < $end)
            : ($minutes >= $start || $minutes < $end);
    }

    /** "HH:MM" → minutes since midnight. */
    private static function toMinutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return $h * 60 + $m;
    }

    /** A strict "HH:MM" (00:00–23:59) check. */
    public static function valid(string $hhmm): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hhmm) === 1;
    }
}
