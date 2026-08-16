<?php

use App\Jobs\Ops\QueueHeartbeatJob;
use App\Support\Ops\Heartbeat;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// DEPLOY §7.2 — the scheduler/queue liveness alarm.
//
// ⚠️ THE FAILURE IT DETECTS IS SILENT. If cron stops running `schedule:run`, all eight scheduled commands
// stop — including the GDPR 14-day guest deletion, the 30-day ID purge and `payouts:run` — and nothing
// reports it. No exception, no log line, no symptom until an erasure is legally overdue or an owner was
// never paid. These tests force the stall, because an alarm nobody has triggered is decoration.
//
// ⚠️ 503, NOT A JSON FLAG. Uptime monitors alert on a non-2xx; almost none parse a body. A 200 carrying
// {"healthy":false} would report the outage to nobody, which is the current situation with extra steps.

const OPSL_ENDPOINT = '/up/liveness';

// ── 1. ⚠️ HEALTHY STAYS SILENT — no alert fatigue ─────────────────────────────────────────────────
it('answers 200 while both heartbeats are fresh', function () {
    Heartbeat::beat(Heartbeat::SCHEDULER);
    Heartbeat::beat(Heartbeat::QUEUE);

    $response = test()->get(OPSL_ENDPOINT)->assertOk();

    expect($response->json('healthy'))->toBeTrue()
        ->and($response->json('scheduler.healthy'))->toBeTrue()
        ->and($response->json('queue.healthy'))->toBeTrue();
});

// ── 2. ⚠️⚠️ A STALLED SCHEDULER ALARMS — the cron-is-dead case ────────────────────────────────────
it('answers 503 when the scheduler heartbeat goes stale', function () {
    Cache::put(Heartbeat::SCHEDULER, now()->subSeconds(Heartbeat::SCHEDULER_STALE_AFTER_SECONDS + 60)->toIso8601String(), 86400);
    Heartbeat::beat(Heartbeat::QUEUE);

    $response = test()->get(OPSL_ENDPOINT)->assertStatus(503);

    // ⚠️ AND IT NAMES WHICH HALF BROKE — the queue is fine, so an operator is not sent hunting Horizon
    // when the real fault is a missing crontab line.
    expect($response->json('scheduler.healthy'))->toBeFalse()
        ->and($response->json('queue.healthy'))->toBeTrue();
});

// ── 3. ⚠️⚠️ A STALLED QUEUE ALARMS — the Horizon-is-dead case, distinguished from the above ───────
it('answers 503 when the queue heartbeat goes stale', function () {
    Heartbeat::beat(Heartbeat::SCHEDULER);
    Cache::put(Heartbeat::QUEUE, now()->subSeconds(Heartbeat::QUEUE_STALE_AFTER_SECONDS + 60)->toIso8601String(), 86400);

    $response = test()->get(OPSL_ENDPOINT)->assertStatus(503);

    expect($response->json('queue.healthy'))->toBeFalse()
        ->and($response->json('scheduler.healthy'))->toBeTrue();
});

// ── 4. ⚠️⚠️ NEVER BEATEN IS STALE, NOT HEALTHY — the deploy that never installed cron ─────────────
it('treats a heartbeat that never existed as stalled', function () {
    Cache::forget(Heartbeat::SCHEDULER);
    Cache::forget(Heartbeat::QUEUE);

    // ⚠️ THE CASE THIS WHOLE FEATURE EXISTS FOR. Reading "no data" as "fine" would leave the check silent
    // in precisely the situation it was built to catch — a fresh deploy whose crontab line was forgotten.
    $response = test()->get(OPSL_ENDPOINT)->assertStatus(503);

    expect($response->json('healthy'))->toBeFalse()
        ->and($response->json('scheduler.last'))->toBeNull();
});

// ── 5. ⚠️ NO ALERT FATIGUE — a missed beat inside tolerance does NOT alarm ────────────────────────
it('tolerates a brief blip without alarming', function () {
    // Cron has one-minute granularity and a deploy can skip a beat. A check that pages on that gets muted,
    // and a muted check restores the silence — so tolerance is a feature, tuned not guessed.
    Cache::put(Heartbeat::SCHEDULER, now()->subSeconds(Heartbeat::SCHEDULER_STALE_AFTER_SECONDS - 60)->toIso8601String(), 86400);
    Heartbeat::beat(Heartbeat::QUEUE);

    test()->get(OPSL_ENDPOINT)->assertOk();
});

// ── 6. ⚠️⚠️ THE QUEUE PROBE IS A ROUND TRIP — dispatching is not evidence ─────────────────────────
it('stamps the queue heartbeat only when a worker actually runs the job', function () {
    Cache::forget(Heartbeat::QUEUE);

    // ⚠️ Dispatch succeeds even with every worker dead — the row just sits there. If the heartbeat were
    // stamped at dispatch, a dead Horizon would look perfectly healthy forever.
    Queue::fake();
    dispatch(new QueueHeartbeatJob);
    Queue::assertPushed(QueueHeartbeatJob::class);
    expect(Heartbeat::lastBeat(Heartbeat::QUEUE))->toBeNull();

    // Only executing it — what a live worker does — leaves the evidence.
    (new QueueHeartbeatJob)->handle();
    expect(Heartbeat::lastBeat(Heartbeat::QUEUE))->not->toBeNull();
});

// ── 7. ⚠️ THE HEARTBEATS ARE ACTUALLY SCHEDULED, every minute ─────────────────────────────────────
it('registers both heartbeats on the scheduler every minute', function () {
    $events = collect(app(Schedule::class)->events());
    $names = $events->map(fn ($e) => $e->description)->filter()->values()->all();

    expect($names)->toContain('ops-scheduler-heartbeat')
        ->and($names)->toContain('ops-queue-heartbeat');

    foreach ($events as $event) {
        if (in_array($event->description, ['ops-scheduler-heartbeat', 'ops-queue-heartbeat'], true)) {
            // ⚠️ Every minute matters: all eight real commands run daily, so at their cadence a cron that
            // died this morning would look healthy until tomorrow.
            expect($event->expression)->toBe('* * * * *');
        }
    }
});
