<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `message_reactions` (Owner Messages exact-match · GATE 2) — one emoji reaction by one user on one
 * message. Tenant-scoped (BelongsToOrganization) like every business table; the emoji is constrained to a
 * fixed app-level allow-list, never free text (so no arbitrary payload lands in a rendered surface).
 *
 * `unique(message_id, user_id, emoji)` makes a reaction IDEMPOTENT — a double-tap of the same emoji cannot
 * create a second row (the toggle removes it instead). cascadeOnDelete on the message: a deleted message
 * takes its reactions with it (unlike a reply, a reaction is meaningless without its message).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_reactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'emoji'], 'msg_reaction_uq');
            $table->index(['organization_id', 'message_id'], 'msg_reaction_org_msg_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reactions');
    }
};
