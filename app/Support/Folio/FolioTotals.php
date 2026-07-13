<?php

declare(strict_types=1);

namespace App\Support\Folio;

use App\Models\Folio;

/**
 * The append-only folio reconciliation formula, in ONE place — shared by the on-screen folio
 * (ReservationDetail) and the folio PDF so both reconcile to the exact same figures (and to the folio's
 * FolioService-maintained cached balance). Pure + read-only over the real folio_items + payments:
 *   charges = Σ items · paid = Σ settled payments − Σ succeeded refunds · balance = charges − paid.
 * Requires the `items` + `payments` relations to be loaded.
 */
final class FolioTotals
{
    /**
     * @return array{charges_minor: int, paid_minor: int, balance_minor: int}
     */
    public static function for(Folio $folio): array
    {
        $charges = 0;
        foreach ($folio->items as $item) {
            $charges += (int) $item->amount_minor;
        }

        $received = 0;
        $refunded = 0;
        foreach ($folio->payments as $payment) {
            $amount = (int) $payment->amount_minor;
            $type = (string) $payment->type;
            $status = (string) $payment->status;

            if (in_array($type, ['payment', 'capture'], true) && in_array($status, ['succeeded', 'captured'], true)) {
                $received += $amount;
            } elseif ($type === 'refund' && $status === 'succeeded') {
                $refunded += $amount;
            }
        }

        $paid = $received - $refunded;

        return [
            'charges_minor' => $charges,
            'paid_minor' => $paid,
            'balance_minor' => $charges - $paid,
        ];
    }
}
