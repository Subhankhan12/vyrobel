<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A guest's review of a REAL completed stay (Phase 5). Tenant-scoped. Tied to one reservation (one review per
 * stay). PUBLICLY visible iff `visibility = 'public'` AND `status = 'published'` — a `private` review (sent to the
 * manager) is never public; a `pending`/`rejected` public review is not public either. The rating is DERIVED
 * elsewhere (App\Support\Reviews\ReviewRating) from published+public reviews only — never stored as an average.
 *
 * @property int $rating
 * @property array<int, string>|null $tags
 * @property string $visibility
 * @property string $status
 */
class Review extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PRIVATE = 'private';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    /** The optional per-category sub-ratings (guest-set 1–5) → the owner "By category" averages. */
    public const CATEGORIES = [
        'rating_cleanliness' => 'Cleanliness',
        'rating_communication' => 'Communication',
        'rating_location' => 'Location',
        'rating_value' => 'Value',
    ];

    /** A rating-category CODE ({@see CATEGORIES}' key) → its label in the reader's language, translated at call time (I18N-01). */
    public static function categoryLabel(string $key): string
    {
        return match ($key) {
            'rating_cleanliness' => (string) __('php/models/review.category_cleanliness'),
            'rating_communication' => (string) __('php/models/review.category_communication'),
            'rating_location' => (string) __('php/models/review.category_location'),
            'rating_value' => (string) __('php/models/review.category_value'),
            default => self::CATEGORIES[$key] ?? $key,
        };
    }

    /**
     * Every rating category, CODE → translated label, in {@see CATEGORIES} order (keyed by the code, never a label).
     *
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        $labels = [];
        foreach (array_keys(self::CATEGORIES) as $key) {
            $labels[$key] = self::categoryLabel($key);
        }

        return $labels;
    }

    /**
     * ⚠️ ONLY THE REVIEWS OF STAYS THAT ARE THE OWNER'S (D-OWNERSINCE-WINDOWS, 2026-11-12) — the review's own stay, on its unit, began on or after the day the unit
     * became the owner's (`units.owner_since`). The same rule as {@see ReservationUnit::scopeOwnerStays()}, asked through the review's stay: a guest's first name
     * and rating from before the owner's day are not the owner's to read. A unit with no `owner_since` — and a review with no unit — keeps every review.
     *
     * @param  Builder<Review>  $query
     */
    public function scopeOwnerStays(Builder $query): void
    {
        $query->whereNotExists(function ($units): void {
            $units->selectRaw('1')->from('units')
                ->whereColumn('units.id', 'reviews.unit_id')
                ->whereNotNull('units.owner_since')
                ->whereExists(function ($stays): void {
                    $stays->selectRaw('1')->from('reservation_units')
                        ->whereColumn('reservation_units.reservation_id', 'reviews.reservation_id')
                        ->whereColumn('reservation_units.unit_id', 'reviews.unit_id')
                        ->whereColumn('reservation_units.check_in', '<', 'units.owner_since');
                });
        });
    }

    protected $fillable = [
        'organization_id',
        'guest_id',
        'reservation_id',
        'unit_id',
        'room_type_id',
        'rating',
        'rating_cleanliness',
        'rating_communication',
        'rating_location',
        'rating_value',
        'tags',
        'body',
        'visibility',
        'status',
        'operator_response',
        'responded_at',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'rating_cleanliness' => 'integer',
            'rating_communication' => 'integer',
            'rating_location' => 'integer',
            'rating_value' => 'integer',
            'tags' => 'array',
            'responded_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /** Public-facing iff the guest chose public AND the operator has published it. The single source of that truth. */
    public function isPubliclyVisible(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC && $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
