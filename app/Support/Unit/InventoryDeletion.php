<?php

declare(strict_types=1);

namespace App\Support\Unit;

use App\Http\Controllers\Operator\InventoryActionController;
use App\Livewire\UnitsList;
use App\Models\Document;
use App\Models\Media;
use App\Models\Note;
use App\Models\OwnerInvite;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * WHAT STOPS A UNIT OR A ROOM TYPE BEING DELETED (2026-10-05, the founder: "there must be an option to edit and delete the
 * unit"). ONE definition, read by the page ({@see UnitsList} — to say beforehand whether Delete can remove it, and why not)
 * and by the controller ({@see InventoryActionController}, which asks again inside the transaction with the row locked, so
 * a booking that lands in between is seen).
 *
 * ⚠️⚠️ ONLY WHAT NOTHING HAS RECORDED IS DELETED. 🔬 Measured at the schema (information_schema, 2026-10-05): on a UNIT's
 * delete, housekeeping_tasks · maintenance_tickets · management_agreements · unit_status_logs CASCADE — its cleaning, repair
 * and owner history would vanish — and reservation_units · owner_ledger · owner_expenses · inventory_blocks · reviews SET
 * NULL: a sold stay would lose its room, an owner's money line its unit, and a unit block would widen to its whole room
 * type. On a ROOM TYPE's delete, reservation_units CASCADE — sold stays themselves would be deleted. So each of those is a
 * reason to refuse, in words, and the answer for a unit with history is Pause (it stops new bookings and keeps all of it).
 * A unit or room type added by mistake — the case Delete exists for — has none of them.
 *
 * ⚠️ What a delete DOES take with it is configuration, never history: a unit's own prices and its connecting-room links; a
 * room type's rate plans, nightly prices, seasons, restrictions and availability rows (a room type with no units and no
 * bookings has nothing those can still describe).
 *
 * ⚠️ Counted without the tenant scope (raw tables), but only over ids that were already resolved inside it.
 */
final class InventoryDeletion
{
    /**
     * Why each unit cannot be deleted, as words ("2 bookings"), by unit id — an empty list means it can be.
     *
     * @param  list<string>  $unitIds
     * @return array<string, list<string>>
     */
    public static function unitBlockers(array $unitIds): array
    {
        if ($unitIds === []) {
            return [];
        }

        $found = array_fill_keys($unitIds, []);
        $add = function (array $counts, string $one, string $many) use (&$found): void {
            foreach ($counts as $id => $n) {
                if (isset($found[$id]) && (int) $n > 0) {
                    $found[$id][] = self::count((int) $n, $one, $many);
                }
            }
        };

        $add(self::grouped('reservation_units', 'unit_id', $unitIds, 'reservation_id'), 'booking', 'bookings');
        $add(self::moves($unitIds), 'room move', 'room moves');
        $add(self::grouped('housekeeping_tasks', 'unit_id', $unitIds), 'cleaning task', 'cleaning tasks');
        $add(self::grouped('maintenance_tickets', 'unit_id', $unitIds), 'work order', 'work orders');
        $add(self::grouped('unit_status_logs', 'unit_id', $unitIds), 'status change', 'status changes');
        $add(self::grouped('inventory_blocks', 'unit_id', $unitIds), 'block', 'blocks');
        $add(self::grouped('management_agreements', 'unit_id', $unitIds), 'owner agreement', 'owner agreements');
        $add(self::grouped('owner_ledger', 'unit_id', $unitIds), 'owner statement line', 'owner statement lines');
        $add(self::grouped('owner_expenses', 'unit_id', $unitIds), 'owner expense', 'owner expenses');
        $add(self::grouped('reviews', 'unit_id', $unitIds), 'review', 'reviews');
        $add(self::grouped('unit_requests', 'created_unit_id', $unitIds), 'owner request', 'owner requests');
        $morph = (new Unit)->getMorphClass();
        $add(self::morphed((new Note)->getTable(), 'notable', $morph, $unitIds), 'note', 'notes');
        $add(self::morphed((new Document)->getTable(), 'documentable', $morph, $unitIds), 'document', 'documents');
        $add(self::morphed((new Media)->getTable(), 'mediable', $morph, $unitIds), 'file', 'files');

        // an owner's unit belongs to their portal and statements, whatever else is on record
        foreach (DB::table('units')->whereIn('id', $unitIds)->whereNotNull('owner_id')->pluck('id') as $id) {
            $found[(string) $id][] = 'an owner';
        }
        // an owner invitation still waiting to be accepted would hand over a unit that is gone
        foreach (OwnerInvite::query()->where('status', 'pending')->whereNotNull('assigned_unit_ids')->get(['assigned_unit_ids']) as $invite) {
            foreach (array_intersect((array) $invite->assigned_unit_ids, $unitIds) as $id) {
                if (! in_array('a pending owner invitation', $found[$id], true)) {
                    $found[$id][] = 'a pending owner invitation';
                }
            }
        }

        return $found;
    }

    /**
     * Why each room type cannot be deleted, as words ("3 units"), by room type id — an empty list means it can be.
     *
     * @param  list<string>  $roomTypeIds
     * @return array<string, list<string>>
     */
    public static function roomTypeBlockers(array $roomTypeIds): array
    {
        if ($roomTypeIds === []) {
            return [];
        }

        $found = array_fill_keys($roomTypeIds, []);
        $add = function (array $counts, string $one, string $many) use (&$found): void {
            foreach ($counts as $id => $n) {
                if (isset($found[$id]) && (int) $n > 0) {
                    $found[$id][] = self::count((int) $n, $one, $many);
                }
            }
        };

        // every unit row, a soft-deleted one too: the database would CASCADE it with the room type
        $add(self::grouped('units', 'room_type_id', $roomTypeIds), 'unit', 'units');

        // its rate plans — a booking can name one of them after a move to another room type
        $plans = DB::table('rate_plans')->whereIn('room_type_id', $roomTypeIds)->pluck('room_type_id', 'id')->map(fn ($v): string => (string) $v)->all();
        $bookings = [];
        foreach (DB::table('reservation_units')->where(fn ($q) => $q->whereIn('room_type_id', $roomTypeIds)->orWhereIn('rate_plan_id', array_keys($plans)))
            ->get(['room_type_id', 'rate_plan_id', 'reservation_id']) as $line) {
            foreach (array_unique(array_filter([(string) $line->room_type_id, $plans[(string) $line->rate_plan_id] ?? null])) as $rt) {
                $bookings[$rt][(string) $line->reservation_id] = true;
            }
        }
        $add(array_map('count', $bookings), 'booking', 'bookings');

        $add(self::grouped('inventory_blocks', 'room_type_id', $roomTypeIds), 'block', 'blocks');
        $add(self::grouped('reviews', 'room_type_id', $roomTypeIds), 'review', 'reviews');
        $add(self::grouped('rate_proposals', 'room_type_id', $roomTypeIds), 'rate proposal', 'rate proposals');
        $add(self::grouped('overbooking_conflicts', 'room_type_id', $roomTypeIds), 'overbooking record', 'overbooking records');
        $add(self::grouped('guest_waitlist_entries', 'room_type_id', $roomTypeIds), 'guest waitlist entry', 'guest waitlist entries');
        $add(self::grouped('guest_wishlist_items', 'room_type_id', $roomTypeIds), 'guest wishlist save', 'guest wishlist saves');
        $add(self::grouped('room_types', 'parent_room_type_id', $roomTypeIds), 'room type built on it', 'room types built on it');

        // another room type's plan priced FROM one of these plans would lose its basis (SET NULL) and be repriced
        if ($plans !== []) {
            $derived = [];
            foreach (DB::table('rate_plans')->whereIn('derived_from_rate_plan_id', array_keys($plans))->get(['room_type_id', 'derived_from_rate_plan_id']) as $p) {
                $own = $plans[(string) $p->derived_from_rate_plan_id] ?? null;
                if ($own !== null && (string) $p->room_type_id !== $own) {
                    $derived[$own] = ($derived[$own] ?? 0) + 1;
                }
            }
            $add($derived, 'rate plan elsewhere priced from it', 'rate plans elsewhere priced from it');

            $corporate = [];
            foreach (DB::table('corporate_accounts')->whereIn('default_rate_plan_id', array_keys($plans))->pluck('default_rate_plan_id') as $planId) {
                $own = $plans[(string) $planId];
                $corporate[$own] = ($corporate[$own] ?? 0) + 1;
            }
            $add($corporate, 'corporate account’s default rate', 'corporate accounts’ default rate');

        }

        // A channel connected to the room type, or (⚠️ 2026-10-05, the review) to one of its RATE PLANS — both CASCADE, so deleting
        // the room type would cut either silently. One count, one entry (a mapping is one or the other, never both).
        $mapped = self::grouped('channel_mappings', 'room_type_id', $roomTypeIds);
        if ($plans !== []) {
            foreach (DB::table('channel_mappings')->whereIn('rate_plan_id', array_keys($plans))->pluck('rate_plan_id') as $planId) {
                $own = $plans[(string) $planId];
                $mapped[$own] = ($mapped[$own] ?? 0) + 1;
            }
        }
        $add($mapped, 'channel connection', 'channel connections');

        return $found;
    }

    /** "2 bookings and 1 cleaning task" — a list read as a sentence. @param  list<string>  $items */
    public static function sentence(array $items): string
    {
        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        return implode(', ', array_slice($items, 0, -1)).' and '.$items[count($items) - 1];
    }

    /** The noun for a room type's kind, as its units were added ("rooms", "dorm beds"). */
    public static function kindPlural(string $kind): string
    {
        return match ($kind) {
            'dorm' => 'dorm beds',
            default => Str::plural($kind),
        };
    }

    private static function count(int $n, string $one, string $many): string
    {
        return $n.' '.($n === 1 ? $one : $many);
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, int>
     */
    private static function grouped(string $table, string $column, array $ids, ?string $distinct = null): array
    {
        return DB::table($table)->whereIn($column, $ids)->groupBy($column)
            ->selectRaw($column.' as k, count('.($distinct !== null ? 'distinct '.$distinct : '*').') as n')
            ->pluck('n', 'k')->map(fn ($n): int => (int) $n)->all();
    }

    /**
     * Moves FROM or TO each unit (`reservation_unit_moves` has no foreign key — a deleted unit would leave the record
     * pointing at nothing).
     *
     * @param  list<string>  $ids
     * @return array<string, int>
     */
    private static function moves(array $ids): array
    {
        $out = [];
        foreach (DB::table('reservation_unit_moves')->where(fn ($q) => $q->whereIn('from_unit_id', $ids)->orWhereIn('to_unit_id', $ids))
            ->get(['from_unit_id', 'to_unit_id']) as $move) {
            foreach (array_unique([(string) $move->from_unit_id, (string) $move->to_unit_id]) as $id) {
                if (in_array($id, $ids, true)) {
                    $out[$id] = ($out[$id] ?? 0) + 1;
                }
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, int>
     */
    private static function morphed(string $table, string $name, string $type, array $ids): array
    {
        return DB::table($table)->where($name.'_type', $type)->whereIn($name.'_id', $ids)->groupBy($name.'_id')
            ->selectRaw($name.'_id as k, count(*) as n')->pluck('n', 'k')->map(fn ($n): int => (int) $n)->all();
    }
}
