<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Media;
use App\Models\Membership;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Reservation;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Inbox\MessageAttachments;
use App\Support\Inbox\MessageTemplate;
use App\Support\Messaging\ChatFiles;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

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
    /**
     * Reply — text, and (2026-10-06) pictures, documents and a voice note where the other side can see them: an owner's
     * conversation (their portal) or a guest's Direct one (their guest account). A reply may be a file alone. Sent by the
     * page without a reload it answers in JSON (201, or 422 in words); as a plain form it redirects back, as before.
     */
    public function reply(Request $request, InboxService $inbox, MessageAttachments $attachments, string $thread): RedirectResponse|JsonResponse
    {
        $model = MessageThread::query()->findOrFail($thread);   // tenant-scoped → cross-org 404

        if ($this->paused()) {
            return $this->answer($request, $model, $this->pausedMessage(), false);
        }

        // the size this server takes (ours, or PHP's own ceiling when lower) — and said in those words
        $mb = ChatFiles::limits()['mb'];
        $check = Validator::make($request->all(), [
            'body' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:'.ChatFiles::MAX_FILES],
            'attachments.*' => ['file', 'max:'.($mb * 1024), 'mimes:'.ChatFiles::FILE_EXTENSIONS],
            'voice' => ['nullable', 'file', 'max:'.($mb * 1024), 'mimes:'.ChatFiles::VOICE_EXTENSIONS],
            'voice_seconds' => ['nullable', 'integer'],
        ], [
            'body.max' => __('php/http/controllers/operator/inbox_action_controller.body_max'),
            'attachments.max' => __('php/http/controllers/operator/inbox_action_controller.attachments_max', ['max' => ChatFiles::MAX_FILES]),
            'attachments.*.max' => __('php/http/controllers/operator/inbox_action_controller.attachment_too_big', ['mb' => $mb]),
            'attachments.*.mimes' => __('php/http/controllers/operator/inbox_action_controller.attachment_type'),
            'attachments.*.file' => __('php/http/controllers/operator/inbox_action_controller.attachment_unreadable', ['mb' => $mb]),
            'voice.max' => __('php/http/controllers/operator/inbox_action_controller.voice_too_big', ['mb' => $mb]),
            'voice.mimes' => __('php/http/controllers/operator/inbox_action_controller.voice_unreadable'),
            'voice.file' => __('php/http/controllers/operator/inbox_action_controller.voice_unreadable'),
        ]);
        if ($check->fails()) {
            return $this->answer($request, $model, (string) $check->errors()->first(), false);
        }

        $files = array_values(array_filter((array) $request->file('attachments', []), fn ($f): bool => $f instanceof UploadedFile));
        $voice = $request->file('voice');
        $voice = $voice instanceof UploadedFile ? $voice : null;
        $body = trim((string) $request->input('body', ''));

        if ($body === '' && $files === [] && $voice === null) {
            return $this->answer($request, $model, __('php/http/controllers/operator/inbox_action_controller.reply_empty'), false);
        }
        // a file goes only where the other side can open it — the owner's portal, or the guest's own account (Direct)
        if (($files !== [] || $voice !== null) && $model->owner_id === null && $model->channel !== 'manual') {
            return $this->answer($request, $model, __('php/http/controllers/operator/inbox_action_controller.files_not_allowed'), false);
        }

        $author = $request->user();
        $author = $author instanceof User ? $author : null;

        // ⚠️ The files are kept on the disk FIRST (the independent review, 2026-10-06): the send below is deliberately not
        // in a transaction, so a file refused after it would have left a reply without its files — or, a reply of files
        // alone, an empty message on the other side. A disk that refuses one now means nothing is sent.
        $kept = [];
        try {
            $folder = strtolower((string) Str::ulid());
            foreach ($files as $file) {
                $kept[] = ['file' => $attachments->keep($file, $folder, MessageAttachments::COLLECTION), 'collection' => MessageAttachments::COLLECTION, 'seconds' => null];
            }
            if ($voice !== null) {
                $kept[] = ['file' => $attachments->keep($voice, $folder, MessageAttachments::VOICE), 'collection' => MessageAttachments::VOICE, 'seconds' => ChatFiles::seconds($request->input('voice_seconds'))];
            }
        } catch (Throwable $e) {
            report($e);
            $attachments->discard(array_column($kept, 'file'));

            return $this->answer($request, $model, __('php/http/controllers/operator/inbox_action_controller.file_not_saved'), false);
        }

        try {
            $message = $inbox->sendOutbound($model, $body, $author);
        } catch (RuntimeException $e) {
            // e.g. a deferred channel is not configured — the outbound is recorded FAILED (honest, not
            // faked) and surfaced cleanly, never a 500.
            $attachments->discard(array_column($kept, 'file'));
            $inbox->markRead($model);

            return $this->answer($request, $model, __('php/http/controllers/operator/inbox_action_controller.could_not_send', ['reason' => $e->getMessage()]), false);
        }

        $recorded = [];
        try {
            foreach ($kept as $i => $k) {
                $recorded[] = $attachments->record($message, $k['file'], $k['collection'], $i, $k['seconds']);
            }
        } catch (Throwable $e) {
            // the words went; the files did not — said so, and the page keeps them to send again
            report($e);
            Media::query()->whereKey(array_map(fn (Media $m): string => (string) $m->getKey(), $recorded))->forceDelete();
            $attachments->discard(array_column($kept, 'file'));
            $inbox->markRead($model);

            return $this->answer($request, $model, __('php/http/controllers/operator/inbox_action_controller.files_not_saved_after_send'), true, filesKept: true);
        }

        // Replying implies the operator has read the thread — clear the unread counter.
        $inbox->markRead($model);

        return $this->answer($request, $model, __('php/http/controllers/operator/inbox_action_controller.reply_sent'), true);
    }

    /**
     * The page sent it live → JSON (201, or 422 with the reason); a plain form → back to the conversation with a flash.
     * `filesKept`: the words were sent but the files were not — the page clears the words and keeps the files.
     */
    private function answer(Request $request, MessageThread $model, string $message, bool $ok, bool $filesKept = false): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return $ok ? response()->json(['ok' => true, 'message' => $message, 'files_kept' => $filesKept], 201) : response()->json(['ok' => false, 'message' => $message], 422);
        }

        return $this->back($model, $ok && ! $filesKept ? 'inbox_notice' : 'inbox_error', $message);
    }

    /**
     * Read, on purpose (2026-10-06, the independent review): the open Inbox refreshes itself without reading anything,
     * and says it was read when the person is looking at the conversation — this. The unread counter is shared by the
     * staff, so is its bell entry ({@see InboxService::markRead()}). A support session's POST is refused before here.
     */
    public function read(InboxService $inbox, string $thread): JsonResponse
    {
        $model = MessageThread::query()->findOrFail($thread);   // tenant-scoped → cross-org 404
        $inbox->markRead($model);

        return response()->json(['ok' => true]);
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
                abort(422, __('php/http/controllers/operator/inbox_action_controller.not_a_member'));
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

        return $this->back($model, 'inbox_notice', __('php/http/controllers/operator/inbox_action_controller.assignment_updated'));
    }

    /** Close / snooze / reopen — guarded lifecycle (illegal transition → clean flash, never a 500). */
    public function close(InboxService $inbox, string $thread): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);
        $inbox->close($model);

        return $this->back($model, 'inbox_notice', __('php/http/controllers/operator/inbox_action_controller.conversation_closed'));
    }

    public function reopen(InboxService $inbox, string $thread): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);
        $inbox->reopen($model);

        return $this->back($model, 'inbox_notice', __('php/http/controllers/operator/inbox_action_controller.conversation_reopened'));
    }

    public function snooze(InboxService $inbox, string $thread): RedirectResponse
    {
        $model = MessageThread::query()->findOrFail($thread);

        try {
            $inbox->snooze($model);
        } catch (RuntimeException $e) {
            return $this->back($model, 'inbox_error', $e->getMessage());
        }

        return $this->back($model, 'inbox_notice', __('php/http/controllers/operator/inbox_action_controller.conversation_snoozed'));
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
            return $this->back($model, 'inbox_error', __('php/http/controllers/operator/inbox_action_controller.still_could_not_send', ['reason' => $e->getMessage()]));
        }

        return $this->back($model, 'inbox_notice', __('php/http/controllers/operator/inbox_action_controller.message_resent'));
    }

    /** Link the thread to one of the guest's reservations (tenant-scoped — a foreign id 404s). */
    public function linkReservation(Request $request, InboxService $inbox, string $thread): RedirectResponse
    {
        $validated = $request->validate(['reservation_id' => ['nullable', 'string']]);

        $model = MessageThread::query()->findOrFail($thread);
        $inbox->linkReservation($model, $validated['reservation_id'] ?? null);   // findOrFail inside → cross-org 404

        return $this->back($model, 'inbox_notice', __('php/http/controllers/operator/inbox_action_controller.reservation_linked'));
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
        // "Message" precedent), else open a fresh one linked to their MESSAGING-CONTEXT reservation — the SAME
        // one the composer's meta + pre-merged template referenced (MessageTemplate::contextReservation), so the
        // thread's linked reservation and the sent body can never disagree.
        $reservation = MessageTemplate::contextReservation($guest);
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
                return redirect()->route('app.inbox', ['c' => $thread->id])->with('inbox_error', __('php/http/controllers/operator/inbox_action_controller.could_not_send', ['reason' => $e->getMessage()]));
            }
        }

        return redirect()->route('app.inbox', ['c' => $thread->id])->with('inbox_notice', __('php/http/controllers/operator/inbox_action_controller.conversation_started'));
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
        return __('php/http/controllers/operator/inbox_action_controller.workspace_paused', ['date' => app(OperatorWorkspace::class)->resumeLabel()]);
    }

    private function back(MessageThread $model, string $key, string $message): RedirectResponse
    {
        return redirect()->route('app.inbox', ['c' => $model->id])->with($key, $message);
    }
}
