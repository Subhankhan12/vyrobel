<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\Unit;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The arrival pack for ONE booking (Phase 4b · Sub-gate 4) — directions, check-in/out times, and the sensitive
 * access secrets (door code + wifi) that live on the assigned physical {@see Unit}.
 *
 * 🔒 TWO guards decide whether the door code is ever returned, and the CALLER supplies an OWNED booking (resolved
 * through the authed guest's own relations — cross-guest is a 404 before we get here):
 *   1. OWNED — a foreign booking never reaches this resolver (the controller resolves through the guest guard).
 *   2. TIMING — the code/wifi are withheld until the stay's arrival window opens ({@see LEAD_DAYS} before check-in
 *      through check-out) and after a cancellation. A door code exposed months early, or to a cancelled booking,
 *      is a physical-security leak. Before the window we return null + `released_from` so the screen can say WHEN,
 *      never fabricate a code.
 *
 * Directions come from the property's REAL address via {@see directions()} — the SAME source the Phase-3
 * pre-arrival email reads, so the two surfaces can't drift.
 */
final class ArrivalPack
{
    /** Days before check-in that the access secrets are released (kept small — the code is physically sensitive). */
    public const LEAD_DAYS = 2;

    /**
     * @return array{
     *   directions: ?string, check_in_time: ?string, check_out_time: ?string,
     *   released: bool, released_from: string, has_code: bool,
     *   door_code: ?string, wifi_ssid: ?string, wifi_password: ?string, arrival_notes: ?string
     * }
     */
    public static function for(Reservation $reservation, CarbonInterface $today): array
    {
        $property = $reservation->property;
        $unit = $reservation->reservationUnits->first()?->unit;

        $checkIn = CarbonImmutable::parse($reservation->check_in)->startOfDay();
        $checkOut = CarbonImmutable::parse($reservation->check_out)->startOfDay();
        $opensOn = $checkIn->subDays(self::LEAD_DAYS);
        $todayDate = CarbonImmutable::parse($today)->startOfDay();

        $cancelled = in_array((string) $reservation->status, ReservationStage::CANCELLED_STATUSES, true);
        $released = ! $cancelled
            && $todayDate->greaterThanOrEqualTo($opensOn)
            && $todayDate->lessThanOrEqualTo($checkOut);

        $code = $unit instanceof Unit ? self::clean($unit->door_code) : null;

        return [
            'directions' => self::directions($property),
            'check_in_time' => self::time($property?->check_in_time),
            'check_out_time' => self::time($property?->check_out_time),
            'released' => $released,
            'released_from' => $opensOn->format('D, j M'),
            'has_code' => $code !== null,
            // Sensitive fields are gated on the arrival window — withheld (null) before it opens.
            'door_code' => $released ? $code : null,
            'wifi_ssid' => $released && $unit instanceof Unit ? self::clean($unit->wifi_ssid) : null,
            'wifi_password' => $released && $unit instanceof Unit ? self::clean($unit->wifi_password) : null,
            'arrival_notes' => $released && $unit instanceof Unit ? self::clean($unit->arrival_notes) : null,
        ];
    }

    /** The property's real address as a single directions line, or null — SHARED with PreArrivalMail (no drift). */
    public static function directions(?Property $property): ?string
    {
        $address = is_array($property?->address) ? $property->address : [];
        $line = trim(implode(', ', array_filter(array_map('strval', $address))));

        return $line !== '' ? $line : null;
    }

    private static function time(mixed $value): ?string
    {
        $t = self::clean(is_string($value) ? $value : null);

        return $t !== null ? substr($t, 0, 5) : null;   // 'HH:MM' from a time/'HH:MM:SS'
    }

    private static function clean(mixed $value): ?string
    {
        $s = is_string($value) ? trim($value) : '';

        return $s !== '' ? $s : null;
    }
}
