<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Plan;
use App\Support\Marketing\MarketingPricing;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * No two plans may resolve to the SAME (tier, interval) — ⚠️ because the pricing page silently drops one.
 *
 * ⚠️⚠️ WHAT THIS PREVENTS, MEASURED. `MarketingPricing::tierKey()` derives a tier from a plan code by
 * stripping the interval suffix, so `essential_monthly` and a plain `essential` both resolve to
 * `essential`. `tiersWithCycles()` groups by that key and places each row with `??=`, so the SECOND row in
 * a cycle is never rendered. With five active plans the page rendered **two cards**, and the card keyed
 * `essential` carried the collider's code and price — the real €49.00 plan was replaced on `/pricing`,
 * which is where money is decided.
 *
 * ⚠️ THE `??=` IS NOT THE DEFECT AND IS NOT CHANGED. It was a deliberate choice by the pairing pass so a
 * collision could not overwrite a card mid-render; it turned a corruption into a disappearance. This rule
 * stops the collision existing, which is the layer the problem actually belongs to.
 *
 * ⚠️⚠️ THE DERIVATION IS THE REAL DEFECT; THIS IS A GUARD ON IT. A tier is an identity and should be a
 * COLUMN, not something inferred from the spelling of a code — `my_pro_monthly` derives `my_pro`, which is
 * a tier nobody declared. Making `tier` a real column is a migration plus a backfill plus every reader, and
 * is deliberately not this pass. Until then this keeps the inference from producing two answers for one
 * question.
 *
 * ⚠️ IT REUSES `tierKey()` RATHER THAN RE-DERIVING. A second copy of that logic is exactly how the two
 * would drift, and the drift would be invisible: both would look right in isolation.
 *
 * ⚠️⚠️ ITS LIMIT, STATED PLAINLY: A VALIDATOR COVERS THE TWO IN-APP DOORS AND NOTHING ELSE — `store()` and
 * `update()`. Seeders, migrations, factories and direct writes bypass it entirely, exactly as the
 * soft-delete pass established that a view-level fix is not a rule. A database unique index WOULD cover
 * them, but `tierKey()` is PHP logic over a PHP constant (`CYCLES`); expressing it in SQL would be a second
 * definition of the derivation, which is the drift this class exists to avoid. The seeder writes only
 * `{tier}_{interval}` codes, which cannot collide with each other, so the uncovered writers write safe
 * values today.
 */
class UniquePlanTierCycle implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(private readonly ?string $ignorePlanId = null) {}

    /** @param  array<string, mixed>  $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;   // `required`/`string` already say this; one message per problem.
        }

        $interval = $this->data['interval'] ?? null;

        if (! is_string($interval) || $interval === '') {
            return;   // the interval's own rule reports its absence.
        }

        $tier = MarketingPricing::tierKey($value);

        $clash = Plan::query()
            ->when($this->ignorePlanId !== null, fn ($q) => $q->whereKeyNot($this->ignorePlanId))
            ->where('interval', $interval)
            ->get(['id', 'code'])
            ->first(fn (Plan $plan): bool => MarketingPricing::tierKey((string) $plan->code) === $tier);

        if ($clash !== null) {
            $fail(__('php/support/billing/unique_plan_tier_cycle.tier_taken', [
                'tier' => $tier,
                'plan' => (string) $clash->code,
                'interval' => match ($interval) {
                    'monthly' => __('php/support/billing/unique_plan_tier_cycle.interval_monthly'),
                    'yearly' => __('php/support/billing/unique_plan_tier_cycle.interval_yearly'),
                    default => $interval,
                },
            ]));
        }
    }
}
