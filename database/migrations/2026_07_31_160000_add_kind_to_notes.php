<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #108 (Work Order detail) — a nullable `kind` on `notes` so a ticket's Activity composer can record the
 * REAL update TYPE (note / progress / part). Additive + nullable: every existing note (guest/reservation/…)
 * is unaffected (kind stays null → "note"). Reuses the shared polymorphic notes store — no parallel table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            $table->string('kind', 20)->nullable()->after('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table): void {
            $table->dropColumn('kind');
        });
    }
};
