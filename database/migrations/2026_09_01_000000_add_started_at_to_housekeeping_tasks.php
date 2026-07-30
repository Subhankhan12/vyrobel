<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operator Housekeeping exact-match (#88) — a real START time so the board's "Elapsed {m}/{exp}m" progress
 * bar is genuine, not a fabricated timer. Set when a task advances to `in_progress` (the only transition that
 * begins the clock); `completed_at` already marks the end. Nullable — a task not yet started has no elapsed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housekeeping_tasks', function (Blueprint $table): void {
            $table->timestamp('started_at')->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_tasks', function (Blueprint $table): void {
            $table->dropColumn('started_at');
        });
    }
};
