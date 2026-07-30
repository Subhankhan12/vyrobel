<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operator read-only mode (Operator Dashboard/Calendar exact-match) — a nullable operational-pause window.
 * When `paused_until` is a future timestamp the workspace is READ-ONLY: the operator can view everything +
 * check out in-house guests, but new bookings and edits are off (the wireframe's "Workspace paused" state).
 *
 * ORTHOGONAL to the subscription paywall: a paused org is still trialing/active (so EnsureSubscriptionActive
 * does NOT redirect it), it is just temporarily read-only. Additive + nullable → every existing org is
 * undisturbed (unset → never paused). The Billing subsystem (or a platform admin) sets/clears it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->timestamp('paused_until')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('paused_until');
        });
    }
};
