<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️⚠️ ADMIN 06 (2026-09-27) — WHAT A DELETION MUST KNOW ABOUT MONEY BEFORE IT MAY RUN. ADDITIVE ONLY.
 *
 *   · `livemode` — Stripe's OWN field, stored when a money record is written (from the object Stripe returned, or
 *     the signed event that settled it). Null = not a Stripe record (the manual rail), or written before this
 *     column existed. 🔬 Measured 2026-09-27: production holds NO Stripe money record of any kind, so there is
 *     no existing row whose mode needs proving.
 *   · `payment_disputes` — a card dispute (`charge.dispute.*`) was not recorded at all; now each one is, by Stripe's
 *     own dispute id (📖 Stripe: a payment can, rarely, carry more than one), ordered by the event's own time, so a
 *     deletion can refuse while any is open and a late redelivery cannot reopen a finished one.
 *   · `stripe_checkout_sessions` — KNOWN-LIMITS L19 / GO-LIVE B19: starting a checkout wrote nothing, so a
 *     deletion could not see an open payment page. Every session the app creates is now recorded with Stripe's
 *     own `expires_at` (📖 Stripe: 30 minutes to 24 hours, 24 by default) until it completes or expires.
 */
return new class extends Migration
{
    /**
     * ⚠️ RE-RUNNABLE, DELIBERATELY: MySQL and MariaDB cannot roll back a schema change, so a statement failing part-way
     * leaves the earlier ones applied — and the plain re-run then dies on "duplicate column". 🔬 That happened to THIS
     * migration on the dev machine (a generated index name over the 64-character limit). Each step checks first.
     */
    public function up(): void
    {
        foreach ([
            ['payments', 'livemode', fn (Blueprint $t) => $t->boolean('livemode')->nullable()->after('stripe_account_id')],
            ['payouts', 'livemode', fn (Blueprint $t) => $t->boolean('livemode')->nullable()->after('connect_account_id')],
            ['subscriptions', 'livemode', fn (Blueprint $t) => $t->boolean('livemode')->nullable()->after('stripe_customer_id')],
            ['payment_authorizations', 'livemode', fn (Blueprint $t) => $t->boolean('livemode')->nullable()->after('status')],
        ] as [$table, $column, $add]) {
            if (! Schema::hasColumn($table, $column)) {
                Schema::table($table, $add);
            }
        }

        if (! Schema::hasTable('stripe_checkout_sessions')) {
            Schema::create('stripe_checkout_sessions', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
                $table->string('stripe_session_id');
                $table->string('mode', 16);   // subscription | payment
                $table->boolean('livemode')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('expired_at')->nullable();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('payment_disputes')) {
            Schema::create('payment_disputes', function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignUlid('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->string('stripe_dispute_id');
                $table->string('status', 32);
                $table->boolean('livemode')->nullable();
                $table->timestamp('event_created_at')->nullable();   // the Stripe event's own time — orders redeliveries
                $table->timestamps();
            });
        }
        $disputeIndexes = array_column(Schema::getIndexes('payment_disputes'), 'name');
        Schema::table('payment_disputes', function (Blueprint $table) use ($disputeIndexes): void {
            if (! in_array('payment_disputes_stripe_dispute_unique', $disputeIndexes, true)) {
                $table->unique('stripe_dispute_id', 'payment_disputes_stripe_dispute_unique');
            }
            if (! in_array('payment_disputes_open_idx', $disputeIndexes, true)) {
                $table->index(['organization_id', 'status'], 'payment_disputes_open_idx');
            }
        });

        // the indexes are their own steps: on the dev machine the table was created and an index then failed
        $indexes = array_column(Schema::getIndexes('stripe_checkout_sessions'), 'name');
        Schema::table('stripe_checkout_sessions', function (Blueprint $table) use ($indexes): void {
            if (! in_array('checkout_sessions_stripe_session_unique', $indexes, true)) {
                $table->unique('stripe_session_id', 'checkout_sessions_stripe_session_unique');
            }
            // named: a generated name here is longer than MySQL's 64-character identifier limit
            if (! in_array('checkout_sessions_open_idx', $indexes, true)) {
                $table->index(['organization_id', 'completed_at', 'expired_at'], 'checkout_sessions_open_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_disputes');
        Schema::dropIfExists('stripe_checkout_sessions');
        Schema::table('payment_authorizations', fn (Blueprint $table) => $table->dropColumn('livemode'));
        Schema::table('subscriptions', fn (Blueprint $table) => $table->dropColumn('livemode'));
        Schema::table('payouts', fn (Blueprint $table) => $table->dropColumn('livemode'));
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('livemode'));
    }
};
