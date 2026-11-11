<?php

declare(strict_types=1);

namespace App\Support\TeamChat;

use App\Models\TeamConversation;
use App\Models\TeamConversationMember;
use App\Models\TeamMessage;
use App\Models\TeamMessageReaction;
use App\Models\TeamTyping;
use App\Models\User;
use App\Services\InboxService;
use App\Services\TeamChatService;
use App\Support\I18n\ListJoin;
use App\Support\Schedule\StaffRoster;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

/**
 * TEAM CHAT — the ONE reader (2026-10-06). What a person's conversations are, what is unread, what was said and who has
 * seen it. The page's first draw and the live poll both read it, so the two can never disagree.
 *
 * ⚠️ EVERY QUESTION STARTS FROM THE PERSON'S OWN MEMBERSHIPS — a conversation they are not in does not exist here.
 * ⚠️ "Unread" counts other people's messages after the person's read position, by message id (ULIDs sort by the
 * millisecond; a timestamp is one second wide).
 */
final class TeamChatReader
{
    public const PAGE = 50;

    /**
     * How far the live poll looks back from its cursor. A message's id is made when it is written and its row is seen
     * when its transaction ends — one with files can be seen AFTER a newer one, behind the page's cursor. The poll
     * returns those seconds again and the page keeps each message once, by id (the independent review, 2026-10-06).
     */
    public const LOOK_BACK_SECONDS = 30;

    public function __construct(private TeamChatFiles $files, private StaffRoster $roster) {}

    /**
     * The person's conversations, latest first, each with its unread count and last message.
     *
     * @return list<array<string, mixed>>
     */
    public function conversations(User $viewer): array
    {
        $memberships = TeamConversationMember::query()->where('user_id', $viewer->getKey())->get()->keyBy('conversation_id');
        if ($memberships->isEmpty()) {
            return [];
        }
        $ids = $memberships->keys()->all();

        $conversations = TeamConversation::query()->whereIn('id', $ids)
            ->with(['users' => fn ($q) => $q->select('users.id', 'users.name')])
            ->get();
        $last = TeamMessage::query()
            ->whereIn('id', TeamMessage::query()->selectRaw('max(id)')->whereIn('conversation_id', $ids)->groupBy('conversation_id'))
            ->with('media')->get()->keyBy('conversation_id');
        $unread = $this->unreadByConversation($viewer, $memberships);

        return $conversations
            ->sortByDesc(fn (TeamConversation $c): string => (string) ($c->last_message_at ?? $c->created_at)?->format('Y-m-d H:i:s.u').$c->getKey())
            ->map(fn (TeamConversation $c): array => $this->conversationRow($c, $viewer, $last->get($c->getKey()), (int) ($unread[$c->getKey()] ?? 0)))
            ->values()->all();
    }

    /** Everything unread across the person's conversations. */
    public function unreadTotal(User $viewer): int
    {
        $memberships = TeamConversationMember::query()->where('user_id', $viewer->getKey())->get()->keyBy('conversation_id');

        return $memberships->isEmpty() ? 0 : (int) array_sum($this->unreadByConversation($viewer, $memberships));
    }

    /** How many of other people's messages in this conversation the person has not read. */
    public function unreadIn(TeamConversation $conversation, User $viewer): int
    {
        $member = TeamConversationMember::query()->where('conversation_id', $conversation->getKey())->where('user_id', $viewer->getKey())->first();
        if (! $member instanceof TeamConversationMember) {
            return 0;
        }

        return TeamMessage::query()->where('conversation_id', $conversation->getKey())
            ->whereNull('deleted_at')   // a deleted message is nothing to read
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $viewer->getKey()))
            ->when($member->last_read_message_id !== null, fn ($q) => $q->where('id', '>', $member->last_read_message_id))
            ->count();
    }

    /**
     * Messages in one of the person's conversations: the latest page, those after a message (the live poll — with
     * {@see LOOK_BACK_SECONDS} before it again), or those before one ("Load earlier messages"). Oldest first.
     *
     * ⚠️ THE POLL ALSO ASKS WHAT CHANGED (D-TC-LATER, 2026-11-11): `$since` is the server's own clock at the poll before
     * (epoch seconds — never the browser's). A message edited, deleted, reacted to or scrubbed since then is returned AGAIN,
     * whatever its id, because each of those writes bumps `updated_at`; the page replaces the copy it holds. ONE mechanism
     * delivers all four, and the same {@see LOOK_BACK_SECONDS} covers a write whose second was not yet visible.
     *
     * @return list<array<string, mixed>>
     */
    public function messages(TeamConversation $conversation, User $viewer, ?string $after = null, ?string $before = null, ?int $since = null): array
    {
        $query = TeamMessage::query()->where('conversation_id', $conversation->getKey())
            ->with(['media', 'sender:id,name', 'reactions.user:id,name']);

        if ($after !== null) {
            $changedFrom = $since !== null ? Carbon::createFromTimestamp($since - self::LOOK_BACK_SECONDS)->setTimezone((string) config('app.timezone')) : null;
            $rows = $query->where(function ($q) use ($after, $changedFrom): void {
                $q->where('id', '>', self::lookBackFrom($after));
                if ($changedFrom !== null) {
                    $q->orWhere('updated_at', '>=', $changedFrom);
                }
            })->orderBy('id')->limit(200)->get();
        } else {
            $rows = $query->when($before !== null, fn ($q) => $q->where('id', '<', $before))
                ->orderByDesc('id')->limit(self::PAGE)->get()->reverse()->values();
        }

        return $rows->map(fn (TeamMessage $m): array => $this->messageRow($m, $viewer))->values()->all();
    }

    /** The smallest message id written {@see LOOK_BACK_SECONDS} before the cursor's own moment (a ULID's first ten characters are its time). */
    public static function lookBackFrom(string $cursor): string
    {
        $moment = Ulid::fromString($cursor)->getDateTime()->sub(new \DateInterval('PT'.self::LOOK_BACK_SECONDS.'S'));

        return strtolower(substr(Ulid::generate($moment), 0, 10)).str_repeat('0', 16);
    }

    /**
     * How far each OTHER member has read — the sender's "Seen".
     *
     * @return list<array{user_id: string, name: string, message_id: string|null, at: string|null}>
     */
    public function reads(TeamConversation $conversation, User $viewer): array
    {
        return TeamConversationMember::query()->where('conversation_id', $conversation->getKey())
            ->where('user_id', '!=', $viewer->getKey())->with('user:id,name')->get()
            ->map(fn (TeamConversationMember $m): array => [
                'user_id' => (string) $m->user_id,
                'name' => (string) ($m->user->name ?? __('php/support/team_chat/team_chat_reader.former_teammate')),
                'message_id' => $m->last_read_message_id,
                'at' => $m->last_read_at?->toIso8601String(),
            ])->values()->all();
    }

    /**
     * Who else is typing in this conversation right now — people whose "typing" was posted under
     * {@see TeamChatService::TYPING_SECONDS} seconds ago, never the viewer. Read-only: a GET never writes.
     *
     * @return list<array{id: string, name: string}>
     */
    public function typing(TeamConversation $conversation, User $viewer): array
    {
        return TeamTyping::query()->where('conversation_id', $conversation->getKey())
            ->where('user_id', '!=', $viewer->getKey())
            ->where('typed_at', '>=', now()->subSeconds(TeamChatService::TYPING_SECONDS))
            ->with('user:id,name')->orderBy('typed_at')->get()
            ->map(fn (TeamTyping $t): array => ['id' => (string) $t->user_id, 'name' => (string) ($t->user->name ?? __('php/support/team_chat/team_chat_reader.former_teammate'))])
            ->values()->all();
    }

    /** A request within this many seconds is "online now". */
    public const ONLINE_SECONDS = 90;

    /**
     * Which of the people the viewer talks to are online NOW — a request from them within {@see ONLINE_SECONDS}. Read from
     * the `sessions` table (every request of a signed-in person refreshes its `last_activity`), so it exists ONLY while
     * sessions live in the database; on any other driver this returns null and the page shows nothing rather than a guess.
     * ⚠️ Only members of the viewer's OWN conversations are ever named — presence is not a directory of the workspace.
     * ⚠️ It is "has a page open and recently asked the server", not "is looking at the screen": a tab left open in the
     * background polls less often but still counts.
     *
     * @return list<string>|null user ids; null = presence is not available here
     */
    public function online(User $viewer): ?array
    {
        if (config('session.driver') !== 'database') {
            return null;
        }
        $mine = TeamConversationMember::query()->where('user_id', $viewer->getKey())->pluck('conversation_id');
        if ($mine->isEmpty()) {
            return [];
        }
        $others = TeamConversationMember::query()->whereIn('conversation_id', $mine)->where('user_id', '!=', $viewer->getKey())->pluck('user_id')->unique();
        if ($others->isEmpty()) {
            return [];
        }

        return DB::table('sessions')->whereIn('user_id', $others->all())
            ->where('last_activity', '>=', now()->getTimestamp() - self::ONLINE_SECONDS)
            ->distinct()->pluck('user_id')->map(fn ($id): string => (string) $id)->values()->all();
    }

    /** How many results a search returns — the newest first. */
    public const SEARCH_LIMIT = 30;

    /** The shortest search that is run (one letter would match nearly everything). */
    public const SEARCH_MIN = 2;

    /**
     * Search ONE conversation's messages — newest first, at most {@see SEARCH_LIMIT} — with how many matched in all.
     * A deleted message is nothing to find. The `%` and `_` the person types are literal characters, not patterns.
     *
     * @return array{results: list<array<string, mixed>>, total: int}
     */
    public function search(TeamConversation $conversation, User $viewer, string $term): array
    {
        $term = trim($term);
        $base = TeamMessage::query()->where('conversation_id', $conversation->getKey())
            ->whereNull('deleted_at')->whereNotNull('body')
            ->where('body', 'like', '%'.addcslashes($term, '%_\\').'%');
        $total = (clone $base)->count();
        $rows = $base->with('sender:id,name')->orderByDesc('id')->limit(self::SEARCH_LIMIT)->get();

        return [
            'results' => $rows->map(fn (TeamMessage $m): array => [
                'id' => (string) $m->getKey(),
                'snippet' => $this->snippet((string) $m->body, $term),
                'mine' => $m->user_id !== null && $m->user_id === (string) $viewer->getKey(),
                'sender' => (string) ($m->sender->name ?? __('php/support/team_chat/team_chat_reader.former_teammate')),
                'at' => $m->created_at->toIso8601String(),
            ])->values()->all(),
            'total' => $total,
        ];
    }

    /** About 140 characters of the message around the first match (or its start), on one line. */
    private function snippet(string $body, string $term): string
    {
        $flat = trim((string) preg_replace('/\s+/u', ' ', $body));
        $at = mb_stripos($flat, $term);
        $from = $at === false ? 0 : max(0, $at - 40);
        $cut = mb_substr($flat, $from, 140);

        return ($from > 0 ? '…' : '').$cut.($from + 140 < mb_strlen($flat) ? '…' : '');
    }

    /**
     * Who the person can start a conversation with: the workspace's back-office staff, themselves excepted.
     *
     * @return list<array{id: string, name: string, initials: string, role: string}>
     */
    public function teammates(User $viewer): array
    {
        $out = [];
        foreach ($this->roster->staff() as $person) {
            if ($person['id'] === (string) $viewer->getKey()) {
                continue;
            }
            $out[] = ['id' => $person['id'], 'name' => $person['name'], 'initials' => $person['initials'], 'role' => $person['role_label']];
        }

        return $out;
    }

    /**
     * One message as the page shows it.
     *
     * @return array<string, mixed>
     */
    public function messageRow(TeamMessage $m, User $viewer): array
    {
        $sender = $m->sender;
        $deleted = $m->deleted_at !== null;
        $mine = $m->user_id !== null && $m->user_id === (string) $viewer->getKey();

        return [
            'id' => (string) $m->getKey(),
            'body' => $deleted ? '' : (string) ($m->body ?? ''),
            'mine' => $mine,
            'deleted' => $deleted,
            'edited' => ! $deleted && $m->edited_at !== null,
            'redacted' => ! $deleted && $m->redacted_at !== null,
            // until when the author may still change the words (the server decides; this only hides the button)
            'editable_until' => $mine && ! $deleted ? $m->created_at->copy()->addMinutes(TeamChatService::EDIT_WINDOW_MINUTES)->toIso8601String() : null,
            'reactions' => $deleted ? [] : $this->reactionRows($m, $viewer),
            'sender' => $sender instanceof User
                ? ['id' => (string) $sender->getKey(), 'name' => (string) $sender->name, 'initials' => StaffRoster::initials((string) $sender->name)]
                : ['id' => null, 'name' => __('php/support/team_chat/team_chat_reader.former_teammate'), 'initials' => '?'],
            'at' => $m->created_at->toIso8601String(),
            'files' => $deleted ? [] : $this->files->rows($m),
        ];
    }

    /**
     * A message's reactions, grouped by emoji in the allow-list's order: how many, whether YOU gave it, and who.
     *
     * @return list<array{emoji: string, count: int, mine: bool, names: list<string>}>
     */
    private function reactionRows(TeamMessage $m, User $viewer): array
    {
        $byEmoji = $m->reactions->groupBy('emoji');
        $out = [];
        foreach (InboxService::REACTIONS as $emoji) {
            $group = $byEmoji->get($emoji);
            if ($group === null || $group->isEmpty()) {
                continue;
            }
            $out[] = [
                'emoji' => $emoji,
                'count' => $group->count(),
                'mine' => $group->contains(fn (TeamMessageReaction $r): bool => (string) $r->user_id === (string) $viewer->getKey()),
                'names' => $group->map(fn (TeamMessageReaction $r): string => (string) ($r->user->name ?? __('php/support/team_chat/team_chat_reader.former_teammate')))->values()->all(),
            ];
        }

        return $out;
    }

    /**
     * @param  Collection<string, TeamConversationMember>  $memberships
     * @return array<string, int>
     */
    private function unreadByConversation(User $viewer, Collection $memberships): array
    {
        $counts = [];
        foreach ($memberships as $conversationId => $member) {
            $counts[(string) $conversationId] = 0;
        }

        // one grouped query: other people's messages after each of the person's read positions
        $rows = TeamMessage::query()
            ->join('team_conversation_members as m', function ($join) use ($viewer): void {
                $join->on('m.conversation_id', '=', 'team_messages.conversation_id')->where('m.user_id', '=', $viewer->getKey());
            })
            ->whereNull('team_messages.deleted_at')   // a deleted message is nothing to read
            ->where(fn ($q) => $q->whereNull('team_messages.user_id')->orWhere('team_messages.user_id', '!=', $viewer->getKey()))
            ->where(fn ($q) => $q->whereNull('m.last_read_message_id')->orWhereColumn('team_messages.id', '>', 'm.last_read_message_id'))
            ->groupBy('team_messages.conversation_id')
            ->selectRaw('team_messages.conversation_id as cid, count(*) as n')
            ->pluck('n', 'cid');
        foreach ($rows as $cid => $n) {
            $counts[(string) $cid] = (int) $n;
        }

        return $counts;
    }

    /** @return array<string, mixed> */
    private function conversationRow(TeamConversation $c, User $viewer, ?TeamMessage $last, int $unread): array
    {
        $others = $c->users->filter(fn (User $u): bool => (string) $u->getKey() !== (string) $viewer->getKey())->values();
        $members = $c->users->map(fn (User $u): array => [
            'id' => (string) $u->getKey(), 'name' => (string) $u->name, 'initials' => StaffRoster::initials((string) $u->name),
        ])->values()->all();

        $name = $this->displayName($c, $viewer);

        return [
            'id' => (string) $c->getKey(),
            'kind' => $c->kind,
            'name' => $name,
            'title' => $c->title,
            'initials' => $c->kind === TeamConversation::DIRECT ? StaffRoster::initials($name) : '',
            'members' => $members,
            'unread' => $unread,
            'last' => $last instanceof TeamMessage ? [
                'text' => $this->preview($last),
                'at' => $last->created_at->toIso8601String(),
                'mine' => $last->user_id === (string) $viewer->getKey(),
            ] : null,
            'url' => route('app.inbox.team', ['c' => (string) $c->getKey()]),
        ];
    }

    /**
     * What a conversation is called for this person: the other person in a direct one; a group's name, or the first
     * names of the others in it.
     */
    public function displayName(TeamConversation $c, User $viewer): string
    {
        $others = $c->users->filter(fn (User $u): bool => (string) $u->getKey() !== (string) $viewer->getKey())->values();

        return $c->kind === TeamConversation::DIRECT
            ? (string) ($others->first()->name ?? __('php/support/team_chat/team_chat_reader.former_teammate'))
            : ($c->title ?: $this->groupName($others));
    }

    /** @param Collection<int, User> $others */
    private function groupName(Collection $others): string
    {
        $first = $others->map(fn (User $u): string => (string) strtok((string) $u->name, ' '))->values();

        return match (true) {
            $first->count() <= 3 => ListJoin::join($first),
            default => trans_choice('php/support/team_chat/team_chat_reader.group_name_others', $first->count() - 2, ['names' => ListJoin::join($first->take(2)), 'count' => $first->count() - 2]),
        };
    }

    /** One line for the list: the text, or what was sent instead of it. */
    private function preview(TeamMessage $m): string
    {
        if ($m->deleted_at !== null) {
            return __('php/support/team_chat/team_chat_reader.preview_deleted');
        }
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $m->body));
        if ($text !== '') {
            return mb_strlen($text) > 90 ? mb_substr($text, 0, 89).'…' : $text;
        }
        $kinds = collect($this->files->rows($m))->pluck('kind');

        return match (true) {
            $kinds->contains('voice') => __('php/support/team_chat/team_chat_reader.preview_voice'),
            $kinds->isNotEmpty() && $kinds->every(fn (string $k): bool => $k === 'image') => trans_choice('php/support/team_chat/team_chat_reader.preview_photos', $kinds->count(), ['count' => $kinds->count()]),
            $kinds->isNotEmpty() => trans_choice('php/support/team_chat/team_chat_reader.preview_files', $kinds->count(), ['count' => $kinds->count()]),
            default => '',
        };
    }
}
