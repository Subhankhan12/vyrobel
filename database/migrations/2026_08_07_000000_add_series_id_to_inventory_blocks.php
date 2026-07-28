<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner Block Calendar exact-match — a nullable `series_id` groups the individual blocks that an owner's
 * REPEAT (weekly / fortnightly / monthly × occurrences) expands into. The recurrence is NOT a fake "scheduled
 * series": each occurrence is a real, independent InventoryBlock; `series_id` only lets the calendar badge them
 * as one series and release them together. Null for every non-recurring block (all existing rows, and every
 * maintenance/manual block).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_blocks', function (Blueprint $table): void {
            $table->ulid('series_id')->nullable()->after('reason')->index();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_blocks', function (Blueprint $table): void {
            $table->dropColumn('series_id');
        });
    }
};
