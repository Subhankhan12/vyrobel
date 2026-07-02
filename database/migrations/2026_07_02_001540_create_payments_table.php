<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `payments` — money movements. TOKEN-ONLY: `card_token` holds a gateway token, NEVER raw card
 * data. Correct-by-reversal: a refund/void is a NEW offsetting row; the original is never
 * mutated or deleted by services (SoftDeletes is a safety net only).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('folio_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('gateway', ['stripe', 'paypal', 'square', 'manual', 'bank_transfer', 'ota_collect'])->default('manual');
            $table->string('gateway_ref')->nullable();
            $table->enum('type', ['payment', 'refund', 'deposit_hold', 'capture', 'void', 'chargeback'])->default('payment');
            $table->bigInteger('amount_minor')->default(0);
            $table->char('currency', 3);
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded', 'authorized', 'captured', 'voided'])->default('pending');
            $table->string('card_token')->nullable(); // gateway token only — never raw card data
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('folio_id');
            $table->index('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
