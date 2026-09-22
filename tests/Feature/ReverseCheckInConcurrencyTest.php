<?php

use App\Actions\Reservations\CheckInReservation;
use App\Models\AuditEvent;
use App\Models\DomainEvent;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use Illuminate\Support\Facades\Artisan;

/**
 * ⚠️⚠️ R9 UNDER REAL CONCURRENCY — TWO OS PROCESSES, TWO CONNECTIONS, ONE WINNER.
 *
 * ⚠️⚠️ NO `RefreshDatabase`, AND THAT IS THE ENTIRE REASON THIS IS A SEPARATE FILE. RefreshDatabase wraps a
 * test in an UNCOMMITTED transaction, so a second process cannot see the fixture at all — 🔬 measured: both
 * workers returned "No query results for model [Reservation]" and the race proved nothing while looking like
 * a failure of the guard. **A concurrency test that cannot see its own fixture is the false-red twin of a
 * guard that never fires.** Committed fixtures + `cleanupCommittedTenantData()` is the shape every other
 * race test in this suite already uses.
 *
 * ⚠️ WHAT THIS PROVES THAT THE SEQUENTIAL DOUBLE-SUBMIT DOES NOT: that two transactions arriving inside the
 * `lockForUpdate` window resolve to one winner. A sequential second call only proves the guard reads
 * committed state — a different claim, and not the one the word "concurrent" makes.
 */
beforeEach(fn () => flushQueue());
afterEach(fn () => cleanupCommittedTenantData());

it('⚠️⚠️ R9(d) — two CONCURRENT reversals: one state change, one event, no duplicate rows', function () {
    Artisan::call('migrate', ['--force' => true]);

    $s = seedScenario(1);
    $r = app(CheckInReservation::class)->handle(
        book($s, ['unit_id' => $s['units'][0]->id])->fresh()
    );

    expect((string) $r->fresh()->status)->toBe('checked_in', 'the fixture must really be checked in');

    $startAt = microtime(true) + 1.5;
    $env = array_merge(getenv(), [
        'RACE_ORG_ID' => $s['org']->id,
        'RACE_RESERVATION_ID' => $r->id,
        'RACE_START_AT' => (string) $startAt,
        'QUEUE_CONNECTION' => 'sync',
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => 'vyrobel_test',
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => '',
    ]);

    $worker = base_path('tests/Support/reverse_checkin_race_worker.php');
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

    // ⚠️ EXACTLY ONE WINNER. Two means the lock did nothing; ZERO means the harness never ran — a different
    //    failure that must never read as success, which is why it is asserted as a number and not as "<= 1".
    expect($counts['reversed'] ?? 0)->toBe(1, 'exactly one process may reverse — got: '.json_encode($results));
    expect($counts['refused'] ?? 0)->toBe(1, 'the loser must be told, not silently succeed');

    // ⚠️⚠️ AND IT MUST HAVE FAILED FOR THE RIGHT REASON — this project's own rule: *an assertion that
    //    something failed is not an assertion that it failed CORRECTLY*. 🔬 The first version of this test
    //    counted a worker that died with "No query results for model [Reservation]" as a legitimate refusal,
    //    which would have passed happily on a build where the guard did not exist at all. The loser must lose
    //    the RACE, not the fixture.
    // ⚠️ `toContain()` TAKES VARIADIC NEEDLES, NOT A MESSAGE — a second argument becomes a second needle and
    //    the assertion silently demands both. 🔬 Fourth recorded occurrence in this project; it bit here too,
    //    in the very test written to enforce failing-for-the-right-reason. Use str_contains + a plain bool.
    $loser = collect($results)->firstWhere('status', 'refused');
    expect(str_contains((string) ($loser['message'] ?? ''), 'already reversed'))
        ->toBeTrue('the loser failed for the wrong reason: '.json_encode($loser));

    // ⚠️ THE ROW IS THE EVIDENCE, never the workers' own reports — a process can claim anything.
    expect((string) $r->fresh()->status)->toBe('confirmed')
        ->and(ReservationStatusHistory::query()->where('reservation_id', $r->id)
            ->where('to_status', 'confirmed')->where('reason', 'check-in reversed')->count())
        ->toBe(1, 'a second reversal row means the lock window let both through')
        ->and(DomainEvent::query()->withoutGlobalScopes()
            ->where('subject_id', $r->id)->where('event_type', 'reservation.checkin_reversed')->count())->toBe(1)
        ->and(AuditEvent::query()->withoutGlobalScopes()
            ->where('subject_id', $r->id)->where('action', 'reservation.checkin_reversed')->count())->toBe(1);
});
