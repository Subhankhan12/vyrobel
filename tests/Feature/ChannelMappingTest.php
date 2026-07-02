<?php

use App\Exceptions\TenantContextMissingException;
use App\Exceptions\UnmappedChannelEntityException;
use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Models\Reservation;
use App\Services\ChannelMappingGuard;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

// Phase 2 Step 2 — channels (OTA credential vault) + channel_mappings + the mapping guard.
// The guard is the crux: no ARI push (Step 3) / booking import (Step 4) may proceed for an
// unmapped room_type/rate_plan — it must fail LOUDLY (throw + log), never a silent no-op.
// channelScenario() lives in tests/Support/channel_helpers.php (shared with AriPushTest).

describe('mapping guard', function () {
    it('blocks an unmapped room_type with a clear, actionable error (never a silent no-op)', function () {
        ['scenario' => $s, 'channel' => $channel] = channelScenario();
        Log::spy();

        expect(fn () => app(ChannelMappingGuard::class)->resolveRoomTypeMapping($channel, $s['roomType']->id))
            ->toThrow(
                UnmappedChannelEntityException::class,
                'No active channel_mapping for room_type',
            );

        // Loud even if a caller were to swallow the exception: a warning is logged.
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $msg, array $ctx) => $msg === 'channel.mapping_guard.blocked'
                && $ctx['kind'] === 'room_type'
                && $ctx['entity_id'] === $s['roomType']->id
        )->once();
    });

    it('blocks an unmapped rate_plan with a clear error', function () {
        ['scenario' => $s, 'channel' => $channel] = channelScenario();

        expect(fn () => app(ChannelMappingGuard::class)->resolveRatePlanMapping($channel, $s['ratePlan']->id))
            ->toThrow(UnmappedChannelEntityException::class, 'No active channel_mapping for rate_plan');
    });

    it('treats a non-active mapping (pending/disabled) as unmapped and blocks it', function () {
        ['scenario' => $s, 'channel' => $channel] = channelScenario();
        ChannelMapping::factory()->roomType($s['roomType'])->pending()->create(['channel_id' => $channel->id]);

        expect(fn () => app(ChannelMappingGuard::class)->resolveRoomTypeMapping($channel, $s['roomType']->id))
            ->toThrow(UnmappedChannelEntityException::class, "mapping status is 'pending', not active");

        expect(app(ChannelMappingGuard::class)->hasActiveRoomTypeMapping($channel, $s['roomType']->id))->toBeFalse();
    });

    it('resolves an active room_type mapping and reports it via the non-throwing check', function () {
        ['scenario' => $s, 'channel' => $channel] = channelScenario();
        $mapping = ChannelMapping::factory()->roomType($s['roomType'])->create([
            'channel_id' => $channel->id,
            'external_id' => 'channex-rt-1',
        ]);

        $resolved = app(ChannelMappingGuard::class)->resolveRoomTypeMapping($channel, $s['roomType']->id);

        expect($resolved->id)->toBe($mapping->id)
            ->and($resolved->external_id)->toBe('channex-rt-1')
            ->and($resolved->isActive())->toBeTrue()
            ->and(app(ChannelMappingGuard::class)->hasActiveRoomTypeMapping($channel, $s['roomType']->id))->toBeTrue();
    });

    it('resolves an active rate_plan mapping', function () {
        ['scenario' => $s, 'channel' => $channel] = channelScenario();
        ChannelMapping::factory()->ratePlan($s['ratePlan'])->create([
            'channel_id' => $channel->id,
            'external_id' => 'channex-rp-1',
        ]);

        $resolved = app(ChannelMappingGuard::class)->resolveRatePlanMapping($channel, $s['ratePlan']->id);

        expect($resolved->external_id)->toBe('channex-rp-1')
            ->and($resolved->kind)->toBe(ChannelMapping::KIND_RATE_PLAN);
    });
});

describe('channel credential vault', function () {
    it('stores credentials encrypted at rest and decrypts them on read', function () {
        ['channel' => $channel] = channelScenario();
        $channel->update(['credentials' => 'super-secret-channex-token']);

        // Raw DB value must be ciphertext, not the plaintext token.
        $raw = DB::table('channels')->where('id', $channel->id)->value('credentials');
        expect($raw)->not->toBe('super-secret-channex-token')
            ->and($raw)->not->toContain('super-secret-channex-token');

        // Model read decrypts transparently.
        expect($channel->fresh()->credentials)->toBe('super-secret-channex-token');
    });
});

describe('tenant isolation on channels & mappings', function () {
    it('never leaks another org\'s channels or mappings', function () {
        ['scenario' => $sA, 'channel' => $channelA] = channelScenario();
        $mappingA = ChannelMapping::factory()->roomType($sA['roomType'])->create(['channel_id' => $channelA->id]);

        // Switch to a fresh org B.
        seedScenario(1);

        expect(Channel::count())->toBe(0)
            ->and(Channel::find($channelA->id))->toBeNull()
            ->and(ChannelMapping::count())->toBe(0)
            ->and(ChannelMapping::find($mappingA->id))->toBeNull();

        // Back in org A, the rows are visible again.
        app(TenantContext::class)->set($sA['org']->id);
        expect(Channel::find($channelA->id))->not->toBeNull()
            ->and(ChannelMapping::find($mappingA->id))->not->toBeNull();
    });

    it('throws when channels/mappings are queried with no tenant context', function () {
        app(TenantContext::class)->forget();

        expect(fn () => Channel::query()->count())->toThrow(TenantContextMissingException::class);
        expect(fn () => ChannelMapping::query()->count())->toThrow(TenantContextMissingException::class);
    });
});

it('closes the reservations.channel_id FK — a reservation resolves its channel', function () {
    ['scenario' => $s, 'channel' => $channel] = channelScenario();
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $reservation->update(['channel_id' => $channel->id, 'source' => 'ota']);

    expect(Reservation::find($reservation->id)->channel->id)->toBe($channel->id)
        ->and($channel->fresh()->reservations()->count())->toBe(1);
});
