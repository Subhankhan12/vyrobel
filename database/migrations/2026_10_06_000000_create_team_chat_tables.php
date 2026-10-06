<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TEAM CHAT (2026-10-06, `docs/TEAM-CHAT-DESIGN.md`) — conversations between the people who work in a workspace: the
 * account owner and the staff. ADDITIVE: three new tables, nothing existing is touched.
 *
 * ⚠️ NOT `message_threads`. A guest or owner thread belongs to the ORGANISATION — every `guests.view` holder reads it and
 * one unread counter serves them all. A team conversation belongs to its MEMBERS — each has their own read position, and
 * nobody else may read it. Sharing the table would need a "not team" filter at every guest-inbox read site; one missed
 * filter would put private staff messages in front of every front-desk agent.
 *
 * `direct_key` — the two members' ids in order ("a|b") — is UNIQUE per organisation, so two people have exactly one
 * direct conversation however many times either of them starts it (a group has none: NULLs never collide).
 * Files are rows in the shared polymorphic `media` table (morph alias `team_message`), on the private disk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10);                                  // direct | group
            $table->string('title', 80)->nullable();                     // a group's name (optional)
            $table->string('direct_key', 60)->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'direct_key'], 'team_conv_direct_uq');
            $table->index(['organization_id', 'last_message_at'], 'team_conv_org_last_idx');
        });

        Schema::create('team_conversation_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('conversation_id')->constrained('team_conversations')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            // how far they have read, by MESSAGE id (ULIDs sort by the millisecond; a timestamp is one second wide, and a
            // message in the same second as the read would never count as unread) — and when, for "Seen".
            $table->ulid('last_read_message_id')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id'], 'team_member_uq');
            $table->index(['organization_id', 'user_id'], 'team_member_org_user_idx');
        });

        Schema::create('team_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('conversation_id')->constrained('team_conversations')->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();   // the sender
            $table->text('body')->nullable();                            // a voice note or files may come alone
            $table->timestamps();

            $table->index(['organization_id', 'conversation_id', 'id'], 'team_msg_org_conv_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_messages');
        Schema::dropIfExists('team_conversation_members');
        Schema::dropIfExists('team_conversations');
    }
};
