<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Guest;

/**
 * The one place a guest's DISPLAY name is derived (Phase 3 · Sub-gate 4) — so every guest-facing email greets the
 * guest the same way and a null/anonymized guest never renders "null null" or an empty greeting. First + last
 * (trimmed); falls back to the first name, then a neutral "Guest". Never touches the encrypted email.
 */
class GuestName
{
    public static function display(?Guest $guest): string
    {
        if ($guest === null) {
            return 'Guest';
        }

        $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name));

        return $name !== '' ? $name : 'Guest';
    }

    /**
     * The PUBLIC review byline (Phase 5) — first name + last initial only (e.g. "Ada L."), never the full surname
     * or any contact detail (#28). An anonymized guest (name stripped) renders the neutral "A guest".
     */
    public static function reviewByline(?Guest $guest): string
    {
        $first = trim((string) $guest?->first_name);
        if ($first === '') {
            return 'A guest';
        }

        $last = trim((string) $guest?->last_name);

        return $last !== '' ? $first.' '.mb_substr($last, 0, 1).'.' : $first;
    }
}
