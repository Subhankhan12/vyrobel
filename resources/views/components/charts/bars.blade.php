{{--
    Shared vertical bar-series chart (Batch B) — ONE component so the owner Unit Detail charts (revenue ·
    occupancy) and the operator + owner Insights "occupancy by month" / "nights that fill first" charts all
    render the same way and cannot drift. Pure presentation over REAL, caller-computed figures — it invents
    nothing; each bar's height is the caller's own `pct` (0–100, relative to the series max). Reuses the
    existing vy-chart / vy-bar / vy-bar-label design-system classes (extracted from the owner Archive chart).

    Props:
      bars       — list of ['label' => string, 'display' => string, 'pct' => int 0-100(, 'muted' => bool)]
      height     — the plot height (default 120px)
      accentLast — accent the final bar (the current/most-recent period), like the Archive chart
--}}
@props([
    'bars' => [],
    'height' => '120px',
    'accentLast' => true,
    // Opt-in: the Reports wireframe seats the value ABOVE the bar. Default keeps the existing order so
    // the other consumer (owner Insights) is unchanged.
    'valueTop' => false,
])

@if (count($bars) > 0)
    <div class="vy-chart" style="height:{{ $height }}">
        @foreach ($bars as $bar)
            @php $vyPct = (int) ($bar['pct'] ?? 0); @endphp
            <div class="vy-chart-col" title="{{ $bar['label'] }} · {{ $bar['display'] }}">
                @if ($valueTop)
                    <div class="vy-bar-label" style="font-weight:600;color:var(--vy-ink)">{{ $bar['display'] }}</div>
                @endif
                {{-- A genuinely ZERO bucket draws NOTHING. The old `max(4, …)` floor put a visible stub
                     under a "€0.00" label, which reads as a small non-zero value (#39). --}}
                <div class="vy-bar {{ ($accentLast && $loop->last) ? 'is-last' : '' }}"
                     style="height:{{ $vyPct }}%{{ $vyPct > 0 ? ';min-height:2px' : '' }}{{ ($bar['muted'] ?? false) ? ';opacity:.45' : '' }}"></div>
                <div class="vy-bar-label">{{ $bar['label'] }}</div>
                @unless ($valueTop)
                    <div class="vy-bar-label" style="font-weight:600;color:var(--vy-ink)">{{ $bar['display'] }}</div>
                @endunless
            </div>
        @endforeach
    </div>
@endif
