<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\MessageThread;
use App\Models\User;
use App\Services\InboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Unified Inbox writes — the REAL reply action driven from the (unstyled) thread view via
 * POST → controller → {@see InboxService::sendOutbound} → redirect (PRG), the same tenant-safe pattern
 * as the other operator mutations. A normal POST re-runs the web middleware (SetTenantForWebRequest sets
 * context), so the write is genuinely tenant-scoped; `{thread}` is resolved via the tenant-scoped global
 * scope AFTER the middleware — a cross-org id 404s.
 *
 * NOT a fake button: it sends a real outbound over the thread's channel. On the manual channel the reply
 * is recorded delivered; on a DEFERRED channel the adapter throws "not configured" — the outbound is left
 * as an honest `failed` record (Step 1) and the operator sees a clean flash, never a faked send.
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

            return redirect()->route('app.inbox.show', $model->id)
                ->with('inbox_error', 'Couldn’t send — '.$e->getMessage());
        }

        // Replying implies the operator has read the thread — clear the unread counter.
        $inbox->markRead($model);

        return redirect()->route('app.inbox.show', $model->id)
            ->with('inbox_notice', 'Reply sent.');
    }
}
