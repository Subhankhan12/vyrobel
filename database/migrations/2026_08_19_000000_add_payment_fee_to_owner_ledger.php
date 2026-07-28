<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds `payment_fee` to `owner_ledger.entry_type` (Wave C · GATE 4).
 *
 * A `payment_fee` line is a REAL payment-processing / platform-ops fee deducted from an owner's
 * earnings (e.g. the card-processing fee a live Stripe charge incurs, or a channel payment fee) —
 * distinct from the management `commission` and the channel `ota_cost`. It projects into the owner
 * statement's new "Payment & ops fees" bucket, which reconciles into `net_source_minor` exactly like
 * every other bucket (#17). Until the deferred live-payment integration posts such lines, no writer
 * emits them, so the bucket is genuinely €0 and its statement line is omitted — never a fabricated fee.
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
            ."'cleaning', 'addon', 'expense', 'adjustment', 'payment_fee') NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE owner_ledger MODIFY COLUMN entry_type '
            ."ENUM('room_revenue', 'commission', 'management_fee', 'ota_cost', "
            ."'cleaning', 'addon', 'expense', 'adjustment') NOT NULL"
        );
    }
};
