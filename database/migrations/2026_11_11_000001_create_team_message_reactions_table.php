<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TEAM CHAT — reactions (2026-11-11, D-TC-LATER). ADDITIVE: one new table. One emoji by one teammate on one team message,
 * from the fixed allow-list the Inbox already uses (`InboxService::REACTIONS`) — never free text.
 *
 * `unique(message_id, user_id, emoji)` makes a reaction idempotent: a second tap of the same emoji removes the row.
 * A reaction is meaningless without its message, so it goes with it (cascade).
 * (The Inbox's own `message_reactions.emoji` uses the default collation and shares the equal-emoji problem described below — recorded
 * in OPEN.md as found, not changed here.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_message_reactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('message_id')->constrained('team_messages')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            // ⚠️ BINARY collation, on purpose: under the framework's default (utf8mb4_unicode_ci, on both MySQL and MariaDB)
            // every emoji outside the basic plane compares EQUAL to every other — a second, different reaction by the same
            // person would be found as the first and taken off, and the unique index would refuse it. Measured 2026-11-11.
            $table->string('emoji', 16)->collation('utf8mb4_bin');
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'emoji'], 'team_reaction_uq');
            $table->index(['organization_id', 'message_id'], 'team_reaction_org_msg_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_message_reactions');
    }
};
