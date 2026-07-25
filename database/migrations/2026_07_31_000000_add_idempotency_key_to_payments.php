<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `payments.idempotency_key` (Batch C · GATE 3) — the money-path idempotency guard for the on-desk manual
 * payment. A per-submit nonce is carried on the Record-a-payment form; {@see FolioService::recordPayment}
 * records ONCE per key (checked before the gateway charge), so a double-submit / double-click never
 * double-records. Nullable — the guest hosted-checkout path keys on the Stripe PaymentIntent id
 * (gateway_ref) instead, and other callers pass no key. UNIQUE so it is also the concurrent-race backstop.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('idempotency_key')->nullable()->unique()->after('gateway_ref');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
