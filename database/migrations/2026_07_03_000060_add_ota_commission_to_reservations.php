<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 · Module 1 Step 2 contract input: `reservations.ota_commission_minor` — the per-reservation
 * OTA commission override (in the reservation currency), populated later from the real Channex booking
 * payload. Owner attribution reads this first; when absent it falls back to `channels.commission_pct`,
 * and when BOTH are absent it attributes OTA cost = 0 flagged "unverified / pending channel
 * reconciliation" (never guessed). See DECISIONS.md 2026-07-03 Step 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->bigInteger('ota_commission_minor')->nullable()->after('deposit_minor');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('ota_commission_minor');
        });
    }
};
