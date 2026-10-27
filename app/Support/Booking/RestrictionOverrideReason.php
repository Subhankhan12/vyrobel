<?php

declare(strict_types=1);

namespace App\Support\Booking;

/**
 * ⚠️⚠️ WHY A BOOKING OVERRODE A RESTRICTION — A CLOSED, KEYED SET. NEVER FREE TEXT.
 *
 * ⚠️ THE HONEST MEASURE OF ENFORCEMENT IS THE OVERRIDE RATE. If most bookings override, the RESTRICTIONS
 * are wrong rather than the bookings — and that is only visible if the reasons can be counted. Free text
 * cannot be counted, so an override recorded as prose is an override nobody can evaluate.
 *
 * ⚠️ `other` REQUIRES A NOTE and is COUNTED SEPARATELY. It exists because refusing an unlisted case pushes
 * agents into miscategorising, which corrupts the very count this set produces — so a high `other` rate is
 * itself the finding that the set is wrong.
 */
final class RestrictionOverrideReason
{
    public const REGULAR_GUEST = 'regular_guest';

    public const FILLS_A_GAP = 'fills_a_gap';

    public const RATE_NOT_UPDATED = 'rate_not_updated';

    public const GROUP_OR_CONTRACT = 'group_or_contract';

    public const OTHER = 'other';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::REGULAR_GUEST, self::FILLS_A_GAP, self::RATE_NOT_UPDATED, self::GROUP_OR_CONTRACT, self::OTHER];
    }

    /**
     * Every reason => its label in the reader's language, keyed by the reason CODE (translated at call time).
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::REGULAR_GUEST => __('php/support/booking/restriction_override_reason.regular_guest'),
            self::FILLS_A_GAP => __('php/support/booking/restriction_override_reason.fills_a_gap'),
            self::RATE_NOT_UPDATED => __('php/support/booking/restriction_override_reason.rate_not_updated'),
            self::GROUP_OR_CONTRACT => __('php/support/booking/restriction_override_reason.group_or_contract'),
            self::OTHER => __('php/support/booking/restriction_override_reason.other'),
        ];
    }

    public static function isValid(mixed $reason): bool
    {
        return is_string($reason) && in_array($reason, self::all(), true);
    }
}
