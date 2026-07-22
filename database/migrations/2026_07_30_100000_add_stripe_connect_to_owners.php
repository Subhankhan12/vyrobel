<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner Stripe Connect (Phase 8 · SG3 — the live owner payout rail). `stripe_connect_account_id` = the owner's
 * connected Stripe account (Standard — the owner owns their KYC/compliance/payouts; Stripe handles it).
 * `stripe_connect_payouts_enabled` flips true on the account.updated webhook once Stripe clears the account.
 * Connect REPLACES the manual IBAN for the live rail; the encrypted IBAN stays for the manual/offline rail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->string('stripe_connect_account_id')->nullable()->after('user_id');
            $table->boolean('stripe_connect_payouts_enabled')->default(false)->after('stripe_connect_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn(['stripe_connect_account_id', 'stripe_connect_payouts_enabled']);
        });
    }
};
