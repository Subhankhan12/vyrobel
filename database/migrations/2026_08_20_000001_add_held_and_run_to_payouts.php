<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extends `payouts` for the automated payout engine (Phase 1) — all additive:
 *
 *  • `held` status — the tax-hold becomes a REAL, visible, auto-releasing state (today it is only a thrown
 *    `PayoutHeldException` that leaves the payout `pending`). The engine catches that hold and marks the
 *    payout `held`; a later run releases it once the owner's tax details are on file.
 *  • `payout_run_id` — links a payout to the engine run that created it (manual/legacy payouts stay null).
 *  • `held_at` — when the tax-hold last held it.
 *
 * Raw ALTER for the enum (mirrors the `processing` migration; no doctrine/dbal) — still the ONE payouts table.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payouts MODIFY status ENUM('pending','processing','paid','failed','reversed','held') NOT NULL DEFAULT 'pending'");

        Schema::table('payouts', function (Blueprint $table): void {
            $table->foreignUlid('payout_run_id')->nullable()->after('supersedes_id')->constrained('payout_runs')->nullOnDelete();
            $table->timestamp('held_at')->nullable()->after('failed_at');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payout_run_id');
            $table->dropColumn('held_at');
        });

        DB::statement("ALTER TABLE payouts MODIFY status ENUM('pending','processing','paid','failed','reversed') NOT NULL DEFAULT 'pending'");
    }
};
