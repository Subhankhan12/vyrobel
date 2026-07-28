<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
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
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids, SoftDeletes;

    // Approval state (Wave C · GATE 2). A DEDUCTIBLE expense is created `pending` and only reduces the owner's net
    // once APPROVED (the ledger line posts then); QUERIED holds it out of the net. Non-deductible → `approved`.
    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_QUERIED = 'queried';

    protected $fillable = [
        'organization_id',
        'owner_id',
        'unit_id',
        'category',
        'description',
        'vendor',
        'amount_minor',
        'currency',
        'expense_date',
        'deductible',
        'approval_state',
        'decided_at',
        'decided_by',
        'queried_note',
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
            'decided_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /** Awaiting the owner's decision — a deductible expense that has NOT yet been charged to the ledger. */
    public function isPendingApproval(): bool
    {
        return $this->approval_state === self::APPROVAL_PENDING;
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
