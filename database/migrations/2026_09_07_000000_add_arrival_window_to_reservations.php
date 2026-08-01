<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guest online check-in (Guest Portal batch 2 · GATE 2) — the arrival window the guest picks during
 * online check-in. A real, operator/front-desk-visible slot on the reservation (housekeeping plans
 * around it); the free-text arrival note reuses the existing `notes_guest` column. NOT the authoritative
 * status — the front desk still performs the real check-in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            // e.g. '11-13' | '13-15' | '15-18' | '18+' — a coarse slot, not a precise time (honest).
            $table->string('arrival_window', 16)->nullable()->after('guest_checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn('arrival_window');
        });
    }
};
