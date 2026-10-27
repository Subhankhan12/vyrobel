<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\Channel;
use App\Notifications\FeedNotification;

/**
 * Operator: a channel connection stopped syncing (Phase 3) — dispatched on the REAL `channel.error` domain event
 * to the org's operator staff. System category. There is no dedicated Channels screen yet (it is "coming soon"),
 * so the notification lands the operator on the dashboard and names the provider in the context; the deep link
 * follows when the Channels UI ships.
 */
class ChannelSyncErrorNotification extends FeedNotification
{
    public function __construct(private Channel $channel) {}

    public function eventKey(): string
    {
        return 'channel_sync_error';
    }

    public function category(): string
    {
        return 'system';
    }

    public function data(object $notifiable): array
    {
        $provider = ucfirst((string) $this->channel->provider);
        $name = (string) ($this->channel->name ?: $provider);

        return [
            'title' => $this->t('title', 'php/notifications/operator/channel_sync_error_notification.title', ['provider' => $provider]),
            'context' => $this->t('context', 'php/notifications/operator/channel_sync_error_notification.context', ['channel' => $name]),
            'url' => route('app.dashboard', [], false),
            'category' => 'system',
        ];
    }
}
