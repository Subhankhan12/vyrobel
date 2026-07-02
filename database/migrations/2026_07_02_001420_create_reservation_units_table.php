<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `reservation_units` — the per-unit line item; THIS is what occupies inventory. `unit_id` is
 * null until a physical unit is assigned (e.g. fresh OTA import). Lifecycle is via `status`
 * (active | cancelled | moved) — NOT soft deletes: the Level-B overlap check depends on rows
 * being present, and a soft-deleted "active" row would silently free the unit.
 *
 * `currency` is added alongside `price_minor` so the price snapshot is a proper Money value
 * (schema lists price_minor only; money is always minor units + ISO currency — convention #3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('rate_plan_id')->nullable()->constrained('rate_plans')->nullOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->bigInteger('price_minor')->default(0); // agreed-price snapshot (Money)
            $table->char('currency', 3);
            $table->enum('status', ['active', 'cancelled', 'moved'])->default('active');
            $table->timestamps();

            // Overlap-check index: active unit rows over a date range.
            $table->index(['unit_id', 'status', 'check_in', 'check_out']);
            $table->index(['room_type_id', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_units');
    }
};
