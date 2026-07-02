<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `property_policies` — policies as structured data (separate table to allow versioning).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_policies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->json('cancellation')->nullable();
            $table->json('child_policy')->nullable();
            $table->string('pet')->nullable();     // e.g. not_allowed | on_request | allowed
            $table->string('smoking')->nullable();
            $table->json('deposit')->nullable();
            $table->json('damage')->nullable();
            $table->unsignedInteger('min_stay')->nullable();
            $table->unsignedInteger('max_stay')->nullable();
            $table->json('long_stay_discounts')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_policies');
    }
};
