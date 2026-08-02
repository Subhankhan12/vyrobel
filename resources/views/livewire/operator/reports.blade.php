{{-- Operator Reports (Phase 6b · SG2) — a period report on the ARRIVAL basis, scoped by the Phase-6a
     property context. Revenue is Σ REAL folio items (reconciles to FolioTotals); the only real operator
     cost captured today is MAINTENANCE; the prototype's other expense lines have NO data source and are
     HONESTLY listed as "not tracked yet", never fabricated. No "best month on record" narrative. --}}
<div>
    <x-operator.shell active="reports" title="Reports"
        :subtitle="$scopeLabel . ' · arrivals in ' . $activeRange['label']" :viewer="$viewer">

        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                @foreach ($ranges as $r)
                    <a class="vy-pill @if ($r['active']) is-on @endif" href="?range={{ $r['key'] }}">{{ $r['label'] }}</a>
                @endforeach
                {{-- GATE 10.1 — the prototype's "Reporting month" picker: jump to a specific calendar month (real
                     ?month=YYYY-MM; the shared ReportPeriod resolves it for the page + both exports). --}}
                <select class="vy-input" style="padding:5px 10px;font-size:12px;max-width:150px" aria-label="Reporting month"
                        onchange="if (this.value) window.location.href='?month='+this.value;">
                    <option value="">Jump to month…</option>
                    @foreach ($months as $mo)
                        <option value="{{ $mo['value'] }}" @selected($mo['active'])>{{ $mo['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="vy-right">
                {{-- GATE 10.2 — the wireframe's "Exports" menu. PDF report + CSV are REAL (rendered from the SAME
                     OperatorReportData the page builds — can't drift). Excel + email-as-PDF are HONESTLY "Soon"
                     (Excel needs a spreadsheet library; neither is faked as a working download). --}}
                <div style="position:relative" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="vy-pill" @click="open = !open">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                        Exports
                    </button>
                    <div x-show="open" x-cloak x-transition style="position:absolute;right:0;top:calc(100% + 6px);z-index:30;width:250px;background:#FFFDFB;border:1px solid var(--vy-hairline);border-radius:14px;box-shadow:0 18px 44px rgba(120,70,40,.22);padding:6px">
                        <a href="{{ $pdfUrl }}" style="display:block;padding:9px 10px;border-radius:10px;text-decoration:none;color:var(--vy-ink)">
                            <span style="display:block;font-size:13px;font-weight:600">PDF report</span>
                            <span style="display:block;font-size:11px;color:var(--vy-faint)">Revenue, KPIs &amp; channel mix</span>
                        </a>
                        <a href="{{ $exportUrl }}" style="display:block;padding:9px 10px;border-radius:10px;text-decoration:none;color:var(--vy-ink)">
                            <span style="display:block;font-size:13px;font-weight:600">CSV export</span>
                            <span style="display:block;font-size:11px;color:var(--vy-faint)">Channel &amp; month rows · opens in Excel</span>
                        </a>
                        <div style="padding:9px 10px;border-radius:10px;opacity:.55" title="Needs a spreadsheet library — coming soon">
                            <span style="display:block;font-size:13px;font-weight:600">Excel workbook <span class="vy-soon-tag" style="font-size:9px">Soon</span></span>
                            <span style="display:block;font-size:11px;color:var(--vy-faint)">Use the CSV meanwhile</span>
                        </div>
                        <div style="padding:9px 10px;border-radius:10px;opacity:.55" title="Coming soon">
                            <span style="display:block;font-size:13px;font-weight:600">Email as PDF <span class="vy-soon-tag" style="font-size:9px">Soon</span></span>
                            <span style="display:block;font-size:11px;color:var(--vy-faint)">Send the report to your inbox</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($empty)
            <div class="vy-card vy-fade"><div class="vy-empty">No arrivals or costs in {{ $activeRange['label'] }} for {{ $scopeLabel }} yet. Real data only — nothing is invented for an empty period.</div></div>
        @else
            {{-- GATE 10.1 — the prototype's KPI lead: REAL performance metrics (occupancy · ADR · RevPAR, stay-night
                 basis via PerformanceMetrics) + gross revenue (folio basis). Each performance KPI shows a vs-last-year
                 delta ONLY where the prior-year period has data — never a fabricated trend (#39). --}}
            <div class="vy-dash-kpis vy-dash-kpis--4 vy-fade" style="margin-bottom: 6px;">
                @foreach ($kpis as $c)
                    <div class="vy-dash-kpi">
                        <div class="vy-dk-label">{{ $c['label'] }}</div>
                        <div style="display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;">
                            <div class="vy-dk-val">{{ $c['value'] }}</div>
                            @if ($c['delta'] !== null)
                                <span style="font-size: 12px; font-weight: 700; color: {{ $c['delta_dir'] === 'down' ? '#8F3110' : 'var(--vy-accent)' }};">{{ $c['delta'] }}</span>
                            @endif
                        </div>
                        <div class="vy-dk-sub">{{ $c['sub'] }}</div>
                    </div>
                @endforeach
            </div>
            <p class="vy-fade" style="font-size: 11px; color: var(--vy-faint); margin: 0 0 16px;">Occupancy · ADR · RevPAR are on the stay-night basis; deltas are vs the same period last year, shown only where prior-year data exists. Revenue below is on the folio (arrival) basis.</p>

            <div class="vy-ins-grid vy-fade">
                {{-- REVENUE — real folio breakdown --}}
                <div class="vy-card">
                    <div class="vy-card-head"><h3>Revenue</h3><span class="vy-card-note">{{ $arrivals }} {{ \Illuminate\Support\Str::plural('arrival', $arrivals) }}</span></div>
                    <div class="vy-report-line"><span>Room</span><span>{{ $revenue['room'] }}</span></div>
                    <div class="vy-report-line"><span>Ancillary <span class="vy-fl-note">· add-ons, products, fees</span></span><span>{{ $revenue['ancillary'] }}</span></div>
                    <div class="vy-report-line vy-report-total"><span>Gross revenue</span><span>{{ $revenue['gross'] }}</span></div>
                    <div class="vy-report-line vy-report-muted"><span>Tax collected <span class="vy-fl-note">· pass-through</span></span><span>{{ $revenue['tax'] }}</span></div>
                </div>

                {{-- COSTS — maintenance real; the rest honestly not tracked --}}
                <div class="vy-card">
                    <div class="vy-card-head"><h3>Costs</h3><span class="vy-card-note">real data only</span></div>
                    <div class="vy-report-line"><span>Maintenance <span class="vy-fl-note">· resolved work orders</span></span><span>{{ $expenses['maintenance'] }}</span></div>
                    @foreach ($expenses['untracked'] as $cat)
                        <div class="vy-report-line vy-report-muted"><span>{{ $cat }}</span><span class="vy-soon-tag">Not tracked yet</span></div>
                    @endforeach
                    <div class="vy-report-line vy-report-total"><span>Net (revenue − tracked costs)</span><span>{{ $net }}</span></div>
                </div>
            </div>

            {{-- TOP PERFORMERS — real room revenue per unit --}}
            <div class="vy-card vy-fade" style="margin-top: 14px;">
                <div class="vy-card-head"><h3>Top units</h3><span class="vy-card-note">by room revenue · arrivals in {{ $activeRange['label'] }}</span></div>
                @if (count($topUnits) === 0)
                    <div class="vy-empty">No unit revenue in this range yet.</div>
                @else
                    <table class="vy-table">
                        <thead><tr><th>Unit</th><th class="vy-num">Nights</th><th class="vy-num">Room revenue</th></tr></thead>
                        <tbody>
                            @foreach ($topUnits as $u)
                                <tr>
                                    <td>{{ $u['code'] }}@if ($u['name'] !== $u['code'])<span class="vy-fl-note"> · {{ $u['name'] }}</span>@endif</td>
                                    <td class="vy-num">{{ $u['nights'] }}</td>
                                    <td class="vy-num">{{ $u['revenue'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- #104 GATE 2 — REAL revenue-by-channel table (the reservation's real `source`; OTA brand honest) --}}
            <div class="vy-card vy-fade" style="margin-top: 14px;">
                <div class="vy-card-head"><h3>Revenue by channel</h3><span class="vy-card-note">by booking source · reconciles to gross</span></div>
                @if (count($byChannel) === 0)
                    <div class="vy-empty">No channel revenue in this range yet.</div>
                @else
                    <table class="vy-table">
                        <thead><tr><th>Channel</th><th class="vy-num">Bookings</th><th class="vy-num">Revenue</th><th class="vy-num">Share</th></tr></thead>
                        <tbody>
                            @foreach ($byChannel as $c)
                                <tr>
                                    <td>{{ $c['label'] }}</td>
                                    <td class="vy-num">{{ $c['bookings'] }}</td>
                                    <td class="vy-num">{{ $c['revenue'] }}</td>
                                    <td class="vy-num">{{ $c['share'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- #104 GATE 2 — REAL revenue-by-month chart (same folio basis; every month a real bucket) --}}
            <div class="vy-card vy-fade" style="margin-top: 14px;">
                <div class="vy-card-head"><h3>Revenue by month</h3><span class="vy-card-note">{{ $activeRange['label'] }}</span></div>
                <x-charts.bars :bars="$byMonth" height="140px" />
            </div>
        @endif
    </x-operator.shell>
</div>
