<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Support\Pricing\NightlyRate;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * D18-5 — a nightly price for ONE unit on ONE rate plan, overriding that plan's room-type price for that
 * night only. Its ABSENCE is meaningful: no row means "no override", which resolves to the type price.
 *
 * ⚠️ AN ABSENT OVERRIDE IS NOT A ZERO. Reading a missing row as €0 would price a night at nothing, and the
 * whole point of the fallback is that a unit nobody has priced individually keeps the price it has today.
 * {@see NightlyRate} is the only thing allowed to make that decision.
 */
class UnitRateOverride extends Model
{
    use BelongsToOrganization;
    use HasUlids;

    protected $fillable = ['organization_id', 'rate_plan_id', 'unit_id', 'date', 'price_minor', 'currency'];

    protected function casts(): array
    {
        return ['date' => 'date', 'price_minor' => 'integer'];
    }
}
