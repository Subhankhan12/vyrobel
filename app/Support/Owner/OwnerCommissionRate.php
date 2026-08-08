<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\ManagementAgreement;
use App\Support\Money\MoneyFormatter;

/**
 * The owner's CURRENT commission rate — the ONE shared read of the REAL {@see ManagementAgreement.commission_pct}
 * (18% in the demo), so every surface that shows the rate (Reports' gross-to-net, the Settings billing card)
 * quotes the SAME real figure and can never drift onto the wireframes' hardcoded "15%".
 *
 * ManagementAgreement carries NO OwnerScope, so the owner boundary is applied EXPLICITLY here (whereIn the
 * context's owner ids). An inactive context, an empty owner set, or a genuinely ambiguous set (different rates
 * across the owner's agreements) returns null — the caller shows the figure without a rate label rather than
 * guessing. Nothing is fabricated (#39); integer-clean percentage.
 */
final class OwnerCommissionRate
{
    /** The single effective rate as a trimmed percent string ("18"), or null when none/ambiguous. */
    /**
     * The ONE way a commission percentage is written (wireframe-parity WAVE 3).
     *
     * `management_agreements.commission_pct` is a DB decimal, so casting it to string yields "18.00". The
     * Owner Agreement page had TWO private formatters — one printing "18.00%" and one printing "18%" — so a
     * single page showed the same legally-meaningful rate written two ways. Same drift rule as
     * {@see MoneyFormatter}, applied to a percentage.
     *
     * Returns the bare number ("18"); callers append the % so a sentence can phrase it its own way.
     */
    public static function trim(float|int|string|null $pct): ?string
    {
        if ($pct === null || $pct === '') {
            return null;
        }

        return rtrim(rtrim(number_format((float) $pct, 2), '0'), '.');
    }

    /** The same value ready to print — "18%", or null when no rate is set. */
    public static function display(float|int|string|null $pct): ?string
    {
        $trimmed = self::trim($pct);

        return $trimmed === null ? null : $trimmed.'%';
    }

    public static function percent(OwnerContext $context): ?string
    {
        if (! $context->active() || $context->ownerIds() === []) {
            return null;
        }

        $rates = ManagementAgreement::query()
            ->whereIn('owner_id', $context->ownerIds())
            ->effectiveOn(now()->toDateString())
            ->pluck('commission_pct')
            ->map(fn ($p): string => self::trim($p))
            ->unique()
            ->values();

        return $rates->count() === 1 ? (string) $rates->first() : null;
    }
}
