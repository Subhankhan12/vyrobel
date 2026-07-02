<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stay rules per room_type/rate_plan per date (thin in Phase 1). Either scope may be null.
 */
class Restriction extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'room_type_id',
        'rate_plan_id',
        'date',
        'min_stay',
        'max_stay',
        'closed_to_arrival',
        'closed_to_departure',
        'stop_sell',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'min_stay' => 'integer',
            'max_stay' => 'integer',
            'closed_to_arrival' => 'boolean',
            'closed_to_departure' => 'boolean',
            'stop_sell' => 'boolean',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }
}
