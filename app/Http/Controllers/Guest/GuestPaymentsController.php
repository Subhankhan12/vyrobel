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
 * 💳 CARD CHARGE IS LIVE (test-mode-first, Phase 8): paying a balance by card goes through hosted Stripe Checkout
 * ({@see GuestFolioPaymentController}) — the card NEVER touches Vyrobel (SAQ A), fulfilled on the webhook. The
 * wireframe's SAVED-CARD vault ("Card on file · Expires 04/27") + Apple Pay are REFUSED (#39/#41/#28): no card is
 * stored; an honest no-stored-card state is shown. 🔒 Every booking here is one the guest leads (guest guard).
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
