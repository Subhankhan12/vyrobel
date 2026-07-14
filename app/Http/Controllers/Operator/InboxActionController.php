<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MessageThread;
use App\Models\User;
use App\Services\InboxService;
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

    /** Assign (or unassign) the thread to an active member of the org. */
    public function assign(Request $request, InboxService $inbox, string $thread): RedirectResponse
    {
        $validated = $request->validate(['user_id' => ['nullable', 'string']]);

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

    /** Link the thread to one of the guest's reservations (tenant-scoped — a foreign id 404s). */
    public function linkReservation(Request $request, InboxService $inbox, string $thread): RedirectResponse
    {
        $validated = $request->validate(['reservation_id' => ['nullable', 'string']]);

        $model = MessageThread::query()->findOrFail($thread);
        $inbox->linkReservation($model, $validated['reservation_id'] ?? null);   // findOrFail inside → cross-org 404

        return $this->back($model, 'inbox_notice', 'Reservation linked.');
    }

    private function back(MessageThread $model, string $key, string $message): RedirectResponse
    {
        return redirect()->route('app.inbox.show', $model->id)->with($key, $message);
    }
}
