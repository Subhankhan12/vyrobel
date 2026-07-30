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
        $filter = $this->resolveFilter();
        $search = $this->resolveQuery();

        // Mark the explicitly-opened thread read FIRST (an explicit ?c=, never the default preview) so the rail
        // badge + unread count reflect it on THIS request. Tenant-scoped find → cross-org id = null.
        $explicitId = is_string(request()->query('c')) && request()->query('c') !== '' ? (string) request()->query('c') : null;
        $explicitThread = $explicitId !== null ? MessageThread::query()->find($explicitId) : null;
        if ($explicitThread !== null) {
            app(InboxService::class)->markRead($explicitThread);
        }

        $models = MessageThread::query()
            ->with(['guest', 'owner', 'reservation.reservationUnits.unit', 'assignedTo', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        $rows = $this->rows($models, $viewerId, $filter, $search);

        // The active pane: ?c= (explicit) or the first row of the filtered list — so the pane and rail agree.
        $activeThread = $explicitThread ?? $this->resolveActiveThread($rows[0]['id'] ?? null, $models);
        $active = null;
        $messages = [];
        if ($activeThread !== null) {
            $msgs = Message::query()->with('author')->where('thread_id', $activeThread->getKey())->orderBy('created_at')->orderBy('id')->get();
            $active = $this->activeCard($activeThread);
            $messages = $msgs->map(fn (Message $m): array => $this->messageRow($m, $activeThread))->all();
        }

        return view('livewire.operator.operator-inbox', [
            'viewer' => $this->viewer(),
            'rows' => $rows,
            'filter' => $filter,
            'query' => $search,
            'summary' => [
                'unread' => $models->where('status', '!=', 'closed')->where('unread_count', '>', 0)->count(),
                'total' => $models->count(),
                'mine' => $viewerId !== '' ? $models->where('status', '!=', 'closed')->where('assigned_to', $viewerId)->count() : 0,
                'closed' => $models->where('status', 'closed')->count(),
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
     * @param  Collection<int, MessageThread>  $models
     * @return list<array<string, mixed>>
     */
    private function rows($models, string $viewerId, ?string $filter, string $search): array
    {
        $rows = $models->map(function (MessageThread $thread) use ($viewerId): array {
            $last = $thread->messages->first();
            $channel = $this->channelMeta((string) $thread->channel);
            $name = $this->displayName($thread);
            $assignee = $thread->assignedTo;
            $preview = $last !== null ? Str::limit((string) $last->body, 90) : 'No messages yet';

            return [
                'id' => (string) $thread->getKey(),
                'name' => $name,
                'initials' => $this->initials($name),
                'channel_label' => $channel['label'],
                'channel_tone' => $channel['tone'],
                'is_unread' => $thread->unread_count > 0 && $thread->status !== 'closed',
                'status' => (string) $thread->status,
                'mine' => $viewerId !== '' && (string) $thread->assigned_to === $viewerId,
                'preview' => $preview,
                'time' => $this->shortTime($thread->last_message_at),
                'context' => $this->reservationTag($thread),
                'assignee' => $assignee instanceof User ? $this->initials((string) $assignee->name) : null,
                'search' => Str::lower($name.' '.$preview.' '.$channel['label']),
            ];
        })->all();

        if ($filter === 'unread') {
            $rows = array_values(array_filter($rows, fn (array $r): bool => $r['is_unread']));
        } elseif ($filter === 'mine') {
            $rows = array_values(array_filter($rows, fn (array $r): bool => $r['mine'] && $r['status'] !== 'closed'));
        } elseif ($filter === 'closed') {
            $rows = array_values(array_filter($rows, fn (array $r): bool => $r['status'] === 'closed'));
        }

        if ($search !== '') {
            $needle = Str::lower($search);
            $rows = array_values(array_filter($rows, fn (array $r): bool => str_contains($r['search'], $needle)));
        }

        return $rows;
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

        return in_array($f, ['unread', 'mine', 'closed'], true) ? (string) $f : null;
    }

    private function resolveQuery(): string
    {
        $param = request()->query('q');

        return is_string($param) ? mb_substr(trim($param), 0, self::MAX_QUERY) : '';
    }
}
