<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADMIN 01 · PHASE 1 — the columns the platform console needs. ⚠️⚠️ ADDITIVE ONLY: new NULLABLE columns, no
 * rename, no drop, no NOT NULL on an existing table. That is what keeps the PREVIOUS release working while this
 * one is swapped in, and keeps rollback a symlink: the old code simply never reads these columns.
 *
 *  · platform_admins.role — the console role (owner | support | billing | analyst). ⚠️ NULL FAILS CLOSED:
 *    an admin with no role can do nothing (see PlatformAdminRole::fromAdmin). The one data write below gives
 *    every EXISTING admin the Owner role, because the only admins that exist are the founder's — measured on
 *    production before this was written: exactly one row. Every admin created after this carries an explicit
 *    role chosen at invite time.
 *  · platform_admins.invite_* — a pending invitation: only the SHA-256 of the one-time token is stored, never
 *    the token, so a database read cannot be turned into a working invite link.
 *  · platform_admin_actions.reason — the written reason every admin WRITE must now carry.
 *  · organizations.is_internal — marks Vyrobel's own test/demo/probe organisations, so no platform statistic
 *    counts them as customers. NULL = not internal (a customer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_admins', function (Blueprint $table): void {
            $table->string('role', 20)->nullable()->after('status');
            $table->string('invite_token_hash', 64)->nullable()->after('role');
            $table->timestamp('invited_at')->nullable()->after('invite_token_hash');
            $table->timestamp('invite_expires_at')->nullable()->after('invited_at');
            $table->ulid('invited_by')->nullable()->after('invite_expires_at');
        });

        Schema::table('platform_admin_actions', function (Blueprint $table): void {
            $table->text('reason')->nullable()->after('meta');
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->boolean('is_internal')->nullable()->after('status');
        });

        // The only admins in existence are the founder's own (measured: one row on production). They are the
        // Owner. ⚠️ `whereNull` — this can never overwrite a role somebody has already set.
        DB::table('platform_admins')->whereNull('role')->update(['role' => 'owner']);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn('is_internal');
        });

        Schema::table('platform_admin_actions', function (Blueprint $table): void {
            $table->dropColumn('reason');
        });

        Schema::table('platform_admins', function (Blueprint $table): void {
            $table->dropColumn(['role', 'invite_token_hash', 'invited_at', 'invite_expires_at', 'invited_by']);
        });
    }
};
