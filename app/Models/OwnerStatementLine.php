<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One presentation line of an owner statement (Phase 3 · Module 1 Step 4). Component lines
 * (gross/other_revenue/commission/management_fee/expense/ota_cost/prior_period_adjustment) carry a
 * SIGNED `amount_minor` and sum EXACTLY to the statement's `net_source_minor`; the `net` line is a
 * summary. `unverified` surfaces pending OTA lines to the owner.
 */
class OwnerStatementLine extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'owner_statement_id',
        'line_type',
        'description',
        'amount_minor',
        'currency',
        'unverified',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unverified' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<OwnerStatement, $this>
     */
    public function statement(): BelongsTo
    {
        return $this->belongsTo(OwnerStatement::class, 'owner_statement_id');
    }
}
