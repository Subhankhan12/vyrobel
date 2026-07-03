<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `owner_statements` — a period projection of `owner_ledger` for one owner (Phase 3 · Module 1
 * Step 4). A statement is a PURE PROJECTION: regenerating a DRAFT yields identical numbers; the
 * ledger stays the single source of truth. Finalizing LOCKS the period (`locked=true`) and stamps
 * `owner_ledger.statement_id` on every claimed line — the statement is then immutable and never
 * regenerated. Later activity dated into a locked period carries forward onto the next open
 * statement (gap-free: each ledger line is claimed by exactly one statement).
 *
 * Breakdown figures are in the LEDGER/booking currency (`source_currency`); `net_payable_minor` is in
 * the owner's payout `currency` — equal when they match, else converted at the operator-supplied
 * `fx_rate` (provenance in `fx_source`). No silent float, no auto-FX provider (deferred).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_statements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->char('currency', 3);          // owner payout currency
            $table->char('source_currency', 3);   // ledger/booking currency of the breakdown
            $table->enum('status', ['draft', 'finalized', 'paid'])->default('draft');
            $table->boolean('locked')->default(false);

            // Breakdown caches in source_currency (derived from the claimed ledger lines).
            $table->bigInteger('gross_minor')->default(0);
            $table->bigInteger('other_revenue_minor')->default(0);
            $table->bigInteger('commission_minor')->default(0);
            $table->bigInteger('management_fee_minor')->default(0);
            $table->bigInteger('expenses_minor')->default(0);
            $table->bigInteger('ota_cost_minor')->default(0);
            $table->bigInteger('adjustments_minor')->default(0); // prior-period carry-forward net
            $table->bigInteger('net_source_minor')->default(0);  // == sum of the breakdown == ledger net

            $table->decimal('fx_rate', 20, 10)->nullable();
            $table->string('fx_source')->nullable();
            $table->bigInteger('net_payable_minor')->default(0); // in payout currency

            $table->boolean('has_unverified')->default(false);   // pending OTA lines surfaced
            $table->unsignedBigInteger('sequence_no')->nullable(); // per-owner, gap-free, at finalize
            $table->timestamp('finalized_at')->nullable();
            $table->foreignUlid('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['owner_id', 'period_start', 'period_end']);
            $table->unique(['owner_id', 'sequence_no']);
            $table->index(['owner_id', 'status']);
        });

        // Close the Step 2 forward hook: owner_ledger.statement_id → owner_statements.
        Schema::table('owner_ledger', function (Blueprint $table) {
            $table->foreign('statement_id')->references('id')->on('owner_statements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('owner_ledger', function (Blueprint $table) {
            $table->dropForeign(['statement_id']);
        });
        Schema::dropIfExists('owner_statements');
    }
};
