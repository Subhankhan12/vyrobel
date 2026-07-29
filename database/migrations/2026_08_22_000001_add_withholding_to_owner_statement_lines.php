<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds `withholding` to `owner_statement_lines.line_type` (Wave D · tax engine · Phase 3) — the presentation line
 * for the statement's new withholding bucket. Mirrors the `withholding` ledger entry_type; written only when the
 * bucket is non-zero (a real withholding exists), so no fabricated €0 line is ever created.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE owner_statement_lines MODIFY COLUMN line_type '
            ."ENUM('gross', 'other_revenue', 'commission', 'management_fee', "
            ."'expense', 'ota_cost', 'payment_fee', 'withholding', 'prior_period_adjustment', 'net') NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE owner_statement_lines MODIFY COLUMN line_type '
            ."ENUM('gross', 'other_revenue', 'commission', 'management_fee', "
            ."'expense', 'ota_cost', 'payment_fee', 'prior_period_adjustment', 'net') NOT NULL"
        );
    }
};
