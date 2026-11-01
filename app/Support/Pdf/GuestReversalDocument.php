<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Folio;
use App\Models\Organization;
use App\Models\Reservation;
use App\Support\I18n\LocalDate;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;

/**
 * Builds the guest REFUND RECEIPT and CREDIT NOTE PDF data — both document the REAL money-back events on the
 * booking (the folio's succeeded `refund` payments, which are correct-by-reversal: a refund is its own row, never
 * an edit). The two documents share this data (the reversal is one fact; the receipt frames it as money returned,
 * the credit note as a credit against the invoice). Rendered ONLY when a real refund exists (the controller 404s
 * otherwise), so no reversal document is ever fabricated.
 */
final class GuestReversalDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function data(Reservation $reservation): array
    {
        $currency = (string) ($reservation->currency ?? 'EUR');
        $guest = $reservation->leadGuest;
        $guestName = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : null;

        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->with('payments')->first();

        $refunds = [];
        $totalMinor = 0;
        $latest = null;

        if ($folio !== null) {
            foreach ($folio->payments as $payment) {
                if ((string) $payment->type !== 'refund' || (string) $payment->status !== 'succeeded') {
                    continue;
                }
                $amount = (int) $payment->amount_minor;
                $totalMinor += $amount;
                $when = CarbonImmutable::parse($payment->processed_at ?? $payment->created_at);
                $latest = $latest === null || $when->greaterThan($latest) ? $when : $latest;
                $refunds[] = [
                    'date' => $when->translatedFormat(LocalDate::pattern('j M Y')),
                    'amount' => MoneyFormatter::display($amount, $currency),
                    'method' => ucfirst(str_replace('_', ' ', (string) ($payment->gateway ?? 'manual'))),
                ];
            }
        }

        return [
            'operator' => (string) (Organization::query()->whereKey($reservation->organization_id)->value('name') ?? 'Vyrobel'),
            'property' => (string) ($reservation->property->name ?? ''),
            'reference' => (string) $reservation->reference,
            'guest' => $guestName !== '' ? $guestName : null,
            'stay' => CarbonImmutable::parse($reservation->check_in)->translatedFormat(LocalDate::pattern('j M Y')).' – '.CarbonImmutable::parse($reservation->check_out)->translatedFormat(LocalDate::pattern('j M Y')),
            'currency' => $currency,
            'refunds' => $refunds,
            'total' => MoneyFormatter::display($totalMinor, $currency),
            'issued_on' => ($latest ?? CarbonImmutable::now())->translatedFormat(LocalDate::pattern('j M Y')),
            'refund_no' => 'RF-'.$reservation->reference,
            'credit_note_no' => 'CN-'.$reservation->reference,
        ];
    }
}
