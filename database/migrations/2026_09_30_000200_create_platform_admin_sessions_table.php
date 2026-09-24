<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADMIN 01 · PHASE 1 — platform-admin SESSIONS, tracked by the application itself.
 *
 * ⚠️⚠️ WHY A TABLE OF OUR OWN: production's session driver is `file` (measured), so Laravel's `sessions` table
 * holds nothing there and cannot list who is signed in. This table works on ANY session driver: each admin
 * session carries a random key in its session data, only the SHA-256 of that key is stored here, and a
 * middleware checks the row on every console request — so "force sign-out" is a real refusal on the very next
 * request, not a promise.
 *
 * Rows are ended, never deleted: an ended session is an audit fact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_admin_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('platform_admin_id')->index();
            $table->string('session_key_hash', 64)->unique();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('ended_at')->nullable()->index();
            $table->string('ended_reason', 40)->nullable();   // signed_out | forced | revoked
            $table->ulid('ended_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_admin_sessions');
    }
};
