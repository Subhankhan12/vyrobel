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
            'first_name' => (string) ($guest->first_name ?: 'there'),
            'unit' => $unit !== null ? (string) ($unit->name ?? $unit->code) : 'your unit',
            'reference' => $reservation !== null ? (string) $reservation->reference : '',
            'nights' => (string) $nights,
            'balance' => $reservation !== null
                ? MoneyFormatter::display((int) ($reservation->balance_minor ?? 0), (string) ($reservation->currency ?? 'EUR'))
                : '',
        ];

        return EmailTemplateResolver::for(app(TenantContext::class)->id(), $key, $vars)->message();
    }
}
