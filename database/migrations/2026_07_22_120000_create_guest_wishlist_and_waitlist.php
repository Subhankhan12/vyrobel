<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4c · Sub-gate 4 — the guest WISHLIST (saved room types) + WAITLIST (join for sold-out dates). Both are
 * real, guest-owned stores; every read/write resolves through the authed guest (self-only). Tenant-scoped.
 *
 * The waitlist NOTIFY side ("a spot opens → you hear first") and AUTO-HOLD are deferred honestly (see the model):
 * the store, re-confirm and expiry are real; the trigger (a cancellation freeing a waitlisted date) + Phase-3
 * email + holding inventory are a stated seam, not faked here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_wishlist_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->foreignUlid('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['guest_id', 'room_type_id']);   // saving the same room twice is a no-op
        });

        Schema::create('guest_waitlist_entries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->foreignUlid('room_type_id')->constrained('room_types')->cascadeOnDelete();
            // Exact dates OR a flexible month ('YYYY-MM' = anytime that month). One of the two is set.
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->string('flexible_month', 7)->nullable();
            $table->boolean('notify_similar')->default(false);
            $table->enum('status', ['active', 'notified', 'expired', 'cancelled'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['guest_id', 'status']);
            $table->index(['room_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_wishlist_items');
        Schema::dropIfExists('guest_waitlist_entries');
    }
};
