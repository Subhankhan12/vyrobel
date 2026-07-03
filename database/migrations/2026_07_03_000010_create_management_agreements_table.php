<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `management_agreements` — the commercial terms between operator and owner (Phase 3 · Module 1):
 * commission %, fixed management fee + interval, the overridable attribution rules, and payout
 * schedule/currency.
 *
 * EFFECTIVE-DATED so terms can change over time WITHOUT rewriting history — attribution for a given
 * night uses the agreement in effect on that night. `unit_id` null = applies to ALL the owner's
 * units; set = a per-unit override (most-specific-wins resolution). `attribution_rules` json stores
 * OVERRIDES ONLY (missing key = the documented default) — only OTA-cost-bearer and cleaning-fee-owner
 * are overridable; the rest of the rule set is fixed (see DECISIONS.md 2026-07-03 Step 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_agreements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignUlid('unit_id')->nullable()->constrained('units')->cascadeOnDelete();
            $table->decimal('commission_pct', 5, 2)->nullable();   // % on owner gross (e.g. 15.00)
            $table->bigInteger('management_fee_minor')->nullable(); // fixed periodic fee (Money)
            $table->char('management_fee_currency', 3)->nullable();
            $table->enum('management_fee_interval', ['monthly', 'quarterly', 'yearly'])->nullable();
            $table->char('payout_currency', 3)->nullable();        // overrides owners.payout_currency if set
            $table->enum('payout_schedule', ['monthly', 'quarterly'])->default('monthly');
            $table->json('attribution_rules')->nullable();         // overrides only; null keys = defaults
            $table->date('effective_from');
            $table->date('effective_to')->nullable();              // null = open-ended (current)
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_id');
            $table->index('unit_id');
            $table->index(['effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_agreements');
    }
};
