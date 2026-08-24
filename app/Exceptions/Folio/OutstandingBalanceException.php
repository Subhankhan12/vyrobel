<?php

declare(strict_types=1);

namespace App\Exceptions\Folio;

use RuntimeException;

/**
 * ⚠️ Checkout refused because the folio still has money owed AND it is not billed to a company account.
 *
 * ⚠️ THE MESSAGE IS THE FEATURE. A block that only says "cannot check out" is worse than no block: the
 * agent with a guest waiting fakes a payment or voids the charge, and BOTH are harder to audit than the
 * balance being caught. So it names the amount and every real way forward — including billing it to a
 * company account, which is the route that exists precisely so this block is not a ban on corporate stays.
 */
class OutstandingBalanceException extends RuntimeException
{
    public function __construct(
        public readonly int $balanceMinor,
        public readonly string $currency,
        string $display,
    ) {
        parent::__construct(
            $display.' is still owed on this folio. Take the payment, bill it to a company account if they '
            .'settle later, or post an adjustment or void the charge if it should not stand — then check out.'
        );
    }
}
