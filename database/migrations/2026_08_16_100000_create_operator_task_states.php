<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D1-1 — `operator_task_states`: a staff member's PERSONAL, PER-DAY tick against a dashboard task row.
 *
 * ⚠️ THIS IS NOT A COMPLETION RECORD, AND THE TABLE NAME IS THE ONLY PLACE THAT COULD SUGGEST OTHERWISE.
 * The dashboard's "Today's tasks" are DERIVED live from real counts (urgent work orders · rooms to clean ·
 * arrivals still to check in) and vanish on their own when the count reaches zero. Nothing here can make a
 * task "done": the work is done when the underlying count clears, and a row with a live count > 0 keeps
 * showing that count whether or not it has been ticked. Storing "done" against a number the app can
 * independently see is non-zero would be a fabricated flag (#39) — the register warned about exactly that.
 *
 * ⚠️ PERSONAL, NOT A TEAM SIGNAL. The register left this open ("a personal note or a shared team signal —
 * they are different tables"). It is personal, keyed by `user_id`: the prototype persisted these ticks in
 * `localStorage` (per-browser, per-person, non-authoritative), so a per-user store is the faithful
 * translation rather than a compromise — and one person's tick must never hide urgent work from the rest of
 * the team.
 *
 * ⚠️ PER-DAY. `task_date` is part of the key so a tick does not silently suppress the same recurring task
 * tomorrow. Yesterday's "I've looked at the arrivals" says nothing about today's.
 *
 * ⚠️ `scope_key` IS GENERATED AND NOT NULL, AND THAT IS DELIBERATE. The dashboard reads either ONE selected
 * property or ALL of them, so the natural column would be a NULLABLE `property_id` ("null = all") — and in
 * MySQL a UNIQUE index treats NULLs as DISTINCT, so the constraint would be silently inert for every
 * all-properties tick and double-writes would sail through. This repo learned that on `guest_waitlist_entries`
 * (`DO-CORRECTNESS-1`); the fix there was a generated non-null key, and it is the fix here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_task_states', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            // The property the tick was made under: a property ULID, or the literal 'all'. NEVER null.
            $table->string('scope_key', 32);
            // Which derived row — a value from the CLOSED key set the dashboard produces, never free text.
            $table->string('task_key', 64);
            $table->date('task_date');
            $table->timestamps();

            // 🔒 ONE tick per person, per scope, per task, per day — idempotency by construction, so a
            // double-submit cannot write twice even if a caller forgets to guard.
            $table->unique(['user_id', 'scope_key', 'task_key', 'task_date'], 'operator_task_states_unique');
            $table->index(['organization_id', 'task_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_task_states');
    }
};
