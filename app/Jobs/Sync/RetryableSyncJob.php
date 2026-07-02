<?php

declare(strict_types=1);

namespace App\Jobs\Sync;

use App\Models\SyncJob;
use App\Models\SyncLog;
use App\Support\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * The reliability base class every channel-sync job extends (ARI push, booking import/modify/
 * cancel — Steps 3-4). Built in Step 1, before any real Channex call, to prove the machinery in
 * isolation: retry with exponential backoff, dead-letter after exhaustion, and an idempotent,
 * concurrency-safe claim on the underlying {@see SyncJob} row so two workers can never both
 * run {@see process()} for the same sync job.
 *
 * Subclasses receive an `organizationId` because the job crosses a queue-worker process
 * boundary with no ambient {@see TenantContext} — the whole body runs inside
 * `TenantContext::forOrganization()` so `SyncJob`/`SyncLog` (both tenant-scoped via
 * `BelongsToOrganization`) can be touched safely, and the guard still protects against a
 * mismatched/missing org.
 */
abstract class RetryableSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public readonly string $organizationId,
        public readonly string $syncJobId,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300];
    }

    final public function handle(TenantContext $tenant): void
    {
        $tenant->forOrganization($this->organizationId, function (): void {
            $syncJob = SyncJob::findOrFail($this->syncJobId);

            if ($syncJob->status === 'succeeded') {
                return; // already completed by an earlier delivery — idempotent no-op
            }

            // Atomically claim the row so a concurrent/duplicate delivery of the same job
            // can't run process() twice. Only one worker's UPDATE affects a row.
            $claimed = SyncJob::whereKey($syncJob->getKey())
                ->whereIn('status', ['pending', 'failed'])
                ->update(['status' => 'processing']);

            if ($claimed === 0) {
                return; // already claimed, processing, succeeded, or dead-lettered elsewhere
            }

            $syncJob->refresh();
            $attempt = $syncJob->attempts + 1;

            try {
                $this->process($syncJob);

                $syncJob->update([
                    'status' => 'succeeded',
                    'attempts' => $attempt,
                    'processed_at' => now(),
                    'last_error' => null,
                ]);

                $this->log($syncJob, $attempt, 'info', 'Sync job succeeded.');
            } catch (Throwable $e) {
                $updates = [
                    'status' => 'failed',
                    'attempts' => $attempt,
                    'last_error' => $e->getMessage(),
                ];

                // Surface the next retry time for the mapping-health/sync-error dashboard
                // (Steps 2-4), mirroring the backoff index the queue worker itself will use.
                if ($attempt < $this->tries) {
                    $backoff = $this->backoff();
                    $delay = $backoff[$attempt - 1] ?? end($backoff);
                    $updates['available_at'] = now()->addSeconds($delay);
                }

                $syncJob->update($updates);

                $this->log($syncJob, $attempt, 'error', $e->getMessage());

                throw $e; // let the queue worker decide retry (backoff) vs failed() (exhausted)
            }
        });
    }

    public function failed(Throwable $e): void
    {
        app(TenantContext::class)->forOrganization($this->organizationId, function () use ($e): void {
            $syncJob = SyncJob::find($this->syncJobId);

            if ($syncJob === null || $syncJob->status === 'succeeded') {
                return;
            }

            $syncJob->update([
                'status' => 'dead_letter',
                'dead_lettered_at' => now(),
                'available_at' => null,
                'last_error' => $e->getMessage(),
            ]);

            $this->log(
                $syncJob,
                $syncJob->attempts,
                'error',
                "Moved to dead-letter after {$syncJob->attempts} attempt(s): {$e->getMessage()}"
            );
        });
    }

    abstract protected function process(SyncJob $syncJob): void;

    /**
     * An extra informational log line a subclass can write from inside process() without
     * affecting status/attempts — e.g. "skipped, unmapped" (a handled condition, not a failure).
     *
     * @param  array<string, mixed>  $context
     */
    protected function note(SyncJob $syncJob, string $level, string $message, array $context = []): void
    {
        $this->log($syncJob, $syncJob->attempts + 1, $level, $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(SyncJob $syncJob, int $attempt, string $level, string $message, array $context = []): void
    {
        SyncLog::create([
            'organization_id' => $syncJob->organization_id,
            'sync_job_id' => $syncJob->id,
            'attempt' => $attempt,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }
}
