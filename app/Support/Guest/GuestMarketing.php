<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Guest;

/**
 * The ONE marketing-consent gate (#87). A MARKETING email may reach a guest only when they have genuinely
 * opted in — `marketing_consent` true, not anonymized (erasure withdraws consent), and a real address to send
 * to. Every marketing send path funnels through {@see canReceive()}, so consent-off genuinely stops the send
 * (mutation-proven), exactly like the notification-preference gate governs operator/owner email.
 *
 * ⚠️ TRANSACTIONAL email is NOT marketing and does NOT pass through here — a booking/refund confirmation is a
 * service message the guest is owed regardless of marketing consent. Only genuine marketing (the winback /
 * seasonal-offer path, and a bulk message an operator flags as marketing) is gated.
 */
final class GuestMarketing
{
    /** Whether this guest may receive a MARKETING email right now. */
    public static function canReceive(Guest $guest): bool
    {
        return (bool) $guest->marketing_consent
            && ! $guest->isAnonymized()
            && is_string($guest->email) && trim($guest->email) !== '';
    }
}
