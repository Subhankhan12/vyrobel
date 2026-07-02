<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `reservations` — ONE booking, any source. A direct booking, OTA import, walk-in, corporate,
 * or group booking are all rows here distinguished by `source` (+ `channel_id`/`ota_reservation_id`
 * hooks for Phase 2). NEVER parallel per-channel tables. Stay dates are half-open [check_in, check_out).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->string('reference'); // human code, unique per org
            $table->enum('source', ['direct', 'walk_in', 'ota', 'corporate', 'group', 'agent', 'manual'])->default('direct');
            $table->ulid('channel_id')->nullable();          // FK to channels (Phase 2) — forward hook
            $table->string('ota_reservation_id')->nullable(); // Phase 2 hook
            $table->enum('status', ['enquiry', 'tentative', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'])->default('confirmed');
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->char('currency', 3);
            $table->bigInteger('total_minor')->default(0);
            $table->bigInteger('balance_minor')->default(0);  // signed: can be negative on overpay
            $table->bigInteger('deposit_minor')->nullable();
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded', 'charged_back'])->default('unpaid');
            $table->foreignUlid('lead_guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->foreignUlid('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignUlid('corporate_account_id')->nullable()->constrained('corporate_accounts')->nullOnDelete();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->text('notes_guest')->nullable();
            $table->text('notes_internal')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'reference']);
            $table->index(['property_id', 'check_in', 'check_out']);
            $table->index('channel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
