<?php

declare(strict_types=1);

namespace App\Support\Ops;

use App\Providers\AppServiceProvider;

/**
 * ⚠️ VY-33 (audit of 2026-11-24) — IS THERE A QUEUE FOR THE DASHBOARD TO SHOW?
 *
 * Horizon is a dashboard and supervisor for REDIS queues. Production runs `QUEUE_CONNECTION=database`, drained once a minute by a
 * panel cron (no Redis on that host), so Horizon there could only answer "connection refused" — and its routes were registered anyway,
 * because the package was auto-discovered. It is now registered only when this says yes ({@see AppServiceProvider}).
 *
 * ⚠️ Read through config (`queue.default`), never `env()`: production caches its config, and `env()` then returns NULL (L14).
 */
final class QueueDashboard
{
    public static function wanted(): bool
    {
        return config('queue.default') === 'redis';
    }
}
