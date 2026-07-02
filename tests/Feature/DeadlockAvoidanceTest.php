<?php

use App\Models\AuditEvent;
use App\Models\DomainEvent;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

// Obligation 2 — deadlock avoidance under true OS-level parallelism (no RefreshDatabase).

it('serializes two multi-room bookings that lock room types in OPPOSITE order, with no deadlock', function () {
    Artisan::call('migrate', ['--force' => true]);

    $tenant = app(TenantContext::class);

    /** @var array{0: Organization, 1: Property, 2: RoomType, 3: RoomType, 4: array<int, Unit>, 5: array<int, Unit>, 6: Guest} $seed */
    $seed = $tenant->runAsSuperAdmin(function () use ($tenant) {
        $org = Organization::create(['name' => 'DL Org', 'slug' => 'dl-'.Str::random(10), 'default_currency' => 'EUR']);

        return $tenant->forOrganization($org->id, function () use ($org) {
            $property = Property::create(['name' => 'DL Hotel', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
            $typeX = RoomType::create(['property_id' => $property->id, 'name' => 'X', 'kind' => 'room']);
            $typeY = RoomType::create(['property_id' => $property->id, 'name' => 'Y', 'kind' => 'room']);

            // 2 units each so both concurrent bookings can succeed (distinct units).
            $unitsX = [Unit::create(['property_id' => $property->id, 'room_type_id' => $typeX->id, 'code' => 'X1']), Unit::create(['property_id' => $property->id, 'room_type_id' => $typeX->id, 'code' => 'X2'])];
            $unitsY = [Unit::create(['property_id' => $property->id, 'room_type_id' => $typeY->id, 'code' => 'Y1']), Unit::create(['property_id' => $property->id, 'room_type_id' => $typeY->id, 'code' => 'Y2'])];
            $guest = Guest::create(['first_name' => 'DL', 'last_name' => 'Guest']);

            app(AvailabilityService::class)->recompute($typeX->id, '2026-09-01', '2026-09-03');
            app(AvailabilityService::class)->recompute($typeY->id, '2026-09-01', '2026-09-03');

            return [$org, $property, $typeX, $typeY, $unitsX, $unitsY, $guest];
        });
    });

    [$org, $property, $typeX, $typeY, $unitsX, $unitsY, $guest] = $seed;

    try {
        $startAt = microtime(true) + 2.0;
        $baseEnv = array_merge(getenv(), [
            'RACE_ORG_ID' => $org->id, 'RACE_PROPERTY_ID' => $property->id,
            'RACE_ROOM_TYPE_X' => $typeX->id, 'RACE_ROOM_TYPE_Y' => $typeY->id,
            'RACE_GUEST_ID' => $guest->id, 'RACE_CHECK_IN' => '2026-09-01', 'RACE_CHECK_OUT' => '2026-09-03',
            'RACE_START_AT' => (string) $startAt,
            'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'vyrobel_test', 'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306', 'DB_USERNAME' => 'root', 'DB_PASSWORD' => '',
        ]);

        // Worker 1 books X then Y (unit X1, Y1); Worker 2 books Y then X (unit Y2, X2).
        $env1 = array_merge($baseEnv, ['RACE_UNIT_X' => $unitsX[0]->id, 'RACE_UNIT_Y' => $unitsY[0]->id, 'RACE_LINE_ORDER' => 'xy']);
        $env2 = array_merge($baseEnv, ['RACE_UNIT_X' => $unitsX[1]->id, 'RACE_UNIT_Y' => $unitsY[1]->id, 'RACE_LINE_ORDER' => 'yx']);

        $worker = base_path('tests/Support/deadlock_worker.php');
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        $p1 = proc_open([PHP_BINARY, $worker], $descriptors, $pipes1, base_path(), $env1);
        $p2 = proc_open([PHP_BINARY, $worker], $descriptors, $pipes2, base_path(), $env2);
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
        $context = "w1 out={$out1} err={$err1}\nw2 out={$out2} err={$err2}";

        // Both succeed cleanly — no deadlock error, both reservations committed.
        expect($r1['status'] ?? 'parse_error')->toBe('success', $context);
        expect($r2['status'] ?? 'parse_error')->toBe('success', $context);

        $tenant->forOrganization($org->id, function () use ($typeX, $typeY) {
            expect(soldOn($typeX->id, '2026-09-01'))->toBe(2)
                ->and(soldOn($typeY->id, '2026-09-01'))->toBe(2)
                ->and(Reservation::count())->toBe(2);
        });
    } finally {
        $tenant->runAsSuperAdmin(function () use ($org) {
            DomainEvent::query()->where('organization_id', $org->id)->delete();
            AuditEvent::query()->where('organization_id', $org->id)->delete();
            Organization::query()->whereKey($org->id)->forceDelete();
        });
    }
});
