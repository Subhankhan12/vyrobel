<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demo-readiness polish · Part A — photos reuse the SHARED polymorphic `media` table (convention #7:
 * never a parallel per-module photo table). This adds the photo-specific facets: a `collection` to
 * distinguish photos from other media (proof shots, PDFs), plus ordering, a cover flag, and alt text.
 * All additive + nullable/defaulted — no backfill, no change to existing media rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('collection')->default('default')->after('mediable_id');
            $table->unsignedInteger('sort_order')->default(0)->after('meta');
            $table->boolean('is_cover')->default(false)->after('sort_order');
            $table->string('alt')->nullable()->after('is_cover');

            $table->index(['mediable_type', 'mediable_id', 'collection']);
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['mediable_type', 'mediable_id', 'collection']);
            $table->dropColumn(['collection', 'sort_order', 'is_cover', 'alt']);
        });
    }
};
