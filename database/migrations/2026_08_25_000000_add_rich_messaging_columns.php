<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rich-messaging columns (Owner Messages exact-match · GATE 2) — additive, no backfill needed.
 *
 *   • messages.reply_to_message_id — a message may QUOTE an earlier one in the same thread. A nullable
 *     self-FK; nullOnDelete so a soft/hard-deleted parent leaves the reply standing (it just loses its
 *     quote), never an orphan-FK error.
 *   • message_threads.operator_last_read_at — the OPERATOR's read POSITION, the mirror of the existing
 *     owner_last_read_at/guest_last_read_at. It is what makes the owner-facing "Delivered → Read" tick
 *     REAL (there is no `read` delivery_status to fake): the owner's message is Read once the operator's
 *     read position is at/after it. Set in InboxService::markRead() when an operator opens the thread.
 *   • message_threads.pinned_message_id — the one pinned message per thread (nullable). No DB FK: a pin is
 *     a soft pointer, cleared in app code if the target goes away — a hard FK would couple thread lifecycle
 *     to message deletion for a purely cosmetic banner.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignUlid('reply_to_message_id')->nullable()->after('body')
                ->constrained('messages')->nullOnDelete();
        });

        Schema::table('message_threads', function (Blueprint $table) {
            // Mirrors owner_last_read_at (already present) — the operator's read position for read-receipts.
            $table->timestamp('operator_last_read_at')->nullable()->after('owner_last_read_at');
            // A soft pointer to the pinned message (no FK — cleared in app code, see the class docblock).
            $table->ulid('pinned_message_id')->nullable()->after('operator_last_read_at');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reply_to_message_id');
        });

        Schema::table('message_threads', function (Blueprint $table) {
            $table->dropColumn(['operator_last_read_at', 'pinned_message_id']);
        });
    }
};
