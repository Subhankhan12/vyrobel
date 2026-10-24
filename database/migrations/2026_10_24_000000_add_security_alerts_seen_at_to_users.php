<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-10-24 — the Security pages' "Sign-in blocked" card (App\Support\Security\SignInProblems) shows sign-ins refused on the
 * person's address after too many wrong passwords. "This was me" stamps this column; only refusals after it show again.
 * Additive and nullable: null means the person never dismissed one, so every refusal in the log's 90 days shows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('security_alerts_seen_at')->nullable()->after('require_login_approval');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('security_alerts_seen_at');
        });
    }
};
