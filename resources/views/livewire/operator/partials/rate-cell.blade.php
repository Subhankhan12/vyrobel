{{-- One rate-grid cell body (shared by the editable button and the read-only cell). REAL values only: the rate is
     rate_calendar.price; availability is computed from the availability row; the rules are restrictions the service writes
     and the booking guard enforces (RestrictionGuard) — so no badge here can render a state the code never sets.
     2026-09-30 — one design for every cell: the price, what is left, a thin bar of how much is booked, and the rules. --}}
<span class="vyr-rate {{ $cell['rate_fit'] ?? '' }} @if ($cell['rate'] === null) is-empty @endif">{{ $cell['rate'] ?? '—' }}</span>

@if ($cell['stop_sell'])
    <span class="vyr-avail is-out">{{ __('livewire/operator/partials/rate-cell.closed') }}</span>
@elseif ($cell['available'] !== null)
    <span class="vyr-avail @if ($cell['sold_out']) is-out @endif">{{ $cell['sold_out'] ? __('livewire/operator/partials/rate-cell.full') : trans_choice('livewire/operator/partials/rate-cell.left', $cell['available'], ['count' => $cell['available']]) }}</span>
@endif

@if (($cell['min_stay'] ?? null) !== null || ($cell['max_stay'] ?? null) !== null || ($cell['closed_to_arrival'] ?? false) || ($cell['closed_to_departure'] ?? false))
    <span class="vyr-flags">
        @if (($cell['min_stay'] ?? null) !== null)
            <span class="vyr-flag vyr-flag--min" title="{{ trans_choice('livewire/operator/partials/rate-cell.min_stay_title', $cell['min_stay'], ['count' => $cell['min_stay']]) }}">{{ __('livewire/operator/partials/rate-cell.min_stay_short', ['nights' => $cell['min_stay']]) }}</span>
        @endif
        {{-- ⚠️ Enforced at booking, so they must be VISIBLE here — otherwise an operator sees a refusal with no explanation
             on the calendar in front of them. --}}
        @if (($cell['max_stay'] ?? null) !== null)
            <span class="vyr-flag vyr-flag--min" title="{{ trans_choice('livewire/operator/partials/rate-cell.max_stay_title', $cell['max_stay'], ['count' => $cell['max_stay']]) }}">{{ __('livewire/operator/partials/rate-cell.max_stay_short', ['nights' => $cell['max_stay']]) }}</span>
        @endif
        @if ($cell['closed_to_arrival'] ?? false)
            <span class="vyr-flag vyr-flag--closed" title="{{ __('livewire/operator/partials/rate-cell.closed_to_arrival_title') }}">{{ __('livewire/operator/partials/rate-cell.closed_to_arrival_short') }}</span>
        @endif
        @if ($cell['closed_to_departure'] ?? false)
            <span class="vyr-flag vyr-flag--closed" title="{{ __('livewire/operator/partials/rate-cell.closed_to_departure_title') }}">{{ __('livewire/operator/partials/rate-cell.closed_to_departure_short') }}</span>
        @endif
    </span>
@endif

@if (($cell['booked_pct'] ?? null) !== null)
    <span class="vyr-booked" title="{{ __('livewire/operator/partials/rate-cell.booked_pct', ['pct' => $cell['booked_pct']]) }}" aria-hidden="true"><i style="width: {{ $cell['booked_pct'] }}%"></i></span>
@endif
