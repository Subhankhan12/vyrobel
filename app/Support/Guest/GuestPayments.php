<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Models\Folio;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Folio\FolioTotals;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * The guest Payments hub data (Phase 4c · Sub-gate 1) — the authed guest's balance across THEIR OWN bookings +
 * their payment history, read from the REAL append-only folio ledger through the shared {@see FolioTotals} (the
 * exact charges/paid/balance the operator + owner surfaces show, reconciling to the cent). Integer minor units.
 *
 * 🔒 The caller passes the guest (resolved from the guest guard); every booking here is one the guest leads, so
 * the hub can never surface another guest's balance. Read-only — no money moves here (the card charge is the
 * account-gated Stripe rail, deferred).
 */
final class GuestPayments
{
    /**
     * @return array{
     *   currency: string, total_balance: string, total_balance_minor: int, total_paid: string,
     *   bookings: list<array<string, mixed>>, history: list<array<string, mixed>>
     * }
     */
    public static function overview(Guest $guest, CarbonInterface $today): array
    {
        /** @var Collection<int, Reservation> $reservations */
        $reservations = $guest->reservations()->with('property')->orderByDesc('check_in')->get();

        /** @var Collection<string, Folio> $folios */
        $folios = Folio::query()
            ->whereIn('reservation_id', $reservations->pluck('id')->all())
            ->with(['items', 'payments'])
            ->get()
            ->keyBy('reservation_id');

        $currency = (string) ($reservations->first()->currency ?? 'EUR');
        $totalBalance = 0;
        $totalPaid = 0;
        $bookings = [];
        $history = [];

        foreach ($reservations as $reservation) {
            $folio = $folios->get($reservation->id);
            $totals = $folio !== null ? FolioTotals::for($folio) : ['charges_minor' => 0, 'paid_minor' => 0, 'balance_minor' => 0];
            $bookingCurrency = (string) $reservation->currency;

            $totalBalance += $totals['balance_minor'];
            $totalPaid += $totals['paid_minor'];

            $bookings[] = [
                'id' => (string) $reservation->getKey(),
                'reference' => (string) $reservation->reference,
                'property' => (string) ($reservation->property->name ?? 'Your stay'),
                'stay' => CarbonImmutable::parse($reservation->check_in)->format('j M').' – '.CarbonImmutable::parse($reservation->check_out)->format('j M Y'),
                'balance' => MoneyFormatter::display($totals['balance_minor'], $bookingCurrency),
                'balance_minor' => $totals['balance_minor'],
                'balance_due' => $totals['balance_minor'] > 0,
                'paid' => MoneyFormatter::display($totals['paid_minor'], $bookingCurrency),
                'has_refund' => $folio !== null && $folio->payments->contains(fn (Payment $p): bool => (string) $p->type === 'refund' && (string) $p->status === 'succeeded'),
                'currency' => $bookingCurrency,
            ];

            if ($folio !== null) {
                foreach ($folio->payments as $payment) {
                    $entry = self::historyEntry($payment, (string) $reservation->reference, $bookingCurrency);
                    if ($entry !== null) {
                        $history[] = $entry;
                    }
                }
            }
        }

        usort($history, fn (array $a, array $b): int => $b['ts'] <=> $a['ts']);

        return [
            'currency' => $currency,
            'total_balance' => MoneyFormatter::display($totalBalance, $currency),
            'total_balance_minor' => $totalBalance,
            'total_paid' => MoneyFormatter::display($totalPaid, $currency),
            'bookings' => $bookings,
            'history' => array_map(fn (array $e): array => array_diff_key($e, ['ts' => 0]), $history),
        ];
    }

    /** A settled payment or a succeeded refund becomes a history row; pending/failed/holds are not "history". */
    private static function historyEntry(Payment $payment, string $reference, string $currency): ?array
    {
        $type = (string) $payment->type;
        $status = (string) $payment->status;
        $when = CarbonImmutable::parse($payment->processed_at ?? $payment->created_at);

        if (in_array($type, ['payment', 'capture'], true) && in_array($status, ['succeeded', 'captured'], true)) {
            $label = 'Payment received';
            $signed = false;
        } elseif ($type === 'refund' && $status === 'succeeded') {
            $label = 'Refund issued';
            $signed = true;   // shown as money going back to the guest
        } else {
            return null;
        }

        return [
            'ts' => $when->getTimestamp(),
            'label' => $label,
            'reference' => $reference,
            'date' => $when->format('j M Y'),
            'amount' => ($signed ? '−' : '').MoneyFormatter::display((int) $payment->amount_minor, $currency),
        ];
    }
}
