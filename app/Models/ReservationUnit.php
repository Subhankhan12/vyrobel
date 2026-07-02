<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-unit inventory-occupying line item. Lifecycle via `status`, never soft delete. ALL
 * inserts/mutations of this table go through AssignmentService — no other code touches it
 * (convention #9).
 *
 * `status`: active | cancelled | moved | overbooked. `overbooked` (Step 4) is an OTA line that
 * could not occupy real capacity — recorded, flagged via `overbooking_conflicts`, and NOT counted
 * in `availability.sold`. Declared explicitly because it was added by a raw ALTER migration that
 * static analysis can't read back from the original enum definition.
 *
 * @property string $status
 */
class ReservationUnit extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'reservation_id',
        'organization_id',
        'room_type_id',
        'unit_id',
        'rate_plan_id',
        'check_in',
        'check_out',
        'adults',
        'children',
        'price',
        'price_minor',
        'currency',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'adults' => 'integer',
            'children' => 'integer',
            'price' => MoneyCast::class.':price_minor,currency',
        ];
    }

    /**
     * @param  Builder<ReservationUnit>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }
}
