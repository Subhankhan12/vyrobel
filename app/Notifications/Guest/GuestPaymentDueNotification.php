<?php

declare(strict_types=1);

namespace App\Notifications\Guest;

use App\Mail\PaymentReminderMail;
use App\Models\Reservation;
use App\Support\Money\MoneyFormatter;

/**
 * G4 — an outstanding balance before the stay. Fires from the live `emails:send-payment-reminders` command.
 *
 * ⚠️ ADDITIVE — {@see PaymentReminderMail} still sends; the command's send site is untouched.
 *
 * ⚠️ The `balance` merge var is passed so the row matches the email even if an operator customises the
 * subject to include it (#86). It is formatted through the SAME {@see MoneyFormatter} the Mailable uses —
 * integer minor units in, one formatting rule, so the two surfaces can never disagree about the figure.
 */
class GuestPaymentDueNotification extends GuestReservationNotification
{
    public function __construct(Reservation $reservation, private int $balanceMinor)
    {
        parent::__construct($reservation);
    }

    public function eventKey(): string
    {
        return 'guest_payment_due';
    }

    public function category(): string
    {
        return 'finance';
    }

    protected function templateKey(): string
    {
        return 'payment-reminder';
    }

    protected function extraVars(): array
    {
        return ['balance' => MoneyFormatter::display($this->balanceMinor, (string) $this->reservation->currency)];
    }

    protected function url(): string
    {
        return route('guest.payments', [], false);
    }
}
