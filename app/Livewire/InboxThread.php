<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\Operator\InboxActionController;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use App\Services\InboxService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Unified Inbox — thread VIEW (message history + reply box). UNSTYLED contract stub. READS
 * only, TENANT-scoped.
 *
 * RMB rule: `{thread}` is a plain string prop resolved in render() via a tenant-scoped `findOrFail`
 * (after SetTenantForWebRequest) — a cross-org id 404s. Never a route-model-bound prop.
 *
 * The reply box is NOT a fake button: it POSTs to {@see InboxActionController}
 * which sends a REAL outbound via {@see InboxService::sendOutbound} (the manual channel now;
 * a deferred channel surfaces an honest failed state). PII: guest NAME + the conversation are shown;
 * the encrypted `participant_identity`/contact is NEVER rendered.
 *
 * Data shape — `thread`: array{id, channel, guest, status, reservation, subject};
 *   `messages`: list<array{id, direction, body, author, delivery_status, at}>.
 */
#[Layout('components.layouts.app')]
class InboxThread extends Component
{
    public string $threadId;

    public function mount(string $thread): void
    {
        $this->threadId = $thread;
    }

    public function render(): View
    {
        // Tenant-scoped: a cross-org (or unknown) id resolves to nothing → 404.
        $thread = MessageThread::query()
            ->with(['guest', 'reservation'])
            ->findOrFail($this->threadId);

        $messages = Message::query()
            ->with('author')
            ->where('thread_id', $thread->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $m): array => [
                'id' => (string) $m->id,
                'direction' => (string) $m->direction,
                'body' => (string) $m->body,
                'author' => $m->direction === 'outbound'
                    ? ($m->author instanceof User ? (string) $m->author->name : ($m->sender_label ?? 'Operator'))
                    : $this->guestName($thread),
                'delivery_status' => (string) $m->delivery_status,
                'at' => $m->created_at?->toDayDateTimeString(),
            ])->all();

        return view('livewire.inbox-thread', [
            'thread' => [
                'id' => (string) $thread->id,
                'channel' => (string) $thread->channel,
                'guest' => $this->guestName($thread),
                'status' => (string) $thread->status,
                'reservation' => $thread->reservation?->reference,
                'subject' => $thread->subject,
            ],
            'messages' => $messages,
        ]);
    }

    /** Guest NAME only (front-desk PII precedent) — never the encrypted participant identity/contact. */
    private function guestName(MessageThread $thread): string
    {
        $guest = $thread->guest;
        if ($guest === null) {
            return 'Unknown sender';
        }

        $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name));

        return $name !== '' ? $name : 'Unknown sender';
    }
}
