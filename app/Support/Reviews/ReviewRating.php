<?php

declare(strict_types=1);

namespace App\Support\Reviews;

use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;

/**
 * The ONE rating-derivation definition (Phase 5 · SG2) — shared by every surface (storefront, owner, operator,
 * guest) so they can never disagree. Mirrors the other shared extractions (FolioTotals, OwnerLedgerAttribution).
 *
 * 🪤 A rating is DERIVED from real PUBLISHED + PUBLIC reviews — never a stored or hard-coded number. The single
 * source of "publicly visible" is {@see publicScope()} (visibility = public AND status = published); a pending /
 * rejected / private review is EXCLUDED from every public rating. A subject with no such reviews has `has_reviews`
 * false → the surface shows "No reviews yet", never a fabricated figure. Tenant-scoped via the model's global
 * scope (on the storefront, ResolvePublicProperty sets the property's org).
 */
final class ReviewRating
{
    /** @return Builder<Review> */
    public static function publicScope(): Builder
    {
        return Review::query()
            ->where('visibility', Review::VISIBILITY_PUBLIC)
            ->where('status', Review::STATUS_PUBLISHED);
    }

    /**
     * @return array{count: int, average: float|null, has_reviews: bool}
     */
    public static function forRoomType(string $roomTypeId): array
    {
        return self::summarize(self::publicScope()->where('room_type_id', $roomTypeId));
    }

    /**
     * @return array{count: int, average: float|null, has_reviews: bool}
     */
    public static function forUnit(string $unitId): array
    {
        return self::summarize(self::publicScope()->where('unit_id', $unitId));
    }

    /**
     * @return array{count: int, average: float|null, has_reviews: bool}
     */
    public static function forProperty(string $propertyId): array
    {
        return self::summarize(self::publicScope()->whereHas('roomType', fn (Builder $q) => $q->where('property_id', $propertyId)));
    }

    /**
     * #104 GATE 2 — the operator Reviews SUMMARY dashboard (the wireframe's overall-rating + 5-star distribution
     * + reply-rate strip). Org-scoped (the tenant scope on {@see Review}). Rating + distribution are over the
     * PUBLISHED/public reviews (the reputation the guest sees); reply-rate + pending count over what the operator
     * moderates. Every figure a REAL aggregate (#39) — an org with no reviews yields an honest empty summary.
     *
     * @return array{count: int, average: float|null, distribution: array<int, int>, responded: int, reply_rate: int, pending: int, has_reviews: bool}
     */
    public static function operatorSummary(): array
    {
        $published = self::publicScope();
        $count = (clone $published)->count();
        $average = $count > 0 ? round((float) (clone $published)->avg('rating'), 1) : null;

        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ((clone $published)->selectRaw('rating, COUNT(*) AS c')->groupBy('rating')->pluck('c', 'rating') as $star => $c) {
            $s = (int) $star;
            if (isset($distribution[$s])) {
                $distribution[$s] = (int) $c;
            }
        }

        $responded = (clone $published)->whereNotNull('responded_at')->count();

        return [
            'count' => $count,
            'average' => $average,
            'distribution' => $distribution,
            'responded' => $responded,
            'reply_rate' => $count > 0 ? (int) round($responded * 100 / $count) : 0,
            'pending' => Review::query()->where('status', Review::STATUS_PENDING)->count(),
            'has_reviews' => $count > 0,
        ];
    }

    /**
     * @param  Builder<Review>  $query
     * @return array{count: int, average: float|null, has_reviews: bool}
     */
    private static function summarize(Builder $query): array
    {
        $count = (clone $query)->count();
        if ($count === 0) {
            return ['count' => 0, 'average' => null, 'has_reviews' => false];
        }

        return [
            'count' => $count,
            'average' => round((float) (clone $query)->avg('rating'), 1),
            'has_reviews' => true,
        ];
    }
}
