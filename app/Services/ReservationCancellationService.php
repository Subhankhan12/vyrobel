<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Folio;
use App\Models\Reservation;
use App\Support\Money\Money;
use App\Support\Reservations\CancellationPolicy;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Cancel a reservation AND auto-issue the policy refund (founder decision — "full auto-refund in-window only").
 * When the cancellation falls WITHIN the property's free-cancel window ({@see CancellationPolicy}), everything the
 * guest paid is refunded through the certified, bounded {@see FolioService::refundPayment} (#27, same-currency,
 * gateway = TEST-MODE until go-live). AFTER the window, NO refund is auto-issued — the policy encodes no partial
 * amount, so that stays a manual operator decision (never a fabricated %, #39).
 *
 * Order is load-bearing: cancel FIRST (releases inventory via the certified {@see AssignmentService}; throws if the
 * reservation is already terminal, so the caller never double-refunds), then refund the real payments.
 */
class ReservationCancellationService
{
    public function __construct(
        private readonly AssignmentService $assignment,
        private readonly FolioService $folios,
    ) {}

    /**
     * @return array{cancelled: bool, refunded_minor: int}
     */
    public function cancelWithPolicyRefund(Reservation $reservation, ?string $reason = null, ?CarbonInterface $on = null): array
    {
        $on = $on !== null ? CarbonImmutable::parse($on) : CarbonImmutable::now();
        $refundable = CancellationPolicy::isRefundable($reservation, $on);

        // Throws ReservationAlreadyTerminalException / a booking rejection — the caller handles it (no refund runs).
        $this->assignment->cancelReservation($reservation, $reason);

        $refundedMinor = $refundable ? $this->refundEverythingPaid($reservation) : 0;

        return ['cancelled' => true, 'refunded_minor' => $refundedMinor];
    }

    /** Refund the remaining-refundable of every succeeded payment on the folio (bounded #27; never over-refunds). */
    private function refundEverythingPaid(Reservation $reservation): int
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->with('payments')->first();
        if (! $folio instanceof Folio) {
            return 0;
        }

        $refunded = 0;
        foreach ($folio->payments as $payment) {
            if (! in_array((string) $payment->type, ['payment', 'capture'], true) || (string) $payment->status !== 'succeeded') {
                continue;   // only settled charges are refundable
            }

            $remaining = $this->folios->remainingRefundableMinor($payment);
            if ($remaining > 0) {
                $this->folios->refundPayment($payment, Money::of($remaining, (string) $payment->currency), 'Cancelled within the free-cancellation window');
                $refunded += $remaining;
            }
        }

        return $refunded;
    }
}
