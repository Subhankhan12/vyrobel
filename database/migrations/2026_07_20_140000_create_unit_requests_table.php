<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `unit_requests` — an owner's REQUEST to add a unit (Phase 2 · Sub-gate 6). ⚠️ POLICY: an owner does NOT create
 * billable inventory directly — a new unit changes the operator's live inventory, availability and billing, so
 * the owner submits a request the OPERATOR reviews and (manually, as their own action) turns into a real unit.
 * This table is the intake record only. Owner-scoped (`owner_id` + BelongsToOwner) within the tenant; the
 * operator sees pending requests tenant-scoped. `requested_kind` is the REAL `units.unit_kind` enum (so a
 * request maps to something the operator can actually create) — no invented types.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_requests', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->string('requested_name');
            $table->enum('requested_kind', ['room', 'apartment', 'villa', 'dorm_bed'])->default('apartment');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'status']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_requests');
    }
};
