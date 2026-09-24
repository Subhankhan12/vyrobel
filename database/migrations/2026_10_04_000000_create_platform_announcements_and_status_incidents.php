<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADMIN 01 · PHASE 5 — THREE NEW TABLES, additive; nothing existing is touched.
 *
 *   · platform_announcements  — an in-app notice from Vyrobel to every tenant or chosen ones, with a start and an
 *                               optional end. ⚠️ Plain text only (rendered escaped).
 *   · announcement_dismissals — who dismissed which notice, so it stays dismissed for them.
 *   · status_incidents        — the PUBLIC status page's incidents. ⚠️ MANUAL ONLY: an admin writes every word; the
 *                               page never reads a release, a queue, a config value or any other internal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_announcements', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title', 120);
            $table->text('body');
            $table->string('level', 16);                   // info | warning
            $table->string('audience', 16);                // all | chosen
            $table->json('organization_ids')->nullable();  // when chosen
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->ulid('created_by');
            $table->timestamps();
        });

        Schema::create('announcement_dismissals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('announcement_id');
            $table->ulid('user_id');
            $table->timestamp('dismissed_at');
            $table->unique(['announcement_id', 'user_id']);
        });

        Schema::create('status_incidents', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title', 120);
            $table->string('impact', 16);    // minor | major
            $table->string('status', 16);    // investigating | identified | monitoring | resolved
            $table->text('message');         // PUBLIC — written by an admin for the public
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->ulid('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_incidents');
        Schema::dropIfExists('announcement_dismissals');
        Schema::dropIfExists('platform_announcements');
    }
};
