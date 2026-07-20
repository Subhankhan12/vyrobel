<?php

use App\Services\NotificationPreferences;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `notification_preferences` (Phase 3 · Sub-gate 2) — per-user, per-event, per-channel delivery toggles. A
 * PURPOSE-BUILT, TYPED table (constrained enum + bool), NOT the dormant generic `settings` KV scaffold: the
 * "key" is a constrained `event_key` validated against a fixed catalog in {@see NotificationPreferences},
 * the channel is an enum, and the value is a real boolean. One row per (user, event, channel) that DIFFERS from
 * the catalog default; a missing row means "use the default". Self-only writes (the user id is auth-resolved).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_key');                          // constrained to the catalog by the service
            $table->enum('channel', ['in_app', 'email']);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'event_key', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
