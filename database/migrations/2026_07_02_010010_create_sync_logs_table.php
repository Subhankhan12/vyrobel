<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `sync_logs` — append-only, per-attempt log lines for a `sync_jobs` row (write-once, only
 * `created_at`, same pattern as `reservation_status_history`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('sync_job_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt');
            $table->enum('level', ['info', 'warning', 'error']);
            $table->text('message');
            $table->json('context')->nullable(); // sanitized — no secrets
            $table->timestamp('created_at')->useCurrent();

            $table->index('sync_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
