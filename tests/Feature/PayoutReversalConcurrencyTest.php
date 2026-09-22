<?php

declare(strict_types=1);

use App\Models\Payout;
use Illuminate\Support\Facades\Artisan;

/**
 * ⚠️⚠️ F-53's CONCURRENT HALF — the one the sequential repair tests could not reach.
 *
 * ⚠️⚠️ THIS FILE EXISTS BECAUSE A MUTATION REFUSED TO REDDEN. 🔬 Measured 2026-09-23: removing
 * `lockForUpdate()` from the repaired reversal left `PayoutReversalRepairedTest` **green, 8/8**. The
 * sequential double-press proves the re-check reads COMMITTED state; it says nothing about two transactions
 * racing inside the lock window. **Those are different claims, and only this one is about concurrency** —
 * so until this file existed, the lock in a money path was not under test at all.
 *
 * ⚠️⚠️ NO `RefreshDatabase`, AND THAT IS THE ENTIRE REASON THIS IS A SEPARATE FILE — the same shape
 * `ReverseCheckInConcurrencyTest` uses. RefreshDatabase wraps the test in an UNCOMMITTED transaction, so a
 * separate worker process cannot see the fixture at all: both workers would die on "no query results" and
 * the race would prove nothing while looking like a guard that never fires.
 *
 * ⚠️ The workers drive the PROTECTED `performReversal()` through a test-only subclass, because `reverse()`
 * is quarantined under the OPEN ruling **R-REVERSE**. ⚠️ **The quarantine is not lifted here.**
 */
afterEach(fn () => cleanupCommittedTenantData());

it('⚠️⚠️ F-53 — two CONCURRENT reversals: ONE reversal record, one winner, the source terminal', function () {
    Artisan::call('migrate', ['--force' => true]);

    $c = purWorld();
    $paid = $c['paid'];

    expect((string) $paid->fresh()->status)->toBe('paid', 'the fixture must really be a paid payout');

    $startAt = microtime(true) + 1.5;
    $env = array_merge(getenv(), [
        'RACE_ORG_ID' => $c['statement']->organization_id,
        'RACE_PAYOUT_ID' => $paid->getKey(),
        'RACE_START_AT' => (string) $startAt,
        'QUEUE_CONNECTION' => 'sync',
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => 'vyrobel_test',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
    ]);

    $worker = base_path('tests/Support/payout_reversal_race_worker.php');
    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

    $procs = $pipes = [];
    foreach ([0, 1] as $i) {
        $procs[$i] = proc_open([PHP_BINARY, $worker], $descriptors, $pipes[$i], base_path(), $env);
    }

    $results = [];
    foreach ($procs as $i => $proc) {
        $out = stream_get_contents($pipes[$i][1]);
        fclose($pipes[$i][1]);
        fclose($pipes[$i][2]);
        proc_close($proc);
        $decoded = json_decode(trim((string) $out), true);
        $results[$i] = is_array($decoded) ? $decoded : ['status' => 'parse_error', 'raw' => $out];
    }

    $counts = array_count_values(array_column($results, 'status'));

    // ⚠️ EXACTLY ONE WINNER. Two means the lock did nothing — which IS F-53. Zero means the harness never
    //    ran, a different failure that must never read as success, so it is asserted as a number.
    expect($counts['reversed'] ?? 0)->toBe(1, 'exactly one process may reverse — got: '.json_encode($results))
        ->and($counts['refused'] ?? 0)->toBe(1, 'the loser must be told, not silently succeed');

    // ⚠️ AND THE LOSER MUST HAVE LOST THE RACE, NOT ITS FIXTURE — the same discipline as R9-MSG. The refusal
    //    must name the status it found; an infrastructure death must never count as a legitimate refusal.
    $loser = collect($results)->firstWhere('status', 'refused');
    $message = (string) ($loser['message'] ?? '');

    expect(str_contains($message, 'reversed'))
        ->toBeTrue('the refusal must name the status it found: '.json_encode($loser));

    foreach (['No query results', 'SQLSTATE', 'Undefined', 'Call to a member function'] as $infrastructure) {
        expect(str_contains($message, $infrastructure))
            ->toBeFalse("the loser died on infrastructure, it did not lose the race: {$message}");
    }

    // ⚠️⚠️ THE ROWS ARE THE EVIDENCE, never the workers' own reports — a process can claim anything.
    expect(Payout::query()->withoutGlobalScopes()->where('supersedes_id', $paid->getKey())->count())
        ->toBe(1, 'a second reversal record means the lock window let both through — that is F-53')
        ->and((string) Payout::query()->withoutGlobalScopes()->whereKey($paid->getKey())->value('status'))
        ->toBe('reversed', 'the source must have left LIVE_STATUSES exactly once');

    // ⚠️ And the statement is claimed by exactly one live payout — F-54's invariant, under contention.
    expect(Payout::query()->withoutGlobalScopes()
        ->where('owner_statement_id', $c['statement']->id)
        ->whereIn('status', Payout::LIVE_STATUSES)->count())
        ->toBe(0, 'after a reversal nothing live may still claim the statement');
});
