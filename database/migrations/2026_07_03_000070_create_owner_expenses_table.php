<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `owner_expenses` — owner-attributable costs (cleaning, maintenance, other) — Phase 3 · Module 1
 * Step 3. An expense is attributed to an owner + unit and, when deductible per the governing
 * management agreement, posts a DEDUCTION line to `owner_ledger` (entry_type=expense). A
 * non-deductible expense is recorded but NEVER hits the owner ledger (it is the operator's cost) —
 * modelled explicitly by `deductible=false`, not by omission.
 *
 * REUSE, don't duplicate: `source` is a polymorphic pointer to the EXISTING record the expense came
 * from — a `housekeeping_task` for cleaning (real), a future maintenance ticket when that module
 * lands, or the `unit`. A manual expense leaves `source` null. No parallel cost log is invented.
 *
 * `flag` records why an expense could NOT be attributed (no_owner / no_agreement) so it is visible,
 * never silently dropped. Correcting/voiding uses the SAME whole-transaction reversal as revenue:
 * the ledger line is reversed (a new mirror row), never edited; `voided_at` marks the expense.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_expenses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->foreignUlid('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->enum('category', ['cleaning', 'maintenance', 'other'])->default('other');
            $table->string('description');
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->date('expense_date');
            $table->boolean('deductible')->nullable(); // null until resolved / when flagged unattributed
            $table->nullableUlidMorphs('source'); // housekeeping_task / unit / future maintenance ticket
            $table->foreignUlid('attributed_ledger_id')->nullable()->constrained('owner_ledger')->nullOnDelete();
            $table->string('flag')->nullable(); // no_owner | no_agreement (why it was not attributed)
            $table->string('note')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'expense_date']);
            $table->index('unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_expenses');
    }
};
