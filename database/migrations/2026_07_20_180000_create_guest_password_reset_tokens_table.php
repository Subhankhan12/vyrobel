<?php

use App\Models\Guest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4a · Sub-gate 3 — the GUEST realm's password-reset token store, separate from the operator/owner
 * `password_reset_tokens` (realm separation). ⚠️ The key column stores the guest's BLIND INDEX (`email_hash`),
 * NEVER the plaintext email — {@see Guest::getEmailForPasswordReset()} returns the hash, so no guest
 * PII is written to this table (invariant #28). The token itself is stored HASHED by the framework broker.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();   // the guest email_hash (blind index) — never plaintext
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_password_reset_tokens');
    }
};
