<?php

declare(strict_types=1);

namespace App\Support\Property;

use App\Models\HousekeepingTask;
use App\Models\MaintenanceTicket;
use App\Models\RateProposal;
use App\Models\RoomType;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;

/**
 * WHAT A WRITE ON ANOTHER ROW MAY REACH — the person's property allow-list (#38, the `property_user` pivot-or-all rule),
 * applied to the records one operator page writes by ID.
 *
 * ⚠️⚠️ DEC-i / DEC-j / DEC-q (2026-11-11, his "do all"): the Portfolio's Edit, every maintenance write, every housekeeping write
 * and the rate-proposal writes found their row through the TENANT scope only. Staff limited to some properties could change another
 * property's records by posting an id — the same hole DEC-k closed for units and DEC-l for rates. A row on a property outside the
 * person's allow-list is NOT FOUND — a 404, never a 403 that would confirm it exists.
 *
 * ⚠️ WHY `accessibleIds()` AND NOT `readIds()` (the rule RateScope uses): `readIds()` is the property ON SCREEN, so it would 404 a
 * deep link or a bell notification to a property the person works in but has not selected. An id-addressed write needs only the
 * allow-list — exactly what the maintenance Edit and the Portfolio's Remove / Keep already asked.
 *
 * ⚠️ FAIL CLOSED: an empty allow-list (no property context, no property) finds NOTHING. These writers run only under `/app`,
 * where `SetCurrentPropertyForWebRequest` activates the context; a caller with no context gets a 404, never "everything".
 */
final class PropertyScope
{
    /** @return list<string> */
    private function ids(): array
    {
        return app(PropertyContext::class)->accessibleIds();
    }

    public function unit(string $id): Unit
    {
        return Unit::query()->whereIn('property_id', $this->ids())->findOrFail($id);
    }

    /** @return Builder<MaintenanceTicket> */
    public function tickets(): Builder
    {
        $ids = $this->ids();

        return MaintenanceTicket::query()->whereHas('unit', fn (Builder $u) => $u->whereIn('property_id', $ids));
    }

    /** @param  list<string>  $with */
    public function ticket(string $id, array $with = []): MaintenanceTicket
    {
        return $this->tickets()->with($with)->findOrFail($id);
    }

    /** @return Builder<HousekeepingTask> */
    public function tasks(): Builder
    {
        return HousekeepingTask::query()->whereIn('property_id', $this->ids());
    }

    public function task(string $id): HousekeepingTask
    {
        return $this->tasks()->findOrFail($id);
    }

    public function roomType(string $id): RoomType
    {
        return RoomType::query()->whereIn('property_id', $this->ids())->findOrFail($id);
    }

    /** A rate proposal is on a property through its room type. */
    public function proposal(string $id): RateProposal
    {
        $ids = $this->ids();

        return RateProposal::query()
            ->whereHas('roomType', fn (Builder $r) => $r->whereIn('property_id', $ids))
            ->findOrFail($id);
    }
}
