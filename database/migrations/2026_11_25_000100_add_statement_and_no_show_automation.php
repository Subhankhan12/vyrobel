<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VY-26 (audit of 2026-11-24, read in code) — STATEMENTS AND NO-SHOWS, AUTOMATED WHEN THE OPERATOR CHOOSES. ⚠️ ADDITIVE: two columns.
 *
 *  - `organizations.statement_close_day` — the day of the month (1–28) on which last month's owner statements are closed automatically,
 *    exactly as "Close period" closes one by hand. NULL = off (every organisation, until it chooses): statements stay manual.
 *  - `property_policies.auto_no_show` — a confirmed booking whose guest has not checked in by the property's check-out time the day after
 *    arrival is marked a no-show (its room released). Default FALSE: nothing is marked by a machine until the property asks for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->unsignedTinyInteger('statement_close_day')->nullable();
        });

        Schema::table('property_policies', function (Blueprint $table): void {
            $table->boolean('auto_no_show')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('property_policies', function (Blueprint $table): void {
            $table->dropColumn('auto_no_show');
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn('statement_close_day');
        });
    }
};
