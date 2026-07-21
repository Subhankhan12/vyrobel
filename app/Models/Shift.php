<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\ShiftRosterService;
use App\Support\Schedule\ShiftType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A staff ROSTER SHIFT (Phase 7d · Team Schedule) — a real {@see User} on a {@see ShiftType} for one date at
 * one property. TENANT + PROPERTY scoped (BelongsToOrganization + `property_id`): the roster is per-property,
 * so a shift always belongs to the selected property. Written ONLY through {@see ShiftRosterService}.
 *
 * @property string $property_id
 * @property string $user_id
 * @property string $shift_type
 * @property Carbon $date
 */
class Shift extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'property_id',
        'user_id',
        'date',
        'shift_type',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
