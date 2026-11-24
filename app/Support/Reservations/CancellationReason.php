<?php

declare(strict_types=1);

namespace App\Support\Reservations;

use App\Console\Commands\MarkNoShows;
use App\Support\Booking\UnpaidBookingRelease;

/**
 * The words for a cancellation reason the SYSTEM writes — ONE map (the I18N rule: a sentence the system stores is stored as a code and
 * read in the reader's language). A reason a PERSON typed is their own words and comes back exactly as written; only the codes below are
 * translated. Read by the reservation page, its timeline and the guest's cancellation email.
 */
final class CancellationReason
{
    /** VY-23 — a storefront booking whose card payment was never completed, released by {@see UnpaidBookingRelease}. */
    public const PAYMENT_NOT_COMPLETED = 'system:payment_not_completed';

    /** VY-26 — a booking marked a no-show by the property's own automatic rule ({@see MarkNoShows}). */
    public const AUTO_NO_SHOW = 'system:auto_no_show';

    public static function label(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        return match ($reason) {
            '' => null,
            self::PAYMENT_NOT_COMPLETED => (string) __('php/support/reservations/cancellation_reason.payment_not_completed'),
            self::AUTO_NO_SHOW => (string) __('php/support/reservations/cancellation_reason.auto_no_show'),
            default => $reason,
        };
    }
}
