<?php

declare(strict_types=1);

namespace App\Jobs\Sync;

use App\Messaging\MessageChannelResolver;
use App\Models\SyncJob;
use App\Services\InboxService;

/**
 * Processes one inbound unified-inbox message through the Step 1 reliability backbone (retry/backoff/
 * dead-letter + atomic single-run claim). Mirrors {@see ProcessChannexWebhookJob}: the heavy lifting
 * (normalise via the channel adapter → persist via InboxService) runs off the request, in the worker.
 *
 * Idempotent at BOTH levels: the {@see SyncJobDispatcher} idempotency key dedupes the job per external
 * message id, and {@see InboxService::postInbound()} dedupes the message row via createOrFirst — so a
 * duplicate delivery (or a retried job) results in exactly one message, never two.
 */
class ProcessInboundMessageJob extends RetryableSyncJob
{
    protected function process(SyncJob $syncJob): void
    {
        /** @var array{channel?: string, payload?: array<string, mixed>} $summary */
        $summary = $syncJob->payload_summary ?? [];

        $channel = (string) ($summary['channel'] ?? '');
        $raw = is_array($summary['payload'] ?? null) ? $summary['payload'] : [];

        // Normalise the raw provider payload via the channel adapter (deferred adapters throw here —
        // surfaced through the backbone as a failure, never silently swallowed), then persist idempotently.
        $normalized = app(MessageChannelResolver::class)->resolve($channel)->normalizeInbound($raw);

        app(InboxService::class)->postInbound([
            'channel' => $normalized->channel,
            'body' => $normalized->body,
            'external_thread_ref' => $normalized->externalThreadRef,
            'external_message_id' => $normalized->externalMessageId,
            'participant_identity' => $normalized->participantIdentity,
            'sender_label' => $normalized->senderLabel,
            'subject' => $normalized->subject,
        ]);
    }
}
