<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasNotes;
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
    use BelongsToOrganization, HasAmenities, HasFactory, HasMedia, HasNotes, HasUlids, SoftDeletes;

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
        ];
    }

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
