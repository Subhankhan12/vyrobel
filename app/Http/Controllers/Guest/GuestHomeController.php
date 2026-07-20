<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Support\Guest\GuestName;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * A MINIMAL authenticated guest landing (Phase 4a) — proof that a claimed guest is logged in on the GUEST guard.
 * Resolves the guest from the guard (never a request id); the real Guest Portal (Trips, bookings, Account) is
 * built on this in Phase 4b/4c. An unauthenticated visitor goes to the GUEST login (the guest realm never falls
 * back to the operator/owner login).
 */
class GuestHomeController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $guest = Auth::guard('guest')->user();
        if (! $guest instanceof Guest) {
            return redirect()->route('guest.login');
        }

        return view('guest.home', ['name' => GuestName::display($guest)]);
    }
}
