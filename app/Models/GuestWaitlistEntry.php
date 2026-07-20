<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A guest's waitlist entry for a room type on sold-out dates (Phase 4c · Sub-gate 4) — exact dates OR a flexible
 * month. Guest-owned + tenant-scoped; self-only. The store, re-confirm and expiry are real; the notify trigger
 * (a cancellation freeing a waitlisted date → Phase-3 email) and auto-hold are deferred honestly.
 *
 * @property Carbon|null $check_in
 * @property Carbon|null $check_out
 * @property Carbon|null $expires_at
 * @property Carbon|null $confirmed_at
 * @property bool $notify_similar
 */
class GuestWaitlistEntry extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_NOTIFIED = 'notified';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'organization_id',
        'guest_id',
        'room_type_id',
        'check_in',
        'check_out',
        'flexible_month',
        'notify_similar',
        'status',
        'expires_at',
        'confirmed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'notify_similar' => 'boolean',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * @return BelongsTo<RoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
