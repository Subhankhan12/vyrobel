<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DEC-7 (his ruling, 2026-11-13: "C") — a COMPLIMENTARY plan. ⚠️ ADDITIVE: one nullable column, nothing else.
 *
 * Why a column and not a new `status` value: `subscriptions.status` is a database ENUM, and widening an enum is a column
 * CHANGE, which `ops/release.sh migrate` refuses by design. A complimentary workspace is therefore a `trialing`
 * subscription whose `trial_ends_at` IS this date — so every existing access gate (`EnsureSubscriptionActive`,
 * `SubscriptionStatus`) already does the right thing, and it lapses to the paywall on the day like any trial. This column
 * only says WHY: it is what keeps a complimentary workspace out of the "on a trial" and revenue figures and lets the
 * screens say "Complimentary" instead of counting down a trial. NULL = an ordinary subscription.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->timestamp('complimentary_until')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn('complimentary_until');
        });
    }
};
