<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\BreakGlassSession;
use App\Notifications\FeedNotification;
use App\Support\Notifications\FeedParams;
use App\Support\Platform\BreakGlass;

/**
 * ⚠️ ADMIN 01 · PHASE 3 — TOLD. After a Vyrobel break-glass session ends, every active back-office user of the
 * organisation is told WHO looked, WHEN, for how long, and WHY — in the in-app feed, because mail is dark (email
 * becomes a second channel when Postmark lands). ⚠️ ALWAYS ON: an organisation cannot switch off being told that
 * its data was looked at. Sent synchronously by {@see BreakGlass::end()} — never queued, so a parked queue
 * cannot swallow it.
 */
class SupportAccessNotification extends FeedNotification
{
    public function __construct(private string $adminName, private BreakGlassSession $session) {}

    public function eventKey(): string
    {
        return 'support_access';
    }

    public function category(): string
    {
        return 'account';
    }

    public function data(object $notifiable): array
    {
        $from = $this->session->started_at->copy()->utc();
        $to = ($this->session->ended_at ?? $this->session->expires_at)->copy()->utc();

        return [
            'title' => $this->t('title', 'php/notifications/operator/support_access_notification.title'),
            // ⚠️ I18N-01 — one key per shape (FeedNotification::t() records a key, not a plural choice).
            'context' => $this->t('context', $this->session->pages_viewed === 1 ? 'php/notifications/operator/support_access_notification.context_one_page' : 'php/notifications/operator/support_access_notification.context_pages', [
                'admin' => $this->adminName,
                'from' => FeedParams::date($from, 'j M Y, H:i'),
                'to' => $to->format('H:i'),
                'pages' => $this->session->pages_viewed,
                'reason' => (string) $this->session->reason,
            ]),
            'url' => route('app.notifications', [], false),
            'category' => 'account',
        ];
    }
}
