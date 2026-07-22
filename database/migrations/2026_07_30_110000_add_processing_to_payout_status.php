<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add a `processing` payout status (Phase 8 · SG3). A Stripe Connect payout is INITIATED in the payout run
 * (the transfer is created, row-locked + idempotency-keyed) → `processing`, then flips to `paid` on the
 * CONNECT webhook (transfer.created) — so "paid" is webhook-confirmed, never off a synchronous assumption.
 * The manual/offline rail stays `pending → paid` (instant). Append-only + correct-by-reversal is unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payouts MODIFY status ENUM('pending','processing','paid','failed','reversed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payouts MODIFY status ENUM('pending','paid','failed','reversed') NOT NULL DEFAULT 'pending'");
    }
};
