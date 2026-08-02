<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\InboxActionController;
use App\Livewire\Owner\OwnerMessages;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Reservation;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Guest\GuestHistory;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Schedule\StaffRoster;
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
        $channel = $this->resolveChannel();    // a channel LABEL, or null = all

        // Mark the explicitly-opened thread read FIRST (an explicit ?c=, never the default preview) so the rail
        // badge + unread count reflect it on THIS request. Tenant-scoped find → cross-org id = null.
        $explicitId = is_string(request()->query('c')) && request()->query('c') !== '' ? (string) request()->query('c') : null;
        $explicitThread = $explicitId !== null ? MessageThread::query()->find($explicitId) : null;
        if ($explicitThread !== null) {
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
            ? array_values(array_filter($tabList, fn (array $r): bool => $r['channel_label'] === $channel))
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
                ['key' => 'pre-arrival', 'label' => 'Pre-arrival details'],
                ['key' => 'payment-reminder', 'label' => 'Payment reminder'],
                ['key' => 'review-request', 'label' => 'Thanks & review'],
            ],
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
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
            $preview = $last !== null ? Str::limit((string) $last->body, 90) : 'No messages yet';
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
            || array_filter($tabList, fn (array $r): bool => $r['channel_label'] === $c) !== []));

        if ($present === [] || (count($present) === 1 && $selected === null)) {
            return [];   // a single channel (the common case today) → no filter row needed
        }

        $tabs = [['key' => 'all', 'label' => 'All channels', 'count' => count($tabList), 'active' => $selected === null]];
        foreach ($present as $c) {
            $tabs[] = [
                'key' => $c,
                'label' => $c,
                'count' => count(array_filter($tabList, fn (array $r): bool => $r['channel_label'] === $c)),
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
        $labels = ['unread' => 'Unread', 'arriving' => 'Arriving today', 'mine' => 'Assigned to me', 'closed' => 'Closed'];
        $chips = [];
        if ($filter !== null && isset($labels[$filter])) {
            $chips[] = ['label' => $labels[$filter], 'clear_filter' => true, 'clear_channel' => false];
        }
        if ($channel !== null) {
            $chips[] = ['label' => $channel, 'clear_filter' => false, 'clear_channel' => true];
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
            ->with('author')
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
            'guest' => $this->guestCard($thread),
            'reservation' => $this->reservationCard($thread),
            'assignee' => $thread->assignedTo instanceof User ? ['name' => (string) $thread->assignedTo->name, 'initials' => $this->initials((string) $thread->assignedTo->name)] : null,
        ];
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
                ? ($m->author instanceof User ? (string) $m->author->name : ((string) $m->sender_label ?: 'Operator'))
                : $this->displayName($thread),
            'delivery' => $out ? $this->deliveryMeta((string) $m->delivery_status) : null,
            'time' => $at?->format('H:i') ?? '',
            'day_label' => $at !== null ? $this->dayLabel($at) : '',
        ];
    }

    /**
     * @return array{failed: bool, label: string}
     */
    private function deliveryMeta(string $status): array
    {
        return match ($status) {
            'delivered' => ['label' => 'Delivered', 'failed' => false],
            'sent' => ['label' => 'Sent', 'failed' => false],
            'queued' => ['label' => 'Sending…', 'failed' => false],
            'failed' => ['label' => 'Failed', 'failed' => true],
            default => ['label' => ucfirst($status), 'failed' => false],
        };
    }

    /**
     * Guest context card: NAME + channel + a derived-REAL "N stays · since {year}" (shared {@see GuestHistory}).
     *
     * @return array{name: string, channel: string, stat: string|null, guest_id: string|null}
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
            'channel' => 'via '.$channel['label'],
            'stat' => $stat,
            'guest_id' => $guest?->getKey() !== null ? (string) $guest->getKey() : null,
        ];
    }

    /**
     * @return array<string, string|null>|null
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
            'dates' => CarbonImmutable::parse($reservation->check_in)->format('j M').' – '.CarbonImmutable::parse($reservation->check_out)->format('j M'),
            'status' => (string) $reservation->status,
        ];
    }

    /**
     * Guests with a current, upcoming or recent stay — the "New message" picker (name + a real stay meta line).
     * Name-only (#28); the encrypted contact is never shown. Ordered arriving-soonest first.
     *
     * @return list<array{id: string, name: string, initials: string, meta: string}>
     */
    private function composeGuests(): array
    {
        $today = CarbonImmutable::today()->toDateString();
        $recent = CarbonImmutable::today()->subDays(14)->toDateString();

        $reservations = Reservation::query()
            ->with(['leadGuest', 'reservationUnits.unit'])
            ->whereNotNull('lead_guest_id')
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->where('check_out', '>=', $recent)
            ->orderBy('check_in')
            ->get();

        $seen = [];
        $out = [];
        foreach ($reservations as $reservation) {
            $guest = $reservation->leadGuest;
            if ($guest === null || isset($seen[(string) $guest->getKey()])) {
                continue;
            }
            $seen[(string) $guest->getKey()] = true;

            $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name)) ?: 'Guest';
            $unit = $reservation->reservationUnits->first()?->unit;
            $unitLabel = $unit !== null ? (string) ($unit->name ?? $unit->code) : '';
            $in = CarbonImmutable::parse($reservation->check_in);
            $when = $in->toDateString() > $today
                ? 'Arrives '.$in->format('j M')
                : ($reservation->status === 'checked_out' ? 'Checked out '.CarbonImmutable::parse($reservation->check_out)->format('j M') : 'In-house');

            $out[] = [
                'id' => (string) $guest->getKey(),
                'name' => $name,
                'initials' => $this->initials($name),
                'meta' => trim($when.($unitLabel !== '' ? ' · '.$unitLabel : '').' · '.ucfirst((string) $reservation->source)),
            ];
        }

        return $out;
    }

    // ── Shared helpers ───────────────────────────────────────────────────────────────────────────

    private function displayName(MessageThread $thread): string
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

        $label = $thread->messages->first()?->sender_label;

        return $label !== null && $label !== '' ? (string) $label : 'Unknown sender';
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
            return 'Today';
        }
        if ($at->isSameDay($now->subDay())) {
            return 'Yesterday';
        }

        return $at->format('j M Y');
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
            return $at->format('D');
        }

        return $at->format('j M');
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

    /** A channel display LABEL to filter by, or null for all. Validated against the known channel labels. */
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
