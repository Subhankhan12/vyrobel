<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Booking\UnitUnavailableException;
use App\Exceptions\Owner\OwnerBlockException;
use App\Exceptions\Owner\OwnerUnitPropertyRemovedException;
use App\Models\InventoryBlock;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Owner BLOCK-CALENDAR write path (Phase 2 · Sub-gate 2) — the owner holds / releases dates on their OWN units
 * for personal use, maintenance or seasonal closures. It does NOT reimplement blocking: it resolves + guards the
 * owner boundary, then routes every write through the certified {@see AssignmentService} (the single inventory
 * write path — invariant #1), which enforces non-overlap ({@see AssignmentService::blockInventory()} calls
 * `assertUnitFree`) and recomputes availability via the `inventory_block.*` events.
 *
 * ⚠️ THE OWNER-SCOPE GUARD (on a WRITE): the unit is resolved from the owner-scoped
 * {@see OwnerPortalService::units()} — the SAME source the read screens use — via {@see ownedUnitOrFail()}. A
 * request unit id (or a block's unit) that is NOT among the owner's own units throws {@see OwnerBlockException},
 * so an owner can never block or release another owner's inventory by tampering with an id. Proven by
 * OwnerBlockCalendarTest (and proven to fail if the guard is removed).
 */
class OwnerBlockService
{
    /**
     * The granular block reasons the owner picks (the three the wireframe offers). Stored in the block's `notes`
     * — the `reason` COLUMN is always the real enum value `'owner'` (an owner hold), never an invented enum.
     * Booking-side maintenance blocks are the maintenance module's, not the owner's.
     *
     * @var list<string>
     */
    public const REASONS = ['Personal stay', 'Maintenance', 'Seasonal hold'];

    /**
     * A block reason as a person reads it, by its stored CODE (one of {@see REASONS} — the value the form posts and
     * `notes` keeps, which never changes). Anything else is returned as it is.
     */
    public static function reasonLabel(string $reason): string
    {
        return match ($reason) {
            'Personal stay' => __('php/services/owner_block_service.reason_personal_stay'),
            'Maintenance' => __('php/services/owner_block_service.reason_maintenance'),
            'Seasonal hold' => __('php/services/owner_block_service.reason_seasonal_hold'),
            default => $reason,
        };
    }

    /**
     * Every block reason's display label, keyed by its stored CODE (never by the translated text).
     *
     * @return array<string, string>
     */
    public static function reasonLabels(): array
    {
        $labels = [];
        foreach (self::REASONS as $reason) {
            $labels[$reason] = self::reasonLabel($reason);
        }

        return $labels;
    }

    /** The recurrence frequencies a REPEAT block expands over — each occurrence becomes a REAL individual block. */
    public const FREQUENCIES = ['weekly', 'fortnightly', 'monthly'];

    /** A repeat series is bounded (the first block + up to this many more) — never an unbounded generator. */
    public const MAX_OCCURRENCES = 12;

    public function __construct(
        private AssignmentService $assignment,
        private OwnerPortalService $portal,
    ) {}

    /**
     * Block a date range (first..last night, INCLUSIVE) on one of the owner's OWN units. Converts to the
     * half-open `[start, end)` the schema uses. blockInventory throws {@see UnitUnavailableException}
     * if any night is already booked or blocked (the real rule, not a reinvented check).
     */
    public function block(User $user, string $unitId, string $firstNight, string $lastNight, string $reasonNote, ?string $seriesId = null): InventoryBlock
    {
        $unit = $this->ownedUnitOrFail($unitId);   // ← owner-scope guard: never trusts the request id

        $start = Carbon::parse($firstNight)->startOfDay();
        $end = Carbon::parse($lastNight)->startOfDay()->addDay();   // half-open: last night + 1

        return $this->assignment->blockInventory([
            'property_id' => $unit->property_id,
            'unit_id' => (string) $unit->id,
            'room_type_id' => (string) $unit->room_type_id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => 'owner',
            'series_id' => $seriesId,
            'notes' => $reasonNote,
            'created_by' => $user->getKey(),
        ]);
    }

    /**
     * A REPEAT block: expand the recurrence (weekly / fortnightly / monthly × occurrences, including the first)
     * into REAL individual blocks — each a genuine {@see InventoryBlock} through the same guarded {@see block()},
     * sharing one `series_id`. This is the honest recurring block: NOT a fake "scheduled series" that creates
     * nothing (#39). An occurrence whose nights are already booked/blocked is SKIPPED (the real non-overlap rule)
     * and counted, so the caller can report "N of M nights blocked" truthfully rather than silently swallowing it.
     *
     * @return array{series_id: string, created: int, skipped: int}
     */
    public function blockSeries(User $user, string $unitId, string $firstNight, string $lastNight, string $reasonNote, string $freq, int $count): array
    {
        $this->ownedUnitOrFail($unitId);   // guard once up front (each block() re-guards too — cheap + safe)

        $count = max(2, min(self::MAX_OCCURRENCES, $count));
        $seriesId = (string) Str::ulid();
        $first = Carbon::parse($firstNight)->startOfDay();
        $last = Carbon::parse($lastNight)->startOfDay();

        $created = 0;
        $skipped = 0;

        for ($i = 0; $i < $count; $i++) {
            [$fn, $ln] = $this->shiftOccurrence($first, $last, $freq, $i);

            try {
                $this->block($user, $unitId, $fn->toDateString(), $ln->toDateString(), $reasonNote, $seriesId);
                $created++;
            } catch (UnitUnavailableException) {
                $skipped++;   // this occurrence collides with a booking/block — a REAL outcome, reported honestly
            }
        }

        return ['series_id' => $seriesId, 'created' => $created, 'skipped' => $skipped];
    }

    /** Shift the first occurrence's [first, last] nights forward by the frequency × the occurrence index. */
    private function shiftOccurrence(Carbon $first, Carbon $last, string $freq, int $i): array
    {
        $fn = $first->copy();
        $ln = $last->copy();

        if ($i === 0) {
            return [$fn, $ln];
        }

        if ($freq === 'monthly') {
            $fn->addMonthsNoOverflow($i);
            $ln->addMonthsNoOverflow($i);
        } else {
            $weeks = $freq === 'fortnightly' ? 2 * $i : $i;
            $fn->addWeeks($weeks);
            $ln->addWeeks($weeks);
        }

        return [$fn, $ln];
    }

    /**
     * Release one of the owner's OWN blocks. The block is resolved tenant-scoped, then its unit is put through
     * the SAME owner-scope guard — releasing another owner's block by id is rejected.
     */
    public function release(string $blockId): void
    {
        $block = InventoryBlock::query()->findOrFail($blockId);   // tenant-scoped (BelongsToOrganization)
        $this->ownedUnitOrFail((string) $block->unit_id);         // ← owner-scope guard on the block's unit

        $this->assignment->releaseInventoryBlock($block);
    }

    /**
     * Release EVERY block in one series (the Undo of a just-created repeat, and a whole-series remove). Each
     * block goes through the SAME owner-scope guard, so a foreign series id releases nothing it shouldn't.
     */
    public function releaseSeries(string $seriesId): void
    {
        InventoryBlock::query()
            ->where('series_id', $seriesId)
            ->get()
            ->each(function (InventoryBlock $block): void {
                $this->ownedUnitOrFail((string) $block->unit_id);
                $this->assignment->releaseInventoryBlock($block);
            });
    }

    /**
     * THE guard: resolve a unit from the owner's OWN units (owner-scoped {@see OwnerPortalService::units()}), or
     * throw. Resolving through the owner's own set — never a `Unit::find($requestId)` — is what makes tampering
     * with a foreign unit id impossible.
     */
    public function ownedUnitOrFail(string $unitId): Unit
    {
        // ⚠️ LIVE INVENTORY, NOT THE ATTRIBUTION SET (2026-09-01). A block is a WRITE against sellable
        // inventory, so it must resolve from {@see OwnerPortalService::unitsAtLiveProperties()}. Using
        // `units()` here let an owner block dates on a unit whose PROPERTY had been archived: MEASURED, the
        // write landed — one real `inventory_blocks` row and an audit event against a removed property — and
        // the owner was told it had worked.
        $unit = $this->portal->unitsAtLiveProperties()->first(fn (Unit $u): bool => (string) $u->id === $unitId);

        if ($unit instanceof Unit) {
            return $unit;
        }

        // ⚠️ SAY WHICH REFUSAL THIS IS. If the unit is still ATTRIBUTED to them it is theirs and the property
        // is what has gone — telling them "not one of your units" would be false. A genuinely foreign id is
        // absent from both sets and keeps the original tampering answer.
        if ($this->portal->units()->contains(fn (Unit $u): bool => (string) $u->id === $unitId)) {
            throw OwnerUnitPropertyRemovedException::forUnit($unitId);
        }

        throw OwnerBlockException::notYourUnit($unitId);
    }
}
