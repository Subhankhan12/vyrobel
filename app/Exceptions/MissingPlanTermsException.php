<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when billing is asked what a subscription owes and has NOTHING to answer with.
 *
 * ⚠️⚠️ WHY THIS EXISTS, AND WHY IT IS AN EXCEPTION RATHER THAN A BETTER DEFAULT. `PlanTerms::fromCaptured()`
 * used to coerce an empty snapshot into a charge of **€0 with `tax_known: true`** — not merely the wrong
 * number, but an assertion that the number is CERTAIN. That is the worst outcome this codebase recognises:
 * a 404, a null and an exception each STOP something, while a confident zero lets every caller downstream
 * proceed on a certainty nobody established.
 *
 * ⚠️ THE RULE IS "MUST NOT ANSWER", NOT "MUST NOT ANSWER ZERO". Returning a plausible non-zero figure
 * instead would be worse, not better — it would be a made-up price on a charging path. A function asked to
 * compute money from nothing must refuse.
 *
 * ⚠️ SAFE TO THROW HERE. Both live consumers of `Subscription::terms()` — the Billing page's
 * `billingSummary()` and the Settings summary — already return early when the plan does not resolve, so
 * this cannot take a page down; and `SyncSubscriptionQuantityJob` has `$tries`/`$backoff` and a `failed()`
 * that logs, so on the job path a refusal retries and then announces itself.
 */
class MissingPlanTermsException extends RuntimeException
{
    /** The snapshot carries no pricing shape at all — there is nothing to compute from. */
    public static function noSnapshot(): self
    {
        return new self(
            'Cannot compute a charge: the captured terms carry no `terms_pricing_type`, so there is no '
            .'pricing shape to bill on. A charge of zero would be a confident answer to a question nobody '
            .'answered — refusing instead.'
        );
    }

    /** The shape is known but its money basis is absent — a flat plan with no price, or a rate-less per-unit one. */
    public static function noBasis(string $pricingType): self
    {
        return new self(
            "Cannot compute a charge: the captured terms are [{$pricingType}] but carry no amount to bill "
            .'on. ⚠️ A NULL basis is not a price of zero — `PlanIntegrity` forbids selling either shape '
            .'without a positive amount, so an absent one means the capture is incomplete, not free.'
        );
    }

    /**
     * A plan CHANGE could not re-capture its terms, because the new plan does not resolve.
     *
     * ⚠️ Distinct from {@see self::forSubscription()}: there the terms are ABSENT, here the PREVIOUS terms
     * would survive under a new `plan_id` — an answer, and the wrong one.
     */
    public static function cannotRecapture(string $planId): self
    {
        return new self(
            "Cannot re-capture terms: plan [{$planId}] does not resolve (it is soft-deleted), so this "
            .'subscription would keep billing its PREVIOUS terms while pointing at a different plan. '
            .'⚠️ Refusing the change rather than freezing a lie.'
        );
    }

    /** Neither captured terms nor a resolvable plan — the subscription cannot be priced at all. */
    public static function forSubscription(string $subscriptionId): self
    {
        return new self(
            "Subscription [{$subscriptionId}] has no captured terms and its plan does not resolve (it is "
            .'soft-deleted, or the row was written without the model events that capture terms), so what it '
            .'owes is genuinely unknown. ⚠️ Fix the DATA — restore the plan or backfill the capture — never '
            .'the guard.'
        );
    }
}
