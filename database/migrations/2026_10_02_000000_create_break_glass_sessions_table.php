<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADMIN 01 · PHASE 3 — BREAK-GLASS SESSIONS: the one audited exception to "an admin cannot enter /app". ⚠️ A NEW
 * TABLE — additive; nothing existing is touched.
 *
 * ⚠️ `target_organization_id`, NOT `organization_id`: this is a PLATFORM table (a record of Vyrobel's own
 * access), not a tenant table — it must never carry the tenant scope, and a tenant must never be able to read or
 * delete the record of who looked at them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('break_glass_sessions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('platform_admin_id')->index();
            $table->ulid('target_organization_id')->index();
            $table->ulid('viewing_user_id');
            $table->text('reason');
            $table->string('session_key_hash', 64)->unique();
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamp('ended_at')->nullable()->index();
            $table->string('ended_reason', 20)->nullable();
            $table->unsignedInteger('pages_viewed')->default(0);
            $table->timestamp('tenant_notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('break_glass_sessions');
    }
};
