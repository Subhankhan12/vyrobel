<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Plan;
use App\Models\Unit;

/**
 * THE ONE source of the billable unit count (BILLING PASS 1, 2026-08-13).
 *
 * ⚠️ WHY THIS EXISTS AS ITS OWN CLASS. A per-unit plan charges a QUANTITY, and a quantity that disagrees with
 * the org's actual units is the §N.8d false-claim shape with money attached: the customer is billed for a
 * number that is not true of them. The only way that cannot happen is for the number the engine CHARGES and the
 * number the product DISPLAYS to come from the same expression — so this is it, and both
 * {@see SubscriptionPricing} (display) and {@see Plan::quantityFor()} (charge) read it.
 *
 * ⚠️ IT IS DERIVED, NEVER STORED. There is deliberately no `subscriptions.unit_count` column: a stored copy is
 * correct only until the next unit is added, and nothing would announce the drift. Deriving costs one COUNT
 * query and cannot be wrong.
 *
 * TENANCY: `Unit` carries the BelongsToOrganization global scope, so this counts the CURRENT tenant's units —
 * the same scoping every other billing read runs under. It is not a cross-org total.
 */
class BillableUnits
{
    /**
     * The org's billable units: every ACTIVE unit on the rack.
     *
     * Inactive units are excluded because an operator who pauses a unit has taken it off sale — charging for it
     * would bill them for inventory they cannot let.
     */
    public static function count(): int
    {
        return Unit::query()->where('is_active', true)->count();
    }
}
