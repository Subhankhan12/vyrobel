<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 · Module 1 contract addition: `fees.kind` — makes cleaning-fee attribution DETERMINISTIC
 * rather than string-name-matched. Owner-accounting attributes a `kind=cleaning` fee to the owner or
 * operator per the management agreement; everything else is `other`. Nullable (defaults to `other`
 * semantically) so existing fee rows are unaffected (see DECISIONS.md 2026-07-03 Step 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->enum('kind', ['cleaning', 'other'])->default('other')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
