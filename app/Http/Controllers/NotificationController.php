<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Mark-read writes for the in-app notification feed (Phase 3 · Sub-gate 1). SELF-ONLY by construction: every
 * write goes through `$request->user()->notifications()` / `->unreadNotifications`, so an actor can only ever
 * mark their OWN notifications — a cross-actor id simply doesn't resolve (404). PRG back to the referring feed.
 *
 * ⚠️ SHARED BY ALL THREE REALMS — operator, owner AND guest (guest notifications · GATE 1). The guest realm
 * reuses this controller rather than forking one (§N.8f), and `$request->user()` is correct for it: Laravel's
 * `Authenticate` middleware calls `shouldUse($guard)` for the guard that succeeded, so inside an `auth:guest`
 * route the DEFAULT guard is `guest` and `$request->user()` returns the {@see Guest}. Guest is a
 * `Notifiable` on the same polymorphic `notifications` table, so every query here works unchanged.
 *
 * ⚠️⚠️ THAT MAKES THE ROUTE'S GUARD MIDDLEWARE LOAD-BEARING FOR ISOLATION, not merely for access. This
 * controller derives WHO you are from the request's resolved guard — so a route pointing here MUST sit behind
 * the middleware for the realm it serves. A guest route placed outside `auth:guest` would resolve
 * `$request->user()` on the `web` guard, handing a signed-in operator a guest's mark-read endpoint.
 * `GuestNotificationsRouteGuardTest` pins that the guest routes carry `auth:guest`, so the implicit this
 * reuse depends on is a CHECKED invariant rather than a convention someone could quietly break.
 */
class NotificationController extends Controller
{
    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return redirect()->back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->back();
    }

    /**
     * D29-5 + DO6-4 — DISMISS one notification: take it off this user's feed.
     *
     * ⚠️ DISMISS IS NOT MARK-READ. Read says "I have seen this" and the row STAYS; dismiss says "take this
     * off my list" and the row LEAVES. They are separate columns and can coexist — a notification may be read
     * and dismissed, or dismissed while still unread. Overloading `read_at` would have made the two acts
     * indistinguishable and silently hidden everything a user merely glanced at.
     *
     * ⚠️ AND THIS IS NOT `/owner/attention/dismiss`. That writes `owner_attention_states` — the owner's
     * "needs attention" SIGNAL, a different entity that happens to have a same-named column. This method
     * touches `notifications` and nothing else; dismissing a notification must never clear an attention
     * signal, and `NotificationDismissTest` asserts that boundary rather than trusting it.
     *
     * ⚠️ SELF-ONLY BY CONSTRUCTION, exactly like the mark-read above: the write goes through
     * `$request->user()->notifications()`, so another user's id simply does not resolve (404). The scoping is
     * a property of the query, not a check that could be forgotten.
     */
    public function dismiss(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($notification)
            ->forceFill(['dismissed_at' => now()])->save();

        return redirect()->back();
    }
}
