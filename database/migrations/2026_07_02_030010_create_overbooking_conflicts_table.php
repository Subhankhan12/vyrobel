<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `overbooking_conflicts` — the crux of Step 4's asymmetric policy (Phase 2 schema §4). When an
 * OTA import can't occupy real inventory (sync lag oversold it), the reservation is STILL
 * recorded (never dropped) and a conflict row is raised here for manual resolution. `sold` is
 * never inflated past capacity — the over-capacity `reservation_unit` is marked `overbooked`
 * (not `active`) and this row tracks it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overbooking_conflicts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reservation_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('channel_id')->nullable()->constrained()->nullOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->timestamp('detected_at');
            $table->enum('status', ['open', 'resolved', 'ignored'])->default('open');
            $table->enum('resolution', ['reassigned', 'moved', 'upgraded', 'cancelled_external', 'accepted', 'other'])->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignUlid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overbooking_conflicts');
    }
};
