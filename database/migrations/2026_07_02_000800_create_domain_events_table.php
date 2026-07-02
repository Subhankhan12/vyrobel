<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `domain_events` — the business event OUTBOX (the semantic layer). Every meaningful state
 * change writes one here (e.g. reservation.confirmed, unit.checked_out). Consumed by the
 * availability recompute (now), and later by automations, webhooks, and AI agents.
 *
 * `available_at` supports scheduled/deferred processing; `processed_at` marks consumption.
 * Like audit_events, org/subject are plain indexed columns (polymorphic subject, no FK).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id')->nullable();
            $table->string('event_type'); // e.g. reservation.confirmed
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('available_at')->nullable(); // for scheduled/automation use
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('organization_id');
            $table->index('event_type');
            $table->index('processed_at');
            $table->index('available_at');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_events');
    }
};
