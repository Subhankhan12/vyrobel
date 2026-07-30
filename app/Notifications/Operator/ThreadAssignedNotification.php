<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\MessageThread;
use App\Notifications\FeedNotification;

/**
 * Operator: a conversation was ASSIGNED to you (#90) — dispatched on the REAL `inbox.thread_assigned` domain
 * event to the ONE staff member the thread was handed to. This is the honest, real form of the wireframe's
 * "they're notified on their device": an IN-APP feed row (the thread stays in the shared inbox — an assignment
 * is an internal routing hint, never sent on a guest channel, so the guest never sees it). #33/#28: the feed
 * row carries the guest NAME only (never contact/PII).
 *
 * ⚠️ IN-APP ONLY. {@see via()} is overridden to `['database']` — this is an internal operational hand-off, not
 * an email-worthy event, and there is NO push/SMS infra to fake. It is delivered to the assignee unconditionally
 * (it is not a broadcast the user opts out of; they were personally handed the thread), so it does not sit in the
 * preference catalog — hence no toggle appears for it, matching the wireframe's notify grid.
 */
class ThreadAssignedNotification extends FeedNotification
{
    public function __construct(private MessageThread $thread, private string $guestName) {}

    /** In-app only — never email/push/SMS (no infra to fake; an internal hand-off). */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function eventKey(): string
    {
        return 'conversation_assigned';   // not in the catalog → not a togglable pref; delivery is unconditional in-app
    }

    public function category(): string
    {
        return 'front_desk';
    }

    public function data(object $notifiable): array
    {
        return [
            'title' => 'Conversation assigned to you',
            'context' => $this->guestName.' · in the shared inbox',
            'url' => route('app.inbox', ['c' => $this->thread->getKey()], false),
            'category' => 'front_desk',
        ];
    }
}
