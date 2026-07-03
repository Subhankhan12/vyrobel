<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `owner_ledger` — the append-only SOURCE OF TRUTH for owner accounting (Phase 3 · Module 1 Step 2).
 * Every attributed component is its own immutable line, so an owner statement is explainable
 * line-by-line. Same correct-by-reversal discipline as `folio_items`: a line is NEVER edited or
 * deleted — a cancellation/refund/modification posts REVERSING lines (`reverses_id` set, `direction`
 * flipped). There is deliberately NO SoftDeletes.
 *
 * Idempotency is DB-enforced by the unique `idempotency_key`:
 *   originals  => "{reservation_id}:{unit_id}:{entry_type}:v{version}"
 *   reversals  => "rev:{original_line_id}"
 *   fees       => "fee:{agreement_id}:{period_start}:{period_end}"
 * so re-running attribution never double-posts, while a reversal never collides with its original.
 *
 * Amounts are ALWAYS stored positive; `direction` (credit = increases owner net, debit = decreases)
 * carries the sign. Currency is the BOOKING/source currency — conversion to the owner's payout
 * currency happens (with a recorded rate) only at statement time (Step 4), never as a float here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_ledger', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignUlid('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignUlid('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->enum('entry_type', [
                'room_revenue', 'commission', 'management_fee', 'ota_cost',
                'cleaning', 'addon', 'expense', 'adjustment',
            ]);
            $table->enum('direction', ['credit', 'debit']);
            $table->bigInteger('amount_minor'); // always positive; direction carries the sign
            $table->char('currency', 3);
            $table->string('description');
            $table->date('service_date'); // the night/date the value is attributed to → statement period
            $table->unsignedInteger('attribution_version')->default(1);
            $table->foreignUlid('reverses_id')->nullable()->constrained('owner_ledger')->nullOnDelete();
            $table->ulid('statement_id')->nullable(); // Step 4 forward hook — no FK target yet
            $table->boolean('unverified')->default(false); // e.g. OTA cost = 0 pending Channex reconciliation
            $table->string('note')->nullable();
            $table->json('meta')->nullable(); // audit detail (commission_pct used, weights, source ids)
            $table->string('idempotency_key')->nullable();
            $table->nullableUlidMorphs('source'); // polymorphic origin (folio_item / owner_expense / …)
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique('idempotency_key');
            $table->index(['owner_id', 'service_date']);
            $table->index('reservation_id');
            $table->index('statement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_ledger');
    }
};
