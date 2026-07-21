<?php

namespace App\Models;

use App\Support\Billing\SubscriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The tenant. This table IS the tenant boundary, so it does NOT use BelongsToOrganization;
 * every other business model points back here via `organization_id`.
 */
class Organization extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'mode',
        'default_currency',
        'default_locale',
        'country',
        'status',
        'billing_email',
        'custom_domain',
        'branding',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'branding' => 'array',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->using(Membership::class)
            ->withPivot(['id', 'status', 'invited_at'])
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * The org's current (latest) subscription (Phase 7b). Read with an EXPLICIT org filter and no global
     * scope so it is safe to call in any context — including outside a tenant request (`Subscription` is
     * `BelongsToOrganization`, whose scope would otherwise throw with no tenant context); the explicit
     * `organization_id` predicate is the scope's own boundary, so this never leaks another org's row.
     */
    public function currentSubscription(): ?Subscription
    {
        return Subscription::withoutGlobalScopes()
            ->where('organization_id', $this->id)
            ->latest()
            ->first();
    }

    /** The derived billing/access state for this org (trial/active/lapsed, remaining days, …). */
    public function billingStatus(): SubscriptionStatus
    {
        return new SubscriptionStatus($this->currentSubscription());
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }
}
