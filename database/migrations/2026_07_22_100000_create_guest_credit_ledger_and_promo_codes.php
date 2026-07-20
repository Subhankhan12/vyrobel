<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4c · Sub-gate 2 — the guest WALLET, an APPEND-ONLY ledger built to the same discipline as `owner_ledger`
 * and the folio (invariants #7 append-only · #17 integer money).
 *
 * 🪤 There is DELIBERATELY NO balance column. The spendable balance is DERIVED (Σ live credits − Σ debits) in
 * {@see \App\Services\GuestWalletService}; storing a mutable balance would be the drift bug the ledger discipline
 * exists to prevent. Every row is a credit or a debit with a positive `amount_minor` (the `direction` carries the
 * sign). Correct-by-reversal: reversing a credit posts a NEW debit whose `reverses_id` points at it — never an
 * edit or delete. `expires_at` (credits only) lets a credit stop counting toward the spendable balance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_credit_ledger', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->enum('direction', ['credit', 'debit']);
            // credit types: refund / add_on_cancelled / referral / loyalty / birthday / promo ; debit types:
            // applied (to a booking) / reversal / adjustment.
            $table->enum('type', ['refund', 'add_on_cancelled', 'referral', 'loyalty', 'birthday', 'promo', 'applied', 'reversal', 'adjustment']);
            $table->bigInteger('amount_minor'); // always positive; `direction` carries the sign
            $table->char('currency', 3);
            $table->string('description');
            $table->foreignUlid('reverses_id')->nullable()->constrained('guest_credit_ledger')->nullOnDelete();
            $table->nullableUlidMorphs('source'); // polymorphic origin (reservation / payment / promo_code / …)
            $table->timestamp('expires_at')->nullable(); // credits only — an expired credit stops counting
            $table->string('idempotency_key')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique('idempotency_key');
            $table->index(['guest_id', 'direction']);
        });

        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->bigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('description')->nullable();
            $table->unsignedInteger('max_redemptions')->default(1); // total redemptions across all guests
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_credit_ledger');
        Schema::dropIfExists('promo_codes');
    }
};
