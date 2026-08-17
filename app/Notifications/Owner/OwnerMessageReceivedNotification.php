<?php

declare(strict_types=1);

namespace App\Notifications\Owner;

use App\Models\MessageThread;
use App\Notifications\FeedNotification;

/**
 * OWNER FIX 2 — the management team sent a message on one of THIS owner's threads.
 *
 * ⚠️ IT IS NOT "GUEST MESSAGE", AND THAT IS DELIBERATE. The wireframe's row is labelled "Guest message", but
 * OWNER↔GUEST MESSAGING DOES NOT EXIST in this product: a thread is `guest_id` XOR `owner_id`, and an owner's
 * threads are with their OPERATOR (the owner Booking-detail page says so in as many words — "guest contact
 * details are handled by your management team"). A notification called "Guest message" would advertise a
 * channel the owner does not have, so the type says what actually happened.
 *
 * ⚠️ FIRES ON `inbox.message_sent` ONLY — the OPERATOR's direction. The owner's own reply is recorded
 * `inbound` by InboxService::postOwnerMessage(), so an owner is never notified about their own message.
 *
 * ⚠️ #28 — the thread SUBJECT only, never the message body: the operator writes that text freely and it is
 * not a field this notification can vouch for.
 */
class OwnerMessageReceivedNotification extends FeedNotification
{
    public function __construct(private MessageThread $thread) {}

    public function eventKey(): string
    {
        return 'owner_message';
    }

    public function category(): string
    {
        return 'account';
    }

    public function data(object $notifiable): array
    {
        $subject = trim((string) ($this->thread->subject ?? ''));

        return [
            'title' => 'New message from your management team',
            'context' => $subject !== '' ? $subject : 'Open your inbox to read it.',
            'url' => route('owner.messages', [], false),
            'category' => 'account',
        ];
    }
}
