<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Ask-Vyrobel cost ledger (Wave D · support AI · Phase 2) — one row per owner per day, counting requests
 * and tokens. Checked BEFORE each LLM call (per-owner/day token cap → escalate when exceeded) and incremented
 * AFTER (real spend recorded). This is "the ledger caps spend".
 *
 * Holds NO owner PII (#28 trivially satisfied) — just an owner id, a date, and integer counters. `owner_id` is a
 * ULID string (owner user id); nullable so the counter still works if an owner context can't resolve.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_usage', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('owner_id', 26)->nullable();
            $table->date('usage_date');
            $table->unsignedInteger('request_count')->default(0);
            $table->unsignedBigInteger('token_count')->default(0);
            $table->timestamps();

            $table->unique(['owner_id', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_usage');
    }
};
