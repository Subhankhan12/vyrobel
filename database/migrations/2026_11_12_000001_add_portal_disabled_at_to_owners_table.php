<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DEC-o (2026-11-12) — an operator can switch an OWNER'S PORTAL ACCESS off and on.
 *
 * `portal_disabled_at` is the whole state: NULL = the portal is on (every existing owner, unchanged), a timestamp =
 * it was switched off then. It sits on the OWNER, not on a login, so the owner's team goes with them and comes back
 * with them with nothing to restore, and nobody's `users.status` / `owner_users` / Membership is ever touched (a person
 * who also works for the operator keeps their login). Who switched it, and why, is the audit row — never a column
 * (a `*_by` column without a foreign key would also trip the organisation-deletion column census).
 *
 * ⚠️ ADDITIVE ONLY: one nullable column; `owners.status` (an enum other code reads as "activated") is deliberately not extended.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table): void {
            $table->timestamp('portal_disabled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table): void {
            $table->dropColumn('portal_disabled_at');
        });
    }
};
