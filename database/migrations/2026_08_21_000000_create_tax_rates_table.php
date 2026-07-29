<?php

use App\Services\TaxRateService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The tax-rate CONFIGURATION store (Wave D · tax engine · Phase 1). ⚠️ Tax figures have legal consequences — so
 * the ENGINE is code but the RATES live here, CONFIGURED per jurisdiction by the founder/platform admin. The
 * session NEVER hardcodes a rate; it seeds only clearly-labelled EXAMPLE rows (`is_example = true`, `note` says
 * "replace") which are NEVER presented as authoritative. A real rate is set via `tax:set-rate` (`is_example = false`).
 *
 * PLATFORM-LEVEL: a rate is per-JURISDICTION (country), not per-tenant — no `organization_id`. Read cross-tenant
 * by {@see TaxRateService::rateFor()}, which prefers a REAL rate over an example.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('jurisdiction', 64);                              // matches owners.tax_country
            $table->enum('rate_type', ['accommodation_vat', 'service_vat', 'withholding']);
            $table->decimal('percent', 6, 3);                                // e.g. 6.000, 23.000, 25.000
            $table->date('effective_from');
            $table->date('effective_to')->nullable();                        // open-ended when null
            $table->boolean('is_example')->default(false);                   // ⚠️ true = seeded example, NOT authoritative
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['jurisdiction', 'rate_type', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
