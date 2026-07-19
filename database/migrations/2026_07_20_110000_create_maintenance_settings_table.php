<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 · Sub-gate 4 — real, TYPED storage for the operator Maintenance Settings screen (one row per org).
 * Deliberately typed columns via a SettingsService-style write path, NOT the dormant generic KV `Setting`
 * scaffold (the audit flagged that scaffold as a trap — the shipped pattern is typed columns on a real model).
 *
 *   • SLA resolution targets (hours) per priority.
 *   • Category → assignee (technician) map — one nullable user FK per maintenance category (the 5-value enum
 *     that already exists on maintenance_tickets), so a new order can be auto-routed.
 *   • Out-of-order policy + notification preferences (booleans). ⚠️ The notification toggles STORE the
 *     preference now; the live dispatch waits on the Phase-3 notification subsystem. `require_photo_on_resolve`
 *     is stored too, but its enforcement waits on ticket photos (not yet a feature) — both honestly deferred.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_settings', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->unique()->constrained()->cascadeOnDelete();

            // Resolution targets (hours).
            $table->unsignedSmallInteger('sla_urgent_hours')->default(4);
            $table->unsignedSmallInteger('sla_high_hours')->default(8);
            $table->unsignedSmallInteger('sla_normal_hours')->default(48);
            $table->unsignedSmallInteger('sla_low_hours')->default(168);

            // Category → technician map (one nullable FK per maintenance category).
            $table->foreignUlid('assignee_plumbing_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('assignee_electrical_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('assignee_appliance_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('assignee_structural_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('assignee_other_id')->nullable()->constrained('users')->nullOnDelete();

            // Out-of-order policy.
            $table->boolean('require_photo_on_resolve')->default(false);   // enforcement deferred (no ticket photos yet)
            $table->boolean('block_room_from_sale')->default(true);
            $table->boolean('notify_front_desk')->default(true);

            // Notification preferences — stored now; live dispatch deferred to the Phase-3 notification subsystem.
            $table->boolean('notify_new_urgent')->default(true);
            $table->boolean('notify_out_of_service')->default(true);
            $table->boolean('notify_parts_arrive')->default(false);
            $table->boolean('notify_sla_breach')->default(true);
            $table->boolean('notify_daily_digest')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_settings');
    }
};
