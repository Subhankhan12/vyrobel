<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Notifications\FeedNotification;
use App\Services\TeamChatService;

/**
 * A teammate wrote to you in team chat (2026-10-06, `docs/TEAM-CHAT-DESIGN.md`). ONE bell entry per conversation while it
 * has unread messages — the dispatcher replaces the earlier one ("Max Brown sent you 2 messages") — and reading the
 * conversation reads it ({@see TeamChatService::markRead()}).
 *
 * ⚠️ IN-APP ONLY ({@see via()}): one email per chat message would be noise, and the catalogue is not asked — outside it,
 * NotificationPreferences::allows() fails OPEN and would email every message. ⚠️ It names the sender and the group,
 * never what was written: the text stays in `team_messages`.
 */
class TeamMessageNotification extends FeedNotification
{
    public function __construct(private string $conversationId, private string $title, private string $context) {}

    /** In-app only — never email (see the class docblock). */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function eventKey(): string
    {
        return 'team_message';   // not in the catalogue → unconditional, in-app only (as conversation_assigned)
    }

    public function category(): string
    {
        return 'messages';
    }

    public function data(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'context' => $this->context,
            'url' => route('app.inbox.team', ['c' => $this->conversationId], false),
            'category' => 'messages',
            'conversation_id' => $this->conversationId,
        ];
    }
}
