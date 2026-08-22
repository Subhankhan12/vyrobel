<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
    public function up(): void
    {
        if (Schema::hasColumn('payouts', 'live_statement_key')) {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE payouts
            ADD COLUMN live_statement_key CHAR(26) AS (
                CASE WHEN status IN ('pending','processing','held','paid')
                     THEN owner_statement_id
                     ELSE NULL
                END
            ) VIRTUAL
        SQL);

        DB::statement('CREATE UNIQUE INDEX payouts_one_live_per_statement ON payouts (live_statement_key)');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payouts', 'live_statement_key')) {
            return;
        }

        DB::statement('DROP INDEX payouts_one_live_per_statement ON payouts');
        DB::statement('ALTER TABLE payouts DROP COLUMN live_statement_key');
    }
};
