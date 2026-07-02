<?php

declare(strict_types=1);

namespace Tests\Support\Jobs;

use App\Jobs\Sync\RetryableSyncJob;
use App\Models\SyncJob;
use RuntimeException;

/**
 * Test-only double for exercising {@see RetryableSyncJob}'s retry/backoff/dead-letter machinery
 * without a real Channex/Stripe call — Phase 2 Step 1 builds the reliability harness before any
 * external integration exists. Fails on every attempt up to and including
 * `payload_summary.fail_until_attempt`, then succeeds; set it above the job's `$tries` to force
 * permanent (dead-letter) failure.
 */
class FlakySyncJob extends RetryableSyncJob
{
    protected function process(SyncJob $syncJob): void
    {
        $attempt = $syncJob->attempts + 1;
        $failUntil = (int) ($syncJob->payload_summary['fail_until_attempt'] ?? 0);

        if ($attempt <= $failUntil) {
            throw new RuntimeException("Simulated failure on attempt {$attempt}");
        }
    }
}
