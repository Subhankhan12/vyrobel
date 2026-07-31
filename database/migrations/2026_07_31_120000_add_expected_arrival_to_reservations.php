<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #104 GATE 3 — capture the guest's ESTIMATED ARRIVAL (the New-Reservation "Estimated arrival" field was
 * collected but silently dropped). A free-text window ("3–6 PM") the front desk can see, so arrivals are no
 * longer flying blind. Deliberately a string, not a time: the wireframe's field is a free window, and inventing
 * a precise HH:MM to drive a time-of-day plot would be fabrication (#39) — the honest surface is the window as
 * captured. Nullable + additive, so every existing reservation is untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('expected_arrival', 60)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn('expected_arrival');
        });
    }
};
