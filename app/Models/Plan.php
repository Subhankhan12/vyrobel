<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Global product package catalog (not tenant-scoped). `price` is a Money value object over
 * the `price_minor` + `currency` columns.
 *
 * @property string $code
 * @property string $name
 * @property int $price_minor
 * @property string $currency
 * @property ?string $stripe_price_id
 * @property string $interval
 * @property bool $is_active
 */
class Plan extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'price_minor',
        'currency',
        'stripe_price_id',
        'interval',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => MoneyCast::class.':price_minor,currency',
            'is_active' => 'boolean',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
