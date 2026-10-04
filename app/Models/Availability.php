<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sellable-count source of truth per room_type per date. Rows are written ONLY by
 * AvailabilityService (total/out_of_order/blocked) and, from Step 5, by AssignmentService
 * inside its locked transaction (the `sold` term). `available` is computed, never stored.
 */
class Availability extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $table = 'availability';

    protected $fillable = [
        'organization_id',
        'room_type_id',
        'date',
        'total',
        'sold',
        'out_of_order',
        'blocked',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total' => 'integer',
            'sold' => 'integer',
            'out_of_order' => 'integer',
            'blocked' => 'integer',
        ];
    }

    /**
     * available = total − sold − out_of_order − blocked (never negative).
     */
    public function available(): int
    {
        return self::left((int) $this->total, (int) $this->sold, (int) $this->out_of_order, (int) $this->blocked);
    }

    /**
     * Rooms left to sell on a night — the ONE formula, for a model or for a plain row read without one (the New reservation
     * calendar reads a year of rows; hydrating a model for each cost ~260 ms at 20 room types — the 2026-10-04 review).
     */
    public static function left(int $total, int $sold, int $outOfOrder, int $blocked): int
    {
        return max(0, $total - $sold - $outOfOrder - $blocked);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
