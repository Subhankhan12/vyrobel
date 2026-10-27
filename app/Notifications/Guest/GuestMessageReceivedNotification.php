<?php

declare(strict_types=1);

namespace App\Notifications\Guest;

use App\Listeners\DispatchNotificationsForDomainEvent;
use App\Models\MessageThread;
use App\Notifications\FeedNotification;

/**
 * G3 — the property replied to the guest. Fires on the live `inbox.message_sent` (the OPERATOR's direction;
 * the guest's own reply is recorded `inbound`, so a guest is never notified about their own message).
 *
 * ⚠️ THIS IS THE "else NOBODY WROTE". {@see DispatchNotificationsForDomainEvent::ownerMessage()}
 * bails on a thread with no `owner_id` — commented "an operator↔guest thread — not the owner's conversation".
 * That branch is now written.
 *
 * ⚠️ FROM THE PROPERTY, NEVER FROM AN OWNER. `message_threads` is `guest_id` XOR `owner_id`, so a thread that
 * reaches a guest structurally cannot be an owner's. Owner↔guest messaging does not exist and this must not
 * accidentally invent it.
 *
 * ⚠️⚠️ ONE WIRE COVERS BOTH SENDING PATHS — and that is a correction to the gate brief, established by
 * reading the code. The brief stated the BULK send (`GuestBulkActionController` → `OperatorGuestMessageMail`)
 * is "a separate path that does NOT go through the inbox thread". It does: the bulk loop calls
 * `$inbox->sendOutbound($thread, ...)`, and `InboxService::sendOutbound()` records `inbox.message_sent`.
 * So the founder's "both directions notify" ruling is satisfied by this ONE handler; adding a second notify
 * in the bulk controller would have given every bulk recipient TWO rows for one message.
 *
 * ⚠️ #28 — NO MESSAGE BODY IN THE PAYLOAD. The row says a message arrived and links to the thread, where the
 * body is read under the guest's own scope. A notification is pushed rather than visited and its payload is a
 * durable store, so putting the body here would copy the conversation into a second place that
 * `Guest::anonymize()` would then have to chase.
 */
class GuestMessageReceivedNotification extends FeedNotification
{
    public function __construct(private MessageThread $thread) {}

    public function eventKey(): string
    {
        return 'guest_message_received';
    }

    public function category(): string
    {
        return 'messages';
    }

    public function data(object $notifiable): array
    {
        $subject = trim((string) ($this->thread->subject ?? ''));

        return [
            'title' => $this->t('title', 'php/notifications/guest/guest_message_received_notification.title'),
            'context' => $subject !== '' ? $subject : $this->t('context', 'php/notifications/guest/guest_message_received_notification.context'),
            'url' => route('guest.messages.show', ['thread' => $this->thread->getKey()], false),
            'category' => 'messages',
        ];
    }
}
