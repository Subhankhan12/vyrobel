<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Membership;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Reservation;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Inbox\MessageTemplate;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Unified Inbox writes — the REAL reply + lifecycle actions driven from the styled conversation view via
 * POST → controller → {@see InboxService} → redirect (PRG), the same tenant-safe pattern as the other
 * operator mutations. A normal POST re-runs the web middleware (SetTenantForWebRequest sets context), so
 * every write is genuinely tenant-scoped; `{thread}` is resolved via the tenant-scoped global scope AFTER
 * the middleware — a cross-org id 404s.
 *
 * The reply is NOT a fake button: it sends a real outbound over the thread's channel. On the manual channel
 * the reply is recorded delivered; on a DEFERRED channel the adapter throws "not configured" — the outbound
 * is left as an honest `failed` record (invariant #24) and the operator sees a clean flash, never a faked
 * send, never a 500. Lifecycle actions (assign/close/snooze/reopen) run through the guarded service.
 */
class InboxActionController extends Controller
{
    public function reply(Request $request, InboxService $inbox, string $thread): RedirectResponse
    {
        if ($this->paused()) {
            $model = MessageThread::query()->findOrFail($thread);

            return $this->back($model, 'inbox_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $model = MessageThread::query()->findOrFail($thread);   // tenant-scoped → cross-org 404

        $author = $request->user();
        $author = $author instanceof User ? $author : null;

        try {
            $inbox->sendOutbound($model, $validated['body'], $author);
        } catch (RuntimeException $e) {
            // e.g. a deferred channel is not configured — the outbound is recorded FAILED (honest, not
            // faked) and surfaced cleanly, never a 500.
            $inbox->markRead($model);

            return $this->back($model, 'inbox_error', 'Couldn’t send — '.$e->getMessage());
        }

        // Replying implies the operator has read the thread — clear the unread counter.
        $inbox->markRead($model);

        return $this->back($model, 'inbox_notice', 'Reply sent.');
    }

    /** Assign (or unassign) the thread to an active member of the org — an INTERNAL hand-off (the guest never sees it). */
    public function assign(Request $request, InboxService $inbox, string $thread): RedirectResponse
    {
        if ($this->paused()) {
            $model = MessageThread::query()->findOrFail($thread);

            return $this->back($model, 'inbox_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'string'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $model = MessageThread::query()->findOrFail($thread);   // tenant-scoped → cross-org 404

        $userId = $validated['user_id'] ?? null;
        if ($userId !== null && $userId !== '') {
            $isMember = Membership::query()
                ->where('organization_id', app(TenantContext::class)->id())
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->exists();

            if (! $isMember) {
                abort(422, 'That user is not a member of this organization.');
            }
        } else {
            $userId = null;
        }

        $inbox->assign($model, $userId);

        // The wireframe's assign carries an editable INTERNAL note (staff-only, guests never see it). It is a
        // REAL, persisted note now — never a textarea whose text goes nowhere. Empty → no note.
        $note = trim((string) ($validated['note'] ?? ''));
        if ($note !== '') {
            $inbox->addNote($model, $note, $request->user() instanceof User ? $request->user() : null);
        }

        return $this->back($model, 'inbox_notice', 'Conversation assignment updated.');
    }

    /** Close / snooze / reopen — guarded lifecycle (illegal transition → clean flash, never a 500). */
    public function close(InboxService $inbox, string $thread): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);
        $inbox->close($model);

        return $this->back($model, 'inbox_notice', 'Conversation closed.');
    }

    public function reopen(InboxService $inbox, string $thread): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);
        $inbox->reopen($model);

        return $this->back($model, 'inbox_notice', 'Conversation reopened.');
    }

    public function snooze(InboxService $inbox, string $thread): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);

        try {
            $inbox->snooze($model);
        } catch (RuntimeException $e) {
            return $this->back($model, 'inbox_error', $e->getMessage());
        }

        return $this->back($model, 'inbox_notice', 'Conversation snoozed.');
    }

    /**
     * Retry a FAILED outbound — re-send its body over the channel via the certified sendOutbound. A REAL
     * attempt: it delivers on the manual channel and fails again (honestly) on a deferred one — never the
     * prototype's fabricated "flip to delivered". The message must be a failed outbound OF this thread.
     */
    public function retryMessage(Request $request, InboxService $inbox, string $thread, string $message): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);   // tenant-scoped → cross-org 404

        if ($this->paused()) {
            return $this->back($model, 'inbox_error', $this->pausedMessage());
        }

        $original = Message::query()
            ->where('thread_id', $model->getKey())
            ->where('direction', 'outbound')
            ->where('delivery_status', 'failed')
            ->findOrFail($message);

        $author = $request->user();

        try {
            $inbox->sendOutbound($model, (string) $original->body, $author instanceof User ? $author : null);
        } catch (RuntimeException $e) {
            return $this->back($model, 'inbox_error', 'Still couldn’t send — '.$e->getMessage());
        }

        return $this->back($model, 'inbox_notice', 'Message re-sent.');
    }

    /** Link the thread to one of the guest's reservations (tenant-scoped — a foreign id 404s). */
    public function linkReservation(Request $request, InboxService $inbox, string $thread): RedirectResponse
    {
        $validated = $request->validate(['reservation_id' => ['nullable', 'string']]);

        $model = MessageThread::query()->findOrFail($thread);
        $inbox->linkReservation($model, $validated['reservation_id'] ?? null);   // findOrFail inside → cross-org 404

        return $this->back($model, 'inbox_notice', 'Reservation linked.');
    }

    /**
     * NEW MESSAGE (#90) — start a thread with a real guest who has a current/upcoming (or recent) stay, optionally
     * seeded from a #83 template (pre-arrival / payment-reminder / review-request). The thread is created on the
     * `manual` channel (recorded delivered; the guest sees it in their portal inbox / email) — "the guest replies
     * wherever they booked". A template pre-fills the first message with the guest+reservation merge vars; an empty
     * start just opens the thread. Respects read-only.
     */
    public function start(Request $request, InboxService $inbox): RedirectResponse
    {
        if ($this->paused()) {
            return redirect()->route('app.inbox')->with('inbox_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'guest_id' => ['required', 'string'],
            'template' => ['nullable', 'string', 'in:pre-arrival,payment-reminder,review-request'],
            'body' => ['nullable', 'string', 'max:5000'],
        ]);

        $guest = Guest::query()->findOrFail($validated['guest_id']);   // tenant-scoped → cross-org 404

        // Reuse an existing manual thread for this guest rather than spawning duplicates (the guest-profile
        // "Message" precedent), else open a fresh one linked to their latest reservation.
        $reservation = Reservation::query()->where('lead_guest_id', $guest->getKey())->orderByDesc('check_in')->first();
        $thread = MessageThread::query()->where('guest_id', $guest->getKey())->where('channel', 'manual')->orderByDesc('last_message_at')->first();
        if ($thread === null) {
            $thread = $inbox->startThread([
                'channel' => 'manual',
                'guest_id' => (string) $guest->getKey(),
                'reservation_id' => $reservation?->getKey() !== null ? (string) $reservation->getKey() : null,
                'subject' => 'Conversation',
                'assigned_to' => (string) ($request->user()?->getAuthIdentifier() ?? '') ?: null,
            ]);
        }

        // Seed the first message: an explicit body wins; else the chosen template's merged message; else nothing
        // (just open the thread and let the operator type).
        $body = trim((string) ($validated['body'] ?? ''));
        if ($body === '' && ! empty($validated['template'])) {
            $body = $this->templateBody((string) $validated['template'], $guest, $reservation);
        }

        if ($body !== '') {
            $author = $request->user();
            try {
                $inbox->sendOutbound($thread, $body, $author instanceof User ? $author : null);
            } catch (RuntimeException $e) {
                return redirect()->route('app.inbox', ['c' => $thread->id])->with('inbox_error', 'Couldn’t send — '.$e->getMessage());
            }
        }

        return redirect()->route('app.inbox', ['c' => $thread->id])->with('inbox_notice', 'Conversation started.');
    }

    /** Resolve a #83 template's message with the guest/reservation merge vars — the composer seed (never door codes #33). */
    private function templateBody(string $key, Guest $guest, ?Reservation $reservation): string
    {
        return MessageTemplate::merged($key, $guest, $reservation);   // the ONE merge, shared with the composer preview
    }

    private function paused(): bool
    {
        return app(OperatorWorkspace::class)->isReadOnly();
    }

    private function pausedMessage(): string
    {
        return 'Workspace paused — messaging is read-only until '.app(OperatorWorkspace::class)->resumeLabel().'.';
    }

    private function back(MessageThread $model, string $key, string $message): RedirectResponse
    {
        return redirect()->route('app.inbox', ['c' => $model->id])->with($key, $message);
    }
}
