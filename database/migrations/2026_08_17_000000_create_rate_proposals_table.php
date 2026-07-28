<?php

declare(strict_types=1);

use App\Models\Concerns\BelongsToOwner;
use App\Services\OwnerRateService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner completeness sweep · Wave C · GATE 1 — the RATE-PROPOSAL state machine (⚠️ REAL MONEY).
 *
 * An operator PROPOSES a rate change on one of an owner's EXCLUSIVELY-owned room types; the owner APPROVES (the
 * change is applied through the certified {@see OwnerRateService} — the rate genuinely changes) or
 * DECLINES (nothing changes). STATES: pending → approved | declined | withdrawn. Integer minor-unit money (#17);
 * the current values are SNAPSHOTTED at propose time so the owner sees a truthful before/after. Owner-scoped
 * ({@see BelongsToOwner}); every transition is a controller POST, capability-gated + audited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_proposals', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('proposed_by')->nullable()->constrained('users')->nullOnDelete();   // the operator
            $table->string('currency', 3);

            // Snapshot of the CURRENT values (at propose time) + the PROPOSED values — integer minor units / percent.
            $table->unsignedBigInteger('current_rate_minor')->nullable();
            $table->unsignedBigInteger('proposed_rate_minor');
            $table->decimal('current_weekend_pct', 5, 2)->nullable();
            $table->decimal('proposed_weekend_pct', 5, 2)->nullable();
            $table->unsignedSmallInteger('current_min_stay')->nullable();
            $table->unsignedSmallInteger('proposed_min_stay')->nullable();

            $table->date('from_date');
            $table->date('to_date');
            $table->text('note')->nullable();

            $table->string('state', 20)->default('pending');   // pending | approved | declined | withdrawn
            $table->timestamp('decided_at')->nullable();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->index(['owner_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_proposals');
    }
};
