<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Unified Inbox — thread LIST (design pass · Inbox UI). Styled from the catalogued "Operator
 * Inbox" design onto the SAME operator design system as the other screens (operator layout,
 * <x-operator.shell>, FormatsOperatorData — reused, never redefined). Presentation + a thin read: the
 * list only READS; no service/schema/guarantee is touched.
 *
 * TENANT-scoped automatically (MessageThread is BelongsToOrganization) — one org never sees another's
 * threads.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — each thread's real fields: guest NAME, the real `channel` (badge keyed to the enum), the
 *                real last-message preview, unread state + count, status, and the linked-reservation tag
 *                ONLY when the thread is genuinely linked. Assignee shown when set.
 *   • DERIVED  — the summary chips + the filter facets (All/Unread/Assigned-to-me/Closed + channel), from
 *                the real rows only (Alpine filters the rendered rows client-side).
 *   • PII      — guest NAME only (front-desk precedent); the encrypted `participant_identity`/contact is
 *                NEVER rendered (invariant #25).
 *
 * The view receives:
 *   viewer: array{name: string, initials: string}
 *   threads: list<array<string, mixed>>
 *   summary: array{unread: int, open: int, mine: int, closed: int}
 *   channels: list<array{value: string, label: string}>
 */
#[Layout('components.layouts.operator')]
class InboxList extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        $viewerId = (string) (auth()->id() ?? '');

        $models = MessageThread::query()
            ->with([
                'guest',
                'owner',   // Wave 3 — an owner thread's counterparty (guest_id XOR owner_id)
                'reservation.reservationUnits.unit',
                'assignedTo',
                'messages' => fn ($q) => $q->latest()->limit(1),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        $threads = $models->map(function (MessageThread $thread) use ($viewerId): array {
            /** @var Message|null $last */
            $last = $thread->messages->first();
            $channel = $this->channelMeta((string) $thread->channel);
            $guest = $this->displayName($thread);
            $assignee = $thread->assignedTo;
            $mine = $viewerId !== '' && (string) $thread->assigned_to === $viewerId;
            $preview = $last !== null ? Str::limit((string) $last->body, 90) : 'No messages yet';

            return [
                'id' => (string) $thread->id,
                'guest' => $guest,
                'initials' => $this->initials($guest),
                'channel_label' => $channel['label'],
                'channel_short' => $channel['short'],
                'channel_tone' => $channel['tone'],
                'channel_key' => (string) $thread->channel,
                'unread' => (int) $thread->unread_count,
                'is_unread' => $thread->unread_count > 0,
                'status' => (string) $thread->status,
                'mine' => $mine,
                'preview' => $preview,
                'time' => $this->shortTime($thread->last_message_at),
                'reservation' => $this->reservationTag($thread),
                'assignee' => $assignee instanceof User ? (string) $assignee->name : null,
                'assignee_initials' => $assignee instanceof User ? $this->initials((string) $assignee->name) : null,
                // A lowercase blob for the client-side search box (name + preview + channel) — no PII beyond the name.
                'search' => Str::lower($guest.' '.$preview.' '.$channel['label']),
            ];
        })->all();

        return view('livewire.inbox-list', [
            'viewer' => $this->viewer(),
            'threads' => $threads,
            // Chips match the tab filters exactly (unread/mine are the ACTIVE, non-closed views).
            'summary' => [
                'unread' => $models->where('status', '!=', 'closed')->where('unread_count', '>', 0)->count(),
                'open' => $models->where('status', 'open')->count(),
                'mine' => $viewerId !== '' ? $models->where('status', '!=', 'closed')->where('assigned_to', $viewerId)->count() : 0,
                'closed' => $models->where('status', 'closed')->count(),
            ],
            'channels' => $models->pluck('channel')->unique()->values()
                ->map(fn (string $c): array => ['value' => $c, 'label' => $this->channelMeta($c)['label']])
                ->all(),
        ]);
    }

    /** Guest NAME (front-desk precedent), else the last sender label — NEVER the encrypted identity. */
    private function displayName(MessageThread $thread): string
    {
        // WAVE 3 — an owner↔operator thread has NO guest (`guest_id` XOR `owner_id`). Before this it fell
        // through to "Unknown sender", which was both ugly and WRONG: the owner is the most precisely-known
        // counterparty in the system. The operator MUST see these threads and be able to reply, or an owner
        // messages into a void — so naming them is part of closing the loop, not cosmetics.
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

    /** "{ref} · {unit}" ONLY when the thread is genuinely linked to a reservation; else null. */
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

    /** A compact last-activity stamp: time today, weekday this week, else a short date. */
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
}
