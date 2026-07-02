<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a public URL identifier to `properties` for the Step 7 booking engine. Nullable + unique
 * (MySQL allows multiple NULLs, so pre-existing properties without a public page don't collide).
 * The public booking route resolves a property by this slug — the tenant/property context for the
 * unauthenticated flow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
