<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerMessageActionController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Media;
use App\Models\Membership;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Scopes\OwnerScope;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Authorization\RoleLabel;
use App\Support\I18n\LocalDate;
use App\Support\Inbox\MessageAttachments;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner portal · MESSAGES — the owner's conversations with their operator's staff (Wave 3). READ-ONLY here:
 * the reply is a real POST through {@see OwnerMessageActionController} →
 * {@see InboxService::postOwnerMessage()}.
 *
 * ⚠️ OWNER-SCOPED, AND THE ISOLATION IS NOT THIS FILE'S DOING. {@see MessageThread} gained `owner_id` +
 * `BelongsToOwner` in Wave 3, so {@see OwnerScope} constrains every read below to `owner_id IN (their ids)`
 * automatically. Two consequences worth stating, because both are load-bearing:
 *   • A guest thread has `owner_id = NULL`, so an operator↔guest conversation is excluded **without anyone
 *     writing a predicate** — the two-inbox boundary falls out of the scope.
 *   • A portal user linked to no owner gets `1 = 0` — fail-closed, never all-owners.
 * This file therefore adds NO `->where('owner_id', …)` of its own: writing one would imply the scope were
 * optional. `OwnerInboxIsolationTest` proves it symmetrically (both owners, both directions) and
 * mutation-proves the trait.
 *
 * ⚠️ THE TRANSCRIPT IS MIRRORED, AND THAT IS THE MODEL BEING RIGHT, NOT A HACK. `messages.direction` is
 * ORG-RELATIVE: `inbound` = into the organization. So on an owner thread the OWNER's own messages are the
 * `inbound` ones and the staff replies are `outbound` — the exact mirror of the operator inbox, reading the
 * same rows. One column, two correct perspectives, no per-surface duplicate.
 *
 * Honesty triage (invariant #19; standing order = OMIT AGGRESSIVELY). The design is ~1/3 fabricated and a
 * third of what it shows cannot be known by this app:
 *   • REAL     — the owner's threads; the staff correspondent (`messages.author_user_id` → the user's real
 *                name, and their real back-office role); message bodies + timestamps; the unread state (see
 *                below); the last-message preview.
 *   • DERIVED  — the unread badge/filter/count, from `owner_last_read_at` vs later `outbound` messages; the
 *                relative timestamps; the day separators; initials.
 *   • OMITTED (no backend — absent, not greyed out):
 *       – "✓✓ READ" per-message receipts. `messages.delivery_status` is exactly
 *         received/queued/sent/delivered/failed — **there is no `read` value**. The design invents one AND
 *         defaults every seeded message to it, asserting the correspondent has read everything the owner
 *         ever sent. (It also omits `failed`, the one real state the operator inbox honestly surfaces.)
 *       – PRESENCE DOTS. No presence exists anywhere: `users` has `last_login_at`, a login timestamp, not a
 *         heartbeat. There is no websocket layer.
 *       – "USUALLY REPLIES WITHIN AN HOUR". Not computed — the design picks between two strings using the
 *         fabricated presence boolean. There is no first-response metric and no SLA. Rendering it would make
 *         a response-time PROMISE on behalf of staff that the app cannot measure, let alone keep. Same call
 *         as Wave 1's stub ADR/RevPAR.
 *       – The TYPING INDICATOR (a setTimeout on a fake bot), STARS + REACTIONS (localStorage — per-browser,
 *         invisible to the operator), the PIN (persists nowhere at all, not even localStorage),
 *         ATTACHMENTS (blob: URLs that die on reload; the real `media`/`documents` polymorphs exist but
 *         wiring them is its own gate), and the CANNED AUTO-REPLY BOT — which would demo this as a live,
 *         staffed, two-way system with presence and receipts. None of that exists.
 *       – "VYROBEL SUPPORT" / "VYROBEL BILLING". These are the SaaS VENDOR, not the operator — a
 *         cross-tenant helpdesk cannot live in an `organization_id`-scoped table. A separate product.
 *       – "MAINTENANCE TEAM" as a correspondent: a team is not a user, so there is nobody to address or
 *         attribute. The Maintenance module already exists and is where repair conversations belong.
 *   • SOON     — nothing. Every control here does something real.
 *
 * Scope-dependent reads ride GET links (`?filter=`, `?q=`), never wire-actions: a wire-action re-renders over
 * /livewire/update and bypasses the middleware that establishes the owner context (invariant #20). No public
 * props, so the prop/view-key shadowing trap cannot arise.
 */
#[Layout('components.layouts.owner')]
class OwnerMessages extends Component
{
    use FormatsOwnerData;

    /** Bound the search term: a LIKE pattern is not a place to accept unbounded user input. */
    private const MAX_QUERY = 100;

    public function render(): View
    {
        $filter = $this->resolveFilter();
        $search = $this->resolveQuery();

        // Mark the opened thread read FIRST — an EXPLICIT open (?c=…), never the default preview — so the rail's
        // per-row badge AND the unread count computed just below reflect it on THIS request, not one navigation
        // late. The explicit id comes straight from the query (owner-scoped find → cross-owner/guest = null), so
        // this needs no $rows. Only owner_last_read_at moves (never the operator's counter).
        $explicitId = is_string(request()->query('c')) && request()->query('c') !== '' ? request()->query('c') : null;
        $explicitThread = $explicitId !== null ? MessageThread::query()->find($explicitId) : null;
        if ($explicitThread !== null) {
            app(InboxService::class)->markReadByOwner($explicitThread);
        }

        $rows = $this->rows($filter, $search);

        // The owner's TRUE total, ignoring filter + search — it distinguishes "no conversations" from "your
        // filter matched none", which are different empty states with different advice.
        $total = MessageThread::query()->count();
        $unread = $this->unreadThreadCount();

        // The 3-pane layout (exact-match): the conversation rail + the ACTIVE thread pane. The active thread is
        // ?c={id} (an explicit open) or the filtered-list default; ?compose={staffId} switches to a NEW conversation.
        $staff = $this->staffDirectory();
        $composeStaff = $this->resolveComposeStaff($staff);
        $active = null;
        $messages = [];
        $pinned = null;
        $starred = [];
        $viewerId = (string) (auth()->id() ?? '');
        if ($composeStaff === null) {
            // ?c wins; otherwise default the pane to the first thread IN THE FILTERED/SEARCHED list (never a
            // globally-latest thread the current filter hid) — so the pane and the rail always agree.
            $activeThread = $explicitThread ?? $this->resolveActiveThread($rows[0]['id'] ?? null);
            if ($activeThread !== null) {
                // Eager-load the rich relations in one pass (reactions/stars/media/quoted-parent) — no N+1.
                $msgs = Message::query()->where('thread_id', $activeThread->getKey())
                    ->with(['author', 'reactions', 'stars', 'media', 'replyTo.author'])
                    ->orderBy('created_at')->orderBy('id')->get();
                $active = $this->activeCard($activeThread);
                $messages = $this->transcript($msgs, $activeThread, $viewerId);
                $pinned = $this->pinnedCard($activeThread, $msgs);
                $starred = $this->starredList($msgs, $viewerId);
            }
        }

        return view('livewire.owner.messages', [
            'viewer' => $this->viewer(),
            'rows' => $rows,
            'total' => $total,
            'unread' => $unread,
            'filter' => $filter,
            'query' => $search,
            'subtitle' => $this->subtitle($total, $unread),
            'staff' => $staff,
            'active' => $active,
            'messages' => $messages,
            'pinned' => $pinned,
            'starred' => $starred,
            'reactionPalette' => InboxService::REACTIONS,
            'composeStaff' => $composeStaff,
            'quickReplies' => [__('php/livewire/owner/owner_messages.quick_reply_payout'), __('php/livewire/owner/owner_messages.quick_reply_block_dates'), __('php/livewire/owner/owner_messages.quick_reply_tax_summary')],
        ]);
    }

    // ── The active thread pane (unified 3-pane) ──────────────────────────────────────────────────

    /** The thread to show: ?c={id} (owner-scoped → cross-owner/guest 404s to null), else the filtered-list default. */
    private function resolveActiveThread(?string $defaultId): ?MessageThread
    {
        $param = request()->query('c');
        $id = is_string($param) && $param !== '' ? $param : $defaultId;

        return $id !== null ? MessageThread::query()->find($id) : null;   // owner-scoped; null if not the owner's
    }

    /**
     * ?compose={staffId} → the picked back-office staff (a NEW conversation), or null. NEVER a guest — the id
     * must match a real back-office member of this org.
     *
     * @param  list<array{id: string, name: string, role: string, initials: string}>  $staff
     * @return array{id: string, name: string, role: string, initials: string}|null
     */
    private function resolveComposeStaff(array $staff): ?array
    {
        $param = request()->query('compose');
        if (! is_string($param) || $param === '') {
            return null;
        }

        foreach ($staff as $member) {
            if ($member['id'] === $param) {
                return $member;
            }
        }

        return null;
    }

    /**
     * The active thread's header card — the REAL staff correspondent (name + back-office role), never a guest.
     *
     * @return array{id: string, name: string, role: string, initials: string, first_name: string, subject: string|null}
     */
    private function activeCard(MessageThread $thread): array
    {
        $correspondent = $this->correspondent($thread);

        return [
            'id' => (string) $thread->getKey(),
            'name' => $correspondent['name'],
            'role' => $correspondent['role'],
            'initials' => $this->initials($correspondent['name']),
            'first_name' => explode(' ', $correspondent['name'])[0],
            'subject' => $thread->subject,
        ];
    }

    /**
     * The transcript, MIRRORED for the owner (inbound = the owner's own message) with day separators + the
     * honest failed state, plus the GATE 2 rich data per message: the quoted reply-to, attachments, grouped
     * reactions (with the viewer's own tap flagged), the viewer's star, whether it is the pinned message, and
     * the REAL read receipt on the owner's own messages (see {@see receiptCode()}).
     *
     * @param  Collection<int, Message>  $messages
     * @return list<array<string, mixed>>
     */
    private function transcript($messages, MessageThread $thread, string $viewerId): array
    {
        $rows = [];
        $lastDay = null;
        $pinnedId = (string) ($thread->pinned_message_id ?? '');

        foreach ($messages as $message) {
            $at = $message->created_at !== null ? CarbonImmutable::parse($message->created_at) : null;
            $day = $at?->toDateString();
            $mine = $message->direction === 'inbound';   // MIRRORED: inbound = into the org = the OWNER
            $receiptCode = $mine && $message->delivery_status !== 'failed' ? $this->receiptCode($message, $thread) : null;

            $rows[] = [
                'id' => (string) $message->getKey(),
                'mine' => $mine,
                'body' => (string) $message->body,
                'author' => $mine
                    ? __('php/livewire/owner/owner_messages.you')
                    : ($message->author instanceof User ? (string) $message->author->name : ((string) $message->sender_label ?: __('php/livewire/owner/owner_messages.management_team'))),
                'time' => $at?->format('H:i') ?? '',
                'meta' => __('php/livewire/owner/owner_messages.meta', [
                    'author' => $mine ? __('php/livewire/owner/owner_messages.you') : ($message->author instanceof User ? explode(' ', (string) $message->author->name)[0] : __('php/livewire/owner/owner_messages.team_author')),
                    'time' => $at?->translatedFormat(LocalDate::pattern('j M, H:i')) ?? '',
                ]),
                'day_label' => $day !== $lastDay && $at !== null ? $this->dayLabel($at) : null,
                'failed' => $mine && $message->delivery_status === 'failed',
                'reply_to' => $this->quoted($message),
                'attachments' => $this->attachmentList($message),
                'reactions' => $this->reactionSummary($message, $viewerId),
                'starred' => $message->stars->contains(fn ($s): bool => (string) $s->user_id === $viewerId),
                'pinned' => (string) $message->getKey() === $pinnedId,
                // The REAL receipt (never a fabricated ✓✓) — only on the owner's OWN, non-failed messages.
                // `receipt_code` ('read' | 'delivered') is what the view compares; `receipt` is only the words shown.
                'receipt_code' => $receiptCode,
                'receipt' => match ($receiptCode) {
                    'read' => __('php/livewire/owner/owner_messages.receipt_read'),
                    'delivered' => __('php/livewire/owner/owner_messages.receipt_delivered'),
                    default => null,
                },
            ];

            if ($at !== null) {
                $lastDay = $day;
            }
        }

        return $rows;
    }

    /**
     * The quoted parent of a reply (GATE 2), or null. NAME + a short snippet — the same mirror rule (the
     * owner's own quoted message reads "You").
     *
     * @return array{author: string, snippet: string}|null
     */
    private function quoted(Message $message): ?array
    {
        $parent = $message->replyTo;
        if (! $parent instanceof Message) {
            return null;
        }

        $author = $parent->direction === 'inbound'
            ? __('php/livewire/owner/owner_messages.you')
            : ($parent->author instanceof User ? explode(' ', (string) $parent->author->name)[0] : __('php/livewire/owner/owner_messages.team_author'));

        return ['author' => $author, 'snippet' => Str::limit((string) $parent->body, 100)];
    }

    /**
     * A message's attachments (GATE 2) — images render inline; everything else is a download chip with its original name +
     * a human size. ⚠️ Each links to the owner portal's SIGNED-IN route (DEC-m, 2026-09-30), never a public address — the
     * rows come from the one definition the operator inbox shares ({@see MessageAttachments::rows()}).
     *
     * @return list<array{url: string, name: string, is_image: bool, size: string}>
     */
    private function attachmentList(Message $message): array
    {
        return app(MessageAttachments::class)->rows($message, 'owner.messages.attachment');
    }

    /**
     * Reactions grouped by emoji (GATE 2), each with a count and whether the VIEWER is one of them (so the UI
     * highlights the owner's own tap).
     *
     * @return list<array{emoji: string, count: int, mine: bool}>
     */
    private function reactionSummary(Message $message, string $viewerId): array
    {
        $grouped = [];
        foreach ($message->reactions as $reaction) {
            $emoji = InboxService::reactionEmoji((string) $reaction->emoji);   // what the row stands for (a stored key, or an older row's emoji)
            $grouped[$emoji] ??= ['emoji' => $emoji, 'count' => 0, 'mine' => false];
            $grouped[$emoji]['count']++;
            if ((string) $reaction->user_id === $viewerId) {
                $grouped[$emoji]['mine'] = true;
            }
        }

        return array_values($grouped);
    }

    /**
     * The owner's message read state (GATE 2) — REAL, from `operator_last_read_at` (the mirror of the owner's
     * own read position). No `read` delivery_status is faked: the message is "Read" once the operator's read
     * position is at/after it, else "Delivered" (it is recorded the instant it is posted).
     */
    private function receiptCode(Message $message, MessageThread $thread): string
    {
        $readAt = $thread->operator_last_read_at;
        $sentAt = $message->created_at;

        if ($readAt !== null && $sentAt !== null && $readAt->greaterThanOrEqualTo($sentAt)) {
            return 'read';
        }

        return 'delivered';
    }

    /**
     * The thread's pinned message (GATE 2) as a banner card, or null. Resolved from the eager-loaded set so it
     * costs no extra query; a pin pointing at a vanished message simply yields null (honest — no ghost banner).
     *
     * @param  Collection<int, Message>  $messages
     * @return array{id: string, author: string, snippet: string}|null
     */
    private function pinnedCard(MessageThread $thread, $messages): ?array
    {
        $pinnedId = (string) ($thread->pinned_message_id ?? '');
        if ($pinnedId === '') {
            return null;
        }

        $pinned = $messages->first(fn (Message $m): bool => (string) $m->getKey() === $pinnedId);
        if (! $pinned instanceof Message) {
            return null;
        }

        $author = $pinned->direction === 'inbound'
            ? __('php/livewire/owner/owner_messages.you')
            : ($pinned->author instanceof User ? explode(' ', (string) $pinned->author->name)[0] : __('php/livewire/owner/owner_messages.team_author'));

        return ['id' => $pinnedId, 'author' => $author, 'snippet' => Str::limit((string) $pinned->body, 140)];
    }

    /**
     * The viewer's starred messages on this thread (GATE 2) — private view-state, only the owner's own stars.
     *
     * @param  Collection<int, Message>  $messages
     * @return list<array{id: string, author: string, snippet: string, time: string}>
     */
    private function starredList($messages, string $viewerId): array
    {
        return $messages
            ->filter(fn (Message $m): bool => $m->stars->contains(fn ($s): bool => (string) $s->user_id === $viewerId))
            ->map(function (Message $m): array {
                $at = $m->created_at !== null ? CarbonImmutable::parse($m->created_at) : null;
                $author = $m->direction === 'inbound'
                    ? __('php/livewire/owner/owner_messages.you')
                    : ($m->author instanceof User ? explode(' ', (string) $m->author->name)[0] : __('php/livewire/owner/owner_messages.team_author'));

                return [
                    'id' => (string) $m->getKey(),
                    'author' => $author,
                    'snippet' => Str::limit((string) $m->body, 90),
                    'time' => $at?->translatedFormat(LocalDate::pattern('j M')) ?? '',
                ];
            })
            ->values()
            ->all();
    }

    /** "Today" / "Yesterday" / "12 September" — the thread's day separator. */
    private function dayLabel(CarbonImmutable $at): string
    {
        $today = CarbonImmutable::today();
        if ($at->isSameDay($today)) {
            return __('php/livewire/owner/owner_messages.today');
        }
        if ($at->isSameDay($today->subDay())) {
            return __('php/livewire/owner/owner_messages.yesterday');
        }

        return $at->translatedFormat(LocalDate::pattern('j F')).($at->year !== $today->year ? ' '.$at->year : '');
    }

    // ── Request state ────────────────────────────────────────────────────────────────────────────

    /** 'unread' or null (All). An unknown value falls back to All rather than looking active but doing nothing. */
    private function resolveFilter(): ?string
    {
        return request()->query('filter') === 'unread' ? 'unread' : null;
    }

    private function resolveQuery(): string
    {
        $param = request()->query('q');

        return is_string($param) ? mb_substr(trim($param), 0, self::MAX_QUERY) : '';
    }

    // ── The threads ──────────────────────────────────────────────────────────────────────────────

    /**
     * The owner's conversations. OWNER-scoped by OwnerScope — see the class docblock for why no owner
     * predicate appears here.
     *
     * @return Builder<MessageThread>
     */
    private function baseQuery(string $search): Builder
    {
        $query = MessageThread::query();

        if ($search === '') {
            return $query;
        }

        // ⚠️ SEARCHING BODIES IS LEGITIMATE HERE, unlike the Guests CRM. `messages.body` is a PLAINTEXT text
        // column, so a LIKE genuinely matches — no blind index needed. (Guest email/phone are encrypted casts
        // and can never be searched; that standing deferral does not apply to message bodies.)
        $term = '%'.addcslashes($search, '%_\\').'%';

        return $query->where(fn (Builder $inner) => $inner
            ->where('subject', 'like', $term)
            ->orWhereHas('messages', fn (Builder $messages) => $messages->where('body', 'like', $term)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(?string $filter, string $search): array
    {
        $threads = $this->baseQuery($search)
            // ⚠️ `id` IS THE TIE-BREAK, NOT DECORATION. Two messages in one conversation routinely share a
            // `created_at` — a reply seconds after a question is the NORMAL case, and MySQL stores whole
            // seconds here. Ordering on the timestamp alone is therefore ambiguous, and the "last message"
            // preview could show the older one. `id` is a ULID (lexicographically time-ordered + unique), so
            // it breaks the tie by insertion order and gives a TOTAL order — the same reason the reservations
            // list orders `check_in DESC, id DESC`.
            ->with(['messages' => fn ($messages) => $messages->orderByDesc('created_at')->orderByDesc('id')->limit(1), 'messages.author'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        $rows = $threads->map(fn (MessageThread $thread): array => $this->row($thread))->all();

        // The unread filter is applied AFTER the derivation because "unread" is a comparison between
        // `owner_last_read_at` and each thread's latest OUTBOUND message — not a column to WHERE on.
        // The owner's thread list is a handful of rows, so this costs nothing.
        if ($filter === 'unread') {
            $rows = array_values(array_filter($rows, fn (array $row): bool => $row['unread']));
        }

        return $rows;
    }

    /**
     * One conversation row.
     *
     * @return array<string, mixed>
     */
    private function row(MessageThread $thread): array
    {
        $last = $thread->messages->first();
        $correspondent = $this->correspondent($thread);
        $unread = $this->isUnread($thread);

        return [
            'id' => (string) $thread->getKey(),
            'name' => $correspondent['name'],
            'role' => $correspondent['role'],
            'initials' => $this->initials($correspondent['name']),
            // "You: " when the owner spoke last. `inbound` IS the owner on an owner thread (see the docblock).
            'preview' => $last !== null
                ? ($last->direction === 'inbound'
                    ? __('php/livewire/owner/owner_messages.preview_mine', ['text' => mb_substr((string) $last->body, 0, 120)])
                    : mb_substr((string) $last->body, 0, 120))
                : null,
            'time' => $thread->last_message_at !== null ? $this->shortTime($thread->last_message_at) : '',
            'unread' => $unread,
            'subject' => $thread->subject,
        ];
    }

    // ── The correspondent — a REAL staff user, never a fabricated directory entry ─────────────────

    /**
     * Who the owner is talking to: the staff member who last replied, and their real role.
     *
     * The design shipped a hard-coded 5-person directory (and contradicted itself about the same people —
     * `ana` was online in one list and offline in the other). This reads the real `author_user_id` off the
     * thread's outbound messages instead. Falls back to the org's name for a thread nobody has answered yet:
     * honest — the owner is talking to their management company until a person picks it up.
     *
     * @return array{name: string, role: string}
     */
    private function correspondent(MessageThread $thread): array
    {
        // The most recent staff author on this thread — the person the owner is actually corresponding with.
        $reply = Message::query()
            ->where('thread_id', $thread->getKey())
            ->where('direction', 'outbound')
            ->whereNotNull('author_user_id')
            ->with('author')
            ->orderByDesc('created_at')
            ->orderByDesc('id')       // ULID tie-break — same-second messages are the normal case
            ->first();

        $author = $reply?->author;

        if ($author === null) {
            // Nobody has replied yet. If the owner COMPOSED to a specific member, the thread is really
            // `assigned_to` them — name that person (a real, allow-list-checked back-office user), so the header
            // reflects who the owner addressed, matching the wireframe. Unassigned → honestly the whole team.
            $assignee = $this->assignedStaff($thread);
            if ($assignee !== null) {
                return [
                    'name' => (string) $assignee->name,
                    'role' => $this->staffRole($assignee) ?? __('php/livewire/owner/owner_messages.awaiting_reply'),
                ];
            }

            return ['name' => __('php/livewire/owner/owner_messages.management_team'), 'role' => __('php/livewire/owner/owner_messages.no_reply_yet')];
        }

        return [
            'name' => (string) $author->name,
            // The REAL back-office role from the catalog — not "Property manager · Seaside Villas", which the
            // design invented as free text (there is no staff↔property assignment behind it).
            'role' => $this->staffRole($author) ?? __('php/livewire/owner/owner_messages.management_team_role'),
        ];
    }

    /**
     * The back-office member this thread is ASSIGNED to (the compose recipient), or null.
     *
     * Re-resolved through the tenant scope + the back-office allow-list — never trusted from the column alone —
     * so a stale/foreign/de-roled `assigned_to` degrades to null (→ "Your management team"), never to a name
     * the owner should not see. Same guard the compose controller applies when it writes `assigned_to`.
     */
    private function assignedStaff(MessageThread $thread): ?User
    {
        $assignedTo = $thread->assigned_to;
        if (! is_string($assignedTo) || $assignedTo === '') {
            return null;
        }

        $backOffice = (array) config('authorization.back_office_roles');
        $user = User::query()
            ->whereKey($assignedTo)
            ->whereIn('id', Membership::query()->where('status', 'active')->pluck('user_id'))
            ->first();

        return $user !== null && $user->hasAnyRole($backOffice) ? $user : null;
    }

    /** A staff user's real spatie role in THIS org, or null. Team-scoped, so it cannot read another org's roles. */
    private function staffRole(User $user): ?string
    {
        $roles = $user->getRoleNames();

        return $roles->isNotEmpty() ? RoleLabel::for((string) $roles->first()) : null;
    }

    /**
     * The staff an owner may start a conversation with: the org's real back-office members.
     *
     * ONE REAL QUERY, replacing the design's five fabricated entries — of which two ("Vyrobel Support",
     * "Vyrobel Billing") were the SaaS vendor rather than the operator, and one ("Maintenance team") was a
     * team with no user to address.
     *
     * @return list<array{id: string, name: string, role: string, initials: string}>
     */
    private function staffDirectory(): array
    {
        $backOffice = (array) config('authorization.back_office_roles');

        // Members of THIS org (the tenant scope constrains Membership), holding a real back-office role.
        $users = User::query()
            ->whereIn('id', Membership::query()->where('status', 'active')->pluck('user_id'))
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => $user->hasAnyRole($backOffice))
            ->values();

        return $users->map(fn (User $user): array => [
            'id' => (string) $user->getKey(),
            'name' => (string) $user->name,
            'role' => $this->staffRole($user) ?? __('php/livewire/owner/owner_messages.team_role'),
            'initials' => $this->initials((string) $user->name),
        ])->all();
    }

    // ── Unread — DERIVED from owner_last_read_at, never from the operator's counter ───────────────

    /**
     * Has a staff reply landed since the owner last opened this thread?
     *
     * ⚠️ NOT `unread_count`. That column is the OPERATOR's ("inbound since they last read") and
     * `InboxService::markRead()` zeroes it — reading it here would show the owner the operator's workload,
     * and clearing it here would wipe the operator's badge. The owner's unread is its own timestamp vs the
     * thread's latest OUTBOUND (staff) message.
     */
    private function isUnread(MessageThread $thread): bool
    {
        $lastStaffReply = Message::query()
            ->where('thread_id', $thread->getKey())
            ->where('direction', 'outbound')
            ->max('created_at');

        if ($lastStaffReply === null) {
            return false;   // nobody has replied — nothing for the owner to have missed
        }

        // Never opened → any staff reply is unread.
        return $thread->owner_last_read_at === null
            || CarbonImmutable::parse($lastStaffReply)->greaterThan($thread->owner_last_read_at);
    }

    private function unreadThreadCount(): int
    {
        return MessageThread::query()->get()
            ->filter(fn (MessageThread $thread): bool => $this->isUnread($thread))
            ->count();
    }

    // ── Copy — DERIVED from real counts only ─────────────────────────────────────────────────────

    private function subtitle(int $total, int $unread): string
    {
        $line = trans_choice('php/livewire/owner/owner_messages.subtitle_conversations', $total, ['count' => $total]);

        return $unread > 0 ? __('php/livewire/owner/owner_messages.subtitle_with_unread', ['conversations' => $line, 'number' => $unread]) : $line;
    }

    /** "10:18" today, "Yesterday", else "28 Sep" — the rail's compact stamp. */
    private function shortTime(\DateTimeInterface $at): string
    {
        $at = CarbonImmutable::parse($at);
        $today = CarbonImmutable::today();

        if ($at->isSameDay($today)) {
            return $at->format('H:i');
        }

        if ($at->isSameDay($today->subDay())) {
            return __('php/livewire/owner/owner_messages.yesterday');
        }

        return $at->translatedFormat(LocalDate::pattern('j M'));
    }
}
