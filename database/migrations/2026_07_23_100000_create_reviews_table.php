<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — the REVIEWS module. A review is tied to a REAL completed stay (`reservation_id`, unique = one review
 * per stay) and denormalizes the physical `unit_id` + `room_type_id` (from the stay's assigned unit) so the owner
 * surface (owner → units) and the storefront (room-type rating) can query without walking the reservation.
 *
 * PUBLIC visibility requires BOTH `visibility = 'public'` AND `status = 'published'` — a submitted review is
 * `pending` (never auto-public), and a `private` review (the guest's "send privately to the manager" choice) is
 * NEVER public regardless of status. The rating on every surface is DERIVED from published+public reviews only —
 * never a stored/fabricated number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->foreignUlid('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignUlid('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignUlid('room_type_id')->nullable()->constrained('room_types')->nullOnDelete();
            $table->unsignedTinyInteger('rating');                 // 1–5 stars — the real value
            $table->json('tags')->nullable();                      // the selected tag chips
            $table->text('body')->nullable();
            $table->enum('visibility', ['public', 'private'])->default('public');   // private = to the manager only
            $table->enum('status', ['pending', 'published', 'rejected'])->default('pending');   // moderation
            $table->text('operator_response')->nullable();         // SG3 — the operator's public response
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique('reservation_id');                      // ONE review per stay
            $table->index(['unit_id', 'visibility', 'status']);
            $table->index(['room_type_id', 'visibility', 'status']);
            $table->index(['guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
