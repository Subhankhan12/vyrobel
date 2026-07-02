<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A booking-engine quote + ADVISORY hold (Step 7). The hold is a UX timer only — it is NOT
 * counted in `availability.sold` and reserves NO real inventory; inventory is occupied at BOOK
 * time through AssignmentService. `email`/`phone` are encrypted PII (for abandoned-booking
 * recovery). Money fields are integer minor units via the Money cast.
 */
class BookingQuote extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    public const STATUS_HELD = 'held';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'organization_id',
        'property_id',
        'reference',
        'session_token',
        'check_in',
        'check_out',
        'adults',
        'children',
        'requested',
        'currency',
        'subtotal',
        'subtotal_minor',
        'tax',
        'tax_minor',
        'fees',
        'fees_minor',
        'discount',
        'discount_minor',
        'total',
        'total_minor',
        'promo_code',
        'guest_snapshot',
        'email',
        'phone',
        'status',
        'held_until',
        'expires_at',
        'recovery_status',
        'contacted_at',
        'converted_reservation_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'email',
        'phone',
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
            'requested' => 'array',
            'guest_snapshot' => 'array',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'subtotal' => MoneyCast::class.':subtotal_minor,currency',
            'tax' => MoneyCast::class.':tax_minor,currency',
            'fees' => MoneyCast::class.':fees_minor,currency',
            'discount' => MoneyCast::class.':discount_minor,currency',
            'total' => MoneyCast::class.':total_minor,currency',
            'held_until' => 'datetime',
            'expires_at' => 'datetime',
            'contacted_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function convertedReservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'converted_reservation_id');
    }
}
