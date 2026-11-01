<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\InboxActionController;
use App\Livewire\Owner\OwnerMessages;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Owner;
use App\Models\Reservation;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Guest\GuestHistory;
use App\Support\I18n\LocalDate;
use App\Support\Inbox\MessageAttachments;
use App\Support\Inbox\MessageTemplate;
use App\Support\Inbox\ThreadName;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Platform\BreakGlass;
use App\Support\Reservations\ReservationStage;
use App\Support\Reservations\StayLabel;
use App\Support\Schedule\StaffRoster;
use App\Support\TeamChat\TeamChatReader;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator MESSAGES — the unified 3-pane inbox (#90 exact-match), rebuilt to `Operator Messages.html` on the SAME
 * real `MessageThread`/`Message` models + the single `InboxService` write path as the prior two-page inbox (list +
 * thread) it supersedes, and mirroring the owner 3-pane {@see OwnerMessages} with the operator
 * design system. The reply / assign / new-message MUTATIONS run through POST routes ({@see InboxActionController}),
 * never from here (a wire-action bypasses the tenant middleware — invariant #20).
 *
 * ⚠️ OPERATOR = the property MANAGER → FULL guest data (name + channel + reservation context + derived stay
 * history), distinct from the owner↔operator relay which is name-only (#77). The encrypted `participant_identity`
 * (routing) is NEVER rendered (#25); guest contact (email/phone) is never shown here (name-only perimeter #28).
 *
 * Honesty triage (#39): REAL — the threads, transcript (inbound/outbound by `direction`), the honest delivery
 * status incl. `failed` for a deferred channel (#24), the assignee, the linked reservation. DERIVED — unread,
 * relative times, day separators, the "N stays · since {year}" line (shared {@see GuestHistory}). OMITTED — no
 * presence dots / typing / SLA "replies within an hour" / read receipts (no `read` delivery status exists); no
 * fabricated suggested replies (no reply-suggestion engine — never fake AI).
 *
 * Scope-dependent reads ride GET links (`?c=`, `?filter=`, `?q=`), never wire-actions.
 */
#[Layout('components.layouts.operator')]
class OperatorInbox extends Component
{
    use Concerns\FormatsOperatorData;

    private const MAX_QUERY = 100;

    public function render(): View
    {
        $viewerId = (string) (auth()->id() ?? '');
        $filter = $this->resolveFilter();      // null|unread|arriving|mine|closed
        $search = $this->resolveQuery();
        $sort = $this->resolveSort();          // 'newest'|'oldest'
        $channel = $this->resolveChannel();    // a channel filter CODE (the English URL word), or null = all

        // Mark the explicitly-opened thread read FIRST (an explicit ?c=, never the default preview) so the rail
        // badge + unread count reflect it on THIS request. Tenant-scoped find → cross-org id = null.
        // ⚠️ Never the page's own live refresh (`live=1`, 2026-10-06 — the independent review): it re-reads the open
        // conversation every few seconds, and reading it there marked messages read nobody had looked at — and, as a
        // read is itself a change, refreshed again forever. The page posts `app.inbox.read` when the person is there.
        $explicitId = is_string(request()->query('c')) && request()->query('c') !== '' ? (string) request()->query('c') : null;
        $explicitThread = $explicitId !== null ? MessageThread::query()->find($explicitId) : null;
        if ($explicitThread !== null && ! request()->boolean('live')) {
            app(InboxService::class)->markRead($explicitThread);
        }

        $models = MessageThread::query()
            ->with([
                'guest', 'owner', 'reservation.reservationUnits.unit', 'assignedTo',
                // The TRUE last message drives last_who / last_delivery (Waiting vs Needs-sending). Tiebreak on
                // the ULID id (creation-ordered) so same-second messages resolve deterministically — a plain
                // latest() can otherwise return the wrong one (the same-second ambiguity the GATE-1 pass hit).
                'messages' => fn ($q) => $q->orderByDesc('created_at')->orderByDesc('id')->limit(1),
            ])
            ->get();

        // One base row per thread with EVERY derived field, computed once. The stat strip is over ALL threads;
        // the channel tabs are over the status-filtered set; the visible rows are status + channel + search + sort.
        $base = $this->baseRows($models, $viewerId);
        $stats = $this->stats($base);

        $tabList = $this->applyStatusFilter($base, $filter);
        $channelTabs = $this->channelTabs($tabList, $channel);
        $filtered = $channel !== null
            ? array_values(array_filter($tabList, fn (array $r): bool => $r['channel_filter'] === $channel))
            : $tabList;
        $filtered = $this->applySearch($filtered, $search);
        $rows = $this->sortRows($filtered, $sort, $filter);

        // The active pane: ?c= (explicit) or the first row of the filtered list — so the pane and rail agree.
        $activeThread = $explicitThread ?? $this->resolveActiveThread($rows[0]['id'] ?? null, $models);
        $active = null;
        $messages = [];
        if ($activeThread !== null) {
            $active = $this->activeCard($activeThread);
            $messages = $this->transcript($activeThread);
        }

        return view('livewire.operator.operator-inbox', [
            'viewer' => $this->viewer(),
            'rows' => $rows,
            'filter' => $filter,
            'query' => $search,
            'sort' => $sort,
            'channel' => $channel,
            'stats' => $stats,
            'channelTabs' => $channelTabs,
            'chips' => $this->chips($filter, $channel),
            'summary' => [
                'unread' => $stats['unread'],
                'total' => count($base),
            ],
            'active' => $active,
            'messages' => $messages,
            'staff' => app(StaffRoster::class)->staff(),
            'composeGuests' => $this->composeGuests(),
            'templates' => [
                ['key' => 'pre-arrival', 'label' => __('php/livewire/operator/operator_inbox.template_pre_arrival')],
                ['key' => 'payment-reminder', 'label' => __('php/livewire/operator/operator_inbox.template_payment_reminder')],
                ['key' => 'review-request', 'label' => __('php/livewire/operator/operator_inbox.template_review_request')],
            ],
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
            // Guests | Team (team chat, 2026-10-06) — the toggle's two counts (kept live by the pulse) and its label
            // (a support session borrows a staff member's view, never their team chat — its count stays theirs)
            'teamUnread' => auth()->user() instanceof User && ! BreakGlass::hasStash(request()) ? app(TeamChatReader::class)->unreadTotal(auth()->user()) : 0,
            'guestsLabel' => Owner::query()->exists() ? __('php/livewire/operator/operator_inbox.guests_and_owners') : __('php/livewire/operator/operator_inbox.guests'),
            // the live refresh (2026-10-06): the stamp to ask, and the open conversation it covers
            // `opened`: the person opened it (?c=) — only then does the page read new messages for them, never a preview
            'live' => ['live' => route('app.inbox.live'), 'c' => $active['id'] ?? null, 'opened' => $explicitThread !== null,
                'read' => $active !== null ? route('app.inbox.read', $active['id']) : null],
        ]);
    }

    // ── The rail ─────────────────────────────────────────────────────────────────────────────────

    /**
     * One derived row per thread — every field the rail, stat strip, channel tabs, filters and sort need,
     * computed once. `last_who`/`last_delivery` come from the thread's single loaded last message; `arrives_today`
     * from the linked reservation. `ord` is the sort key (last activity). Guest NAME only (#28).
     *
     * @param  Collection<int, MessageThread>  $models
     * @return list<array<string, mixed>>
     */
    private function baseRows($models, string $viewerId): array
    {
        return $models->map(function (MessageThread $thread) use ($viewerId): array {
            $last = $thread->messages->first();
            $channel = $this->channelMeta((string) $thread->channel);
            $name = $this->displayName($thread);
            $assignee = $thread->assignedTo;
            $preview = $last !== null ? Str::limit((string) $last->body, 90) : __('php/livewire/operator/operator_inbox.no_messages_yet');
            $closed = (string) $thread->status === 'closed';

            // Who sent the last message, and — when it was ours (outbound) — its delivery state. This drives the
            // real "Waiting on guest" (we replied, delivered/sent, awaiting them) vs "Needs sending" (a failed
            // outbound to retry) stats, exactly as the prototype derives them.
            $lastOut = $last !== null && $last->direction === 'outbound';
            $lastDelivery = $lastOut ? (string) $last->delivery_status : null;

            $reservation = $thread->reservation;
            $arrivesToday = $reservation !== null
                && CarbonImmutable::parse($reservation->check_in)->toDateString() === CarbonImmutable::today()->toDateString()
                && ! in_array((string) $reservation->status, ['cancelled', 'no_show', 'checked_out'], true);

            return [
                'id' => (string) $thread->getKey(),
                'name' => $name,
                'initials' => $this->initials($name),
                'channel_label' => $channel['label'],
                'channel_filter' => $channel['filter'],
                'channel_tone' => $channel['tone'],
                'is_unread' => $thread->unread_count > 0 && ! $closed,
                'status' => (string) $thread->status,
                'closed' => $closed,
                'mine' => $viewerId !== '' && (string) $thread->assigned_to === $viewerId,
                'preview' => $preview,
                'time' => $this->shortTime($thread->last_message_at),
                'context' => $this->reservationTag($thread),
                'arrives_today' => $arrivesToday,
                'last_who' => $last === null ? null : ($lastOut ? 'host' : 'guest'),
                'last_delivery' => $lastDelivery,
                'assignee' => $assignee instanceof User ? $this->initials((string) $assignee->name) : null,
                'ord' => $thread->last_message_at !== null ? CarbonImmutable::parse($thread->last_message_at)->getTimestamp() : 0,
                'search' => Str::lower($name.' '.$preview.' '.$channel['label']),
            ];
        })->all();
    }

    /**
     * The 4 stat cards — REAL derived counts over ALL threads (never the wireframe's hardcoded figures, #39).
     * Unread = active with unread; Open = active (non-closed); Waiting on guest = active, our last message,
     * delivered/sent (awaiting them); Needs sending = active with a FAILED outbound to retry.
     *
     * @param  list<array<string, mixed>>  $base
     * @return array{unread: int, open: int, waiting: int, needs_sending: int}
     */
    private function stats(array $base): array
    {
        $active = array_filter($base, fn (array $r): bool => ! $r['closed']);

        return [
            'unread' => count(array_filter($active, fn (array $r): bool => $r['is_unread'])),
            'open' => count($active),
            'waiting' => count(array_filter($active, fn (array $r): bool => $r['last_who'] === 'host' && $r['last_delivery'] !== 'failed')),
            'needs_sending' => count(array_filter($active, fn (array $r): bool => $r['last_who'] === 'host' && $r['last_delivery'] === 'failed')),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $base
     * @return list<array<string, mixed>>
     */
    private function applyStatusFilter(array $base, ?string $filter): array
    {
        // Closed threads appear ONLY under "Closed" — every other tab (incl. the default "All") is non-closed,
        // matching the prototype's `if (d.closed) return false` before the per-tab predicate.
        return match ($filter) {
            'unread' => array_values(array_filter($base, fn (array $r): bool => $r['is_unread'])),
            'arriving' => array_values(array_filter($base, fn (array $r): bool => $r['arrives_today'] && ! $r['closed'])),
            'mine' => array_values(array_filter($base, fn (array $r): bool => $r['mine'] && ! $r['closed'])),
            'closed' => array_values(array_filter($base, fn (array $r): bool => $r['closed'])),
            default => array_values(array_filter($base, fn (array $r): bool => ! $r['closed'])),
        };
    }

    /**
     * The channel filter — "All channels" + only the channels ACTUALLY present in the status-filtered set (plus
     * the currently-selected one so it never vanishes mid-filter). Honest: a channel with no threads is never
     * offered, so the deferred external channels appear only as real threads land on them (#39).
     *
     * @param  list<array<string, mixed>>  $tabList
     * @return list<array{key: string, label: string, count: int, active: bool}>
     */
    private function channelTabs(array $tabList, ?string $selected): array
    {
        $order = ['Direct', 'Booking.com', 'Airbnb', 'Expedia', 'WhatsApp', 'Email'];
        $present = array_values(array_filter($order, fn (string $c): bool => $c === $selected
            || array_filter($tabList, fn (array $r): bool => $r['channel_filter'] === $c) !== []));

        if ($present === [] || (count($present) === 1 && $selected === null)) {
            return [];   // a single channel (the common case today) → no filter row needed
        }

        $tabs = [['key' => 'all', 'label' => __('php/livewire/operator/operator_inbox.all_channels'), 'count' => count($tabList), 'active' => $selected === null]];
        foreach ($present as $c) {
            $tabs[] = [
                'key' => $c,
                'label' => $this->channelFilterLabel($c),
                'count' => count(array_filter($tabList, fn (array $r): bool => $r['channel_filter'] === $c)),
                'active' => $selected === $c,
            ];
        }

        return $tabs;
    }

    /**
     * Rail search — matches the guest NAME, the last-message preview, the channel label (in memory) AND the full
     * body of ANY message in the thread (a single DB lookup). Honest scope: it never searches guest CONTACT
     * (encrypted, name-only #28). Message bodies are plaintext, so a full-history text search is real.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function applySearch(array $rows, string $search): array
    {
        if ($search === '') {
            return $rows;
        }

        $needle = Str::lower($search);
        $ids = array_map(fn (array $r): string => $r['id'], $rows);
        $bodyMatch = $ids === [] ? [] : array_flip(
            Message::query()
                ->whereIn('thread_id', $ids)
                ->whereRaw('LOWER(body) LIKE ?', ['%'.$this->escapeLike($needle).'%'])
                ->distinct()
                ->pluck('thread_id')
                ->map(fn ($id): string => (string) $id)
                ->all(),
        );

        return array_values(array_filter(
            $rows,
            fn (array $r): bool => str_contains($r['search'], $needle) || isset($bodyMatch[$r['id']]),
        ));
    }

    /**
     * Sort by last activity (Newest / Oldest first). Unread floats to the top on every non-closed view — the
     * prototype's `unreadFirst` — so the operator sees what needs attention first regardless of direction.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sortRows(array $rows, string $sort, ?string $filter): array
    {
        usort($rows, function (array $a, array $b) use ($sort): int {
            return $sort === 'oldest' ? $a['ord'] <=> $b['ord'] : $b['ord'] <=> $a['ord'];
        });

        if ($filter !== 'closed') {
            // Stable-ish unread-first: PHP's usort isn't stable pre-8.0 but IS from 8.0, so the activity order
            // within each group is preserved.
            usort($rows, fn (array $a, array $b): int => ($b['is_unread'] ? 1 : 0) <=> ($a['is_unread'] ? 1 : 0));
        }

        return $rows;
    }

    /**
     * Active-filter chips (status + channel) → each clears itself; "Clear all" resets both. Presentation over
     * the existing GET params.
     *
     * @return list<array{label: string, clear_filter: bool, clear_channel: bool}>
     */
    private function chips(?string $filter, ?string $channel): array
    {
        $labels = [
            'unread' => __('php/livewire/operator/operator_inbox.chip_unread'),
            'arriving' => __('php/livewire/operator/operator_inbox.chip_arriving'),
            'mine' => __('php/livewire/operator/operator_inbox.chip_mine'),
            'closed' => __('php/livewire/operator/operator_inbox.chip_closed'),
        ];
        $chips = [];
        if ($filter !== null && isset($labels[$filter])) {
            $chips[] = ['label' => $labels[$filter], 'clear_filter' => true, 'clear_channel' => false];
        }
        if ($channel !== null) {
            $chips[] = ['label' => $this->channelFilterLabel($channel), 'clear_filter' => false, 'clear_channel' => true];
        }

        return $chips;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * The active thread's full transcript. Presentation of the real messages, ordered oldest→newest.
     *
     * @return list<array<string, mixed>>
     */
    private function transcript(MessageThread $thread): array
    {
        return Message::query()
            ->with(['author', 'media'])
            ->where('thread_id', $thread->getKey())
            ->orderBy('created_at')->orderBy('id')
            ->get()
            ->map(fn (Message $m): array => $this->messageRow($m, $thread))
            ->all();
    }

    /**
     * @param  Collection<int, MessageThread>  $models
     */
    private function resolveActiveThread(?string $defaultId, $models): ?MessageThread
    {
        if ($defaultId === null) {
            return null;
        }

        return $models->first(fn (MessageThread $t): bool => (string) $t->getKey() === $defaultId);
    }

    // ── The active pane ──────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function activeCard(MessageThread $thread): array
    {
        $thread->loadMissing(['guest', 'owner', 'reservation.reservationUnits.unit', 'assignedTo']);
        $channel = $this->channelMeta((string) $thread->channel);
        $status = (string) $thread->status;
        $connected = $this->channelIsConnected((string) $thread->channel);
        $name = $this->displayName($thread);

        return [
            'id' => (string) $thread->getKey(),
            'unread' => (int) $thread->unread_count,
            'name' => $name,
            'initials' => $this->initials($name),
            'channel_label' => $channel['label'],
            'channel_tone' => $channel['tone'],
            'status' => $status,
            'subject' => $thread->subject,
            'can_reply' => $connected,
            'can_close' => $status !== 'closed',
            'can_reopen' => $status === 'closed',
            'can_snooze' => $status !== 'closed',
            // ⚠️ WAVE 2 · #19 — the composer footnote said "The guest replies wherever they booked" under
            // EVERY thread, including the owner↔operator relay, which has no guest and no booking channel.
            // The sentence branches on what the thread actually is.
            'is_owner_thread' => $thread->owner_id !== null,
            // ⚠️ 2026-10-05 — a DIRECT (manual) conversation sends nothing anywhere: the guest reads it in their own guest account,
            //    if they have one. The words under the reply box say which; a booking channel keeps "replies wherever they booked".
            'is_direct' => (string) $thread->channel === 'manual',
            'guest' => $this->guestCard($thread),
            'reservation' => $this->reservationCard($thread),
            'assignee' => $thread->assignedTo instanceof User ? ['name' => (string) $thread->assignedTo->name, 'initials' => $this->initials((string) $thread->assignedTo->name)] : null,
            // The latest INTERNAL note (staff-only, #28 name-only author) + the guest's reservations for the
            // link-reservation picker. A note whose body was cleared by anonymize is treated as absent.
            'note' => $this->latestNote($thread),
            'has_guest' => $thread->guest !== null,
            'link_reservations' => $this->linkReservations($thread),
        ];
    }

    /**
     * The most recent STAFF internal note on the thread (or null). Author name only (#28). An anonymized note
     * (body redacted to '') is treated as absent — the erase promise holds on this surface too.
     *
     * @return array{body: string, author: string, time: string}|null
     */
    private function latestNote(MessageThread $thread): ?array
    {
        $note = $thread->notes()
            ->with('author')
            ->where('visibility', 'internal')
            ->where('body', '!=', '')
            ->latest()
            ->first();

        if ($note === null) {
            return null;
        }

        return [
            'body' => (string) $note->body,
            'author' => $note->author instanceof User ? (string) $note->author->name : __('php/livewire/operator/operator_inbox.staff_fallback'),
            'time' => $note->created_at !== null ? CarbonImmutable::parse($note->created_at)->diffForHumans() : '',
        ];
    }

    /**
     * The thread guest's reservations, for the link-reservation picker — id + a NAME-free label (reference ·
     * unit · date) + whether it's the currently-linked one. Empty for a guest-less (e.g. owner) thread; the
     * write is the certified InboxService::linkReservation (tenant-scoped) via a POST route.
     *
     * @return list<array{id: string, label: string, linked: bool}>
     */
    private function linkReservations(MessageThread $thread): array
    {
        $guest = $thread->guest;
        if ($guest === null) {
            return [];
        }

        return Reservation::query()
            ->where('lead_guest_id', $guest->getKey())
            ->with('reservationUnits.unit')
            ->orderByDesc('check_in')
            ->limit(12)
            ->get()
            ->map(function (Reservation $r) use ($thread): array {
                $unit = $r->reservationUnits->first()?->unit;

                return [
                    'id' => (string) $r->getKey(),
                    'label' => (string) $r->reference
                        .($unit !== null ? ' · '.($unit->name ?? $unit->code) : '')
                        .' · '.CarbonImmutable::parse($r->check_in)->translatedFormat(LocalDate::pattern('j M')).'–'.CarbonImmutable::parse($r->check_out)->translatedFormat(LocalDate::pattern('j M')),
                    'linked' => (string) $thread->reservation_id === (string) $r->getKey(),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function messageRow(Message $m, MessageThread $thread): array
    {
        $out = $m->direction === 'outbound';
        $at = $m->created_at !== null ? CarbonImmutable::parse($m->created_at) : null;

        return [
            'id' => (string) $m->getKey(),
            'out' => $out,
            'body' => (string) $m->body,
            'author' => $out
                ? ($m->author instanceof User ? (string) $m->author->name : ((string) $m->sender_label ?: __('php/livewire/operator/operator_inbox.operator_fallback')))
                : $this->displayName($thread),
            'delivery' => $out ? $this->deliveryMeta((string) $m->delivery_status, (string) $m->channel) : null,
            // ⚠️ DEC-m (2026-09-30) — what an owner attached, for the staff they sent it to: each file opens through the
            //    inbox's own signed-in route; the rows are the owner portal's ({@see MessageAttachments::rows()}).
            'attachments' => app(MessageAttachments::class)->rows($m, 'app.inbox.attachment'),
            'time' => $at?->format('H:i') ?? '',
            'day_label' => $at !== null ? $this->dayLabel($at) : '',
        ];
    }

    /**
     * @return array{failed: bool, label: string}
     */
    private function deliveryMeta(string $status, string $channel = ''): array
    {
        return match ($status) {
            // ⚠️ 2026-10-05 — Direct "delivers" by keeping the message in Vyrobel (ManualChannel sends nothing): "Saved", never "Delivered"
            'delivered' => ['label' => $channel === 'manual' ? __('php/livewire/operator/operator_inbox.delivery_saved') : __('php/livewire/operator/operator_inbox.delivery_delivered'), 'failed' => false],
            'sent' => ['label' => __('php/livewire/operator/operator_inbox.delivery_sent'), 'failed' => false],
            'queued' => ['label' => __('php/livewire/operator/operator_inbox.delivery_queued'), 'failed' => false],
            'failed' => ['label' => __('php/livewire/operator/operator_inbox.delivery_failed'), 'failed' => true],
            default => ['label' => ucfirst($status), 'failed' => false],
        };
    }

    /**
     * Guest context card: NAME + channel + a derived-REAL "N stays · since {year}" (shared {@see GuestHistory}).
     *
     * @return array{name: string, channel: string, stat: string|null, guest_id: string|null, is_vip: bool, has_account: bool}
     */
    private function guestCard(MessageThread $thread): array
    {
        $guest = $thread->guest;
        $channel = $this->channelMeta((string) $thread->channel);

        $stat = $guest !== null
            ? GuestHistory::for(Reservation::query()->where('lead_guest_id', $guest->getKey())->get(['id', 'status', 'check_in', 'check_out']))['stat']
            : null;

        return [
            'name' => $this->displayName($thread),
            'channel' => __('php/livewire/operator/operator_inbox.via_channel', ['channel' => $channel['label']]),
            'stat' => $stat,
            'guest_id' => $guest?->getKey() !== null ? (string) $guest->getKey() : null,
            /*
             * D10-5 — the guest's REAL VIP flag, the same `guests.is_vip` the front desk already badges.
             * ⚠️ An OPERATIONAL flag, not contact PII — it says how to treat an arrival, never who they are,
             * so it carries none of the #28 perimeter's weight (the front desk's own note says the same).
             * ⚠️ A thread with no linked guest is simply not VIP — never "unknown".
             */
            'is_vip' => $guest !== null && (bool) $guest->is_vip,
            // whether the guest can sign in to their guest account — where a Direct message is read (a yes/no; never the hash)
            'has_account' => $guest !== null && $guest->password !== null,
        ];
    }

    /**
     * The conversation's linked reservation, or null when the thread has none.
     *
     * ⚠️ The shape is stated precisely rather than as `array<string, string|null>`: D10-2 added a nested
     * `stage_meta`, and the loose declaration would have accepted anything. PHPStan caught the mismatch,
     * which is the declaration doing its job.
     *
     * @return array{id: string, reference: string, unit: ?string, dates: string, status: string, stage_meta: array{label: string, tone: string}}|null
     */
    private function reservationCard(MessageThread $thread): ?array
    {
        $reservation = $thread->reservation;
        if ($reservation === null) {
            return null;
        }

        $unit = $reservation->reservationUnits->first()?->unit;

        return [
            'id' => (string) $reservation->getKey(),
            'reference' => (string) $reservation->reference,
            'unit' => $unit !== null ? (string) ($unit->name ?? $unit->code) : null,
            'dates' => __('php/livewire/operator/operator_inbox.reservation_dates', [
                'check_in' => CarbonImmutable::parse($reservation->check_in)->translatedFormat(LocalDate::pattern('j M')),
                'check_out' => CarbonImmutable::parse($reservation->check_out)->translatedFormat(LocalDate::pattern('j M')),
            ]),
            'status' => (string) $reservation->status,
            /*
             * D10-2 — the reservation's LIFECYCLE STAGE, from the ONE shared resolver.
             *
             * ⚠️ THIS IS WHY THE EARLIER ATTEMPT STOPPED. A note in the Blade recorded that the card carried
             * the RAW `status` and that `ReservationStage::meta()` needs a computed STAGE — feeding it a
             * status renders "Unknown" — so rather than re-deriving the vocabulary in a template, the gap was
             * logged. The resolver is called HERE instead, exactly as `ReservationsList` and `GuestProfile`
             * do, so the inbox cannot disagree with them about whether a guest is in-house.
             *
             * ⚠️ A STAGE IS NOT A STATUS: `status` is the 7-value enum, `stage` a derived bucket over that
             * enum PLUS the dates. Both stay on the card — nothing about the existing `status` changed.
             */
            'stage_meta' => ReservationStage::meta(ReservationStage::for($reservation)),
        ];
    }

    /**
     * Guests with a current, upcoming or recent stay — the "New message" picker (name + a real stay meta line).
     * Name-only (#28); the encrypted contact is never shown. Ordered arriving-soonest first.
     *
     * @return list<array{id: string, name: string, initials: string, meta: string, has_account: bool, templates: array<string, string>}>
     */
    private function composeGuests(): array
    {
        $today = CarbonImmutable::today();
        $todayStr = $today->toDateString();
        $recent = $today->subDays(14)->toDateString();

        $reservations = Reservation::query()
            ->with(['leadGuest', 'reservationUnits.unit'])
            ->whereNotNull('lead_guest_id')
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->where('check_out', '>=', $recent)
            ->get();

        // Group by guest, then pick the SAME most-relevant reservation per guest that start() links + merges —
        // a current/upcoming stay beats a recent checkout — so the picker meta, the pre-merged templates, the
        // sent body AND the linked reservation reference the ONE booking (the review-caught drift where the
        // composer merged the guest's oldest reservation while start() linked the newest).
        $byGuest = [];
        foreach ($reservations as $reservation) {
            if ($reservation->lead_guest_id !== null) {
                $byGuest[(string) $reservation->lead_guest_id][] = $reservation;
            }
        }

        $rows = [];
        foreach ($byGuest as $list) {
            $reservation = MessageTemplate::mostRelevant($list, $today);
            $guest = $reservation?->leadGuest;
            if ($reservation === null || $guest === null) {
                continue;
            }

            $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name)) ?: __('php/livewire/operator/operator_inbox.guest_fallback');
            $unit = $reservation->reservationUnits->first()?->unit;
            $unitLabel = $unit !== null ? (string) ($unit->name ?? $unit->code) : '';
            $in = CarbonImmutable::parse($reservation->check_in);
            $outDate = CarbonImmutable::parse($reservation->check_out);
            // ⚠️ 2026-10-05 — the ONE rule the Guests list's Stay column reads: "In-house" was said of a guest arriving today and
            //    not yet checked in; "Checked out" of dates that passed with nobody checked in
            $when = StayLabel::for($reservation, $today, 'j M');

            $rows[] = [
                'id' => (string) $guest->getKey(),
                'name' => $name,
                'initials' => $this->initials($name),
                'meta' => trim($when.($unitLabel !== '' ? ' · '.$unitLabel : '').' · '.ucfirst((string) $reservation->source)),
                // where a Direct message is read: their guest account, if they have one (a yes/no, 2026-10-05)
                'has_account' => $guest->password !== null,
                // Each template pre-MERGED from the SAME context reservation (the ONE shared merge #83) so the
                // New-message modal pre-fills the editable textarea — the operator reviews + edits, and the
                // edited body is what sends (it wins over `template` in start()). No door codes/secrets (#33).
                'templates' => [
                    'pre-arrival' => MessageTemplate::merged('pre-arrival', $guest, $reservation),
                    'payment-reminder' => MessageTemplate::merged('payment-reminder', $guest, $reservation),
                    'review-request' => MessageTemplate::merged('review-request', $guest, $reservation),
                ],
                // Sort key: active/upcoming guests first (soonest check-in), then recently-departed guests last.
                '_active' => $outDate->toDateString() >= $todayStr ? 0 : 1,
                '_sort' => $outDate->toDateString() >= $todayStr ? $in->getTimestamp() : -$outDate->getTimestamp(),
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$a['_active'], $a['_sort']] <=> [$b['_active'], $b['_sort']]);

        return array_map(function (array $r): array {
            unset($r['_active'], $r['_sort']);

            return $r;
        }, $rows);
    }

    // ── Shared helpers ───────────────────────────────────────────────────────────────────────────

    /** The conversation's name — the one rule ({@see ThreadName}), shared with the staff's bell. */
    private function displayName(MessageThread $thread): string
    {
        return ThreadName::for($thread);
    }

    private function reservationTag(MessageThread $thread): ?string
    {
        $reservation = $thread->reservation;
        if ($reservation === null) {
            return null;
        }

        $unit = $reservation->reservationUnits->first()?->unit;
        $unitLabel = $unit !== null ? ($unit->name ?? $unit->code) : null;

        return (string) $reservation->reference.($unitLabel !== null ? ' · '.$unitLabel : '');
    }

    private function dayLabel(CarbonImmutable $at): string
    {
        $now = CarbonImmutable::now();
        if ($at->isSameDay($now)) {
            return __('common.today');
        }
        if ($at->isSameDay($now->subDay())) {
            return __('php/livewire/operator/operator_inbox.yesterday');
        }

        return $at->translatedFormat(LocalDate::pattern('j M Y'));
    }

    private function shortTime(?\DateTimeInterface $at): string
    {
        if ($at === null) {
            return '';
        }

        $at = CarbonImmutable::parse($at);
        $now = CarbonImmutable::now();
        if ($at->isSameDay($now)) {
            return $at->format('H:i');
        }
        if ($at->greaterThan($now->subDays(6))) {
            return $at->translatedFormat(LocalDate::pattern('D'));
        }

        return $at->translatedFormat(LocalDate::pattern('j M'));
    }

    private function resolveFilter(): ?string
    {
        $f = request()->query('filter');

        return in_array($f, ['unread', 'arriving', 'mine', 'closed'], true) ? (string) $f : null;
    }

    /** Newest first by default; ?sort=oldest flips it. Mirrors the ?filter=/?q= GET pattern (RMB-safe). */
    private function resolveSort(): string
    {
        return request()->query('sort') === 'oldest' ? 'oldest' : 'newest';
    }

    /** A channel filter CODE (the English word the URL carries) to filter by, or null for all. Validated against the known codes. */
    private function resolveChannel(): ?string
    {
        $c = request()->query('channel');
        $known = ['Direct', 'Booking.com', 'Airbnb', 'Expedia', 'WhatsApp', 'Email'];

        return is_string($c) && in_array($c, $known, true) ? $c : null;
    }

    private function resolveQuery(): string
    {
        $param = request()->query('q');

        return is_string($param) ? mb_substr(trim($param), 0, self::MAX_QUERY) : '';
    }
}
