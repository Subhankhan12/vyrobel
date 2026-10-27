<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Folio;
use App\Models\Organization;
use App\Models\Reservation;
use App\Support\Folio\FolioTotals;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;

/**
 * Builds the operator FOLIO PDF view data from the REAL append-only ledger (folio_items + payments —
 * read only, services never modified). Charges are positive, settled payments negative, refunds their
 * OWN offsetting line (correct-by-reversal), holds/pending/failed shown with no balance effect. The
 * reconciliation figures come from the SHARED {@see FolioTotals} (the same formula the on-screen folio
 * uses), and money is formatted via the shared {@see MoneyFormatter} — so the PDF matches the screen's
 * charges / paid / balance to the cent (and character). No invented line items.
 */
final class FolioDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function data(Reservation $reservation): array
    {
        $currency = (string) ($reservation->currency ?? 'EUR');
        $guest = $reservation->leadGuest;
        $guestName = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';

        $folio = Folio::query()
            ->where('reservation_id', $reservation->id)
            ->with(['items', 'payments'])
            ->first();

        $lines = [];
        $totals = ['charges_minor' => 0, 'paid_minor' => 0, 'balance_minor' => 0, 'received_minor' => 0, 'refunded_minor' => 0];

        if ($folio !== null) {
            $lines = self::ledger($folio, $currency);
            $totals = FolioTotals::for($folio);
        }

        return [
            'operator' => (string) (Organization::query()->whereKey($reservation->organization_id)->value('name') ?? 'Vyrobel'),
            'property' => (string) ($reservation->property->name ?? ''),
            'reference' => (string) $reservation->reference,
            'guest' => $guestName !== '' ? $guestName : null,
            'stay' => CarbonImmutable::parse($reservation->check_in)->translatedFormat('j M Y').' – '.CarbonImmutable::parse($reservation->check_out)->translatedFormat('j M Y'),
            'currency' => $currency,
            'has_folio' => $folio !== null,
            'lines' => $lines,
            'charges' => MoneyFormatter::display($totals['charges_minor'], $currency),
            'paid' => MoneyFormatter::display($totals['paid_minor'], $currency),
            'balance' => MoneyFormatter::display($totals['balance_minor'], $currency),
            'balance_minor' => $totals['balance_minor'],
            // The Statement of Account's KPI band (audit 2026-08-07 · parity). Both come from the SAME
            // FolioTotals pass that produces `paid` — never a second walk over the payments, which is how
            // two documents come to state different money (#17). `paid` stays NET, exactly as the folio
            // receipt and the on-screen folio show it, so no existing figure moves.
            'credited' => MoneyFormatter::display($totals['refunded_minor'], $currency),
            'credited_minor' => $totals['refunded_minor'],
            'received' => MoneyFormatter::display($totals['received_minor'], $currency),
        ];
    }

    /**
     * The chronological ledger lines, mirroring the on-screen sign conventions (charges +, settled
     * payments −, refunds their own + line, holds/pending flagged and uncounted).
     *
     * @return list<array<string, mixed>>
     */
    private static function ledger(Folio $folio, string $currency): array
    {
        $entries = [];

        foreach ($folio->items as $item) {
            $amount = (int) $item->amount_minor;
            $entries[] = [
                'ts' => CarbonImmutable::parse($item->posted_at ?? $item->created_at)->getTimestamp(),
                'line' => [
                    'label' => self::itemTypeLabel((string) $item->type),
                    'desc' => (string) $item->description,
                    'date' => CarbonImmutable::parse($item->service_date ?? $item->posted_at ?? $item->created_at)->translatedFormat('j M'),
                    'amount' => ($amount < 0 ? '' : '+').MoneyFormatter::display($amount, $currency),
                    'is_negative' => $amount < 0,
                    'flag' => null,
                ],
            ];
        }

        foreach ($folio->payments as $payment) {
            $amount = (int) $payment->amount_minor;
            $type = (string) $payment->type;
            $status = (string) $payment->status;
            $date = CarbonImmutable::parse($payment->processed_at ?? $payment->created_at)->translatedFormat('j M');

            if (in_array($type, ['payment', 'capture'], true) && in_array($status, ['succeeded', 'captured'], true)) {
                $line = ['label' => self::paymentTypeLabel($type), 'desc' => __('php/support/pdf/folio_document.desc_received'), 'date' => $date, 'amount' => '−'.MoneyFormatter::display($amount, $currency), 'is_negative' => true, 'flag' => null];
            } elseif ($type === 'refund' && $status === 'succeeded') {
                $line = ['label' => self::paymentTypeLabel('refund'), 'desc' => __('php/support/pdf/folio_document.desc_reverses_payment'), 'date' => $date, 'amount' => '+'.MoneyFormatter::display($amount, $currency), 'is_negative' => false, 'flag' => null];
            } elseif ($type === 'deposit_hold' && $status === 'authorized') {
                $line = ['label' => self::paymentTypeLabel('deposit_hold'), 'desc' => __('php/support/pdf/folio_document.desc_pre_authorized'), 'date' => $date, 'amount' => MoneyFormatter::display($amount, $currency), 'is_negative' => false, 'flag' => __('php/support/pdf/folio_document.flag_hold')];
            } else {
                $flag = self::statusFlag($status);
                $line = ['label' => self::paymentTypeLabel($type), 'desc' => __('php/support/pdf/folio_document.desc_not_counted'), 'date' => $date, 'amount' => MoneyFormatter::display($amount, $currency), 'is_negative' => false, 'flag' => $flag];
            }

            $entries[] = ['ts' => CarbonImmutable::parse($payment->processed_at ?? $payment->created_at)->getTimestamp(), 'line' => $line];
        }

        usort($entries, fn (array $a, array $b): int => $a['ts'] <=> $b['ts']);

        return array_map(fn (array $e): array => $e['line'], $entries);
    }

    /** A folio item's `type` enum → its line label, in the reader's language (an unknown type is title-cased, never dropped). */
    public static function itemTypeLabel(string $type): string
    {
        return match ($type) {
            'room' => __('php/support/pdf/folio_document.item_room'),
            'tax' => __('php/support/pdf/folio_document.item_tax'),
            'fee' => __('php/support/pdf/folio_document.item_fee'),
            'add_on' => __('php/support/pdf/folio_document.item_add_on'),
            'product' => __('php/support/pdf/folio_document.item_product'),
            'manual' => __('php/support/pdf/folio_document.item_manual'),
            'discount' => __('php/support/pdf/folio_document.item_discount'),
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    /** A payment's `type` enum → its line label. */
    private static function paymentTypeLabel(string $type): string
    {
        return match ($type) {
            'payment' => __('php/support/pdf/folio_document.payment_payment'),
            'capture' => __('php/support/pdf/folio_document.payment_capture'),
            'refund' => __('php/support/pdf/folio_document.payment_refund'),
            'deposit_hold' => __('php/support/pdf/folio_document.payment_deposit_hold'),
            'void' => __('php/support/pdf/folio_document.payment_void'),
            'chargeback' => __('php/support/pdf/folio_document.payment_chargeback'),
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    /** The flag on a line that does not count in the balance — the payment's `status` enum as a word. */
    private static function statusFlag(string $status): string
    {
        return match ($status) {
            'pending' => __('php/support/pdf/folio_document.flag_pending'),
            'succeeded' => __('php/support/pdf/folio_document.flag_succeeded'),
            'failed' => __('php/support/pdf/folio_document.flag_failed'),
            'refunded' => __('php/support/pdf/folio_document.flag_refunded'),
            'authorized' => __('php/support/pdf/folio_document.flag_authorized'),
            'captured' => __('php/support/pdf/folio_document.flag_captured'),
            'voided' => __('php/support/pdf/folio_document.flag_voided'),
            default => ucfirst($status),
        };
    }
}
