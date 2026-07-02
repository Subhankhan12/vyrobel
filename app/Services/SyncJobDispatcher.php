<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\Sync\RetryableSyncJob;
use App\Models\SyncJob;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * The single entry point for enqueuing channel-sync work (ARI push, booking import/modify/
 * cancel — Steps 3-4). Centralizes the idempotency guarantee: a caller enqueuing the same
 * logical operation twice (retry, duplicate webhook, re-triggered recompute) collapses to one
 * `sync_jobs` row and exactly one queued dispatch, via a unique `idempotency_key`.
 *
 * Dispatches `afterCommit()` so a job can never be picked up by a worker before the enqueuing
 * transaction (e.g. the `AssignmentService` write it reacts to) has actually committed. Relies
 * on `SyncJob`'s `BelongsToOrganization` guard to require an active tenant context on create.
 */
class SyncJobDispatcher
{
    /**
     * @param  class-string<RetryableSyncJob>  $jobClass
     * @param  array<string, mixed>  $payloadSummary
     */
    public function dispatch(
        string $type,
        string $direction,
        string $idempotencyKey,
        string $jobClass,
        array $payloadSummary = [],
        ?string $channelId = null,
        string $queue = 'sync',
    ): SyncJob {
        $attributes = [
            'channel_id' => $channelId,
            'direction' => $direction,
            'type' => $type,
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => 5,
            'payload_summary' => $payloadSummary,
        ];

        try {
            $syncJob = DB::transaction(
                fn (): SyncJob => SyncJob::firstOrCreate(['idempotency_key' => $idempotencyKey], $attributes)
            );
        } catch (UniqueConstraintViolationException) {
            // Two concurrent callers raced firstOrCreate's SELECT and both attempted the INSERT;
            // the unique index caught it. The loser just observes the winner's row — still exactly
            // one sync_jobs row and one dispatch for this idempotency key.
            $syncJob = SyncJob::where('idempotency_key', $idempotencyKey)->firstOrFail();
        }

        // Guard enforces a tenant context on creation, so organization_id is always set here.
        if ($syncJob->wasRecentlyCreated) {
            $jobClass::dispatch($syncJob->organization_id, $syncJob->id)
                ->onQueue($queue)
                ->afterCommit();
        }

        return $syncJob;
    }
}
