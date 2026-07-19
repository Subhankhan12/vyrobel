<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 · Sub-gate 1 — EXTEND the existing `groups` table (never a parallel structure) for the operator
 * Group Booking screen. `name`, `block_code`, `pickup_deadline` (the tentative "option held until"), and
 * `contact` (the organiser) already exist; this adds the two group-level facts the wizard needs:
 *   • `occasion`     — the block type (wedding / corporate / family / sports), descriptive.
 *   • `billing_mode` — the folio roll-up: `master` (organiser settles ONE bill) vs `individual` (a folio per
 *     unit). Typed enum (a real value, not the dormant KV Setting scaffold) because it drives folio behaviour.
 * Hold type (confirmed / tentative) is carried by the member reservations' own `status` enum — no new column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->string('occasion')->nullable()->after('name');
            $table->enum('billing_mode', ['individual', 'master'])->default('individual')->after('block_code');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->dropColumn(['occasion', 'billing_mode']);
        });
    }
};
