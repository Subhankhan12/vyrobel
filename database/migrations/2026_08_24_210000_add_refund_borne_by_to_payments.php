<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️ A MONEY-DECIDING VALUE, SO IT IS A TYPED COLUMN — NOT A KEY IN `payments.metadata`.
 *
 * `metadata` already carries the refund REASON. A reason is DESCRIPTIVE: if it goes absent or is misspelled
 * nothing downstream changes. This value is DECISIVE — it decides whether the owner's ledger is debited at
 * all — and a JSON key can be absent, misspelled or wrong-typed with NOTHING announcing it. The two must not
 * share a home.
 *
 * ⚠️ NULL MEANS OWNER-BORNE. That is the ruling, and it is also the fail-safe direction: a caller that
 * forgets to set it debits the owner exactly as the product does today. The opposite default would silently
 * STOP charging owners — a far larger change wearing a smaller diff.
 *
 * ⚠️ NOTHING IS BACK-FILLED. Every existing row stays NULL and therefore owner-borne, so the whole existing
 * ledger is untouched by construction rather than by inspection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('refund_borne_by', 16)->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('refund_borne_by');
        });
    }
};
