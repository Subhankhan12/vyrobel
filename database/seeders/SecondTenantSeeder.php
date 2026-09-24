<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\MaintenanceTicket;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Owner;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\FolioService;
use App\Services\TenantProvisioningService;
use App\Support\Demo\DemoDataSafetyGuard;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * ⚠️⚠️ THE SECOND TENANT — the founder's ruling, 2026-09-14 (FIX 17).
 *
 * With ONE organisation, every "tenant-scoped → cross-org 404" claim in this codebase was READ AND NEVER
 * EXERCISED — through fifteen audit phases and sixteen fix passes. That was the single largest untested
 * property in the product. This seeder creates a second organisation so those claims can be DRIVEN.
 *
 * ⚠️ PROVISIONED, NOT HAND-BUILT. The org, its role catalogue, the founder, the membership, the
 * `Property Owner` assignment, the first property and the free trial all come from
 * {@see TenantProvisioningService::provision()} — the same transaction a real self-signup runs. A hand-built
 * fixture would not be a second tenant; it would be rows that resemble one, and every isolation test written
 * against it would be testing the fixture rather than the product.
 *
 * ⚠️⚠️ GBP, DELIBERATELY. Tenant one is EUR. A different currency makes a cross-tenant money leak visible at
 * a glance — a £ figure on a Vyrobel Demo screen is wrong on sight, with no arithmetic — and it exercises the
 * multi-currency paths a single-currency demo never could. If a surface assumes EUR, that is a real finding
 * and this makes it surface instead of hiding.
 *
 * ⚠️ TENANT ONE IS NEVER TOUCHED. Every row here belongs to the new organisation. The money gate
 * (`ops/money-as-seeded.sh`) is SCOPED to `vyrobel-demo` for exactly this reason, so its six pinned figures
 * keep their values and their meaning.
 *
 * ⚠️ THE GUARD IS CALLED HERE, DIRECTLY — not inherited from {@see DatabaseSeeder}. `db:seed --class=` reaches
 * a seeder WITHOUT running the parent, which is precisely how the original staging bypass was demonstrated.
 * This seeder creates accounts whose password is published in this repository; more accounts is more ways in.
 */
class SecondTenantSeeder extends Seeder
{
    /**
     * ⚠️ THE SLUG IS DERIVED FROM THE NAME BY `TenantProvisioningService`, NOT SET HERE — and this constant
     * being wrong is not cosmetic: the idempotency check below keys on it, so a mismatch means a re-run
     * creates a SECOND copy of the second tenant. 🔬 That is exactly what happened on the first build of this
     * seeder (`northgate-demo` assumed, `northgate-demo-city-rooms` produced), caught by measuring the row
     * rather than trusting the constant. It is now ASSERTED after provisioning, so a renamed organisation
     * fails loudly here instead of silently duplicating on the next `migrate:fresh --seed`.
     */
    public const ORG_SLUG = 'northgate-demo-city-rooms';

    public const CURRENCY = 'GBP';

    /** The founder / operator account for tenant two. */
    public const FOUNDER_EMAIL = 'demo-northgate@northgate-demo.test';

    public const OWNER_EMAIL = 'demo-northgate-owner@northgate-demo.test';

    public const GUEST_CLAIMED_EMAIL = 'demo.northgate.guest@northgate-demo.test';

    public const GUEST_UNCLAIMED_EMAIL = 'demo.northgate.walker@northgate-demo.test';

    public function run(): void
    {
        // ⚠️⚠️ DIRECTLY, NOT VIA THE PARENT. See the class docblock: `--class=` skips DatabaseSeeder entirely.
        DemoDataSafetyGuard::ensurePermitted((string) app()->environment(), 'the second demo organisation');

        // Idempotent: re-running must not create a third organisation.
        $existing = Organization::query()->withoutGlobalScopes()->where('slug', self::ORG_SLUG)->first();
        if ($existing !== null) {
            return;
        }

        $result = app(TenantProvisioningService::class)->provision(
            organization: [
                'name' => 'Northgate Demo — City Rooms',
                'currency' => self::CURRENCY,
                'country' => 'GB',
            ],
            founder: [
                'name' => 'Nadia Okafor',
                'email' => self::FOUNDER_EMAIL,
                'password' => 'password',
            ],
            property: [
                'name' => 'Northgate City Rooms',
                'type' => 'hotel',
            ],
        );

        /** @var Organization $org */
        $org = $result['organization'];

        // ⚠️ ADMIN 01 · Phase 1 — a DEMO organisation is Vyrobel's own, never a customer: excluded from every
        //    platform statistic. Set here, where it is created, so a fresh seed can never count it.
        $org->forceFill(['is_internal' => true])->save();

        // ⚠️ THE ASSERTION THAT KEEPS THE RE-RUN IDEMPOTENT. See ORG_SLUG.
        if ((string) $org->slug !== self::ORG_SLUG) {
            throw new \RuntimeException(sprintf(
                'SecondTenantSeeder::ORG_SLUG is "%s" but provisioning derived "%s" from the organisation '
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
            $this->buildDepth($org, $property, $founder);
        });
    }

    /**
     * ⚠️ WHAT PROVISIONING DOES NOT CREATE. It builds the org, roles, founder, membership, property and
     * trial — and nothing sellable. Everything below is what makes tenant two worth reaching: the question
     * every later pass asks is "can tenant A reach tenant B's X", so B needs an X worth reaching.
     *
     * ⚠️ Built through the CERTIFIED SERVICES (`AssignmentService`, `FolioService`), never raw inserts, so
     * these rows are the same shape the product makes at runtime.
     */
    private function buildDepth(Organization $org, Property $property, User $founder): void
    {
        $roomType = RoomType::create([
            'property_id' => $property->id,
            'name' => 'City Double',
            'code' => 'NG-DBL',
            'kind' => 'room',
            'std_occupancy' => 2,
            'max_adults' => 2,
        ]);

        $units = collect(['N101', 'N102', 'N103'])->map(fn (string $code): Unit => Unit::create([
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
            'code' => 'NG-FLEX',
            'currency' => self::CURRENCY,
            'is_refundable' => true,
            'is_active' => true,
        ]);

        $roomType->forceFill(['default_rate_plan_id' => $ratePlan->id])->save();

        // ⚠️ A 120-night window from the app's own "today", so tenant two is sellable whenever the demo runs.
        //    £90.00 a night — a figure that cannot be confused with any tenant-one price.
        $start = CarbonImmutable::today();
        for ($i = 0; $i < 120; $i++) {
            $date = $start->addDays($i);
            RateCalendar::create([
                'rate_plan_id' => $ratePlan->id,
                'date' => $date->toDateString(),
                'price_minor' => 9000,
                'currency' => self::CURRENCY,
            ]);
        }

        app(AvailabilityService::class)->recompute(
            (string) $roomType->id,
            $start->toDateString(),
            $start->addDays(120)->toDateString(),
        );

        // A CLAIMED guest (can sign in) and an UNCLAIMED one (exists, never claimed) — the two shapes the
        // guest realm distinguishes.
        $claimed = Guest::create([
            'first_name' => 'Priya', 'last_name' => 'Raman',
            'email' => self::GUEST_CLAIMED_EMAIL,
            'phone' => '+44 20 7946 0001',
            'nationality' => 'GB',
        ]);

        // ⚠️ CLAIMED = a password on the GUEST row. The guest realm is a separate guard reading `guests`,
        //    not `users` — the same shape DemoSeeder uses for `demo.traveller`.
        $claimed->forceFill(['password' => 'password', 'email_verified_at' => now()])->save();

        // …and an UNCLAIMED guest: exists, has a booking history shape, cannot sign in.
        Guest::create([
            'first_name' => 'Tomas', 'last_name' => 'Weber',
            'email' => self::GUEST_UNCLAIMED_EMAIL,
            'nationality' => 'DE',
        ]);

        // A REAL stay, through AssignmentService — the ONE inventory write path.
        $checkIn = $start->addDays(10)->toDateString();
        $checkOut = $start->addDays(13)->toDateString();
        $roomMinor = (int) RateCalendar::query()
            ->where('rate_plan_id', $ratePlan->id)
            ->whereDate('date', '>=', $checkIn)
            ->whereDate('date', '<', $checkOut)
            ->sum('price_minor');

        $reservation = app(AssignmentService::class)->createBooking(
            [
                'property_id' => $property->id, 'currency' => self::CURRENCY,
                'check_in' => $checkIn, 'check_out' => $checkOut,
                'adults' => 2, 'lead_guest_id' => $claimed->id, 'source' => 'direct',
            ],
            [[
                'room_type_id' => $roomType->id, 'unit_id' => $units->first()->id,
                'rate_plan_id' => $ratePlan->id,
                'check_in' => $checkIn, 'check_out' => $checkOut, 'adults' => 2,
                'price_minor' => $roomMinor, 'currency' => self::CURRENCY,
            ]],
        );

        $folios = app(FolioService::class);
        $folio = $folios->openFolio($reservation);
        $folios->postCharge($folio, 'room', "Room stay {$checkIn} → {$checkOut}", Money::of($roomMinor, self::CURRENCY));
        $folio->refresh();
        $folios->recordPayment($folio, Money::of($folio->balance_minor, self::CURRENCY), 'tok_demo_card');

        // An OWNER with a unit — so owner-scoped surfaces have something to scope.
        $ownerUser = User::create([
            'name' => 'Idris Bello',
            'email' => self::OWNER_EMAIL,
            'password' => 'password',
            'status' => 'active',
            'locale' => 'en',
        ]);

        $ownerParty = Owner::create([
            'type' => 'company',
            'name' => 'Bello Holdings',
            'email' => self::OWNER_EMAIL,
            'payout_currency' => self::CURRENCY,
            'user_id' => $ownerUser->id,
            'status' => 'active',
        ]);

        $units->last()->forceFill(['owner_id' => $ownerParty->id])->save();

        // ⚠️ THE OWNER NEEDS THE ROLE, NOT JUST THE PARTY ROW. 🔬 Caught by walking the realm: without
        //    `External Owner` the account exists, owns a unit, and cannot open `/owner` at all — a tenant
        //    that cannot be used is not a second tenant. The role is team-scoped, so it is assigned inside
        //    this organisation's team context exactly as TenantProvisioningService does for the founder.
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);
        $ownerUser->assignRole('External Owner');

        Membership::create([
            'organization_id' => $org->id,
            'user_id' => $ownerUser->id,
            'status' => 'active',
            'invited_at' => now(),
        ]);

        // Housekeeping + maintenance — two more objects a cross-tenant probe can aim at.
        HousekeepingTask::create([
            'property_id' => $property->id,
            'unit_id' => $units->first()->id,
            'date' => $start->addDays(13)->toDateString(),
            'type' => 'departure',
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        MaintenanceTicket::create([
            'unit_id' => $units->get(1)->id,
            'title' => 'Shower thermostat sticking',
            'description' => 'Guest reported inconsistent temperature in N102.',
            'category' => 'plumbing',
            'priority' => 'normal',
            'status' => 'open',
            'currency' => self::CURRENCY,
            'reported_by' => $founder->id,
            'opened_at' => now(),
        ]);
    }
}
