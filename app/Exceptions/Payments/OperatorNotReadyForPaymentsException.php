<?php

declare(strict_types=1);

namespace App\Exceptions\Payments;

use RuntimeException;

/**
 * THE MONEY SWITCH — the operator's Stripe account cannot take a payment.
 *
 * ⚠️⚠️ THERE IS NO FALLBACK, AND THAT IS DELIBERATE. Charging on the platform instead would put guest money
 * back in Vyrobel's balance — the exact holding problem direct charges exist to remove — and a "temporary"
 * fallback with no expiry is how that problem survives its own fix. A fork in the money path is also the
 * two-definitions defect in the worst possible place.
 *
 * ⚠️ So an operator without `charges_enabled` simply cannot take a payment, which makes onboarding a REAL
 * gate rather than an optional step. The message says what is wrong AND what to do — "payment unavailable"
 * would leave a guest and a desk with nowhere to go.
 */
final class OperatorNotReadyForPaymentsException extends RuntimeException
{
    public static function make(): self
    {
        return new self(
            'This property cannot take card payments yet: its Stripe account has not finished verification. '
            .'The property team can complete it from Billing → Connect; until then, take payment another way '
            .'and record it on the folio.'
        );
    }
}
