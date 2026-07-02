<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `corporate_accounts` — companies / travel agents with negotiated terms. `default_rate_plan_id`
 * FK to `rate_plans` (exists since Step 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corporate_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['corporate', 'travel_agent'])->default('corporate');
            $table->json('billing')->nullable();
            $table->foreignUlid('default_rate_plan_id')->nullable()->constrained('rate_plans')->nullOnDelete();
            $table->decimal('commission_pct', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporate_accounts');
    }
};
