<?php

declare(strict_types=1);

namespace App\Support\Inbox;

use App\Models\Guest;
use App\Models\Reservation;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Money\MoneyFormatter;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;

/**
 * The ONE place a #83 message template is merged with a guest's real merge-vars (first name / unit /
 * reference / nights / balance) for the operator inbox composer. Shared by {@see InboxActionController::start()}
 * (which seeds the sent body) AND {@see OperatorInbox} (which pre-fills the editable New-message textarea so
 * the operator REVIEWS + EDITS the merged text before sending) — one definition, so the previewed body and
 * the sent body can never drift. Never emits door codes / secrets (#33) — it merges only these safe vars.
 */
final class MessageTemplate
{
    /** The template keys offered in the composer (must match the EmailTemplateResolver + the route validation). */
    public const KEYS = ['pre-arrival', 'payment-reminder', 'review-request'];

    /**
     * The guest's MESSAGING-CONTEXT reservation — the ONE the composer references (meta + merged template) AND
     * the one {@see InboxActionController::start()} links the thread to, so those surfaces can never disagree
     * (the drift an adversarial review caught: the composer merged the guest's OLDEST reservation while start()
     * linked the newest). Relevance: a current/upcoming stay (soonest) beats a recent checkout. Tenant-scoped.
     */
    public static function contextReservation(Guest $guest): ?Reservation
    {
        $reservations = Reservation::query()
            ->where('lead_guest_id', $guest->getKey())
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->with('reservationUnits.unit')
            ->get();

        return self::mostRelevant($reservations, CarbonImmutable::today());
    }

    /**
     * Pick the most-relevant reservation from a set by the ONE rule both the composer and start() use: an
     * active/future stay (check_out ≥ today), soonest check_in first (so in-house then next-upcoming), beats a
     * past stay; among past stays, the most-recent checkout wins. Pure — no query, so the composer can apply it
     * over its already-loaded rows and start() over a fresh query and both land on the same reservation.
     *
     * @param  iterable<int, Reservation>  $reservations
     */
    public static function mostRelevant(iterable $reservations, CarbonImmutable $today): ?Reservation
    {
        $best = null;
        foreach ($reservations as $r) {
            if ($best === null || self::moreRelevant($r, $best, $today)) {
                $best = $r;
            }
        }

        return $best;
    }

    private static function moreRelevant(Reservation $a, Reservation $b, CarbonImmutable $today): bool
    {
        $t = $today->toDateString();
        $aOut = CarbonImmutable::parse($a->check_out)->toDateString();
        $bOut = CarbonImmutable::parse($b->check_out)->toDateString();
        $aActive = $aOut >= $t;
        $bActive = $bOut >= $t;

        if ($aActive !== $bActive) {
            return $aActive;   // an active/future stay always beats a past one
        }
        if ($aActive) {
            return CarbonImmutable::parse($a->check_in)->toDateString() < CarbonImmutable::parse($b->check_in)->toDateString();   // soonest first
        }

        return $aOut > $bOut;   // both past → the most-recent checkout wins
    }

    /**
     * The merged message body for one template + guest (+ their reservation, for the unit/reference/balance
     * vars). Returns '' for an unknown key. Tenant-scoped via the active TenantContext.
     */
    public static function merged(string $key, Guest $guest, ?Reservation $reservation): string
    {
        if (! in_array($key, self::KEYS, true)) {
            return '';
        }

        $unit = $reservation?->reservationUnits?->first()?->unit;
        $nights = $reservation !== null
            ? CarbonImmutable::parse($reservation->check_in)->diffInDays(CarbonImmutable::parse($reservation->check_out))
            : 0;

        $vars = [
            'first_name' => (string) ($guest->first_name ?: __('php/support/inbox/message_template.first_name_fallback')),   // "Hi there"
            'unit' => $unit !== null ? (string) ($unit->name ?? $unit->code) : __('php/support/inbox/message_template.unit_fallback'),
            'reference' => $reservation !== null ? (string) $reservation->reference : '',
            'nights' => (string) $nights,
            'balance' => $reservation !== null
                ? MoneyFormatter::display((int) ($reservation->balance_minor ?? 0), (string) ($reservation->currency ?? 'EUR'))
                : '',
        ];

        return EmailTemplateResolver::for(app(TenantContext::class)->id(), $key, $vars)->message();
    }
}
