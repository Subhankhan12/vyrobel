<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7b — billing identity on the org (company legal name + VAT/tax id) for the Checkout + future
 * invoices. These are the operator's OWN business identifiers (not personal PII, and NOT card data — the
 * card/charge is the account-gated Stripe seam and is never stored), so they live as plain nullable columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('billing_company')->nullable()->after('billing_email');
            $table->string('billing_vat_id')->nullable()->after('billing_company');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['billing_company', 'billing_vat_id']);
        });
    }
};
