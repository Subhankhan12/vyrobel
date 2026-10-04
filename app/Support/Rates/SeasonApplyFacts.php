<?php

declare(strict_types=1);

namespace App\Support\Rates;

use App\Models\RateSeason;
use App\Services\SeasonalRateService;
use App\Support\Money\MoneyFormatter;

/**
 * What the "Apply this season?" dialog says — ONE builder for every page that offers Apply (the Rates page, and since
 * 2026-10-04 the New reservation page), read by the ONE disclosure partial
 * (`livewire/operator/partials/season-apply-disclosure`). Every figure comes straight from {@see SeasonalRateService::preview()},
 * never recomputed; the apply address is built by the router, never assembled in script.
 *
 * ⚠️ 2026-10-04 — the New reservation page's first Apply posted straight away, with none of this said (the independent
 * review's H2): how many nights it overwrites, that it cannot be undone, and what it replaces that somebody negotiated.
 */
final class SeasonApplyFacts
{
    /**
     * @param  array<string, mixed>  $preview  {@see SeasonalRateService::preview()} for this season
     * @return array<string, mixed>
     */
    public static function of(RateSeason $season, array $preview): array
    {
        return [
            'id' => (string) $season->getKey(),
            'apply_url' => route('app.seasons.apply', $season->getKey()),
            'name' => (string) $season->name,
            'nights' => $preview['nights'],
            'units' => $preview['units'],
            'changing' => $preview['changing'],
            'weekend_nights' => $preview['weekend_nights'],
            'from_display' => $preview['from_minor'] === null ? null : MoneyFormatter::display($preview['from_minor'], $preview['currency']),
            'to_display' => MoneyFormatter::display($preview['to_minor'], $preview['currency']),
            'overlaps' => $preview['overlaps'],
            'losing_nights' => $preview['losing_nights'],
            'losing_to' => $preview['losing_to'],
            'rewritten_nights' => $preview['rewritten_nights'],
            'agreed_nights' => $preview['agreed_nights'],
            'agreed_owners' => $preview['agreed_owners'],
            'agreed_from' => $preview['agreed_from'],
            'agreed_to' => $preview['agreed_to'],
        ];
    }
}
