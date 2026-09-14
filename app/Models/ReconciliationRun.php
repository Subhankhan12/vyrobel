<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One reconciliation run — ⚠️ the evidence that it happened at all.
 *
 * @property string $status
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 * @property int $examined
 * @property int $agreed
 * @property int $disagreed
 * @property int $unchecked
 * @property array<int, mixed>|null $report
 * @property string|null $error
 *
 * ⚠️ NO TENANT TRAIT, DELIBERATELY. A reconciliation spans every tenant and runs with no request context.
 */
class ReconciliationRun extends Model
{
    use HasUlids;

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'status', 'started_at', 'finished_at',
        'examined', 'agreed', 'disagreed', 'unchecked', 'report', 'error',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'examined' => 'integer',
            'agreed' => 'integer',
            'disagreed' => 'integer',
            'unchecked' => 'integer',
            'report' => 'array',
        ];
    }
}
