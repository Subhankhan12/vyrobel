<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Livewire\Operator\OperatorInbox;
use App\Models\Reservation;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * THE ONE definition of a guest's STAY HISTORY — how many times they have stayed, for how many nights, and
 * since when — shared by the Guests CRM list, the guest profile and the inbox's guest card (Wave 2). The
 * sibling of {@see GuestSpend} (what they were billed); this is how often they came.
 *
 * WHY IT EXISTS: THREE SCREENS WERE ABOUT TO DISAGREE. {@see OperatorInbox::guestCard()} already rendered
 * "N stays · guest since YYYY" — counting EVERY reservation, cancellations included, and taking "since" from
 * the earliest of those. Sub-gate 4 then locked the CRM's rule as "a cancelled booking is not a stay". Left
 * alone, the inbox would say "3 stays" and the CRM "2 stays" about the same person on the same afternoon,
 * and both would be sincere. That is precisely the drift CLAUDE.md's shared-helper rule exists to stop
 * ("two matches for one concept WILL drift and make the two surfaces disagree"), so the rule now lives here
 * once and all three read it.
 *
 * THE RULE: a STAY is a reservation that is not CANCELLED — keyed on
 * {@see ReservationStage::CANCELLED_STATUSES}, so this cannot drift from the stage badge either. A booking
 * that was cancelled or no-showed never happened; counting it would tell an operator this guest has stayed
 * more often than they have, and would date "guest since" from a visit that did not occur.
 *
 * Nights are half-open `[check_in, check_out)` — the same arithmetic as the folio's room nights and the
 * reservations list, so a "3 nights" here is the 3 nights the guest is billed for.
 *
 * Pure + read-only: it takes reservations the caller already has and computes over them in PHP. No query,
 * no write, no second opinion.
 */
final class GuestHistory
{
    /**
     * Stays · nights · first-stay year for one guest, plus the inbox's ready-made one-line stat.
     *
     * Returns zeros and NULLs for a guest who has never stayed — the callers render "—" / omit the line
     * rather than claiming a history that does not exist.
     *
     * @param  iterable<Reservation>  $reservations  the guest's reservations (cancelled ones included — they
     *                                               are excluded HERE, so every caller excludes them the same way)
     * @return array{stays: int, nights: int, since_year: ?string, stat: ?string}
     */
    public static function for(iterable $reservations): array
    {
        $stays = 0;
        $nights = 0;
        $since = null;

        foreach ($reservations as $reservation) {
            if (in_array((string) $reservation->status, ReservationStage::CANCELLED_STATUSES, true)) {
                continue;   // never happened — not a stay, and not a "guest since" date
            }

            $checkIn = CarbonImmutable::parse($reservation->check_in)->startOfDay();
            $checkOut = CarbonImmutable::parse($reservation->check_out)->startOfDay();

            $stays++;
            // Half-open [check_in, check_out) — matches the folio's room nights.
            $nights += (int) $checkIn->diffInDays($checkOut);

            if ($since === null || $checkIn->lessThan($since)) {
                $since = $checkIn;
            }
        }

        return [
            'stays' => $stays,
            'nights' => $nights,
            'since_year' => $since?->format('Y'),
            'stat' => self::stat($stays, $since?->format('Y')),
        ];
    }

    /**
     * The inbox guest card's one-liner — "3 stays · guest since 2023".
     *
     * Kept here rather than in the view so the STRING the inbox has always shown is generated from the same
     * count the CRM shows. Null when the guest has never stayed: the card omits the line entirely rather
     * than rendering "0 stays", which reads like a data error next to a guest who is clearly talking to us.
     */
    private static function stat(int $stays, ?string $sinceYear): ?string
    {
        if ($stays === 0) {
            return null;
        }

        return $stays.' '.Str::plural('stay', $stays).($sinceYear !== null ? ' · guest since '.$sinceYear : '');
    }
}
