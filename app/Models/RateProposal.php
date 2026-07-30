<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A RATE PROPOSAL (Wave C · GATE 1) — an operator's proposed rate change to one of an owner's exclusively-owned
 * room types, awaiting the owner's decision. Owner-scoped ({@see BelongsToOwner}) so a cross-owner proposal is
 * invisible + un-actionable. The money fields are integer minor units (#17); the current-value snapshot lets the
 * owner see a truthful before/after. State transitions live in {@see App\Services\RateProposalService}.
 *
 * @property ?Carbon $decided_at
 * @property ?Carbon $from_date
 * @property ?Carbon $to_date
 */
class RateProposal extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids;

    public const STATE_PENDING = 'pending';

    public const STATE_APPROVED = 'approved';

    public const STATE_DECLINED = 'declined';

    public const STATE_WITHDRAWN = 'withdrawn';

    /** The owner has proposed a DIFFERENT rate back to the operator (Owner Rates #81). Nothing applied yet. */
    public const STATE_COUNTERED = 'countered';

    protected $fillable = [
        'organization_id',
        'owner_id',
        'room_type_id',
        'proposed_by',
        'currency',
        'current_rate_minor',
        'proposed_rate_minor',
        'current_weekend_pct',
        'proposed_weekend_pct',
        'current_min_stay',
        'proposed_min_stay',
        'countered_rate_minor',
        'countered_note',
        'from_date',
        'to_date',
        'note',
        'state',
        'decided_at',
        'decided_by',
        'countered_at',
        'countered_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'decided_at' => 'datetime',
            'countered_at' => 'datetime',
            'countered_rate_minor' => 'integer',
            'current_weekend_pct' => 'decimal:2',
            'proposed_weekend_pct' => 'decimal:2',
        ];
    }

    public function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    public function isCountered(): bool
    {
        return $this->state === self::STATE_COUNTERED;
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
}
