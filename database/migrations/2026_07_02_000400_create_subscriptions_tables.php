<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `subscriptions` / `subscription_items` / `usage_counters` — tenant billing state.
 * Stripe references stay null until Phase 2 payment wiring.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('plan_id')->constrained();
            $table->string('stripe_subscription_id')->nullable();
            $table->enum('status', ['trialing', 'active', 'past_due', 'canceled', 'incomplete', 'paused'])->default('trialing');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // subscription_items — Step 0 approved minimal shape.
        Schema::create('subscription_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('plan_feature_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('stripe_item_id')->nullable(); // null until Phase 2 Stripe wiring
            $table->timestamps();
        });

        Schema::create('usage_counters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('key'); // matches a plan_features.key
            $table->string('period'); // e.g. '2026-07' or 'lifetime'
            $table->unsignedBigInteger('used')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'key', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
    }
};
