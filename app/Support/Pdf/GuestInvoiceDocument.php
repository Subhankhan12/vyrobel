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
 * Builds the guest VAT INVOICE PDF data from the REAL folio ledger. The invoice splits the folio's own lines into
 * NET (every non-tax charge) and VAT (the `type='tax'` items) — net + vat = the folio's charges, so the invoice
 * reconciles to the on-screen figures and to {@see FolioTotals} to the cent. Nothing is invented; if the folio
 * has no tax lines the VAT total is simply zero.
 */
final class GuestInvoiceDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function data(Reservation $reservation): array
    {
        $currency = (string) ($reservation->currency ?? 'EUR');
        $guest = $reservation->leadGuest;
        $guestName = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : null;

        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->with(['items', 'payments'])->first();

        $lines = [];
        $netMinor = 0;
        $vatMinor = 0;

        if ($folio !== null) {
            foreach ($folio->items as $item) {
                $amount = (int) $item->amount_minor;
                if ((string) $item->type === 'tax') {
                    $vatMinor += $amount;

                    continue;
                }
                $netMinor += $amount;
                $lines[] = [
                    'label' => ucfirst(str_replace('_', ' ', (string) $item->type)),
                    'desc' => (string) $item->description,
                    'date' => CarbonImmutable::parse($item->service_date ?? $item->posted_at ?? $item->created_at)->format('j M'),
                    'amount' => MoneyFormatter::display($amount, $currency),
                ];
            }
        }

        $totals = $folio !== null ? FolioTotals::for($folio) : ['charges_minor' => 0, 'paid_minor' => 0, 'balance_minor' => 0];

        return [
            'seller' => (string) (Organization::query()->whereKey($reservation->organization_id)->value('name') ?? 'Vyrobel'),
            'property' => (string) ($reservation->property->name ?? ''),
            'buyer' => $guestName !== '' ? $guestName : null,
            'invoice_no' => 'INV-'.$reservation->reference,
            'invoice_date' => CarbonImmutable::parse($reservation->booked_at ?? $reservation->created_at)->format('j M Y'),
            'reference' => (string) $reservation->reference,
            'stay' => CarbonImmutable::parse($reservation->check_in)->format('j M Y').' – '.CarbonImmutable::parse($reservation->check_out)->format('j M Y'),
            'currency' => $currency,
            'lines' => $lines,
            'net' => MoneyFormatter::display($netMinor, $currency),
            'vat' => MoneyFormatter::display($vatMinor, $currency),
            'gross' => MoneyFormatter::display($netMinor + $vatMinor, $currency),
            'paid' => MoneyFormatter::display($totals['paid_minor'], $currency),
            'balance' => MoneyFormatter::display($totals['balance_minor'], $currency),
        ];
    }
}
