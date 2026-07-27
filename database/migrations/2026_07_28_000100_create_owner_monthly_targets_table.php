<?php

use App\Models\Scopes\OwnerScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `owner_monthly_targets` — the owner's OWN monthly NET-revenue goal (GATE 1, Owner Bookings exact-match).
 *
 * WHY THIS EXISTS: the Bookings wireframe shows a "Monthly goal" ring ("123% of goal", "Target €6k /mo").
 * That percentage is only honest (#39) if it divides a REAL net by a REAL target — so the target is a value
 * the OWNER sets themselves, stored here, never a fabricated constant. `% = current-month net ÷ target_minor`.
 *
 * Scope: one row per (owner, currency) — a portfolio spanning currencies keeps a target per currency (money is
 * never summed across currencies, invariant #17). Owner-scoped exactly like `owner_ledger`: `organization_id`
 * (tenant) + `owner_id` (owner boundary), with the read {@see OwnerScope} on the model.
 * The unique key makes the write an idempotent upsert (set-your-target, not append).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_monthly_targets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->char('currency', 3);
            $table->bigInteger('target_minor'); // always positive minor units; the owner's monthly net goal
            $table->timestamps();

            // One target per owner per currency — the write is an upsert, not an append.
            $table->unique(['owner_id', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_monthly_targets');
    }
};
