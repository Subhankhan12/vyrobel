{{-- ⚠️ ADMIN 01 · PHASE 5 — HOTEL-PLATFORM STATISTICS: aggregates only (never a row, never a guest), customers only
     unless internal organisations are asked for. Cohorts are a table, not a chart — and with few hotels the page says
     the numbers describe individual hotels, not a trend. --}}
@php
    use App\Support\Money\MoneyFormatter;
    use App\Support\Platform\PlatformHotelStats;
    $few = $s['organizations'] < PlatformHotelStats::TREND_FLOOR;
    $maxDay = max(1, max($s['bookings_per_day'] ?: [0]));
@endphp
<x-admin.shell active="hotels" :title="__('admin/hotels.title')" :subtitle="__('admin/hotels.subtitle')">

    <div class="ad-filters" style="justify-content: space-between;">
        <span class="ad-stat-sub" style="margin: 0;">{{ $s['include_internal'] ? trans_choice('admin/hotels.scope_internal', $s['organizations'], ['count' => number_format($s['organizations'])]) : trans_choice('admin/hotels.scope_customers', $s['organizations'], ['count' => number_format($s['organizations'])]) }}</span>
        <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.hotels', $s['include_internal'] ? [] : ['internal' => 'include']) }}">{{ $s['include_internal'] ? __('admin/hotels.customers_only') : __('admin/hotels.include_internal') }}</a>
    </div>

    @if ($s['organizations'] === 0)
        <div class="ad-card" style="margin-bottom: 18px;">{!! __('admin/hotels.no_customers') !!}</div>
    @elseif ($few)
        <div class="ad-card" style="margin-bottom: 18px;">{!! __('admin/hotels.few_organisations', ['floor' => e(PlatformHotelStats::TREND_FLOOR)]) !!}</div>
    @endif

    <div class="ad-grid-2">
        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/hotels.under_management') }}</h2>
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/hotels.rooms_and_units') }}</span><span class="ad-row-v">{{ number_format($s['rooms']) }}</span></div>
            <h2 class="ad-panel-title" style="margin-top: 18px;">{{ __('admin/hotels.average_booking_heading') }}</h2>
            @forelse ($s['average_booking'] as $currency => $row)
                <div class="ad-row"><span class="ad-row-k">{{ trans_choice('admin/hotels.average_booking_row', $row['bookings'], ['currency' => $currency, 'count' => $row['bookings']]) }}</span><span class="ad-row-v">{{ MoneyFormatter::display($row['average_minor'], $currency) }}</span></div>
            @empty
                <div class="ad-empty">{{ __('admin/hotels.no_bookings_30d') }}</div>
            @endforelse
            <p class="ad-reason-hint">{{ __('admin/hotels.average_booking_hint') }}</p>
        </div>
        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/hotels.money_heading') }}</h2>
            @forelse ($s['money'] as $currency => $row)
                <div class="ad-row"><span class="ad-row-k">{{ __('admin/hotels.money_all_time', ['currency' => $currency]) }}</span><span class="ad-row-v">{{ MoneyFormatter::display($row['processed_minor'], $currency) }}</span></div>
                <div class="ad-row"><span class="ad-row-k">{{ __('admin/hotels.money_30d', ['currency' => $currency]) }}</span><span class="ad-row-v">{{ MoneyFormatter::display($row['processed_30d_minor'], $currency) }}</span></div>
                <div class="ad-row"><span class="ad-row-k">{{ __('admin/hotels.money_refunded', ['currency' => $currency]) }}</span><span class="ad-row-v">{{ MoneyFormatter::display($row['refunded_minor'], $currency) }}</span></div>
            @empty
                <div class="ad-empty">{{ __('admin/hotels.no_payments') }}</div>
            @endforelse
            <p class="ad-reason-hint">{{ __('admin/hotels.money_hint') }}</p>
        </div>
    </div>

    <div class="ad-card" style="margin-top: 18px;">
        <h2 class="ad-panel-title">{{ __('admin/hotels.bookings_per_day_heading') }}</h2>
        <div style="display: flex; align-items: flex-end; gap: 3px; height: 90px;" role="img" aria-label="{{ __('admin/hotels.bookings_per_day_aria') }}">
            @foreach ($s['bookings_per_day'] as $day => $n)
                <div title="{{ $day }}: {{ $n }}" style="flex: 1; min-width: 4px; height: {{ max(2, (int) round($n * 86 / $maxDay)) }}px; background: {{ $n > 0 ? 'var(--ad-accent)' : 'var(--ad-border)' }}; border-radius: 2px;"></div>
            @endforeach
        </div>
        <p class="ad-reason-hint" style="margin-top: 8px;">{{ __('admin/hotels.bookings_per_day_summary', ['total' => number_format(array_sum($s['bookings_per_day'])), 'max' => number_format(max($s['bookings_per_day'] ?: [0]))]) }}</p>
    </div>

    <div class="ad-grid-2" style="margin-top: 18px;">
        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/hotels.occupancy_heading') }}</h2>
            @foreach ($s['occupancy']['buckets'] as $bucket => $n)
                <div class="ad-row"><span class="ad-row-k">{{ $bucket }}</span><span class="ad-row-v">{{ trans_choice('admin/hotels.properties_count', $n, ['count' => $n]) }}</span></div>
            @endforeach
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/hotels.no_sellable_inventory') }}</span><span class="ad-row-v">{{ $s['occupancy']['no_inventory'] }}</span></div>
            <p class="ad-reason-hint">{{ __('admin/hotels.occupancy_hint') }}</p>
        </div>
        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/hotels.adoption_heading') }}</h2>
            @php($moduleWords = (array) __('admin/hotels.modules'))
            @foreach ($s['adoption'] as $module => $n)
                <div class="ad-row"><span class="ad-row-k">{{ is_string($moduleWords[$module] ?? null) ? $moduleWords[$module] : $module }}</span><span class="ad-row-v">{{ __('admin/hotels.adoption_row', ['used' => $n, 'total' => $s['organizations']]) }}</span></div>
            @endforeach
            <p class="ad-reason-hint">{{ __('admin/hotels.adoption_hint') }}</p>
        </div>
    </div>

    <div class="ad-card" style="margin-top: 18px; overflow-x: auto;">
        <h2 class="ad-panel-title">{{ __('admin/hotels.cohorts_heading') }}</h2>
        @if ($s['cohorts'] === [])
            <div class="ad-empty">{{ __('admin/hotels.no_cohorts') }}</div>
        @else
            <table class="ad-table">
                <thead><tr><th>{{ __('admin/hotels.col_joined') }}</th><th class="ad-num">{{ __('admin/hotels.col_organisations') }}</th>@foreach (PlatformHotelStats::COHORT_MONTHS as $k)<th class="ad-num">{{ __('admin/hotels.col_month', ['month' => $k]) }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($s['cohorts'] as $c)
                        <tr>
                            {{-- ADMIN 03 · F18 — "Sep 2026" like every other date here; the `Y-m` key stays underneath because it sorts. --}}
                            <td>{{ preg_match('/^\d{4}-\d{2}$/', $c['month']) === 1 ? \Carbon\CarbonImmutable::createFromFormat('!Y-m', $c['month'], 'UTC')->translatedFormat(\App\Support\I18n\LocalDate::pattern('M Y')) : __('admin/hotels.unknown_month') }}</td>
                            <td class="ad-num">{{ $c['size'] }}</td>
                            @foreach (PlatformHotelStats::COHORT_MONTHS as $k)
                                <td class="ad-num" style="{{ $c['active'][$k] === null ? 'color: var(--ad-faint);' : '' }}">{{ $c['active'][$k] === null ? __('admin/hotels.not_yet') : __('admin/hotels.cohort_active_of', ['active' => $c['active'][$k], 'size' => $c['size']]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <p class="ad-reason-hint" style="margin-top: 8px;">{{ __('admin/hotels.cohorts_hint') }}</p>
    </div>
</x-admin.shell>
