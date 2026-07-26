<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SUPER ADMIN · GATE 0 — the platform-admin identity table.
 *
 * A platform admin is the founder's console operator. It is DELIBERATELY a SEPARATE table from `users`:
 *   - a platform admin is NOT a tenant user, so a tenant user can never authenticate into /admin (realm
 *     separation by construction — the same principle the `guest` realm uses against `web`);
 *   - it has NO `organization_id` — it sits ABOVE tenancy, never inside an org's scope;
 *   - it holds NO spatie roles — it is NOT the (unassignable) global `Super Admin` role, so the deferred
 *     Phase-7a "global Super Admin assignability" escalation surface simply does not apply to it.
 *
 * The 2FA columns mirror `users` (TOTP via google2fa): a secret + confirmation timestamp + encrypted
 * one-time recovery codes. 2FA is MANDATORY for platform admins (enforced in the admin realm middleware),
 * so a seeded admin must enrol before reaching any /admin page.
 *
 * ⚠️ ADDITIVE ONLY — this is a NEW table. It touches nothing existing (no FK to users/organizations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_admins', function (Blueprint $table): void {
            $table->ulid('id')->primary();                  // ULID PK — uniform with the rest of the schema
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            // 2FA (TOTP) — same shape/casts as users; secret + recovery codes are ENCRYPTED at rest.
            $table->text('two_factor_secret')->nullable();
            $table->boolean('two_factor_enabled')->default(false);   // display mirror only
            $table->text('two_factor_recovery_codes')->nullable();   // encrypted:array {code, used_at}
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('status')->default('active');    // active | disabled
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_admins');
    }
};
