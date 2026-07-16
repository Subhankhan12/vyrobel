<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\Operator\InboxActionController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Membership;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Reservation;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Guest\GuestHistory;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Unified Inbox — thread VIEW / conversation (design pass · Inbox UI). Styled from the
 * catalogued "Operator Conversation" design onto the shared operator design system (operator layout,
 * <x-operator.shell>, FormatsOperatorData — reused, never redefined). Presentation + a thin read: the
 * page READS; the reply + lifecycle MUTATIONS run through the real {@see InboxService} via
 * POST routes ({@see InboxActionController}), NOT from here.
 *
 * RMB rule: `{thread}` is a plain string prop resolved in render() via a tenant-scoped `findOrFail`
 * (after SetTenantForWebRequest) — a cross-org id 404s. Opening a thread marks it read (mount()).
 *
 * Honesty triage:
 *   • REAL     — the message transcript (inbound/outbound by `direction`), real timestamps + the real
 *                outbound DELIVERY status (Sent/Delivered, and the honest FAILED for a deferred-channel
 *                send — invariant #24); the reply sends over the thread's real channel; the deferred
 *                channels show an honest "not connected" composer (never a fake send).
 *   • DERIVED  — the guest "N stays · since {year}" line, from the guest's real reservations (or OMITTED).
 *   • PII      — guest NAME + the CHANNEL only ("via WhatsApp"); the encrypted contact is NEVER rendered.
 */
#[Layout('components.layouts.operator')]
class InboxThread extends Component
{
    use FormatsOperatorData;

    public string $threadId;

    public function mount(string $thread): void
    {
        $this->threadId = $thread;

        // Tenant-scoped: a cross-org (or unknown) id 404s. Opening the thread marks it read.
        $model = MessageThread::query()->findOrFail($thread);
        app(InboxService::class)->markRead($model);
    }

    public function render(): View
    {
        $thread = MessageThread::query()
            ->with(['guest', 'reservation.reservationUnits.unit'])
            ->findOrFail($this->threadId);

        $channel = $this->channelMeta((string) $thread->channel);
        $status = (string) $thread->status;
        $connected = $this->channelIsConnected((string) $thread->channel);

        $messages = Message::query()
            ->with('author')
            ->where('thread_id', $thread->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $m): array => $this->messageRow($m, $thread))
            ->all();

        return view('livewire.inbox-thread', [
            'viewer' => $this->viewer(),
            'thread' => [
                'id' => (string) $thread->id,
                'guest' => $this->guestName($thread),
                'initials' => $this->initials($this->guestName($thread)),
                'channel_label' => $channel['label'],
                'channel_short' => $channel['short'],
                'channel_tone' => $channel['tone'],
                'status' => $status,
                'subject' => $thread->subject,
                'can_reply' => $connected,
                'can_close' => $status !== 'closed',
                'can_reopen' => $status === 'closed',
                'can_snooze' => $status !== 'closed',
            ],
            'messages' => $messages,
            'guestCard' => $this->guestCard($thread),
            'reservationCard' => $this->reservationCard($thread),
            'linkable' => $this->linkableReservations($thread),
            'assignee' => $this->assigneeCard($thread),
            'staff' => $this->orgStaff(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function messageRow(Message $m, MessageThread $thread): array
    {
        $out = $m->direction === 'outbound';
        $at = $m->created_at !== null ? CarbonImmutable::parse($m->created_at) : null;

        return [
            'id' => (string) $m->id,
            'direction' => (string) $m->direction,
            'out' => $out,
            'body' => (string) $m->body,
            'author' => $out
                ? ($m->author instanceof User ? (string) $m->author->name : ($m->sender_label ?? 'Operator'))
                : $this->guestName($thread),
            'delivery' => $out ? $this->deliveryMeta((string) $m->delivery_status) : null,
            'time' => $at?->format('H:i') ?? '',
            'day_key' => $at?->format('Y-m-d') ?? '',
            'day_label' => $at !== null ? $this->dayLabel($at) : '',
        ];
    }

    /**
     * Outbound delivery status → label + failed flag. `failed` is the honest deferred-channel state
     * (invariant #24) — small + muted-warm in the UI, never hidden.
     *
     * @return array{label: string, failed: bool}
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

    /** Guest NAME only (front-desk precedent) — never the encrypted participant identity/contact. */
    private function guestName(MessageThread $thread): string
    {
        $guest = $thread->guest;
        if ($guest === null) {
            return 'Unknown sender';
        }

        $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name));

        return $name !== '' ? $name : 'Unknown sender';
    }

    /**
     * Guest context card: NAME + the CHANNEL only (never raw contact), + a derived-REAL "N stays · since
     * {year}" from the guest's real reservations (OMITTED when there's nothing real to show).
     *
     * @return array{name: string, channel: string, stat: string|null, guest_id: string|null}
     */
    private function guestCard(MessageThread $thread): array
    {
        $guest = $thread->guest;
        $channel = $this->channelMeta((string) $thread->channel);

        // The SHARED definition (Wave 2 · Sub-gate 5), not this screen's own count. This card used to count
        // EVERY reservation — cancellations included — and date "guest since" from the earliest of them,
        // while the Guests CRM counts only stays that actually happened. Two screens, two sincere answers,
        // one guest. GuestHistory is now the single rule; `status` is selected because it decides what
        // counts.
        $stat = $guest !== null
            ? GuestHistory::for(Reservation::query()->where('lead_guest_id', $guest->id)->get(['id', 'status', 'check_in', 'check_out']))['stat']
            : null;

        return [
            'name' => $this->guestName($thread),
            'channel' => 'via '.$channel['label'],
            'stat' => $stat,
            'guest_id' => $guest?->id !== null ? (string) $guest->id : null,
        ];
    }

    /**
     * The linked reservation's context (ref · unit · dates · status), or null when unlinked.
     *
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
            'id' => (string) $reservation->id,
            'reference' => (string) $reservation->reference,
            'unit' => $unit !== null ? (string) ($unit->name ?? $unit->code) : null,
            'dates' => CarbonImmutable::parse($reservation->check_in)->format('j M').' – '.CarbonImmutable::parse($reservation->check_out)->format('j M'),
            'status' => (string) $reservation->status,
        ];
    }

    /**
     * When the thread is UNLINKED, the guest's own reservations that could be linked (tenant-scoped).
     *
     * @return list<array{id: string, label: string}>
     */
    private function linkableReservations(MessageThread $thread): array
    {
        if ($thread->reservation_id !== null || $thread->guest === null) {
            return [];
        }

        return Reservation::query()
            ->where('lead_guest_id', $thread->guest->id)
            ->orderByDesc('check_in')
            ->get()
            ->map(fn (Reservation $r): array => [
                'id' => (string) $r->id,
                'label' => (string) $r->reference.' · '.CarbonImmutable::parse($r->check_in)->format('j M'),
            ])->all();
    }

    /**
     * @return array{name: string|null, initials: string|null}
     */
    private function assigneeCard(MessageThread $thread): array
    {
        $assignee = $thread->assignedTo;

        return [
            'name' => $assignee instanceof User ? (string) $assignee->name : null,
            'initials' => $assignee instanceof User ? $this->initials((string) $assignee->name) : null,
        ];
    }

    /**
     * The active staff of the current org, for the Assign picker.
     *
     * @return list<array{id: string, name: string}>
     */
    private function orgStaff(): array
    {
        $orgId = app(TenantContext::class)->id();
        if ($orgId === null) {
            return [];
        }

        $memberIds = Membership::query()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', $memberIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u): array => ['id' => (string) $u->id, 'name' => (string) $u->name])
            ->all();
    }
}
