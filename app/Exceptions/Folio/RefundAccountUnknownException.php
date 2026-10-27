<?php

declare(strict_types=1);

namespace App\Exceptions\Folio;

use RuntimeException;

/**
 * A Stripe payment that does not record which connected account took it cannot be refunded automatically.
 *
 * ⚠️⚠️ THIS REFUSES; IT DOES NOT FALL BACK. A direct charge lives on the operator's connected account.
 * Refunding without naming it targets the PLATFORM — either the charge is not there, or, far worse, it
 * succeeds against platform funds and Vyrobel's money settles an operator's guest. Guessing is the failure
 * mode; refusing is the feature.
 *
 * ⚠️ IT WAS BUILT, WITHDRAWN, AND HAS NOW COME BACK — and the history is the point. Written when the switch
 * covered only the two hosted-Checkout paths, it immediately blocked a certified desk-payment refund, whose
 * payment was genuinely a PLATFORM charge because `recordPayment()` had not been switched. ⚠️ A guard that
 * presumes a migration is complete is not a guard, it is a break. It is correct now, and ONLY now, because
 * all three charge paths stamp the account they were taken on.
 *
 * ⚠️ A `manual` payment never reaches this: it has no gateway charge at all, so its refund is a folio
 * adjustment and a null account is the honest value there.
 *
 * ⚠️⚠️ THIS MESSAGE DELIBERATELY NAMES NO VYROBEL SCREEN, even though the onboarding screen now exists.
 * Onboarding an account cannot repair a PAYMENT whose account reference was never recorded — pointing there
 * would send someone to a page that cannot help them, which is the exact defect the readiness refusal was
 * corrected for. Only refunding in Stripe directly resolves this one.
 */
final class RefundAccountUnknownException extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('php/exceptions/folio/refund_account_unknown_exception.message'));
    }
}
