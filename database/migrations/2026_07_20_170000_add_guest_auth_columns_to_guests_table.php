<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4a · Sub-gate 1 — make a Guest CLAIMABLE for portal access, WITHOUT disturbing the operator/owner auth
 * (that lives on `users`; this is a separate realm on `guests`). A booking's Guest row exists first (unclaimed);
 * claiming later sets the password + verifies the email.
 *
 *  - `password`           nullable — a booking exists before the account is claimed (null = unclaimed).
 *  - `remember_token`     the standard "remember me" column for the guest guard.
 *  - `email_verified_at`  MustVerifyEmail — the account isn't active until the email is verified.
 *  - `email_hash`         a BLIND INDEX: a keyed HMAC of the normalised email. `guests.email` is encrypted
 *                         (non-deterministic → not queryable), so auth/claim look a guest up by this hash, never
 *                         by the plaintext. Not unique (duplicate guest rows can share an email); indexed for lookup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->string('password')->nullable()->after('phone');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable()->after('password');
            $table->string('email_hash', 64)->nullable()->after('email');
            $table->index('email_hash');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->dropIndex(['email_hash']);
            $table->dropColumn(['password', 'remember_token', 'email_verified_at', 'email_hash']);
        });
    }
};
