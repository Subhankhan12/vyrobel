{{-- ⚠️ ADMIN 01 · PHASE 5 — HOTEL-PLATFORM STATISTICS: aggregates only (never a row, never a guest), customers only
     unless internal organisations are asked for. Cohorts are a table, not a chart — and with few hotels the page says
     the numbers describe individual hotels, not a trend. --}}
@php
    use App\Support\Money\MoneyFormatter;
    use App\Support\Platform\PlatformHotelStats;
    $few = $s['organizations'] < PlatformHotelStats::TREND_FLOOR;
    $maxDay = max(1, max($s['bookings_per_day'] ?: [0]));
@endphp
<x-admin.shell active="hotels" title="Statistics" subtitle="What the platform is doing — in aggregate. Never a guest, never a row.">

    <div class="ad-filters" style="justify-content: space-between;">
        <span class="ad-stat-sub" style="margin: 0;">{{ $s['include_internal'] ? 'Including Vyrobel’s own internal organisations.' : 'Customers only.' }} · {{ number_format($s['organizations']) }} organisation{{ $s['organizations'] === 1 ? '' : 's' }}</span>
        <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.hotels', $s['include_internal'] ? [] : ['internal' => 'include']) }}">{{ $s['include_internal'] ? 'Customers only' : 'Include internal' }}</a>
    </div>

    @if ($s['organizations'] === 0)
        <div class="ad-card" style="margin-bottom: 18px;"><strong>No customers yet</strong> — every organisation on the platform is one of Vyrobel’s own. Include internal organisations to see the machinery work.</div>
    @elseif ($few)
        <div class="ad-card" style="margin-bottom: 18px;">⚠️ <strong>Fewer than {{ PlatformHotelStats::TREND_FLOOR }} organisations.</strong> These numbers describe individual hotels, not a trend — read them as facts about those hotels, never as a curve.</div>
    @endif

    <div class="ad-grid-2">
        <div class="ad-card">
            <h2 class="ad-panel-title">Under management</h2>
            <div class="ad-row"><span class="ad-row-k">Rooms and units (active, in live properties)</span><span class="ad-row-v">{{ number_format($s['rooms']) }}</span></div>
            <h2 class="ad-panel-title" style="margin-top: 18px;">Average booking value · last 30 days</h2>
            @forelse ($s['average_booking'] as $currency => $row)
                <div class="ad-row"><span class="ad-row-k">{{ $currency }} · {{ $row['bookings'] }} booking{{ $row['bookings'] === 1 ? '' : 's' }}</span><span class="ad-row-v">{{ MoneyFormatter::display($row['average_minor'], $currency) }}</span></div>
            @empty
                <div class="ad-empty">No bookings in the last 30 days.</div>
            @endforelse
            <p class="ad-reason-hint">Created in the last 30 days, not cancelled or no-show. Per currency — never mixed.</p>
        </div>
        <div class="ad-card">
            <h2 class="ad-panel-title">Money processed through folios</h2>
            @forelse ($s['money'] as $currency => $row)
                <div class="ad-row"><span class="ad-row-k">{{ $currency }} · all time</span><span class="ad-row-v">{{ MoneyFormatter::display($row['processed_minor'], $currency) }}</span></div>
                <div class="ad-row"><span class="ad-row-k">{{ $currency }} · last 30 days</span><span class="ad-row-v">{{ MoneyFormatter::display($row['processed_30d_minor'], $currency) }}</span></div>
                <div class="ad-row"><span class="ad-row-k">{{ $currency }} · refunded, all time</span><span class="ad-row-v">{{ MoneyFormatter::display($row['refunded_minor'], $currency) }}</span></div>
            @empty
                <div class="ad-empty">No payment has been recorded on a folio.</div>
            @endforelse
            <p class="ad-reason-hint">Succeeded and captured payments — card, cash and transfer alike — recorded by the hotels. This is the hotels’ money, not Vyrobel’s revenue.</p>
        </div>
    </div>

    <div class="ad-card" style="margin-top: 18px;">
        <h2 class="ad-panel-title">Bookings created per day · last 30 days</h2>
        <div style="display: flex; align-items: flex-end; gap: 3px; height: 90px;" role="img" aria-label="Bookings per day, last 30 days">
            @foreach ($s['bookings_per_day'] as $day => $n)
                <div title="{{ $day }}: {{ $n }}" style="flex: 1; min-width: 4px; height: {{ max(2, (int) round($n * 86 / $maxDay)) }}px; background: {{ $n > 0 ? 'var(--ad-accent)' : 'var(--ad-border)' }}; border-radius: 2px;"></div>
            @endforeach
        </div>
        <p class="ad-reason-hint" style="margin-top: 8px;">{{ number_format(array_sum($s['bookings_per_day'])) }} in total · the busiest day had {{ number_format(max($s['bookings_per_day'] ?: [0])) }}.</p>
    </div>

    <div class="ad-grid-2" style="margin-top: 18px;">
        <div class="ad-card">
            <h2 class="ad-panel-title">Occupancy · last 30 nights</h2>
            @foreach ($s['occupancy']['buckets'] as $bucket => $n)
                <div class="ad-row"><span class="ad-row-k">{{ $bucket }}</span><span class="ad-row-v">{{ $n }} propert{{ $n === 1 ? 'y' : 'ies' }}</span></div>
            @endforeach
            <div class="ad-row"><span class="ad-row-k">No sellable inventory</span><span class="ad-row-v">{{ $s['occupancy']['no_inventory'] }}</span></div>
            <p class="ad-reason-hint">Nights sold ÷ nights sellable (total less out-of-order and blocked), per property. A property with nothing to sell is counted apart — never as 0%.</p>
        </div>
        <div class="ad-card">
            <h2 class="ad-panel-title">Module adoption · last 30 days</h2>
            @foreach ($s['adoption'] as $module => $n)
                <div class="ad-row"><span class="ad-row-k">{{ $module }}</span><span class="ad-row-v">{{ $n }} of {{ $s['organizations'] }}</span></div>
            @endforeach
            <p class="ad-reason-hint">Organisations with at least one real usage event for the module — the product’s own record of business actions — in the last 30 days.</p>
        </div>
    </div>

    <div class="ad-card" style="margin-top: 18px; overflow-x: auto;">
        <h2 class="ad-panel-title">Cohorts — still active after 1, 3, 6 and 12 months</h2>
        @if ($s['cohorts'] === [])
            <div class="ad-empty">No organisations to group.</div>
        @else
            <table class="ad-table">
                <thead><tr><th>Joined</th><th class="ad-num">Organisations</th>@foreach (PlatformHotelStats::COHORT_MONTHS as $k)<th class="ad-num">Month {{ $k }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($s['cohorts'] as $c)
                        <tr>
                            {{-- ADMIN 03 · F18 — "Sep 2026" like every other date here; the `Y-m` key stays underneath because it sorts. --}}
                            <td>{{ preg_match('/^\d{4}-\d{2}$/', $c['month']) === 1 ? \Carbon\CarbonImmutable::createFromFormat('!Y-m', $c['month'], 'UTC')->format('M Y') : 'Unknown' }}</td>
                            <td class="ad-num">{{ $c['size'] }}</td>
                            @foreach (PlatformHotelStats::COHORT_MONTHS as $k)
                                <td class="ad-num" style="{{ $c['active'][$k] === null ? 'color: var(--ad-faint);' : '' }}">{{ $c['active'][$k] === null ? 'not yet' : $c['active'][$k].' of '.$c['size'] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <p class="ad-reason-hint" style="margin-top: 8px;">Active in month k = at least one business event in the k-th month after it joined. “Not yet” = that month has not ended for every organisation in the cohort — never counted as zero.</p>
    </div>
</x-admin.shell>
