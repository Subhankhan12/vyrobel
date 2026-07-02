<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\ChannelMappingGuard;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Maps one of our `room_type`s or `rate_plan`s to the provider's id, with a health `status`.
 * `kind` distinguishes the two; exactly one of `roomType` / `ratePlan` is set. Only a
 * `status = active` mapping is usable — the {@see ChannelMappingGuard} treats
 * anything else as unmapped.
 */
class ChannelMapping extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    public const KIND_ROOM_TYPE = 'room_type';

    public const KIND_RATE_PLAN = 'rate_plan';

    public const STATUS_ACTIVE = 'active';

    protected $fillable = [
        'organization_id',
        'channel_id',
        'kind',
        'room_type_id',
        'rate_plan_id',
        'external_id',
        'status',
        'last_verified_at',
        'last_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_verified_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * @return BelongsTo<RatePlan, $this>
     */
    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }
}
