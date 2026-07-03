<?php

use App\Models\Scopes\OwnerScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `owner_statement_lines.owner_id` (Phase 3 · Module 1 Step 6) — denormalized from the parent
 * statement so the owner-portal {@see OwnerScope} can restrict statement lines
 * DIRECTLY (defense-in-depth: an owner never reads another owner's lines even if queried without the
 * parent statement). Set by OwnerStatementService when writing the lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_statement_lines', function (Blueprint $table) {
            $table->foreignUlid('owner_id')->nullable()->after('owner_statement_id')->constrained('owners')->cascadeOnDelete();
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::table('owner_statement_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });
    }
};
