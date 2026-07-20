<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A guest-initiated change REQUEST (Phase 4b · Sub-gate 3) — the coordination record for a date change, add-on,
 * or cancellation. The guest creates it `pending`; ONLY the operator moves it to `confirmed`/`declined` (and
 * executes the real inventory/folio/refund write with their certified tools). Tenant-scoped; a guest can only
 * ever create one against their OWN booking (the controller resolves through the guest's own reservations).
 *
 * @property array<string, mixed> $payload
 */
class GuestChangeRequest extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    public const TYPE_DATE_CHANGE = 'date_change';

    public const TYPE_ADD_ON = 'add_on';

    public const TYPE_CANCELLATION = 'cancellation';

    protected $fillable = [
        'organization_id',
        'reservation_id',
        'guest_id',
        'type',
        'payload',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
