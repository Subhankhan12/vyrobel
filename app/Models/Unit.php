<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasNotes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The physical sellable thing. `owner_id` is a nullable forward hook (owners module, Phase 3)
 * with no relation/FK yet. Inventory-affecting writes go through AssignmentService (Step 5).
 */
class Unit extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasAmenities, HasFactory, HasMedia, HasNotes, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'property_id',
        'room_type_id',
        'building_id',
        'floor_id',
        'code',
        'name',
        'unit_kind',
        'max_occupancy',
        'owner_id',
        'housekeeping_status',
        'condition_status',
        'door_code',
        'wifi_ssid',
        'wifi_password',
        'arrival_notes',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_occupancy' => 'integer',
            'is_active' => 'boolean',
            // Physically-sensitive arrival secrets — encrypted at rest (defence-in-depth beneath the
            // owned-booking + arrival-window guards that govern who ever sees them). See ArrivalPack.
            'door_code' => 'encrypted',
            'wifi_password' => 'encrypted',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * The external owner of this unit, if any (Phase 3 · Module 1). Null for operator-owned
     * inventory such as hotel rooms.
     *
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /** Maintenance conditions that make a physical unit unbookable (no new/moved stay may land on it). */
    public const UNBOOKABLE_CONDITIONS = ['out_of_order', 'out_of_service'];

    /**
     * BOOKABLE units only: active (not owner-paused, #69) AND in service (not maintenance out-of-service/
     * out-of-order). This is EXACTLY the set the availability count treats as sellable — AvailabilityService
     * counts only `is_active` units in `total` and subtracts the out-of-service ones — so any read that offers
     * a unit for a NEW booking must use this scope, or it will offer a unit the count already excluded (an
     * unbookable, non-reconciling option). Occupancy / block overlap is a separate, date-scoped check.
     *
     * @param  Builder<Unit>  $query
     * @return Builder<Unit>
     */
    public function scopeBookable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNotIn('condition_status', self::UNBOOKABLE_CONDITIONS);
    }

    /**
     * The per-instance counterpart of {@see scopeBookable()} — the SAME rule, for guarding a chosen target
     * unit on a WRITE (a new booking's / a move's destination) so it can never land on a paused or
     * out-of-service unit. One rule, two forms (query + instance), sharing UNBOOKABLE_CONDITIONS.
     */
    public function isBookable(): bool
    {
        return (bool) $this->is_active
            && ! in_array((string) $this->condition_status, self::UNBOOKABLE_CONDITIONS, true);
    }

    public function inventoryBlocks(): HasMany
    {
        return $this->hasMany(InventoryBlock::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(UnitStatusLog::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(UnitRelationship::class);
    }
}
