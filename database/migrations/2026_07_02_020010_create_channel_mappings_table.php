<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `channel_mappings` — maps one of our `room_type`s or `rate_plan`s to the provider's id, with a
 * health `status`. `kind` says which entity a row maps; exactly one of `room_type_id` /
 * `rate_plan_id` is set accordingly.
 *
 * The two unique keys coexist because MySQL allows multiple NULLs in a unique index: a room_type
 * mapping has rate_plan_id = NULL (so many share (channel_id, NULL) on the rate_plan unique) and
 * a rate_plan mapping has room_type_id = NULL — so each unique only ever constrains its own kind.
 *
 * Only a `status = active` mapping is usable — the mapping guard (ChannelMappingGuard) treats
 * pending/error/disabled as unmapped and blocks any ARI push / booking import for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_mappings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('channel_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', ['room_type', 'rate_plan']);
            $table->foreignUlid('room_type_id')->nullable()->constrained('room_types')->cascadeOnDelete();
            $table->foreignUlid('rate_plan_id')->nullable()->constrained('rate_plans')->cascadeOnDelete();
            $table->string('external_id'); // Channex room_type / rate_plan id
            $table->enum('status', ['pending', 'active', 'error', 'disabled'])->default('pending');
            $table->timestamp('last_verified_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['channel_id', 'room_type_id']);
            $table->unique(['channel_id', 'rate_plan_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_mappings');
    }
};
