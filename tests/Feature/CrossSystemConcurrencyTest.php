<?php

use App\Exceptions\UnmappedChannelEntityException;
use App\Jobs\Sync\AriPushJob;
use App\Models\Availability;
use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Models\DomainEvent;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\OverbookingConflict;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Models\SyncJob;
use App\Models\SyncLog;
use App\Models\Unit;
use App\Models\WebhookEvent;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\BookingImportService;
use App\Services\Channex\ChannelManagerClient;
use App\Services\Channex\FakeChannexClient;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/*
 * Phase 2 Step 5 — the cross-system concurrency & sync-reliability crux suite. Does NOT use
 * RefreshDatabase: the guarantees are MySQL row-locking + committed-state properties that only
 * manifest across separate connections. Tests 1/2/4c race REAL OS-level processes (proc_open,
 * separate MySQL connections, shared future-timestamp barrier) — the same technique as Phase 1's
 * ConcurrentBookingTest. Tests 3/4/5/6/7 run in-process against the FakeChannexClient. No test
 * hits the network. Each test cleans up (afterEach) so committed rows don't leak forward.
 */

beforeEach(fn () => flushQueue());
afterEach(fn () => cleanupCommittedTenantData());

/**
 * Seed a race scenario with `$totalUnits` physical units (default 1 = "the last unit"), a channel,
 * and room_type + rate_plan channel_mappings. Leaves tenant context on the new org.
 *
 * @return array{org: Organization, property: Property, roomType: RoomType, units: list<Unit>, ratePlan: RatePlan, guest: Guest, channel: Channel, checkIn: string, checkOut: string}
 */
function seedCrossRaceScenario(int $totalUnits = 1): array
{
    $tenant = app(TenantContext::class);
    $checkIn = '2026-09-01';
    $checkOut = '2026-09-03';

    $org = $tenant->runAsSuperAdmin(fn () => Organization::create([
        'name' => 'XRace Org', 'slug' => 'xrace-'.Str::lower(Str::random(10)), 'default_currency' => 'EUR',
    ]));
    $tenant->set($org->id);

    $property = Property::create(['name' => 'XRace Hotel', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $roomType = RoomType::create(['property_id' => $property->id, 'name' => 'Std', 'kind' => 'room']);

    $units = [];
    for ($i = 1; $i <= $totalUnits; $i++) {
        $units[] = Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'X'.$i]);
    }

    $ratePlan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'name' => 'Flex', 'currency' => 'EUR']);
    $guest = Guest::create(['first_name' => 'XRace', 'last_name' => 'Guest']);
    $channel = Channel::factory()->create(['property_id' => $property->id, 'name' => 'XRace Channex', 'external_property_id' => 'chx-prop']);
    ChannelMapping::factory()->roomType($roomType)->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt-1']);
    ChannelMapping::factory()->ratePlan($ratePlan)->create(['channel_id' => $channel->id, 'external_id' => 'chx-rp-1']);

    app(AvailabilityService::class)->recompute($roomType->id, $checkIn, $checkOut);

    return compact('org', 'property', 'roomType', 'units', 'ratePlan', 'guest', 'channel', 'checkIn', 'checkOut');
}

/**
 * Launch N worker processes simultaneously (shared RACE_START_AT barrier) and return decoded
 * JSON results in launch order.
 *
 * @param  list<array<string, string>>  $perWorkerEnv  extra env per worker (RACE_MODE, ids, …)
 * @param  array<string, mixed>  $s  a seedCrossRaceScenario() result
 * @return list<array<string, mixed>>
 */
function launchRaceWorkers(array $perWorkerEnv, array $s): array
{
    $startAt = microtime(true) + 1.5;
    $baseEnv = array_merge(getenv(), [
        'RACE_ORG_ID' => $s['org']->id,
        'RACE_PROPERTY_ID' => $s['property']->id,
        'RACE_ROOM_TYPE_ID' => $s['roomType']->id,
        'RACE_GUEST_ID' => $s['guest']->id,
        'RACE_CHANNEL_ID' => $s['channel']->id,
        'RACE_CHECK_IN' => $s['checkIn'],
        'RACE_CHECK_OUT' => $s['checkOut'],
        'RACE_START_AT' => (string) $startAt,
        'QUEUE_CONNECTION' => 'sync',
        'CHANNEX_FAKE' => 'true',
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => 'vyrobel_test',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
    ]);

    $worker = base_path('tests/Support/cross_race_worker.php');
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

    $procs = [];
    $pipes = [];
    foreach ($perWorkerEnv as $i => $extra) {
        $procs[$i] = proc_open([PHP_BINARY, $worker], $descriptors, $pipes[$i], base_path(), array_merge($baseEnv, $extra));
    }

    $results = [];
    foreach ($procs as $i => $proc) {
        $out = stream_get_contents($pipes[$i][1]);
        $err = stream_get_contents($pipes[$i][2]);
        fclose($pipes[$i][1]);
        fclose($pipes[$i][2]);
        proc_close($proc);
        $decoded = json_decode(trim((string) $out), true);
        $results[$i] = is_array($decoded) ? $decoded : ['status' => 'parse_error', 'raw' => $out, 'err' => $err];
    }

    return $results;
}

it('DIRECT vs OTA race for the last unit: exactly one occupies, the other is rejected or overbooked, sold never exceeds total (5×)', function () {
    Artisan::call('migrate', ['--force' => true]);

    foreach (range(1, 5) as $round) {
        $s = seedCrossRaceScenario(totalUnits: 1); // ONE unit = the last unit

        $results = launchRaceWorkers([
            ['RACE_MODE' => 'direct', 'RACE_UNIT_ID' => $s['units'][0]->id],
            ['RACE_MODE' => 'ota', 'RACE_OTA_ID' => 'OTA-'.Str::random(8)],
        ], $s);

        $context = "round {$round}: ".json_encode($results);
        $direct = $results[0];
        $ota = $results[1];

        // Valid outcomes: direct wins (success + ota overbooked) OR ota wins (seated + direct rejected).
        $pair = [$direct['status'] ?? 'x', $ota['status'] ?? 'x'];
        expect($pair === ['success', 'overbooked'] || $pair === ['rejected', 'seated'])
            ->toBeTrue("neither-both must occupy — {$context}");

        app(TenantContext::class)->forOrganization($s['org']->id, function () use ($s, $pair, $context): void {
            // sold is exactly 1 on every night — never inflated past the single unit.
            Availability::query()->where('room_type_id', $s['roomType']->id)->get()
                ->each(fn (Availability $r) => expect($r->sold)->toBe(1, "sold must be 1 — {$context}"));

            // Exactly one ACTIVE occupying line (the seat).
            expect(ReservationUnit::query()->where('status', 'active')->count())->toBe(1, "one active line — {$context}");

            // If the OTA import lost the seat, exactly one open conflict; if it won, none.
            $expectedConflicts = $pair === ['success', 'overbooked'] ? 1 : 0;
            expect(OverbookingConflict::query()->where('status', 'open')->count())->toBe($expectedConflicts, "conflicts — {$context}");
        });

        cleanupCommittedTenantData();
    }
});

it('OTA vs OTA race for the last unit: one seats, one is overbooked + conflict, sold stays at capacity (5×)', function () {
    Artisan::call('migrate', ['--force' => true]);

    foreach (range(1, 5) as $round) {
        $s = seedCrossRaceScenario(totalUnits: 1);

        $results = launchRaceWorkers([
            ['RACE_MODE' => 'ota', 'RACE_OTA_ID' => 'OTA-A-'.Str::random(8)],
            ['RACE_MODE' => 'ota', 'RACE_OTA_ID' => 'OTA-B-'.Str::random(8)],
        ], $s);

        $context = "round {$round}: ".json_encode($results);
        $statuses = [$results[0]['status'] ?? 'x', $results[1]['status'] ?? 'x'];
        sort($statuses);

        expect($statuses)->toBe(['overbooked', 'seated'], "one seats, one overbooked — {$context}");

        app(TenantContext::class)->forOrganization($s['org']->id, function () use ($s, $context): void {
            Availability::query()->where('room_type_id', $s['roomType']->id)->get()
                ->each(fn (Availability $r) => expect($r->sold)->toBe(1, "sold must be 1 — {$context}"));
            expect(ReservationUnit::query()->where('status', 'active')->count())->toBe(1, "one active line — {$context}")
                ->and(ReservationUnit::query()->where('status', 'overbooked')->count())->toBe(1, "one overbooked line — {$context}")
                ->and(OverbookingConflict::query()->where('status', 'open')->count())->toBe(1, "one conflict — {$context}");
        });

        cleanupCommittedTenantData();
    }
});

// ── Test 3 — ARI push on every availability-affecting change (in-process, FakeChannexClient) ──

it('enqueues exactly one correct current-state push per change (book/import/cancel/block/rate), no loop, no sold mutation', function () {
    $s = seedCrossRaceScenario(totalUnits: 3); // room for a direct + an OTA seat without overbooking

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);

    // Each change should add exactly ONE push and exactly ONE write-side domain event (a push is a
    // pure consumer — it emits no domain events, so no cascade/loop).
    $assertOneMore = function (int $pushBefore, int $eventsBefore) use ($fake): void {
        expect($fake->pushCount())->toBe($pushBefore + 1)
            ->and(DomainEvent::count())->toBe($eventsBefore + 1);
    };

    // 1) direct booking
    $p = $fake->pushCount();
    $e = DomainEvent::count();
    $direct = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $s['checkIn'], 'check_out' => $s['checkOut'], 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'check_in' => $s['checkIn'], 'check_out' => $s['checkOut'], 'price_minor' => 10000, 'currency' => 'EUR']],
    );
    $assertOneMore($p, $e);
    expect(soldOn($s['roomType']->id, $s['checkIn']))->toBe(1);

    // 2) OTA import (seats — 3 units)
    $p = $fake->pushCount();
    $e = DomainEvent::count();
    app(AssignmentService::class)->importBooking(
        ['property_id' => $s['property']->id, 'channel_id' => $s['channel']->id, 'ota_reservation_id' => 'OTA-'.Str::random(6), 'source' => 'ota', 'currency' => 'EUR', 'check_in' => $s['checkIn'], 'check_out' => $s['checkOut'], 'lead_guest_id' => $s['guest']->id],
        [['room_type_id' => $s['roomType']->id, 'check_in' => $s['checkIn'], 'check_out' => $s['checkOut'], 'price_minor' => 10000, 'currency' => 'EUR']],
    );
    $assertOneMore($p, $e);
    expect(soldOn($s['roomType']->id, $s['checkIn']))->toBe(2);

    // 3) cancel the direct booking
    $p = $fake->pushCount();
    $e = DomainEvent::count();
    $soldBeforeCancel = soldOn($s['roomType']->id, $s['checkIn']);
    app(AssignmentService::class)->cancelReservation($direct);
    $assertOneMore($p, $e);
    expect(soldOn($s['roomType']->id, $s['checkIn']))->toBe($soldBeforeCancel - 1);

    // 4) inventory block
    $p = $fake->pushCount();
    $e = DomainEvent::count();
    app(AssignmentService::class)->blockInventory(['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'start_date' => $s['checkIn'], 'end_date' => $s['checkOut'], 'reason' => 'maintenance']);
    $assertOneMore($p, $e);

    // 5) rate change
    $p = $fake->pushCount();
    $e = DomainEvent::count();
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], $s['checkIn'], $s['checkOut'], Money::of(18000, 'EUR'));
    $assertOneMore($p, $e);

    // Every recorded push carried this room_type — none missed, none stray.
    expect($fake->pushesFor($s['roomType']->id))->toHaveCount($fake->pushCount());
});

// ── Test 4 — webhook idempotency under N deliveries (sequential via endpoint + true-parallel) ──

it('imports exactly one reservation when the same webhook is delivered N times (sequential + concurrent)', function () {
    Artisan::call('migrate', ['--force' => true]);

    // (a) Sequential redelivery through the real endpoint.
    config(['queue.default' => 'database']);
    $s = seedCrossRaceScenario(totalUnits: 3);
    $payload = channexBookingPayload(['ota_reservation_id' => 'OTA-SEQ-1']);

    foreach (range(1, 5) as $ignored) {
        postChannexWebhook($s['channel'], $payload)->assertStatus(202);
        drainSync();
    }

    expect(WebhookEvent::query()->where('external_event_id', $payload['event_id'])->count())->toBe(1)
        ->and(Reservation::query()->where('ota_reservation_id', 'OTA-SEQ-1')->count())->toBe(1)
        ->and(SyncJob::query()->where('type', 'booking_import')->where('status', 'succeeded')->count())->toBe(1);

    cleanupCommittedTenantData();

    // (b) True-parallel: 3 processes import the SAME booking simultaneously → still one reservation.
    $s = seedCrossRaceScenario(totalUnits: 3);
    $otaId = 'OTA-CONC-1';
    $results = launchRaceWorkers([
        ['RACE_MODE' => 'import_dup', 'RACE_OTA_ID' => $otaId, 'RACE_EXT_ROOM_TYPE' => 'chx-rt-1', 'RACE_EXT_RATE_PLAN' => 'chx-rp-1'],
        ['RACE_MODE' => 'import_dup', 'RACE_OTA_ID' => $otaId, 'RACE_EXT_ROOM_TYPE' => 'chx-rt-1', 'RACE_EXT_RATE_PLAN' => 'chx-rp-1'],
        ['RACE_MODE' => 'import_dup', 'RACE_OTA_ID' => $otaId, 'RACE_EXT_ROOM_TYPE' => 'chx-rt-1', 'RACE_EXT_RATE_PLAN' => 'chx-rp-1'],
    ], $s);

    $context = json_encode($results);
    foreach ($results as $r) {
        expect($r['status'] ?? 'x')->toBe('imported', "no worker errored — {$context}");
    }

    app(TenantContext::class)->forOrganization($s['org']->id, function () use ($otaId, $context): void {
        expect(Reservation::query()->where('ota_reservation_id', $otaId)->count())->toBe(1, "exactly one reservation — {$context}");
    });
});

// ── Test 5 — retry / backoff / dead-letter, end-to-end on REDIS, via time-travel ──

it('retries a failing sync job with backoff and dead-letters at max attempts on the Redis queue, never applying it twice', function () {
    Artisan::call('migrate', ['--force' => true]);
    config(['queue.default' => 'redis']);
    Artisan::call('queue:clear', ['connection' => 'redis', '--queue' => 'sync']);

    $s = seedCrossRaceScenario(totalUnits: 1);

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    $fake->failUntilCall = 99; // every push attempt fails

    // A direct booking enqueues one ARI push to the Redis 'sync' queue.
    app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $s['checkIn'], 'check_out' => $s['checkOut'], 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'check_in' => $s['checkIn'], 'check_out' => $s['checkOut'], 'price_minor' => 10000, 'currency' => 'EUR']],
    );

    $syncJob = SyncJob::query()->where('type', 'ari_push')->firstOrFail();

    for ($i = 0; $i < 6; $i++) { // tries = 5, +1 slack
        Artisan::call('queue:work', ['connection' => 'redis', '--queue' => 'sync', '--once' => true]);
        $syncJob->refresh();
        if ($syncJob->status === 'dead_letter') {
            break;
        }
        $this->travel(400)->seconds();
    }

    expect($syncJob->status)->toBe('dead_letter')
        ->and($syncJob->attempts)->toBe(5)
        ->and($fake->callCount())->toBe(5)   // exactly 5 attempts — never beyond max
        ->and($fake->pushCount())->toBe(0);  // never actually delivered

    // Readable dead-letter log line exists.
    expect(SyncLog::query()->where('sync_job_id', $syncJob->id)->where('level', 'error')->count())->toBeGreaterThanOrEqual(1);

    // Idempotent redelivery after dead-letter: running the job again is a pure no-op.
    app()->call([new AriPushJob($s['org']->id, $syncJob->id), 'handle']);
    $syncJob->refresh();
    expect($syncJob->status)->toBe('dead_letter')->and($syncJob->attempts)->toBe(5);

    Artisan::call('queue:clear', ['connection' => 'redis', '--queue' => 'sync']);
});

// ── Test 6 — overbooking detection + conflict resolution on cancel (in-process) ──

it('records an OTA overbooking then resolves the conflict when the reservation is cancelled', function () {
    $s = seedCrossRaceScenario(totalUnits: 1);

    // Fill the single unit directly, then import an OTA booking for the same night → overbooked.
    app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $s['checkIn'], 'check_out' => $s['checkOut'], 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'check_in' => $s['checkIn'], 'check_out' => $s['checkOut'], 'price_minor' => 10000, 'currency' => 'EUR']],
    );

    $ota = app(AssignmentService::class)->importBooking(
        ['property_id' => $s['property']->id, 'channel_id' => $s['channel']->id, 'ota_reservation_id' => 'OTA-OB-1', 'source' => 'ota', 'currency' => 'EUR', 'check_in' => $s['checkIn'], 'check_out' => $s['checkOut'], 'lead_guest_id' => $s['guest']->id],
        [['room_type_id' => $s['roomType']->id, 'check_in' => $s['checkIn'], 'check_out' => $s['checkOut'], 'price_minor' => 10000, 'currency' => 'EUR']],
    );

    expect(OverbookingConflict::query()->where('reservation_id', $ota->id)->where('status', 'open')->count())->toBe(1)
        ->and($ota->reservationUnits()->first()->status)->toBe('overbooked')
        ->and(soldOn($s['roomType']->id, $s['checkIn']))->toBe(1); // sold still at capacity

    // Cancelling the OTA reservation resolves its conflict (no sold change — it never consumed sold).
    app(AssignmentService::class)->cancelReservation($ota, 'OTA cancellation');

    expect(OverbookingConflict::query()->where('reservation_id', $ota->id)->where('status', 'open')->count())->toBe(0)
        ->and(OverbookingConflict::query()->where('reservation_id', $ota->id)->where('status', 'resolved')->where('resolution', 'cancelled_external')->count())->toBe(1)
        ->and(soldOn($s['roomType']->id, $s['checkIn']))->toBe(1);
});

// ── Test 7 — mapping guard, both directions (in-process) ──

it('blocks an unmapped outbound push and an unmapped inbound import, both with clear errors and no crash', function () {
    // A channel with NO mappings at all.
    $ctx = channelScenario();
    $s = $ctx['scenario'];
    $channel = $ctx['channel'];

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    $pushesBefore = $fake->pushCount();

    // OUTBOUND: a booking enqueues an ARI push; the unmapped room_type makes it a logged skip
    // (succeeded, zero pushes) — never a silent success-with-push.
    app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'price_minor' => 10000, 'currency' => 'EUR']],
    );

    $pushJob = SyncJob::query()->where('type', 'ari_push')->where('channel_id', $channel->id)->firstOrFail();
    expect($fake->pushCount())->toBe($pushesBefore) // no push happened
        ->and($pushJob->status)->toBe('succeeded')  // handled as a skip, not a crash
        ->and(SyncLog::query()->where('sync_job_id', $pushJob->id)->where('level', 'warning')->count())->toBeGreaterThanOrEqual(1);

    // INBOUND: importing a booking for an unmapped external room type fails loudly (no reservation).
    expect(fn () => app(BookingImportService::class)->import($channel, [
        'ota_reservation_id' => 'OTA-UNMAPPED-1',
        'currency' => 'EUR',
        'total_minor' => 10000,
        'customer' => ['ota_guest_id' => 'g1', 'first_name' => 'A', 'last_name' => 'B'],
        'rooms' => [['external_room_type_id' => 'nope-not-mapped', 'checkin_date' => '2026-09-01', 'checkout_date' => '2026-09-03', 'occupancy' => ['adults' => 1], 'amount_minor' => 10000]],
    ]))->toThrow(UnmappedChannelEntityException::class);

    expect(Reservation::query()->where('ota_reservation_id', 'OTA-UNMAPPED-1')->count())->toBe(0);
});
