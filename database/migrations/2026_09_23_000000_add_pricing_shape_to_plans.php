<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BILLING PASS 1 — a plan carries its PRICING SHAPE (2026-08-13).
 *
 * The founder ruled that pricing is configurable DATA supporting BOTH shapes, managed from a later super-admin
 * panel — which dissolves the old §G.1 "model A vs model B" contradiction: there is no single model to choose,
 * there is a plan ENGINE that must charge faithfully to whatever shape a plan declares.
 *
 *   · FLAT     — one price regardless of unit count. Uses the existing `price_minor`. Quantity is always 1.
 *   · PER_UNIT — `unit_rate_minor` × the org's REAL active-unit count, + `vat_pct`. Quantity is the unit count.
 *
 * ⚠️ `pricing_type` DEFAULTS TO 'flat', so every existing row stays valid and keeps charging exactly as it does
 * today — the four seeded tier plans are flat and remain flat. Nothing about an existing subscription moves.
 *
 * ⚠️ THE PER-UNIT RATE LIVES ON THE PLAN, NOT IN CONFIG. `config('billing.per_unit_minor')` is a global
 * placeholder that cannot express "this plan charges €9 and that one €12" — which is precisely what a
 * configurable catalogue needs. The config value seeds the column; the column is what the engine charges from.
 * ⚠️ The unit COUNT is deliberately NOT stored here: a stored count is a copy that drifts from reality, and a
 * charged quantity that disagrees with the org's actual units is the §N.8d false-claim shape with money
 * attached. It is derived at charge time from the one source (see App\Support\Billing\BillableUnits).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            $table->string('pricing_type', 16)->default('flat')->after('price_minor');
            // Per-unit only; null on a flat plan (the shape decides which fields are meaningful).
            $table->unsignedBigInteger('unit_rate_minor')->nullable()->after('pricing_type');
            $table->unsignedTinyInteger('vat_pct')->nullable()->after('unit_rate_minor');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            $table->dropColumn(['pricing_type', 'unit_rate_minor', 'vat_pct']);
        });
    }
};
