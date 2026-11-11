<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\OwnerPayoutService;
use App\Support\Owner\OwnerPortalAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * An external owner/investor (Phase 3 · Module 1) — a person or company that owns `units` and
 * receives periodic payouts. Org-scoped, optionally linked to a `User` for owner-portal login via
 * the Phase 1 External Owner role. PII (`email`/`phone`/`tax_id`) is encrypted at rest (convention
 * #5). `payout_currency` is the currency the owner is PAID in (may differ from booking currency).
 *
 * @property array<string, mixed>|null $address
 * @property Carbon|null $portal_disabled_at DEC-o — when an operator switched this owner's portal access off; null = on
 */
class Owner extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'type',
        'name',
        'legal_name',
        'email',
        'phone',
        'tax_id',
        'tax_country',
        'vat_number',
        'vat_registered',
        'address',
        'payout_currency',
        'user_id',
        'stripe_connect_account_id',
        'stripe_connect_payouts_enabled',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'tax_id' => 'encrypted',
            'vat_number' => 'encrypted',
            'vat_registered' => 'boolean',
            'address' => 'array',
            'stripe_connect_payouts_enabled' => 'boolean',
            'portal_disabled_at' => 'datetime',
        ];
    }

    /**
     * ⚠️ DEC-o — THE ONE DEFINITION OF "THIS OWNER'S PORTAL IS ON / OFF". `portal_disabled_at` is null while the portal is on.
     * Every reader asks through these (or through {@see OwnerPortalAccess}), so "off" cannot be spelled two ways.
     *
     * @param  Builder<Owner>  $query
     * @return Builder<Owner>
     */
    public function scopePortalOn(Builder $query): Builder
    {
        return $query->whereNull($query->qualifyColumn('portal_disabled_at'));
    }

    /**
     * @param  Builder<Owner>  $query
     * @return Builder<Owner>
     */
    public function scopePortalOff(Builder $query): Builder
    {
        return $query->whereNotNull($query->qualifyColumn('portal_disabled_at'));
    }

    public function portalIsOff(): bool
    {
        return $this->portal_disabled_at !== null;
    }

    /**
     * Whether the owner's tax details are complete enough to release payouts — i.e. a tax ID is on file. This
     * is the ONE definition of "tax complete", read by {@see OwnerPayoutService::pay()} (the
     * genuine payout hold) and the Tax Center. `tax_id` is an encrypted column; this only reads its presence.
     */
    public function hasTaxDetails(): bool
    {
        return filled($this->tax_id);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * The owner's operator-written note timeline (#93) — polymorphic {@see Note} entries. Named `noteEntries`,
     * NOT `notes`, because `owners.notes` is a legacy single free-text column; a `notes()` relation would shadow
     * that attribute. Authored + timestamped; operator-internal (never shown in the owner portal).
     *
     * @return MorphMany<Note, $this>
     */
    public function noteEntries(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    /**
     * The owner's team — delegate sub-users with an owner-team role (Phase 7c). The primary owner
     * (`user_id`) is the implicit Co-owner and is NOT in this list.
     *
     * @return HasMany<OwnerUser, $this>
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(OwnerUser::class);
    }

    /**
     * @return HasMany<ManagementAgreement, $this>
     */
    public function agreements(): HasMany
    {
        return $this->hasMany(ManagementAgreement::class);
    }
}
