<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DO9-2 — a DRAFT state on the existing `unit_requests` (§N.8f: extend the state machine the request already
 * has; never a parallel draft table). The lifecycle becomes **draft → pending → approved | declined**, with
 * `draft` sitting BEFORE the request is ever filed.
 *
 * ⚠️ TWO SCHEMA FACTS THE LIVE TABLE FORCED (read it, don't assume — D34-1/D2-1):
 *
 *  1. `status` is a real MySQL **enum**, so a new state is an ALTER, not an app-level constant. (Same shape as
 *     the `payouts` enum, which gained `held`/`processing` the same way.)
 *  2. `requested_name` is **NOT NULL with no default** — and the entire point of a draft is that it may be
 *     saved before the owner has typed a name. It becomes nullable HERE, or a draft-save of an empty wizard
 *     would fail at the database. ⚠️ This does NOT weaken submission: `requested_name` stays `required` in the
 *     SUBMIT validation, so every request that reaches the operator still has one. Only an owner-private,
 *     never-submitted draft can hold a null.
 *
 * Purely additive and reversible: no existing row changes status, and every existing row already has a name.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `unit_requests` MODIFY `status` ENUM('draft','pending','approved','declined') NOT NULL DEFAULT 'pending'");
        DB::statement('ALTER TABLE `unit_requests` MODIFY `requested_name` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        // A draft has no meaning without the state — drop them rather than silently reinterpreting them as
        // filed requests, which would put half-finished work on the operator's desk (the exact leak DO9-2
        // exists to prevent).
        DB::table('unit_requests')->where('status', 'draft')->delete();
        DB::table('unit_requests')->whereNull('requested_name')->delete();

        DB::statement("ALTER TABLE `unit_requests` MODIFY `status` ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending'");
        DB::statement('ALTER TABLE `unit_requests` MODIFY `requested_name` VARCHAR(255) NOT NULL');
    }
};
