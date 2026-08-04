<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QA D2-OBS-1 — give a payment a distinct, queryable `method` (cash / bank_transfer / card / …) instead of
 * leaving the real on-desk choice only inside the `metadata` JSON while the queryable `gateway` reads a
 * generic "manual". Purely a LABEL/reporting column — the money (amount, integer-minor, #17 reconciliation)
 * is untouched. Additive + nullable, and backfilled from the choice already stored in `metadata->method`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('method')->nullable()->after('gateway');
        });

        // Backfill the real method already captured in metadata (on-desk cash / bank transfer), so historical
        // rows read distinctly too. Money columns are never touched.
        DB::table('payments')
            ->whereNull('method')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.method')) IS NOT NULL")
            ->update(['method' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.method'))")]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('method');
        });
    }
};
