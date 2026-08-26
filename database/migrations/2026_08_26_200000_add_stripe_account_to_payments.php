<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * THE MONEY SWITCH — the connected account a charge was taken on.
 *
 * ⚠️⚠️ A REFUND MUST BE ISSUED ON THE ACCOUNT THAT TOOK THE CHARGE. Stripe: "Stripe debits the connected
 * account directly for refunds to direct charge payments." Without this column a refund would be attempted
 * on the platform, where the charge does not exist — so the account is recorded ON THE PAYMENT at charge
 * time and read back at refund time, rather than re-derived from the organisation later (an operator could
 * have re-onboarded, and a refund must follow the money, not the current configuration).
 *
 * ⚠️ NULL means the platform took it — every payment recorded before this switch, and every payment through
 * the `manual` gateway. Those refund exactly as they did before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('stripe_account_id')->nullable()->after('gateway_ref');
            $table->index('stripe_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['stripe_account_id']);
            $table->dropColumn('stripe_account_id');
        });
    }
};
