<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasPhotos;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sellable category. `default_rate_plan_id` is a forward hook to rate_plans (Step 3) — a
 * plain nullable ULID column with no relation/FK yet.
 *
 * `bedrooms`/`std_occupancy` are nullable columns; the `integer` cast makes Larastan infer a
 * non-null int, so they carry explicit nullable @property types (same pattern as other cast edge
 * cases in the codebase).
 *
 * @property int|null $bedrooms
 * @property int|null $std_occupancy
 */
class RoomType extends Model
{
    use BelongsToOrganization, HasAmenities, HasFactory, HasMedia, HasPhotos, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'property_id',
        'name',
        'code',
        'kind',
        'std_occupancy',
        'max_adults',
        'max_children',
        'bedrooms',
        'is_virtual',
        'parent_room_type_id',
        'default_rate_plan_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'std_occupancy' => 'integer',
            'max_adults' => 'integer',
            'max_children' => 'integer',
            'bedrooms' => 'integer',
            'is_virtual' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_room_type_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_room_type_id');
    }

    /**
     * @return HasMany<RatePlan, $this>
     */
    public function ratePlans(): HasMany
    {
        return $this->hasMany(RatePlan::class);
    }

    public function availability(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    /**
     * @return BelongsTo<RatePlan, $this>
     */
    public function defaultRatePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class, 'default_rate_plan_id');
    }

    /**
     * The room type's PRIMARY rate plan: its declared default, else its oldest active plan (null if it has
     * none). Rates are stored per rate plan (`rate_calendar.rate_plan_id`), so both the operator Rates grid
     * and the owner Rates screen edit exactly this one plan per room type — ONE definition here so the two
     * surfaces can never drift about which plan a room type's rate lives on.
     */
    public function primaryRatePlan(): ?RatePlan
    {
        if ($this->default_rate_plan_id !== null) {
            $default = $this->defaultRatePlan()->first();
            if ($default !== null) {
                return $default;
            }
        }

        return $this->ratePlans()->where('is_active', true)->orderBy('created_at')->first();
    }
}
