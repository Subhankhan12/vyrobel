<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `booking_quotes` — the booking engine's quote / ADVISORY hold / abandoned-capture record
 * (Phase 2 schema §5; abandoned folded in via `status`, per the Step 0 decision).
 *
 * CRITICAL (Step 0 locked): the hold is ADVISORY only — a price + `held_until` timer. It is NOT
 * counted in `availability.sold`, is NOT a hard-hold through AssignmentService, and occupies NO
 * real inventory. Inventory is only ever occupied at BOOK time, atomically, through
 * AssignmentService (which re-checks under its lock). Two holds on the last unit is fine; only one
 * conversion seats.
 *
 * `email`/`phone` are encrypted PII (convention #5), captured for abandoned-booking recovery.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_quotes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->string('session_token');
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->json('requested'); // [{room_type_id, rate_plan_id, qty}]
            $table->char('currency', 3);
            $table->bigInteger('subtotal_minor')->default(0);
            $table->bigInteger('tax_minor')->default(0);
            $table->bigInteger('fees_minor')->default(0);
            $table->bigInteger('discount_minor')->default(0);
            $table->bigInteger('total_minor')->default(0);
            $table->string('promo_code')->nullable();
            $table->json('guest_snapshot')->nullable();
            $table->text('email')->nullable();  // encrypted cast
            $table->text('phone')->nullable();  // encrypted cast
            $table->enum('status', ['quoted', 'held', 'converted', 'expired', 'abandoned', 'recovered'])->default('quoted');
            $table->timestamp('held_until')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('recovery_status', ['none', 'pending', 'contacted', 'recovered', 'expired'])->default('none');
            $table->timestamp('contacted_at')->nullable();
            $table->ulid('converted_reservation_id')->nullable();
            $table->timestamps();

            $table->index('session_token');
            $table->index('status');
            $table->index(['property_id', 'status']);
            $table->foreign('converted_reservation_id')->references('id')->on('reservations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_quotes');
    }
};
