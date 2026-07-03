<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An owner payout (Phase 3 · Module 1 Step 5) — the disbursement of a finalized statement's net
 * payable. Append-only lifecycle: a failed/reversed payout is superseded by a NEW row
 * (`supersedes_id`), never edited away. `amount` is Money in the owner's payout currency.
 */
class Payout extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'owner_statement_id',
        'amount_minor',
        'currency',
        'rail',
        'status',
        'external_ref',
        'connect_account_id',
        'supersedes_id',
        'initiated_by',
        'failure_reason',
        'notes',
        'paid_at',
        'failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class.':amount_minor,currency',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
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
     * @return BelongsTo<OwnerStatement, $this>
     */
    public function statement(): BelongsTo
    {
        return $this->belongsTo(OwnerStatement::class, 'owner_statement_id');
    }

    /**
     * The prior payout this one replaces (a retry of a failed payout, or a reversal), if any.
     *
     * @return BelongsTo<Payout, $this>
     */
    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(Payout::class, 'supersedes_id');
    }
}
