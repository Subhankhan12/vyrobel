<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyPolicy extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'property_id',
        'cancellation',
        'child_policy',
        'pet',
        'smoking',
        'deposit',
        'damage',
        'min_stay',
        'max_stay',
        'long_stay_discounts',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cancellation' => 'array',
            'child_policy' => 'array',
            'deposit' => 'array',
            'damage' => 'array',
            'long_stay_discounts' => 'array',
            'min_stay' => 'integer',
            'max_stay' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
