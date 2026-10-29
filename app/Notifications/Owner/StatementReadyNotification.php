<?php

declare(strict_types=1);

namespace App\Notifications\Owner;

use App\Models\OwnerStatement;
use App\Notifications\FeedNotification;
use App\Support\Money\MoneyFormatter;
use App\Support\Notifications\FeedParams;
use Illuminate\Support\Carbon;

/**
 * Owner: their monthly statement was finalized (Phase 3) — dispatched on the REAL `owner_statement.finalized`
 * domain event to the ONE owner it belongs to (via their linked user). Finance category; links to that specific
 * statement. Integer money via {@see MoneyFormatter} (the same net-payable figure as the statement/payout).
 */
class StatementReadyNotification extends FeedNotification
{
    public function __construct(private OwnerStatement $statement) {}

    public function eventKey(): string
    {
        return 'statement_ready';
    }

    public function category(): string
    {
        return 'finance';
    }

    public function data(object $notifiable): array
    {
        $amount = MoneyFormatter::display((int) $this->statement->net_payable_minor, (string) $this->statement->currency);
        $period = FeedParams::date(Carbon::parse($this->statement->period_start), 'F Y');

        return [
            'title' => $this->t('title', 'php/notifications/owner/statement_ready_notification.title'),
            'context' => $this->t('context', 'php/notifications/owner/statement_ready_notification.context', ['period' => $period, 'amount' => $amount]),
            'url' => route('owner.statements.show', [$this->statement->getKey()], false),
            'category' => 'finance',
        ];
    }
}
