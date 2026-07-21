{{-- Owner Insights (Phase 6b · SG3) — the owner's OWN units' performance over the SG1 metric layer,
     owner-scoped. Every figure is real; a thin range shows an honest empty state; the ONE observation is
     genuinely derived (busiest weekday) and omitted when the data is too thin — no invented narrative. --}}
<x-owner.shell active="insights" title="Insights" :viewer="$viewer">

    {{-- Date-range selector (real GET links over real data) --}}
    <div class="vy-fade" style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap">
        @foreach ($ranges as $r)
            <a class="vy-btn {{ $r['active'] ? 'vy-btn--accent' : 'vy-btn--ghost' }}" href="?range={{ $r['key'] }}">{{ $r['label'] }}</a>
        @endforeach
    </div>

    @if ($unitsCount === 0)
        <div class="vy-card vy-fade"><div class="vy-empty">No units linked to your account yet.</div></div>
    @else
        {{-- METRIC CARDS — real, owner-scoped --}}
        <div class="vy-kpi-grid vy-fade" style="animation-delay:.02s">
            @foreach ($cards as $c)
                <div class="vy-kpi">
                    <div class="vy-kpi-label">{{ $c['label'] }}</div>
                    <div class="vy-kpi-val">{{ $c['value'] }}</div>
                    <div class="vy-kpi-sub">{{ $c['sub'] }}</div>
                </div>
            @endforeach
        </div>

        @if ($observation)
            <div class="vy-card vy-fade" style="margin-top:16px;display:flex;align-items:center;gap:11px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" style="color:var(--vy-accent,#b5654a);flex:none"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.1V17h6v-.2c0-.8.4-1.6 1-2.1A7 7 0 0 0 12 2z"/></svg>
                <span>{{ $observation }}</span>
            </div>
        @endif

        {{-- REVENUE TREND — real months only; honest thin-state below 2 active months --}}
        <div class="vy-card vy-fade" style="margin-top:16px">
            <div class="vy-card-title">Room revenue by month · {{ $activeRange['label'] }}</div>
            @if (! $trend['enough'])
                <div class="vy-empty">Not enough data yet for a trend — it appears once at least two months have bookings. (Real data only; no invented history.)</div>
            @else
                <div style="display:flex;align-items:flex-end;gap:8px;height:140px;margin-top:8px">
                    @foreach ($trend['months'] as $mo)
                        @php $h = $trend['max_minor'] > 0 ? max(3, (int) round($mo['revenue_minor'] / $trend['max_minor'] * 100)) : 0; @endphp
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;height:100%" title="{{ $mo['label'] }}: {{ $mo['revenue'] }}">
                            <div style="flex:1;width:100%;display:flex;align-items:flex-end;justify-content:center">
                                <span style="display:block;width:60%;min-height:2px;height:{{ $h }}%;border-radius:5px 5px 0 0;background:var(--vy-grad,#b5654a)"></span>
                            </div>
                            <span style="font-size:10.5px;color:var(--vy-muted,#8a8079)">{{ $mo['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-owner.shell>
