<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DEC-p (2026-11-12) — an operator can give an EXISTING owner a unit that has no owner, FROM A CHOSEN DAY.
 *
 * `owner_since` is the first day the unit's owner (`units.owner_id`) is paid for the unit's stays. NULL = "always" — every existing
 * unit, and every unit an accepted invite or an approved request gives an owner, keep behaving exactly as before (the owner is the
 * owner for every stay). A date = only a stay that STARTS on or after it is the owner's: earnings are worked out when a booking is
 * made, from the unit's owner, and a stay that began before the date is operator inventory (OwnerAttributionService).
 *
 * ⚠️ ADDITIVE ONLY: one nullable date column, no default, no index, no foreign key. Who gave the unit, and on whose authority, is
 * the audit row (`unit.owner_assigned`) — never a `*_by` column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->date('owner_since')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->dropColumn('owner_since');
        });
    }
};
