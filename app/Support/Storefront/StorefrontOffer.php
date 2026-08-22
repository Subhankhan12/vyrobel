<?php

declare(strict_types=1);

namespace App\Support\Storefront;

use App\Models\Fee;
use App\Models\Property;
use App\Models\Tax;
use App\Services\BookingEngineService;
use App\Support\Folio\StayEstimator;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * STOREFRONT SEO (D10) — THE ONE DEFINITION OF "IS THIS PROPERTY SELLABLE, AND AT WHAT PRICE".
 *
 * ⚠️⚠️ THIS CLASS COMPUTES NOTHING ITSELF. It is a thin probe over {@see BookingEngineService::search()},
 * which already IS the sellable chain (P-6 Gate 1 derived it, and this class does not re-derive it):
 *
 *   capacity (max_adults/max_children) · ≥1 available every night · an ACTIVE rate plan ·
 *   a price for EVERY night (priceOver() returns null if any night is unpriced)
 *
 * ⚠️ IT EXISTS SO THE PAGE AND THE SITEMAP CANNOT DISAGREE (§N.8f). The JSON-LD `Offer` on a property page
 * and the sitemap's inclusion test read the SAME probe. A second implementation of "sellable" is exactly how
 * a sitemap comes to advertise a property whose page offers nothing.
 *
 * ⚠️ B12 — THE PRICE COMES FROM THE ENGINE'S OWN SOURCE. `search()` returns `total_minor` derived from
 * `priceOver()` over `rate_calendar`. Nothing here reads a rate, a plan or a calendar row directly, so there
 * is no second representation of a price to drift from the one the guest is actually charged.
 *
 * ⚠️⚠️ AND THE QUOTED FIGURE IS THE GRAND TOTAL, NOT THE ROOM SUBTOTAL. `search()` returns the room-only
 * price, which is VAT-EXCLUSIVE (the ratified F3 basis) and excludes the cleaning fee and city tax. The
 * storefront card does NOT lead with that figure alone — it shows the nightly beside a `grand_total` that
 * folds in every mandatory tax and fee, and that grand total is what the folio reconciles to (#17). A
 * structured-data price of the room subtotal would therefore have advertised a number LOWER than anything a
 * guest can actually pay, and lower than the page's own total — a price mismatch is the single most common
 * cause of a structured-data manual action.
 *
 * ⚠️ SO THE GRAND TOTAL IS COMPUTED THROUGH {@see StayEstimator} — the SAME certified estimator the browse
 * card, the checkout breakdown and the folio use. It is not a second computation of the total; it is the
 * existing one, called from one more place (§N.8f).
 *
 * ⚠️ THE WINDOW LADDER, AND WHY IT IS A LADDER. A canonical property URL carries no dates, so a price can
 * only be quoted for some window — and any single window is a false negative waiting to happen (a property
 * fully booked next week is still perfectly sellable next month). So the probe tries a small ladder and
 * takes the FIRST window that yields offers. The window that produced the price is RETURNED and rendered
 * into the markup (`availabilityStarts`/`availabilityEnds`), so the claim is bounded and checkable rather
 * than an unqualified "from €X".
 *
 * ⚠️ NO WINDOW YIELDS OFFERS ⇒ NOT SELLABLE ⇒ NO `Offer` IS EMITTED AND THE PROPERTY IS NOT SITEMAPPED.
 * That is the fail-safe direction: a missing offer costs a rich result; a fabricated one is a false claim
 * about a price a guest cannot actually get (#39).
 */
final class StorefrontOffer
{
    /**
     * The probe ladder: [days from today until arrival, nights].
     *
     * ⚠️ Near-term first (the most useful price to show), then progressively further out so a property that
     * is merely BUSY is not mistaken for one that is UNSELLABLE. Three probes, so the cost is bounded.
     */
    private const WINDOWS = [
        [1, 1],
        [7, 2],
        [30, 3],
    ];

    public function __construct(private readonly BookingEngineService $engine) {}

    /**
     * The cheapest real offer for a property, or NULL when the engine offers nothing in any probed window.
     *
     * ⚠️ `low_minor`/`high_minor` are STAY totals for the returned window (not per-night), which is why the
     * window is returned with them — a caller that renders one without the other is making an unbounded
     * claim. `nights` is provided so a caller can present a per-night figure honestly if it wants one.
     *
     * @return array{low_minor: int, high_minor: int, currency: string, offer_count: int, check_in: string, check_out: string, nights: int}|null
     */
    public function probe(Property $property): ?array
    {
        $today = CarbonImmutable::now($property->timezone ?: 'UTC')->startOfDay();

        foreach (self::WINDOWS as [$offsetDays, $nights]) {
            $checkIn = $today->addDays($offsetDays);
            $checkOut = $checkIn->addDays($nights);

            // ⚠️ 1 adult / 0 children — the LEAST restrictive occupancy, so the probe asks "can this property
            // sell to anyone at all", not "can it sell to a party of two". A stricter occupancy here would
            // drop single-occupancy inventory out of the sitemap.
            $offers = $this->engine->search($property, $checkIn->toDateString(), $checkOut->toDateString(), 1, 0);

            if ($offers === []) {
                continue;
            }

            $currency = (string) $offers[0]['currency'];

            // ⚠️ Loaded ONCE per property, not per offer — and only on the window that actually produced
            // offers, so an unsellable property costs no tax/fee queries at all.
            $taxes = Tax::query()->where('property_id', $property->getKey())->get();
            $fees = Fee::query()->where('property_id', $property->getKey())->get();

            $totals = array_map(
                fn (array $o): int => $this->grandTotalMinor($taxes, $fees, $o, $currency),
                $offers,
            );

            return [
                'low_minor' => min($totals),
                'high_minor' => max($totals),
                'currency' => $currency,
                'offer_count' => count($offers),
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'nights' => $nights,
            ];
        }

        return null;
    }

    /**
     * The all-in price a guest would actually be charged for one engine offer — room + every mandatory fee
     * and tax — via the certified {@see StayEstimator}.
     *
     * ⚠️ `persons` is the probe's own occupancy (1 adult, 0 children), matching the search that produced the
     * offer. A per-person fee is therefore priced for exactly the party the price is quoted for; quoting a
     * different party size than the one searched would silently mis-state per-person city tax.
     *
     * @param  Collection<int, Tax>  $taxes
     * @param  Collection<int, Fee>  $fees
     * @param  array<string, mixed>  $offer
     */
    private function grandTotalMinor(Collection $taxes, Collection $fees, array $offer, string $currency): int
    {
        $estimate = StayEstimator::estimate(
            $taxes,
            $fees,
            Money::of((int) $offer['total_minor'], $currency),
            max(1, (int) $offer['nights']),
            1,
            $currency,
        );

        return $estimate['total']->amountMinor();
    }

    /**
     * ⚠️ THE SITEMAP'S INCLUSION TEST — deliberately expressed in terms of {@see probe()} rather than
     * duplicated, so "sellable" has exactly one meaning in this codebase.
     */
    public function isSellable(Property $property): bool
    {
        return $this->probe($property) !== null;
    }
}
