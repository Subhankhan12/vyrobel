<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Lang;

/**
 * One presentation line of an owner statement (Phase 3 · Module 1 Step 4). Component lines
 * (gross/other_revenue/commission/management_fee/expense/ota_cost/prior_period_adjustment) carry a
 * SIGNED `amount_minor` and sum EXACTLY to the statement's `net_source_minor`; the `net` line is a
 * summary. `unverified` surfaces pending OTA lines to the owner.
 */
class OwnerStatementLine extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'owner_statement_id',
        'owner_id',
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
     * I18N-01 — the words for this line in the reader's language. The row stores its English description (the statement is a
     * record, written once); a line whose description is the standard English for its type is said in the reader's language,
     * anything else (an older or hand-written wording) shows as stored. In English this is the stored description, byte for byte.
     */
    public function label(): string
    {
        $key = 'php/models/owner_statement_line.'.$this->line_type;
        $description = (string) $this->description;

        return Lang::has($key, 'en', false) && Lang::get($key, [], 'en', false) === $description ? (string) __($key) : $description;
    }

    /**
     * @return BelongsTo<OwnerStatement, $this>
     */
    public function statement(): BelongsTo
    {
        return $this->belongsTo(OwnerStatement::class, 'owner_statement_id');
    }
}
