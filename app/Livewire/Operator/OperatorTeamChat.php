<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\TeamChatController;
use App\Models\MessageThread;
use App\Models\Owner;
use App\Models\TeamConversation;
use App\Models\TeamConversationMember;
use App\Models\User;
use App\Support\Messaging\ChatFiles;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Platform\BreakGlass;
use App\Support\TeamChat\TeamChatFiles;
use App\Support\TeamChat\TeamChatReader;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * TEAM CHAT — the page (2026-10-06, `docs/TEAM-CHAT-DESIGN.md`; his "messages must be owner and the employees as well").
 * Render-only, like the Inbox: it draws the first view (the person's conversations, the open one's latest messages) and
 * hands it to the page's script, which keeps it live through `app.inbox.team.poll` and writes through the JSON routes
 * of {@see TeamChatController}. It reads only the person's own conversations
 * ({@see TeamChatReader}); `?c=` of a conversation they are not in opens nothing.
 *
 * ⚠️ A GET NEVER WRITES: opening a conversation does not mark it read here — the page posts that once it is on screen
 * (a Vyrobel support session's GETs run read-only, and its view of a staff member must not move their read position).
 * ⚠️ A support (break-glass) session sees that team chat is private, and nothing of it.
 */
#[Layout('components.layouts.operator')]
class OperatorTeamChat extends Component
{
    public function render(): View
    {
        $request = request();
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $canGuests = $user->can('guests.view');
        $common = [
            'guestsLabel' => Owner::query()->exists() ? __('php/livewire/operator/operator_team_chat.guests_and_owners') : __('php/livewire/operator/operator_team_chat.guests'),
            'guestUnread' => $canGuests ? MessageThread::query()->where('unread_count', '>', 0)->where('status', '!=', 'closed')->count() : 0,
        ];

        if (BreakGlass::hasStash($request)) {
            return view('livewire.operator.team-chat', $common + ['support' => true, 'teamUnread' => 0, 'boot' => null]);
        }

        $reader = app(TeamChatReader::class);
        $conversations = $reader->conversations($user);
        $wanted = is_string($request->query('c')) ? strtolower($request->query('c')) : null;
        $open = $wanted !== null && TeamConversationMember::query()->where('conversation_id', $wanted)->where('user_id', $user->getKey())->exists()
            ? TeamConversation::query()->find($wanted)
            : null;
        $teammates = $reader->teammates($user);
        $unread = $reader->unreadTotal($user);

        return view('livewire.operator.team-chat', $common + [
            'support' => false,
            'teamUnread' => $unread,
            'boot' => [
                'me' => ['id' => (string) $user->getKey(), 'name' => (string) $user->name],
                'teammates' => $teammates,
                'conversations' => $conversations,
                'active' => $open?->getKey(),
                'messages' => $open !== null ? $reader->messages($open, $user) : [],
                'reads' => $open !== null ? $reader->reads($open, $user) : [],
                'unread' => $unread,
                'page' => TeamChatReader::PAGE,
                'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
                'csrf' => csrf_token(),
                'urls' => [
                    'page' => route('app.inbox.team'),
                    'poll' => route('app.inbox.team.poll'),
                    'start' => route('app.inbox.team.start'),
                    'send' => route('app.inbox.team.send', '__ID__'),
                    'read' => route('app.inbox.team.read', '__ID__'),
                    'roles' => $user->can('users.manage') ? route('app.roles') : null,
                ],
                'limits' => [
                    'files' => TeamChatFiles::MAX_FILES,
                    // this server's own ceilings — each file, and one message altogether (ChatFiles::limits())
                    'mb' => ChatFiles::limits()['mb'],
                    'post' => ChatFiles::limits()['post_bytes'],
                    'voice' => TeamChatFiles::MAX_VOICE_SECONDS,
                    'body' => 5000,
                    'accept' => '.'.str_replace(',', ',.', TeamChatFiles::FILE_EXTENSIONS),
                ],
            ],
        ]);
    }
}
