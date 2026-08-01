<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Listeners\SendGuestTransactionalEmails;

/**
 * A request-scoped flag letting an OPERATOR suppress the guest booking-confirmation email for ONE booking
 * (the New Reservation wizard's "Send confirmation email" checkbox, GATE 1). The confirmation is normally
 * sent automatically on `reservation.confirmed` by {@see SendGuestTransactionalEmails}; the
 * event fires synchronously inside `createBooking`, so the operator's choice is stashed here BEFORE the call
 * and read by the listener during it. Bound as a singleton (one instance per request); defaults to "send"
 * so every non-operator path (storefront, imports) is unchanged.
 */
class BookingNotificationIntent
{
    private bool $suppressConfirmation = false;

    public function suppressConfirmation(): void
    {
        $this->suppressConfirmation = true;
    }

    public function confirmationSuppressed(): bool
    {
        return $this->suppressConfirmation;
    }
}
