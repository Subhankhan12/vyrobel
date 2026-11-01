<?php

declare(strict_types=1);

namespace App\Support\Reservations;

use App\Models\Reservation;
use App\Support\I18n\LocalDate;
use Carbon\CarbonImmutable;

/**
 * ONE booking's place in time, in words (2026-10-05) — read by the Guests list's "Stay" column and the Inbox's New message picker,
 * so the two can never say different things about the same booking.
 *
 * ⚠️⚠️ Said from THAT booking's own status and dates. "Left …" only after a check-out; an arrival due today, one overdue, and dates
 * that passed with nobody checked in are not departures — the Guests review's HIGH: they read "Left <its check-out>", for the first
 * two a date still ahead; and the Inbox said "In-house" of a guest arriving today, not yet checked in. Callers pass a booking that
 * is not cancelled (a cancelled booking is not a stay).
 */
final class StayLabel
{
    public static function for(Reservation $reservation, CarbonImmutable $today, string $dateFormat = 'j M Y'): string
    {
        $own = ReservationStage::for($reservation, $today);   // THIS booking's stage, from the one resolver the badges read
        $checkIn = CarbonImmutable::parse($reservation->check_in)->startOfDay();
        $day = $checkIn->translatedFormat(LocalDate::pattern($dateFormat));

        return match (true) {
            $own === ReservationStage::IN_HOUSE => __('php/support/reservations/stay_label.in_house'),
            (string) $reservation->status === 'checked_out' => __('php/support/reservations/stay_label.left', ['date' => CarbonImmutable::parse($reservation->check_out)->translatedFormat(LocalDate::pattern($dateFormat))]),
            $own === ReservationStage::TENTATIVE => __('php/support/reservations/stay_label.tentative', ['date' => $day]),   // the badge's word for enquiry and tentative
            $own === ReservationStage::UPCOMING => __('php/support/reservations/stay_label.arrives', ['date' => $day]),
            $checkIn->isSameDay($today) => __('php/support/reservations/stay_label.arrives_today'),
            default => __('php/support/reservations/stay_label.was_due', ['date' => $day]),   // confirmed, its check-in came, nobody checked it in — overdue, or its dates passed
        };
    }
}
