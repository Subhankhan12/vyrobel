<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Legacy guest landing (Phase 4a) — superseded by Guest Trips (Phase 4b), which is now the post-login landing.
 * Kept as a permanent redirect so any old `/guest/home` link resolves to the real portal home.
 */
class GuestHomeController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('guest.trips');
    }
}
