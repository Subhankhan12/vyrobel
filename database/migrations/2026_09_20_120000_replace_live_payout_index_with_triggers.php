<?php

declare(strict_types=1);

use App\Models\Payout;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️⚠️ ONE LIVE PAYOUT PER STATEMENT — ENFORCED BY THE DATABASE, NOW WITH TRIGGERS.
 *
 * ⚠️⚠️ **DO NOT DELETE THIS AS EXOTIC. TRIGGERS ARE UNUSUAL IN THIS CODEBASE ON PURPOSE, AND THIS IS THE ONLY
 * MECHANISM THAT CAN EXPRESS THE RULE ON THE ENGINE THIS PRODUCT ACTUALLY RUNS ON.**
 *
 * ── ⚠️ WHY THE PREVIOUS MECHANISM HAD TO GO ─────────────────────────────────────────────────────────────
 *
 * The rule was a VIRTUAL generated column (`live_statement_key`) carrying `owner_statement_id` while a payout
 * was live and NULL otherwise, plus a unique index — NULLs do not collide, so failed/reversed rows were free.
 * ⚠️ That works on MySQL 8 and **cannot be created on MariaDB at all**:
 *
 *   🔬 generated column (any `CASE` form, VIRTUAL or PERSISTENT) → **error 1901**, refused outright.
 *      Measured with `IN`, with a single `=`, and rewritten as `IF()` — MariaDB refuses all three.
 *   🔬 functional / expression index `CREATE UNIQUE INDEX … ((CASE …))` → **error 1064**, not supported.
 *   🔬 a plain unique index on a REAL column works on both — but the column would have to be set by the
 *      writer, so a raw INSERT that omits it lands NULL and collides with nothing. **That is precisely the
 *      bypass this rule exists to stop**, so it is not a substitute.
 *
 * ⚠️⚠️ 🔬 On production the old migration therefore left the COLUMN created and the INDEX absent — and its own
 * `hasColumn` guard made a re-run **skip silently and report "Ran"**, so the guarantee read as installed and
 * was not. That is the state this migration replaces.
 *
 * ── ⚠️ WHY A TRIGGER, AND WHY IT IS NOT THE THING THE NO-TRIGGER RULE WARNED ABOUT ───────────────────────
 *
 * ⚠️ The objection to triggers is that they are added by hand in a GUI, invisible to the codebase and
 * untested. ⚠️ This one is **created in a migration** — it is in version control, it is dropped by `down()`,
 * and `OwnerCollectionGateTest` proves it REFUSES by writing raw SQL around the service entirely.
 *
 * ⚠️⚠️ **IT FIRES ON INSERT *AND* UPDATE, AND THE UPDATE HALF IS THE ONE THAT WOULD HAVE BEEN MISSED.**
 * A payout can be **moved into** a live status without ever being inserted as one (a retry being re-queued, a
 * status correction, a backfill). 🔬 An insert-only trigger leaves that path open while reading as covered,
 * which is worse than no trigger at all.
 *
 * ⚠️ **IT REFUSES; IT NEVER SILENTLY CORRECTS.** `SIGNAL SQLSTATE '45000'` with a message naming the statement
 * and the payout already holding the slot. A trigger that quietly fixes rows is a second place money logic
 * lives where nobody reads it.
 *
 * ⚠️ 🔬 VERIFIED IDENTICAL ON BOTH ENGINES before this was written: a trigger on `payouts` may READ `payouts`
 * (MySQL's error 1442 restricts MODIFYING the subject table, not reading it), and both engines refuse the
 * second live INSERT **and** the UPDATE-into-live.
 *
 * ⚠️ `LIVE_STATUSES` is repeated here as a SQL literal because a trigger cannot call PHP — the same second
 * expression of one rule (§N.8f) the previous migration carried, and `OwnerPayoutRetryTest` asserts the two
 * stay in agreement.
 */
return new class extends Migration
{
    /** ⚠️ Built from the model so the two cannot silently diverge in THIS file; the literal still lands in SQL. */
    private function liveList(): string
    {
        return "'".implode("','", Payout::LIVE_STATUSES)."'";
    }

    private function body(string $selfExclusion): string
    {
        $live = $this->liveList();

        return "IF NEW.status IN ({$live}) AND NEW.owner_statement_id IS NOT NULL THEN "
            .'IF EXISTS (SELECT 1 FROM payouts WHERE owner_statement_id = NEW.owner_statement_id '
            ."AND status IN ({$live}){$selfExclusion}) THEN "
            // ⚠️⚠️ 128 CHARACTERS IS A HARD LIMIT. 🔬 A longer MESSAGE_TEXT does not truncate politely — the
            //    engine raises **1648 "Data too long for condition item"** INSTEAD of this message, so the row is
            //    still refused but the operator sees an internal error rather than something actionable.
            //    Caught by driving a real duplicate, not by reading the manual. Keep this under 128.
            ."SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = "
            ."'Refusing a 2nd LIVE payout for this statement: one is already pending/processing/held/paid.'; "
            .'END IF; END IF;';
    }

    public function up(): void
    {
        // ⚠️ TRIGGERS FIRST, then remove the old mechanism — so there is never a window with no guard at all.
        DB::unprepared('DROP TRIGGER IF EXISTS payouts_one_live_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS payouts_one_live_bu');

        DB::unprepared('CREATE TRIGGER payouts_one_live_bi BEFORE INSERT ON payouts FOR EACH ROW BEGIN '
            .$this->body('').' END');

        // ⚠️ `id <> NEW.id` so a row updating itself (e.g. pending → paid) is not refused by its own existence.
        DB::unprepared('CREATE TRIGGER payouts_one_live_bu BEFORE UPDATE ON payouts FOR EACH ROW BEGIN '
            .$this->body(' AND id <> NEW.id').' END');

        // ⚠️ Now retire the generated column and its index where they exist (MySQL had both; production had
        //    only the column, because the index creation failed — see the docblock).
        if (Schema::hasColumn('payouts', 'live_statement_key')) {
            $hasIndex = DB::selectOne(
                'SELECT COUNT(*) AS c FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
                ['payouts', 'payouts_one_live_per_statement'],
            );

            if ((int) $hasIndex->c > 0) {
                DB::statement('DROP INDEX payouts_one_live_per_statement ON payouts');
            }

            DB::statement('ALTER TABLE payouts DROP COLUMN live_statement_key');
        }
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS payouts_one_live_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS payouts_one_live_bu');
        // ⚠️ The generated column is NOT recreated: it cannot exist on MariaDB, so restoring it would make
        //    `down()` engine-dependent. The earlier migration still owns that column's history.
    }
};
