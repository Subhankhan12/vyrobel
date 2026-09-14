<?php

use App\Models\LoginActivity;
use App\Support\Retention\RetentionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ══ AUDIT 2026-08-29 · M-6 — RETENTION ═══════════════════════════════════════════════════════════════
//
// ⚠️ Nine tables grew forever while the scheduler ran eight commands and two heartbeats, not one a prune.
//
// ⚠️ "0 rows pruned" AND "the query is broken" LOOK IDENTICAL (§N.27.7). Every prune below is therefore
// proven on a NON-EMPTY table, with a row one day INSIDE the window that must SURVIVE — an off-by-one on a
// retention window is a data-loss bug, and it is invisible against an empty table.

/** Insert one row into a retention-swept store, aged $days old. Returns its id. */
function seedAgedRow(string $table, int $days): string
{
    $id = (string) Str::ulid();
    $at = now()->subDays($days);

    match ($table) {
        'failed_jobs' => DB::table('failed_jobs')->insert([
            'uuid' => $id, 'connection' => 'redis', 'queue' => 'default',
            'payload' => '{}', 'exception' => 'x', 'failed_at' => $at,
        ]),
        'sessions' => DB::table('sessions')->insert([
            'id' => $id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test',
            'payload' => 'x', 'last_activity' => $at->getTimestamp(),
        ]),
        'notifications' => DB::table('notifications')->insert([
            'id' => $id, 'type' => 'T', 'notifiable_type' => 'App\Models\User',
            'notifiable_id' => (string) Str::ulid(), 'data' => '{}',
            'created_at' => $at, 'updated_at' => $at,
        ]),
        'webhook_events' => DB::table('webhook_events')->insert([
            'id' => $id, 'source' => 'stripe', 'external_event_id' => 'evt_'.$id,
            'event_type' => 't', 'signature_verified' => 1, 'payload' => '{}',
            'status' => 'processed', 'received_at' => $at, 'created_at' => $at, 'updated_at' => $at,
        ]),
        'llm_usage' => DB::table('llm_usage')->insert([
            'id' => $id, 'usage_date' => $at->toDateString(),
            'created_at' => $at, 'updated_at' => $at,
        ]),
        default => DB::table($table)->insert(['id' => $id, 'created_at' => $at, 'updated_at' => $at]),
    };

    return $id;
}

function rowExists(string $table, string $id): bool
{
    $key = $table === 'failed_jobs' ? 'uuid' : 'id';

    return DB::table($table)->where($key, $id)->exists();
}

// ── 1. ⛔ EVERY SWEPT STORE: old rows go, a row ONE DAY INSIDE the window SURVIVES ───────────────────
it('prunes past the window and keeps a row one day inside it', function () {
    $cases = [];

    foreach (RetentionPolicy::windows() as $table => $rule) {
        // export_downloads has NOT-NULL columns a bare insert cannot satisfy; covered by test 4 instead.
        if ($table === 'export_downloads') {
            continue;
        }

        $cases[$table] = [
            'stale' => seedAgedRow($table, $rule['days'] + 1),   // one day PAST  → must be deleted
            'fresh' => seedAgedRow($table, $rule['days'] - 1),   // one day INSIDE → must SURVIVE
        ];
    }

    expect($cases)->not->toBeEmpty();   // the sweep must actually have stores to prove

    $this->artisan('retention:prune')->assertExitCode(0);

    foreach ($cases as $table => $ids) {
        expect(rowExists($table, $ids['stale']))->toBeFalse("{$table}: a row PAST the window survived");
        expect(rowExists($table, $ids['fresh']))->toBeTrue("{$table}: a row INSIDE the window was DELETED — off by one");
    }
});

// ── 2. ⛔ NEVER PRUNED — audit_events and domain_events are EVIDENCE ─────────────────────────────────
// ⚠️ A TEST, NOT A COMMENT. A table with no prune beside seven that have one looks like an oversight, and
// someone will "tidy" it. Adding either to the policy fails the build here.
it('never prunes the evidence tables', function () {
    foreach (RetentionPolicy::NEVER_PRUNED as $table) {
        // ⚠️ BOTH the raw declaration AND the filtered accessor. Checking only windows() was a hole in a
        // first version of this test: array_diff_key() silently removes a never-pruned table again, so
        // ADDING one to WINDOWS passed. Caught by mutating and disbelieving the clean result.
        expect(array_key_exists($table, RetentionPolicy::WINDOWS))
            ->toBeFalse("{$table} was ADDED to RetentionPolicy::WINDOWS. It is EVIDENCE — how a disputed owner figure is reconstructed, and a dispute surfaces late. Its size problem is the created_at index, not deletion.");

        expect(array_key_exists($table, RetentionPolicy::windows()))
            ->toBeFalse("{$table} is reachable by the sweep.");
    }

    // And prove it end to end: an ancient audit row survives a real sweep.
    $id = (string) Str::ulid();
    DB::table('audit_events')->insert([
        'id' => $id, 'organization_id' => (string) Str::ulid(), 'actor_type' => 'system',
        'action' => 'test.evidence', 'subject_type' => 'T', 'subject_id' => 'x',
        'created_at' => now()->subYears(5),
    ]);

    $this->artisan('retention:prune')->assertExitCode(0);

    expect(DB::table('audit_events')->where('id', $id)->exists())->toBeTrue();
});

// ── 3. ⛔ NO MONEY TABLE IS REACHABLE by the sweep ───────────────────────────────────────────────────
it('cannot reach any money table', function () {
    $money = ['owner_ledger', 'owner_statements', 'owner_statement_lines', 'payouts', 'payments', 'folios', 'folio_items', 'reservations'];

    foreach ($money as $table) {
        expect(array_key_exists($table, RetentionPolicy::WINDOWS))->toBeFalse("{$table} is a money table and must never be swept");
    }
});

// ── 4. batching drains more than one batch, and the sweep reports honestly ───────────────────────────
it('deletes in batches and drains a backlog larger than one batch', function () {
    // ⚠️ COUNT ONLY THE ROWS THIS TEST OWNS. A first version asserted the WHOLE table was 1205 then 0, which
    // passed alone and failed in the full suite (1215 — ten notifications from elsewhere). An exact
    // whole-table count is a hidden coupling to global state; the subject here is the BATCHING, not the table.
    $mine = (string) Str::ulid();
    $stale = [];

    for ($i = 0; $i < 1205; $i++) {                       // > BATCH (1000), so it must loop
        $id = (string) Str::ulid();
        $stale[] = $id;
        DB::table('notifications')->insert([
            'id' => $id, 'type' => 'T', 'notifiable_type' => 'App\Models\User', 'notifiable_id' => $mine,
            'data' => '{}', 'created_at' => now()->subDays(200), 'updated_at' => now()->subDays(200),
        ]);
    }

    // A fresh row of MINE, to prove the sweep drained the backlog without taking everything.
    $fresh = (string) Str::ulid();
    DB::table('notifications')->insert([
        'id' => $fresh, 'type' => 'T', 'notifiable_type' => 'App\Models\User', 'notifiable_id' => $mine,
        'data' => '{}', 'created_at' => now()->subDays(1), 'updated_at' => now(),
    ]);

    expect(DB::table('notifications')->where('notifiable_id', $mine)->count())->toBe(1206);

    $this->artisan('retention:prune')->assertExitCode(0);

    // Every stale row gone — all 1205, which cannot happen in a single 1000-row batch.
    expect(DB::table('notifications')->whereIn('id', $stale)->count())->toBe(0);
    // …and the recent one survived.
    expect(DB::table('notifications')->where('id', $fresh)->exists())->toBeTrue();
});

// ── 4b. ⛔ THE WINDOWS ARE PINNED, NOT DERIVED ──────────────────────────────────────────────────────
// ⚠️ Test 1 seeds its fixture from $rule['days'], so it ADAPTS to any change and can never notice one —
// "an assertion satisfiable by copying a value". Proven: widening notifications 90→91 left it green.
// These are the founder-approved numbers, written out, so changing one is a deliberate visible act.
it('pins each approved retention window', function () {
    $days = array_map(fn (array $r): int => $r['days'], RetentionPolicy::WINDOWS);

    expect($days)->toBe([
        'failed_jobs' => 7,        // ⚠️ a PRIVACY window: statement PDF + recipient email in the payload
        'notifications' => 90,
        'sessions' => 30,          // ⚠️ » the 120-MINUTE session lifetime, so it cannot log anyone out
        'webhook_events' => 90,    // ⚠️ » Stripe's ~3-day retry window; pruning a dedupe key early = double-post
        // ⚠️ A NIGHTLY REPORT IS NOT EVIDENCE. `audit_events`/`domain_events` are never pruned because they
        //    record what HAPPENED; a reconciliation run records what was TRUE at 04:00, which stops being
        //    interesting long before it stops taking space. 90 days keeps a full quarter of history so a
        //    drift that appears and disappears is still visible, and matches the other report-shaped tables.
        'reconciliation_runs' => 90,
        'export_downloads' => 90,
        'llm_usage' => 90,
    ]);
});

// ── 5. login_activity is NOT duplicated here — one window, one owner ────────────────────────────────
it('does not duplicate the existing login-activity window', function () {
    expect(array_key_exists('login_activity', RetentionPolicy::WINDOWS))->toBeFalse();
    expect(LoginActivity::RETENTION_DAYS)->toBe(90);   // still owned by platform:prune-activity
});
