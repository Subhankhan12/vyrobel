<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\Guest;
use App\Models\Message;
use App\Models\MessageThread;
use App\Services\InboxService;
use App\Support\I18n\LocalDate;
use App\Support\Inbox\MessageAttachments;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Guest Messages (Phase 4c · Sub-gate 3) — the guest side of the EXISTING Unified Inbox, the mirror of the owner
 * inbox. The authed guest sees their OWN thread(s) with the property, sends a real message through
 * {@see InboxService::postGuestMessage()} (never a raw insert / never a reimplementation), and reads the
 * operator's replies. The transcript is MIRRORED: `messages.direction` is org-relative, so the guest's own
 * messages are `inbound` and the staff replies `outbound` — the same rows the operator inbox reads the other way up.
 *
 * 🔒 ISOLATION: a thread resolves ONLY through the authed guest's `guest_id` ({@see owned()}) — never route-model
 * binding. A foreign thread (another guest's, or an owner↔operator thread, which has `owner_id` not `guest_id`) →
 * 404. GDPR: {@see Guest::anonymize()} already redacts these threads' bodies (#25).
 */
class GuestMessagesController extends Controller
{
    use ResolvesGuest;

    public function __construct(private readonly InboxService $inbox) {}

    public function index(): View
    {
        $threads = MessageThread::query()
            ->where('guest_id', $this->guest()->getKey())
            ->whereNull('owner_id')
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn (MessageThread $t): array => [
                'id' => (string) $t->getKey(),
                'subject' => (string) ($t->subject ?? __('php/http/controllers/guest/guest_messages_controller.your_conversation')),
                'at' => $t->last_message_at !== null ? CarbonImmutable::parse($t->last_message_at)->translatedFormat(LocalDate::pattern('j M Y · H:i')) : '',
                'unread' => $t->last_message_at !== null && ($t->guest_last_read_at === null || $t->last_message_at->greaterThan($t->guest_last_read_at)),
            ])
            ->all();

        return view('guest.messages', ['threads' => $threads]);
    }

    public function show(string $thread): View
    {
        $model = $this->owned($thread);
        $this->inbox->markReadByGuest($model);   // guest read position only — never the operator's unread_count

        return view('guest.message-thread', [
            'thread' => (string) $model->getKey(),
            'subject' => (string) ($model->subject ?? __('php/http/controllers/guest/guest_messages_controller.your_conversation')),
            'messages' => $model->messages()->with('media')->orderBy('created_at')->get()->map(fn (Message $m): array => [
                // MIRRORED: `inbound` = into the org = the guest's OWN message.
                'mine' => (string) $m->direction === 'inbound',
                'author' => (string) $m->direction === 'inbound' ? __('php/http/controllers/guest/guest_messages_controller.author_you') : (($m->sender_label !== null && $m->sender_label !== '') ? (string) $m->sender_label : __('php/http/controllers/guest/guest_messages_controller.author_property')),
                'body' => (string) $m->body,
                'at' => CarbonImmutable::parse($m->created_at)->translatedFormat(LocalDate::pattern('j M · H:i')),
                // pictures, documents and voice notes the property sent (2026-10-06) — through this guest's own door
                'files' => app(MessageAttachments::class)->rows($m, 'guest.messages.attachment'),
            ])->all(),
        ]);
    }

    public function send(Request $request, string $thread): RedirectResponse
    {
        $model = $this->owned($thread);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $this->inbox->postGuestMessage($model, trim((string) $data['body']));

        return redirect()->route('guest.messages.show', $model->getKey());
    }

    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $thread = $this->inbox->startThread([
            'channel' => 'manual',
            'guest_id' => $this->guest()->getKey(),
            'subject' => trim((string) $data['subject']),
        ]);
        $this->inbox->postGuestMessage($thread, trim((string) $data['body']));

        return redirect()->route('guest.messages.show', $thread->getKey());
    }

    /** The authed guest's OWN thread, or 404 — resolved through their `guest_id` (never a request id). */
    private function owned(string $thread): MessageThread
    {
        return MessageThread::query()
            ->where('guest_id', $this->guest()->getKey())
            ->whereNull('owner_id')
            ->findOrFail($thread);
    }
}
