<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\Property;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the public (unauthenticated) booking context from the `{property}` route slug and sets
 * REAL tenant context — it never bypasses the BelongsToOrganization guard. The property is looked
 * up once under an explicit super-admin context (the only place the slug→org mapping is known),
 * then {@see TenantContext} + the spatie team id are set to that org for the rest of the request.
 * The resolved property id is stashed for the controller, which re-loads it tenant-scoped — so
 * every downstream query runs scoped, and a request for property A can never read property B.
 *
 * No route-model binding (that would trip the tenant scope before context is set — the Phase 1
 * Step 9 gotcha); the slug is a plain string param.
 *
 * ⚠️⚠️ R-SUSPEND (founder, 2026-09-19) — A SUSPENDED ORGANISATION STOPS TAKING BOOKINGS, AND THIS IS WHERE
 * THAT IS ENFORCED. Until this ruling the lookup filtered `properties.status` and NEVER READ THE
 * ORGANISATION, so suspending a tenant took their back office away and LEFT THEIR SHOP OPEN — while
 * {@see ResolvePublicOrganization} already closed `/stay/{organization}` for the same org. The product
 * contradicted itself (F-48).
 *
 * ⚠️ THE CHECK LIVES HERE, NOT ON THE ROUTES. 🔬 All nine `book/{property}` routes run this middleware and
 * none bypasses it (re-measured from `route:list`, not the routes file) — and there will be a tenth route.
 * Nine separate checks is nine chances to miss one.
 *
 * ⚠️⚠️ `suspended` AND NOTHING ELSE. The column is `enum('trialing','active','suspended','cancelled')`.
 * The founder ruled on ONE of those states, so exactly one is acted on here. ⚠️ `cancelled` is deliberately
 * NOT closed by this guard even though `/stay` closes it — inferring that a cancelled account should behave
 * like a suspended one is a COMMERCIAL policy, and guessing one is the thing this project does not do. The
 * divergence is recorded in docs/AUDIT-SUMMARY.md rather than resolved here.
 *
 * ⚠️⚠️ AND THE GUEST IS NEVER TOLD WHY. A guest is a stranger to the billing relationship between Vyrobel
 * and the operator; publishing its state to the operator's own customers would damage them. The closed page
 * says online booking is unavailable and offers a real next step — it never says "suspended", "unpaid" or
 * "account".
 *
 * ⚠️ IT CLOSES NEW BOOKINGS ONLY. 🔬 None of the 58 `guest/` routes runs this middleware, so a guest holding
 * a confirmed stay reaches their portal, check-in, messages and folio exactly as before. Suspension is
 * between Vyrobel and the operator; a guest who has already paid is not the lever.
 */
class ResolvePublicProperty
{
    public const ATTRIBUTE = 'booking_property_id';

    /**
     * ⚠️ THE ONE STATE THE FOUNDER RULED ON. Not a list, deliberately: adding a second value here is a
     * commercial decision, not a refactor.
     */
    public const CLOSED_FOR_BOOKING = 'suspended';

    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('property');

        $tenant = app(TenantContext::class);
        $property = $tenant->runAsSuperAdmin(
            fn () => Property::query()->where('slug', $slug)->where('status', 'active')->first(),
        );

        if ($property === null) {
            abort(404, 'Unknown property.');
        }

        // ⚠️⚠️ R-SUSPEND — the organisation, not just the property. See the class docblock.
        $organizationStatus = $tenant->runAsSuperAdmin(
            fn (): ?string => Organization::query()->whereKey($property->organization_id)->value('status'),
        );

        if ($organizationStatus === self::CLOSED_FOR_BOOKING) {
            // ⚠️ RETURNED, NOT `abort()`ed. `abort($response)` is re-rendered by the exception handler as
            //    the GENERIC 404 — 🔬 measured: the closed-sign copy was replaced by "We couldn't find that",
            //    which is precisely the "looks broken / hotel has vanished" outcome this page exists to avoid.
            return $this->closedResponse();
        }

        $tenant->set((string) $property->organization_id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($property->organization_id);
        $request->attributes->set(self::ATTRIBUTE, $property->getKey());

        return $next($request);
    }

    /**
     * ⚠️⚠️ THE CLOSED SIGN — AND WHAT IT MUST NOT DO. Not a 500, not a blank page, and not an empty calendar
     * that a guest reads as "no availability"; each of those is worse than an honest closed door because it
     * looks like the product is broken.
     *
     * ⚠️ 404 — THE SAME ANSWER `/stay/{organization}` ALREADY GIVES for the same organisation, so the two
     * public surfaces stop contradicting each other. The STATUS withholds the bookable resource from
     * crawlers and keeps the page out of an index; the BODY stops a returning guest concluding their hotel
     * has vanished.
     *
     * ⚠️ NO NEW ERROR PAGE — this renders the SHARED `errors.layout` the 403/404/419/429/500/503 pages all
     * use, with copy of its own.
     *
     * ⚠️⚠️ AND IT NAMES NO REASON. There is no "suspended", no "unpaid", no "account", and no status value a
     * curious reader could look up.
     */
    private function closedResponse(): Response
    {
        return response()->view('errors.layout', [
            'code' => 404,
            'title' => 'Online booking isn’t available here',
            'message' => 'This property isn’t taking online bookings at the moment. If you already have a '
                .'booking, you can still manage it from the link in your confirmation email. To ask about '
                .'staying here, please contact the property directly.',
        ], 404);
    }
}
