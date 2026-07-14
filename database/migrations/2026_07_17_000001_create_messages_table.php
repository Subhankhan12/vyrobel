<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `messages` — one message in a `message_thread`. Tenant-scoped (BelongsToOrganization). `direction`
 * = inbound|outbound; `channel` is denormalised from the thread (the medium this message travelled).
 *
 * `unique(organization_id, channel, external_message_id)` is the INBOUND idempotency anchor — a
 * duplicate provider delivery (same external id) collapses to one row via `createOrFirst` (the same
 * exactly-once-over-at-least-once discipline as `webhook_events`). `external_message_id` is NULL for
 * outbound/manual messages (MySQL allows multiple NULLs → they never collide).
 *
 * `body` holds message content (guest-authored content is PII-adjacent → redacted on the guest's
 * `anonymize()` in Step 2). `sender_label` is a NON-PII display label (guest name / "You"), never the
 * encrypted contact. Outbound delivery lifecycle: queued → sent → delivered / failed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('thread_id')->constrained('message_threads')->cascadeOnDelete();

            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('channel', ['manual', 'email', 'whatsapp', 'booking_com', 'airbnb']);
            $table->string('external_message_id')->nullable();

            $table->text('body');
            $table->foreignUlid('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_label')->nullable();   // non-PII display label (guest name / "You")

            $table->enum('delivery_status', ['received', 'queued', 'sent', 'delivered', 'failed'])->default('received');
            $table->string('delivery_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'thread_id', 'created_at'], 'msg_org_thread_created_idx');
            $table->unique(['organization_id', 'channel', 'external_message_id'], 'msg_org_channel_extmsg_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
