<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BOOKING TERMS (his ruling, 2026-11-14 — option B, D-BOOK-NO-TERMS). ⚠️ ADDITIVE: four nullable columns, nothing changed.
 *
 *  - `reservations.terms_accepted_at`  when the guest ticked the box on the public checkout. NULL = no acceptance on record (a booking made
 *    by the operator, an OTA, a group, or a direct booking made before this existed) — never a guess.
 *  - `reservations.terms_version`      a short hash of the FACTS the terms were built from (policy values + the hotel's own text), so
 *    "which terms were these?" has an answer that does not depend on the language they were shown in.
 *  - `reservations.terms_snapshot`     the terms exactly as the guest saw them (their language, their stay's free-cancel date), so a later
 *    edit of the property's rules can never rewrite what a past guest agreed to. ⚠️ It holds the HOTEL'S text only — no guest data.
 *  - `property_policies.booking_terms_extra`  the hotel's own words (house rules, what is charged after the free-cancel date), shown to guests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->timestamp('terms_accepted_at')->nullable()->after('house_rules_ack_at');
            $table->string('terms_version', 24)->nullable()->after('terms_accepted_at');
            $table->json('terms_snapshot')->nullable()->after('terms_version');
        });

        Schema::table('property_policies', function (Blueprint $table): void {
            $table->text('booking_terms_extra')->nullable()->after('long_stay_discounts');
        });
    }

    public function down(): void
    {
        Schema::table('property_policies', function (Blueprint $table): void {
            $table->dropColumn('booking_terms_extra');
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn(['terms_accepted_at', 'terms_version', 'terms_snapshot']);
        });
    }
};
