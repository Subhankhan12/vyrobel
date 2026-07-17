<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Scopes\OwnerScope;
use App\Models\User;
use App\Services\InboxService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner portal · ONE CONVERSATION (Wave 3) — the transcript + the reply composer.
 *
 * RMB RULE (invariant #20): `{thread}` is a plain STRING prop that Livewire fills from the route param and
 * that render() resolves via an OWNER-scoped findOrFail — AFTER SetOwnerScopeForPortal. Never a
 * route-model-bound typed prop (RMB resolves during SubstituteBindings, i.e. BEFORE the scope middleware →
 * leak). A cross-owner or guest thread therefore 404s. ⚠️ The `Id` suffix is load-bearing, not styling:
 * Livewire injects public props into the view scope, so a prop named `$thread` would SHADOW the
 * `'thread' => [...]` array render() passes and `$thread['name']` would fatal on a string offset.
 *
 * ⚠️ THE TRANSCRIPT IS MIRRORED — and that is the model being right. `messages.direction` is ORG-RELATIVE
 * (`inbound` = into the organization), so on an owner thread the OWNER's own messages are the `inbound` ones
 * and the staff replies are `outbound`. The operator inbox reads the very same rows the other way up. One
 * column, two correct perspectives; no per-surface duplicate to drift.
 *
 * Opening the thread marks it read FOR THE OWNER — {@see InboxService::markReadByOwner()}, which touches only
 * `owner_last_read_at`. It must never call `markRead()`: that zeroes `unread_count`, the OPERATOR's counter,
 * and would silently clear their badge for work they have not seen.
 *
 * Honesty triage — the omissions and their reasons live in {@see OwnerMessages}'s docblock (they are the same
 * design). The ones that bite hardest here: there is no `read` value in `messages.delivery_status`, so the
 * design's "✓✓ Read" is an INVENTED enum value and is not rendered; and its typing indicator, per-message
 * pin/star/react row and attachments are all local/localStorage/blob fictions.
 *
 * ⚠️ DELIVERY STATUS IS RENDERED HONESTLY, INCLUDING `failed` — which the design omits entirely even though
 * it is the one real state the operator inbox surfaces (invariant #24). On the `manual` channel recording IS
 * the delivery, so a failure is not expected — but if one is ever recorded, the owner sees it rather than a
 * comfortable fiction.
 */
#[Layout('components.layouts.owner')]
class OwnerMessageThread extends Component
{
    use FormatsOwnerData;

    /** The `{thread}` route param — a plain STRING id. See the class docblock: the `Id` suffix is load-bearing. */
    public string $threadId = '';

    public function mount(string $thread): void
    {
        $this->threadId = $thread;
    }

    public function render(): View
    {
        // OWNER-scoped by OwnerScope (MessageThread is BelongsToOwner) → a cross-owner thread, and any
        // operator↔guest thread (owner_id NULL), 404 here. Resolved AFTER the scope middleware, never RMB.
        $thread = MessageThread::query()->findOrFail($this->threadId);

        // Opening it is the owner reading it. Only `owner_last_read_at` moves.
        app(InboxService::class)->markReadByOwner($thread);

        $messages = Message::query()
            ->where('thread_id', $thread->getKey())
            ->with('author')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return view('livewire.owner.message-thread', [
            'viewer' => $this->viewer(),
            'thread' => $this->threadCard($thread, $messages),
            'messages' => $this->transcript($messages),
        ]);
    }

    /**
     * @param  Collection<int, Message>  $messages
     * @return array<string, mixed>
     */
    private function threadCard(MessageThread $thread, $messages): array
    {
        // The staff member the owner is corresponding with — a REAL user, from the last staff reply.
        $reply = $messages->last(fn (Message $message): bool => $message->direction === 'outbound' && $message->author_user_id !== null);
        $author = $reply?->author;

        $name = $author instanceof User ? (string) $author->name : 'Your management team';
        $role = $author instanceof User && $author->getRoleNames()->isNotEmpty()
            ? (string) $author->getRoleNames()->first()
            : 'No one has replied yet';

        return [
            'id' => (string) $thread->getKey(),
            'name' => $name,
            // The design's "Property manager · Seaside Villas" is free text with no staff↔property model
            // behind it. This is the real back-office role from the catalog, and nothing more is claimed.
            'role' => $role,
            'initials' => $this->initials($name),
            'first_name' => explode(' ', $name)[0],
            'subject' => $thread->subject,
        ];
    }

    /**
     * The transcript, MIRRORED for the owner (see the class docblock) with day separators.
     *
     * @param  Collection<int, Message>  $messages
     * @return list<array<string, mixed>>
     */
    private function transcript($messages): array
    {
        $rows = [];
        $lastDay = null;

        foreach ($messages as $message) {
            $at = $message->created_at !== null ? CarbonImmutable::parse($message->created_at) : null;
            $day = $at?->toDateString();

            $rows[] = [
                'id' => (string) $message->getKey(),
                // ⚠️ MIRRORED: `inbound` = into the org = the OWNER's own message.
                'mine' => $message->direction === 'inbound',
                'body' => (string) $message->body,
                'author' => $message->direction === 'inbound'
                    ? 'You'
                    : ($message->author instanceof User ? (string) $message->author->name : ((string) $message->sender_label ?: 'Your management team')),
                'time' => $at?->format('H:i') ?? '',
                // A separator only when the day changes — computed here so the view stays declarative.
                'day_label' => $day !== $lastDay && $at !== null ? $this->dayLabel($at) : null,
                // Honest delivery state, `failed` included (invariant #24) — shown only on the owner's OWN
                // messages, and only when it is not the boring happy path.
                'failed' => $message->direction === 'inbound' && $message->delivery_status === 'failed',
            ];

            $lastDay = $day;
        }

        return $rows;
    }

    /**
     * "Today" / "Yesterday" / "1 October".
     *
     * ⚠️ SERVER time, matching every other day-boundary in the app (ReservationStage, FrontDesk). CLAUDE.md
     * puts guest-facing day logic in the PROPERTY's timezone and none of them honour it yet; this is
     * deliberately consistent with what ships rather than silently divergent. (The design hard-codes
     * `TODAY = '2025-10-09'`, so its own "Today"/"Yesterday" branches never fire and its rail shows a bare
     * `10:18` for a message its transcript dates 8 days earlier.) When it is fixed, fix them together.
     */
    private function dayLabel(CarbonImmutable $at): string
    {
        $today = CarbonImmutable::today();

        if ($at->isSameDay($today)) {
            return 'Today';
        }

        if ($at->isSameDay($today->subDay())) {
            return 'Yesterday';
        }

        return $at->format('j F');
    }
}
