<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\Operator\InventoryActionController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\RoomType;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Unit\UnitListParams;
use App\Support\Unit\UnitRows;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Units — the organisation's physical units and the room types they are sold as, rebuilt 2026-09-30 on the
 * founder's report and request (design approved the same day): the same list system as Guests and Maintenance — figures,
 * one toolbar (search · filter chips with counts · Export · Select · Add room type · Add unit), a table that sorts by
 * unit (natural order), room type and sleeps and pages 25 / 50 / 100, a menu on every row, room types as cards, a setup
 * guide while something is missing, and centred dialogs. READ view: every write is a guarded POST →
 * {@see InventoryActionController}; an action started here comes back here, as it was.
 *
 * TENANT + property scoped (`currentReadIds`). The rows come from the ONE {@see UnitRows}, which the export reads too.
 *
 * ⚠️ ONE NAME FOR ONE THING. The switch that stops new bookings (`is_active`) is PAUSE / RESUME — the words the owner
 * portal already uses for the same flag. "Out of service" is the maintenance CONDITION shown beside it; the old control
 * was called "Take out of service" and meant something else.
 *
 * @phpstan-import-type UnitRow from UnitRows
 *
 * @phpstan-type PageState array{filter: ?string, q: string, sort: 'unit'|'room_type'|'sleeps', dir: 'asc'|'desc', per: int, page: int}
 */
#[Layout('components.layouts.operator')]
class UnitsList extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    /** The room-type kinds offered — the same four the controller accepts. */
    private const KINDS = ['room' => 'Room', 'apartment' => 'Apartment', 'villa' => 'Villa', 'dorm' => 'Dorm'];

    /** One tile icon per kind (inner SVG paths for the choice tiles). */
    private const KIND_ICONS = [
        'room' => '<path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6M3 18h18M3 18v2M21 18v2M7 10V7a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v3"/>',
        'apartment' => '<rect x="4" y="3" width="16" height="18" rx="1.5"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1M11 21v-3h2v3"/>',
        'villa' => '<path d="M3 11l9-7 9 7M5 10v10h14V10M10 20v-5h4v5"/>',
        'dorm' => '<path d="M4 6h16M4 6v14M20 6v14M4 12h16M8 9h.01M8 15h.01"/>',
    ];

    public function render(): View
    {
        $readIds = $this->currentReadIds;
        $state = $this->state();
        $rowsOf = app(UnitRows::class);
        $all = $rowsOf->load($readIds);

        $user = auth()->user();
        $canManageUnits = (bool) $user?->can('units.manage');
        $canManageRates = (bool) $user?->can('rates.manage');
        $readOnly = app(OperatorWorkspace::class)->isReadOnly();
        // A write that would be refused is not offered: the edit and pause routes, and the rates writer, all refuse a
        // paused workspace. Arrival info keeps its own rules (its controller has no pause guard), as it always did.
        $canWrite = $canManageUnits && ! $readOnly;
        $canPrice = $canManageRates && ! $readOnly;

        $filtered = array_values(array_filter($all, fn (array $r): bool => $rowsOf->matches($r, $state['filter'], $state['q'])));
        // what this viewer may do to each row travels with it (the menu and `data-ul-actions` read it)
        $rows = $this->page(array_map(
            fn (array $r): array => $r + ['actions' => $this->actions($r, $canWrite, $canPrice, $canManageUnits)],
            $rowsOf->sort($filtered, $state['sort'], $state['dir']),
        ), $state);

        $roomTypes = RoomType::query()->whereIn('property_id', $readIds)->with('photos')->orderBy('name')->get();

        return view('livewire.units-list', [
            'viewer' => $this->viewer(),
            'rows' => $rows,
            // each row on this page, once, by id — the row menu and the dialogs read it (the page script's `row()`)
            'rowsById' => collect($rows->items())->keyBy('id')->all(),
            'total' => count($all),
            'kpis' => [
                'total' => count($all),
                'bookable' => count(array_filter($all, fn (array $r): bool => $r['bookable'])),
                'paused' => count(array_filter($all, fn (array $r): bool => ! $r['is_active'])),
                'dirty' => count(array_filter($all, fn (array $r): bool => $r['housekeeping'] === 'dirty')),
                'ooo' => count(array_filter($all, fn (array $r): bool => in_array($r['condition'], ['out_of_order', 'out_of_service'], true))),
            ],
            'query' => $state['q'],
            'keep' => array_diff_key(UnitListParams::clean($this->params($state)), ['q' => true]),
            'chips' => $this->chips($all, $state, $rowsOf),
            'sorts' => $this->sortLinks($state),
            'sizes' => $this->sizeLinks($state),
            'pages' => $this->pageLinks($state, $rows),
            'sortedBy' => $this->sortedBy($state),
            'exportUrl' => route('app.units.export', UnitListParams::clean($this->params($state))),
            'roomTypeCards' => $roomTypes->map(fn (RoomType $rt): array => $this->card($rt, $all))->all(),
            'roomTypeOptions' => $roomTypes->map(fn (RoomType $rt): array => [
                'value' => (string) $rt->getKey(),
                'label' => (string) $rt->name,
                'hint' => $this->unitKindLabel((string) $rt->kind).' · sleeps '.((int) $rt->max_adults + (int) $rt->max_children),
            ])->all(),
            'kindChoices' => array_map(fn (string $value, string $label): array => ['value' => $value, 'label' => $label, 'icon' => self::KIND_ICONS[$value]],
                array_keys(self::KINDS), self::KINDS),
            'setup' => ['room_types' => $roomTypes->count(), 'units' => count($all)],
            // D18-5 — the ONE per-unit pricing form needs the room types and the units beneath them, labelled `name ?: code`.
            'unitPriceRoomTypes' => $roomTypes->map(fn (RoomType $rt): array => ['id' => (string) $rt->getKey(), 'name' => (string) $rt->name])->all(),
            'unitPriceUnits' => collect($all)->groupBy('room_type_id')
                ->map(fn ($g): array => $g->map(fn (array $u): array => ['id' => $u['id'], 'name' => $u['name'] ?? $u['code']])->values()->all())
                ->all(),
            'canManageUnits' => $canManageUnits,
            'canManageRates' => $canManageRates,
            'canWrite' => $canWrite,
            'canPrice' => $canPrice,
            'canExport' => (bool) $user?->can('units.view'),
            'canSelect' => $canManageUnits,
            'reveal' => $this->reveal(),
            // The page script's start: the action URLs (the id fills `__ID__`), the search as loaded, and anything the
            // server just refused, to reopen as it was typed.
            'boot' => [
                'urls' => [
                    'edit' => route('app.inventory.units.update', ['unit' => '__ID__']),
                    'active' => route('app.inventory.units.active', ['unit' => '__ID__']),
                    'arrival' => route('app.inventory.units.arrival-info', ['unit' => '__ID__']),
                    'reveal' => route('app.inventory.units.arrival-info.reveal', ['unit' => '__ID__']),
                    'bulkActive' => route('app.inventory.units.bulk-active'),
                ],
                'q' => $state['q'],
                'retry' => $canWrite ? $this->editRetry() : null,
                'addRetry' => $canWrite ? $this->addRetry() : null,
                'reveal' => $this->reveal()['unit'] ?? null,
            ],
        ]);
    }

    // ── The page's settings ──────────────────────────────────────────────────────────────────────

    /** @return PageState */
    private function state(): array
    {
        $clean = UnitListParams::clean((array) request()->query());
        $sort = $clean['sort'] ?? 'unit';

        return [
            'filter' => $clean['filter'] ?? null,
            'q' => $clean['q'] ?? '',
            'sort' => $sort,
            'dir' => $clean['dir'] ?? UnitListParams::SORTS[$sort],
            'per' => $clean['per'] ?? UnitListParams::PER_PAGE,
            'page' => $clean['page'] ?? 1,
        ];
    }

    /**
     * @param  PageState  $state
     * @return array<string, string|int|null>
     */
    private function params(array $state): array
    {
        return ['filter' => $state['filter'], 'q' => $state['q'], 'sort' => $state['sort'], 'dir' => $state['dir'], 'per' => $state['per']];
    }

    /**
     * A link to the page with some settings changed and every other one kept — and back to page 1.
     *
     * @param  PageState  $state
     * @param  array<string, string|int|null>  $changes
     */
    private function href(array $state, array $changes): string
    {
        return UnitListParams::url(array_merge($this->params($state), $changes));
    }

    /**
     * What this viewer may do to THIS unit, in a fixed order — each only where its route would accept it.
     *
     * @param  UnitRow  $row
     * @return list<string>
     */
    private function actions(array $row, bool $canWrite, bool $canPrice, bool $canManageUnits): array
    {
        $actions = [];
        if ($canWrite) {
            $actions[] = 'edit';
        }
        if ($canPrice) {
            $actions[] = 'price';
        }
        if ($canManageUnits) {
            $actions[] = 'arrival';
        }
        if ($canWrite) {
            $actions[] = $row['is_active'] ? 'pause' : 'resume';
        }

        return $actions;
    }

    /**
     * One page of the sorted rows. A page past the end shows the last one rather than an empty table.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  PageState  $state
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function page(array $rows, array $state): LengthAwarePaginator
    {
        $total = count($rows);
        $page = min($state['page'], max(1, (int) ceil($total / $state['per'])));

        return new LengthAwarePaginator(array_slice($rows, ($page - 1) * $state['per'], $state['per']), $total, $state['per'], $page,
            ['path' => route('app.units')]);
    }

    /**
     * @param  list<UnitRow>  $all
     * @param  PageState  $state
     * @return list<array{key: string, label: string, count: int, active: bool, href: string}>
     */
    private function chips(array $all, array $state, UnitRows $rowsOf): array
    {
        $chips = [];
        foreach ([[null, 'All'], ['bookable', 'Bookable'], ['paused', 'Paused'], ['dirty', 'To clean'], ['ooo', 'Out of order']] as [$filter, $label]) {
            $chips[] = [
                'key' => $filter ?? 'all',
                'label' => $label,
                // the REAL count of what clicking it shows, the search included
                'count' => count(array_filter($all, fn (array $r): bool => $rowsOf->matches($r, $filter, $state['q']))),
                'active' => $state['filter'] === $filter,
                'href' => $this->href($state, ['filter' => $filter]),
            ];
        }

        return $chips;
    }

    /**
     * @param  PageState  $state
     * @return array<string, array{href: string, active: bool, dir: string, aria: string}>
     */
    private function sortLinks(array $state): array
    {
        $links = [];
        foreach (UnitListParams::SORTS as $column => $start) {
            $active = $state['sort'] === $column;
            $links[$column] = [
                'href' => $this->href($state, ['sort' => $column, 'dir' => $active ? ($state['dir'] === 'asc' ? 'desc' : 'asc') : $start]),
                'active' => $active,
                'dir' => $active ? $state['dir'] : '',
                'aria' => $active ? ($state['dir'] === 'asc' ? 'ascending' : 'descending') : 'none',
            ];
        }

        return $links;
    }

    /**
     * @param  PageState  $state
     * @return list<array{size: int, active: bool, href: string}>
     */
    private function sizeLinks(array $state): array
    {
        return array_map(fn (int $size): array => ['size' => $size, 'active' => $state['per'] === $size, 'href' => $this->href($state, ['per' => $size])],
            UnitListParams::PER_PAGE_OPTIONS);
    }

    /**
     * @param  PageState  $state
     * @param  LengthAwarePaginator<int, array<string, mixed>>  $rows
     * @return array{prev: ?string, next: ?string}
     */
    private function pageLinks(array $state, LengthAwarePaginator $rows): array
    {
        $page = $rows->currentPage();

        return [
            'prev' => $page > 1 ? UnitListParams::url($this->params($state) + ['page' => $page - 1]) : null,
            'next' => $rows->hasMorePages() ? UnitListParams::url($this->params($state) + ['page' => $page + 1]) : null,
        ];
    }

    /** @param  PageState  $state */
    private function sortedBy(array $state): string
    {
        return match ($state['sort'].'.'.$state['dir']) {
            'unit.asc' => 'by unit, A–Z',
            'unit.desc' => 'by unit, Z–A',
            'room_type.asc' => 'by room type, A–Z',
            'room_type.desc' => 'by room type, Z–A',
            'sleeps.desc' => 'sleeps most first',
            'sleeps.asc' => 'sleeps fewest first',
        };
    }

    // ── Room types ───────────────────────────────────────────────────────────────────────────────

    /**
     * @param  list<UnitRow>  $all
     * @return array<string, mixed>
     */
    private function card(RoomType $rt, array $all): array
    {
        $units = array_filter($all, fn (array $r): bool => $r['room_type_id'] === (string) $rt->getKey());

        return [
            'id' => (string) $rt->getKey(),
            'name' => (string) $rt->name,
            'kind' => $this->unitKindLabel((string) $rt->kind),
            'adults' => (int) $rt->max_adults,
            'children' => (int) $rt->max_children,
            'units' => count($units),
            'bookable' => count(array_filter($units, fn (array $r): bool => $r['bookable'])),
            'cover' => $rt->coverPhoto()?->url,
            'photos' => $rt->photos->map(fn ($p): array => [
                'id' => (string) $p->getKey(),
                'url' => (string) $p->url,
                'alt' => (string) $p->alt,
                'is_cover' => (bool) $p->is_cover,
            ])->values()->all(),
        ];
    }

    // ── What the server just refused or revealed ─────────────────────────────────────────────────

    /**
     * The Edit dialog, reopened with what was typed when the server refused it — only the page's own Edit form carries
     * `_unit`, so no other refusal opens it.
     *
     * @return array<string, string>|null
     */
    private function editRetry(): ?array
    {
        $id = old('_unit');
        if (! is_string($id) || preg_match('/^[0-9A-Za-z]{26}$/', $id) !== 1 || ! session()->has('errors')) {
            return null;
        }

        return ['id' => $id, 'code' => $this->oldText('code'), 'name' => $this->oldText('name'), 'max_occupancy' => $this->oldText('max_occupancy')];
    }

    /**
     * An Add dialog, reopened with what was typed when the server refused it — `_form` says which one.
     *
     * @return array<string, string>|null
     */
    private function addRetry(): ?array
    {
        $form = old('_form');
        if (! in_array($form, ['room_type', 'unit'], true) || ! session()->has('errors')) {
            return null;
        }

        return $form === 'room_type'
            ? ['form' => 'room_type', 'name' => $this->oldText('name'), 'kind' => $this->oldText('kind'),
                'max_adults' => $this->oldText('max_adults'), 'max_children' => $this->oldText('max_children')]
            : ['form' => 'unit', 'room_type_id' => $this->oldText('room_type_id'), 'code' => $this->oldText('code'),
                'name' => $this->oldText('name'), 'max_occupancy' => $this->oldText('max_occupancy')];
    }

    private function oldText(string $key): string
    {
        $value = old($key);

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * The one unit whose stored arrival values were just revealed on purpose (an audited action) — or none.
     *
     * @return array{unit: string, door_code: ?string, wifi_ssid: ?string, wifi_password: ?string}|null
     */
    private function reveal(): ?array
    {
        $reveal = session('arrival_reveal');

        return is_array($reveal) && is_string($reveal['unit'] ?? null) ? [
            'unit' => $reveal['unit'],
            'door_code' => isset($reveal['door_code']) ? (string) $reveal['door_code'] : null,
            'wifi_ssid' => isset($reveal['wifi_ssid']) ? (string) $reveal['wifi_ssid'] : null,
            'wifi_password' => isset($reveal['wifi_password']) ? (string) $reveal['wifi_password'] : null,
        ] : null;
    }
}
