<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️ RETENTION INDEXES (audit 2026-08-29 · M-6). These five tables are paged and filtered BY RECENCY and had
 * no index on `created_at`, so every such read filesorts — and it degrades exactly as the table grows.
 *
 * ⚠️ NEEDED WHETHER OR NOT ANYTHING PRUNES:
 *   · `notifications`, `webhook_events`, `llm_usage` are now pruned, and a date-keyed DELETE without this
 *     index SCANS THE TABLE — worst precisely when the table is biggest, which is the first real run.
 *   · `audit_events` and `domain_events` are DELIBERATELY NEVER PRUNED (they are evidence — see
 *     App\Support\Retention\RetentionPolicy). For those two the index is the ONLY available fix.
 */
return new class extends Migration
{
    /** table => index name */
    private const INDEXES = [
        'audit_events' => 'audit_events_created_at_index',
        'domain_events' => 'domain_events_created_at_index',
        'notifications' => 'notifications_created_at_index',
        'webhook_events' => 'webhook_events_created_at_index',
        'llm_usage' => 'llm_usage_created_at_index',
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $index) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'created_at')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                $blueprint->index('created_at', $index);
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $index) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                $blueprint->dropIndex($index);
            });
        }
    }
};
