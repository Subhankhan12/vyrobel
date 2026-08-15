<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DO9-3 — a WITHDRAWN state on `unit_requests`, completing the lifecycle
 * **draft → pending → approved | declined | withdrawn** (§N.8f: the enum DO9-2 already extended, extended once
 * more; never a delete and never a parallel entity).
 *
 * ⚠️ WITHDRAWING IS A SOFT TRANSITION, NOT A DELETION. The row stays exactly where it is, so the audit trail
 * still shows what the owner asked for, when they filed it, and that they later retracted it. Deleting would
 * erase a request the operator may already have read — and would make "what happened to that villa request?"
 * unanswerable from the record.
 *
 * Purely additive and reversible: no existing row changes status.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `unit_requests` MODIFY `status` ENUM('draft','pending','approved','declined','withdrawn') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // A withdrawn request has no meaning without the state. Return it to `declined` rather than `pending`:
        // reverting must never put a retracted request back on the operator's actionable desk.
        DB::table('unit_requests')->where('status', 'withdrawn')->update(['status' => 'declined']);

        DB::statement("ALTER TABLE `unit_requests` MODIFY `status` ENUM('draft','pending','approved','declined') NOT NULL DEFAULT 'pending'");
    }
};
