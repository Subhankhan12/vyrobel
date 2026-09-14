<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️⚠️ HOW ANYONE KNOWS THE RECONCILIATION RAN.
 *
 * The whole point of this table is that **"the report was empty" and "the report never ran" must not look
 * the same**. Every defect this sequence produced was silent, and the scheduler's own failure is one log
 * line with nothing watching it — so a reconciliation whose only output was console text would be the
 * defect it exists to solve, wearing its own uniform. A row with a timestamp survives the terminal closing
 * and makes a stale last-success visible.
 *
 * ⚠️ PLATFORM-LEVEL, NOT TENANT-SCOPED. A reconciliation runs across every tenant and has no request
 * context, so there is deliberately no `organization_id` and the model carries no tenant trait.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            // `running` is written BEFORE the checks so a run that dies midway leaves a visible corpse
            // rather than nothing at all.
            $table->string('status', 16)->default('running');
            // ⚠️ `useCurrent()` rather than a bare NOT NULL. The command always sets this, so the default
            //    never decides anything in practice — but the generic retention sweep inserts a bare row
            //    into EVERY windowed table to prove the pruning works, and a column with no default made
            //    this table the one that could not be swept. Never null, and generically writable.
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();

            // ⚠️ THE NON-VACUITY FLOOR, PERSISTED. "No drift" across zero objects is not a result, so the
            //    count of what was EXAMINED is a first-class column rather than something buried in JSON.
            $table->unsignedInteger('examined')->default(0);
            $table->unsignedInteger('agreed')->default(0);
            $table->unsignedInteger('disagreed')->default(0);
            // ⚠️ UNCHECKED IS ITS OWN COLUMN because collapsing it into `agreed` is precisely the
            //    confident-zero shape this project has already refused twice.
            $table->unsignedInteger('unchecked')->default(0);

            $table->json('report')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'finished_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_runs');
    }
};
