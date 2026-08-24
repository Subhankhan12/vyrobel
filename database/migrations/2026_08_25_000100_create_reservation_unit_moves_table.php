<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️ THE MID-STAY MOVE RECORD — the fact the product could not previously express.
 *
 * ⚠️ TWO DATES, DELIBERATELY. `effective_date` is what the operator STATES; `stated_at` is when they
 * stated it. ⚠️ THE GAP BETWEEN THEM IS THE ENTIRE MANIPULATION SIGNAL: a move logged three weeks late
 * leaves a permanent, visible mark, and a lone effective date cannot be told apart from an honest one —
 * which is exactly the property backdating exploits. The backdating hazard was stated and accepted, so
 * this makes it VISIBLE rather than impossible.
 *
 * ⚠️ NO INVENTORY CHANGE. The reservation keeps ONE line; `assignUnit()` still updates its `unit_id` to
 * the CURRENT unit, so `ArrivalPack` and the five other `reservationUnits->first()` readers keep showing
 * the room the guest is actually in. Splitting the line into two would have shown a moved guest the door
 * code of the room they left.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_unit_moves', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('organization_id')->index();
            $table->ulid('reservation_unit_id')->index();
            $table->ulid('from_unit_id')->nullable();
            $table->ulid('to_unit_id');

            // ⚠️ THE BOUNDARY, IN ONE SENTENCE, WHEREVER IT APPEARS: the effective date is THE FIRST NIGHT
            //    THE GUEST SLEPT IN THE NEW UNIT. Nights before it belong to the old unit's owner; that
            //    night and after belong to the new one.
            $table->date('effective_date');
            $table->timestamp('stated_at');
            $table->ulid('stated_by_user_id')->nullable();

            $table->timestamps();
            $table->index(['reservation_unit_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_unit_moves');
    }
};
