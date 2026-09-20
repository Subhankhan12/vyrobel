<?php

declare(strict_types=1);

use App\Services\AssignmentService;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️⚠️ THE TRANSACTION ISOLATION LEVEL IS A MONEY-PATH GUARANTEE, AND UNTIL 2026-09-20 NOTHING ASSERTED IT.
 *
 * ⚠️⚠️ WHY THIS EXISTS, AND IT IS NOT A STYLE PREFERENCE. The no-double-booking guarantee is
 * {@see AssignmentService} holding `SELECT … FOR UPDATE` on `availability` rows. What that
 * statement actually DOES differs by engine AND by isolation level:
 *
 *   · 🔬 **MariaDB 11.6+ ships `innodb_snapshot_isolation=ON`.** At REPEATABLE READ a competing `FOR UPDATE`
 *     does not block-then-reject — it aborts with **error 1020, "Record has changed since last read"**. The
 *     booking path then surfaces a raw database error instead of refusing gracefully, and 🔬
 *     `DeadlockAvoidanceTest` showed a LEGITIMATE booking being lost to it.
 *   · 🔬 **At READ COMMITTED both engines block, re-read and reject cleanly** — the behaviour every
 *     concurrency test in this suite was written against.
 *
 * 🔬 **THE FULL MATRIX, MEASURED 2026-09-20 — same commit, same seed, full suite each time:**
 *
 *     MySQL 8.4.10    @ REPEATABLE READ → 3,651 pass          (the historical control)
 *     MySQL 8.4.10    @ READ COMMITTED  → 3,651 pass          ⚠️ the cell nobody had ever run
 *     MariaDB 11.8.9  @ REPEATABLE READ → concurrency FAILS, error 1020
 *     MariaDB 11.8.9  @ READ COMMITTED  → 3,648 pass          (3 failures, separately explained)
 *
 * ⚠️⚠️ **READ COMMITTED IS THE ONLY LEVEL CORRECT ON BOTH ENGINES.** It was NOT chosen because it makes
 * MariaDB pass — that would be the same mistake that created this situation, pointed the other way. It was
 * chosen after filling the missing cell, because it is the only one where both engines behave as required.
 *
 * ── ⚠️⚠️ AND THE REASON THIS IS A TEST RATHER THAN A COMMENT ─────────────────────────────────────────────
 *
 * ⚠️⚠️ **THE THING THIS GUARDS BELONGS TO SOMEBODY ELSE.** Until FIX/DEPLOY 03 the application set no
 * isolation level at all, and production was READ COMMITTED **only because Hostinger's server default happened
 * to be**. Nobody chose it, nothing recorded it, and a hosting provider could have changed it in a
 * maintenance window without telling anyone. ⚠️ It is now set explicitly in `config/database.php` via
 * `Mysql::ATTR_INIT_COMMAND`, which PDO applies at CONNECT time — so it reaches a web request, an `artisan`
 * command, a **queued job** and a **scheduled task** alike.
 *
 * ⚠️ That last part is the one that mattered: a middleware or request-scoped hook would have left the QUEUE
 * WORKER — where payment webhooks fulfil and payouts are written — running on the host default, which is
 * exactly the dependency being removed, surviving in the one place it matters most.
 *
 * 🔬 **PROVEN ON FOUR ENTRY POINTS** (2026-09-20): artisan/CLI · from inside a queued job · the litespeed web
 * SAPI on the live host · and locally with the SERVER GLOBAL DELIBERATELY SET TO REPEATABLE READ, where the
 * session still reported READ COMMITTED — which is what proves the application overrides the host rather
 * than merely agreeing with it.
 *
 * ⚠️ **DO NOT DELETE THIS BECAUSE IT LOOKS LIKE IT ASSERTS AN OBVIOUS CONSTANT.** It asserts that a setting
 * owned by a third party has been taken back. See `docs/KNOWN-LIMITS.md` L12b.
 */
it('⚠️⚠️ pins the effective transaction isolation level — the no-double-booking guarantee depends on it', function () {
    $effective = DB::selectOne('SELECT @@SESSION.transaction_isolation AS level')->level;

    expect($effective)->toBe('READ-COMMITTED',
        "⚠️⚠️ THE EFFECTIVE TRANSACTION ISOLATION LEVEL IS '{$effective}', NOT 'READ-COMMITTED'.\n"
        .'⚠️ This is not cosmetic. On MariaDB 11.6+ at REPEATABLE READ, `SELECT … FOR UPDATE` aborts with '
        .'error 1020 instead of blocking and rejecting, so the booking path throws a raw database error and '
        ."a legitimate concurrent booking can be LOST.\n"
        .'⚠️ Check `config/database.php` → the mysql/mariadb `options` → `Mysql::ATTR_INIT_COMMAND`, and the '
        .'`DB_TRANSACTION_ISOLATION` env override. ⚠️ If a host changed its default, this is the test doing '
        .'its job — do NOT adjust the expectation to match the server.');
});

/**
 * ⚠️ AND THE SETTING MUST SURVIVE A RECONNECT, WHICH IS NOT ACADEMIC HERE: 🔬 the production host drops idle
 * connections after **20 seconds** (`wait_timeout=20`). A queue worker that reconnects mid-shift must come
 * back at the same isolation level, or the guarantee quietly lapses on exactly the long-running process that
 * writes payouts. `ATTR_INIT_COMMAND` re-runs on every connect, including reconnects — this asserts it.
 */
it('⚠️ keeps the isolation level across a reconnect — the host drops idle connections after 20s', function () {
    $before = DB::selectOne('SELECT @@SESSION.transaction_isolation AS level')->level;

    DB::disconnect();
    DB::reconnect();

    $after = DB::selectOne('SELECT @@SESSION.transaction_isolation AS level')->level;

    expect($after)->toBe($before)
        ->and($after)->toBe('READ-COMMITTED',
            'the isolation level did not survive a reconnect — a reconnecting queue worker would silently '
            .'fall back to the host default, which is the exact dependency this removes');
});
