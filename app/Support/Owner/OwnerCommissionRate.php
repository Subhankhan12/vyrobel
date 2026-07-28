<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Models\ManagementAgreement;

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
    public static function percent(OwnerContext $context): ?string
    {
        if (! $context->active() || $context->ownerIds() === []) {
            return null;
        }

        $rates = ManagementAgreement::query()
            ->whereIn('owner_id', $context->ownerIds())
            ->effectiveOn(now()->toDateString())
            ->pluck('commission_pct')
            ->map(fn ($p): string => rtrim(rtrim(number_format((float) $p, 2), '0'), '.'))
            ->unique()
            ->values();

        return $rates->count() === 1 ? (string) $rates->first() : null;
    }
}
