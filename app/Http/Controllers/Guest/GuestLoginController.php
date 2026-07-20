<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\GuestLoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Guest login / logout (Phase 4a · Sub-gate 3) — session auth on the GUEST guard ONLY. On success the session is
 * regenerated (fixation protection) and the guest lands in their area; logout invalidates the guest session and
 * leaves any operator/owner `web` session untouched (the realms are independent).
 */
class GuestLoginController extends Controller
{
    /** The "Welcome back / sign in to manage your stays" screen. */
    public function create(): View
    {
        return view('guest.auth.login');
    }

    /** Authenticate on the guest guard (rate-limited in the form request) → the guest area. */
    public function store(GuestLoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();   // fixation protection

        // Always land on the guest's Trips — NOT redirect()->intended(), whose session('url.intended') is shared
        // across guards and could carry a stale /app or /owner target into the guest realm.
        return redirect()->route('guest.trips');
    }

    /** Sign out of the GUEST guard only. */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('guest')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('guest.login');
    }
}
