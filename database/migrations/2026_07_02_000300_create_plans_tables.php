<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `plans` / `plan_features` — product packages (Starter/Growth/Pro/Enterprise) and their
 * feature/limit entitlements. These are a GLOBAL first-party catalog (not tenant-scoped),
 * referenced by tenant subscriptions across orgs. ULID PKs for cross-schema FK uniformity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('price_minor')->default(0); // Money via Money cast
            $table->char('currency', 3);
            $table->enum('interval', ['monthly', 'yearly'])->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_id')->constrained()->cascadeOnDelete();
            $table->string('key'); // e.g. max_units, max_messages, ai_calls, storage_mb, website_builder
            $table->integer('limit')->nullable(); // null = unlimited/boolean feature
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['plan_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
