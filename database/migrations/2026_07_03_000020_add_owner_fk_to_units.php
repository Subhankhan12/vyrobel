<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closes the Phase 1 forward hook: `units.owner_id` becomes a real FK now that `owners` exists.
 * Stays NULLABLE — not every unit has an owner (hotel rooms typically don't). `nullOnDelete` so
 * removing an owner detaches their units rather than cascading them away. Same pattern as Phase 2's
 * add_channel_fk_to_reservations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->foreign('owner_id')
                ->references('id')
                ->on('owners')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });
    }
};
