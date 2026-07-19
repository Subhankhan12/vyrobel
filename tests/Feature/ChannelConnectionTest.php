<?php

use App\Models\AuditEvent;
use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Models\DomainEvent;
use App\Models\SyncJob;
use App\Services\ChannelConnectionService;
use App\Services\Channex\ChannelManagerClient;
use App\Services\Channex\FakeChannexClient;

// Phase 0.1 — the channels.status writer that un-inerts the certified channel manager. The outbound ARI push
// (EnqueueAriPushOnInventoryChange) targets ONLY channels with status='connected', and the mapping guard/snapshot
// only include a mapping with status='active' — but nothing in app/ ever wrote either value, so for a real
// operator the push could never fire. ChannelConnectionService is that writer. Like AriPushTest, this file does
// NOT use RefreshDatabase: the push proof relies on afterCommit dispatch (which never fires inside a rolled-back
// transaction), so rows are committed and cleaned up afterEach.

beforeEach(fn () => flushQueue());
afterEach(fn () => cleanupCommittedTenantData());

it('connect() writes status=connected and records audit + domain events', function () {
    $s = seedScenario(1);
    $channel = Channel::factory()->disconnected()->create(['property_id' => $s['property']->id]);
    expect($channel->status)->toBe('disconnected');

    app(ChannelConnectionService::class)->connect($channel);

    expect($channel->fresh()->status)->toBe('connected')
        ->and(DomainEvent::query()->where('event_type', 'channel.connected')->where('subject_id', $channel->id)->exists())->toBeTrue()
        ->and(AuditEvent::query()->where('action', 'channel.connected')->where('subject_id', $channel->id)->exists())->toBeTrue();
});

it('connect() is idempotent — a second connect records no duplicate event', function () {
    $s = seedScenario(1);
    $channel = Channel::factory()->disconnected()->create(['property_id' => $s['property']->id]);
    $service = app(ChannelConnectionService::class);

    $service->connect($channel);
    $eventCount = DomainEvent::query()->where('event_type', 'channel.connected')->where('subject_id', $channel->id)->count();

    $service->connect($channel->fresh());

    expect(DomainEvent::query()->where('event_type', 'channel.connected')->where('subject_id', $channel->id)->count())->toBe($eventCount);
});

it('markError() then disconnect() write their statuses and manage last_error', function () {
    $s = seedScenario(1);
    $channel = Channel::factory()->create(['property_id' => $s['property']->id]); // factory default: connected
    $service = app(ChannelConnectionService::class);

    $service->markError($channel, 'credential handshake failed');
    expect($channel->fresh()->status)->toBe('error')
        ->and($channel->fresh()->last_error)->toBe('credential handshake failed');

    $service->disconnect($channel->fresh());
    expect($channel->fresh()->status)->toBe('disconnected')
        ->and($channel->fresh()->last_error)->toBeNull(); // a clean transition clears the stale error
});

it('activateMapping() writes status=active and stamps last_verified_at', function () {
    $s = seedScenario(1);
    $channel = Channel::factory()->create(['property_id' => $s['property']->id]);
    $mapping = ChannelMapping::factory()->roomType($s['roomType'])->pending()->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt-x']);
    expect($mapping->status)->toBe('pending');

    app(ChannelConnectionService::class)->activateMapping($mapping);

    expect($mapping->fresh()->status)->toBe('active')
        ->and($mapping->fresh()->last_verified_at)->not->toBeNull();
});

// ⚠️ THE MUTATION PROOF — the certified channel manager is INERT until the service writes 'connected'.
it('is inert until connect(): a disconnected channel enqueues NO push; after connect() a booking pushes exactly once', function () {
    $s = seedScenario(2); // two physical units so two bookings on the same type + dates don't collide
    $channel = Channel::factory()->disconnected()->create(['property_id' => $s['property']->id]);
    ChannelMapping::factory()->roomType($s['roomType'])->create(['channel_id' => $channel->id, 'external_id' => 'chx-rt-1']);

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);

    // BEFORE — disconnected (the production reality: nothing writes 'connected'). A booking enqueues no push.
    book($s, ['unit_id' => $s['units'][0]->id]);
    expect(SyncJob::query()->where('channel_id', $channel->id)->count())->toBe(0)
        ->and($fake->pushCount())->toBe(0);

    // Connect through the real writer — the fix.
    app(ChannelConnectionService::class)->connect($channel);
    expect($channel->fresh()->status)->toBe('connected');

    // AFTER — an equivalent booking now enqueues + processes exactly one ARI push.
    book($s, ['unit_id' => $s['units'][1]->id]);
    expect(SyncJob::query()->where('channel_id', $channel->id)->count())->toBe(1)
        ->and($fake->pushCount())->toBe(1);
});
