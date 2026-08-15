<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * D29-5 + DO6-4 — per-notification DISMISS for the in-app feed.
 *
 * ⚠️ A NEW COLUMN, DELIBERATELY NOT `read_at`. Mark-read and dismiss are different acts: read says "I have
 * seen this" and the row STAYS in the feed; dismiss says "take this off my list" and the row LEAVES it.
 * Overloading `read_at` would make the two indistinguishable and would silently hide everything a user had
 * merely glanced at. They coexist: a notification can be read and dismissed, or dismissed while unread.
 *
 * ⚠️ AND THIS IS NOT THE OTHER `dismissed_at`. `owner_attention_states.dismissed_at` already exists and is a
 * DIFFERENT ENTITY — the owner's "needs attention" SIGNAL (keyed by `owner_id` + `attention_key`), written by
 * `/owner/attention/dismiss`. Two tables now carry a same-named column for genuinely different concepts, so
 * the risk is not the schema but the wiring: dismissing a notification must never clear an attention signal,
 * and vice versa. `NotificationDismissTest` asserts that boundary explicitly rather than trusting it.
 *
 * Nullable + no default: the overwhelming majority of rows are never dismissed, and NULL is the honest
 * "no-one dismissed this" rather than a sentinel date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->timestamp('dismissed_at')->nullable()->after('read_at');

            // The feed's hot path is "this user's rows, not dismissed, newest first" — the notifiable pair is
            // already indexed by Laravel's own migration, so this covers the added predicate.
            $table->index(['notifiable_id', 'dismissed_at'], 'notifications_notifiable_dismissed_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_notifiable_dismissed_index');
            $table->dropColumn('dismissed_at');
        });
    }
};
