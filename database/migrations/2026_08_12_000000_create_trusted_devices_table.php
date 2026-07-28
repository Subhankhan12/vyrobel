<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner completeness sweep · Wave B · GATE 4 — TRUSTED DEVICES (skip-2FA-from-here).
 *
 * A device the user has chosen to trust after a successful 2FA challenge. The browser holds a random bearer
 * token in an httpOnly cookie; only its SHA-256 HASH is stored here (a stolen DB row cannot reconstruct the
 * cookie). A matching, non-expired, non-revoked row lets that browser SKIP the 2FA challenge on the next login
 * (the same "remember this device" pattern as GitHub/Google). Bounded by `expires_at` + revocation; self-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();       // sha256(cookie token) — never the token itself
            $table->string('label')->nullable();              // a human label derived from the user-agent
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->index();         // hard expiry — a trust does not last forever
            $table->timestamp('revoked_at')->nullable();      // an explicit remove (kept for the audit trail)
            $table->timestamps();
            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
