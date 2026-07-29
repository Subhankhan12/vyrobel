<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds `withholding` to `owner_ledger.entry_type` (Wave D · tax engine · Phase 3).
 *
 * A `withholding` line is tax the agency withholds from an owner's payout on their behalf (per the owner's
 * residency, at a FOUNDER-CONFIGURED rate). It is a real DEBIT — computed at statement finalize from the
 * pre-withholding net — that reduces `net_source_minor`/`net_payable_minor` and so the payout, reconciling to
 * the cent (#17), exactly like the `payment_fee` bucket. Until a withholding rate is configured for the owner's
 * jurisdiction, no line is posted (genuine €0) — never a fabricated rate.
 *
 * Raw ALTER (no doctrine/dbal) — extend-don't-duplicate: still the ONE owner_ledger, one more type.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE owner_ledger MODIFY COLUMN entry_type '
            ."ENUM('room_revenue', 'commission', 'management_fee', 'ota_cost', "
            ."'cleaning', 'addon', 'expense', 'adjustment', 'payment_fee', 'withholding') NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE owner_ledger MODIFY COLUMN entry_type '
            ."ENUM('room_revenue', 'commission', 'management_fee', 'ota_cost', "
            ."'cleaning', 'addon', 'expense', 'adjustment', 'payment_fee') NOT NULL"
        );
    }
};
