<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `message_threads` — a unified-inbox conversation. Tenant-scoped (BelongsToOrganization). The
 * `channel` is a COMMUNICATION MEDIUM enum (manual/email/whatsapp/booking_com/airbnb) — NOT a FK to
 * the OTA-distribution `channels` table (a different concept). `manual` works now; the rest are
 * deferred adapters. A thread optionally links a real `guest` + `reservation` for booking context.
 *
 * `participant_identity` is the guest's address on this channel (email/phone/OTA handle) used to
 * ROUTE outbound — stored ENCRYPTED (guest-PII discipline), never rendered on the operator surface.
 * It is the conversation's routing anchor (needed even for an UNLINKED enquiry with no guest yet);
 * when a guest IS linked, the `guests` record stays the source of truth for that guest's PII.
 *
 * `unique(organization_id, channel, external_thread_ref)` is the threading anchor — inbound groups
 * into the right thread instead of spawning a new one (MySQL allows multiple NULLs → manual threads
 * created without an external ref never collide).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_threads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();

            $table->enum('channel', ['manual', 'email', 'whatsapp', 'booking_com', 'airbnb']);
            $table->string('external_thread_ref')->nullable();
            $table->string('subject')->nullable();

            $table->foreignUlid('guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->foreignUlid('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();

            $table->text('participant_identity')->nullable();   // ENCRYPTED cast — routing anchor, never rendered

            $table->enum('status', ['open', 'snoozed', 'closed'])->default('open');
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('snoozed_until')->nullable();
            $table->json('meta')->nullable();   // non-PII channel metadata (display name, provider flags)

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status', 'last_message_at'], 'mt_org_status_last_idx');
            $table->unique(['organization_id', 'channel', 'external_thread_ref'], 'mt_org_channel_extref_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_threads');
    }
};
