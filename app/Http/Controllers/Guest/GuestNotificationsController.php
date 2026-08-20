<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Http\Controllers\NotificationController;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * GUEST NOTIFICATIONS · GATE 3 — the guest's own notification FEED screen.
 *
 * ⚠️ READ-ONLY. The mark-read / dismiss / mark-all WRITES are not here: they reuse the shared
 * {@see NotificationController} through the Gate-1 guest routes, which sit inside
 * `auth:guest` so `$request->user()` resolves to the Guest (Gate 1 established that; the belief that it is
 * null for a guest was false). One write path for three realms (§N.8f).
 *
 * 🔒 SELF-ONLY (#28) — the feed is built by {@see NotificationFeed}, which reads strictly through the
 * notifiable's own relation. The Guest is resolved from the GUARD via {@see ResolvesGuest}, never from a
 * request id, so another guest's rows are not reachable from here — the same isolation the guest audit proved
 * for reservations, receipts and threads.
 *
 * ⚠️ The feed BUILDER is shared with the operator and owner screens (widened to `User|Guest` in Gate 1), so
 * the three feeds cannot drift in shape, ordering, counts or the dismissed-row rule.
 */
class GuestNotificationsController extends Controller
{
    use ResolvesGuest;

    public function index(Request $request): View
    {
        $filter = (string) $request->query('filter', 'all');

        return view('guest.notifications', [
            'feed' => NotificationFeed::forUser($this->guest(), $filter),
            'filter' => $filter,
        ]);
    }
}
