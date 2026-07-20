<?php

declare(strict_types=1);

namespace App\Notifications\Owner;

use App\Models\Payout;
use App\Notifications\FeedNotification;
use App\Support\Money\MoneyFormatter;

/**
 * Owner: a payout was sent to them (Phase 3) — dispatched on the REAL `payout.paid` domain event to the ONE
 * owner the payout belongs to. Finance category; links to the owner's Payouts page. Integer money via
 * {@see MoneyFormatter} (the same figure as the payout/statement).
 */
class PayoutSentNotification extends FeedNotification
{
    public function __construct(private Payout $payout) {}

    public function eventKey(): string
    {
        return 'payout_sent';
    }

    public function category(): string
    {
        return 'finance';
    }

    public function data(object $notifiable): array
    {
        $amount = MoneyFormatter::display((int) $this->payout->amount_minor, (string) $this->payout->currency);

        return [
            'title' => 'Payout sent',
            'context' => $amount.' is on its way to your account',
            'url' => route('owner.payouts', [], false),
            'category' => 'finance',
        ];
    }
}
