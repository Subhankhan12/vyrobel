<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #104b GATE 2 — the Settings hub's "Invoice details / numbering" config (real fields; the CERTIFIED numbered
 * issuance + emailed PDF + SAF-T (PT) export stay honestly deferred — no pipeline is fabricated). Legal entity +
 * VAT number already exist (`billing_company` / `billing_vat_id`); this adds the billing city + the invoice
 * series prefix / next number / auto-issue flag. Org-level (invoicing is a legal-entity concern). Additive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('billing_city', 120)->nullable()->after('billing_vat_id');
            $table->string('invoice_series_prefix', 16)->nullable()->after('billing_city');
            $table->unsignedInteger('invoice_next_number')->default(1)->after('invoice_series_prefix');
            $table->boolean('invoice_auto_issue')->default(false)->after('invoice_next_number');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn(['billing_city', 'invoice_series_prefix', 'invoice_next_number', 'invoice_auto_issue']);
        });
    }
};
