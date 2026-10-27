<?php

declare(strict_types=1);

namespace App\Support\Reviews;

use App\Models\Review;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Services\ReviewService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * The owner Reviews page's REAL aggregates (exact-match rebuild) — DERIVED from the owner's OWN published+public
 * reviews only (never a stored/fabricated figure, #39). Owner-scoped: the caller passes the owner's OWN unit ids
 * ({@see OwnerPortalService::units()} → the {@see OwnerScope}), so another owner's
 * unit reviews (a different unit_id) can never contribute (#28). Built ON TOP of {@see ReviewRating::publicScope()}
 * (the ONE "publicly visible" rule) so it can never disagree with the storefront/owner rating.
 *
 * The RESPONSE RATE + avg response time are real: the operator posts a public response in-app
 * ({@see ReviewService::respond()} → `operator_response` + `responded_at`), so rate = responded ÷
 * total and avg-time = `responded_at` − `published_at`. When no review has been responded to yet, the time is
 * honestly omitted (never a fabricated "~6 hours").
 */
final class OwnerReviewSummary
{
    /**
     * @param  list<string>  $unitIds
     * @return array{
     *   has_reviews: bool, count: int, average: float|null,
     *   response_rate: int|null, responded: int, avg_response_hours: int|null,
     *   last30_count: int, last30_average: float|null,
     *   by_villa: list<array{unit_id: string, name: string, count: int, rating: float, pct: int, trend: string, direction: string}>,
     *   distribution: list<array{star: int, count: int, pct: int}>,
     *   categories: list<array{key: string, label: string, score: float, pct: int, softest: bool}>,
     *   softest_label: string|null
     * }
     */
    public static function forUnits(array $unitIds): array
    {
        if ($unitIds === []) {
            return self::empty();
        }

        /** @var Collection<int, Review> $reviews */
        $reviews = ReviewRating::publicScope()
            ->whereIn('unit_id', $unitIds)
            ->with('unit:id,name,code')
            ->get();

        $count = $reviews->count();
        if ($count === 0) {
            return self::empty();
        }

        $average = round((float) $reviews->avg('rating'), 1);

        // Response rate + avg response time — REAL, from operator_response + responded_at.
        $responded = $reviews->filter(fn (Review $r): bool => trim((string) $r->operator_response) !== '');
        $respondedCount = $responded->count();
        $responseRate = (int) round(($respondedCount / $count) * 100);

        $avgResponseHours = null;
        $withTimes = $responded->filter(fn (Review $r): bool => $r->responded_at !== null && $r->published_at !== null);
        if ($withTimes->isNotEmpty()) {
            $hours = $withTimes->map(fn (Review $r): float => max(
                0.0,
                CarbonImmutable::parse($r->published_at)->diffInMinutes(CarbonImmutable::parse($r->responded_at)) / 60,
            ));
            $avgResponseHours = (int) max(1, round((float) $hours->avg()));
        }

        // Last 30 days.
        $cutoff = CarbonImmutable::now()->subDays(30);
        $recent = $reviews->filter(fn (Review $r): bool => $r->published_at !== null && CarbonImmutable::parse($r->published_at)->greaterThanOrEqualTo($cutoff));
        $last30Count = $recent->count();
        $last30Average = $last30Count > 0 ? round((float) $recent->avg('rating'), 1) : null;

        $categories = self::categories($reviews);
        $softest = null;
        foreach ($categories as $c) {
            if ($c['softest']) {
                $softest = $c['label'];
            }
        }

        return [
            'has_reviews' => true,
            'count' => $count,
            'average' => $average,
            'response_rate' => $responseRate,
            'responded' => $respondedCount,
            'avg_response_hours' => $avgResponseHours,
            'last30_count' => $last30Count,
            'last30_average' => $last30Average,
            'by_villa' => self::byVilla($reviews),
            'distribution' => self::distribution($reviews),
            'categories' => $categories,
            'softest_label' => $softest,
        ];
    }

    /**
     * The per-category averages (Cleanliness/Communication/Location/Value) — DERIVED from the guest sub-ratings
     * that are actually present (a category no guest rated is omitted; never a fabricated score, #39). The lowest
     * scorer is flagged `softest` (drives the "Value is your softest score — rate changes factor this in" note).
     *
     * @param  Collection<int, Review>  $reviews
     * @return list<array{key: string, label: string, score: float, pct: int, softest: bool}>
     */
    private static function categories(Collection $reviews): array
    {
        $rows = [];
        foreach (Review::CATEGORIES as $key => $label) {
            $rated = $reviews->filter(fn (Review $r): bool => $r->{$key} !== null);
            if ($rated->isEmpty()) {
                continue;
            }
            $score = round((float) $rated->avg($key), 1);
            $rows[] = ['key' => $key, 'label' => $label, 'score' => $score, 'pct' => (int) round(($score / 5) * 100), 'softest' => false];
        }

        // Flag the single lowest-scoring category (ties → the first in the canonical order).
        if ($rows !== []) {
            $minScore = min(array_column($rows, 'score'));
            foreach ($rows as $i => $row) {
                if ($row['score'] === $minScore) {
                    $rows[$i]['softest'] = true;
                    break;
                }
            }
        }

        return $rows;
    }

    /**
     * Per-unit ("villa") breakdown — real count + avg rating, with a REAL trend (the unit's recent-window avg vs
     * its prior window). "— steady" when a delta can't be formed (never a fabricated trend, #39).
     *
     * @param  Collection<int, Review>  $reviews
     * @return list<array{unit_id: string, name: string, count: int, rating: float, pct: int, trend: string, direction: string}>
     */
    private static function byVilla(Collection $reviews): array
    {
        $now = CarbonImmutable::now();
        $out = [];

        foreach ($reviews->groupBy(fn (Review $r): string => (string) $r->unit_id) as $unitId => $group) {
            $unit = $group->first()?->unit;
            $name = $unit instanceof Unit ? (string) ($unit->name ?? $unit->code ?? __('php/support/reviews/owner_review_summary.unit_fallback')) : __('php/support/reviews/owner_review_summary.unit_fallback');
            $rating = round((float) $group->avg('rating'), 1);

            // Trend: recent 90d avg vs the prior 90d avg (real; steady when either window is empty).
            $recent = $group->filter(fn (Review $r): bool => $r->published_at !== null && CarbonImmutable::parse($r->published_at)->greaterThanOrEqualTo($now->subDays(90)));
            $prior = $group->filter(fn (Review $r): bool => $r->published_at !== null
                && CarbonImmutable::parse($r->published_at)->lessThan($now->subDays(90))
                && CarbonImmutable::parse($r->published_at)->greaterThanOrEqualTo($now->subDays(180)));

            $direction = 'steady';
            $trend = __('php/support/reviews/owner_review_summary.trend_steady');
            if ($recent->isNotEmpty() && $prior->isNotEmpty()) {
                $delta = round((float) $recent->avg('rating') - (float) $prior->avg('rating'), 1);
                if ($delta > 0) {
                    $direction = 'up';
                    $trend = __('php/support/reviews/owner_review_summary.trend_up', ['delta' => number_format($delta, 1)]);
                } elseif ($delta < 0) {
                    $direction = 'down';
                    $trend = __('php/support/reviews/owner_review_summary.trend_down', ['delta' => number_format(abs($delta), 1)]);
                }
            }

            $out[] = [
                'unit_id' => (string) $unitId,
                'name' => $name,
                'count' => $group->count(),
                'rating' => $rating,
                'pct' => (int) round(($rating / 5) * 100),
                'trend' => $trend,
                'direction' => $direction,
            ];
        }

        // Most-reviewed first (stable, deterministic).
        usort($out, fn (array $a, array $b): int => $b['count'] <=> $a['count'] ?: strcmp($a['name'], $b['name']));

        return $out;
    }

    /**
     * The 5★→1★ distribution — real counts, reconciling to the total. `pct` is bar width vs the biggest bucket.
     *
     * @param  Collection<int, Review>  $reviews
     * @return list<array{star: int, count: int, pct: int}>
     */
    private static function distribution(Collection $reviews): array
    {
        $counts = [];
        foreach (range(5, 1) as $star) {
            $counts[$star] = $reviews->where('rating', $star)->count();
        }
        $max = max(1, max($counts));

        $out = [];
        foreach ($counts as $star => $c) {
            $out[] = ['star' => $star, 'count' => $c, 'pct' => (int) max($c > 0 ? 2 : 0, round(($c / $max) * 100))];
        }

        return $out;
    }

    /**
     * @return array{
     *   has_reviews: bool, count: int, average: float|null,
     *   response_rate: int|null, responded: int, avg_response_hours: int|null,
     *   last30_count: int, last30_average: float|null,
     *   by_villa: list<array{unit_id: string, name: string, count: int, rating: float, pct: int, trend: string, direction: string}>,
     *   distribution: list<array{star: int, count: int, pct: int}>,
     *   categories: list<array{key: string, label: string, score: float, pct: int, softest: bool}>,
     *   softest_label: string|null
     * }
     */
    private static function empty(): array
    {
        return [
            'has_reviews' => false, 'count' => 0, 'average' => null,
            'response_rate' => null, 'responded' => 0, 'avg_response_hours' => null,
            'last30_count' => 0, 'last30_average' => null,
            'by_villa' => [], 'distribution' => [], 'categories' => [], 'softest_label' => null,
        ];
    }
}
