<?php

declare(strict_types=1);

namespace App\Support\Rates;

use App\Models\RateCalendar;
use App\Models\RateSeason;
use App\Services\SeasonalRateService;
use App\Support\I18n\LocalDate;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;

/**
 * A SAVED SEASON NEVER APPLIED, offered as the way to price a stay — ONE rule for every booking surface: the New reservation
 * page (2026-10-04, `rel_AV`) and the Calendar's New reservation dialog (2026-10-04, the founder's next report: "it is not possible
 * to do reservation" there too). 🔬 On production his €200 "Winter" season was saved on 28 Sep and never applied, so his property
 * had no price on any night and nothing could be booked.
 *
 * Only a season that would price a night of the stay STILL WITHOUT ONE is offered: one over nights that already have a price
 * would change them and leave the missing nights as they are (the review, M2). The figures come from
 * {@see SeasonApplyFacts} — the Rates page's own "Apply this season?" facts — never recomputed here.
 */
final class SavedSeasonOffer
{
    /**
     * @param  bool  $withFacts  the "Apply this season?" facts (a preview each) — only for someone who may apply it
     * @return list<array<string, mixed>> each season's {@see SeasonApplyFacts} (or only its id and name) plus `nightly`, `from`, `to`, `prices` (the stay's
     *                                    unpriced nights it would price) and `missing` (the stay's unpriced nights)
     */
    public static function forStay(string $planId, string $checkIn, string $checkOut, bool $withFacts = true): array
    {
        $lastNight = CarbonImmutable::parse($checkOut)->subDay()->toDateString();
        $priced = RateCalendar::query()->where('rate_plan_id', $planId)->where('date', '>=', $checkIn)->where('date', '<', $checkOut)
            ->pluck('date')->map(fn ($d): string => CarbonImmutable::parse($d)->toDateString())->all();
        $missing = [];
        for ($d = CarbonImmutable::parse($checkIn); $d->toDateString() < $checkOut; $d = $d->addDay()) {
            if (! in_array($d->toDateString(), $priced, true)) {
                $missing[] = $d->toDateString();
            }
        }
        if ($missing === []) {
            return [];
        }

        $preview = app(SeasonalRateService::class);
        $out = [];
        foreach (RateSeason::query()->where('rate_plan_id', $planId)->whereNull('applied_at')
            ->where('starts_on', '<=', $lastNight)->where('ends_on', '>=', $checkIn)->orderBy('starts_on')->get() as $season) {
            $from = $season->starts_on->toDateString();
            $to = $season->ends_on->toDateString();
            $prices = count(array_filter($missing, fn (string $d): bool => $d >= $from && $d <= $to));
            if ($prices === 0) {
                continue;   // it would change a price the stay already has, and leave the missing nights as they are
            }
            // ⚠️ the facts (a preview each) only for whoever may apply it — for everyone else its name, price and dates (2026-10-04)
            $out[] = ($withFacts ? SeasonApplyFacts::of($season, $preview->preview($season)) : ['id' => (string) $season->getKey(), 'name' => (string) $season->name]) + [
                'nightly' => __('php/support/rates/saved_season_offer.nightly', ['amount' => MoneyFormatter::display((int) $season->price_minor, (string) $season->currency)]),
                'from' => $season->starts_on->translatedFormat(LocalDate::pattern('j M')),
                'to' => $season->ends_on->translatedFormat(LocalDate::pattern('j M')),
                'prices' => $prices,
                'missing' => count($missing),
            ];
        }

        return $out;
    }
}
