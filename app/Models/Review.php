<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
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
