<?php

declare(strict_types=1);

namespace App\Support\Unit;

use App\Http\Controllers\Operator\UnitExportController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Livewire\UnitsList;
use App\Models\Unit;

/**
 * The Units page's rows, filters and sort orders (2026-09-30) — ONE definition, read by the page ({@see UnitsList}) and
 * by its export ({@see UnitExportController}), so a CSV can never list a view differently from the screen.
 *
 * ⚠️ ARRIVAL INFO AS STATUS, NEVER AS VALUES (CLUSTER E). A row says whether a door code, a wifi password and notes
 * EXIST; it never carries them. The SSID is different in kind — a router broadcasts it — so it travels.
 *
 * ⚠️ "BOOKABLE" IS WHAT THE STOREFRONT CAN SELL: on sale (`is_active`) and in service. AvailabilityService counts only
 * active units into `total` and subtracts the out-of-order / out-of-service ones, so this is the same rule, not a guess.
 *
 * @phpstan-type UnitRow array{id: string, code: string, name: ?string, room_type: string, room_type_id: string,
 *   kind: string, kind_key: string, occupancy: int, is_active: bool, bookable: bool, housekeeping: string, condition: string,
 *   hk_label: string, hk_tone: string, condition_label: string, condition_tone: string, owner: ?string, cover: ?string,
 *   arrival: array{has_code: bool, ssid: ?string, has_wifi: bool, has_notes: bool}, work_orders: string}
 */
final class UnitRows
{
    use FormatsOperatorData;

    private const OUT_OF_ORDER = ['out_of_order', 'out_of_service'];

    /**
     * Every unit in these properties, as rows.
     *
     * @param  list<string>  $propertyIds
     * @return list<UnitRow>
     */
    public function load(array $propertyIds): array
    {
        return Unit::query()
            ->whereIn('property_id', $propertyIds)
            ->with(['roomType.photos', 'owner'])
            ->get()
            ->map(fn (Unit $unit): array => $this->row($unit))
            ->values()
            ->all();
    }

    /** @return UnitRow */
    public function row(Unit $unit): array
    {
        $hk = $this->unitHousekeepingMeta((string) $unit->housekeeping_status);
        $condition = $this->unitConditionMeta((string) $unit->condition_status);
        $active = $unit->is_active !== false;

        return [
            'id' => (string) $unit->getKey(),
            'code' => (string) $unit->code,
            'name' => $unit->name !== null && (string) $unit->name !== '' ? (string) $unit->name : null,
            'room_type' => (string) $unit->roomType->name,
            'room_type_id' => (string) $unit->room_type_id,
            'kind' => $this->unitKindLabel((string) $unit->unit_kind),
            'kind_key' => (string) $unit->unit_kind,
            'occupancy' => (int) $unit->max_occupancy,
            'is_active' => $active,
            'bookable' => $active && ! in_array((string) $unit->condition_status, self::OUT_OF_ORDER, true),
            'housekeeping' => (string) $unit->housekeeping_status,
            'condition' => (string) $unit->condition_status,
            'hk_label' => $hk['label'],
            'hk_tone' => $hk['tone'],
            'condition_label' => $condition['label'],
            'condition_tone' => $condition['tone'],
            'owner' => $unit->owner?->name !== null ? (string) $unit->owner->name : null,
            'cover' => $unit->roomType->coverPhoto()?->url,
            'arrival' => [
                'has_code' => $unit->door_code !== null && (string) $unit->door_code !== '',
                'ssid' => $unit->wifi_ssid !== null && (string) $unit->wifi_ssid !== '' ? (string) $unit->wifi_ssid : null,
                'has_wifi' => $unit->wifi_password !== null && (string) $unit->wifi_password !== '',
                'has_notes' => $unit->arrival_notes !== null && (string) $unit->arrival_notes !== '',
            ],
            // its work orders — the Maintenance board searched to this unit's code
            'work_orders' => route('app.maintenance', ['q' => (string) $unit->code]),
        ];
    }

    /** @param  UnitRow  $row */
    public function matches(array $row, ?string $filter, string $search): bool
    {
        $ok = match ($filter) {
            'bookable' => $row['bookable'],
            'paused' => ! $row['is_active'],
            'dirty' => $row['housekeeping'] === 'dirty',
            'ooo' => in_array($row['condition'], self::OUT_OF_ORDER, true),
            default => true,
        };
        if (! $ok) {
            return false;
        }
        if ($search === '') {
            return true;
        }

        $hay = mb_strtolower(implode(' ', [$row['code'], (string) $row['name'], $row['room_type'], $row['kind'], (string) $row['owner']]));

        return str_contains($hay, mb_strtolower($search));
    }

    /**
     * Order the rows. Each key is a value the row SHOWS; ties fall back to the unit (natural order) and then the id, so the
     * order is total and a row can never appear on two pages.
     *
     * @param  list<UnitRow>  $rows
     * @param  'unit'|'room_type'|'sleeps'  $sort
     * @param  'asc'|'desc'  $dir
     * @return list<UnitRow>
     */
    public function sort(array $rows, string $sort, string $dir): array
    {
        $sign = $dir === 'asc' ? 1 : -1;

        usort($rows, function (array $a, array $b) use ($sort, $sign): int {
            $primary = match ($sort) {
                'unit' => $sign * strnatcasecmp($a['code'], $b['code']),
                'room_type' => $sign * strnatcasecmp($a['room_type'], $b['room_type']),
                'sleeps' => $sign * ($a['occupancy'] <=> $b['occupancy']),
            };

            return $primary !== 0 ? $primary
                : (strnatcasecmp($a['code'], $b['code']) ?: strcmp($a['id'], $b['id']));
        });

        return $rows;
    }
}
