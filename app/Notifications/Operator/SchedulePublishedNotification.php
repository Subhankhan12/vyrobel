<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Notifications\FeedNotification;

/**
 * #104b GATE 3 — a rostered staff member is notified when their week's schedule is PUBLISHED. Real values only
 * (#39): the property name + the week label. Operations category; links to the schedule.
 */
class SchedulePublishedNotification extends FeedNotification
{
    public function __construct(private string $propertyName, private string $weekLabel) {}

    public function eventKey(): string
    {
        return 'schedule_published';
    }

    public function category(): string
    {
        return 'operations';
    }

    public function data(object $notifiable): array
    {
        return [
            'title' => $this->t('title', 'php/notifications/operator/schedule_published_notification.title'),
            'context' => $this->t('context', 'php/notifications/operator/schedule_published_notification.context', ['property' => $this->propertyName, 'week' => $this->weekLabel]),
            'url' => route('app.schedule', [], false),
            'category' => 'operations',
        ];
    }
}
