<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds `payment_fee` to `owner_statement_lines.line_type` (Wave C · GATE 4) — the presentation line for the
 * statement's new "Payment & ops fees" bucket. Mirrors the `payment_fee` ledger entry_type; the line is only
 * written when the bucket is non-zero (real fees exist), so no fabricated €0 line is ever created.
 *
 * Raw ALTER (no doctrine/dbal) — still the ONE owner_statement_lines, one more presentation type.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE owner_statement_lines MODIFY COLUMN line_type '
            ."ENUM('gross', 'other_revenue', 'commission', 'management_fee', "
            ."'expense', 'ota_cost', 'payment_fee', 'prior_period_adjustment', 'net') NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE owner_statement_lines MODIFY COLUMN line_type '
            ."ENUM('gross', 'other_revenue', 'commission', 'management_fee', "
            ."'expense', 'ota_cost', 'prior_period_adjustment', 'net') NOT NULL"
        );
    }
};
