<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `availability` — the sellable-count source of truth per room_type per date.
 * available = total − sold − out_of_order − blocked (computed, not stored).
 *
 * Written ONLY by AvailabilityService (recompute) and, from Step 5, by AssignmentService
 * (the `sold` term, inside its locked transaction). No other write path — ever.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_type_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('sold')->default(0);
            $table->unsignedInteger('out_of_order')->default(0);
            $table->unsignedInteger('blocked')->default(0);
            $table->timestamps();

            $table->unique(['room_type_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability');
    }
};
