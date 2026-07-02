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
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
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
