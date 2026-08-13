<?php

declare(strict_types=1);

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * THE SUBSCRIPTION CAPTURES ITS TERMS (the plan-panel pass, 2026-08-13).
 *
 * ⚠️ WHY. The founder's ruling is that editing a plan affects NEW subscriptions only — existing subscribers keep
 * their terms. Until now `subscriptions` stored only `plan_id`, so every price the app showed was read from the
 * LIVE plan row, and that ruling was **unenforceable**: the moment an admin edited a plan, every existing
 * subscriber's displayed price changed with it.
 *
 * ⚠️ AND THE FAILURE WAS NOT "THEY GET RE-PRICED" — IT WAS WORSE. Stripe bills an existing subscription against
 * the Stripe **Price object** it was created with, and nothing in this app pushes a rate change to it. So an
 * edit would NOT have moved the money; it would have moved only the number Vyrobel *displays* — leaving the
 * product quoting one figure while Stripe charged another. That is exactly the display ≠ charge divergence
 * billing passes 1/3/2/2b spent four passes closing, re-opened from the admin side.
 *
 * ⚠️ THE SPLIT TO GET RIGHT (§N.8f, inverted). The billable **QUANTITY** stays DERIVED — it must track reality,
 * because an org that adds a unit really does owe for it (billing pass 1). The agreed **TERMS** must be FROZEN —
 * they must NOT move when someone edits a catalogue row. Quantity live, terms captured.
 *
 * ⚠️ EXISTING ROWS ARE BACKFILLED from their current plan. Leaving them null would mean "the ruling holds for
 * customers who subscribe from today" — true, and useless to everyone already on the books.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Nullable so the column can exist before it is filled; `terms_captured_at` is the real flag —
            // a null price is indistinguishable from "free", but a null captured_at is unambiguous.
            $table->string('terms_pricing_type', 16)->nullable()->after('plan_id');
            $table->unsignedBigInteger('terms_price_minor')->nullable()->after('terms_pricing_type');
            $table->unsignedBigInteger('terms_unit_rate_minor')->nullable()->after('terms_price_minor');
            $table->unsignedTinyInteger('terms_vat_pct')->nullable()->after('terms_unit_rate_minor');
            $table->string('terms_currency', 3)->nullable()->after('terms_vat_pct');
            $table->string('terms_stripe_price_id')->nullable()->after('terms_currency');
            $table->timestamp('terms_captured_at')->nullable()->after('terms_stripe_price_id');
        });

        // ⚠️ BACKFILL — every existing subscription freezes the plan it is on RIGHT NOW. This is the correct
        // value by definition: it is what the customer is currently being shown and charged, so capturing it
        // changes nothing today and protects them from every edit after.
        DB::table('subscriptions')->whereNull('terms_captured_at')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $plan = DB::table('plans')->where('id', $row->plan_id)->first();

                if ($plan === null) {
                    continue;
                }

                DB::table('subscriptions')->where('id', $row->id)->update([
                    'terms_pricing_type' => $plan->pricing_type ?? Plan::PRICING_FLAT,
                    'terms_price_minor' => (int) ($plan->price_minor ?? 0),
                    'terms_unit_rate_minor' => $plan->unit_rate_minor,
                    'terms_vat_pct' => $plan->vat_pct,
                    'terms_currency' => $plan->currency ?? 'EUR',
                    'terms_stripe_price_id' => $plan->stripe_price_id,
                    'terms_captured_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'terms_pricing_type', 'terms_price_minor', 'terms_unit_rate_minor',
                'terms_vat_pct', 'terms_currency', 'terms_stripe_price_id', 'terms_captured_at',
            ]);
        });
    }
};
