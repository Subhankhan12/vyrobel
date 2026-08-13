<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Support\Billing\PlanTerms;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Tenant billing state. `stripe_subscription_id` stays null until live Stripe Billing (account-gated).
 *
 * @property string $status
 * @property ?Carbon $trial_ends_at
 * @property ?Carbon $current_period_end
 * @property ?string $stripe_subscription_id
 * @property ?string $stripe_customer_id
 * @property-read ?Plan $plan
 */
class Subscription extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'plan_id',
        'terms_pricing_type',
        'terms_price_minor',
        'terms_unit_rate_minor',
        'terms_vat_pct',
        'terms_currency',
        'terms_stripe_price_id',
        'terms_captured_at',
        'stripe_subscription_id',
        'stripe_customer_id',
        'status',
        'trial_ends_at',
        'current_period_end',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_end' => 'datetime',
            'terms_captured_at' => 'datetime',
        ];
    }

    /**
     * ⚠️ CAPTURE TERMS AT THE MODEL, so no writer can forget.
     *
     * The first version of this pass called a capture helper from each known call site — the billing service,
     * the admin customer screen. Verifying the demo seed then showed a subscription with NULL terms, because
     * `DemoSeeder` creates rows directly and was a call site nobody had listed. That is the same shape as the
     * third VAT-ID writer found in `DO-BILL-1` (§N.18.3): **enumerating writers is a losing game; bind the
     * behaviour to the model instead.** Every path that persists a subscription now captures, including
     * seeders, factories and anything written later.
     */
    protected static function booted(): void
    {
        static::creating(function (self $subscription): void {
            if ($subscription->terms_captured_at === null) {
                $subscription->fillCapturedTerms();
            }
        });

        static::updating(function (self $subscription): void {
            // A plan CHANGE re-agrees the terms; anything else must leave them frozen.
            if ($subscription->isDirty('plan_id')) {
                $subscription->fillCapturedTerms();
            }
        });
    }

    /** Set (but do not save) the captured columns from the currently-referenced plan. */
    private function fillCapturedTerms(): void
    {
        $plan = Plan::query()->find($this->plan_id);

        if ($plan !== null) {
            $this->forceFill(PlanTerms::fromPlan($plan)->toCapturedColumns());
        }
    }

    /**
     * The terms THIS subscription bills on — captured at subscribe-time, not the live catalogue row.
     *
     * ⚠️ THIS IS WHAT MAKES THE FOUNDER'S RULING TRUE. "Editing a plan affects new subscriptions only" is a
     * claim about architecture, not intent: while every price was read from the live `Plan`, an admin edit
     * silently changed what existing subscribers were shown. Reading captured terms is what stops it.
     *
     * ⚠️ FALLS BACK TO THE PLAN ONLY IF NEVER CAPTURED. The migration backfills every existing row, so this
     * should not trigger in practice — but a subscription created by some future path that forgets to capture
     * must still price correctly rather than return zero. It is a safety net, not a supported mode.
     */
    public function terms(): PlanTerms
    {
        if ($this->terms_captured_at !== null) {
            return PlanTerms::fromCaptured($this->only([
                'terms_pricing_type', 'terms_price_minor', 'terms_unit_rate_minor',
                'terms_vat_pct', 'terms_currency', 'terms_stripe_price_id',
            ]));
        }

        $plan = $this->plan;

        return $plan !== null ? PlanTerms::fromPlan($plan) : PlanTerms::fromCaptured([]);
    }

    /** What this subscription owes this period — its OWN terms, the org's LIVE unit count. */
    public function chargeFor(): array
    {
        return $this->terms()->charge();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }
}
