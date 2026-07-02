<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `audit_events` — security/compliance trail: WHO did WHAT (actor, before/after, ip).
 * Write-once and immutable: no updated_at, no soft delete.
 *
 * `organization_id` and `actor_id` are plain indexed ULID columns (no FK): audit rows are
 * a log that must survive tenant/user lifecycle, and the actor may be a user, an agent, or
 * the system (see `actor_type`) — so it is not constrained to any single table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id')->nullable();
            $table->enum('actor_type', ['user', 'agent', 'system'])->default('system');
            $table->ulid('actor_id')->nullable();
            $table->string('action'); // e.g. reservation.confirmed, guest.anonymized
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('organization_id');
            $table->index('action');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
