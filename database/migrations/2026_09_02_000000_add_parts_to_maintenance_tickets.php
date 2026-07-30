<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operator Maintenance exact-match (#89) — the real labor/parts split + the awaiting-parts state.
 *
 *  • `labor_minor` + `parts_minor` — the real breakdown captured at resolve; **labor + parts === cost_minor**
 *    (invariant #17), so the Costs "Billable" table (Labor|Parts|Total) and the labor-vs-parts split are REAL,
 *    not a fabricated split of one figure. The owner_expense stays = `cost_minor` (the total) — the owner tie
 *    is unchanged.
 *  • `awaiting_parts` + `part_name` + `part_eta` — a real "waiting on a part" state on an ACTIVE ticket (the
 *    board's "Awaiting parts" filter + the Parts-on-order panel), never a fabricated parts row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table): void {
            $table->unsignedBigInteger('labor_minor')->nullable()->after('cost_minor');
            $table->unsignedBigInteger('parts_minor')->nullable()->after('labor_minor');
            $table->boolean('awaiting_parts')->default(false)->after('parts_minor');
            $table->string('part_name')->nullable()->after('awaiting_parts');
            $table->date('part_eta')->nullable()->after('part_name');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table): void {
            $table->dropColumn(['labor_minor', 'parts_minor', 'awaiting_parts', 'part_name', 'part_eta']);
        });
    }
};
