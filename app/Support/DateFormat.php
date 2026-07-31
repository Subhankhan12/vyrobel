<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * THE ONE mapping of a user's stored `date_format` preference (#96) → a PHP format, so the Profile (and, applied
 * incrementally, other surfaces) render dates the way the operator asked. A workspace-wide retrofit is intentionally
 * incremental — this helper is the shared seam every new surface reads, never a per-screen re-derivation.
 */
final class DateFormat
{
    /** Map the stored token to a PHP date format. Unknown → the sensible default (DD/MM/YYYY). */
    public static function phpFormat(?string $token): string
    {
        return match ($token) {
            'MM/DD/YYYY' => 'm/d/Y',
            default => 'd/m/Y',
        };
    }

    /** Format a date for a specific user's preference. Null date → ''. */
    public static function forUser(?User $user, ?DateTimeInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        return Carbon::instance($date)->format(self::phpFormat($user?->date_format));
    }
}
