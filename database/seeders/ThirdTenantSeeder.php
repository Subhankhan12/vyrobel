<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Organization;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Subscription;
use App\Models\Unit;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\TenantProvisioningService;
use App\Support\Demo\DemoDataSafetyGuard;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * ⚠️⚠️ THE THIRD TENANT — the founder's ruling, 2026-09-19 (F-61). IT EXISTS TO BE WRITTEN TO.
 *
 * ⚠️⚠️ THE PROBLEM IT SOLVES, STATED PLAINLY: **no human could drive a write through the back office in any
 * organisation.** Tenant one is the untouchable money baseline — browsable, never writable. Tenant two is
 * CORRECTLY paywalled: it is seeded with a 14-day trial, and seeding at the money gate's reference date
 * expires that trial, so every one of its `/app/*` routes redirects to `/app/billing` (invariant #41 doing
 * its job — `KNOWN-LIMITS.md` **L8**). Between them, the core of the product had become undrivable.
 *
 * ⚠️⚠️ TENANT TWO AND TENANT THREE ARE NOT REDUNDANT AND NEITHER REPLACES THE OTHER:
 *   · **Tenant two exists to PROVE THE BILLING REFUSAL and must stay paywalled.**
 *   · **Tenant three exists to BE WALKED and must stay entitled.**
 *
 * ── ⚠️⚠️ NO PINNED FIGURE, ANYWHERE, MAY REFERENCE THIS ORGANISATION ─────────────────────────────────────
 *
 * ⚠️⚠️ **THE MOMENT A TEST ASSERTS ITS COUNTS, IT STOPS BEING DISPOSABLE AND BECOMES A FIXTURE SOMEBODY HAS
 * TO PRESERVE — AND IT WAS BUILT TO BE DESTROYED BY EVERY `migrate:fresh --seed`.** Walk it, write to it,
 * break it, and let the next reseed take it away. If you find yourself wanting to pin one of its numbers,
 * that is the signal you are using it as a baseline: use tenant one, or measure by hand.
 * ⚠️ The same sentence is in `docs/KNOWN-LIMITS.md` L9 and in the F-61 register row, because a rule recorded
 * in one place is a rule that goes stale in the other two.
 *
 * ── ⚠️ WHY `active` AND NOT A LONG TRIAL — MEASURED, NOT ASSUMED ─────────────────────────────────────────
 *
 * 🔬 `SubscriptionStatus::hasFullAccess()` is `onTrial() || active()`. `onTrial()` is `trialing` AND not
 * lapsed — **date-dependent, and that dependency is exactly the fuse that paywalled tenant two.**
 * `active()` is `status === 'active'` and **checks no date at all**; 🔬 `current_period_end` gates nothing
 * anywhere in `app/` — `OperatorBilling` only formats it for display. So an `active` organisation is
 * STRUCTURALLY INCAPABLE of expiring into tenant two's trap, whereas any trial length is just a later fuse.
 *
 * ⚠️ A PERPETUAL TRIALIST IS NOT A CUSTOMER. Journeys run as one would miss every behaviour that keys on the
 * real paying state, which is the state this tenant exists to represent.
 *
 * ⚠️ AND IT IS NOT NEW GROUND: 🔬 `vyrobel-demo` ALREADY carries `subscriptions.status = 'active'` with
 * `stripe_subscription_id = NULL`. This follows the primary demo organisation rather than inventing a shape.
 *
 * ── ⚠️ ZERO STRIPE ──────────────────────────────────────────────────────────────────────────────────────
 *
 * ⚠️ No live key, no Stripe customer, no Connect account, no gateway flip. `stripe_subscription_id` and
 * `stripe_customer_id` stay **NULL**, and `StripeSafetyGuard` is untouched. The entitlement is a seeded row
 * in a development database, which is what invariant #41 forbids the RUNTIME subscribe action from faking —
 * not what it forbids a flagged demo seeder from creating in `local`/`testing`.
 *
 * ⚠️ IT CARRIES ITS OWN `DemoDataSafetyGuard` CALL. A `--class=` run never reaches `DatabaseSeeder`, so
 * inheritance is not assumed — it is re-asserted here.
 */
class ThirdTenantSeeder extends Seeder
{
    /**
     * ⚠️ DERIVED BY `TenantProvisioningService` FROM THE NAME, NOT SET HERE. The idempotency check keys on
     * this constant, so a mismatch would make every reseed create another organisation — asserted in run().
     */
    public const ORG_SLUG = 'harbourpoint-demo-walkthrough';

    public const CURRENCY = 'EUR';

    public const FOUNDER_EMAIL = 'demo-harbour@harbourpoint-demo.test';

    public const GUEST_EMAIL = 'demo.harbour.guest@harbourpoint-demo.test';

    /** ⚠️ A price that cannot be confused with tenant one's (€100.00) or tenant two's (£90.00). */
    private const NIGHTLY_MINOR = 12300;

    public function run(): void
    {
        // ⚠️⚠️ DIRECTLY, NOT VIA THE PARENT — a `--class=` run skips DatabaseSeeder entirely.
        DemoDataSafetyGuard::ensurePermitted((string) app()->environment(), 'the third demo organisation');

        if (Organization::query()->withoutGlobalScopes()->where('slug', self::ORG_SLUG)->exists()) {
            return;
        }

        $result = app(TenantProvisioningService::class)->provision(
            organization: [
                'name' => 'Harbourpoint Demo — Walkthrough',
                'currency' => self::CURRENCY,
                'country' => 'PT',
            ],
            founder: [
                'name' => 'Tomas Ferreira',
                'email' => self::FOUNDER_EMAIL,
                'password' => 'password',
            ],
            property: [
                'name' => 'Harbourpoint Walkthrough',
                'type' => 'hotel',
            ],
        );

        /** @var Organization $org */
        $org = $result['organization'];

        // ⚠️ ADMIN 01 · Phase 1 — a DEMO organisation is Vyrobel's own, never a customer: excluded from every
        //    platform statistic. Set here, where it is created, so a fresh seed can never count it.
        $org->forceFill(['is_internal' => true])->save();

        if ((string) $org->slug !== self::ORG_SLUG) {
            throw new \RuntimeException(sprintf(
                'ThirdTenantSeeder::ORG_SLUG is "%s" but provisioning derived "%s" from the organisation '
                .'name. The idempotency check keys on that constant, so leaving them out of step would make '
                .'every re-seed create another organisation. Update the constant to match the name.',
                self::ORG_SLUG,
                $org->slug,
            ));
        }

        /** @var Property $property */
        $property = $result['property'];
        /** @var User $founder */
        $founder = $result['user'];

        app(TenantContext::class)->forOrganization($org->id, function () use ($org, $property, $founder): void {
            $this->entitle($org);
            $this->makeSellable($property);

            unset($founder);
        });
    }

    /**
     * ⚠️ Provisioning creates a 14-day TRIAL — the same one a real self-signup gets, and the same one that
     * paywalled tenant two. This turns it into the state a PAYING customer occupies.
     */
    private function entitle(Organization $org): void
    {
        $subscription = Subscription::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->firstOrFail();

        $subscription->forceFill([
            'status' => 'active',

            // ⚠️ NULLED DELIBERATELY. A leftover trial end on an `active` row is a date nobody reads that
            //    looks exactly like a fuse to the next person who does.
            'trial_ends_at' => null,

            // ⚠️⚠️ DERIVED FROM SEED TIME, NEVER AN ABSOLUTE CALENDAR DATE — the rule tenant two's expiry
            //    bought. 🔬 Under `VY_SEED_DATE` the seeder freezes Carbon, so "now" IS the reference date
            //    and this moves with it. ⚠️ It is DISPLAY ONLY: `active()` checks no date, so entitlement
            //    does not depend on this value — which is why this tenant cannot expire at all.
            'current_period_end' => CarbonImmutable::now()->addYear(),

            // ⚠️ ZERO STRIPE. Same shape as `vyrobel-demo`.
            'stripe_subscription_id' => null,
            'stripe_customer_id' => null,
        ])->save();
    }

    /** The minimum that makes an operator write journey possible: something to sell, and someone to sell it to. */
    private function makeSellable(Property $property): void
    {
        $roomType = RoomType::create([
            'property_id' => $property->id,
            'name' => 'Harbour Twin',
            'code' => 'HP-TWN',
            'kind' => 'room',
            'std_occupancy' => 2,
            'max_adults' => 2,
        ]);

        collect(['H201', 'H202', 'H203'])->each(fn (string $code) => Unit::create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'code' => $code,
            'unit_kind' => 'room',
            'max_occupancy' => 2,
        ]));

        $ratePlan = RatePlan::create([
            'property_id' => $property->id,
            'room_type_id' => $roomType->id,
            'name' => 'Flexible',
            'code' => 'HP-FLEX',
            'currency' => self::CURRENCY,
            'is_refundable' => true,
            'is_active' => true,
        ]);

        $roomType->forceFill(['default_rate_plan_id' => $ratePlan->id])->save();

        // ⚠️ A 180-night window from the app's OWN today, so this tenant is sellable whenever the demo runs.
        $start = CarbonImmutable::today();

        for ($i = 0; $i < 180; $i++) {
            RateCalendar::create([
                'rate_plan_id' => $ratePlan->id,
                'date' => $start->addDays($i)->toDateString(),
                'price_minor' => self::NIGHTLY_MINOR,
                'currency' => self::CURRENCY,
            ]);
        }

        app(AvailabilityService::class)->recompute(
            (string) $roomType->id,
            $start->toDateString(),
            $start->addDays(180)->toDateString(),
        );

        // ⚠️ SOMEBODY TO BOOK, AND NO BOOKINGS. This tenant ships with ZERO reservations deliberately: the
        //    first one is meant to be written BY A HUMAN DRIVING THE PRODUCT, which is the entire point of it.
        Guest::create([
            'first_name' => 'Ines',
            'last_name' => 'Moreira',
            'email' => self::GUEST_EMAIL,
            'phone' => '+351 21 000 0003',
            'nationality' => 'PT',
        ]);
    }
}
