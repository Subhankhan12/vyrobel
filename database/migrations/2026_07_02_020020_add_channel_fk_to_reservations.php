<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closes the Phase 1 forward hook: `reservations.channel_id` becomes a real FK now that
 * `channels` exists. `nullOnDelete` matches the nullable column (direct/walk-in reservations
 * carry no channel). Same pattern as Phase 1's add_reservation_fk_to_guest_documents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreign('channel_id')
                ->references('id')
                ->on('channels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
        });
    }
};
