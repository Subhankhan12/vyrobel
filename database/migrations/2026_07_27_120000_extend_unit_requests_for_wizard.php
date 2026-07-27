<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enrich `unit_requests` for the owner Add-Unit WIZARD (My Units · GATE 3). The wizard captures far more than the
 * original 3-field intake (name · kind · notes): where the unit is, its size & capacity, a PROPOSED rate/cleaning
 * fee + minimum stay. These are the OWNER's PROPOSED values on a still-PENDING request — NOT a live unit and NOT
 * a `rate_calendar` write (an owner cannot price inventory that doesn't exist yet, #39). Money is integer minor
 * units + currency (#17). Amenities attach through the shared polymorphic `amenitiables` pivot (reuse, never a
 * parallel table); photos through the shared `media` table. Purely additive — every column is nullable / defaulted,
 * so existing rows and the operator review screen are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_requests', function (Blueprint $table): void {
            $table->string('location')->nullable()->after('requested_kind');        // town / city (owner-typed)
            $table->string('street_address')->nullable()->after('location');         // street & number (owner-typed)
            $table->unsignedTinyInteger('bedrooms')->default(0)->after('street_address');
            $table->unsignedTinyInteger('bathrooms')->default(0)->after('bedrooms');
            $table->unsignedTinyInteger('sleeps')->default(0)->after('bathrooms');
            $table->unsignedBigInteger('requested_rate_minor')->nullable()->after('sleeps');   // PROPOSED nightly (#17)
            $table->unsignedBigInteger('cleaning_fee_minor')->nullable()->after('requested_rate_minor');
            $table->unsignedTinyInteger('min_nights')->nullable()->after('cleaning_fee_minor');
            $table->char('currency', 3)->nullable()->after('min_nights');
        });
    }

    public function down(): void
    {
        Schema::table('unit_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'location',
                'street_address',
                'bedrooms',
                'bathrooms',
                'sleeps',
                'requested_rate_minor',
                'cleaning_fee_minor',
                'min_nights',
                'currency',
            ]);
        });
    }
};
