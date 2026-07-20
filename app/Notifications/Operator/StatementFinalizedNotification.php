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
        $owner = (string) ($this->statement->owner->name ?? 'owner');

        return [
            'title' => 'Statement finalized · '.$owner,
            'context' => 'Net payable '.$amount,
            'url' => route('app.owners', [], false),
            'category' => 'finance',
        ];
    }
}
