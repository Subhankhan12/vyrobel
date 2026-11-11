<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TEAM CHAT — edit, delete and the privacy scrub (2026-11-11, D-TC-LATER + D-TC-PII). ADDITIVE: three new NULLABLE columns
 * and one index on `team_messages`; no existing column or row changes.
 *
 * `edited_at`   — the author changed the words (the page shows "edited"; no history of the old words is kept).
 * `deleted_at`  — the author deleted it. The row STAYS as a tombstone (body empty, files gone) so read positions, ordering
 *                 and "Seen" keep pointing at real ids. NOT soft-delete in the framework's sense: the model has no scope.
 * `redacted_at` — part of the text was removed because it named a guest who has been erased (D-TC-PII).
 *
 * The index serves the live poll's second question — "which messages in this conversation changed since you last asked?" —
 * which is how an edit, a delete, a reaction or a redaction reaches a screen that already holds the message
 * (each of them bumps `updated_at`, so ONE mechanism delivers all four).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_messages', function (Blueprint $table) {
            $table->timestamp('edited_at')->nullable()->after('body');
            $table->timestamp('deleted_at')->nullable()->after('edited_at');
            $table->timestamp('redacted_at')->nullable()->after('deleted_at');

            $table->index(['organization_id', 'conversation_id', 'updated_at'], 'team_msg_org_conv_upd_idx');
        });
    }

    public function down(): void
    {
        Schema::table('team_messages', function (Blueprint $table) {
            $table->dropIndex('team_msg_org_conv_upd_idx');
            $table->dropColumn(['edited_at', 'deleted_at', 'redacted_at']);
        });
    }
};
