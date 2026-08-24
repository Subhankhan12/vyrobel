<?php

declare(strict_types=1);

namespace App\Support\Folio;

/**
 * ⚠️⚠️ WHY A CHECKOUT WAS OVERRIDDEN — A CLOSED, KEYED SET. NEVER A FREE-TEXT BOX.
 *
 * ⚠️ THE POINT IS COUNTABILITY. An override that is easier than taking payment becomes the default action,
 * and then the block is theatre. The only defence is being able to ask "how often was this used, and why?"
 * and get a real answer — and free text cannot be counted, so an override recorded as prose is an override
 * nobody can evaluate.
 *
 * ⚠️ THE SET COMES FROM THE CASES THAT WERE ACTUALLY MEASURED, not from imagination:
 *   · `rounding_residue`  — the €0.01 case, measured live: the guard is `> 0` and cannot tell a cent from
 *                           €500, so a residue too small to collect strands a departure.
 *   · `guest_departed`    — the 6am case, measured live: the guest is gone, and a blocked checkout means
 *                           `GenerateDepartureCleanTask` never fires and the room is never released.
 *   · `disputed_charge`   — the charge is contested and under review; the debt stands while it is resolved.
 *   · `settled_offline`   — money changed hands outside the system and has yet to be recorded here.
 *   · `other`             — ⚠️ REQUIRES A NOTE and is COUNTED SEPARATELY. It exists because refusing an
 *                           unlisted case would push agents into miscategorising, which corrupts the very
 *                           count this set exists to produce. ⚠️ A high `other` rate is itself the finding
 *                           that the set is wrong.
 */
final class CheckoutOverrideReason
{
    public const ROUNDING_RESIDUE = 'rounding_residue';

    public const GUEST_DEPARTED = 'guest_departed';

    public const DISPUTED_CHARGE = 'disputed_charge';

    public const SETTLED_OFFLINE = 'settled_offline';

    public const OTHER = 'other';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::ROUNDING_RESIDUE,
            self::GUEST_DEPARTED,
            self::DISPUTED_CHARGE,
            self::SETTLED_OFFLINE,
            self::OTHER,
        ];
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::ROUNDING_RESIDUE => 'Rounding residue — too small to collect',
            self::GUEST_DEPARTED => 'Guest has already departed',
            self::DISPUTED_CHARGE => 'Charge is disputed and under review',
            self::SETTLED_OFFLINE => 'Settled outside the system, not yet recorded',
            self::OTHER => 'Other (note required)',
        ];
    }

    public static function isValid(mixed $reason): bool
    {
        return is_string($reason) && in_array($reason, self::all(), true);
    }
}
