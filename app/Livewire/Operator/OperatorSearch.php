<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Reservation;
use App\Services\SearchService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator GLOBAL SEARCH (Phase 1 · Sub-gate 2) — find any reservation / guest / unit / owner from one bar.
 * READ-ONLY; the query rides a GET (`?q=`), re-run through the tenant middleware, so the scoped reads never
 * leak (invariant #20 — the same reason New Reservation's guest search rides GET, not a wire-action).
 *
 * The work is {@see SearchService} (a GLOBAL cross-entity search — NOT the property-scoped availability search
 * BookingEngineService::search does). Results are TENANT-scoped by construction (every model is
 * BelongsToOrganization). Per-entity visibility follows the viewer's abilities: the page is gated on
 * `reservations.view`, so reservations always show; guests show only with `guests.view`, units with
 * `units.view`; owners mirror the #26-only operator owners list.
 *
 * ⚠️ ENCRYPTED CONTACT (the honesty guard): guests are searched by NAME only. If the operator types something
 * that looks like an email/phone, we SURFACE why we can't match it (encrypted, blind index deferred) rather than
 * silently returning nothing — the Guests-CRM precedent.
 */
#[Layout('components.layouts.operator')]
class OperatorSearch extends Component
{
    use FormatsOperatorData;

    private const MAX_QUERY = 120;

    public function render(SearchService $search): View
    {
        $query = $this->resolveQuery();
        $filters = $this->resolveFilters();
        $user = auth()->user();
        $can = [
            'guests' => (bool) $user?->can('guests.view'),
            'units' => (bool) $user?->can('units.view'),
        ];

        $emptyCounts = ['reservations' => 0, 'guests' => 0, 'units' => 0, 'owners' => 0, 'all' => 0];
        $results = $query !== ''
            ? $search->search($query, $can, $filters)
            : ['reservations' => [], 'guests' => [], 'units' => [], 'owners' => [], 'counts' => $emptyCounts];

        // Add display initials for the name-avatar rows (initials() is a trait method — resolved here, not in
        // the view, since it is protected).
        foreach (['guests', 'owners'] as $group) {
            $results[$group] = array_map(
                fn (array $row): array => array_merge($row, ['initials' => $this->initials($row['name'])]),
                $results[$group],
            );
        }

        $total = $results['counts']['all'];

        return view('livewire.operator.operator-search', [
            'viewer' => $this->viewer(),
            'query' => $query,
            'filters' => $filters,
            'results' => $results,
            'counts' => $results['counts'],
            'total' => $total,
            'searched' => $query !== '',
            'contactNote' => $query !== '' && str_contains($query, '@') && ! $can['guests'],
            'canGuests' => $can['guests'],
            'canUnits' => $can['units'],
            'sources' => SearchService::SOURCES,
            'statuses' => SearchService::STATUSES,
            'suggested' => $this->suggestedArrivals(),
        ]);
    }

    private function resolveQuery(): string
    {
        $param = request()->query('q');

        return is_string($param) ? mb_substr(trim($param), 0, self::MAX_QUERY) : '';
    }

    /**
     * The real filter state from the GET request (source / status / date range / occupancy / sort).
     *
     * @return array<string, string>
     */
    private function resolveFilters(): array
    {
        $filters = [];
        foreach (['source', 'status', 'from', 'to', 'occupancy', 'sort'] as $key) {
            $value = request()->query($key);
            if (is_string($value) && $value !== '') {
                $filters[$key] = mb_substr($value, 0, 40);
            }
        }

        return $filters;
    }

    /**
     * Suggested = REAL upcoming arrivals (reservations checking in from today) — a genuine shortcut, never a
     * fabricated suggestion (#39). Empty when there are none.
     *
     * @return list<array{reference: string, guest: string, when: string}>
     */
    private function suggestedArrivals(): array
    {
        return Reservation::query()
            ->with('leadGuest')
            ->whereIn('status', ['confirmed', 'tentative'])
            ->whereDate('check_in', '>=', CarbonImmutable::today()->toDateString())
            ->orderBy('check_in')
            ->limit(5)
            ->get()
            ->map(fn (Reservation $r): array => [
                'reference' => (string) $r->reference,
                'guest' => trim(((string) $r->leadGuest?->first_name).' '.((string) $r->leadGuest?->last_name)) ?: 'Guest',
                'when' => CarbonImmutable::parse($r->check_in)->format('j M'),
            ])->all();
    }
}
