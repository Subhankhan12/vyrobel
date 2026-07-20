<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `payout_methods` — an owner's bank account(s) for payouts (Phase 2 · Sub-gate 5). Owner-scoped
 * (`owner_id` + BelongsToOwner) within the tenant. DATA CAPTURE only: the full `iban` is ENCRYPTED at rest
 * on the model (sensitive financial data); `iban_last4` + `iban_country` are kept in the clear ONLY so the UI
 * can mask it (···· 4821) without decrypting the whole number. The LIVE payout rail (Stripe Connect) that would
 * actually send money to this account is deferred/account-gated — nothing here executes a transfer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_methods', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->string('label')->nullable();          // the owner's own name for the account (e.g. "Revolut Business")
            $table->string('account_holder');             // the legal name on the account
            $table->text('iban');                         // ENCRYPTED cast on the model
            $table->string('iban_last4', 4);              // plaintext — for masked display only
            $table->string('iban_country', 2);            // plaintext — the country detected from the IBAN
            $table->string('bic')->nullable();
            $table->char('currency', 3);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_methods');
    }
};
