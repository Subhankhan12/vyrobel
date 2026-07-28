<?php

declare(strict_types=1);

use App\Support\Owner\OwnerAttention;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner completeness sweep · Wave A · GATE 1 — the SHARED attention-state store.
 *
 * The owner "Needs your attention" feed ({@see OwnerAttention}) derives REAL signals (payout
 * tax-hold · missing payout method · agreement renewal). This table records the owner's per-signal STATE —
 * snoozed-until / dismissed / read — so the My Units action-center and the Overview feed share ONE truth
 * (a snoozed item hides on both until its date; a dismissed item stays hidden; undo restores).
 *
 * Keyed by (owner_id, attention_key): the signal's stable deterministic key (e.g. `tax_hold`, `payout_method`,
 * `agreement_renewal:{agreementId}`). Owner-scoped (BelongsToOwner) + tenant-scoped. Nothing is fabricated — a
 * state row only ever exists for a real signal the owner acted on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_attention_states', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained()->cascadeOnDelete();
            $table->string('attention_key', 120);            // the derived signal's stable key
            $table->timestamp('snoozed_until')->nullable();  // hidden until this moment (null = not snoozed)
            $table->timestamp('dismissed_at')->nullable();   // hidden permanently until restored
            $table->timestamp('read_at')->nullable();        // seen (does not hide)
            $table->timestamps();

            $table->unique(['owner_id', 'attention_key']);   // one state row per owner per signal
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_attention_states');
    }
};
