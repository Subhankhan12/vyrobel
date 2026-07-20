<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Support\Guest\GuestName;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * A MINIMAL authenticated guest landing (Phase 4a · Sub-gate 2) — proof that a claimed guest is logged in on the
 * GUEST guard. Resolves the guest from the guard (never a request id); the real Guest Portal (Trips, bookings,
 * Account) is built on this in Phase 4b/4c. Redirects an unauthenticated visitor to the claim page (the guest
 * realm never falls back to the operator/owner login).
 */
class GuestHomeController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $guest = Auth::guard('guest')->user();
        if (! $guest instanceof Guest) {
            return redirect()->route('guest.claim');
        }

        return view('guest.home', ['name' => GuestName::display($guest)]);
    }

    /** Sign the guest out of the GUEST guard only (the web session is untouched). */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('guest')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('guest.claim');
    }
}
