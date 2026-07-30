<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operator Billing exact-match (#85) — the CANCEL side of the read-only mode. Where `paused_until` means
 * "read-only NOW, resuming on this future date" (a temporary PAUSE), `readonly_from` means "read-only FROM
 * this date onward" (a scheduled CANCELLATION at the paid-through date): the org keeps full access until the
 * date, then the workspace goes read-only indefinitely. Additive + nullable — every existing org is
 * undisturbed. `OperatorWorkspace` reads both; the pause/cancel state machine writes them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->timestamp('readonly_from')->nullable()->after('paused_until');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('readonly_from');
        });
    }
};
