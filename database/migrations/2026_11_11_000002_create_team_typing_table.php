<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TEAM CHAT — "is typing…" (2026-11-11, D-TC-LATER). ADDITIVE: one new table, one row per (conversation, person) holding the
 * moment they last typed. The page posts it at most every three seconds while someone types; the poll shows a person as
 * typing while it is under six seconds old; sending clears it. There are no websockets here — this is the polling design's
 * honest version, and the rows are transient (a stale row is simply ignored, and a later pass may sweep them).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_typing', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('conversation_id')->constrained('team_conversations')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('typed_at');
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id'], 'team_typing_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_typing');
    }
};
