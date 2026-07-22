<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff time-off / leave (Phase 7d · Team Schedule · SG2). A staff member requests time off (their own) → a
 * MANAGER approves → it becomes approved leave that shows on the roster and conflicts with any shift on those
 * days. The request/approval SEPARATION mirrors guest-change-requests / owner-add-unit: `status` starts
 * `pending`, a manager flips it (never the requester — the self-approval guard lives in the service).
 *
 * Leave is about the PERSON, not a property: `user_id` + a date RANGE, org-scoped (no `property_id`) — a
 * staff member on leave is unavailable on EVERY property's roster for those days.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();   // the staff member on leave
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending');
            $table->foreignUlid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['organization_id', 'status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
