<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `payment_fee_minor` to `owner_statements` (Wave C · GATE 4) — the signed sum of the period's
 * `payment_fee` ledger lines, the "Payment & ops fees" bucket in the gross→net breakdown. Included in
 * `net_source_minor` by construction (the statement engine sums every bucket into net), so the page +
 * PDF breakdown still reconcile to the cent (#17). Defaults to 0: existing statements — and every
 * statement until a live-payment writer posts a fee — carry a genuine €0, and the line is omitted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_statements', function (Blueprint $table): void {
            $table->bigInteger('payment_fee_minor')->default(0)->after('ota_cost_minor');
        });
    }

    public function down(): void
    {
        Schema::table('owner_statements', function (Blueprint $table): void {
            $table->dropColumn('payment_fee_minor');
        });
    }
};
