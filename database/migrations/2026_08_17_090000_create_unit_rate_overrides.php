<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D18-5 phase 1 — a rate may bind to a UNIT as an override, not only to a room type.
 *
 * ⚠️ A SEPARATE TABLE, NOT A COLUMN ON `rate_calendar`, AND THAT IS THE ZERO-MOVEMENT GUARANTEE. Adding a
 * nullable `unit_id` to `rate_calendar` would have meant altering the table every existing price lives in,
 * and would have made its `UNIQUE(rate_plan_id, date)` either wrong or dependent on a nullable column — and
 * MySQL treats NULLs as distinct, so that index would have gone quietly inert and allowed duplicate
 * type-level prices for one night (the DO-CORRECTNESS-1 waitlist lesson, on a money table). Leaving
 * `rate_calendar` **completely untouched** means no existing price can move: byte-for-byte, the old table is
 * the old table.
 *
 * ⚠️ EVERY COLUMN IN THE UNIQUE KEY IS NOT NULL, deliberately. `UNIQUE(rate_plan_id, unit_id, date)` means
 * one override per unit per plan per night, enforced by the database rather than by hope. There is no
 * "applies to all units" row — that case is the ABSENCE of a row, which is what makes the fallback total.
 *
 * ⚠️ REVERSIBLE WITH ZERO LOSS TO EXISTING DATA: `down()` drops only this new table. No type-level rate is
 * touched in either direction, so a rollback restores exactly today's behaviour.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_rate_overrides', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('rate_plan_id')->constrained('rate_plans')->cascadeOnDelete();
            // ⚠️ Cascade on unit deletion: an override for a unit that no longer exists is not a price, it is
            // a fragment that would resolve for nobody.
            $table->foreignUlid('unit_id')->constrained('units')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3);
            $table->timestamps();

            // ⚠️ NOT NULL throughout — see the class note. A nullable member here would make this inert.
            $table->unique(['rate_plan_id', 'unit_id', 'date'], 'unit_rate_overrides_plan_unit_date_unique');
            $table->index(['unit_id', 'date']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_rate_overrides');
    }
};
