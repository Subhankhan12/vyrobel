<?php

declare(strict_types=1);

namespace App\Notifications\Guest;

use App\Mail\RefundConfirmationMail;

/**
 * G6 — a refund was processed on the guest's booking. Fires on the live `payment.refunded`, for a genuinely
 * succeeded refund only (the same condition the email applies — a failed refund tells the guest nothing).
 *
 * ⚠️ ADDITIVE — {@see RefundConfirmationMail} still sends; that listener is untouched.
 *
 * ⚠️ MONEY IS NOT RESTATED HERE. The row says a refund was processed and links to Payments, where the real
 * folio figures are read from the certified source. Restating an amount in a pushed row would be a second
 * computation of a money figure — the kind that drifts (§N.8f, #17). The email carries the amount; the row
 * carries the pointer.
 */
class GuestRefundProcessedNotification extends GuestReservationNotification
{
    public function eventKey(): string
    {
        return 'guest_refund_processed';
    }

    public function category(): string
    {
        return 'finance';
    }

    protected function templateKey(): string
    {
        return 'refund-confirmation';
    }

    protected function url(): string
    {
        return route('guest.payments', [], false);
    }
}
