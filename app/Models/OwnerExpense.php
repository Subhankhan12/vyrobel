<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An owner-attributable cost (Phase 3 · Module 1 Step 3). Attributes to an owner + unit and, when
 * deductible per the governing agreement, posts a DEDUCTION line to `owner_ledger`. `source` links
 * to the EXISTING record it came from (housekeeping_task / unit / future maintenance ticket) — reuse,
 * never a parallel cost log. A non-deductible expense (`deductible=false`) is the operator's cost and
 * is recorded WITHOUT an owner-ledger line. `flag` explains any non-attribution (no_owner/no_agreement).
 */
class OwnerExpense extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'unit_id',
        'category',
        'description',
        'amount_minor',
        'currency',
        'expense_date',
        'deductible',
        'source_type',
        'source_id',
        'attributed_ledger_id',
        'flag',
        'note',
        'created_by',
        'voided_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class.':amount_minor,currency',
            'expense_date' => 'date',
            'deductible' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * The existing record this expense came from (housekeeping_task / unit / …), if any.
     *
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<OwnerLedgerEntry, $this>
     */
    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(OwnerLedgerEntry::class, 'attributed_ledger_id');
    }
}
