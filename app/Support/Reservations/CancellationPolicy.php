<?php

declare(strict_types=1);

namespace App\Support\Reservations;

use App\Livewire\ReservationDetail;
use App\Mail\BookingConfirmationMail;
use App\Models\PropertyPolicy;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The ONE reading of a property's cancellation policy (anti-drift — the free-cancel date was previously derived
 * inline in {@see BookingConfirmationMail} and {@see ReservationDetail}; two `match`es for
 * one concept WILL drift). The demo policy shape (the only writer today) is `{"type":"flexible","free_until_days":N}`.
 *
 * ⚠️ The policy encodes ONLY a free-cancel deadline — NOT any post-deadline refund amount. So this helper answers
 * exactly one money question honestly: is a cancellation ON a given date WITHIN the free window (→ a full refund is
 * due)? After the deadline the policy is silent, so no percentage is invented (#39) — that stays a manual decision.
 */
final class CancellationPolicy
{
    /** The free-cancellation deadline = check-in − free_until_days, or null when the property has no such policy. */
    public static function freeUntil(Reservation $reservation): ?CarbonImmutable
    {
        return self::freeUntilForCheckIn((string) $reservation->property_id, $reservation->check_in);
    }

    /**
     * The free-cancel deadline for a property + a proposed check-in date — the ONE policy reading, so the
     * pre-booking storefront checkout (no reservation yet) and the post-booking surfaces can't drift.
     */
    public static function freeUntilForCheckIn(string $propertyId, mixed $checkIn): ?CarbonImmutable
    {
        $policy = PropertyPolicy::query()->where('property_id', $propertyId)->first();
        $cancellation = is_array($policy?->cancellation) ? $policy->cancellation : [];

        // array_key_exists (not isset/truthiness): `free_until_days: 0` is a real, meaningful value.
        if (! array_key_exists('free_until_days', $cancellation) || ! is_numeric($cancellation['free_until_days'])) {
            return null;
        }

        return CarbonImmutable::parse($checkIn)->startOfDay()->subDays((int) $cancellation['free_until_days']);
    }

    /** True when a cancellation ON `$on` qualifies for a FULL refund (on/before the free-cancel deadline). */
    public static function isRefundable(Reservation $reservation, CarbonInterface $on): bool
    {
        $freeUntil = self::freeUntil($reservation);

        return $freeUntil !== null && CarbonImmutable::parse($on)->startOfDay()->lessThanOrEqualTo($freeUntil);
    }

    /** The free-cancel deadline as a display label (e.g. "10 Sep"), or null. */
    public static function freeUntilLabel(Reservation $reservation, string $format = 'j M'): ?string
    {
        return self::freeUntil($reservation)?->format($format);
    }
}
