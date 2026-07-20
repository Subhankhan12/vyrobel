<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\Reservation;
use App\Notifications\FeedNotification;
use App\Support\Folio\FolioTotals;
use App\Support\Money\MoneyFormatter;

/**
 * Operator: a guest checked out still owing money (Phase 3) — dispatched on the REAL `reservation.checked_out`
 * domain event, but ONLY when the reservation's folio has an outstanding balance (the dispatcher computes it
 * from the shared {@see FolioTotals} and skips the notification when the balance is zero).
 * Front-desk category; links to the front desk. Integer money via {@see MoneyFormatter}.
 */
class BalanceDueNotification extends FeedNotification
{
    public function __construct(private Reservation $reservation, private int $balanceMinor, private string $currency) {}

    public function eventKey(): string
    {
        return 'balance_due';
    }

    public function category(): string
    {
        return 'front_desk';
    }

    public function data(object $notifiable): array
    {
        $amount = MoneyFormatter::display($this->balanceMinor, $this->currency);

        return [
            'title' => 'Balance due · '.((string) $this->reservation->reference),
            'context' => $amount.' outstanding at check-out',
            'url' => route('app.front-desk', [], false),
            'category' => 'front_desk',
        ];
    }
}
