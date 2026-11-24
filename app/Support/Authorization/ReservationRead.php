<?php

declare(strict_types=1);

namespace App\Support\Authorization;

use App\Http\Middleware\EnsureCanReadReservations;
use App\Models\User;

/**
 * WHO MAY READ A RESERVATION (VY-14, audit of 2026-11-24, reproduced) — the ONE rule, read by the route gate
 * ({@see EnsureCanReadReservations}) and by every link that leads to the two screens, so a screen never offers a page that refuses.
 *
 * The reservations list and a reservation's own page carried NO permission check: a Housekeeping user opened both and read the
 * guest's name and the amount. They now need one of:
 *
 *   · `reservations.view` — the reservation roles (Front Desk, Reservation Agent, the managers, Revenue, Guest Support);
 *   · `folios.view`       — because the folio (charges, payments, refunds) is worked ON the reservation's page. The Accountant holds
 *     the money permissions and not `reservations.view`; refusing them here would take away the only screen their role exists for.
 *
 * Housekeeping, Maintenance and Marketing hold neither and are refused.
 *
 * ⚠️ THIS NARROWS A FOUNDER RULING (2026-09-22: the back-office READ screens are open to every back-office role) FOR THESE TWO SCREENS
 *    ONLY, on his word of 2026-11-24 ("Fix all of the followings"). The Calendar, the Front Desk and the dashboard stay open to every
 *    role under that ruling — they show a name and a stay, and their links to these two screens follow this rule.
 */
final class ReservationRead
{
    /** Either permission is enough — see the class note. */
    public const PERMISSIONS = ['reservations.view', 'folios.view'];

    public static function allowed(?User $user): bool
    {
        return $user !== null && $user->canAny(self::PERMISSIONS);
    }
}
