<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CONNECT PHASE 1 — the OPERATOR's Stripe connected account (Standard).
 *
 * ⚠️ PURELY ADDITIVE. Nothing charges on this column in this phase. `createPaymentCheckout()` is untouched
 * and every guest payment behaves exactly as it did before — an organization with this column NULL is
 * indistinguishable from today.
 *
 * ⚠️⚠️ THE COLUMN HOLDS AN IDENTIFIER, NEVER A READINESS FLAG. Whether the account may take a charge is a
 * fact held by STRIPE (`charges_enabled`), read live. A local "onboarded" boolean would be an ASSERTION,
 * and it would be wrong the first time Stripe suspends the account or asks for more information.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('stripe_connect_account_id')->nullable()->after('default_currency');
            $table->index('stripe_connect_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropIndex(['stripe_connect_account_id']);
            $table->dropColumn('stripe_connect_account_id');
        });
    }
};
