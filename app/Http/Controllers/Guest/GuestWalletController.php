<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Exceptions\Guest\WalletOverdrawException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\GuestCreditEntry;
use App\Models\Reservation;
use App\Services\GuestWalletService;
use App\Support\Guest\GuestPayments;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Guest Wallet (Phase 4c · Sub-gate 2) — the guest's credit balance (DERIVED from the append-only ledger), the
 * added/used history, redeem-a-code, and applying credit to a booking.
 *
 * 🔒 ISOLATION: everything resolves from the GUEST guard — the wallet's owner is the authed guest ({@see guest()}),
 * so a guest can only ever see/spend their OWN wallet; a foreign booking (for an apply) → 404. No overdraw, no
 * spending another guest's credit.
 */
class GuestWalletController extends Controller
{
    use ResolvesGuest;

    public function __construct(private readonly GuestWalletService $wallet) {}

    public function show(): View
    {
        $guest = $this->guest();
        $now = CarbonImmutable::now();
        $entries = $this->wallet->entries($guest);
        $currency = (string) ($entries->first()->currency ?? 'EUR');

        return view('guest.wallet', [
            'spendable' => MoneyFormatter::display($this->wallet->spendableMinor($guest), $currency),
            'spendable_minor' => $this->wallet->spendableMinor($guest),
            'entries' => $entries->map(fn (GuestCreditEntry $e): array => [
                'label' => $this->typeLabel($e),
                'description' => (string) $e->description,
                'signed_amount' => ((string) $e->direction === GuestCreditEntry::DIRECTION_DEBIT ? '−' : '+').MoneyFormatter::display((int) $e->amount_minor, (string) $e->currency),
                'is_debit' => (string) $e->direction === GuestCreditEntry::DIRECTION_DEBIT,
                'date' => CarbonImmutable::parse($e->posted_at ?? $e->created_at)->format('j M Y'),
                'expired' => $e->expires_at !== null && $e->expires_at->lessThanOrEqualTo($now),
                'expires' => $e->expires_at !== null ? $e->expires_at->format('j M Y') : null,
            ])->all(),
            // Owed bookings the guest can apply credit to (real folio balances via the shared overview).
            'owed' => collect(GuestPayments::overview($guest, $now)['bookings'])->filter(fn (array $b): bool => $b['balance_due'])->values()->all(),
        ]);
    }

    public function redeem(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64']]);
        $result = $this->wallet->redeemCode($this->guest(), (string) $data['code']);

        return redirect()->route('guest.wallet')->with($result['ok'] ? 'status' : 'error', $result['message']);
    }

    public function apply(Request $request, string $reservation): RedirectResponse
    {
        $model = $this->owned($reservation);
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0']]);
        $amountMinor = (int) round(((float) $data['amount']) * 100);

        try {
            $this->wallet->applyToBooking($this->guest(), $model, Money::of($amountMinor, (string) $model->currency));
        } catch (WalletOverdrawException) {
            return redirect()->route('guest.wallet')->with('error', 'That’s more than you can apply — check your balance and the amount owed.');
        }

        return redirect()->route('guest.wallet')->with('status', 'Credit applied to your booking.');
    }

    /** The authed guest's OWN reservation, or 404 — resolved through their own relation (never a request id). */
    private function owned(string $reservation): Reservation
    {
        return $this->guest()->reservations()->findOrFail($reservation);
    }

    private function typeLabel(GuestCreditEntry $entry): string
    {
        return match ((string) $entry->type) {
            GuestCreditEntry::TYPE_REFUND => 'Refund credit',
            GuestCreditEntry::TYPE_ADD_ON_CANCELLED => 'Add-on cancelled',
            GuestCreditEntry::TYPE_REFERRAL => 'Referral bonus',
            GuestCreditEntry::TYPE_LOYALTY => 'Loyalty reward',
            GuestCreditEntry::TYPE_BIRTHDAY => 'Birthday credit',
            GuestCreditEntry::TYPE_PROMO => 'Promo code',
            GuestCreditEntry::TYPE_APPLIED => 'Applied to booking',
            GuestCreditEntry::TYPE_REVERSAL => 'Reversed',
            default => 'Adjustment',
        };
    }
}
