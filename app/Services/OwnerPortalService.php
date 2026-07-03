<?php

namespace App\Services;

use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use Illuminate\Support\Collection;

/**
 * Read-only assembly for the owner portal (Phase 3 · Module 1 Step 6). Every query runs through the
 * owner-scoped models, so an authenticated owner only ever sees their own rows — the isolation lives
 * in the {@see OwnerScope} global scope, not in ad-hoc where clauses here. Reuses
 * {@see AvailabilityService} for occupancy rather than re-deriving it.
 */
class OwnerPortalService
{
    public function __construct(
        private AvailabilityService $availability,
    ) {}

    /**
     * The authenticated owner's units (owner-scoped).
     *
     * @return Collection<int, Unit>
     */
    public function units(): Collection
    {
        return Unit::query()->with('roomType')->orderBy('code')->get();
    }

    /**
     * The authenticated owner's statements, most recent first (owner-scoped).
     *
     * @return Collection<int, OwnerStatement>
     */
    public function statements(): Collection
    {
        return OwnerStatement::query()->orderByDesc('period_end')->get();
    }

    /**
     * The authenticated owner's payouts, most recent first (owner-scoped).
     *
     * @return Collection<int, Payout>
     */
    public function payouts(): Collection
    {
        return Payout::query()->orderByDesc('created_at')->get();
    }

    /**
     * Per-night availability for a room type over a window (reuses AvailabilityService).
     *
     * @return array<string, int>
     */
    public function occupancy(string $roomTypeId, string $start, string $end): array
    {
        return $this->availability->availabilityRange($roomTypeId, $start, $end);
    }
}
