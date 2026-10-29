<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-10-29 · I18N-01 — the console's language. Staff and owners choose theirs on their profile (`users.locale`) and guests
 * on their account (`guests.language`); a platform admin had no column, so the console printed English for everyone even
 * with its words translated. Additive and nullable: null means English, which is what every admin sees today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_admins', function (Blueprint $table): void {
            $table->string('locale', 10)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('platform_admins', function (Blueprint $table): void {
            $table->dropColumn('locale');
        });
    }
};
