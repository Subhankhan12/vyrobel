<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4b · Sub-gate 3 — guest-INITIATED change REQUESTS. A guest changing dates / adding extras / cancelling
 * touches the operator's inventory / folio / availability, so the guest NEVER writes those directly: each action
 * records a pending REQUEST here that the operator reviews + confirms. The guest side only ever creates a
 * `pending` row (it can't move to `confirmed`/`declined` — that's an operator action). Tenant-scoped + owned by
 * the requesting guest's booking. `payload` snapshots the request (new dates + the informational quote / the
 * requested add-ons / the cancellation reason).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_change_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignUlid('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->enum('type', ['date_change', 'add_on', 'cancellation']);
            $table->json('payload');
            $table->enum('status', ['pending', 'confirmed', 'declined'])->default('pending');
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_change_requests');
    }
};
