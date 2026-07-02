<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sellable extra (breakfast, transfer, early check-in, …). `price` is Money.
 *
 * @property Money $price
 */
class AddOn extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $table = 'add_ons';

    protected $fillable = [
        'organization_id',
        'property_id',
        'name',
        'price',
        'price_minor',
        'currency',
        'taxable',
        'inventory_limit',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => MoneyCast::class.':price_minor,currency',
            'taxable' => 'boolean',
            'inventory_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
