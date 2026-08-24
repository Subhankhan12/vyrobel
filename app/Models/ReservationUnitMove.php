<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * A mid-stay room change: which line, from where to where, and — the point of the record — the date the
 * operator STATED the guest first slept in the new unit, alongside the moment they stated it.
 */
class ReservationUnitMove extends Model
{
    use BelongsToOrganization;
    use HasUlids;

    protected $table = 'reservation_unit_moves';

    protected $fillable = [
        'organization_id',
        'reservation_unit_id',
        'from_unit_id',
        'to_unit_id',
        'effective_date',
        'stated_at',
        'stated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'stated_at' => 'datetime',
        ];
    }

    /** ⚠️ How late the move was recorded, in days. The manipulation signal, derived not stored. */
    public function statedLateDays(): int
    {
        $effective = CarbonImmutable::parse($this->effective_date)->startOfDay();
        $stated = CarbonImmutable::parse($this->stated_at)->startOfDay();

        return (int) $effective->diffInDays($stated);
    }
}
