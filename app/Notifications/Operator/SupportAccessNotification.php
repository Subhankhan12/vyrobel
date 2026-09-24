<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\BreakGlassSession;
use App\Notifications\FeedNotification;
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
            'title' => 'Vyrobel support viewed your workspace (read-only)',
            'context' => $this->adminName.' · '.$from->format('j M Y, H:i').'–'.$to->format('H:i').' UTC · '
                .$this->session->pages_viewed.' page'.($this->session->pages_viewed === 1 ? '' : 's').' viewed · Reason: '.$this->session->reason,
            'url' => route('app.notifications', [], false),
            'category' => 'account',
        ];
    }
}
