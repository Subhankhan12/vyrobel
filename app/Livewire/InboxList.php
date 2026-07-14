<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Message;
use App\Models\MessageThread;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Unified Inbox — thread LIST. UNSTYLED contract stub (the styled screen is a later design
 * pass, same as the original Phase-1 stubs). READS only, TENANT-scoped (MessageThread is
 * BelongsToOrganization, so an org sees only its own threads). Documents the data shape the future
 * styled screen will consume.
 *
 * PII: shows the guest NAME (front-desk precedent) + a last-message preview; NEVER the encrypted
 * `participant_identity`/contact.
 *
 * Data shape — `threads`: list<array{
 *   id: string, channel: string, guest: string, unread: int, status: string,
 *   preview: string, last_at: string|null, reservation: string|null,
 * }>.
 */
#[Layout('components.layouts.app')]
class InboxList extends Component
{
    public function render(): View
    {
        $threads = MessageThread::query()
            ->with(['guest', 'reservation', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (MessageThread $thread): array {
                /** @var Message|null $last */
                $last = $thread->messages->first();

                return [
                    'id' => (string) $thread->id,
                    'channel' => (string) $thread->channel,
                    'guest' => $this->displayName($thread),
                    'unread' => (int) $thread->unread_count,
                    'status' => (string) $thread->status,
                    'preview' => $last !== null ? Str::limit((string) $last->body, 80) : '—',
                    'last_at' => $thread->last_message_at?->toDayDateTimeString(),
                    'reservation' => $thread->reservation?->reference,
                ];
            })->all();

        return view('livewire.inbox-list', ['threads' => $threads]);
    }

    /**
     * A non-PII display label for the conversation: the linked guest's NAME, else the last sender's
     * label — NEVER the encrypted participant identity/contact.
     */
    private function displayName(MessageThread $thread): string
    {
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
}
