<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner completeness sweep · Wave B · GATE 6 — NOTIFICATION QUIET HOURS.
 *
 * A per-user window during which routine EMAIL notifications are held back (the in-app feed still records
 * everything, so nothing is lost). Stored as two "HH:MM" strings; both null = quiet hours off. Interpreted in the
 * user's property timezone. Always-on (security) notifications ignore quiet hours — you always hear about those.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('quiet_hours_start', 5)->nullable()->after('require_login_approval');   // "22:00"
            $table->string('quiet_hours_end', 5)->nullable()->after('quiet_hours_start');           // "07:00"
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['quiet_hours_start', 'quiet_hours_end']);
        });
    }
};
