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
     *   stage_label: string, stage_tone: string, is_past: bool, gradient: string, countdown: string,
     *   date_range: string, year: string, country: string}
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
            // Pixel-diff (guest Phase-4 G1): a DETERMINISTIC warm gradient placeholder for the card's image band —
            // the property has no hero photo, so a real, stable colour band (NOT a fabricated photo). Compact
            // date range + a real relative countdown/label + the year (for the Trips year filter) + the country.
            'gradient' => self::gradient((string) $reservation->getKey()),
            'countdown' => self::countdown($stage, $checkIn, $checkOut, $today),
            'date_range' => self::dateRange($checkIn, $checkOut),
            'year' => $checkIn->format('Y'),
            'country' => is_array($reservation->property->address ?? null) ? (string) ($reservation->property->address['country'] ?? '') : '',
        ];
    }

    /** A deterministic warm gradient (from the reservation key) — a stable placeholder band, never a fake photo. */
    private static function gradient(string $key): string
    {
        $palette = [
            'linear-gradient(140deg,#E8703A,#C2410C)',
            'linear-gradient(140deg,#F0A57A,#D9662E)',
            'linear-gradient(140deg,#D99A3D,#B4791C)',
            'linear-gradient(140deg,#E88A5A,#C25410)',
            'linear-gradient(140deg,#DDA15E,#BC6C25)',
        ];

        return $palette[hexdec(substr(md5($key), 0, 4)) % count($palette)];
    }

    /** A real relative label for the image band's clock pill — never a fabricated one. */
    private static function countdown(string $stage, CarbonImmutable $checkIn, CarbonImmutable $checkOut, CarbonInterface $today): string
    {
        $today = CarbonImmutable::parse($today)->startOfDay();

        return match ($stage) {
            ReservationStage::CANCELLED => 'Cancelled',
            ReservationStage::PAST => 'Stayed '.$checkIn->format('M Y'),
            default => match (true) {
                $today->betweenIncluded($checkIn->startOfDay(), $checkOut->startOfDay()->subDay()) => 'In your stay',
                $checkIn->isSameDay($today) => 'Check-in today',
                $checkIn->startOfDay()->equalTo($today->addDay()) => 'Check-in tomorrow',
                default => 'In '.$today->diffInDays($checkIn->startOfDay()).' days',
            },
        };
    }

    /** A compact "5–8 Sep 2026" / "29 Dec 2026 – 2 Jan 2027" range. */
    private static function dateRange(CarbonImmutable $checkIn, CarbonImmutable $checkOut): string
    {
        if ($checkIn->isSameMonth($checkOut)) {
            return $checkIn->format('j').'–'.$checkOut->format('j M Y');
        }
        if ($checkIn->isSameYear($checkOut)) {
            return $checkIn->format('j M').' – '.$checkOut->format('j M Y');
        }

        return $checkIn->format('j M Y').' – '.$checkOut->format('j M Y');
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
