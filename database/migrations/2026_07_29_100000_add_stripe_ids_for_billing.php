<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Live Stripe Billing (Phase 8 · SG1). `subscriptions.stripe_customer_id` = the Stripe Customer the org's
 * subscription belongs to (stored on checkout completion, so subscription/invoice webhooks resolve the org).
 * `plans.stripe_price_id` = the Stripe Price a plan row (tier × interval) maps to, used as the Checkout line
 * item. Both nullable — the app runs on the fake client / manual gateway until real ids are configured.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('stripe_subscription_id');
            $table->index('stripe_customer_id');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->string('stripe_price_id')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['stripe_customer_id']);
            $table->dropColumn('stripe_customer_id');
        });
        Schema::table('plans', fn (Blueprint $table) => $table->dropColumn('stripe_price_id'));
    }
};
