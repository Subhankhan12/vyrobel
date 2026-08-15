<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Mark-read writes for the in-app notification feed (Phase 3 · Sub-gate 1). SELF-ONLY by construction: every
 * write goes through `$request->user()->notifications()` / `->unreadNotifications`, so a user can only ever mark
 * their OWN notifications — a cross-user id simply doesn't resolve (404). Shared by the operator + owner feeds
 * (both authenticated); PRG back to the referring feed.
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
