<?php

namespace App\Models;

use App\Exceptions\MissingPlanTermsException;
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
            // ⚠️ `mustCapture` — see below. On a CHANGE a silent failure is not "no terms", it is the OLD
            //    terms surviving under a new `plan_id`: a confident wrong number, the worst shape available.
            if ($subscription->isDirty('plan_id')) {
                $subscription->fillCapturedTerms(mustCapture: true);
            }
        });
    }

    /**
     * Set (but do not save) the captured columns from the currently-referenced plan.
     *
     * ⚠️⚠️ THE SILENT NO-OP BECAME LOAD-BEARING, AND THE TWO CALLERS NEEDED DIFFERENT ANSWERS. `Plan::find()`
     * respects soft-deletion, so a plan row that no longer resolves used to mean "capture nothing, say
     * nothing" — harmless while the billing SHAPE was read live, and not harmless once the shape is read
     * from this capture.
     *
     * ⚠️ ON CREATE it may still fail quietly: the result is a subscription with NO captured terms and no
     * plan, and `terms()` refuses that outright — loudly, and before any number exists. Nothing can be
     * billed on it, so the downstream refusal is sufficient.
     *
     * ⚠️⚠️ ON A PLAN CHANGE it must NOT. There, a quiet failure leaves the PREVIOUS terms in place while
     * `plan_id` points somewhere else — so the subscription would keep billing the old model while every
     * screen showed the new plan. That is not an absent answer, it is a confident wrong one, which is
     * precisely what this codebase refuses to ship.
     */
    private function fillCapturedTerms(bool $mustCapture = false): void
    {
        $plan = Plan::query()->find($this->plan_id);

        if ($plan === null) {
            if ($mustCapture) {
                throw MissingPlanTermsException::cannotRecapture((string) $this->plan_id);
            }

            return;
        }

        $this->forceFill(PlanTerms::fromPlan($plan)->toCapturedColumns());
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
     * must still price correctly. It is a safety net, not a supported mode.
     *
     * ⚠️⚠️ AND IF NEITHER SOURCE EXISTS IT THROWS. `fillCapturedTerms()` is a no-op when the plan does not
     * resolve, so a subscription created against a SOFT-DELETED plan lands here with no capture and no
     * plan — measured, not assumed. Answering that with a number was the defect.
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

        if ($plan === null) {
            // ⚠️⚠️ NEITHER SOURCE. This used to be `PlanTerms::fromCaptured([])`, which answered €0 and
            //    marked the tax KNOWN. The safety net described above was quietly a second failure mode:
            //    it did not "still price correctly rather than return zero" — returning zero is exactly
            //    what it did, with confidence. What this subscription owes is genuinely unknown, so the
            //    only honest move is to refuse and let the caller (or the job's retry/`failed()`) surface it.
            throw MissingPlanTermsException::forSubscription((string) $this->getKey());
        }

        return PlanTerms::fromPlan($plan);
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
