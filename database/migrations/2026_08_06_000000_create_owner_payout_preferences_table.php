<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The owner's PAYOUT PREFERENCES (Owner Payouts exact-match) — the owner's STATED preference for how their
 * earnings settle: frequency, the business-day-of-month, a minimum threshold, and a pause switch. One row per
 * owner (unique `owner_id`). This is a PREFERENCE, not a schedule engine: the actual payout cadence + execution
 * are confirmed with the operator + the live payout rail (never a fabricated "next payout on the 3rd", #39).
 * Owner-scoped like every owner table (organization_id + owner_id). Money is an integer minor unit (#17).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_payout_preferences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained()->cascadeOnDelete();
            $table->string('frequency', 20)->default('monthly');       // monthly | twice_monthly | weekly
            $table->unsignedTinyInteger('payout_day')->default(3);       // Nth business day of the month (1–5)
            $table->unsignedBigInteger('minimum_minor')->default(0);     // roll-forward threshold; 0 = none
            $table->boolean('paused')->default(false);
            $table->timestamps();

            $table->unique('owner_id');   // one preference row per owner
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_payout_preferences');
    }
};
