<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner Rates exact-match (#81) — two additive builds:
 *
 *   • rate_proposals gains a COUNTER (the owner proposes a different rate back to the operator): the `state`
 *     column is a plain string(20), so `countered` needs no enum change — only the owner's countered figures.
 *     `pending → countered` (owner) → `approved` on the operator accepting the counter (applied via the
 *     certified OwnerRateService). Nothing is applied while it sits `countered`.
 *
 *   • rate_guardrails — a per-owner store of "the limits your manager prices within" that GENUINELY constrains
 *     operator proposals in RateProposalService::propose(): a proposed rate below floor / above ceiling is
 *     rejected; within ±auto_approve_pct of current AND in-band auto-approves; else stays pending. Enforcement
 *     is gated on an EXPLICIT row (absent = today's behaviour), so existing proposals are undisturbed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rate_proposals', function (Blueprint $table): void {
            // The owner's COUNTER — their proposed-back nightly rate (minor units) + an optional note.
            $table->unsignedBigInteger('countered_rate_minor')->nullable()->after('proposed_min_stay');
            $table->text('countered_note')->nullable()->after('countered_rate_minor');
            $table->timestamp('countered_at')->nullable()->after('decided_by');
            $table->foreignUlid('countered_by')->nullable()->after('countered_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('rate_guardrails', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained()->cascadeOnDelete();
            $table->enum('pricing_mode', ['dynamic', 'fixed'])->default('dynamic');
            // Changes within ±this % of the current rate auto-apply (dynamic mode); larger wait for the owner.
            $table->unsignedSmallInteger('auto_approve_pct')->default(0);
            $table->unsignedBigInteger('floor_minor')->nullable();     // a proposal below this is rejected
            $table->unsignedBigInteger('ceiling_minor')->nullable();   // a proposal above this is rejected
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();

            $table->unique('owner_id');   // one guardrail set per owner
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_guardrails');

        Schema::table('rate_proposals', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('countered_by');
            $table->dropColumn(['countered_rate_minor', 'countered_note', 'countered_at']);
        });
    }
};
