<?php

use App\Models\AuditEvent;
use App\Models\Availability;
use App\Models\DomainEvent;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/*
 * NOTE: this file intentionally does NOT use RefreshDatabase. The double-booking guarantee is
 * a MySQL row-locking property that only manifests across SEPARATE connections with COMMITTED
 * data — so it seeds real committed rows and races two independent OS processes (true parallel-
 * ism, not two sequential calls, and not an injected barrier). It cleans up after itself.
 */

/**
 * @return array{0: Organization, 1: Property, 2: RoomType, 3: Unit, 4: RatePlan, 5: Guest, 6: string, 7: string}
 */
function seedRaceScenario(): array
{
    $tenant = app(TenantContext::class);
    $availability = app(AvailabilityService::class);

    $checkIn = '2026-09-01';
    $checkOut = '2026-09-03'; // 2 nights

    $org = $tenant->runAsSuperAdmin(fn () => Organization::create([
        'name' => 'Race Org', 'slug' => 'race-'.Str::random(10), 'default_currency' => 'EUR',
    ]));

    return $tenant->forOrganization($org->id, function () use ($org, $availability, $checkIn, $checkOut): array {
        $property = Property::create(['name' => 'Race Hotel', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
        $roomType = RoomType::create(['property_id' => $property->id, 'name' => 'Double', 'kind' => 'room', 'std_occupancy' => 2]);

        // TWO units of the type, so Level-A counts alone would NOT reject a second booking —
        // forcing Level B (physical-unit non-overlap) to be the decider under contention.
        $unitX = Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'X1']);
        Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'X2']);

        $ratePlan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'name' => 'Flex', 'currency' => 'EUR']);
        $guest = Guest::create(['first_name' => 'Race', 'last_name' => 'Guest']);

        $availability->recompute($roomType->id, $checkIn, $checkOut);

        return [$org, $property, $roomType, $unitX, $ratePlan, $guest, $checkIn, $checkOut];
    });
}

function cleanupRaceScenario(Organization $org): void
{
    app(TenantContext::class)->runAsSuperAdmin(function () use ($org): void {
        DomainEvent::query()->where('organization_id', $org->id)->delete();
        AuditEvent::query()->where('organization_id', $org->id)->delete();
        // Hard delete the org → DB ON DELETE CASCADE removes all tenant rows.
        Organization::query()->whereKey($org->id)->forceDelete();
    });
}

it('permits exactly one of two truly-concurrent bookings on the same unit and dates', function () {
    Artisan::call('migrate', ['--force' => true]); // ensure schema regardless of test order

    [$org, $property, $roomType, $unitX, $ratePlan, $guest, $checkIn, $checkOut] = seedRaceScenario();

    try {
        $startAt = microtime(true) + 2.0; // both processes boot, then fire together

        $env = array_merge(getenv(), [
            'RACE_ORG_ID' => $org->id,
            'RACE_PROPERTY_ID' => $property->id,
            'RACE_ROOM_TYPE_ID' => $roomType->id,
            'RACE_UNIT_ID' => $unitX->id,
            'RACE_RATE_PLAN_ID' => $ratePlan->id,
            'RACE_GUEST_ID' => $guest->id,
            'RACE_CHECK_IN' => $checkIn,
            'RACE_CHECK_OUT' => $checkOut,
            'RACE_CURRENCY' => 'EUR',
            'RACE_START_AT' => (string) $startAt,
            ...raceWorkerDbEnv(),
        ]);

        $worker = base_path('tests/Support/booking_race_worker.php');
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $p1 = proc_open([PHP_BINARY, $worker], $descriptors, $pipes1, base_path(), $env);
        $p2 = proc_open([PHP_BINARY, $worker], $descriptors, $pipes2, base_path(), $env);

        expect(is_resource($p1) && is_resource($p2))->toBeTrue();

        $out1 = stream_get_contents($pipes1[1]);
        $err1 = stream_get_contents($pipes1[2]);
        $out2 = stream_get_contents($pipes2[1]);
        $err2 = stream_get_contents($pipes2[2]);

        foreach ([$pipes1, $pipes2] as $pipes) {
            fclose($pipes[1]);
            fclose($pipes[2]);
        }
        proc_close($p1);
        proc_close($p2);

        $r1 = json_decode(trim((string) $out1), true);
        $r2 = json_decode(trim((string) $out2), true);

        $context = "worker1 out={$out1} err={$err1}\nworker2 out={$out2} err={$err2}";
        expect($r1)->toBeArray($context);
        expect($r2)->toBeArray($context);

        $statuses = [$r1['status'] ?? 'parse_error', $r2['status'] ?? 'parse_error'];
        sort($statuses);

        // The make-or-break assertion: exactly one succeeds, the other is cleanly rejected.
        expect($statuses)->toBe(['rejected', 'success'], $context);

        // And the database reflects exactly one booking of the unit, sold == 1 every night.
        app(TenantContext::class)->forOrganization($org->id, function () use ($unitX, $roomType): void {
            $activeOnUnit = ReservationUnit::query()
                ->where('unit_id', $unitX->id)->where('status', 'active')->count();
            expect($activeOnUnit)->toBe(1);

            Availability::query()->where('room_type_id', $roomType->id)->get()
                ->each(fn (Availability $row) => expect($row->sold)->toBe(1));
        });
    } finally {
        cleanupRaceScenario($org);
    }
});
