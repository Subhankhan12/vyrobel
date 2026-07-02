<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recorded-but-unseatable OTA import (Step 4). Raised by AssignmentService::importBooking when
 * an OTA line can't occupy real capacity; resolved manually (reassign/upgrade/walk/cancel). The
 * paired `reservation_unit` is in status `overbooked` and never counted in `availability.sold`.
 */
class OverbookingConflict extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'organization_id',
        'property_id',
        'reservation_id',
        'reservation_unit_id',
        'room_type_id',
        'channel_id',
        'check_in',
        'check_out',
        'detected_at',
        'status',
        'resolution',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<ReservationUnit, $this>
     */
    public function reservationUnit(): BelongsTo
    {
        return $this->belongsTo(ReservationUnit::class);
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
