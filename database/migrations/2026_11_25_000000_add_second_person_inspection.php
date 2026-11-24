<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VY-27 (audit of 2026-11-24, read in code) — AN OPTIONAL "INSPECTION BY A SECOND PERSON". ⚠️ ADDITIVE: two columns.
 *
 * A room could be marked clean and then inspected by the same person, so "inspected" said nothing a cleaner had not already said.
 *
 *  - `property_policies.inspection_by_second_person` — when on, the person who marked a room clean cannot also mark it inspected.
 *    Default FALSE: every property keeps working exactly as before until it chooses the rule (Settings → Policies).
 *  - `housekeeping_tasks.cleaned_by` — WHO marked the room clean (until now only the audit trail knew, and `inspected_by` had no
 *    counterpart to compare with). NULL for every task cleaned before this column existed: the rule cannot know who cleaned those, so
 *    it does not refuse them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_policies', function (Blueprint $table): void {
            $table->boolean('inspection_by_second_person')->default(false);
        });

        Schema::table('housekeeping_tasks', function (Blueprint $table): void {
            $table->foreignUlid('cleaned_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cleaned_by');
        });

        Schema::table('property_policies', function (Blueprint $table): void {
            $table->dropColumn('inspection_by_second_person');
        });
    }
};
