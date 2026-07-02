<?php

use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Models\DomainEvent;
use App\Models\SyncJob;
use App\Models\SyncLog;
use App\Services\AssignmentService;
use App\Services\Channex\ChannelManagerClient;
use App\Services\Channex\FakeChannexClient;
use App\Services\RateAndRestrictionService;
use Illuminate\Support\Facades\Artisan;

// Phase 2 Step 3 — outbound ARI push. This file intentionally does NOT use RefreshDatabase:
// SyncJobDispatcher dispatches `afterCommit()`, which never fires inside a transaction that
// gets rolled back at test end (same reasoning as SyncReliabilityTest / the true-parallel
// concurrency tests). All Channex interaction here is against the FakeChannexClient bound by
// AppServiceProvider (services.channex.fake=true, pinned in phpunit.xml) — no network.

// Start each test with an empty queue: the jobs table is shared and not rolled back, so a job
// left by another test (or a prior run, before the suite's first migrate:fresh) must not bleed in.
beforeEach(fn () => flushQueue());

// COMMIT real rows (no RefreshDatabase) → clean up after each test so committed data can't leak
// into later tests' global counts regardless of suite ordering.
afterEach(fn () => cleanupCommittedTenantData());

it('enqueues and processes exactly one correct current-state ARI push for a confirmed booking', function () {
    ['scenario' => $s, 'channel' => $channel] = channelScenario();
    ChannelMapping::factory()->roomType($s['roomType'])->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt-1']);

    $domainEventsBefore = DomainEvent::count();

    book($s, ['unit_id' => $s['units'][0]->id]);

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    expect($fake)->toBeInstanceOf(FakeChannexClient::class)
        ->and($fake->pushCount())->toBe(1)
        ->and(DomainEvent::count())->toBe($domainEventsBefore + 1); // booking's own event only — push emits none

    $push = $fake->lastPush();
    $snapshot = $push['snapshot'];
    expect($snapshot->roomTypeId)->toBe($s['roomType']->id)
        ->and($snapshot->externalRoomTypeId)->toBe('chx-rt-1')
        ->and($snapshot->startDate)->toBe('2026-09-01')
        ->and($snapshot->endDate)->toBe('2026-09-03');

    $day1 = collect($snapshot->dates)->firstWhere('date', '2026-09-01');
    expect($day1->available)->toBe(0); // 1 unit, now sold
});

it('does not mutate availability.sold and does not emit any inventory event from inside the push', function () {
    ['scenario' => $s, 'channel' => $channel] = channelScenario();
    ChannelMapping::factory()->roomType($s['roomType'])->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt-1']);

    book($s, ['unit_id' => $s['units'][0]->id]);

    // Same value a plain Phase 1 booking (no channel at all) would produce — proves the push
    // added nothing on top of AssignmentService's own inline sold++.
    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1)
        ->and(soldOn($s['roomType']->id, '2026-09-02'))->toBe(1);

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    expect($fake->pushCount())->toBe(1) // one push, not a runaway loop
        ->and($fake->callCount())->toBe(1);
});

it('cancelling a reservation enqueues exactly one push reflecting restored availability', function () {
    ['scenario' => $s, 'channel' => $channel] = channelScenario();
    ChannelMapping::factory()->roomType($s['roomType'])->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt-1']);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    expect($fake->pushCount())->toBe(1); // from the booking

    app(AssignmentService::class)->cancelReservation($reservation);

    expect($fake->pushCount())->toBe(2); // +1 from the cancellation, not more

    $day1 = collect($fake->lastPush()['snapshot']->dates)->firstWhere('date', '2026-09-01');
    expect($day1->available)->toBe(1); // unit freed back up
});

it('creating an inventory block enqueues exactly one correct current-state push', function () {
    ['scenario' => $s, 'channel' => $channel] = channelScenario();
    ChannelMapping::factory()->roomType($s['roomType'])->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt-1']);

    app(AssignmentService::class)->blockInventory([
        'property_id' => $s['property']->id,
        'room_type_id' => $s['roomType']->id,
        'start_date' => '2026-09-05',
        'end_date' => '2026-09-06',
        'reason' => 'maintenance',
    ]);

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    expect($fake->pushCount())->toBe(1);

    $day = collect($fake->lastPush()['snapshot']->dates)->firstWhere('date', '2026-09-05');
    expect($day->available)->toBe(0); // 1 unit, now blocked
});

it('a rate change enqueues exactly one push including the mapped rate plan price', function () {
    ['scenario' => $s, 'channel' => $channel] = channelScenario();
    ChannelMapping::factory()->roomType($s['roomType'])->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt-1']);
    ChannelMapping::factory()->ratePlan($s['ratePlan'])->create(['channel_id' => $channel->id, 'external_id' => 'chx-rp-1']);

    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-03', moneyEur(15000));

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    expect($fake->pushCount())->toBe(1);

    $snapshot = $fake->lastPush()['snapshot'];
    expect($snapshot->startDate)->toBe('2026-09-01')->and($snapshot->endDate)->toBe('2026-09-03');

    $day1 = collect($snapshot->dates)->firstWhere('date', '2026-09-01');
    expect($day1->ratePlans)->toHaveCount(1)
        ->and($day1->ratePlans[0]->ratePlanId)->toBe($s['ratePlan']->id)
        ->and($day1->ratePlans[0]->externalRatePlanId)->toBe('chx-rp-1')
        ->and($day1->ratePlans[0]->priceMinor)->toBe(15000)
        ->and($day1->ratePlans[0]->currency)->toBe('EUR');
});

it('does not push for an unmapped room_type — logs a clear mapping error and the job still completes', function () {
    ['scenario' => $s, 'channel' => $channel] = channelScenario(); // no mapping created

    book($s, ['unit_id' => $s['units'][0]->id]);

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    expect($fake->pushCount())->toBe(0);

    $syncJob = SyncJob::query()->where('channel_id', $channel->id)->first();
    expect($syncJob)->not->toBeNull()
        ->and($syncJob->status)->toBe('succeeded'); // handled/skipped, not a retryable failure

    $warning = SyncLog::query()->where('sync_job_id', $syncJob->id)->where('level', 'warning')->first();
    expect($warning)->not->toBeNull()
        ->and($warning->message)->toContain('No active channel_mapping');
});

it('does not enqueue any push for a disconnected channel', function () {
    $s = seedScenario(1);
    $channel = Channel::factory()->disconnected()->create(['property_id' => $s['property']->id]);
    ChannelMapping::factory()->roomType($s['roomType'])->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt-1']);

    book($s, ['unit_id' => $s['units'][0]->id]);

    expect(SyncJob::query()->where('channel_id', $channel->id)->count())->toBe(0);
});

// The next two tests need genuine multi-attempt retry timing, so — like SyncReliabilityTest —
// they force the `database` queue connection and drive it with real queue:work + time-travel
// instead of the `sync` connection tests default to (which runs a job inline, single-attempt).

it('retries a failing ARI push with backoff and eventually succeeds, sending exactly one real push', function () {
    config(['queue.default' => 'database']);

    ['scenario' => $s, 'channel' => $channel] = channelScenario();
    ChannelMapping::factory()->roomType($s['roomType'])->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt-1']);

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    $fake->failUntilCall = 2; // fails calls 1 & 2, succeeds on 3

    book($s, ['unit_id' => $s['units'][0]->id]);

    $syncJob = SyncJob::query()->where('channel_id', $channel->id)->firstOrFail();
    expect($syncJob->status)->toBe('pending');

    for ($i = 0; $i < 3; $i++) {
        Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'sync', '--once' => true]);
        $syncJob->refresh();
        if ($syncJob->status === 'succeeded') {
            break;
        }
        $this->travel(400)->seconds();
    }

    expect($syncJob->status)->toBe('succeeded')
        ->and($syncJob->attempts)->toBe(3)
        ->and($fake->callCount())->toBe(3)   // 2 failed attempts + 1 that succeeded
        ->and($fake->pushCount())->toBe(1);  // exactly one push actually recorded — never double-applied
});

it('dead-letters a permanently failing ARI push after max attempts and never exceeds/reprocesses it', function () {
    config(['queue.default' => 'database']);

    ['scenario' => $s, 'channel' => $channel] = channelScenario();
    ChannelMapping::factory()->roomType($s['roomType'])->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt-1']);

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    $fake->failUntilCall = 99; // always fails

    book($s, ['unit_id' => $s['units'][0]->id]);

    $syncJob = SyncJob::query()->where('channel_id', $channel->id)->firstOrFail();

    for ($i = 0; $i < 5; $i++) { // RetryableSyncJob::$tries = 5
        Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'sync', '--once' => true]);
        $this->travel(400)->seconds();
    }

    $syncJob->refresh();
    expect($syncJob->status)->toBe('dead_letter')
        ->and($syncJob->attempts)->toBe(5)
        ->and($fake->callCount())->toBe(5)  // exactly 5 attempts — never retried beyond max
        ->and($fake->pushCount())->toBe(0); // never actually delivered
});
