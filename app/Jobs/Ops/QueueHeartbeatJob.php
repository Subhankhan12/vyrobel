<?php

declare(strict_types=1);

namespace App\Jobs\Ops;

use App\Support\Ops\Heartbeat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * DEPLOY §7.2 — proves a QUEUE WORKER is actually running, not merely that a job could be dispatched.
 *
 * ⚠️ THE ROUND TRIP IS THE WHOLE POINT, AND IT IS WHY THE STAMP IS IN `handle()`. Dispatching succeeds even
 * when every worker is dead — the row simply sits in the queue. Stamping the heartbeat at construction or
 * dispatch would therefore make a dead Horizon look perfectly healthy forever, which is the exact silence
 * this feature exists to end. It is stamped here, where only a live worker can reach it: if Horizon stops,
 * this timestamp goes stale while the scheduler's stays fresh, and the report can say which half broke.
 */
class QueueHeartbeatJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Heartbeat::beat(Heartbeat::QUEUE);
    }
}
