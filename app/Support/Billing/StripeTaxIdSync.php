<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Organization;
use App\Payments\Stripe\StripeClientInterface;
use Illuminate\Support\Facades\Log;

/**
 * THE ONE path from `organizations.billing_vat_id` to the Stripe customer (DO-BILL-1, 2026-08-13).
 *
 * ⚠️ THE PROBLEM IT CLOSES. Vyrobel collects a VAT number at checkout and in Billing details, and until now
 * used it for **PDF letterheads only** — billing pass 2b verified by grep that it never reached Stripe. But 2b
 * also turned Stripe Tax on, and Stripe Tax decides reverse charge from the **customer's** `tax_ids`. So a
 * business that had already told Vyrobel its VAT number, and then skipped Stripe's own prompt for it, was
 * charged VAT it was entitled to reverse-charge. The app held the answer and never passed it on.
 *
 * ⚠️ THE POSTURE IS ASYMMETRIC, ON PURPOSE. A VAT ID drives the tax OUTCOME:
 *   - fail one way and the customer is **overcharged** — visible, complained about, refundable;
 *   - fail the other and they are **wrongly zero-rated** — silent, and the operator carries the liability.
 * So every uncertain path here resolves to **taxable**: an untypeable number is not sent, a rejected number is
 * not retried into place, and neither condition is allowed to look like success.
 *
 * ⚠️ WHAT THIS IS NOT. It does not VALIDATE the number against VIES — see {@see VatId}. Sending an id to Stripe
 * means Stripe may validate it asynchronously and may zero-rate before that completes. Whether the product
 * should require validation *first* is a compliance decision recorded for the founder in §G.1; the safe half
 * (never send what we cannot type; never treat a rejection as success) is implemented here regardless.
 *
 * ⚠️ IT DOES NOT CHANGE WHAT THE APP CLAIMS. display = charge = Stripe still holds: the page still shows the
 * pre-tax subtotal and "tax calculated at checkout" (pass 2b). This changes what STRIPE computes, never what
 * Vyrobel asserts — the app has no more idea of the final tax than it did before.
 */
class StripeTaxIdSync
{
    /**
     * Push the org's stored VAT number onto its Stripe customer, if both exist and the number can be typed.
     *
     * Safe to call repeatedly and from anywhere — it no-ops without a customer or a usable number, so callers
     * do not need to know which of those is true.
     *
     * @return bool true only when a typed id is now on the customer.
     */
    public static function push(Organization $organization): bool
    {
        $subscription = $organization->currentSubscription();
        $customerId = $subscription === null ? '' : trim((string) $subscription->stripe_customer_id);

        if ($customerId === '') {
            // No Stripe customer yet — the org has not completed a checkout. Nothing to attach to, and nothing
            // is lost: Stripe's own `tax_id_collection` (pass 2b) covers the very first session, and this runs
            // again the moment the webhook gives us a customer id.
            return false;
        }

        $typed = VatId::typed($organization->billing_vat_id);

        if ($typed === null) {
            // ⚠️ FAIL SAFE. Either there is no VAT number, or it is one we cannot confidently type. We do NOT
            // send it hopefully: an id typed under the wrong regime can be accepted and zero-rate the sale.
            // The customer stays taxable, which is the recoverable direction.
            if (self::hasSomethingUnusable($organization)) {
                Log::warning('DO-BILL-1: stored VAT id could not be typed for Stripe — customer stays taxable.', [
                    'organization_id' => $organization->getKey(),
                    'stripe_customer_id' => $customerId,
                    // ⚠️ The VALUE is deliberately not logged — it is a customer business identifier.
                    'vat_id_length' => strlen((string) $organization->billing_vat_id),
                ]);
            }

            return false;
        }

        $attached = app(StripeClientInterface::class)->syncCustomerTaxId($customerId, $typed);

        if (! $attached) {
            // ⚠️ Stripe refused it. Surfaced rather than swallowed — but NOT thrown: a bad VAT number must not
            // break a subscription. The customer simply earns no zero rating.
            Log::warning('DO-BILL-1: Stripe rejected the stored VAT id — customer stays taxable.', [
                'organization_id' => $organization->getKey(),
                'stripe_customer_id' => $customerId,
                'tax_id_type' => $typed['type'],
            ]);
        }

        return $attached;
    }

    /** A non-empty stored value that {@see VatId} could not type — worth warning about; blank is not. */
    private static function hasSomethingUnusable(Organization $organization): bool
    {
        return trim((string) $organization->billing_vat_id) !== '';
    }
}
