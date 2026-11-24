<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VY-04 (audit of 2026-11-24, reproduced) — ONE CHECKOUT PAGE MAKES ONE BOOKING. ⚠️ ADDITIVE: one nullable column and its unique index.
 *
 * `booking_quotes.checkout_token` is the one-time token the public checkout page prints into its form. The quote the form creates stores
 * it, and the UNIQUE index is what makes "the same page posted twice" a single booking: the second insert cannot happen, whatever the
 * timing (a double click, a retry, two requests in flight at once). NULL = a quote that did not come from the checkout form (the JSON
 * API's quotes, every quote made before this existed) — and many NULLs live side by side under a unique index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_quotes', function (Blueprint $table): void {
            $table->string('checkout_token', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('booking_quotes', function (Blueprint $table): void {
            $table->dropUnique(['checkout_token']);
            $table->dropColumn('checkout_token');
        });
    }
};
