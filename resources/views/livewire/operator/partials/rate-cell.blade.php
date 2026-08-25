{{-- One rate-grid cell body (shared by the editable button and the read-only cell). REAL values only:
     the rate is rate_calendar.price; availability is computed from the availability row; the two flags are
     restrictions.min_stay / restrictions.stop_sell — both values the service actually writes, so no badge
     here can render a status the code never sets. --}}
<div class="vyr-rate @if ($cell['rate'] === null) is-empty @endif">{{ $cell['rate'] ?? '—' }}</div>

@if ($cell['available'] !== null)
    <div class="vyr-avail @if ($cell['sold_out']) is-out @endif">
        {{ $cell['sold_out'] ? 'Full' : $cell['available'].' open' }}
    </div>
@endif

<div class="vyr-flags">
    @if ($cell['stop_sell'])
        <span class="vyr-flag vyr-flag--closed" title="Closed — not for sale">Closed</span>
    @endif
    @if ($cell['min_stay'] !== null)
        <span class="vyr-flag vyr-flag--min" title="Minimum {{ $cell['min_stay'] }} nights">{{ $cell['min_stay'] }}n min</span>
    @endif
    {{-- ⚠️ The three that became settable this pass. They are enforced at booking, so they must be VISIBLE
         here — otherwise an operator sees a refusal with no explanation on the calendar in front of them. --}}
    @if (($cell['max_stay'] ?? null) !== null)
        <span class="vyr-flag vyr-flag--min" title="Maximum {{ $cell['max_stay'] }} nights">{{ $cell['max_stay'] }}n max</span>
    @endif
    @if ($cell['closed_to_arrival'] ?? false)
        <span class="vyr-flag vyr-flag--closed" title="Closed to arrival — a stay cannot start today">No arr</span>
    @endif
    @if ($cell['closed_to_departure'] ?? false)
        <span class="vyr-flag vyr-flag--closed" title="Closed to departure — a stay cannot end today">No dep</span>
    @endif
</div>
