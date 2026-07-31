<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task #98 GATE 2. Two additions:
 *  • `fees.optional` — an "optional at booking" flag (task #98 custom Add-a-fee). A non-optional fee is applied
 *    to EVERY folio automatically (cleaning); an optional fee is offered at booking (guest can add it).
 *  • `properties.removal_*` — the scheduled ARCHIVE-NOT-DESTROY state machine: the property stays live until the
 *    last checkout, then is soft-deleted; cancellable before it runs. Records are RETAINED (never destroyed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table): void {
            $table->boolean('optional')->default(false)->after('taxable');
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->timestamp('removal_scheduled_at')->nullable()->after('bookings_paused_at');
            $table->timestamp('removal_effective_at')->nullable()->after('removal_scheduled_at');
            $table->string('removal_requested_by')->nullable()->after('removal_effective_at');
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table): void {
            $table->dropColumn('optional');
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn(['removal_scheduled_at', 'removal_effective_at', 'removal_requested_by']);
        });
    }
};
