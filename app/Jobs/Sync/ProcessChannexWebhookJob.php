<?php

declare(strict_types=1);

namespace App\Jobs\Sync;

use App\Models\Channel;
use App\Models\SyncJob;
use App\Models\WebhookEvent;
use App\Services\BookingImportService;
use InvalidArgumentException;
use Throwable;

/**
 * Processes one recorded inbound Channex webhook (Step 4) through the Step 1 reliability backbone
 * (retry/backoff/dead-letter). Reads the `webhook_events` row referenced by the sync job, routes
 * by event type to {@see BookingImportService}, and tracks the webhook_event's own status.
 *
 * The heavy lifting (import through AssignmentService) is deliberately here, not in the HTTP
 * request: the controller only validates + records + enqueues and returns fast.
 */
class ProcessChannexWebhookJob extends RetryableSyncJob
{
    protected function process(SyncJob $syncJob): void
    {
        /** @var array{webhook_event_id: string} $payload */
        $payload = $syncJob->payload_summary ?? [];

        $webhookEvent = WebhookEvent::query()->findOrFail($payload['webhook_event_id']);

        if ($webhookEvent->status === WebhookEvent::STATUS_PROCESSED) {
            return; // already applied by an earlier delivery — idempotent no-op
        }

        $webhookEvent->update(['status' => WebhookEvent::STATUS_PROCESSING]);

        try {
            $this->route($webhookEvent);

            $webhookEvent->update([
                'status' => WebhookEvent::STATUS_PROCESSED,
                'processed_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $e) {
            $webhookEvent->update([
                'status' => WebhookEvent::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);

            throw $e; // surface via the backbone (retry → dead-letter with a readable log)
        }
    }

    private function route(WebhookEvent $webhookEvent): void
    {
        $channel = Channel::query()->findOrFail($webhookEvent->channel_id);
        $importer = app(BookingImportService::class);

        /** @var array<string, mixed> $body */
        $body = $webhookEvent->payload ?? [];
        /** @var array<string, mixed> $booking */
        $booking = is_array($body['booking'] ?? null) ? $body['booking'] : [];

        match ($webhookEvent->event_type) {
            'booking', 'booking_new' => $importer->import($channel, $booking),
            'booking_modification' => $importer->modify($channel, $booking),
            'booking_cancellation' => $importer->cancel($channel, $booking),
            default => throw new InvalidArgumentException(
                "Unknown Channex webhook event_type [{$webhookEvent->event_type}]."
            ),
        };
    }
}
