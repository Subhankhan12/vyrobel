<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTags;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One booking, any source. `source` distinguishes direct/OTA/walk-in/corporate/group/agent —
 * there is exactly one reservations table (convention #8). Money fields are Money value objects.
 */
class Reservation extends Model
{
    use BelongsToOrganization, HasDocuments, HasFactory, HasNotes, HasTags, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'property_id',
        'reference',
        'source',
        'expected_arrival',
        'channel_id',
        'ota_reservation_id',
        'status',
        'check_in',
        'check_out',
        'adults',
        'children',
        'currency',
        'total',
        'total_minor',
        'balance',
        'balance_minor',
        'deposit',
        'deposit_minor',
        'ota_commission_minor',
        'payment_status',
        'lead_guest_id',
        'group_id',
        'corporate_account_id',
        'booked_at',
        'cancelled_at',
        'cancellation_reason',
        'guest_checked_in_at',
        'arrival_window',
        'house_rules_ack_at',
        'notes_guest',
        'notes_internal',
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
            'total' => MoneyCast::class.':total_minor,currency',
            'balance' => MoneyCast::class.':balance_minor,currency',
            'deposit' => MoneyCast::class.':deposit_minor,currency',
            'booked_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'guest_checked_in_at' => 'datetime',
            'house_rules_ack_at' => 'datetime',
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
     * The OTA channel this booking came in through (null for direct/walk-in). FK closed in Step 2.
     *
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function leadGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'lead_guest_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function corporateAccount(): BelongsTo
    {
        return $this->belongsTo(CorporateAccount::class);
    }

    /**
     * @return HasMany<ReservationUnit, $this>
     */
    public function reservationUnits(): HasMany
    {
        return $this->hasMany(ReservationUnit::class);
    }

    public function reservationGuests(): HasMany
    {
        return $this->hasMany(ReservationGuest::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ReservationStatusHistory::class);
    }

    /**
     * @return HasMany<OverbookingConflict, $this>
     */
    public function overbookingConflicts(): HasMany
    {
        return $this->hasMany(OverbookingConflict::class);
    }
}
