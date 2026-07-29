<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `message_stars` (Owner Messages exact-match · GATE 2) — a PER-USER bookmark of a message. Like a read
 * position, a star is private view-state: it is the current user's own flag, invisible to the other party,
 * so the starred panel shows only the viewer's stars. Tenant-scoped (BelongsToOrganization).
 *
 * `unique(message_id, user_id)` — one star per user per message (the toggle removes it). cascadeOnDelete on
 * the message: a deleted message's stars go with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_stars', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['message_id', 'user_id'], 'msg_star_uq');
            $table->index(['organization_id', 'user_id'], 'msg_star_org_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_stars');
    }
};
