<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property array<string, mixed>|null $cancellation
 * @property array<string, mixed>|null $child_policy
 * @property array<string, mixed>|null $deposit
 * @property array<string, mixed>|null $damage
 * @property array<string, mixed>|null $long_stay_discounts
 * @property string|null $pet
 * @property string|null $smoking
 * @property bool $self_check_in
 * @property int|null $min_stay
 * @property int|null $max_stay
 */
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
        'self_check_in',
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
            'self_check_in' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
