<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SUPER ADMIN · ACCOUNT — `platform_admins.pending_email` for the re-verified email-change flow (mirrors
 * `users.pending_email`): a requested new login email is PARKED here until a signed verify link opened from the
 * NEW inbox confirms control — the live `email` (the login) is unchanged until then. ⚠️ ADDITIVE ONLY.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_admins', function (Blueprint $table): void {
            $table->string('pending_email')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('platform_admins', function (Blueprint $table): void {
            $table->dropColumn('pending_email');
        });
    }
};
