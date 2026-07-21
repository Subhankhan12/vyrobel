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
