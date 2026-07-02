<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feature/limit entitlement belonging to a {@see Plan}. `limit` null = unlimited or boolean.
 */
class PlanFeature extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'plan_id',
        'key',
        'limit',
        'enabled',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'limit' => 'integer',
            'enabled' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
