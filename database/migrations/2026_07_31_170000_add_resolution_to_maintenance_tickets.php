<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #108 (Work Order · Mark-resolved) — real resolve-time captures the wireframe's modal asks for:
 * `labor_hours` (Time spent — informational; hours are NOT auto-priced, no labour rate is modelled, so this
 * never affects the owner bill) and `resolution_notes` (What was done — also posted to the Activity timeline).
 * Both additive + nullable: existing tickets/behaviour + the reconciling cost split (labor_minor/parts_minor)
 * are unchanged. Money still bills the owner via `parts_minor` (#17); hours/notes are the work record.
 */
return new class extends Migration
{
    public function up(): void
    {
        // NOTE: no ->after('parts_minor') — parts_minor is added by a later-DATED migration (2026_09_02), so a
        // positional clause would fail on a fresh migrate. Column order is cosmetic; append at the end.
        Schema::table('maintenance_tickets', function (Blueprint $table): void {
            $table->decimal('labor_hours', 6, 2)->nullable();
            $table->text('resolution_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table): void {
            $table->dropColumn(['labor_hours', 'resolution_notes']);
        });
    }
};
