<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `withholding_minor` to `owner_statements` (Wave D · tax engine · Phase 3) — the signed sum of the period's
 * `withholding` ledger lines (the tax withheld from the payout). Included in `net_source_minor` by construction
 * (the statement engine sums every bucket into net), so `net_payable_minor` — and the payout — are reduced by the
 * withheld amount and the page + PDF still reconcile to the cent (#17). Defaults to 0: every statement until a
 * withholding rate is configured for the owner's jurisdiction carries a genuine €0, and the line is omitted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_statements', function (Blueprint $table): void {
            $table->bigInteger('withholding_minor')->default(0)->after('payment_fee_minor');
        });
    }

    public function down(): void
    {
        Schema::table('owner_statements', function (Blueprint $table): void {
            $table->dropColumn('withholding_minor');
        });
    }
};
