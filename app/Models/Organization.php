<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use App\Support\Billing\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * The tenant. This table IS the tenant boundary, so it does NOT use BelongsToOrganization;
 * every other business model points back here via `organization_id`.
 *
 * @property Carbon|null $paused_until
 * @property Carbon|null $readonly_from
 * @property bool|null $is_internal Vyrobel's own test/demo/probe org — excluded from platform statistics.
 *                                  ⚠️ Deliberately NOT fillable: only the audited console action (and the demo seeder) may set it.
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
        'paused_until',
        'readonly_from',
        'billing_email',
        'billing_company',
        'billing_vat_id',
        'billing_city',
        'invoice_series_prefix',
        'invoice_next_number',
        'invoice_auto_issue',
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
            'paused_until' => 'datetime',
            'readonly_from' => 'datetime',
            'is_internal' => 'boolean',
        ];
    }

    /** Vyrobel's own test/demo/probe organisation — never counted as a customer. */
    public function isInternal(): bool
    {
        return (bool) $this->is_internal;
    }

    /**
     * ⚠️ THE ONE DEFINITION of "a customer" for every platform statistic: not marked internal. NULL counts as a
     * customer — a new signup is a customer until somebody says otherwise, never the reverse.
     *
     * @param  Builder<self>  $query
     */
    public function scopeCustomers($query): void
    {
        $query->where(fn ($q) => $q->whereNull('is_internal')->orWhere('is_internal', false));
    }

    /** @param  Builder<self>  $query */
    public function scopeInternal($query): void
    {
        $query->where('is_internal', true);
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
        return Subscription::withoutGlobalScopes([OrganizationScope::class])
            ->where('organization_id', $this->id)
            ->latest()
            ->first();
    }

    /** The derived billing/access state for this org (trial/active/lapsed, remaining days, …). */
    public function billingStatus(): SubscriptionStatus
    {
        return new SubscriptionStatus($this->currentSubscription());
    }

    /**
     * Whether the org's current PLAN grants a feature (Phase 7b feature-gating) — e.g. `channels` is Pro-only.
     * FAILS OPEN on a missing subscription (never gate an org with no billing record); returns false while
     * lapsed (no access at all); otherwise reads the real `plan_features` row for the org's plan (an unknown
     * feature key defaults to allowed, so gating is opt-in per key).
     */
    public function planAllows(string $feature): bool
    {
        $subscription = $this->currentSubscription();
        if ($subscription === null) {
            return true;
        }

        if (! $this->billingStatus()->hasFullAccess()) {
            return false;
        }

        $planFeature = PlanFeature::query()
            ->where('plan_id', $subscription->plan_id)
            ->where('key', $feature)
            ->first();

        return $planFeature === null ? true : (bool) $planFeature->enabled;
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
