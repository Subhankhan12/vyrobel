<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OWNER↔OPERATOR THREADS (Wave 3) — the minimal addition that makes the owner inbox representable AND safe.
 *
 * The Unified Inbox was built OPERATOR↔GUEST. A thread was never structurally guest-BOUND (`guest_id` has
 * always been nullable), but it had no way to say who ELSE a conversation could be with. These two columns
 * are the whole backend of Wave 3.
 *
 * ⚠️ `owner_id` IS THE SECURITY FIX, NOT A CONVENIENCE. `OwnerScope` (the owner portal's row-level boundary)
 * keys on `owner_id`. With no such column the `BelongsToOwner` trait cannot be applied — so the scope does not
 * "fail closed", **it never runs at all**. An owner inbox built over `message_threads` as it stood would have
 * been tenant-scoped only: every guest conversation and every other owner's threads, visible to any owner.
 * That is invariant #26's exact shape, and it is why this column lands BEFORE the screen.
 *
 * With the column + the trait, the two-inbox boundary falls out of the existing scope for free:
 *   • `/owner` (OwnerContext ACTIVE) → `owner_id IN (their ids)` → a guest thread (owner_id NULL) is excluded
 *     automatically, and a non-linked user gets `1=0` (fail-closed).
 *   • `/app` (OwnerContext INACTIVE) → no constraint → the operator still sees every thread, so they can
 *     actually reply. The reply loop closes without a second query path.
 *
 * THE DISCRIMINATOR IS `guest_id` XOR `owner_id` — deliberately NOT a `participant_type` enum. The two FKs
 * already say which kind of conversation a row is, and a type column would be a third source of truth that
 * can disagree with them (the drift `RailLabel`/`OwnerOccupancy` were extracted to prevent).
 *
 * `owner_last_read_at` EXISTS BECAUSE `unread_count` IS THE OPERATOR'S. That column means "inbound messages
 * since the operator last read", and `InboxService::markRead()` zeroes it — so an owner-portal read action
 * that touched it would silently clear the OPERATOR's badge. The owner's unread is therefore its own
 * timestamp: outbound messages created after it. (Per-MESSAGE read receipts — the design's "✓✓ Read" — are
 * deliberately NOT modelled: `messages.delivery_status` has no `read` value, and inventing one to satisfy a
 * design would be fabricating a fact the app cannot know.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_threads', function (Blueprint $table): void {
            // Nullable: a guest thread has no owner, and an owner thread has no guest.
            // nullOnDelete mirrors `guest_id` — deleting the counterparty must never cascade away the
            // conversation, which is audit history.
            $table->foreignUlid('owner_id')->nullable()->after('guest_id')
                ->constrained('owners')->nullOnDelete();

            // The OWNER's read position. Distinct from `unread_count`, which is the OPERATOR's.
            $table->timestamp('owner_last_read_at')->nullable()->after('last_inbound_at');

            // The owner portal's list query: the owner's threads, newest conversation first.
            $table->index(['organization_id', 'owner_id', 'last_message_at'], 'mt_org_owner_last_idx');
        });
    }

    public function down(): void
    {
        Schema::table('message_threads', function (Blueprint $table): void {
            $table->dropIndex('mt_org_owner_last_idx');
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn('owner_last_read_at');
        });
    }
};
