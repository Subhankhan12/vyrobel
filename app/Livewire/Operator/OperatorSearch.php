<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Services\SearchService;
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
        $user = auth()->user();
        $can = [
            'guests' => (bool) $user?->can('guests.view'),
            'units' => (bool) $user?->can('units.view'),
        ];

        $results = $query !== ''
            ? $search->search($query, $can)
            : ['reservations' => [], 'guests' => [], 'units' => [], 'owners' => []];

        // Add display initials for the name-avatar rows (initials() is a trait method — resolved here, not in
        // the view, since it is protected).
        foreach (['guests', 'owners'] as $group) {
            $results[$group] = array_map(
                fn (array $row): array => array_merge($row, ['initials' => $this->initials($row['name'])]),
                $results[$group],
            );
        }

        $total = count($results['reservations']) + count($results['guests']) + count($results['units']) + count($results['owners']);

        return view('livewire.operator.operator-search', [
            'viewer' => $this->viewer(),
            'query' => $query,
            'results' => $results,
            'total' => $total,
            'searched' => $query !== '',
            'contactNote' => $query !== '' && $search->looksLikeContact($query),
            'canGuests' => $can['guests'],
            'canUnits' => $can['units'],
        ]);
    }

    private function resolveQuery(): string
    {
        $param = request()->query('q');

        return is_string($param) ? mb_substr(trim($param), 0, self::MAX_QUERY) : '';
    }
}
