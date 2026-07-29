<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Llm\LlmCostLedger;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The Ask-Vyrobel cost ledger row (Wave D · support AI · Phase 2) — per owner, per day: request + token counts.
 * Read/written by {@see LlmCostLedger}. Holds NO owner PII (#28 trivial). Not tenant-scoped
 * (platform cost accounting keyed by owner id); read only by the ledger service, never a tenant query.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property Carbon $usage_date
 * @property int $request_count
 * @property int $token_count
 */
class LlmUsage extends Model
{
    use HasUlids;

    protected $table = 'llm_usage';

    protected $fillable = [
        'owner_id',
        'usage_date',
        'request_count',
        'token_count',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'request_count' => 'integer',
        'token_count' => 'integer',
    ];
}
