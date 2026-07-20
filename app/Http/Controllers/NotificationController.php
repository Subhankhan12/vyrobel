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
}
