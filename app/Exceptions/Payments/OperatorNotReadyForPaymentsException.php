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
 *
 * ⚠️⚠️ IT NAMES *WHO*, NOT *WHERE*, AND THAT IS THE WHOLE LESSON OF THIS STRING. A first version read "…can
 * complete it from Billing → Connect" — ⚠️ A SCREEN THAT DOES NOT EXIST. The onboarding pass built a gated
 * ROUTE and no interface, and said so plainly; the refusal then sent a blocked operator to a page they
 * could not reach, costing them the time to go and look.
 *
 * ⚠️ IT GOT THERE BY FOLLOWING AN INSTRUCTION. "Make the refusal actionable" was read as "name a route",
 * and those are NOT the same instruction: the second manufactures a CAPABILITY CLAIM. ⚠️ And it landed in
 * an ERROR STRING — the one place no reviewer reads and no gate parses, in a product whose recent history
 * is removing exactly these claims from pages that DO get reviewed.
 *
 * ⚠️ THE RULE: an error may name a ROLE and a NEXT ACTION. It may not name a screen, a route or a
 * capability unless something already reachable provides it.
 *
 * ⚠️⚠️ IT NAMES A SCREEN AGAIN — AND ONLY BECAUSE THE SCREEN NOW EXISTS. "Billing → Card acceptance" is a
 * real panel on `/app/billing`, gated `can:billing.manage`, which the matrix grants to Super Admin and
 * Property Owner — ⚠️ EXACTLY the people "someone who manages billing for the account" describes. The role
 * and the gate were checked against each other rather than assumed; had they not matched, the MESSAGE would
 * have been fixed, never the gate.
 */
final class OperatorNotReadyForPaymentsException extends RuntimeException
{
    public static function make(): self
    {
        return new self(
            'This property cannot take card payments yet: its Stripe account has not finished verification. '
            .'Someone who manages billing for the account can finish it under Billing → Card acceptance. '
            .'Until then, take payment another way and record it on the folio.'
        );
    }
}
