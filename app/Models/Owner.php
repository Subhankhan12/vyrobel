<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\OwnerPayoutService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An external owner/investor (Phase 3 · Module 1) — a person or company that owns `units` and
 * receives periodic payouts. Org-scoped, optionally linked to a `User` for owner-portal login via
 * the Phase 1 External Owner role. PII (`email`/`phone`/`tax_id`) is encrypted at rest (convention
 * #5). `payout_currency` is the currency the owner is PAID in (may differ from booking currency).
 *
 * @property array<string, mixed>|null $address
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
        ];
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
