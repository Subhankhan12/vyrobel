<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `restrictions` — stay rules per room_type/rate_plan per date (thin in Phase 1; full use in
 * P2/P4). Either scope column may be null (type-wide or plan-wide).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restrictions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('rate_plan_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('min_stay')->nullable();
            $table->unsignedInteger('max_stay')->nullable();
            $table->boolean('closed_to_arrival')->default(false);
            $table->boolean('closed_to_departure')->default(false);
            $table->boolean('stop_sell')->default(false);
            $table->timestamps();

            $table->unique(['room_type_id', 'rate_plan_id', 'date'], 'restrictions_scope_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restrictions');
    }
};
