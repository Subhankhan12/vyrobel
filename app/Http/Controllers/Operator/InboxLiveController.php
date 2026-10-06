<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * THE GUESTS SIDE, LIVE (2026-10-06, `docs/TEAM-CHAT-DESIGN.md` §6) — a small stamp the open Inbox asks every few
 * seconds: it changes when anything the page shows changes (a message in, a reply out, a conversation closed, assigned
 * or read). When it moves, the page reads itself again and swaps its list, its counts and the open conversation's
 * messages in place — never the reply being written. Behind the Inbox's own permission (`guests.view`, on the route).
 * Read-only: one aggregate over this organisation's conversations, and one over the open conversation's messages.
 */
final class InboxLiveController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $all = MessageThread::query()
            ->selectRaw('count(*) as n, max(last_message_at) as last, sum(unread_count) as unread, max(updated_at) as changed')
            ->first();
        $open = null;
        $c = $request->query('c');
        if (is_string($c) && $c !== '') {
            $thread = MessageThread::query()->find($c);   // this organisation's — another's is simply not there
            if ($thread instanceof MessageThread) {
                $open = [(string) $thread->updated_at, (string) Message::query()->where('thread_id', $thread->getKey())->max('updated_at'),
                    Message::query()->where('thread_id', $thread->getKey())->count()];
            }
        }

        return response()->json([
            'stamp' => substr(sha1((string) json_encode([$all?->getAttributes(), $open])), 0, 16),
        ]);
    }
}
