<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-tenant metered usage against plan limits (e.g. max_units, ai_calls) per period.
 */
class UsageCounter extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'key',
        'period',
        'used',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'used' => 'integer',
        ];
    }
}
