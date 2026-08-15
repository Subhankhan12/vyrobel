<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * DO-CORRECTNESS-1 · FIX 3 — the waitlist gets the uniqueness rule its SIBLING has always had.
 *
 * `guest_wishlist_items` was created with `unique(['guest_id','room_type_id'])` in the very same migration
 * (2026_07_22_120000) that created `guest_waitlist_entries` with only two plain indexes. `joinWaitlist()` is a
 * bare `create()`, so a double-submit — a double-click, a retried POST, an impatient refresh — wrote two
 * identical rows. A one-table omission, not a design position.
 *
 * ⚠️ THE TRAP THAT DECIDED THIS MIGRATION'S SHAPE, AND IT WAS VERIFIED ON THIS MySQL RATHER THAN ASSUMED:
 * **a MySQL UNIQUE index does not constrain rows where any indexed column is NULL.** Proven directly —
 * two identical `('g', NULL, '2026-09')` rows insert happily under `UNIQUE (a,b,c)`.
 *
 * That matters here more than anywhere, because of this table's own design comment: *"Exact dates OR a
 * flexible month. One of the two is set."* — so EVERY row has a NULL among `check_in` / `check_out` /
 * `flexible_month`. A unique index across those nullable columns would therefore have been **completely
 * inert**: it would read as a backstop in the schema, pass review, and never once fire. ⚠️ A guard that does
 * not guard is worse than a missing one, because it stops anyone looking again.
 *
 * ⚠️ AND THE DATES GENUINELY BELONG IN THE IDENTITY — the sibling's two columns are NOT enough. A guest
 * waiting on the same room type for 3–7 Sep *and* for 20–24 Sep has made two legitimately different requests;
 * collapsing them onto `(guest_id, room_type_id)` would silently discard the second.
 *
 * So the identity is expressed as a STORED GENERATED column that can never be NULL:
 *     exact dates   → '2026-09-03..2026-09-07'
 *     flexible month→ '2026-09'
 *     neither       → ''            (defensive; the service always sets one)
 * and the unique index is (guest_id, room_type_id, window_key). Standard InnoDB, no Postgres-only construct.
 *
 * `status` is deliberately NOT part of the identity: `leaveWaitlist()` HARD-DELETES the row, so a cancelled
 * entry never lingers to block a rejoin, and a lapsed one is revived through `reconfirmWaitlist()`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ⚠️ DE-DUPE BEFORE CONSTRAINING — adding a unique index to a table that already holds duplicates
        // FAILS, and it would fail in production rather than here. Keep the EARLIEST row of each identity
        // (it carries the guest's original `confirmed_at`, so their place in the queue is the one they
        // actually earned) and delete the later copies.
        $duplicates = DB::table('guest_waitlist_entries')
            ->selectRaw("id, guest_id, room_type_id, COALESCE(flexible_month, CONCAT_WS('..', check_in, check_out), '') AS window_key, created_at")
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $seen = [];
        $stale = [];
        foreach ($duplicates as $row) {
            $key = $row->guest_id.'|'.$row->room_type_id.'|'.$row->window_key;
            if (isset($seen[$key])) {
                $stale[] = $row->id;

                continue;
            }
            $seen[$key] = true;
        }

        if ($stale !== []) {
            DB::table('guest_waitlist_entries')->whereIn('id', $stale)->delete();
        }

        Schema::table('guest_waitlist_entries', function (Blueprint $table): void {
            // Never NULL — which is the whole point (see the header note on NULLs in a MySQL unique index).
            $table->string('window_key', 40)
                ->storedAs("COALESCE(`flexible_month`, CONCAT_WS('..', `check_in`, `check_out`), '')")
                ->after('flexible_month');

            $table->unique(['guest_id', 'room_type_id', 'window_key'], 'guest_waitlist_entries_identity_unique');
        });
    }

    public function down(): void
    {
        Schema::table('guest_waitlist_entries', function (Blueprint $table): void {
            $table->dropUnique('guest_waitlist_entries_identity_unique');
            $table->dropColumn('window_key');
        });
    }
};
