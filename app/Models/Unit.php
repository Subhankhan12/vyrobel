<?php

namespace App\Models;

use App\Jobs\Billing\SyncSubscriptionQuantityJob;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasNotes;
use App\Support\Billing\BillableUnits;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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
 *
 * @property Carbon|null $owner_since DEC-p — the first day `owner_id` is paid for this unit's stays; NULL = always (see {@see ownerIdForStayStarting()})
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
        'owner_since',
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
            // DEC-p — the first day the owner is paid for this unit's stays; NULL = always (see the migration + OwnerAttributionService)
            'owner_since' => 'date',
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
     * ⚠️⚠️ THE ONE ANSWER TO "WHO IS PAID FOR A STAY THAT STARTS ON THIS DAY?" (DEC-p, 2026-11-12) — the unit's owner, or null when it was operator
     * inventory then. `owner_since` is the first day the owner is paid for the unit's stays: NULL = always (every unit before DEC-p, and every unit an
     * invite or an approved request gives an owner — unchanged), a date = a stay that STARTS before it is not theirs. Earnings, the new-booking
     * notification and a repair's cost all ask HERE, so a second reading of "who owns it" cannot drift from the one that decides where the money goes.
     */
    public function ownerIdForStayStarting(CarbonInterface|string $date): ?string
    {
        if ($this->owner_id === null) {
            return null;
        }

        if ($this->owner_since !== null && Carbon::parse($date)->toDateString() < $this->owner_since->toDateString()) {
            return null;
        }

        return (string) $this->owner_id;
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

    /**
     * ⚠️⚠️ THE BILLING CHOKE POINT (F3, 2026-09-01) — the ONE place a unit-count change reaches billing.
     *
     * A per-unit subscription is charged a QUANTITY, and {@see BillableUnits::count()}
     * derives that quantity from THESE rows. Until this hook existed, `syncSubscriptionQuantity()` was
     * complete and had no caller at all: an org on a per-unit plan could add units forever and keep paying
     * for its original count.
     *
     * ⚠️ WHY THE MODEL AND NOT THE THREE WRITERS. Three paths change the count today — the operator's
     * `POST /units`, the operator approving an owner's unit request, and the owner's pause/resume toggle —
     * and a call placed on each is the same defect in a new place the moment a fourth appears. Every one of
     * them goes through an Eloquent write on THIS model, so the model is the only site that cannot be
     * forgotten. ⚠️ Its one real blind spot is a mass `Unit::query()->update()`, which fires no events; the
     * two that exist touch `owner_id` only and cannot move the count.
     *
     * ⚠️ `updated` IS FILTERED. A rename, a housekeeping-status change or an occupancy edit does not change
     * what is billable, and dispatching on those would send Stripe a quantity it already has.
     */
    protected static function booted(): void
    {
        $sync = function (self $unit): void {
            $organizationId = $unit->organization_id;

            // Never an empty payload: `BelongsToOrganization` stamps this on create and THROWS without a
            // tenant context, so a unit with no org cannot reach here — but a job keyed on '' would sync
            // the wrong thing rather than nothing, which is the failure worth spending a branch on.
            if ($organizationId !== '') {
                SyncSubscriptionQuantityJob::dispatch($organizationId);
            }
        };

        static::created($sync);
        static::deleted($sync);     // soft-delete AND force-delete both fire this
        static::restored($sync);

        // Of this row's columns, only `is_active` moves the billable count. ⚠️ The count ALSO depends on the
        // unit's property being live ({@see BillableUnits}), but that changes on the PROPERTY, which is why
        // {@see Property::booted()} carries its own hook rather than this one trying to see across the FK.
        static::updated(function (self $unit) use ($sync): void {
            if ($unit->wasChanged('is_active')) {
                $sync($unit);
            }
        });
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
