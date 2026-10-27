<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Notifications\FeedNotification;
use App\Services\InboxService;
use App\Support\Inbox\ThreadName;

/**
 * A guest or a unit owner wrote (2026-10-06, `docs/TEAM-CHAT-DESIGN.md` §6) — before this, nobody on the staff was told
 * (`inbox.message_received` had no handler). Sent to the person the conversation is assigned to, else to everyone who
 * may read guest conversations (`guests.view`); ONE bell entry per conversation while it has unread messages ("2 new
 * messages from Ines Ibarra"), and opening the conversation reads it for everyone — the unread counter is the staff's,
 * shared ({@see InboxService::markRead()}).
 *
 * ⚠️ IN-APP ONLY ({@see via()}) — outside the catalogue, allows() would fail open and email every message. ⚠️ NAME ONLY
 * (#28): the conversation's name ({@see ThreadName}), never the message or a contact detail.
 */
class InboxMessageNotification extends FeedNotification
{
    /**
     * I18N-01 — the facts, not a sentence: the title is built in data() with {@see FeedNotification::tc()}, so the bell shows
     * it in each READER's language. $unread is at least 1.
     */
    public function __construct(private string $threadId, private string $name, private int $unread, private bool $fromOwner) {}

    /** In-app only — see the class docblock. */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function eventKey(): string
    {
        return 'inbox_message';   // not in the catalogue → unconditional, in-app only (as conversation_assigned)
    }

    public function category(): string
    {
        return 'messages';
    }

    public function data(object $notifiable): array
    {
        return [
            'title' => $this->tc('title', 'php/notifications/operator/inbox_message_notification.title', $this->unread, ['name' => $this->name]),
            'context' => $this->fromOwner
                ? $this->t('context', 'php/notifications/operator/inbox_message_notification.context_owner')
                : $this->t('context', 'php/notifications/operator/inbox_message_notification.context_guest'),
            'url' => route('app.inbox', ['c' => $this->threadId], false),
            'category' => 'messages',
            'thread_id' => $this->threadId,
        ];
    }
}
