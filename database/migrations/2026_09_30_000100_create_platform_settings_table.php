<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADMIN 01 · PHASE 1 — the platform's RUNTIME switches (kill switches), stored in the DATABASE.
 *
 * ⚠️⚠️ WHY NOT `.env` OR `config/`: production caches its configuration, and a cached config means Laravel
 * never reads `.env` again (KNOWN-LIMITS L14 — measured). A switch kept there could not be flipped without a
 * deploy, which is the one moment a kill switch is not for. A row here takes effect on the next request.
 *
 * ⚠️⚠️ A MISSING ROW MEANS "ON" — today's behaviour. An empty table after a deploy therefore changes nothing,
 * so shipping this table cannot become an outage nobody is watching for.
 *
 * Platform-level on purpose: no organization_id. The existing `settings` table is tenant-owned and would put
 * a platform switch under a tenant scope it does not belong to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->string('key', 100)->primary();
            $table->json('value')->nullable();
            $table->ulid('updated_by')->nullable();   // the platform admin who last changed it (no FK — a log outlives a row)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
