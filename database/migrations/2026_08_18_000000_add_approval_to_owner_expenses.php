<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner completeness sweep · Wave C · GATE 2 — the EXPENSE-APPROVAL state machine (⚠️ CHANGES THE DEDUCTION MODEL).
 *
 * A DEDUCTIBLE expense no longer reduces the owner's net the moment it's recorded — it is created `pending` and is
 * charged to the owner's ledger ONLY when the owner APPROVES it (or it stays out of the net if QUERIED). Because
 * net is Σ owner_ledger, gating the ledger post behind approval makes the reconciliation hold in BOTH states (#17):
 * a pending/queried expense doesn't reduce net; an approved one does. Non-deductible expenses never post a ledger
 * line, so they need no approval → `approved`. Existing rows default `approved` (they already posted).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_expenses', function (Blueprint $table): void {
            $table->string('approval_state', 20)->default('approved')->after('deductible');   // pending | approved | queried
            $table->timestamp('decided_at')->nullable()->after('approval_state');
            $table->foreignUlid('decided_by')->nullable()->after('decided_at')->constrained('users')->nullOnDelete();
            $table->text('queried_note')->nullable()->after('decided_by');
        });
    }

    public function down(): void
    {
        Schema::table('owner_expenses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['approval_state', 'decided_at', 'queried_note']);
        });
    }
};
