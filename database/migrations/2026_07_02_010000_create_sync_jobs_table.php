<?php

use App\Jobs\Sync\RetryableSyncJob;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `sync_jobs` — the durable, business-visible record of every channel-sync operation (Phase 2
 * schema §3). Distinct from Laravel's internal `jobs`/`failed_jobs` (queue transport): this is
 * the domain ledger the mapping-health/sync-error surfaces read, and the anchor for the
 * idempotency + retry/backoff + dead-letter machinery in {@see RetryableSyncJob}.
 *
 * `channel_id` is a forward hook (no FK yet — `channels` lands in Step 2), same pattern as
 * Phase 1's `reservations.channel_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->ulid('channel_id')->nullable(); // FK to channels (Step 2) — forward hook
            $table->enum('direction', ['outbound', 'inbound']);
            $table->string('type'); // e.g. ari_push, booking_import — grows in Steps 3-4, not a fixed DB enum
            $table->string('idempotency_key')->unique();
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'dead_letter'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(5);
            $table->timestamp('available_at')->nullable();
            $table->json('payload_summary')->nullable(); // human-readable; no secrets, no raw card data
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('dead_lettered_at')->nullable();
            $table->timestamps();

            $table->index('channel_id');
            $table->index('status');
            $table->index(['direction', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_jobs');
    }
};
