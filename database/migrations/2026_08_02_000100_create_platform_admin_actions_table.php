<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SUPER ADMIN · GATE 0 — the platform-admin ACTION LEDGER (audit of what the platform operator did).
 *
 * DELIBERATELY SEPARATE from the tenant `audit_events` table:
 *   - a platform-admin action is a PLATFORM event, not a tenant event — keeping it in its own immutable
 *     ledger keeps the tenant audit views clean and gives an unambiguous "what did the founder's console
 *     do" feed (no `actor_type` enum change on the shared tenant table → nothing existing is disturbed);
 *   - every cross-tenant read (the deliberate `runAsSuperAdmin` bypass) and every admin write is recorded
 *     here with the acting admin + IP + user-agent, so the highest-privilege surface is fully accountable.
 *
 * Write-once / immutable (no `updated_at`). `target_type`/`target_id` are plain strings the logger controls
 * (NOT an eloquent morph relation), so they never touch the enforced morph map.
 *
 * ⚠️ ADDITIVE ONLY — a NEW table, no FK into existing tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_admin_actions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('platform_admin_id')->nullable()->index();   // the acting admin (no FK — a log outlives the row)
            $table->string('action');                                 // e.g. platform.login, platform.cross_tenant_read
            $table->string('target_type')->nullable();                // plain descriptor, not a morph type
            $table->string('target_id')->nullable();
            $table->json('meta')->nullable();                         // small, non-PII context (counts, route, etc.)
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_admin_actions');
    }
};
