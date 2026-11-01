<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Organization;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Support\Guest\GuestName;
use App\Support\I18n\LocalDate;
use App\Support\Money\MoneyFormatter;
use App\Support\Owner\OwnerBookingsReader;
use App\Support\Owner\OwnerCommissionRate;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerLedgerAttribution;
use App\Support\Reservations\ReservationStage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the OWNER BOOKING CONFIRMATION PDF view data (#78) — ONE owner-scoped reservation: its facts, the
 * REAL gross→net earnings breakdown, and "Net to you". The per-booking companion to the statement-level
 * {@see OwnerBookingSummaryDocument}, on the SAME disciplines:
 *
 *   • Pure + read-only — it renders only what the reservation + the certified owner ledger actually hold.
 *   • #28 — GUEST NAME ONLY, via the shared {@see GuestName::display()} (the same name the owner Bookings page
 *     shows). NO email / phone / country ever reaches this document — a PDF an owner can keep is exactly where
 *     a leaked contact would do the most disintermediation harm.
 *   • #17 — earnings via the ONE shared {@see OwnerLedgerAttribution::breakdown()} (so the PDF reconciles to the
 *     on-screen Booking Detail to the cent), and the commission LABEL is the REAL {@see OwnerCommissionRate}
 *     (18%), never a hardcoded 15%.
 *
 * ⚠️ OWNER SCOPE IS THE CALLER'S JOB. This builder assumes the reservation has ALREADY been proven to touch one
 * of the requesting owner's own units (the controller applies the identical guard as the page); it queries the
 * owner-scoped {@see OwnerLedgerEntry} (its scope constrains the rows to the owner), but the reservation itself
 * carries no owner_id, so a caller MUST NOT hand it a reservation it hasn't owner-checked.
 *
 * @param  list<array{id: string, label: string}>  $ownedUnits
 */
final class OwnerBookingConfirmationDocument
{
    /**
     * @param  list<array{id: string, label: string}>  $ownedUnits
     * @return array<string, mixed>
     */
    public static function data(Reservation $reservation, array $ownedUnits): array
    {
        $entries = OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->get();
        $earnings = OwnerLedgerAttribution::breakdown($entries);

        $checkIn = Carbon::parse($reservation->check_in);
        $checkOut = Carbon::parse($reservation->check_out);
        $nights = (int) $checkIn->diffInDays($checkOut);
        $guestCount = max(0, (int) $reservation->adults + (int) $reservation->children);

        // Letterhead — the operator's REAL registered identity (mirrors the summary PDF). VAT only when set.
        $org = Organization::query()->whereKey($reservation->organization_id)->firstOrNew(['id' => $reservation->organization_id]);
        $addr = OperatorLetterhead::line($org);   // the ONE letterhead rule (shared)

        return [
            'operator' => OperatorLetterhead::registeredName($org),
            'operator_addr' => $addr,
            'reference' => (string) $reservation->reference,
            'status' => ReservationStage::meta(ReservationStage::for($reservation))['label'],
            // #28 — NAME ONLY (shared GuestName; anonymized → "Guest").
            'guest' => GuestName::display($reservation->leadGuest),
            'units' => collect($ownedUnits)->pluck('label')->implode(', '),
            'check_in' => $checkIn->translatedFormat(LocalDate::pattern('D j M Y')),
            'check_out' => $checkOut->translatedFormat(LocalDate::pattern('D j M Y')),
            'nights' => $nights,
            'guests' => $guestCount > 0 ? $guestCount.' '.Str::plural('guest', $guestCount) : '—',
            'source_label' => OwnerBookingsReader::sourceLabel((string) $reservation->source),
            'booked_on' => ($reservation->booked_at ?? $reservation->created_at)?->translatedFormat(LocalDate::pattern('j M Y')) ?? '—',
            'earnings' => $earnings,
            'attributed' => $entries->isNotEmpty(),
            'commission_rate' => OwnerCommissionRate::percent(app(OwnerContext::class)),
            'nightly_rate' => self::nightlyRate($entries, $reservation, $nights),
            'prepared_on' => now()->translatedFormat(LocalDate::pattern('j M Y')),
        ];
    }

    /**
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     */
    private static function nightlyRate($entries, Reservation $reservation, int $nights): ?string
    {
        if ($nights < 1) {
            return null;
        }

        $room = (int) $entries->where('entry_type', 'room_revenue')->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
        $accom = $room > 0 ? $room : (int) $reservation->total_minor;

        return $accom > 0 ? MoneyFormatter::display(intdiv($accom, $nights), (string) $reservation->currency) : null;
    }
}
