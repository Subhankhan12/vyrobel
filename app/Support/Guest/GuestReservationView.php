<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Support\Folio\FolioTotals;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;

/**
 * The guest-facing view of ONE booking's money + history (Phase 4b · Sub-gate 2). Charges/balance are the SAME
 * {@see FolioTotals} figures the operator/owner see, reconciling to the folio — just the guest's own read. The
 * timeline is the REAL reservation/folio history (status milestones + payments), but guest-facing: it NEVER
 * exposes the operator staff member who acted (that actor is on the operator screen, not here) — a guest sees
 * WHAT happened to their booking, not WHO on the operator side did it. Pure + read-only.
 */
class GuestReservationView
{
    /**
     * The folio charge lines + charges/paid/balance totals (guest-facing).
     *
     * @return array{lines: list<array{label: string, amount: string}>, charges: string, paid: string,
     *   balance: string, balance_minor: int, balance_due: bool}
     */
    public static function folio(Reservation $reservation): array
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->with(['items', 'payments'])->first();

        if ($folio === null) {
            $zero = MoneyFormatter::display(0, (string) $reservation->currency);

            return ['lines' => [], 'charges' => $zero, 'paid' => $zero, 'balance' => $zero, 'balance_minor' => 0, 'balance_due' => false];
        }

        $currency = (string) $folio->currency;
        $totals = FolioTotals::for($folio);

        $lines = $folio->items->map(fn (FolioItem $item): array => [
            'label' => self::itemLabel($item),
            'amount' => MoneyFormatter::display((int) $item->amount_minor, $currency),
        ])->all();

        return [
            'lines' => $lines,
            'charges' => MoneyFormatter::display($totals['charges_minor'], $currency),
            'paid' => MoneyFormatter::display($totals['paid_minor'], $currency),
            'balance' => MoneyFormatter::display($totals['balance_minor'], $currency),
            'balance_minor' => $totals['balance_minor'],
            'balance_due' => $totals['balance_minor'] > 0,
        ];
    }

    /**
     * The guest-facing timeline: status milestones + payments, chronological, NO operator actor.
     *
     * @return list<array{label: string, at: string}>
     */
    public static function timeline(Reservation $reservation): array
    {
        $entries = [];

        $history = ReservationStatusHistory::query()->where('reservation_id', $reservation->getKey())->orderBy('created_at')->orderBy('id')->get();
        foreach ($history as $row) {
            $label = self::statusMilestone((string) $row->to_status);
            if ($label !== null) {
                $entries[] = ['ts' => CarbonImmutable::parse($row->created_at)->getTimestamp(), 'label' => $label];
            }
        }

        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->with('payments')->first();
        if ($folio !== null) {
            $currency = (string) $folio->currency;
            foreach ($folio->payments as $payment) {
                $label = self::paymentMilestone($payment, $currency);
                if ($label !== null) {
                    $at = CarbonImmutable::parse($payment->processed_at ?? $payment->created_at);
                    $entries[] = ['ts' => $at->getTimestamp(), 'label' => $label];
                }
            }
        }

        usort($entries, fn (array $a, array $b): int => $a['ts'] <=> $b['ts']);

        return array_map(fn (array $e): array => [
            'label' => $e['label'],
            'at' => CarbonImmutable::createFromTimestamp($e['ts'])->translatedFormat('j M Y · H:i'),
        ], $entries);
    }

    private static function itemLabel(FolioItem $item): string
    {
        $description = trim((string) $item->description);
        if ($description !== '') {
            return $description;
        }

        return (string) match ((string) $item->type) {
            'room' => __('php/support/guest/guest_reservation_view.item_room'), 'tax' => __('php/support/guest/guest_reservation_view.item_tax'), 'fee' => __('php/support/guest/guest_reservation_view.item_fee'),
            'add_on' => __('php/support/guest/guest_reservation_view.item_add_on'), 'product' => __('php/support/guest/guest_reservation_view.item_product'),
            'discount' => __('php/support/guest/guest_reservation_view.item_discount'),
            default => ucfirst((string) $item->type),
        };
    }

    /** A guest-friendly milestone for a status, or null to skip an internal transition. */
    private static function statusMilestone(string $toStatus): ?string
    {
        return match ($toStatus) {
            'confirmed' => (string) __('php/support/guest/guest_reservation_view.milestone_confirmed'),
            'checked_in' => (string) __('php/support/guest/guest_reservation_view.milestone_checked_in'),
            'checked_out' => (string) __('php/support/guest/guest_reservation_view.milestone_checked_out'),
            'cancelled' => (string) __('php/support/guest/guest_reservation_view.milestone_cancelled'),
            'no_show' => (string) __('php/support/guest/guest_reservation_view.milestone_no_show'),
            default => null,
        };
    }

    private static function paymentMilestone(Payment $payment, string $currency): ?string
    {
        if ((string) $payment->status !== 'succeeded') {
            return null;
        }

        $amount = MoneyFormatter::display(abs((int) $payment->amount_minor), $currency);

        return (string) ((string) $payment->type === 'refund'
            ? __('php/support/guest/guest_reservation_view.refund_of', ['amount' => $amount])
            : __('php/support/guest/guest_reservation_view.payment_received', ['amount' => $amount]));
    }
}
