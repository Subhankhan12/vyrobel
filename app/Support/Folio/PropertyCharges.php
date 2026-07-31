<?php

declare(strict_types=1);

namespace App\Support\Folio;

use App\Models\Fee;
use App\Models\Folio;
use App\Models\Tax;
use App\Services\FolioService;
use App\Support\Money\Money;

/**
 * The ONE definition of "post a property's configured taxes + non-optional fees onto a folio" (#104 GATE 1). It
 * was a pair of private methods on the single-reservation `ReservationCreateController`; the audit found the
 * GROUP-booking flow posted room charges only, so a group under-billed relative to both the wireframe
 * AND the single-reservation flow (a real #17 inconsistency ACROSS surfaces — exactly the anti-drift trap two
 * `match`es fall into). Extracting it here means the direct booking and the group block charge tax/fees through
 * the SAME code — they cannot drift.
 *
 * The rules are unchanged and shared with the Property-Settings sample-stay preview via {@see StayEstimator}:
 * an INCLUSIVE percentage tax (VAT already in the price) is informational, not charged ({@see StayEstimator::taxApplies});
 * a percentage tax uses the taxable base, per-night/per-person taxes use the stay; only non-optional fees that
 * apply in this currency post ({@see StayEstimator::feeApplies}). Integer money throughout (#17).
 */
final class PropertyCharges
{
    /**
     * Post every configured, applicable tax for the property onto the folio, computed from the taxable base +
     * the stay (nights · persons). Inclusive percentage taxes are skipped (informational, already in the price).
     */
    public static function postTaxes(FolioService $folios, Folio $folio, string $propertyId, Money $taxableBase, int $nights, int $persons, string $currency): void
    {
        Tax::query()->where('property_id', $propertyId)->get()
            ->filter(fn (Tax $tax): bool => StayEstimator::taxApplies($tax, $currency))
            ->each(fn (Tax $tax) => $folios->applyTax($folio, $tax, $taxableBase, $nights, $persons));
    }

    /**
     * Post the property's non-optional fees (cleaning + custom always-apply fees) onto the folio. Optional-at-
     * booking fees are offered separately (the guest adds them), never auto-charged here.
     */
    public static function postFees(FolioService $folios, Folio $folio, string $propertyId, Money $base, int $nights, int $persons, string $currency): void
    {
        Fee::query()->where('property_id', $propertyId)->get()
            ->filter(fn (Fee $fee): bool => StayEstimator::feeApplies($fee, $currency))
            ->each(fn (Fee $fee) => $folios->applyFee($folio, $fee, $base, $nights, $persons));
    }
}
