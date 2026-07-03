<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
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
        'address',
        'payout_currency',
        'user_id',
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
            'address' => 'array',
        ];
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
     * @return HasMany<ManagementAgreement, $this>
     */
    public function agreements(): HasMany
    {
        return $this->hasMany(ManagementAgreement::class);
    }
}
