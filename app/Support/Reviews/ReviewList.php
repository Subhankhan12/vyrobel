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
        return self::map(ReviewRating::publicScope()->where('room_type_id', $roomTypeId)
            ->with(['guest', 'roomType', 'unit:id,name,code', 'reservation:id,source'])
            ->latest('published_at')->get());
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

        return self::map(ReviewRating::publicScope()->whereIn('unit_id', $unitIds)
            ->with(['guest', 'roomType', 'unit:id,name,code', 'reservation:id,source'])
            ->latest('published_at')->get());
    }

    /**
     * @param  Collection<int, Review>  $reviews
     * @return list<array<string, mixed>>
     */
    private static function map(Collection $reviews): array
    {
        return $reviews->map(function (Review $r): array {
            $byline = GuestName::reviewByline($r->guest);   // first name + initial only (#28)
            $response = trim((string) $r->operator_response);
            $publishedAt = $r->published_at !== null ? CarbonImmutable::parse($r->published_at) : null;

            return [
                'byline' => $byline,
                'initials' => self::initials($byline),
                'rating' => (int) $r->rating,
                'stars' => str_repeat('★', (int) $r->rating).str_repeat('☆', 5 - (int) $r->rating),
                'stars_on' => str_repeat('★', (int) $r->rating),
                'stars_off' => str_repeat('★', 5 - (int) $r->rating),
                'tags' => $r->tags ?? [],
                'body' => (string) $r->body,
                'response' => $response,
                // Honest pending state — a PUBLISHED review with no operator response yet is "awaiting a reply"
                // (a real state, never a fabricated "being drafted"). The operator posts responses in-app.
                'pending' => $response === '',
                'source' => self::sourceLabel((string) $r->reservation->source),   // the REAL reservation source (OTA brand unmodelled)
                'source_key' => (string) $r->reservation->source,
                'room' => (string) ($r->roomType->name ?? ''),
                'villa' => (string) ($r->unit->name ?? $r->unit->code ?? $r->roomType->name ?? ''),
                'unit_key' => (string) ($r->unit_id ?? ''),
                'at' => $publishedAt?->format('M Y') ?? '',
                'when' => $publishedAt?->diffForHumans() ?? '',
            ];
        })->all();
    }

    /** Up-to-2 uppercase initials from the #28 byline (e.g. "Sofia M." → "SM"). */
    private static function initials(string $byline): string
    {
        $parts = array_values(array_filter(preg_split('/[\s&]+/', $byline) ?: []));
        $letters = array_map(fn (string $w): string => mb_strtoupper(mb_substr($w, 0, 1)), array_slice($parts, 0, 2));

        return implode('', $letters) ?: '·';
    }

    /** The real reservation source → a human label. The Airbnb/Booking.com brand split is unmodelled (honest). */
    private static function sourceLabel(?string $source): string
    {
        return match ($source) {
            'ota' => 'OTA',
            'walk_in' => 'Walk-in',
            'corporate' => 'Corporate',
            'group' => 'Group',
            'agent' => 'Agent',
            default => 'Direct',   // direct / manual / null
        };
    }
}
