<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\MessageThread;
use App\Models\User;
use App\Support\Notifications\NotificationFeed;
use App\Support\Platform\BreakGlass;
use App\Support\TeamChat\TeamChatReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

/**
 * THE PULSE (2026-10-06, `docs/TEAM-CHAT-DESIGN.md`) — what every back-office page asks every 20 seconds so the bell, the
 * Messages badge and the chime are live: the person's unread notifications (the bell's own count), their unread team
 * messages, and — only for someone who may read them (`guests.view`) — guest and owner conversations waiting to be read.
 * Counts only: never a name, never a message. Read-only (a support session's GETs never write).
 */
final class PulseController extends Controller
{
    public function __invoke(Request $request, TeamChatReader $reader): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $support = BreakGlass::hasStash($request);

        $out = [
            // the bell's own count — the feed's one rule (it leaves team chat out of a support session)
            'notifications' => NotificationFeed::unreadCount($user),
            'team' => $support ? 0 : $reader->unreadTotal($user),
            'guests' => $user->can('guests.view')
                ? MessageThread::query()->where('unread_count', '>', 0)->where('status', '!=', 'closed')->count()
                : null,
        ];
        // when the count moved, the page asks for the bell itself — the SAME component the shell draws, so its list is
        // never stale after a chime (one definition of the bell, never a second copy in the script)
        if ($request->boolean('bell')) {
            $out['bell'] = Blade::render('<x-notifications.bell :open-all-url="$url" />', ['url' => route('app.notifications')]);
        }

        return response()->json($out);
    }
}
