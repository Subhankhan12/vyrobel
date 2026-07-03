<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `payouts` — the disbursement record for a finalized owner statement's net payable (Phase 3 ·
 * Module 1 Step 5). Amount is Money in the owner's payout currency. The lifecycle is APPEND-ONLY:
 * status moves pending→paid or pending→failed via explicit transitions (with an audit trail), and a
 * retry / reversal is a NEW row (`supersedes_id` → the record it replaces) — never an edit that
 * erases history. `external_ref`/`connect_account_id` are for the money rail (Connect DEFERRED).
 *
 * A payout is only creatable from a FINALIZED (locked) statement, and only when its net is positive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignUlid('owner_statement_id')->constrained('owner_statements')->cascadeOnDelete();
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->enum('rail', ['manual', 'stripe_connect'])->default('manual');
            $table->enum('status', ['pending', 'paid', 'failed', 'reversed'])->default('pending');
            $table->string('external_ref')->nullable();       // bank ref / Stripe transfer id (deferred)
            $table->string('connect_account_id')->nullable(); // Stripe Connect acct (deferred)
            $table->foreignUlid('supersedes_id')->nullable()->constrained('payouts')->nullOnDelete();
            $table->foreignUlid('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('failure_reason')->nullable();
            $table->string('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index('owner_statement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
