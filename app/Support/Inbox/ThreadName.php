<?php

declare(strict_types=1);

namespace App\Support\Inbox;

use App\Models\MessageThread;

/**
 * What a guest or owner conversation is called — the ONE rule, read by the Inbox (its list and its conversation) and by
 * the staff's bell when someone writes (2026-10-06), so the two always name a conversation the same way: the unit owner's
 * name, else the guest's, else the name its latest message came with. Name only (#28) — never a contact detail.
 */
final class ThreadName
{
    public static function for(MessageThread $thread): string
    {
        $owner = $thread->owner;
        if ($owner !== null && trim((string) $owner->name) !== '') {
            return (string) $owner->name;
        }

        $guest = $thread->guest;
        if ($guest !== null) {
            $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name));
            if ($name !== '') {
                return $name;
            }
        }

        // the Inbox loads only the latest message; anyone else asks for that one message — never every message the
        // conversation holds (the bell's handler used to load them all, and named it by whichever came first)
        $label = $thread->relationLoaded('messages')
            ? $thread->messages->first()?->sender_label
            : $thread->messages()->orderByDesc('created_at')->orderByDesc('id')->value('sender_label');

        return $label !== null && $label !== '' ? (string) $label : __('php/support/inbox/thread_name.unknown_sender');
    }
}
