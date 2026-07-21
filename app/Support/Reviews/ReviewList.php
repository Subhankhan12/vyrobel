<?php

declare(strict_types=1);

namespace App\Support\Reviews;

use App\Models\Review;
use App\Support\Guest\GuestName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * The shared PUBLIC review-list display (Phase 5 · SG4) — so the owner surface and the public storefront render the
 * same published reviews the same way. Reuses {@see ReviewRating::publicScope()} (published + public only — a
 * pending/rejected/private review is never listed) and the #28 byline (first name + last initial only, never full
 * contact). The owner filters by their OWN unit ids; the storefront by a room type.
 */
final class ReviewList
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function forRoomType(string $roomTypeId): array
    {
        return self::map(ReviewRating::publicScope()->where('room_type_id', $roomTypeId)->with('guest')->latest('published_at')->get());
    }

    /**
     * @param  list<string>  $unitIds
     * @return list<array<string, mixed>>
     */
    public static function forUnits(array $unitIds): array
    {
        if ($unitIds === []) {
            return [];
        }

        return self::map(ReviewRating::publicScope()->whereIn('unit_id', $unitIds)->with(['guest', 'roomType'])->latest('published_at')->get());
    }

    /**
     * @param  Collection<int, Review>  $reviews
     * @return list<array<string, mixed>>
     */
    private static function map(Collection $reviews): array
    {
        return $reviews->map(fn (Review $r): array => [
            'byline' => GuestName::reviewByline($r->guest),   // first name + initial only (#28)
            'rating' => (int) $r->rating,
            'stars' => str_repeat('★', (int) $r->rating).str_repeat('☆', 5 - (int) $r->rating),
            'tags' => $r->tags ?? [],
            'body' => (string) $r->body,
            'response' => (string) $r->operator_response,
            'room' => (string) ($r->roomType->name ?? ''),
            'at' => $r->published_at !== null ? CarbonImmutable::parse($r->published_at)->format('M Y') : '',
        ])->all();
    }
}
