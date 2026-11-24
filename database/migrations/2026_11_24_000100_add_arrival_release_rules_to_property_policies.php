<?php

declare(strict_types=1);

use App\Support\Guest\ArrivalPack;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VY-06 (audit of 2026-11-24, reproduced) — THE DOOR CODE IS RELEASED ON CONDITIONS EACH PROPERTY SETS. ⚠️ ADDITIVE: three columns.
 *
 * Until now the only rule was a date, fixed at two days for every property, so a guest with the whole stay still owing saw the door
 * code. Each property now has its own three rules (Settings → Policies):
 *
 *  - `arrival_lead_days`        how many days before check-in the door code and Wi-Fi are released. NULL = the product default (2) —
 *    never a number the property did not choose, and exactly what every existing property had.
 *  - `arrival_requires_paid`    the code is withheld while the guest's balance is owing. ⚠️ DEFAULT TRUE: that is the fix. A property
 *    that takes payment at its front desk switches it off.
 *  - `arrival_requires_checkin` the code is withheld until the guest has completed online check-in. Default false (as before).
 *
 * A property with no policy row at all reads as the same defaults ({@see ArrivalPack}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_policies', function (Blueprint $table): void {
            $table->unsignedSmallInteger('arrival_lead_days')->nullable();
            $table->boolean('arrival_requires_paid')->default(true);
            $table->boolean('arrival_requires_checkin')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('property_policies', function (Blueprint $table): void {
            $table->dropColumn(['arrival_lead_days', 'arrival_requires_paid', 'arrival_requires_checkin']);
        });
    }
};
