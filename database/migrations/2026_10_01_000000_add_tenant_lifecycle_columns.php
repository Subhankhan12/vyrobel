<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADMIN 01 · PHASE 2 — tenant lifecycle. ⚠️ ADDITIVE ONLY: three new NULLABLE columns, nothing renamed, dropped or
 * tightened, so the previous release keeps working against this schema during the swap.
 *
 *   · `signup_source`          — how the organisation was created: email · google · twitter. ⚠️ NULL = UNKNOWN.
 *                                Nothing recorded it before this migration, and there is deliberately NO backfill:
 *                                "came through the email path" and "unknown" are different claims.
 *   · `lifecycle_lock`         — `baseline` (every console write refused) · `confirm` (a lifecycle action needs the
 *                                organisation's exact name typed as a second confirmation). NULL = no lock.
 *   · `suspended_from_status`  — the status an organisation had when it was suspended, so reactivating it restores
 *                                THAT status (a trialing hotel stays trialing) instead of guessing `active`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('signup_source', 20)->nullable();
            $table->string('lifecycle_lock', 20)->nullable();
            $table->string('suspended_from_status', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn(['signup_source', 'lifecycle_lock', 'suspended_from_status']);
        });
    }
};
