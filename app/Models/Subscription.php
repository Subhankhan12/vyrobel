<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Tenant billing state. `stripe_subscription_id` stays null until live Stripe Billing (account-gated).
 *
 * @property string $status
 * @property ?Carbon $trial_ends_at
 * @property ?Carbon $current_period_end
 * @property ?string $stripe_subscription_id
 * @property-read ?Plan $plan
 */
class Subscription extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'plan_id',
        'stripe_subscription_id',
        'status',
        'trial_ends_at',
        'current_period_end',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_end' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }
}
