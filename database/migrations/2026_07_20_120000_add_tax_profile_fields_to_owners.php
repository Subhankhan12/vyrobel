<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner tax-profile fields (Phase 2 · Sub-gate 4 — Tax Center). `tax_id`, `legal_name` and `type`
 * (individual/company) already exist; this adds the two remaining tax-profile fields:
 *   • `tax_country` — the owner's tax residency (a plain country string; not sensitive).
 *   • `vat_number`  — the owner's VAT identifier; ENCRYPTED at rest on the model (a tax identifier, PII —
 *     the same posture as `tax_id`), so `text` here to hold the ciphertext.
 * The tax ID that GATES payouts is the existing `owners.tax_id`; these are additional profile detail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table): void {
            $table->string('tax_country', 60)->nullable()->after('tax_id');
            $table->text('vat_number')->nullable()->after('tax_country');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table): void {
            $table->dropColumn(['tax_country', 'vat_number']);
        });
    }
};
