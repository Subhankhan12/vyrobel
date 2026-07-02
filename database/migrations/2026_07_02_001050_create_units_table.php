<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `units` — the physical sellable thing (room / apartment / villa / dorm bed).
 *
 * `owner_id` is a nullable ULID FORWARD HOOK for apartment-owner accounting (owners module,
 * Phase 3). It is indexed but has NO foreign-key target yet — deliberately, not a stub.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('floor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('name')->nullable();
            $table->enum('unit_kind', ['room', 'apartment', 'villa', 'dorm_bed'])->default('room');
            $table->unsignedInteger('max_occupancy')->default(2);
            $table->ulid('owner_id')->nullable(); // FK target arrives with owners (Phase 3)
            $table->enum('housekeeping_status', ['clean', 'dirty', 'inspected'])->default('clean');
            $table->enum('condition_status', ['in_service', 'out_of_order', 'out_of_service'])->default('in_service');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_id');
            $table->unique(['property_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
