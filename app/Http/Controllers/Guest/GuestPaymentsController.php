<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Support\Guest\GuestPayments;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

/**
 * Guest Payments hub (Phase 4c · Sub-gate 1) — the authed guest's balance across THEIR OWN bookings + payment
 * history, from the real folio ledger via {@see GuestPayments} (reconciling to the operator/owner figures).
 *
 * 🪤 CARD CHARGE DEFERRED: paying a balance by card / Apple Pay is the account-gated Stripe rail (the same
 * deferral as New-Reservation card capture) — the screen shows the balance and an honest "Soon", never a faked
 * payment and never a raw PAN. 🔒 Every booking here is one the guest leads (resolved from the guest guard).
 */
class GuestPaymentsController extends Controller
{
    use ResolvesGuest;

    public function index(): View
    {
        return view('guest.payments', [
            'overview' => GuestPayments::overview($this->guest(), CarbonImmutable::today()),
        ]);
    }
}
