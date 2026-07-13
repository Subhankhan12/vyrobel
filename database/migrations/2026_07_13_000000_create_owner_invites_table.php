<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-invite records (Auth Phase B). An operator invites an owner → a hashed, single-use, expiring
 * token is issued and emailed → the owner sets a password to activate. Security: the token is stored
 * ONLY as a sha256 hash (never plaintext); single-use via `status`; expiring via `expires_at`; tied to
 * the specific email + organization; revocable. Tenant-scoped (BelongsToOrganization).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_invites', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignUlid('invited_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('email');
            // sha256 hex of the high-entropy random token — the plaintext lives only in the email link.
            $table->string('token_hash', 64);
            $table->string('role')->default('owner');           // only 'owner' is wired in Phase B
            $table->json('assigned_unit_ids')->nullable();       // applied to the owner on acceptance
            $table->decimal('commission_pct', 5, 2)->nullable(); // management-agreement commission (modal)
            $table->enum('status', ['pending', 'accepted', 'expired', 'revoked'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index('token_hash');
            $table->index(['organization_id', 'email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_invites');
    }
};
