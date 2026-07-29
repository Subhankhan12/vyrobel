<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The automated PAYOUT-ENGINE run record (Phase 8 · payout engine · Phase 1) — one row per engine sweep.
 *
 * PLATFORM-LEVEL by design: a scheduled run is cross-tenant (it selects due owners across every org), so —
 * like `webhook_events` and platform-activity — it carries NO `organization_id` and is created/read under
 * `TenantContext::runAsSuperAdmin`. The per-owner writes it drives (create/pay/hold a Payout) still happen
 * INSIDE each owner's org context, tenant-scoped and audited as today. This table is the run's own audit +
 * idempotency record (owner/operator surfaces read `payouts`, not this).
 *
 * ⚠️ `mode` is stamped from PayoutEngineMode — `test` in the entire build. Real money never moves in test mode.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            // The idempotency anchor: `scheduled:YYYY-MM-DD` (one scheduled run per day) or `manual:{ulid}`.
            $table->string('run_key')->unique();
            $table->enum('trigger', ['scheduled', 'manual'])->default('scheduled');
            $table->enum('mode', ['test', 'live'])->default('test');   // TEST-MODE boundary, stamped per run
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->unsignedInteger('owners_selected')->default(0);
            $table->unsignedInteger('payouts_created')->default(0);
            $table->unsignedInteger('payouts_paid')->default(0);      // reached paid/processing this run
            $table->unsignedInteger('payouts_held')->default(0);      // held by the tax-hold this run
            $table->unsignedInteger('payouts_failed')->default(0);
            $table->json('totals')->nullable();                        // {currency: minor} — Σ disbursed, per currency
            $table->string('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_runs');
    }
};
