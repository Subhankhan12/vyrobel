<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nightly price for a rate_plan on a date. `price` is a Money value object over the
 * `price_minor` + `currency` columns — never a float.
 */
class RateCalendar extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $table = 'rate_calendar';

    protected $fillable = [
        'organization_id',
        'rate_plan_id',
        'date',
        'price',        // Money value object (writes price_minor + currency via MoneyCast)
        'price_minor',
        'currency',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'price' => MoneyCast::class.':price_minor,currency',
        ];
    }

    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }
}
