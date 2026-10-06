<?php

declare(strict_types=1);

namespace App\Support\TeamChat;

use App\Models\TeamConversation;
use App\Models\TeamConversationMember;
use App\Models\TeamMessage;
use App\Models\User;
use App\Support\Schedule\StaffRoster;
use Illuminate\Support\Collection;
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
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $viewer->getKey()))
            ->when($member->last_read_message_id !== null, fn ($q) => $q->where('id', '>', $member->last_read_message_id))
            ->count();
    }

    /**
     * Messages in one of the person's conversations: the latest page, those after a message (the live poll — with
     * {@see LOOK_BACK_SECONDS} before it again), or those before one ("Load earlier messages"). Oldest first.
     *
     * @return list<array<string, mixed>>
     */
    public function messages(TeamConversation $conversation, User $viewer, ?string $after = null, ?string $before = null): array
    {
        $query = TeamMessage::query()->where('conversation_id', $conversation->getKey())->with(['media', 'sender:id,name']);

        if ($after !== null) {
            $rows = $query->where('id', '>', self::lookBackFrom($after))->orderBy('id')->limit(200)->get();
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
                'name' => (string) ($m->user->name ?? 'Former teammate'),
                'message_id' => $m->last_read_message_id,
                'at' => $m->last_read_at?->toIso8601String(),
            ])->values()->all();
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

        return [
            'id' => (string) $m->getKey(),
            'body' => (string) ($m->body ?? ''),
            'mine' => $m->user_id !== null && $m->user_id === (string) $viewer->getKey(),
            'sender' => $sender instanceof User
                ? ['id' => (string) $sender->getKey(), 'name' => (string) $sender->name, 'initials' => StaffRoster::initials((string) $sender->name)]
                : ['id' => null, 'name' => 'Former teammate', 'initials' => '?'],
            'at' => $m->created_at->toIso8601String(),
            'files' => $this->files->rows($m),
        ];
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
            ? (string) ($others->first()->name ?? 'Former teammate')
            : ($c->title ?: $this->groupName($others));
    }

    /** @param Collection<int, User> $others */
    private function groupName(Collection $others): string
    {
        $first = $others->map(fn (User $u): string => (string) strtok((string) $u->name, ' '))->values();

        return match (true) {
            $first->count() <= 3 => $first->implode(', '),
            default => $first->take(2)->implode(', ').' and '.($first->count() - 2).' others',
        };
    }

    /** One line for the list: the text, or what was sent instead of it. */
    private function preview(TeamMessage $m): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $m->body));
        if ($text !== '') {
            return mb_strlen($text) > 90 ? mb_substr($text, 0, 89).'…' : $text;
        }
        $kinds = collect($this->files->rows($m))->pluck('kind');

        return match (true) {
            $kinds->contains('voice') => 'Voice message',
            $kinds->isNotEmpty() && $kinds->every(fn (string $k): bool => $k === 'image') => $kinds->count() > 1 ? $kinds->count().' photos' : 'Photo',
            $kinds->isNotEmpty() => $kinds->count() > 1 ? $kinds->count().' files' : 'File',
            default => '',
        };
    }
}
