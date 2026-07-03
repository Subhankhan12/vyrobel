<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `owner_statement_lines` — the human-readable breakdown of an owner statement (Phase 3 · Module 1
 * Step 4), derived from the claimed `owner_ledger` lines: gross → other revenue → commission →
 * management fee → deductible expenses → OTA cost → prior-period adjustments → NET. Each component
 * line's `amount_minor` is a SIGNED presentation value (credits +, deductions −) in the statement's
 * source currency; the component lines sum EXACTLY to the statement's `net_source_minor`. The `net`
 * line is a summary (not summed with the components). `unverified` surfaces pending OTA lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_statement_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_statement_id')->constrained('owner_statements')->cascadeOnDelete();
            $table->enum('line_type', [
                'gross', 'other_revenue', 'commission', 'management_fee',
                'expense', 'ota_cost', 'prior_period_adjustment', 'net',
            ]);
            $table->string('description');
            $table->bigInteger('amount_minor'); // signed presentation value (source currency)
            $table->char('currency', 3);
            $table->boolean('unverified')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('owner_statement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_statement_lines');
    }
};
