<?php

use App\Models\SyncJob;
use App\Models\SyncLog;
use App\Services\SyncJobDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Support\Jobs\FlakySyncJob;

// Phase 2 Step 1 — the sync reliability backbone, proven in isolation (no Channex/Stripe call
// exists yet — Steps 3-4 wire the real thing behind this same machinery). Obligations:
// idempotent enqueue, enqueue -> process -> succeed, retry with backoff, dead-letter after max
// attempts (never applied twice), and idempotent execution on redelivery.

describe('idempotent enqueue', function () {
    uses(RefreshDatabase::class);

    it('collapses a repeated idempotency key into one sync_jobs row and one dispatch', function () {
        Queue::fake();
        $org = newDemoOrg();
        $key = 'idem-'.Str::random(12);

        $first = app(SyncJobDispatcher::class)->dispatch(
            type: 'test_op',
            direction: 'outbound',
            idempotencyKey: $key,
            jobClass: FlakySyncJob::class,
        );
        $second = app(SyncJobDispatcher::class)->dispatch(
            type: 'test_op',
            direction: 'outbound',
            idempotencyKey: $key,
            jobClass: FlakySyncJob::class,
        );

        expect($first->id)->toBe($second->id)
            ->and($second->wasRecentlyCreated)->toBeFalse()
            ->and(SyncJob::where('idempotency_key', $key)->count())->toBe(1)
            ->and($org)->not->toBeNull();

        Queue::assertPushed(FlakySyncJob::class, 1);
    });
});

// The remaining tests exercise the real `database` queue connection end-to-end (dispatch,
// worker pop, retry backoff via real time-travel, dead-letter). They intentionally do NOT use
// RefreshDatabase: SyncJobDispatcher dispatches `afterCommit()`, which never fires inside a
// transaction that gets rolled back at test end, so these need genuine commits (same reasoning
// as the true-parallel concurrency tests, which also opt out — see ConcurrentBookingTest).

it('processes a pending sync job to success and records one info attempt', function () {
    $org = newDemoOrg();
    $syncJob = SyncJob::create([
        'organization_id' => $org->id,
        'direction' => 'outbound',
        'type' => 'test_op',
        'idempotency_key' => 'run-'.Str::random(16),
        'status' => 'pending',
        'attempts' => 0,
        'max_attempts' => 5,
        'payload_summary' => ['fail_until_attempt' => 0],
    ]);

    FlakySyncJob::dispatch($org->id, $syncJob->id)->onConnection('database')->onQueue('sync');
    Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'sync', '--once' => true]);

    $syncJob->refresh();

    expect($syncJob->status)->toBe('succeeded')
        ->and($syncJob->attempts)->toBe(1)
        ->and($syncJob->processed_at)->not->toBeNull()
        ->and($syncJob->last_error)->toBeNull();

    $logs = SyncLog::where('sync_job_id', $syncJob->id)->get();
    expect($logs)->toHaveCount(1)
        ->and($logs->first()->level)->toBe('info')
        ->and($logs->first()->attempt)->toBe(1);
});

it('retries a failing sync job with backoff and eventually succeeds', function () {
    $org = newDemoOrg();
    $syncJob = SyncJob::create([
        'organization_id' => $org->id,
        'direction' => 'outbound',
        'type' => 'test_op',
        'idempotency_key' => 'retry-'.Str::random(16),
        'status' => 'pending',
        'attempts' => 0,
        'max_attempts' => 5,
        'payload_summary' => ['fail_until_attempt' => 2], // fails attempts 1 & 2, succeeds on 3
    ]);

    FlakySyncJob::dispatch($org->id, $syncJob->id)->onConnection('database')->onQueue('sync');

    for ($i = 0; $i < 3; $i++) {
        Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'sync', '--once' => true]);
        $syncJob->refresh();

        if ($syncJob->status === 'succeeded') {
            break;
        }

        // The dashboard-facing "next retry at" marker should be set while a retry is pending.
        expect($syncJob->status)->toBe('failed')
            ->and($syncJob->available_at)->not->toBeNull()
            ->and($syncJob->available_at->isFuture())->toBeTrue();

        // Jump past the longest backoff tier so the released job is available on the next pop,
        // without the test actually waiting in real time.
        $this->travel(400)->seconds();
    }

    expect($syncJob->status)->toBe('succeeded')
        ->and($syncJob->attempts)->toBe(3)
        ->and($syncJob->dead_lettered_at)->toBeNull();

    $logs = SyncLog::where('sync_job_id', $syncJob->id)->orderBy('attempt')->get();
    expect($logs)->toHaveCount(3)
        ->and($logs->pluck('level')->all())->toBe(['error', 'error', 'info'])
        ->and($logs->pluck('attempt')->all())->toBe([1, 2, 3]);
});

it('moves a permanently failing sync job to dead-letter after max attempts and never reprocesses it', function () {
    $org = newDemoOrg();
    $syncJob = SyncJob::create([
        'organization_id' => $org->id,
        'direction' => 'outbound',
        'type' => 'test_op',
        'idempotency_key' => 'dead-'.Str::random(16),
        'status' => 'pending',
        'attempts' => 0,
        'max_attempts' => 5,
        'payload_summary' => ['fail_until_attempt' => 99], // always fails
    ]);

    FlakySyncJob::dispatch($org->id, $syncJob->id)->onConnection('database')->onQueue('sync');

    // tries = 5 on RetryableSyncJob: 5 real attempts, then the worker permanently fails it.
    for ($i = 0; $i < 5; $i++) {
        Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'sync', '--once' => true]);
        $this->travel(400)->seconds();
    }

    $syncJob->refresh();

    expect($syncJob->status)->toBe('dead_letter')
        ->and($syncJob->attempts)->toBe(5)
        ->and($syncJob->dead_lettered_at)->not->toBeNull()
        ->and($syncJob->available_at)->toBeNull(); // no retry coming — nothing left to schedule

    // 5 per-attempt error logs + 1 dead-letter summary from failed().
    expect(SyncLog::where('sync_job_id', $syncJob->id)->count())->toBe(6);

    // Simulate an at-least-once redelivery of the same underlying sync job after dead-letter:
    // running the job's own handle() again must be a pure no-op.
    app()->call([new FlakySyncJob($org->id, $syncJob->id), 'handle']);

    $syncJob->refresh();
    expect($syncJob->status)->toBe('dead_letter')
        ->and($syncJob->attempts)->toBe(5)
        ->and(SyncLog::where('sync_job_id', $syncJob->id)->count())->toBe(6);
});

it('does not reprocess a sync job that already succeeded', function () {
    $org = newDemoOrg();
    $syncJob = SyncJob::create([
        'organization_id' => $org->id,
        'direction' => 'outbound',
        'type' => 'test_op',
        'idempotency_key' => 'succ-'.Str::random(16),
        'status' => 'pending',
        'attempts' => 0,
        'max_attempts' => 5,
        'payload_summary' => ['fail_until_attempt' => 0],
    ]);

    FlakySyncJob::dispatch($org->id, $syncJob->id)->onConnection('database')->onQueue('sync');
    Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'sync', '--once' => true]);

    $syncJob->refresh();
    expect($syncJob->status)->toBe('succeeded')->and($syncJob->attempts)->toBe(1);

    // A duplicate/redelivered execution of the same sync job must not run process() again.
    app()->call([new FlakySyncJob($org->id, $syncJob->id), 'handle']);

    $syncJob->refresh();
    expect($syncJob->attempts)->toBe(1)
        ->and(SyncLog::where('sync_job_id', $syncJob->id)->count())->toBe(1);
});
