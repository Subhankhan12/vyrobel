<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

/**
 * ⚠️⚠️ ONE LIVE PAYOUT PER STATEMENT — AT THE DATABASE, NOT ONLY IN THE APPLICATION.
 *
 * The rule already existed and was carefully written: `OwnerPayoutService::createFromStatement()` and
 * `retry()` both take a row lock and re-check `whereIn('status', Payout::LIVE_STATUSES)` inside it. ⚠️ **But
 * `SHOW INDEX FROM payouts` returned no UNIQUE index except `PRIMARY`** — so the invariant was enforced
 * entirely by application code.
 *
 * ⚠️ **AN APPLICATION-ONLY INVARIANT IS NOT A CONSTRAINT.** It holds only for callers that go through that
 * code, under that lock, in that order. A second entry point, a future job, a console command, a raw insert
 * during a migration or a backfill — any of them can write a second live payout for one statement, and the
 * result is a DOUBLE PAYMENT to an owner. The lock narrows the window; it does not close the class.
 *
 * ⚠️ MYSQL HAS NO PARTIAL INDEX, so "unique among LIVE rows only" is expressed with a VIRTUAL GENERATED
 * COLUMN: it carries `owner_statement_id` while the row is live and NULL otherwise, and MySQL's unique
 * indexes do not collide on NULL. ⚠️ VIRTUAL rather than STORED deliberately — a STORED column forces a
 * full table rebuild, which MySQL refuses on this table with error 1215 because of its existing foreign
 * keys. A virtual column is computed on read and is still indexable, which is all this needs. So:
 *   • two live payouts for one statement  → duplicate key, refused by the engine itself;
 *   • any number of failed/reversed rows  → all NULL, all permitted (the retry history stays intact);
 *   • a superseding retry after a failure → the failed row goes NULL, the new one takes the slot.
 *
 * ⚠️ `LIVE_STATUSES` is duplicated here as a SQL literal because a generated column cannot call PHP. That is
 * a second expression of one rule (§N.8f), so `PayoutConstraintTest` asserts the two agree — if
 * `Payout::LIVE_STATUSES` ever changes without this migration, that test reds rather than the constraint
 * silently guarding the wrong set.
 */
return new class extends Migration
{
    /**
     * ⚠️⚠️ RETIRED 2026-09-20 — THIS MIGRATION IS NOW A DELIBERATE NO-OP. DO NOT "RESTORE" IT.
     *
     * ⚠️ The rule it created is NOT gone; it MOVED. 🔬 The VIRTUAL generated column plus unique index below
     * works on MySQL 8 and **cannot be created on MariaDB at all**: any `CASE` form, VIRTUAL or PERSISTENT,
     * is refused with **error 1901**, and the functional-index alternative with **error 1064**. Production
     * runs MariaDB 11.8.9, where this migration threw and left the COLUMN created with NO INDEX — and its own
     * `hasColumn` guard then made a re-run skip silently and report "Ran", so the guarantee read as installed
     * and was not.
     *
     * ⚠️ The one-live-payout rule is now enforced by TRIGGERS on INSERT **and** UPDATE, created in
     * `2026_09_20_120000_replace_live_payout_index_with_triggers`, which also drops the stale column where it
     * exists. One mechanism, both engines, proven by `OwnerCollectionGateTest` writing raw SQL around the
     * service.
     *
     * ⚠️ The body is kept below, commented, as the record of what was tried and why it could not stay.
     */
    public function up(): void
    {
        // ⚠️ Intentionally empty. See the note above — superseded by the trigger migration.
        //
        // The retired body was:
        //   ALTER TABLE payouts ADD COLUMN live_statement_key CHAR(26) AS (
        //       CASE WHEN status IN ('pending','processing','held','paid')
        //            THEN owner_statement_id ELSE NULL END
        //   ) VIRTUAL
        //   CREATE UNIQUE INDEX payouts_one_live_per_statement ON payouts (live_statement_key)
    }

    public function down(): void
    {
        // ⚠️ Nothing to reverse — `up()` no longer creates anything.
    }
};
