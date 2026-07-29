<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An owner statement (Phase 3 · Module 1 Step 4) — a period projection of `owner_ledger`. Draft while
 * being prepared (regenerating yields identical numbers); FINALIZED locks the period (`locked=true`)
 * and stamps its claimed ledger lines, making it immutable. Breakdown figures are in `source_currency`;
 * `net_payable_minor` is in the payout `currency` (converted at `fx_rate` when they differ). No
 * SoftDeletes — a superseded draft is regenerated, a finalized one is never deleted.
 *
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property ?Carbon $finalized_at
 */
class OwnerStatement extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'period_start',
        'period_end',
        'currency',
        'source_currency',
        'status',
        'locked',
        'gross_minor',
        'other_revenue_minor',
        'commission_minor',
        'management_fee_minor',
        'expenses_minor',
        'ota_cost_minor',
        'payment_fee_minor',
        'withholding_minor',
        'adjustments_minor',
        'net_source_minor',
        'fx_rate',
        'fx_source',
        'net_payable_minor',
        'has_unverified',
        'sequence_no',
        'finalized_at',
        'finalized_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'locked' => 'boolean',
            'has_unverified' => 'boolean',
            'fx_rate' => 'decimal:10',
            'finalized_at' => 'datetime',
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked === true;
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * @return HasMany<OwnerStatementLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OwnerStatementLine::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<OwnerLedgerEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(OwnerLedgerEntry::class, 'statement_id');
    }
}
