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
])

@if (count($bars) > 0)
    <div class="vy-chart" style="height:{{ $height }}">
        @foreach ($bars as $bar)
            <div class="vy-chart-col" title="{{ $bar['label'] }} · {{ $bar['display'] }}">
                <div class="vy-bar {{ ($accentLast && $loop->last) ? 'is-last' : '' }}"
                     style="height:{{ max(4, (int) ($bar['pct'] ?? 0)) }}%{{ ($bar['muted'] ?? false) ? ';opacity:.45' : '' }}"></div>
                <div class="vy-bar-label">{{ $bar['label'] }}</div>
                <div class="vy-bar-label" style="font-weight:600;color:var(--vy-ink)">{{ $bar['display'] }}</div>
            </div>
        @endforeach
    </div>
@endif
