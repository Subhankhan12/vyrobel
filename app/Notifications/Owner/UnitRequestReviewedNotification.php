<?php

declare(strict_types=1);

namespace App\Notifications\Owner;

use App\Models\UnitRequest;
use App\Notifications\FeedNotification;

/**
 * #104 GATE 1 — the owner's add-unit REQUEST was reviewed by the operator (approved → a real unit now exists, or
 * declined). Dispatched to the ONE owner the request belongs to (via their linked user), closing the feedback
 * loop the audit found missing (the owner used to never learn the outcome). Real values only (#39): the title
 * reflects the REAL decision, the context carries the requested unit name + any operator note. Links back to the
 * owner's My Units so an approved unit is one click away.
 */
class UnitRequestReviewedNotification extends FeedNotification
{
    public function __construct(private UnitRequest $request, private string $decision) {}

    public function eventKey(): string
    {
        return 'unit_request_reviewed';
    }

    public function category(): string
    {
        return 'account';
    }

    public function data(object $notifiable): array
    {
        $approved = $this->decision === 'approved';
        $name = (string) $this->request->requested_name;
        $note = (string) ($this->request->review_note ?? '');

        $params = ['name' => $name, 'note' => $note];
        $context = match (true) {
            $approved && $note === '' => $this->t('context', 'php/notifications/owner/unit_request_reviewed_notification.context_approved', $params),
            $approved => $this->t('context', 'php/notifications/owner/unit_request_reviewed_notification.context_approved_note', $params),
            $note === '' => $this->t('context', 'php/notifications/owner/unit_request_reviewed_notification.context_declined', $params),
            default => $this->t('context', 'php/notifications/owner/unit_request_reviewed_notification.context_declined_note', $params),
        };

        return [
            'title' => $approved
                ? $this->t('title', 'php/notifications/owner/unit_request_reviewed_notification.title_approved')
                : $this->t('title', 'php/notifications/owner/unit_request_reviewed_notification.title_declined'),
            'context' => $context,
            'url' => route('owner.units', [], false),
            'category' => 'account',
        ];
    }
}
