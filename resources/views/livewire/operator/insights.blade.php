{{-- Operator Insights (Phase 6b · SG2) — performance over the SG1 metric layer, scoped by the Phase-6a
     property context + a real date range. EVERY figure is real/derived; a thin range shows an honest
     "not enough data yet" state; the prototype's invented multi-year history + narrative are NOT rendered.
     Lead time + booking pace are deferred "Soon" (real data too thin to be meaningful). --}}
<div>
    <x-operator.shell active="insights" title="Insights"
        :subtitle="$scopeLabel . ' · ' . $activeRange['label']" :viewer="$viewer">

        {{-- Date-range selector (real GET links over real data) --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                @foreach ($ranges as $r)
                    <a class="vy-pill @if ($r['active']) is-on @endif" href="?range={{ $r['key'] }}">{{ $r['label'] }}</a>
                @endforeach
            </div>
            <div class="vy-right">
                <span class="vy-topbar-sub" style="font-size:12px">Switch property in the top bar →</span>
            </div>
        </div>

        @if ($multiCurrency)
            <div class="vy-flash vy-fade" role="status">This scope spans more than one currency — money figures below cover {{ $activeRange['label'] }} in the primary currency only.</div>
        @endif

        {{-- METRIC CARDS — all REAL, reconciling to the metric layer / FolioTotals --}}
        <div class="vy-dash-kpis vy-fade" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            @foreach ($cards as $c)
                {{-- Uniform light tiles (the prototype's Insights KPI band has no dark hero; matches the
                     Rates/Reservations/Guests precedent). --}}
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ $c['label'] }}</div>
                    <div class="vy-dk-val">{{ $c['value'] }}</div>
                    <div class="vy-dk-sub">{{ $c['sub'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="vy-ins-grid vy-fade">
            {{-- REVENUE TREND — real months of real data; honest thin-state below 2 active months --}}
            <div class="vy-card">
                <div class="vy-card-head"><h3>Room revenue by month</h3><span class="vy-card-note">{{ $activeRange['label'] }}</span></div>
                @if (! $trend['enough'])
                    <div class="vy-empty">Not enough data yet for a trend — it appears once at least two months have bookings. (Real data only; no invented history.)</div>
                @else
                    <div class="vy-trend">
                        @foreach ($trend['months'] as $mo)
                            @php $h = $trend['max_minor'] > 0 ? max(3, (int) round($mo['revenue_minor'] / $trend['max_minor'] * 100)) : 0; @endphp
                            <div class="vy-trend-col" title="{{ $mo['label'] }}: {{ $mo['revenue'] }} · {{ $mo['occupancy_pct'] }}% occ">
                                <div class="vy-trend-bar-wrap"><span class="vy-trend-bar" style="height: {{ $h }}%"></span></div>
                                <span class="vy-trend-lab">{{ $mo['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- CHANNEL MIX — real source split (room-nights) --}}
            <div class="vy-card">
                <div class="vy-card-head"><h3>Channel mix</h3><span class="vy-card-note">room-nights by source</span></div>
                @if (count($channelMix) === 0)
                    <div class="vy-empty">No bookings in this range yet.</div>
                @else
                    @foreach ($channelMix as $ch)
                        <div class="vy-mix-row">
                            <span class="vy-mix-lab">{{ $ch['label'] }}</span>
                            <span class="vy-mix-track"><span class="vy-mix-fill" style="width: {{ max(4, $ch['pct']) }}%"></span></span>
                            <span class="vy-mix-val">{{ $ch['pct'] }}%</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- DEFERRED (SG1 verdict) — derivable but too thin in real data to be meaningful, so honestly "Soon" --}}
        <div class="vy-dash-kpis vy-fade" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-top: 14px;">
            <div class="vy-dash-kpi" style="opacity:.72">
                <div class="vy-dk-label">Lead time <span class="vy-soon-tag">Soon</span></div>
                <div class="vy-dk-val" style="color:var(--vy-faint)">—</div>
                <div class="vy-dk-sub">days booked ahead — needs more booking history to be meaningful</div>
            </div>
            <div class="vy-dash-kpi" style="opacity:.72">
                <div class="vy-dk-label">Booking pace <span class="vy-soon-tag">Soon</span></div>
                <div class="vy-dk-val" style="color:var(--vy-faint)">—</div>
                <div class="vy-dk-sub">pickup curve — needs a longer booking-date history</div>
            </div>
        </div>
    </x-operator.shell>
</div>
