<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Reservation;
use App\Support\Guest\ArrivalPack;
use App\Support\Guest\GuestName;
use App\Support\I18n\LocalDate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The printable Arrival Guide for ONE owned booking — built from the SAME window-gated {@see ArrivalPack}
 * that feeds the on-screen arrival page AND the #83 pre-arrival email, so the three surfaces cannot drift.
 *
 * 🔒 #33 — the door code + wifi are physically sensitive, and a PDF is downloadable/shareable. This builder
 * NEVER re-reads the unit secrets directly; it calls {@see ArrivalPack::for} (owned booking + arrival window),
 * so a PRE-WINDOW PDF prints the honest "appears closer to arrival" state and carries NO code. The guest sees
 * their OWN stay's secrets (the right side of the perimeter); the owner never does. The guest's own name on
 * their own guide is their data (#28). No fabricated house-guide / checkout-steps / local picks.
 */
final class ArrivalPackDocument
{
    /**
     * @return array{
     *   reference: string, property: string, guestName: string, checkIn: string, checkOut: string,
     *   pack: array<string, mixed>
     * }
     */
    public static function data(Reservation $reservation, CarbonInterface $today): array
    {
        return [
            'reference' => (string) $reservation->reference,
            'property' => (string) ($reservation->property->name ?? __('php/support/pdf/arrival_pack_document.property_fallback')),
            'guestName' => GuestName::display($reservation->leadGuest),
            'checkIn' => CarbonImmutable::parse($reservation->check_in)->translatedFormat(LocalDate::pattern('l, j M Y')),
            'checkOut' => CarbonImmutable::parse($reservation->check_out)->translatedFormat(LocalDate::pattern('l, j M Y')),
            'pack' => ArrivalPack::for($reservation, $today),
        ];
    }
}
