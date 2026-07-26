<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SUPER ADMIN · BUILD 2 · GATE 1 — the LOGIN-ACTIVITY security signal.
 *
 * Auth events (successful login / failed attempt / lockout) are NOT domain events, so they are captured here
 * by ADDED listeners on the framework auth events — the tenant login/auth flow is NOT modified. Covers every
 * realm (operator/owner `web`, `guest`, and the platform `admin`).
 *
 * 🔒 PRIVACY (GDPR — built in from the first commit, not retrofitted):
 *   - PURPOSE: SECURITY MONITORING ONLY — spotting suspicious logins / repeated failures (probing), support.
 *     This is a security signal tied to AUTH events, NOT ambient tracking or browsing surveillance.
 *   - RETENTION: rows are PURGED after 90 days by the scheduled `platform:prune-activity` command (the IP + all).
 *   - LAWFUL BASIS: legitimate interest (account security / fraud prevention).
 *   - ACCESS: the platform /admin realm ONLY.
 *
 * `identifier` is the login email attempted/used (the account being accessed/targeted — the security signal);
 * we NEVER store passwords. `subject_*` identifies the actor on a SUCCESSFUL login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_activity', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('event');                         // login | failed | lockout
            $table->string('guard')->nullable();             // web | guest | admin (the realm)
            $table->string('subject_type')->nullable();      // user | guest | platform_admin (successful login)
            $table->string('subject_id')->nullable();
            $table->string('identifier')->nullable();        // the login email (security signal; purged at 90d)
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();   // indexed for recency + the retention prune
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_activity');
    }
};
