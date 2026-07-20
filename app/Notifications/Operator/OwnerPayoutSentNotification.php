<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\Payout;
use App\Notifications\FeedNotification;
use App\Notifications\Owner\PayoutSentNotification;
use App\Support\Money\MoneyFormatter;

/**
 * Operator: a payout was disbursed to an owner (Phase 3) — dispatched on the REAL `payout.paid` domain event to
 * the org's operator staff. This is the OPERATOR side of the same event that notifies the owner
 * ({@see PayoutSentNotification}); the two are distinct notifications with distinct
 * preference keys and recipients. Finance category; links to the Owners screen. Integer money via {@see MoneyFormatter}.
 */
class OwnerPayoutSentNotification extends FeedNotification
{
    public function __construct(private Payout $payout) {}

    public function eventKey(): string
    {
        return 'owner_payout_sent';
    }

    public function category(): string
    {
        return 'finance';
    }

    public function data(object $notifiable): array
    {
        $amount = MoneyFormatter::display((int) $this->payout->amount_minor, (string) $this->payout->currency);
        $owner = (string) ($this->payout->owner->name ?? 'owner');

        return [
            'title' => 'Owner payout sent',
            'context' => $amount.' disbursed to '.$owner,
            'url' => route('app.owners', [], false),
            'category' => 'finance',
        ];
    }
}
