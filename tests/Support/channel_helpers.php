<?php

use App\Models\Channel;
use App\Services\Channex\ChannexWebhookSignature;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

if (! function_exists('channelScenario')) {
    /**
     * A seedScenario() plus one connected Channel on that property (no mapping yet).
     *
     * @return array{scenario: array<string, mixed>, channel: Channel}
     */
    function channelScenario(): array
    {
        $s = seedScenario(1);
        $channel = Channel::factory()->create([
            'property_id' => $s['property']->id,
            'name' => 'Channex Main',
            'external_property_id' => 'chx-prop-1',
        ]);

        return ['scenario' => $s, 'channel' => $channel];
    }
}

if (! function_exists('channexBookingPayload')) {
    /**
     * Build a simulated Channex webhook payload (documented-guess shape — see
     * ChannexWebhookController). Override any top-level or booking key via $overrides.
     *
     * @param  array<string, mixed>  $overrides  merged into the `booking` sub-array
     * @param  array<string, mixed>  $top  merged into the top level (event, event_id)
     * @return array<string, mixed>
     */
    function channexBookingPayload(array $overrides = [], array $top = []): array
    {
        $booking = array_merge([
            'ota_reservation_id' => 'OTA-'.Str::upper(Str::random(8)),
            'currency' => 'EUR',
            'total_minor' => 20000,
            'customer' => [
                'ota_guest_id' => 'g-'.Str::random(6),
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.com',
            ],
            'rooms' => [[
                'external_room_type_id' => 'chx-rt-1',
                'external_rate_plan_id' => 'chx-rp-1',
                'checkin_date' => '2026-09-01',
                'checkout_date' => '2026-09-03',
                'occupancy' => ['adults' => 2, 'children' => 0],
                'amount_minor' => 20000,
            ]],
        ], $overrides);

        return array_merge([
            'event' => 'booking',
            'event_id' => 'evt-'.Str::upper(Str::random(10)),
            'booking' => $booking,
        ], $top);
    }
}

if (! function_exists('drainSync')) {
    /**
     * Process the `sync` queue on the `database` connection until empty — drains a webhook-import
     * job plus any ARI-push job it enqueues. Use in happy-path tests; failure/retry tests drive
     * `queue:work --once` manually so backoff/dead-letter timing is explicit.
     */
    function drainSync(): void
    {
        Artisan::call('queue:work', [
            'connection' => 'database',
            '--queue' => 'sync',
            '--stop-when-empty' => true,
        ]);
    }
}

if (! function_exists('postChannexWebhook')) {
    /**
     * POST a signed Channex webhook to a channel's endpoint. Pass $signature=false to send a
     * deliberately-bad signature (for the rejection test).
     *
     * @param  array<string, mixed>  $payload
     */
    function postChannexWebhook(Channel $channel, array $payload, bool $validSignature = true): TestResponse
    {
        $raw = json_encode($payload, JSON_THROW_ON_ERROR);
        $secret = (string) config('services.channex.webhook_secret');
        $signature = $validSignature
            ? app(ChannexWebhookSignature::class)->sign($raw, $secret)
            : 'deadbeef-invalid';

        return test()->call(
            'POST',
            "/webhooks/channex/{$channel->id}",
            [],
            [],
            [],
            [
                'HTTP_'.str_replace('-', '_', strtoupper(ChannexWebhookSignature::HEADER)) => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $raw,
        );
    }
}
