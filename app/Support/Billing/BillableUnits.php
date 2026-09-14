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
     * The org's billable units: every ACTIVE unit on a LIVE property.
     *
     * Inactive units are excluded because an operator who pauses a unit has taken it off sale — charging for it
     * would bill them for inventory they cannot let.
     *
     * ⚠️⚠️ AND SO ARE UNITS ON AN ARCHIVED PROPERTY (2026-09-01). `PropertyRemovalService::archive()` SOFT-DELETES
     * the property and deliberately never touches the unit rows — reservations, folios and statements must stay
     * queryable, and a cascade would destroy them. The consequence was that an operator archived a property and
     * kept being counted for every unit on it, with nothing on any screen saying so.
     *
     * ⚠️ WHY THIS IS A FILTER AND NOT `is_active = false` ON ARCHIVE. That flag means one specific thing — the
     * OWNER took this unit off sale (#69) — and writing it on archive would fuse two different facts into one
     * bit. It would also be unrecoverable: after an archive you could no longer tell which units the owner had
     * paused ON PURPOSE, so any future un-archive would silently put them back on sale. Filtering by the
     * property instead is one expression, mutates nothing, and needs no memory of prior state.
     *
     * ⚠️ `whereHas('property')` IS THE SOFT-DELETE TEST, VERIFIED NOT ASSUMED. `Unit::property()` is a plain
     * `belongsTo(Property::class)`, and `Property` uses `SoftDeletes`, so the relation's own global scope
     * excludes a trashed parent — measured: after an archive `$unit->property` is null and this count drops by
     * exactly that property's active units. It tests EXISTENCE only, so it cannot touch `condition_status`: an
     * out-of-service unit on a live property still bills, which is the deliberate {@see Unit::scopeBookable()}
     * divergence and is asserted separately.
     *
     * ⚠️ COST: one correlated EXISTS on the indexed `units.property_id`. This runs on a billing path — a single
     * COUNT, never a list — so the subquery is not on a hot loop.
     */
    public static function count(): int
    {
        return Unit::query()->where('is_active', true)->whereHas('property')->count();
    }
}
