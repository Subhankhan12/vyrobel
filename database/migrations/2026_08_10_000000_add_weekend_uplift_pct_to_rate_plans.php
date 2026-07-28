<?php

declare(strict_types=1);

use App\Services\OwnerRateService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner completeness sweep · Wave A · GATE 2 — the shared WEEKEND-UPLIFT policy.
 *
 * The owner-editable rate model (per-room-type, exclusive-owned) gains a stored `weekend_uplift_pct` on the rate
 * plan — the owner's current weekend-pricing policy (a percent added to the base nightly rate on Fri/Sat nights).
 * It is NOT an inert store: {@see OwnerRateService::apply()} actually writes the uplifted price on
 * weekend nights into `rate_calendar` when the owner applies a rate — so a weekend-uplift change genuinely changes
 * weekend prices (never a fake save, #39). Additive + nullable; minimum stay is already modelled (`restrictions`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rate_plans', function (Blueprint $table): void {
            $table->decimal('weekend_uplift_pct', 5, 2)->nullable()->after('adjustment');
        });
    }

    public function down(): void
    {
        Schema::table('rate_plans', function (Blueprint $table): void {
            $table->dropColumn('weekend_uplift_pct');
        });
    }
};
