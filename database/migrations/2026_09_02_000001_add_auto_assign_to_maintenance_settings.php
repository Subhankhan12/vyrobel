<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operator Maintenance Settings exact-match (#89) — the Auto-assignment MASTER toggle. The per-category
 * assignee map already exists (`assignee_{category}_id`); this is the on/off switch the wireframe draws, and
 * it genuinely gates whether {@see \App\Services\MaintenanceService::open()} routes a new ticket to the
 * category's technician (the map was dead config until #89 wired it).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_settings', function (Blueprint $table): void {
            $table->boolean('auto_assign_enabled')->default(false)->after('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_settings', function (Blueprint $table): void {
            $table->dropColumn('auto_assign_enabled');
        });
    }
};
