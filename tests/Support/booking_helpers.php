<?php

use App\Models\Availability;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\GroupBookingService;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (! function_exists('newDemoOrg')) {
    /**
     * Create a fresh organization and set it as the active tenant context.
     */
    function newDemoOrg(string $currency = 'EUR'): Organization
    {
        $tenant = app(TenantContext::class);
        $org = $tenant->runAsSuperAdmin(fn () => Organization::create([
            'name' => 'Org '.Str::random(6), 'slug' => 'org-'.Str::lower(Str::random(10)), 'default_currency' => $currency,
        ]));
        $tenant->set($org->id);

        return $org;
    }
}

if (! function_exists('seedScenario')) {
    /**
     * One org + property + room_type with N units + rate plan + guest, availability published for
     * a horizon. Leaves the tenant context set to the new org.
     *
     * @return array{org: Organization, property: Property, roomType: RoomType, units: list<Unit>, ratePlan: RatePlan, guest: Guest}
     */
    function seedScenario(int $unitCount = 1): array
    {
        $org = newDemoOrg();

        $property = Property::create(['name' => 'H', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
        $roomType = RoomType::create(['property_id' => $property->id, 'name' => 'Std', 'kind' => 'room']);

        $units = [];
        for ($i = 1; $i <= $unitCount; $i++) {
            $units[] = Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'U'.$i]);
        }

        $ratePlan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'name' => 'Flex', 'currency' => 'EUR']);
        $guest = Guest::create(['first_name' => 'G', 'last_name' => 'One']);

        app(AvailabilityService::class)->recompute($roomType->id, '2026-09-01', '2026-09-10');

        return compact('org', 'property', 'roomType', 'units', 'ratePlan', 'guest');
    }
}

if (! function_exists('secondProperty')) {
    /**
     * A SECOND property in a seedScenario() organisation. ⚠️ An organisation can no longer remove its LAST property
     * (PropertyRemovalService::isLastRemaining — the back office needs one to work in), so a test that removes the
     * scenario's property gives the organisation another one first. Call it inside the scenario's tenant context.
     */
    function secondProperty(array $scenario, string $name = 'Second House'): Property
    {
        return Property::create([
            'name' => $name.' '.Str::upper(Str::random(4)), 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC',
            'slug' => 'second-'.Str::lower(Str::random(8)), 'status' => 'active',
        ]);
    }
}

if (! function_exists('pauseWorkspace')) {
    /**
     * Pause a seedScenario() organisation's workspace — the read-only state OperatorWorkspace::isReadOnly() reports. Shared
     * because two Rates suites need it, and a function declared in one Pest file is missing when a sibling runs alone.
     */
    function pauseWorkspace(array $scenario, ?CarbonImmutable $until = null): void
    {
        app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($scenario['org']->id)
            ->update(['paused_until' => ($until ?? CarbonImmutable::now()->addDays(3))]));
    }
}

if (! function_exists('book')) {
    /**
     * Book a single-line reservation through AssignmentService (default 2 nights).
     *
     * @param  array{property: Property, roomType: RoomType, ratePlan: RatePlan, guest: Guest, units: list<Unit>}  $scenario
     * @param  array<string, mixed>  $lineOverrides
     */
    function book(array $scenario, array $lineOverrides = []): Reservation
    {
        return app(AssignmentService::class)->createBooking(
            [
                'property_id' => $scenario['property']->id,
                'currency' => 'EUR',
                'check_in' => '2026-09-01',
                'check_out' => '2026-09-03',
                'lead_guest_id' => $scenario['guest']->id,
                'source' => 'direct',
            ],
            [array_merge([
                'room_type_id' => $scenario['roomType']->id,
                'rate_plan_id' => $scenario['ratePlan']->id,
                'check_in' => '2026-09-01',
                'check_out' => '2026-09-03',
                'price_minor' => 10000,
                'currency' => 'EUR',
            ], $lineOverrides)],
        );
    }
}

if (! function_exists('soldOn')) {
    function soldOn(string $roomTypeId, string $date): int
    {
        return (int) Availability::query()
            ->where('room_type_id', $roomTypeId)->whereDate('date', $date)->value('sold');
    }
}

if (! function_exists('moneyEur')) {
    function moneyEur(int $minor): Money
    {
        return Money::of($minor, 'EUR');
    }
}

if (! function_exists('cleanupCommittedTenantData')) {
    /**
     * Force-delete every organization (cascades via FK to properties/reservations/channels/…),
     * then clear the append-only event logs (plain columns, no cascade). Non-RefreshDatabase
     * tests COMMIT real rows, so without this their data leaks into later tests — in particular
     * global super-admin counts (see TenantIsolationTest). Same self-clean discipline as
     * ConcurrentBookingTest, generalized. Safe: each of these tests builds its own fresh orgs.
     */
    function cleanupCommittedTenantData(): void
    {
        app(TenantContext::class)->runAsSuperAdmin(function (): void {
            Organization::withTrashed()->get()->each(fn (Organization $org) => $org->forceDelete());
        });

        DB::table('domain_events')->delete();
        DB::table('audit_events')->delete();
    }
}

if (! function_exists('flushQueue')) {
    /**
     * Clear the shared `jobs`/`failed_jobs` tables. Tests that drive the database queue with
     * queue:work rely on these being empty — they are NOT tenant-scoped and NOT rolled back by
     * non-RefreshDatabase tests, so a job left delayed/failed by one test (or a prior run, before
     * the suite's first migrate:fresh) would otherwise be picked up by another test's worker.
     */
    function flushQueue(): void
    {
        DB::table('jobs')->delete();
        DB::table('failed_jobs')->delete();
    }
}

if (! function_exists('tentativeHold')) {
    /**
     * ⚠️ A TENTATIVE GROUP HOLD, FROM THE REAL PRODUCER — never from writing a status onto a row.
     *
     * 🔬 Measured: `GroupBookingService::create()` gives every member a `lead_guest_id`
     * (`$member['lead_guest_id'] ?? $input['organiser_guest_id']`), so a held member ALWAYS has a lead guest.
     * That matters to more than the fixture: it is the reason F-64's defect was always reachable — the only
     * guest-side gate the confirmation listener had was `leadGuest !== null`, and a hold always satisfies it.
     *
     * Shared by {@see tests/Feature/ConfirmTentativeHoldTest.php} (the conversion) and
     * {@see tests/Feature/HoldDoesNotEmailTheGuestTest.php} (the send), so the two cannot drift apart.
     *
     * @param  array{property: Property, roomType: RoomType, units: list<Unit>, ratePlan: RatePlan, guest: Guest}  $scenario
     */
    function tentativeHold(array $scenario): Reservation
    {
        $group = app(GroupBookingService::class)->create([
            'property_id' => $scenario['property']->id,
            'name' => 'Held party',
            'occasion' => null,
            'billing_mode' => 'individual',
            'status' => 'tentative',
            'check_in' => '2026-09-01',
            'check_out' => '2026-09-03',
            'currency' => 'EUR',
            'adults' => 2,
            'children' => 0,
            'organiser_guest_id' => $scenario['guest']->id,
            'pickup_deadline' => '2026-09-01',
            'members' => [[
                'room_type_id' => $scenario['roomType']->id,
                'unit_id' => $scenario['units'][0]->id,
                'rate_plan_id' => $scenario['ratePlan']->id,
                'price_minor' => 10000,
            ]],
        ]);

        return Reservation::query()->where('group_id', $group->getKey())->firstOrFail();
    }
}

if (! function_exists('soldOver')) {
    /** Σ `availability.sold` across a reservation's own room type and stay dates — the inventory spine. */
    function soldOver(Reservation $reservation): int
    {
        $line = $reservation->reservationUnits()->first();

        return (int) DB::table('availability')
            ->where('room_type_id', $line->room_type_id)
            ->whereBetween('date', [$reservation->check_in, $reservation->check_out])
            ->sum('sold');
    }
}

if (! function_exists('raceWorkerDbEnv')) {
    /**
     * The database a race worker must reach — the test process's OWN resolved connection, never a hardcoded host. ⚠️ (2026-10-06)
     * Nine concurrency tests wrote `127.0.0.1:3306` for their workers, so a worker ignored where the suite's database really was:
     * GitHub's checks (MySQL at the service host `mysql`) and an engine gate on another port could never reach it.
     *
     * @return array<string, string>
     */
    function raceWorkerDbEnv(): array
    {
        $c = (array) config('database.connections.mysql');

        return [
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => (string) ($c['database'] ?? 'vyrobel_test'),
            'DB_HOST' => (string) ($c['host'] ?? '127.0.0.1'),
            'DB_PORT' => (string) ($c['port'] ?? '3306'),
            'DB_USERNAME' => (string) ($c['username'] ?? 'root'),
            'DB_PASSWORD' => (string) ($c['password'] ?? ''),
        ];
    }
}
