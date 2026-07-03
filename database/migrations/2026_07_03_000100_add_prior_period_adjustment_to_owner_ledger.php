<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `owner_ledger.prior_period_adjustment` (Phase 3 · Module 1 Step 4) — set true when a line is posted
 * with a `service_date` that falls inside an already-LOCKED statement period (a post-lock refund,
 * expense, or correction). The line keeps its true service_date but is EXPLICITLY marked so the next
 * open statement labels it a prior-period adjustment. This is the explicit lock boundary the
 * attribution/expense services consult — locked statements are never edited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_ledger', function (Blueprint $table) {
            $table->boolean('prior_period_adjustment')->default(false)->after('unverified');
        });
    }

    public function down(): void
    {
        Schema::table('owner_ledger', function (Blueprint $table) {
            $table->dropColumn('prior_period_adjustment');
        });
    }
};
