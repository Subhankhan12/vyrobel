<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D1-2 — a staff member's PERSONAL dashboard display preference. Today that is one field: how the derived
 * task list is grouped (by priority, the design's default, or by category).
 *
 * ⚠️ WHY THIS IS A SIBLING TABLE AND NOT A ROW IN `operator_task_states` (§N.27.4 — de-duplication is not
 * flatten-to-anything-that-looks-similar). The two share a discipline — personal, generated NOT NULL key,
 * unique, self-only — but NOT a key: a tick is scoped to a **day** (`task_date` is part of its uniqueness, so
 * yesterday's tick cannot suppress today's task), while a display preference must **outlive the day**.
 * Storing it there would have meant either a sentinel date or a preference that silently reset every
 * midnight. Same shape, different lifetime — so the shape is mirrored, not the table.
 *
 * ⚠️ NO NULLABLE KEY COLUMNS. Both key columns are NOT NULL, so the unique index genuinely applies — the
 * NULL-inert-index lesson this repo learned on `guest_waitlist_entries` (`DO-CORRECTNESS-1`) and applied
 * again in D1-1's `scope_key`.
 *
 * ⚠️ NOT scoped per property, deliberately: this is how a person likes their list arranged, not a fact about
 * a property. A grouping that flipped when you switched properties would read as a bug.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_dashboard_preferences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            // 'priority' | 'category' — validated against a closed set on the way in.
            $table->string('task_grouping', 16)->default('priority');
            $table->timestamps();

            // 🔒 One preference row per person per org — idempotency by construction.
            $table->unique(['organization_id', 'user_id'], 'operator_dashboard_prefs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_dashboard_preferences');
    }
};
