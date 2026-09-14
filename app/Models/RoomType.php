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
            // ⚠️ `relationLoaded` FIRST so an eager-loading caller is not turned into an N+1. Two callers
            // (the operator Rates grid, UnitAvailabilityService) load `defaultRatePlan` per room type
            // precisely to avoid a query per row; `$this->defaultRatePlan()->first()` would issue one anyway.
            $default = $this->relationLoaded('defaultRatePlan')
                ? $this->defaultRatePlan
                : $this->defaultRatePlan()->first();

            // ⚠️⚠️ F-34 — THE `is_active` CHECK THIS BRANCH NEVER HAD (found by FIX 12, fixed by FIX 15).
            // The fallback below always filtered; the DECLARED-DEFAULT branch did not, so a plan taken off
            // sale was still returned as the room type's primary — and the rate surfaces went on editing it
            // while the storefront had stopped offering it. ⚠️ FIX 12 built the first operator-reachable way
            // to produce that state, so this went from latent to live in the same pass that created it.
            if ($default !== null && (bool) $default->is_active) {
                return $default;
            }
        }

        // The fallback. ⚠️ It cannot come back empty in practice: `InventoryActionController::setRatePlanActive()`
        // refuses to take the LAST active plan of a room type off sale, so at least one always survives. It is
        // still written to return null rather than assume that — the guard is a route-level rule and this is a
        // model, reachable from a seeder, a job or a test that never passed through it.
        return $this->relationLoaded('ratePlans')
            ? $this->ratePlans->first(fn (RatePlan $plan): bool => (bool) $plan->is_active)
            : $this->ratePlans()->where('is_active', true)->orderBy('created_at')->first();
    }
}
