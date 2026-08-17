<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\SeasonalRateService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A named seasonal ("peak") price for a rate plan over an inclusive date range.
 *
 * ⚠️ AN AUTHORING RECORD, NOT A PRICE SOURCE. Nothing reads this at charge time: applying a season writes
 * its nights into `rate_calendar` through the certified writer, and the charge reads `rate_calendar`.
 * {@see SeasonalRateService} — and the migration for why read-time resolution was rejected.
 *
 * @property string $id
 * @property string $rate_plan_id
 * @property string $name
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property int $price_minor
 * @property string $currency
 * @property Carbon|null $applied_at
 * @property-read RatePlan|null $ratePlan
 */
class RateSeason extends Model
{
    use BelongsToOrganization;
    use HasUlids;

    protected $fillable = [
        'organization_id', 'rate_plan_id', 'name', 'starts_on', 'ends_on', 'price_minor', 'currency', 'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'price_minor' => 'integer',
            'applied_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<RatePlan, $this> */
    public function ratePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class);
    }

    /** Whether this season covers a given Y-m-d — inclusive at both ends, the way it reads to a person. */
    public function covers(string $date): bool
    {
        return $date >= $this->starts_on->toDateString() && $date <= $this->ends_on->toDateString();
    }

    /** Nights in the season — inclusive, so 1–3 July is three nights. */
    public function nights(): int
    {
        return (int) $this->starts_on->diffInDays($this->ends_on) + 1;
    }
}
