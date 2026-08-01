<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operator New Reservation (GATE 1) — the "Guest accepts the house rules" checkbox was collected on the
 * wizard and silently dropped. Persist it as a real acknowledgement timestamp (who/when is captured by
 * the reservation's own audit trail). Nullable — a booking created without ticking it simply has no ack.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->timestamp('house_rules_ack_at')->nullable()->after('expected_arrival');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn('house_rules_ack_at');
        });
    }
};
