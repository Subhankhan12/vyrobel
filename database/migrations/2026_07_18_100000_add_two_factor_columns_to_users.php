<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two-factor authentication (TOTP) columns. `two_factor_secret` already existed (text, nullable) but was
 * stored PLAINTEXT and unused — the model now casts it `encrypted` (no data migration: it's empty). This adds
 * the two missing columns:
 *   - `two_factor_recovery_codes` — encrypted JSON of one-time recovery codes ({code, used_at}); model casts
 *     it `encrypted:array`, so `text` holds the ciphertext.
 *   - `two_factor_confirmed_at`   — set when the user CONFIRMS enrollment with a valid code. 2FA is ACTIVE iff
 *     the secret is present AND this is non-null (User::hasTwoFactorEnabled), so a generated-but-unconfirmed
 *     secret never gates login (the anti-lockout confirm step).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
