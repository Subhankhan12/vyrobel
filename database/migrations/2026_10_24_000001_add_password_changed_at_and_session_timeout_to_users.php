<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-10-24, Part 3 of the Security plan — two facts the Security pages show and nothing stored:
 *   · `password_changed_at` — written by the User model whenever the password changes (every path: change, reset, set, invite);
 *     null means "not recorded yet", which is the truth for every account older than this column;
 *   · `session_timeout_minutes` — the person's own "sign me out after this long without activity" (15 / 30 / 60), enforced by
 *     EnforceSessionTimeout; null means off, the store's own lifetime, as before.
 * Additive and nullable: the old release keeps running on the new schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('password_changed_at')->nullable()->after('password');
            $table->unsignedSmallInteger('session_timeout_minutes')->nullable()->after('security_alerts_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['password_changed_at', 'session_timeout_minutes']);
        });
    }
};
