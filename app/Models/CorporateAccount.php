<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Company / travel-agent account with negotiated terms.
 */
class CorporateAccount extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'billing',
        'default_rate_plan_id',
        'commission_pct',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing' => 'array',
            'commission_pct' => 'decimal:2',
        ];
    }

    public function defaultRatePlan(): BelongsTo
    {
        return $this->belongsTo(RatePlan::class, 'default_rate_plan_id');
    }
}
