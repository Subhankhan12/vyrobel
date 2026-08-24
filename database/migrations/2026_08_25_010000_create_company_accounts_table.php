<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️ COMPANY ACCOUNTS — the party a folio balance can be billed TO when settlement follows departure.
 *
 * ⚠️ THIS IS NOT `reservations.source = 'corporate'`. That is a booking CHANNEL — how the booking arrived —
 * and overloading it to also mean "settles on account" would be one field answering two questions.
 *
 * ⚠️ DELIBERATELY MINIMAL. No payment terms, no credit limit, no aging, no dunning, no company statement or
 * portal, and no separate receivables ledger. The test applied was: does the checkout block become
 * DEFENSIBLE with this and nothing more? It does — what a fuller version would add is recorded in DECISIONS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_accounts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('organization_id')->index();
            $table->string('name');
            $table->string('reference')->nullable();   // the operator's own account code, if they use one
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['organization_id', 'name']);
        });

        Schema::table('folios', function (Blueprint $table): void {
            // ⚠️⚠️ A KEYED REFERENCE TO A REAL ACCOUNT ROW — never a free-text note somebody typed. A value
            //    that decides whether a debt may be deferred is typed and closed, or it is unverifiable.
            $table->ulid('billed_to_account_id')->nullable()->after('status');
            $table->timestamp('billed_at')->nullable()->after('billed_to_account_id');
            $table->ulid('billed_by_user_id')->nullable()->after('billed_at');
            $table->index('billed_to_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('folios', function (Blueprint $table): void {
            $table->dropIndex(['billed_to_account_id']);
            $table->dropColumn(['billed_to_account_id', 'billed_at', 'billed_by_user_id']);
        });
        Schema::dropIfExists('company_accounts');
    }
};
