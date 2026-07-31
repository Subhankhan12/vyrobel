<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A sellable price configuration for a room_type. `derivedFrom` supports derived rates
 * (e.g. parent −10% via `adjustment`).
 *
 * @property string $name
 * @property string $currency
 * @property string $room_type_id
 * @property bool $is_refundable
 * @property bool $is_active
 * @property string|null $derived_from_rate_plan_id
 * @property array<string, mixed>|null $adjustment
 * @property string|null $weekend_uplift_pct
 */
class RatePlan extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'property_id',
        'room_type_id',
        'name',
        'code',
        'currency',
        'is_refundable',
        'cancellation_policy',
        'derived_from_rate_plan_id',
        'adjustment',
        'weekend_uplift_pct',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_refundable' => 'boolean',
            'cancellation_policy' => 'array',
            'adjustment' => 'array',
            'weekend_uplift_pct' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function derivedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'derived_from_rate_plan_id');
    }

    public function derivedRates(): HasMany
    {
        return $this->hasMany(self::class, 'derived_from_rate_plan_id');
    }

    public function calendar(): HasMany
    {
        return $this->hasMany(RateCalendar::class);
    }

    public function restrictions(): HasMany
    {
        return $this->hasMany(Restriction::class);
    }
}
