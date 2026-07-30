<?php

use App\Models\Guest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operator Guest-CRM exact-match (#87) — the three columns the guest profile draws that no column backed:
 *
 *  • `city`         — a coarse residence ("Dublin, Ireland" on the wireframe). NOT encrypted: a city is not an
 *                     exact address, and it must be searchable/displayable like the name. `nationality` (char2)
 *                     is a nationality, a different fact — this is the residence the Contact card + edit modal show.
 *  • `prefers_email` — the guest's preferred contact channel (the "Prefers email" toggle). Default true.
 *  • `preferences`   — a structured set of stay preferences (sea_view/high_floor/late_checkout/quiet_room/…),
 *                     shown to the front desk + housekeeping. A JSON array of active keys from a fixed catalog.
 *
 * All three are personal data ABOUT the guest, so {@see Guest::anonymize()} nulls them on erasure
 * (added in the same change) — never leave a residence/preference behind after a right-to-erasure request.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->string('city')->nullable()->after('phone');
            $table->boolean('prefers_email')->default(true)->after('language');
            $table->json('preferences')->nullable()->after('marketing_consent');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->dropColumn(['city', 'prefers_email', 'preferences']);
        });
    }
};
