<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Folio;
use App\Models\Reservation;
use App\Support\Folio\FolioTotals;
use App\Support\Money\MoneyFormatter;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The guest-facing view of a booking (Phase 4b) — the ONE place a guest trip card is derived, so Trips and the
 * Reservation detail can't disagree. Status via the shared {@see ReservationStage}; money via {@see FolioTotals}
 * + {@see MoneyFormatter} (the SAME figures the operator/owner see, reconciling to the folio). Pure + read-only.
 */
class GuestTrip
{
    /**
     * @return array{id: string, reference: string, property: string, location: string, check_in: string,
     *   check_out: string, nights: int, total: string, balance_due: bool, balance_label: string, stage: string,
     *   stage_label: string, stage_tone: string, is_past: bool}
     */
    public static function card(Reservation $reservation, CarbonInterface $today): array
    {
        $stage = ReservationStage::for($reservation, $today);
        $meta = ReservationStage::meta($stage);
        $currency = (string) $reservation->currency;

        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();
        $balance = $folio !== null ? FolioTotals::for($folio)['balance_minor'] : 0;

        $checkIn = CarbonImmutable::parse($reservation->check_in);
        $checkOut = CarbonImmutable::parse($reservation->check_out);

        return [
            'id' => (string) $reservation->getKey(),
            'reference' => (string) $reservation->reference,
            'property' => (string) ($reservation->property->name ?? 'Your stay'),
            'location' => self::location($reservation),
            'check_in' => $checkIn->format('D, j M Y'),
            'check_out' => $checkOut->format('D, j M Y'),
            'nights' => max(1, $checkIn->diffInDays($checkOut)),
            'total' => MoneyFormatter::display((int) $reservation->total_minor, $currency),
            'balance_due' => $balance > 0,
            'balance_label' => $balance > 0 ? MoneyFormatter::display($balance, $currency).' due' : 'Paid in full',
            'stage' => $stage,
            'stage_label' => $meta['label'],
            'stage_tone' => $meta['tone'],
            'is_past' => in_array($stage, [ReservationStage::PAST, ReservationStage::CANCELLED], true),
        ];
    }

    /** A short "City, Country" from the property's address (a real array) — empty when none is set. */
    private static function location(Reservation $reservation): string
    {
        $address = $reservation->property->address;
        if (! is_array($address)) {
            return '';
        }

        $preferred = array_filter([(string) ($address['city'] ?? ''), (string) ($address['country'] ?? '')]);
        if ($preferred !== []) {
            return implode(', ', $preferred);
        }

        return trim(implode(', ', array_filter(array_map('strval', $address))));
    }
}
