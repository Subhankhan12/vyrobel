<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner Agreement exact-match (#82) — additive contractual terms the wireframe surfaces (Term / Notice /
 * Auto-renew) that were not previously modelled. All NULLABLE so every existing agreement is undisturbed
 * and each renders HONESTLY (row omitted when null — never a fabricated "12 months"/"60 days"). Purely
 * additive; the certified attribution engine ignores them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('management_agreements', function (Blueprint $table) {
            $table->unsignedSmallInteger('term_months')->nullable()->after('payout_schedule');
            $table->unsignedSmallInteger('notice_days')->nullable()->after('term_months');
            $table->boolean('auto_renews')->nullable()->after('notice_days');
        });
    }

    public function down(): void
    {
        Schema::table('management_agreements', function (Blueprint $table) {
            $table->dropColumn(['term_months', 'notice_days', 'auto_renews']);
        });
    }
};
