<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest\Concerns;

use App\Models\Guest;
use Illuminate\Support\Facades\Auth;

/**
 * The single guest-resolution point for the guest portal (Phase 4b). 🔒 GUEST ISOLATION lives here: every guest
 * controller resolves the subject as the authenticated GUEST-guard user — NEVER a request id — so a guest can only
 * ever reach their OWN record/bookings. A booking is then found THROUGH this guest's own relations
 * ({@see Guest::reservations()}), so a foreign id simply doesn't resolve → 404.
 */
trait ResolvesGuest
{
    /** SELF — the authenticated guest, from the guard. The `auth:guest` middleware guarantees one is present. */
    protected function guest(): Guest
    {
        $guest = Auth::guard('guest')->user();
        abort_unless($guest instanceof Guest, 403);

        return $guest;
    }
}
