<?php

use App\Exceptions\Booking\InsufficientAvailabilityException;
use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Models\Guest;
use App\Models\OverbookingConflict;
use App\Models\Reservation;
use App\Models\SyncJob;
use App\Models\Unit;
use App\Models\WebhookEvent;
use App\Services\AvailabilityService;
use App\Services\Channex\ChannelManagerClient;
use App\Services\Channex\FakeChannexClient;
use Illuminate\Support\Facades\Artisan;

// Phase 2 Step 4 — inbound booking import. Like AriPushTest, this file does NOT use
// RefreshDatabase: the webhook enqueues afterCommit and the flow needs genuine commits. Each
// test uses a fresh org (channelScenario), so org-scoping isolates them. The queue runs on the
// `database` connection so the controller returns 202 fast and we drive the worker explicitly.
// All Channex interaction is against the FakeChannexClient + simulated signed payloads — no
// network. The webhook shape and HMAC scheme are documented guesses (see DECISIONS.md).

beforeEach(function () {
    config(['queue.default' => 'database']);
    flushQueue(); // shared jobs table — don't inherit jobs from another test or a prior run
});

// COMMIT real rows (no RefreshDatabase) → clean up after each test so nothing leaks into later
// tests' global counts (e.g. TenantIsolationTest's super-admin property count).
afterEach(fn () => cleanupCommittedTenantData());

/**
 * channelScenario() + active room_type & rate_plan mappings, so imports can reverse-map cleanly.
 *
 * @return array{scenario: array<string, mixed>, channel: Channel}
 */
function mappedChannelScenario(int $units = 1): array
{
    $ctx = channelScenario();
    // channelScenario seeds 1 unit; bump to $units if asked.
    for ($i = 2; $i <= $units; $i++) {
        Unit::create([
            'property_id' => $ctx['scenario']['property']->id,
            'room_type_id' => $ctx['scenario']['roomType']->id,
            'code' => 'U'.$i,
        ]);
    }
    app(AvailabilityService::class)->recompute($ctx['scenario']['roomType']->id, '2026-09-01', '2026-09-10');

    ChannelMapping::factory()->roomType($ctx['scenario']['roomType'])->create(['channel_id' => $ctx['channel']->id, 'external_id' => 'chx-rt-1']);
    ChannelMapping::factory()->ratePlan($ctx['scenario']['ratePlan'])->create(['channel_id' => $ctx['channel']->id, 'external_id' => 'chx-rp-1']);

    return $ctx;
}

it('imports exactly one OTA reservation through AssignmentService, reflects sold, and pushes ARI outward', function () {
    ['scenario' => $s, 'channel' => $channel] = mappedChannelScenario(units: 2);
    $payload = channexBookingPayload();

    postChannexWebhook($channel, $payload)->assertStatus(202);
    drainSync();

    $reservations = Reservation::query()->where('source', 'ota')->get();
    expect($reservations)->toHaveCount(1);

    $reservation = $reservations->first();
    expect($reservation->channel_id)->toBe($channel->id)
        ->and($reservation->ota_reservation_id)->toBe($payload['booking']['ota_reservation_id'])
        ->and($reservation->reservationUnits()->where('status', 'active')->count())->toBe(1)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1)
        ->and(soldOn($s['roomType']->id, '2026-09-02'))->toBe(1);

    /** @var FakeChannexClient $fake */
    $fake = app(ChannelManagerClient::class);
    expect($fake->pushCount())->toBeGreaterThanOrEqual(1); // OTA import propagates outward
    expect($fake->pushesFor($s['roomType']->id))->not->toBeEmpty();
});

it('is idempotent on the webhook event id — a redelivered webhook imports once', function () {
    ['channel' => $channel] = mappedChannelScenario();
    $payload = channexBookingPayload();

    postChannexWebhook($channel, $payload)->assertStatus(202);
    postChannexWebhook($channel, $payload)->assertStatus(202); // same event_id redelivered
    drainSync();

    expect(WebhookEvent::query()->where('external_event_id', $payload['event_id'])->count())->toBe(1)
        ->and(Reservation::query()->where('source', 'ota')->count())->toBe(1);
});

it('is idempotent on ota_reservation_id — two different events for the same booking import once', function () {
    ['channel' => $channel] = mappedChannelScenario();
    $otaId = 'OTA-SAME-1';

    postChannexWebhook($channel, channexBookingPayload(['ota_reservation_id' => $otaId]))->assertStatus(202);
    drainSync();
    // A second, distinct event that refers to the same booking.
    postChannexWebhook($channel, channexBookingPayload(['ota_reservation_id' => $otaId]))->assertStatus(202);
    drainSync();

    expect(Reservation::query()->where('source', 'ota')->where('ota_reservation_id', $otaId)->count())->toBe(1);
});

it('records an OTA overbooking as reservation + one conflict without inflating sold past capacity', function () {
    ['scenario' => $s, 'channel' => $channel] = mappedChannelScenario(units: 1); // capacity = 1

    // Fill the single unit with a DIRECT booking first.
    book($s, ['unit_id' => $s['units'][0]->id]);
    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1);

    // OTA import for the same (now full) night — accept-and-flag.
    postChannexWebhook($channel, channexBookingPayload())->assertStatus(202);
    drainSync();

    $otaReservation = Reservation::query()->where('source', 'ota')->firstOrFail();
    $line = $otaReservation->reservationUnits()->firstOrFail();

    expect($line->status)->toBe('overbooked')                              // recorded, not seated
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1)          // sold NEVER exceeds total
        ->and(soldOn($s['roomType']->id, '2026-09-02'))->toBe(1)
        ->and(OverbookingConflict::query()->where('reservation_id', $otaReservation->id)->where('status', 'open')->count())->toBe(1);
});

it('keeps the asymmetry: a DIRECT booking still rejects when full while the OTA import is accepted-and-flagged', function () {
    ['scenario' => $s, 'channel' => $channel] = mappedChannelScenario(units: 1);

    book($s, ['unit_id' => $s['units'][0]->id]); // fills capacity

    // Direct booking still REJECTS.
    expect(fn () => book($s))->toThrow(InsufficientAvailabilityException::class);

    // OTA import for the same full night is ACCEPTED (recorded + flagged), never rejected.
    postChannexWebhook($channel, channexBookingPayload())->assertStatus(202);
    drainSync();

    expect(Reservation::query()->where('source', 'ota')->count())->toBe(1)
        ->and(OverbookingConflict::query()->where('status', 'open')->count())->toBe(1)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1);
});

it('applies an OTA modification: frees the old nights and re-occupies the new ones, idempotently', function () {
    ['scenario' => $s, 'channel' => $channel] = mappedChannelScenario(units: 2);
    $otaId = 'OTA-MOD-1';

    postChannexWebhook($channel, channexBookingPayload(['ota_reservation_id' => $otaId]))->assertStatus(202);
    drainSync();
    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1)
        ->and(soldOn($s['roomType']->id, '2026-09-04'))->toBe(0);

    // Modify to shift the stay to 09-04..09-06.
    $modified = channexBookingPayload([
        'ota_reservation_id' => $otaId,
        'rooms' => [[
            'external_room_type_id' => 'chx-rt-1',
            'external_rate_plan_id' => 'chx-rp-1',
            'checkin_date' => '2026-09-04',
            'checkout_date' => '2026-09-06',
            'occupancy' => ['adults' => 2, 'children' => 0],
            'amount_minor' => 20000,
        ]],
    ], ['event' => 'booking_modification']);

    postChannexWebhook($channel, $modified)->assertStatus(202);
    drainSync();

    $reservation = Reservation::query()->where('ota_reservation_id', $otaId)->firstOrFail();
    expect(Reservation::query()->where('ota_reservation_id', $otaId)->count())->toBe(1) // still one reservation
        ->and($reservation->reservationUnits()->where('status', 'active')->count())->toBe(1)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0)  // old nights freed
        ->and(soldOn($s['roomType']->id, '2026-09-04'))->toBe(1)  // new nights occupied
        ->and(soldOn($s['roomType']->id, '2026-09-05'))->toBe(1);
});

it('applies an OTA cancellation: frees sold and is idempotent; cancel of an unknown booking is a safe no-op', function () {
    ['scenario' => $s, 'channel' => $channel] = mappedChannelScenario();
    $otaId = 'OTA-CANCEL-1';

    postChannexWebhook($channel, channexBookingPayload(['ota_reservation_id' => $otaId]))->assertStatus(202);
    drainSync();
    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1);

    postChannexWebhook($channel, channexBookingPayload(['ota_reservation_id' => $otaId], ['event' => 'booking_cancellation']))->assertStatus(202);
    drainSync();

    $reservation = Reservation::query()->where('ota_reservation_id', $otaId)->firstOrFail();
    expect($reservation->status)->toBe('cancelled')
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);

    // Cancel of a booking we never saw → safe no-op (accepted, processed, no crash, no reservation).
    postChannexWebhook($channel, channexBookingPayload(['ota_reservation_id' => 'OTA-NEVER-SEEN'], ['event' => 'booking_cancellation']))->assertStatus(202);
    drainSync();

    expect(Reservation::query()->where('ota_reservation_id', 'OTA-NEVER-SEEN')->count())->toBe(0)
        ->and(WebhookEvent::query()->where('status', WebhookEvent::STATUS_PROCESSED)->count())->toBeGreaterThanOrEqual(3);
});

it('matches an existing guest by external ref across two different bookings', function () {
    ['channel' => $channel] = mappedChannelScenario(units: 2);

    postChannexWebhook($channel, channexBookingPayload([
        'ota_reservation_id' => 'OTA-G1',
        'customer' => ['ota_guest_id' => 'guest-shared', 'first_name' => 'Sam', 'last_name' => 'Traveller'],
    ]))->assertStatus(202);
    drainSync();

    postChannexWebhook($channel, channexBookingPayload([
        'ota_reservation_id' => 'OTA-G2',
        'check_in' => '2026-09-05',
        'rooms' => [[
            'external_room_type_id' => 'chx-rt-1', 'external_rate_plan_id' => 'chx-rp-1',
            'checkin_date' => '2026-09-05', 'checkout_date' => '2026-09-07',
            'occupancy' => ['adults' => 1, 'children' => 0], 'amount_minor' => 15000,
        ]],
        'customer' => ['ota_guest_id' => 'guest-shared', 'first_name' => 'Sam', 'last_name' => 'Traveller'],
    ]))->assertStatus(202);
    drainSync();

    expect(Reservation::query()->where('source', 'ota')->count())->toBe(2)
        ->and(Guest::query()->where('external_refs->'.$channel->id, 'guest-shared')->count())->toBe(1);
});

it('rejects a webhook with an invalid signature and records nothing', function () {
    ['channel' => $channel] = mappedChannelScenario();

    postChannexWebhook($channel, channexBookingPayload(), validSignature: false)->assertStatus(401);

    expect(WebhookEvent::query()->count())->toBe(0)
        ->and(Reservation::query()->where('source', 'ota')->count())->toBe(0);
});

it('surfaces an unmapped incoming room type as a clear error without crashing or importing', function () {
    ['channel' => $channel] = channelScenario(); // NO mappings created

    postChannexWebhook($channel, channexBookingPayload())->assertStatus(202); // accepted + recorded

    // Process one attempt: the import fails loudly (not a crash, not a silent drop).
    Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'sync', '--once' => true, '--memory' => VY_QUEUE_WORKER_MEMORY_MB]);

    $syncJob = SyncJob::query()->where('type', 'booking_import')->firstOrFail();
    expect($syncJob->status)->toBe('failed')
        ->and($syncJob->last_error)->toContain('No active channel_mapping')
        ->and(Reservation::query()->where('source', 'ota')->count())->toBe(0);

    $webhookEvent = WebhookEvent::query()->firstOrFail();
    expect($webhookEvent->status)->toBe(WebhookEvent::STATUS_FAILED);
});

it('unknown channel id → 404, nothing recorded', function () {
    channelScenario();

    postChannexWebhook(new Channel(['id' => '01JUNKUNKNOWNCHANNELXXXXXX']), channexBookingPayload())
        ->assertStatus(404);

    expect(WebhookEvent::query()->count())->toBe(0);
});
