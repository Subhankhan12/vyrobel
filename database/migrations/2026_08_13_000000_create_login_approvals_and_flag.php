<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner completeness sweep · Wave B · GATE 4 — LOGIN APPROVAL for new devices.
 *
 * Opt-in per user (`users.require_login_approval`). When ON, a password-correct sign-in from a device that is
 * NOT already trusted is held PENDING and an approval email is sent; the sign-in completes ONLY after the emailed
 * link is clicked (email-based device approval). The pending sign-in grants no authenticated access until then.
 * `login_approvals` stores each request with only the token's SHA-256 hash, an expiry, and single-use consumption.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('require_login_approval')->default(false)->after('two_factor_enabled');
        });

        Schema::create('login_approvals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();       // sha256(approval token) — never the token itself
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('approved_at')->nullable();     // set when the emailed link is clicked
            $table->timestamp('consumed_at')->nullable();     // set when the pending login is completed (single-use)
            $table->timestamp('expires_at')->index();         // short-lived — a stale request cannot be approved
            $table->timestamps();
            $table->index(['user_id', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_approvals');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('require_login_approval');
        });
    }
};
