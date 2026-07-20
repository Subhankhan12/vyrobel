<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4b · Sub-gate 4 — the arrival pack + guest pre-check-in.
 *
 * A SIMPLE arrival-info store on the physical unit (the door code + wifi are the unit's, not the booking's): the
 * guest portal renders these ONLY for a booking the authed guest owns, and only within the stay's arrival window
 * (App\Support\Guest\ArrivalPack). `door_code` + `wifi_password` are physically/credential-sensitive, so they are
 * ENCRYPTED at rest (the encrypted cast) — defence-in-depth beneath the owned-booking + timing guards. Directions
 * come from the property's real address (no new column), so the arrival screen and the Phase-3 pre-arrival email
 * read the SAME source.
 *
 * `reservations.guest_checked_in_at` is the GUEST's pre-check-in flag — a submitted intent, DISTINCT from the
 * operator's authoritative check-in (`status = 'checked_in'`, set by FrontDeskService). The guest confirming their
 * details never moves the booking's status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->text('door_code')->nullable()->after('condition_status');       // encrypted cast
            $table->string('wifi_ssid')->nullable()->after('door_code');
            $table->text('wifi_password')->nullable()->after('wifi_ssid');           // encrypted cast
            $table->text('arrival_notes')->nullable()->after('wifi_password');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            // The guest's pre-check-in submission — NOT the operator's authoritative check-in (status='checked_in').
            $table->timestamp('guest_checked_in_at')->nullable()->after('booked_at');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->dropColumn(['door_code', 'wifi_ssid', 'wifi_password', 'arrival_notes']);
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn('guest_checked_in_at');
        });
    }
};
