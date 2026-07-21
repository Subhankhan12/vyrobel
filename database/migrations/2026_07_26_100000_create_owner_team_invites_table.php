<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-TEAM invite records (Phase 7c · SG3). A PRIMARY OWNER invites a sub-user into THEIR OWN team by
 * email + an owner-team role → a hashed, single-use, expiring token is issued and emailed → the invitee
 * sets a password to activate and joins as an `owner_users` member of that owner at that role.
 *
 * The OWNER-REALM parallel of `owner_invites` (operator → owner): the SAME token discipline (sha256-only,
 * single-use via `status`, expiring, revocable, no enumeration) but different semantics — `owner_id` is the
 * owner the invitee JOINS (not an Owner party they become), and `role` is the fixed owner-team set
 * (co_owner/accountant/view_only), never the operator catalog. Tenant-scoped (BelongsToOrganization); the
 * invite can only ever land the sub-user under the `owner_id` on the row, so it can't be mis-scoped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_team_invites', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();   // the owner the sub-user joins
            $table->foreignUlid('invited_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('email');
            // sha256 hex of the high-entropy random token — the plaintext lives only in the email link.
            $table->string('token_hash', 64);
            $table->enum('role', ['co_owner', 'accountant', 'view_only']);   // the fixed owner-team role
            $table->enum('status', ['pending', 'accepted', 'expired', 'revoked'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index('token_hash');
            $table->index(['owner_id', 'email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_team_invites');
    }
};
