<?php

declare(strict_types=1);

namespace App\Support\Ops;

use App\Jobs\Ops\QueueHeartbeatJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * DEPLOY §7.2 — the ONE definition of "is the scheduler alive, and is the queue alive".
 *
 * ⚠️ THE FAILURE THIS EXISTS FOR IS SILENT. If the crontab line that runs `schedule:run` is missing or the
 * machine's cron dies, all eight scheduled commands stop — and NOTHING reports it. No exception, no log line,
 * no user-visible symptom, until a GDPR erasure is legally overdue or `payouts:run` has not paid an owner for
 * a month. A runbook documenting the crontab cannot detect the crontab being absent; only a heartbeat can.
 *
 * ⚠️ TWO SEPARATE HEARTBEATS, DELIBERATELY, BECAUSE THEY FAIL SEPARATELY:
 *   · SCHEDULER — written directly by an every-minute scheduled closure. Fresh ⇒ cron is firing.
 *   · QUEUE — written by {@see QueueHeartbeatJob} only when a WORKER actually runs it. A
 *     dispatch that is never processed leaves this stale while the scheduler stays fresh, which is what
 *     distinguishes "cron is dead" from "Horizon is dead". One combined heartbeat could not tell them apart.
 *
 * ⚠️ THE THRESHOLDS ARE TUNED AGAINST ALERT FATIGUE, WHICH IS ITSELF A FAILURE MODE. A check that pages on a
 * single missed minute gets muted, and a muted check restores exactly the silence this replaces. Cron has
 * one-minute granularity and a deploy or a slow run can legitimately skip a beat, so the scheduler tolerates
 * five minutes; the queue tolerates fifteen, because a worker under load may defer a low-priority probe.
 */
final class Heartbeat
{
    public const SCHEDULER = 'ops:heartbeat:scheduler';

    public const QUEUE = 'ops:heartbeat:queue';

    /** Missed beats tolerated before a stall is real rather than a blip. */
    public const SCHEDULER_STALE_AFTER_SECONDS = 300;

    public const QUEUE_STALE_AFTER_SECONDS = 900;

    /** Kept far longer than the staleness window so a LONG outage still reports its true age. */
    private const TTL_SECONDS = 86400;

    public static function beat(string $key): void
    {
        Cache::put($key, CarbonImmutable::now()->toIso8601String(), self::TTL_SECONDS);
    }

    public static function lastBeat(string $key): ?CarbonImmutable
    {
        $raw = Cache::get($key);

        return is_string($raw) && $raw !== '' ? CarbonImmutable::parse($raw) : null;
    }

    /**
     * ⚠️ A heartbeat that has NEVER been written counts as STALE, not as healthy. A fresh deploy whose cron
     * was never installed is precisely the case this must catch — treating "no data" as "fine" would make the
     * check silent in the one situation it exists for.
     *
     * @return array{key: string, healthy: bool, last: ?string, age_seconds: ?int, stale_after: int}
     */
    public static function status(string $key, int $staleAfter): array
    {
        $last = self::lastBeat($key);
        $age = $last === null ? null : max(0, CarbonImmutable::now()->getTimestamp() - $last->getTimestamp());

        return [
            'key' => $key,
            'healthy' => $age !== null && $age <= $staleAfter,
            'last' => $last?->toIso8601String(),
            'age_seconds' => $age,
            'stale_after' => $staleAfter,
        ];
    }

    /** @return array{healthy: bool, scheduler: array<string, mixed>, queue: array<string, mixed>} */
    public static function report(): array
    {
        $scheduler = self::status(self::SCHEDULER, self::SCHEDULER_STALE_AFTER_SECONDS);
        $queue = self::status(self::QUEUE, self::QUEUE_STALE_AFTER_SECONDS);

        return [
            'healthy' => $scheduler['healthy'] && $queue['healthy'],
            'scheduler' => $scheduler,
            'queue' => $queue,
        ];
    }
}
