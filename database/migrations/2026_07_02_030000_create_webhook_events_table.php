<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `webhook_events` — INBOUND provider-webhook idempotency (Phase 2 schema §6). Distinct from
 * Phase 1's `webhooks`/`webhook_deliveries`, which are OUTBOUND tenant webhooks.
 *
 * `unique(source, external_event_id)` is the exactly-once guarantee: Channex (and later Stripe)
 * deliver at-least-once, so a redelivered event collapses onto the same row and is processed
 * once. `channel_id` is set for Channex (from the webhook route); Stripe events (Step 6) resolve
 * org/channel differently, hence both nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source', ['channex', 'stripe']);
            $table->foreignUlid('channel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_event_id');
            $table->string('event_type');
            $table->boolean('signature_verified')->default(false);
            $table->json('payload')->nullable();
            $table->enum('status', ['received', 'processing', 'processed', 'failed', 'ignored'])->default('received');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_event_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
