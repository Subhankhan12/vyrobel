<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\OwnerStatement;
use App\Notifications\FeedNotification;
use App\Notifications\Owner\StatementReadyNotification;
use App\Support\Money\MoneyFormatter;

/**
 * Operator: an owner statement was finalized (Phase 3) — dispatched on the REAL `owner_statement.finalized`
 * domain event to the org's operator staff. The OPERATOR side of the same event that notifies the owner
 * ({@see StatementReadyNotification}). Finance category; links to the Owners screen.
 * Integer money via {@see MoneyFormatter}.
 */
class StatementFinalizedNotification extends FeedNotification
{
    public function __construct(private OwnerStatement $statement) {}

    public function eventKey(): string
    {
        return 'statement_finalized';
    }

    public function category(): string
    {
        return 'finance';
    }

    public function data(object $notifiable): array
    {
        $amount = MoneyFormatter::display((int) $this->statement->net_payable_minor, (string) $this->statement->currency);
        $owner = $this->statement->owner->name ?? null;

        return [
            'title' => $owner !== null
                ? $this->t('title', 'php/notifications/operator/statement_finalized_notification.title', ['owner' => (string) $owner])
                : $this->t('title', 'php/notifications/operator/statement_finalized_notification.title_no_owner'),
            'context' => $this->t('context', 'php/notifications/operator/statement_finalized_notification.context', ['amount' => $amount]),
            'url' => route('app.owners', [], false),
            'category' => 'finance',
        ];
    }
}
