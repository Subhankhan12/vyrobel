<?php

declare(strict_types=1);

namespace App\Support\Folio;

use App\Http\Controllers\Operator\ReservationCreateController;
use App\Models\Fee;
use App\Models\Tax;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Support\Collection;

/**
 * The ONE rule set for which of a property's taxes/fees land on a folio, and the sample-stay estimate (task #98
 * GATE 2). Anti-drift: {@see ReservationCreateController} posts taxes/fees using the
 * SAME {@see taxApplies}/{@see feeApplies} predicates + {@see FolioService::computeTax}/{@see FolioService::computeFee}
 * amounts that {@see estimate} uses — so the Property-Settings sample-stay reconciles to a REAL folio to the cent (#17).
 *
 * The rules:
 *  • A percentage tax marked `inclusive` is ALREADY in the room price → it is NOT charged (informational only); the
 *    displayed amount is the EXTRACTED portion `base·rate/(100+rate)`, never `base·rate/100`.
 *  • Every other tax (per-night / per-person-night / flat / exclusive percentage) is charged.
 *  • A fee marked `optional` is offered at booking (guest can add it) → NOT auto-charged; a non-optional fee
 *    (cleaning) is applied to every folio.
 */
final class StayEstimator
{
    /**
     * ⚠️ THE ONE DERIVATION of "this percentage tax is ALREADY in the room price" (A2).
     *
     * It existed twice: here, inverted inside {@see taxApplies}, and again inline inside {@see estimate}. The
     * two agreed — but a mutation that changed `taxApplies()` left `estimate()` untouched and the suite stayed
     * GREEN, which is how the duplication was found: a money-path guard that cannot be mutated is a guard
     * whose reach is unknown. Both callers now route through this, so mutating it reaches both.
     */
    public static function isInclusivePercentage(Tax $tax): bool
    {
        return (string) $tax->mode === 'percentage' && (bool) $tax->inclusive;
    }

    /** True when this tax is charged on the folio (an inclusive percentage tax is informational, not charged). */
    public static function taxApplies(Tax $tax, string $currency): bool
    {
        if ($tax->currency !== null && (string) $tax->currency !== $currency) {
            return false;
        }

        return ! self::isInclusivePercentage($tax);
    }

    /** True when this fee is auto-charged on every folio (an optional-at-booking fee is not). */
    public static function feeApplies(Fee $fee, string $currency): bool
    {
        if ($fee->currency !== null && (string) $fee->currency !== $currency) {
            return false;
        }

        return ! (bool) $fee->optional;
    }

    /**
     * The sample-stay estimate — one line per component + the guest-pays total. Uses the certified compute
     * functions, so a REAL folio built from the same config totals identically (#17).
     *
     * @param  Collection<int, Tax>  $taxes
     * @param  Collection<int, Fee>  $fees
     * @return array{lines: list<array{label: string, detail: string, amount: Money, informational: bool}>, total: Money}
     */
    public static function estimate(Collection $taxes, Collection $fees, Money $room, int $nights, int $persons, string $currency): array
    {
        $folios = app(FolioService::class);
        $lines = [[
            'label' => (string) __('php/support/folio/stay_estimator.room'),
            'detail' => Money::of((int) round($room->amountMinor() / max(1, $nights)), $currency)->amountMinor() > 0
                // ⚠️ The per-night sublabel hardcoded '€' while the amount beside it was built in `$currency`
                // — the same function's own argument. This line renders on the PUBLIC GUEST CHECKOUT
                // (booking/checkout.blade.php:253), so a GBP property quoted "€150.00 × 4" under a "£600.00"
                // total at the point of payment. The ONE symbol map answers it.
                ? MoneyFormatter::symbol($currency).number_format($room->amountMinor() / 100 / max(1, $nights), 2).' × '.$nights
                : '',
            'amount' => $room,
            'informational' => false,
        ]];
        $addedMinor = $room->amountMinor();

        foreach ($taxes as $tax) {
            if (($tax->currency !== null && (string) $tax->currency !== $currency)) {
                continue;
            }
            $inclusive = self::isInclusivePercentage($tax);   // ⚠️ A2 — the ONE derivation, not a second copy
            $amount = $inclusive
                ? Money::of((int) round($room->amountMinor() * (float) $tax->rate / (100 + (float) $tax->rate)), $currency)
                : $folios->computeTax($tax, $room, $nights, $persons);

            $lines[] = [
                'label' => (string) $tax->name,
                'detail' => self::taxDetail($tax, $nights, $persons, $inclusive),
                'amount' => $amount,
                'informational' => $inclusive,
            ];
            if (! $inclusive) {
                $addedMinor += $amount->amountMinor();
            }
        }

        foreach ($fees as $fee) {
            if (! self::feeApplies($fee, $currency)) {
                continue;
            }
            $amount = $folios->computeFee($fee, $room, $nights, $persons);
            $lines[] = [
                'label' => (string) $fee->name,
                'detail' => self::feeDetail($fee),
                'amount' => $amount,
                'informational' => false,
            ];
            $addedMinor += $amount->amountMinor();
        }

        return ['lines' => $lines, 'total' => Money::of($addedMinor, $currency)];
    }

    private static function taxDetail(Tax $tax, int $nights, int $persons, bool $inclusive): string
    {
        $rate = rtrim(rtrim((string) $tax->rate, '0'), '.');

        return (string) match ((string) $tax->mode) {
            'percentage' => $inclusive
                ? __('php/support/folio/stay_estimator.tax_percent_included', ['rate' => $rate])
                : __('php/support/folio/stay_estimator.tax_percent_added', ['rate' => $rate]),
            'per_person_night' => __('php/support/folio/stay_estimator.tax_per_person_night', [
                'guests' => trans_choice('php/support/folio/stay_estimator.guests', $persons, ['count' => $persons]),
                'nights' => trans_choice('php/support/folio/stay_estimator.nights', $nights, ['count' => $nights]),
            ]),
            'per_night' => __('php/support/folio/stay_estimator.tax_per_night', ['nights' => trans_choice('php/support/folio/stay_estimator.nights', $nights, ['count' => $nights])]),
            default => __('php/support/folio/stay_estimator.tax_flat'),
        };
    }

    private static function feeDetail(Fee $fee): string
    {
        return (string) match ((string) $fee->mode) {
            'per_night' => __('php/support/folio/stay_estimator.fee_per_night'),
            'per_person' => __('php/support/folio/stay_estimator.fee_per_guest'),
            'percentage' => rtrim(rtrim((string) $fee->rate, '0'), '.').'%',
            default => __('php/support/folio/stay_estimator.fee_per_stay'),
        };
    }
}
