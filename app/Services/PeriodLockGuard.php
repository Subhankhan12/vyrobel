<?php

namespace App\Services;

use App\Models\OwnerStatement;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The explicit lock boundary (Phase 3 · Module 1 Step 4). Attribution and expense services consult
 * this before posting a ledger line so a line dated into an already-LOCKED statement period is marked
 * `prior_period_adjustment` — it keeps its true service_date but carries forward onto the next open
 * statement instead of ever touching the sealed one. Locked-period immutability is enforced here and
 * at finalize, not by "don't regenerate" convention.
 */
class PeriodLockGuard
{
    /**
     * The latest date sealed by a finalized/locked statement for this owner, or null if none.
     */
    public function lockedThrough(string $ownerId): ?string
    {
        $through = OwnerStatement::query()
            ->where('owner_id', $ownerId)
            ->where('locked', true)
            ->max('period_end');

        return $through !== null ? Carbon::parse($through)->toDateString() : null;
    }

    /**
     * True when $date falls inside an already-locked period for this owner (a post-lock adjustment).
     */
    public function isSealed(string $ownerId, CarbonInterface|string $date): bool
    {
        $through = $this->lockedThrough($ownerId);

        return $through !== null && Carbon::parse($date)->toDateString() <= $through;
    }
}
