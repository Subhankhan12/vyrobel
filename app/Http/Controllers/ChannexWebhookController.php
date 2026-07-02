<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\Sync\ProcessChannexWebhookJob;
use App\Models\Channel;
use App\Models\WebhookEvent;
use App\Services\Channex\ChannexWebhookSignature;
use App\Services\SyncJobDispatcher;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public (unauthenticated) inbound Channex webhook endpoint (Step 4). Does the MINIMUM in the
 * request: resolve channel → validate signature → dedupe (webhook_events) → enqueue processing →
 * return fast. NO inventory work here; the import runs later in {@see ProcessChannexWebhookJob}.
 *
 * Runs outside any tenant context (no authenticated user). It resolves the channel under an
 * explicit super-admin context, then sets the tenant context to that channel's org for the
 * tenant-scoped webhook_events write — never a guard bypass leak. The route is CSRF-exempt
 * (stateless, signature-authenticated) and does NOT use route-model binding (that would trip the
 * tenant scope before context is set — the Phase 1 Step 9 gotcha).
 */
class ChannexWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $channel,
        TenantContext $tenant,
        ChannexWebhookSignature $signature,
        SyncJobDispatcher $dispatcher,
    ): JsonResponse {
        $channelModel = $tenant->runAsSuperAdmin(fn () => Channel::query()->find($channel));

        if ($channelModel === null) {
            return response()->json(['error' => 'unknown_channel'], 404);
        }

        if (! $signature->verify($request->getContent(), $request->header(ChannexWebhookSignature::HEADER))) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        /** @var array<string, mixed> $body */
        $body = $request->json()->all();
        $externalEventId = is_string($body['event_id'] ?? null) ? $body['event_id'] : null;
        $eventType = is_string($body['event'] ?? null) ? $body['event'] : null;

        if ($externalEventId === null || $eventType === null) {
            return response()->json(['error' => 'malformed_payload'], 422);
        }

        return $tenant->forOrganization((string) $channelModel->organization_id, function () use (
            $channelModel,
            $dispatcher,
            $body,
            $externalEventId,
            $eventType,
        ): JsonResponse {
            // createOrFirst (not firstOrCreate) so two CONCURRENT deliveries of the same event
            // resolve to one row: the unique(source, external_event_id) index turns the losing
            // INSERT into a fetch instead of a 500 (proven by the Step 5 concurrency suite).
            $webhookEvent = WebhookEvent::createOrFirst(
                ['source' => 'channex', 'external_event_id' => $externalEventId],
                [
                    'channel_id' => $channelModel->getKey(),
                    'event_type' => $eventType,
                    'signature_verified' => true,
                    'payload' => $body,
                    'status' => WebhookEvent::STATUS_RECEIVED,
                    'received_at' => now(),
                ],
            );

            // Redelivery of an already-recorded event: acknowledge, don't re-enqueue.
            if ($webhookEvent->wasRecentlyCreated) {
                $dispatcher->dispatch(
                    type: 'booking_import',
                    direction: 'inbound',
                    idempotencyKey: "channex_webhook:{$channelModel->getKey()}:{$externalEventId}",
                    jobClass: ProcessChannexWebhookJob::class,
                    payloadSummary: ['webhook_event_id' => $webhookEvent->getKey()],
                    channelId: (string) $channelModel->getKey(),
                );
            }

            return response()->json(['status' => 'accepted'], 202);
        });
    }
}
