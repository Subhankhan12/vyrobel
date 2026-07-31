<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #104b GATE 3 — the Team-Schedule interactive rota's two new (minimal-real) models:
 *  • `schedule_publications` — a per-(property, week) "published" stamp. Edits stay LIVE; "Publish week" =
 *    finalize + notify the rostered staff (no draft buffer — the honest posture the PDF already documents).
 *  • `open_shifts` — an unassigned shift slot (a Shift row can't be open: `shifts.user_id` is non-nullable +
 *    unique(property,user,date)). Assigning one creates a real Shift via ShiftRosterService + deletes the open row.
 * Both tenant-scoped (BelongsToOrganization). Additive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_publications', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->index();
            $table->foreignUlid('property_id')->index();
            $table->date('week_starts_on');              // the Monday of the published week
            $table->timestamp('published_at');
            $table->foreignUlid('published_by')->nullable();
            $table->timestamps();
            $table->unique(['property_id', 'week_starts_on']);
        });

        Schema::create('open_shifts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->index();
            $table->foreignUlid('property_id')->index();
            $table->date('date');
            $table->enum('shift_type', ['early', 'day', 'late']);   // an open slot is a WORKING shift (never 'off')
            $table->string('department', 40)->nullable();
            $table->foreignUlid('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_shifts');
        Schema::dropIfExists('schedule_publications');
    }
};
