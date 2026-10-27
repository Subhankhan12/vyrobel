<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Folio;
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
                    'label' => FolioDocument::itemTypeLabel((string) $item->type),   // the ONE folio item-type label (shared)
                    'desc' => (string) $item->description,
                    'date' => CarbonImmutable::parse($item->service_date ?? $item->posted_at ?? $item->created_at)->translatedFormat('j M'),
                    'amount' => MoneyFormatter::display($amount, $currency),
                ];
            }
        }

        $totals = $folio !== null ? FolioTotals::for($folio) : ['charges_minor' => 0, 'paid_minor' => 0, 'balance_minor' => 0];

        // ⚠️ THE SUPPLIER'S STATUTORY IDENTIFIERS (audit 2026-08-07 · §5 parity).
        //
        // This is a TAX document, and it went out carrying only the org's trading name — no registered
        // legal name, no supplier VAT number — although both are real, stored, operator-editable values
        // (`organizations.billing_company` / `billing_vat_id`, set on /app/settings) that the four owner
        // PDFs had been printing all along. The shared OperatorLetterhead is now the one definition, so
        // the invoice cannot state a different supplier identity than the owner's remittance.
        //
        // HONEST-EMPTY (#39): an operator who has not filled these in gets NO VAT line and a name plainly
        // labelled as the trading name. Nothing is invented, defaulted or inferred from the country — a
        // fabricated VAT number on a tax document is materially worse than an absent one.
        $letterhead = OperatorLetterhead::forId(
            (string) $reservation->organization_id,
            OperatorLetterhead::ROLE_ACCOMMODATION,
        );

        // ⚠️ CALL IT WHAT IT ACTUALLY IS (#39). "VAT invoice" is a claim: it says the supplier is VAT-
        // registered and the document evidences VAT. When the operator has no VAT registration AND the
        // folio carries no tax line, both halves of that claim are false — so the document titles itself
        // a plain "Invoice". Derived from real state; the wording follows the data, never the reverse.
        $isVatDocument = $letterhead['vat_id'] !== null || $vatMinor !== 0;

        return [
            'doc_title' => $isVatDocument ? __('php/support/pdf/guest_invoice_document.title_vat_invoice') : __('php/support/pdf/guest_invoice_document.title_invoice'),
            'is_vat_document' => $isVatDocument,
            'seller' => $letterhead['name'],
            'seller_is_registered' => $letterhead['is_registered'],
            'seller_vat_id' => $letterhead['vat_id'],
            'seller_city' => $letterhead['city'],
            'seller_country' => $letterhead['country'],
            'seller_role' => OperatorLetterhead::roleLabel(OperatorLetterhead::ROLE_ACCOMMODATION),
            'property' => (string) ($reservation->property->name ?? ''),
            'buyer' => $guestName !== '' ? $guestName : null,
            'invoice_no' => 'INV-'.$reservation->reference,
            'invoice_date' => CarbonImmutable::parse($reservation->booked_at ?? $reservation->created_at)->translatedFormat('j M Y'),
            'reference' => (string) $reservation->reference,
            'stay' => CarbonImmutable::parse($reservation->check_in)->translatedFormat('j M Y').' – '.CarbonImmutable::parse($reservation->check_out)->translatedFormat('j M Y'),
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
