<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7c — OWNER TEAM (owner sub-tenancy). `owner_users` links a delegate USER to an OWNER with a fixed
 * owner-team role. This is the NESTED boundary: a sub-user ⊆ their owner ⊆ their org. The primary owner
 * (`owners.user_id`) is the implicit Co-owner; this table holds the ADDITIONAL team members.
 *
 * The three roles gate real capabilities (Phase 7c · SG2): `co_owner` (full — incl. team + payout settings),
 * `accountant` (earnings/statements/tax — read; NO payout settings, NO team mgmt), `view_only` (read
 * dashboards/calendar/reports — no actions, no exports). `status = 'disabled'` cleanly revokes access.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['co_owner', 'accountant', 'view_only'])->default('view_only');
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // One membership per (owner, user) — a user is a sub-user of an owner at most once.
            $table->unique(['owner_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_users');
    }
};
