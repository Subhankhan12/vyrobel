<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `inventory_blocks` — date-range blocks that reduce availability (maintenance, owner-hold,
 * manual, overbooking-protection). Dates are half-open `[start_date, end_date)`.
 *
 * These affect availability and must not overlap an active reservation_unit on the same unit;
 * that enforcement lives in `AssignmentService` (Step 5). No availability logic here yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_blocks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('room_type_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('reason', ['maintenance', 'owner', 'manual', 'overbooking_protection'])->default('manual');
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit_id', 'start_date', 'end_date']);
            $table->index(['room_type_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_blocks');
    }
};
