<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #96 — the operator Profile's personal details + UI preferences. `locale` (Language) already exists; this adds
 * the wireframe's remaining REAL fields: preferred_name, phone, a per-user timezone, and the four UI preferences
 * (compact density, onboarding tips, reduce motion, date format) — genuinely stored + applied, never a fake save.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('preferred_name')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('timezone')->nullable()->after('locale');
            $table->boolean('compact_density')->default(false)->after('timezone');
            $table->boolean('onboarding_tips')->default(true)->after('compact_density');
            $table->boolean('reduce_motion')->default(false)->after('onboarding_tips');
            $table->string('date_format', 20)->default('DD/MM/YYYY')->after('reduce_motion');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['preferred_name', 'phone', 'timezone', 'compact_density', 'onboarding_tips', 'reduce_motion', 'date_format']);
        });
    }
};
