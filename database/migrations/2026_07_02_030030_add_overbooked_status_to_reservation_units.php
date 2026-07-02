<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds `overbooked` to `reservation_units.status` for Step 4's accept-and-flag OTA import path.
 *
 * An `overbooked` line is a recorded OTA reservation that could NOT occupy real capacity (sync
 * lag oversold it). It is deliberately NOT `active`: it never contributed to `availability.sold`
 * (so `sold` stays ≤ `total`), it carries no `unit_id`, and it is excluded from Level B overlap
 * checks and from sold-freeing on cancel. The invariant stays clean —
 * `sold = count of ACTIVE occupying lines` — with overbooking tracked separately via this status
 * + the `overbooking_conflicts` row.
 *
 * Raw ALTER (no doctrine/dbal dependency) — extend-don't-duplicate: still the ONE reservations/
 * reservation_units model, just one more lifecycle value.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE reservation_units MODIFY COLUMN status '
            ."ENUM('active', 'cancelled', 'moved', 'overbooked') NOT NULL DEFAULT 'active'"
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE reservation_units MODIFY COLUMN status '
            ."ENUM('active', 'cancelled', 'moved') NOT NULL DEFAULT 'active'"
        );
    }
};
