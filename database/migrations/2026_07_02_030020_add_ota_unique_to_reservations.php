<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-level backstop for the Step 4 booking-import idempotency: `unique(channel_id,
 * ota_reservation_id)`. MySQL allows multiple NULLs in a unique index, so direct/walk-in
 * reservations (both columns NULL) never collide, while a repeated OTA `(channel,
 * ota_reservation_id)` does — behind the app-level dedupe in BookingImportService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unique(['channel_id', 'ota_reservation_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique(['channel_id', 'ota_reservation_id']);
        });
    }
};
