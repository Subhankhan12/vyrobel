<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Booking\UnitUnavailableException;
use App\Exceptions\Owner\OwnerBlockException;
use App\Models\InventoryBlock;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Carbon;

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
     * The granular block reasons the owner picks. These are stored in the block's `notes` — the `reason` COLUMN
     * is always the real enum value `'owner'` (an owner hold), never an invented enum. Booking-side maintenance
     * blocks are the maintenance module's, not the owner's.
     *
     * @var list<string>
     */
    public const REASONS = ['Owner stay', 'Family visit', 'Personal stay', 'Seasonal hold', 'Boiler service', 'Deep clean'];

    public function __construct(
        private AssignmentService $assignment,
        private OwnerPortalService $portal,
    ) {}

    /**
     * Block a date range (first..last night, INCLUSIVE) on one of the owner's OWN units. Converts to the
     * half-open `[start, end)` the schema uses. blockInventory throws {@see UnitUnavailableException}
     * if any night is already booked or blocked (the real rule, not a reinvented check).
     */
    public function block(User $user, string $unitId, string $firstNight, string $lastNight, string $reasonNote): InventoryBlock
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
            'notes' => $reasonNote,
            'created_by' => $user->getKey(),
        ]);
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
     * THE guard: resolve a unit from the owner's OWN units (owner-scoped {@see OwnerPortalService::units()}), or
     * throw. Resolving through the owner's own set — never a `Unit::find($requestId)` — is what makes tampering
     * with a foreign unit id impossible.
     */
    public function ownedUnitOrFail(string $unitId): Unit
    {
        $unit = $this->portal->units()->first(fn (Unit $u): bool => (string) $u->id === $unitId);

        if (! $unit instanceof Unit) {
            throw OwnerBlockException::notYourUnit($unitId);
        }

        return $unit;
    }
}
