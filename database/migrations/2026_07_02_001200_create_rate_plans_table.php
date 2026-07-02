<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `rate_plans` — a sellable price configuration for a room_type. `derived_from_rate_plan_id`
 * is a self-FK for derived rates (parent −10%, etc.), mirroring room_types.parent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->char('currency', 3);
            $table->boolean('is_refundable')->default(true);
            $table->json('cancellation_policy')->nullable();
            $table->foreignUlid('derived_from_rate_plan_id')->nullable()->constrained('rate_plans')->nullOnDelete();
            $table->json('adjustment')->nullable(); // e.g. {"type":"percent","value":-10}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_plans');
    }
};
