<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `room_types` — the sellable category (what OTAs and the booking engine sell against).
 * `parent_room_type_id` supports virtual/parent-child types. `default_rate_plan_id` is a
 * forward hook to `rate_plans` (Step 3) — nullable column, NO FK target yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->enum('kind', ['room', 'apartment', 'villa', 'dorm'])->default('room');
            $table->unsignedInteger('std_occupancy')->default(2);
            $table->unsignedInteger('max_adults')->default(2);
            $table->unsignedInteger('max_children')->default(0);
            $table->unsignedInteger('bedrooms')->nullable();
            $table->boolean('is_virtual')->default(false);
            $table->foreignUlid('parent_room_type_id')->nullable()->constrained('room_types')->nullOnDelete();
            $table->ulid('default_rate_plan_id')->nullable(); // FK added in Step 3 (rate_plans)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
