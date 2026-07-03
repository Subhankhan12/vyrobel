<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 · Module 1 contract addition: `channels.commission_pct` — the per-connection DEFAULT OTA
 * commission % for this channel. Nullable: when absent (and no per-reservation override on
 * `reservations.ota_commission_minor`), owner-accounting attributes OTA cost = 0 and flags it
 * "unverified / pending channel reconciliation" VISIBLY on the owner statement — never guessed.
 * Real commissions backfill and the flag clears when the Channex live checkpoint lands
 * (see DECISIONS.md 2026-07-03 Step 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->decimal('commission_pct', 5, 2)->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('commission_pct');
        });
    }
};
