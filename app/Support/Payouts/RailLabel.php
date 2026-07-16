<?php

declare(strict_types=1);

namespace App\Support\Payouts;

use App\Support\Folio\FolioTotals;
use App\Support\Money\MoneyFormatter;

/**
 * The ONE payout-rail → human label map, shared by the owner Payouts screen and the owner Remittance
 * PDF (Wave 1 · Sub-gate 2). Same discipline as {@see MoneyFormatter} and
 * {@see FolioTotals}: a document and the screen it mirrors must never disagree, so
 * the mapping lives in one place rather than being re-`match`ed per surface.
 *
 * Presentation only — it reads the `payouts.rail` enum and returns a word. It has no knowledge of the
 * payout lifecycle and never touches the certified payout backend.
 *
 * HONESTY: `manual` is an offline operator-issued transfer — deliberately NOT labelled "SEPA" (the rail
 * makes no scheme claim). `stripe_connect` is the DEFERRED rail (`StripeConnectPayoutRail::send()`
 * throws); it is labelled truthfully here so an existing row can be described, never so one can be sent.
 */
final class RailLabel
{
    /**
     * Covers the real `payouts.rail` enum ('manual','stripe_connect'). An unknown rail THROWS rather
     * than falling back to a prettified column value: this label tells an owner how real money reached
     * them, so a silent "Sepa Direct" guess would be a fabricated payment method (invariant #19). A new
     * rail must be labelled here deliberately.
     *
     * @throws \InvalidArgumentException
     */
    public static function for(string $rail): string
    {
        return match ($rail) {
            'manual' => 'Bank transfer',
            'stripe_connect' => 'Stripe Connect',
            default => throw new \InvalidArgumentException("Unlabelled payout rail [{$rail}] — add it to ".self::class.' rather than rendering a guess.'),
        };
    }
}
