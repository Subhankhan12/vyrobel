<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel's native `notifications` table (Phase 3 · Sub-gate 1 — the notification CORE). We use the framework's
 * own database-channel schema rather than a hand-rolled one, adapted for this app's ULID models: the notification
 * `id` is Laravel's ordered UUID; the `notifiable` morph points at a User (ULID char(26) via {@see ulidMorphs}).
 * Isolation is per-notifiable BY CONSTRUCTION — a user's notifications are only ever reached through their own
 * `notifications()` relation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');                 // the Notification class
            $table->ulidMorphs('notifiable');       // notifiable_type + char(26) notifiable_id + index
            $table->text('data');                   // the toArray() payload (title/context/url/category)
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
