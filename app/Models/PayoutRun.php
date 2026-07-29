<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\OwnerPayoutService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One automated PAYOUT-ENGINE sweep (payout engine · Phase 1). PLATFORM-LEVEL: cross-tenant, no
 * `organization_id`, created/read under {@see TenantContext::runAsSuperAdmin}. It is
 * the run's own audit + idempotency record — a scheduled run is keyed `scheduled:YYYY-MM-DD` (one per day),
 * so a same-day re-run resumes the same row and never double-pays. The per-owner payouts it drives are the
 * tenant-scoped, audited money writes (via {@see OwnerPayoutService}); this row just tallies.
 *
 * ⚠️ `mode` is the TEST-MODE boundary — `test` throughout the build (real money never moves in test mode).
 *
 * @property string $id
 * @property string $run_key
 * @property string $trigger
 * @property string $mode
 * @property string $status
 * @property int $owners_selected
 * @property int $payouts_created
 * @property int $payouts_paid
 * @property int $payouts_held
 * @property int $payouts_failed
 * @property array<string, int>|null $totals
 * @property string|null $notes
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 */
class PayoutRun extends Model
{
    use HasUlids;

    public const TRIGGER_SCHEDULED = 'scheduled';

    public const TRIGGER_MANUAL = 'manual';

    public const MODE_TEST = 'test';

    public const MODE_LIVE = 'live';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'run_key',
        'trigger',
        'mode',
        'status',
        'owners_selected',
        'payouts_created',
        'payouts_paid',
        'payouts_held',
        'payouts_failed',
        'totals',
        'notes',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'owners_selected' => 'integer',
            'payouts_created' => 'integer',
            'payouts_paid' => 'integer',
            'payouts_held' => 'integer',
            'payouts_failed' => 'integer',
            'totals' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * The payouts this run created — the reconciling detail behind the tallied totals.
     *
     * @return HasMany<Payout, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }
}
