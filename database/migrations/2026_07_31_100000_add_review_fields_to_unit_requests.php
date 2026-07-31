<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #104 GATE 1 — make the operator Unit-Requests review REAL (approve → create the unit · decline). Two additive
 * columns: `created_unit_id` links the fulfilled request to the REAL unit the operator created from it (clean
 * provenance — a fulfilled request points at what it became), and `review_note` records the operator's decline
 * reason / approval note (surfaced back to the owner). Both nullable, so every existing pending request is
 * untouched. `reviewed_by` / `reviewed_at` already exist on the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_requests', function (Blueprint $table): void {
            $table->foreignUlid('created_unit_id')->nullable()->after('status');   // the REAL unit an approval created
            $table->string('review_note')->nullable()->after('created_unit_id');   // decline reason / approval note
        });
    }

    public function down(): void
    {
        Schema::table('unit_requests', function (Blueprint $table): void {
            $table->dropColumn(['created_unit_id', 'review_note']);
        });
    }
};
