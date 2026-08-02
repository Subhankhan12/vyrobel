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
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/*
 * The reschedule counterpart to ConcurrentBookingTest. Like it, this file does NOT use RefreshDatabase:
 * the no-double-booking guarantee under a MOVE is a MySQL row-locking property that only manifests across
 * SEPARATE connections with COMMITTED rows, so it seeds real committed bookings and races two independent
 * OS processes, each dragging its booking onto the SAME target unit + nights. Exactly one may win.
 */

/**
 * @return array{org: Organization, roomType: RoomType, unitX1: Unit, lineA: string, lineB: string}
 */
function seedRescheduleRaceScenario(): array
{
    $tenant = app(TenantContext::class);
    $availability = app(AvailabilityService::class);

    $org = $tenant->runAsSuperAdmin(fn () => Organization::create([
        'name' => 'Resched Race Org', 'slug' => 'resched-'.Str::random(10), 'default_currency' => 'EUR',
    ]));

    return $tenant->forOrganization($org->id, function () use ($org, $availability): array {
        $property = Property::create(['name' => 'Race Hotel', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
        $roomType = RoomType::create(['property_id' => $property->id, 'name' => 'Double', 'kind' => 'room', 'std_occupancy' => 2]);
        $unitX1 = Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'X1']);
        $unitX2 = Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'X2']);
        $ratePlan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'name' => 'Flex', 'currency' => 'EUR']);
        $guest = Guest::create(['first_name' => 'Race', 'last_name' => 'Guest']);

        // Publish availability across BOTH the origin (Sep 1–3) and the target (Sep 5–7) nights.
        $availability->recompute($roomType->id, '2026-09-01', '2026-09-09');

        $assign = app(AssignmentService::class);
        $mk = function (Unit $unit) use ($property, $roomType, $ratePlan, $guest, $assign): string {
            $reservation = $assign->createBooking(
                ['property_id' => $property->id, 'currency' => 'EUR', 'check_in' => '2026-09-01', 'check_out' => '2026-09-03',
                    'lead_guest_id' => $guest->id, 'source' => 'direct', 'status' => 'confirmed'],
                [['room_type_id' => $roomType->id, 'unit_id' => $unit->id, 'rate_plan_id' => $ratePlan->id,
                    'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'price_minor' => 10000, 'currency' => 'EUR']],
            );

            return (string) $reservation->reservationUnits()->first()->id;
        };

        // B_A on X1, B_B on X2 — both currently Sep 1–3; both will try to move to X1 · Sep 5–7.
        return ['org' => $org, 'roomType' => $roomType, 'unitX1' => $unitX1, 'lineA' => $mk($unitX1), 'lineB' => $mk($unitX2)];
    });
}

it('permits exactly one of two truly-concurrent reschedules onto the same unit and dates', function () {
    Artisan::call('migrate', ['--force' => true]);

    $s = seedRescheduleRaceScenario();
    $org = $s['org'];

    try {
        $startAt = microtime(true) + 2.0;

        $baseEnv = array_merge(getenv(), [
            'RACE_ORG_ID' => $org->id,
            'RACE_TARGET_UNIT_ID' => $s['unitX1']->id,
            'RACE_NEW_CHECK_IN' => '2026-09-05',
            'RACE_NEW_CHECK_OUT' => '2026-09-07',
            'RACE_START_AT' => (string) $startAt,
            'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'vyrobel_test',
            'DB_HOST' => '127.0.0.1', 'DB_PORT' => '3306', 'DB_USERNAME' => 'root', 'DB_PASSWORD' => '',
        ]);

        $worker = base_path('tests/Support/reschedule_race_worker.php');
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $p1 = proc_open([PHP_BINARY, $worker], $descriptors, $pipes1, base_path(), array_merge($baseEnv, ['RACE_LINE_ID' => $s['lineA']]));
        $p2 = proc_open([PHP_BINARY, $worker], $descriptors, $pipes2, base_path(), array_merge($baseEnv, ['RACE_LINE_ID' => $s['lineB']]));

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

        // The make-or-break assertion: exactly one move succeeds, the other is cleanly rejected.
        expect($statuses)->toBe(['rejected', 'success'], $context);

        app(TenantContext::class)->forOrganization($org->id, function () use ($s): void {
            // Exactly ONE active line occupies X1 over the contested target window Sep 5–7.
            $onTarget = ReservationUnit::query()
                ->where('unit_id', $s['unitX1']->id)->where('status', 'active')
                ->whereDate('check_in', '<', '2026-09-07')->whereDate('check_out', '>', '2026-09-05')
                ->count();
            expect($onTarget)->toBe(1);

            // Both bookings still exist as exactly one active line each (the loser rolled back, not lost).
            expect(ReservationUnit::query()->where('status', 'active')->count())->toBe(2);

            // Inventory integrity: the target nights carry sold == 1 (no double-claim); no oversell anywhere.
            Availability::query()->where('room_type_id', $s['roomType']->id)->get()->each(
                fn (Availability $row) => expect($row->sold)->toBeLessThanOrEqual($row->total),
            );
            $sept5 = Availability::query()->where('room_type_id', $s['roomType']->id)->whereDate('date', '2026-09-05')->first();
            expect((int) $sept5->sold)->toBe(1);
        });
    } finally {
        app(TenantContext::class)->runAsSuperAdmin(function () use ($org): void {
            DomainEvent::query()->where('organization_id', $org->id)->delete();
            AuditEvent::query()->where('organization_id', $org->id)->delete();
            Organization::query()->whereKey($org->id)->forceDelete();
        });
    }
});
