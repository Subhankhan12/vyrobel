<?php

declare(strict_types=1);

namespace App\Support\Reservations;

use App\Models\AddOn;
use App\Services\FolioService;

/**
 * THE ONE definition of how a New-Reservation EXTRA is quantified (#90) — read by both the wizard (to preview a
 * per-extra line total) and the create controller (to post the REAL {@see FolioService::postAddOn}
 * line). The AddOn record carries the unit PRICE (real, org-owned, integer minor); this maps the AddOn to how
 * many units the stay implies, so the two surfaces cannot drift and the folio reconciles (#17).
 *
 * The basis is derived from the AddOn's name (there is no `code` column): a "breakfast" is per-guest-per-night,
 * a "cot" per-night, a "transfer" per-leg, everything else flat. A property configures its own AddOns; an
 * unrecognised one is simply flat × 1 (honest — never a fabricated multiplier).
 */
final class ReservationExtras
{
    public const BASIS_PER_GUEST_NIGHT = 'per_guest_night';

    public const BASIS_PER_NIGHT = 'per_night';

    public const BASIS_PER_LEG = 'per_leg';

    public const BASIS_FLAT = 'flat';

    /** The quantity basis for an AddOn, keyed off its name. */
    public static function basisFor(AddOn $addOn): string
    {
        $name = mb_strtolower((string) $addOn->name);

        return match (true) {
            str_contains($name, 'breakfast'), str_contains($name, 'half board'), str_contains($name, 'dinner') => self::BASIS_PER_GUEST_NIGHT,
            str_contains($name, 'cot'), str_contains($name, 'crib'), str_contains($name, 'parking') => self::BASIS_PER_NIGHT,
            str_contains($name, 'transfer'), str_contains($name, 'pickup'), str_contains($name, 'pick-up') => self::BASIS_PER_LEG,
            default => self::BASIS_FLAT,
        };
    }

    /** The real quantity for this extra given the stay — server-authoritative (never a client-sent quantity). */
    public static function quantityFor(AddOn $addOn, int $guests, int $nights, int $legs = 1): int
    {
        $guests = max(1, $guests);
        $nights = max(1, $nights);
        $legs = max(1, $legs);

        return match (self::basisFor($addOn)) {
            self::BASIS_PER_GUEST_NIGHT => $guests * $nights,
            self::BASIS_PER_NIGHT => $nights,
            self::BASIS_PER_LEG => $legs,
            default => 1,
        };
    }

    /** A short human basis label for the wizard ("per guest / night", etc.). */
    public static function basisLabel(AddOn $addOn): string
    {
        return match (self::basisFor($addOn)) {
            self::BASIS_PER_GUEST_NIGHT => 'per guest · night',
            self::BASIS_PER_NIGHT => 'per night',
            self::BASIS_PER_LEG => 'each way',
            default => 'one-off',
        };
    }
}
