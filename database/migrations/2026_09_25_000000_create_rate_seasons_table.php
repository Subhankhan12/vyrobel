<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OWNER FIX 4b — the seasonal ("peak") rate tier, as an AUTHORING RECORD.
 *
 * ⚠️ THIS TABLE IS NEVER READ AT CHARGE TIME, AND THAT IS THE WHOLE DESIGN. A season is APPLIED by writing
 * its nights into `rate_calendar` through the certified writer, exactly as `setRateWeekendAware()` already
 * materializes the weekend uplift. The charge keeps reading `rate_calendar` — one price source, so the
 * storefront, `priceOver()`, the folio, both grids and the 22 other direct readers all agree for free.
 *
 * ⚠️ THE REJECTED ALTERNATIVE was resolving a season at read time inside `NightlyRate`. It would have been
 * INERT for most bookings (`stayTotalForUnit()` returns NULL for a unit with no override, so the default
 * charge is `priceOver()` → `rate_calendar`) and would have CONTRADICTED the landing page, which reads
 * `rate_calendar` directly — two sources for one night's price (D4-1).
 *
 * `price_minor` is a FINAL, VAT-inclusive price (§G.1) — not an uplift percentage, so what an operator types
 * is what a guest pays.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_seasons', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('rate_plan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Both bounds INCLUSIVE — a season reads "1 Jul to 31 Aug" to a person. The certified writer's
            // range is half-open, so applying converts once, in one place (SeasonalRateService::apply()).
            $table->date('starts_on');
            $table->date('ends_on');
            $table->bigInteger('price_minor');
            $table->string('currency', 3);
            // When this season was last materialized into rate_calendar — NULL means "defined, never applied",
            // which is an honest state the UI shows rather than implying the nights already carry the price.
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'rate_plan_id', 'starts_on', 'ends_on'], 'rate_seasons_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_seasons');
    }
};
