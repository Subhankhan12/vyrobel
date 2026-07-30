{{-- Operator INSIGHTS (#88 exact-match) — portfolio performance over the REAL SG1 metric layer, scoped by the
     property context + a real range. EVERY figure real/derived (PerformanceMetrics/BookingCadence); vs-LY shown
     only where prior-year data exists; the "Worth a look" cards are REAL DERIVED observations shown only when
     the data supports them; the Expedia/brand fabrications are REFUSED (honest note). "Decisions stay yours."
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="insights" title="Portfolio insights" :subtitle="$scopeLabel . ' · ' . $activeRange['label']" :viewer="$viewer">

        {{-- Range presets (real GET over real data) --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left" style="flex-wrap: wrap; gap: 6px;">
                @foreach ($ranges as $r)
                    <a class="vy-pill @if ($r['active']) is-on @endif" href="?range={{ $r['key'] }}">{{ $r['label'] }}</a>
                @endforeach
            </div>
            <div class="vy-right"><span class="vy-topbar-sub" style="font-size: 12px;">Switch property in the top bar →</span></div>
        </div>

        @if ($multiCurrency)
            <div class="vy-flash vy-fade" role="status">This scope spans more than one currency — money figures cover {{ $activeRange['label'] }} in the primary currency only.</div>
        @endif

        {{-- ── KPI cards (4) with real vs-LY deltas ───────────────────────────────────────────────── --}}
        <div class="vy-dash-kpis vy-dash-kpis--4 vy-fade">
            @foreach ($cards as $c)
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

        <div class="vy-ins-grid vy-fade" style="margin-top: 18px;">
            {{-- ── Occupancy by month (this-year + on-the-books + real LY) ─────────────────────────── --}}
            <div class="vy-card">
                <div class="vy-detail-head" style="margin-bottom: 4px;">
                    <h2 class="vy-card-title">Occupancy by month</h2>
                    <div style="display: flex; align-items: center; gap: 12px; font-size: 11px; color: var(--vy-muted);">
                        <span><span style="display: inline-block; width: 9px; height: 9px; border-radius: 2px; background: var(--vy-accent);"></span> This year</span>
                        <span><span style="display: inline-block; width: 9px; height: 9px; border-radius: 2px; background: repeating-linear-gradient(45deg, var(--vy-accent), var(--vy-accent) 2px, #fff 2px, #fff 4px);"></span> On the books</span>
                        @if ($occByMonth['has_ly'])
                            <span><span style="display: inline-block; width: 9px; height: 9px; border-radius: 2px; background: rgba(120,70,40,.22);"></span> Last year</span>
                        @endif
                    </div>
                </div>
                @if ($occByMonth['enough'])
                    <div style="display: flex; align-items: flex-end; gap: 6px; height: 160px; margin-top: 14px;">
                        @foreach ($occByMonth['months'] as $m)
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; height: 100%;">
                                <div style="flex: 1; width: 100%; display: flex; align-items: flex-end; justify-content: center; gap: 2px;">
                                    @if ($m['ly'] !== null)
                                        <div title="LY {{ $m['ly'] }}%" style="width: 34%; height: {{ $m['ly'] }}%; background: rgba(120,70,40,.20); border-radius: 3px 3px 0 0;"></div>
                                    @endif
                                    <div title="{{ $m['occ'] }}%" style="width: 42%; height: {{ max(2, $m['occ']) }}%; border-radius: 3px 3px 0 0; background: {{ $m['on_books'] ? 'repeating-linear-gradient(45deg, var(--vy-accent), var(--vy-accent) 3px, #F6D9C7 3px, #F6D9C7 6px)' : 'var(--vy-accent)' }};"></div>
                                </div>
                                <div style="font-size: 10px; color: var(--vy-muted);">{{ $m['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="vy-empty">Not enough data in this range yet.</div>
                @endif
            </div>

            {{-- ── Pace by property (forward 30d vs LY) ────────────────────────────────────────────── --}}
            <div class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">Pace by property</h2>
                <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 12px;">Next 30 days booked vs the same moment last year.</p>
                @forelse ($pace as $row)
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                            <span style="font-size: 13px; color: var(--vy-ink);">{{ $row['name'] }}</span>
                            @php $tone = ['ahead' => ['Ahead', 'var(--vy-accent)'], 'behind' => ['Behind', '#8F3110'], 'onpace' => ['On pace', 'var(--vy-muted)'], 'flat' => ['—', 'var(--vy-faint)']][$row['tone']]; @endphp
                            <span style="font-size: 11.5px; color: var(--vy-muted);">
                                <span style="font-weight: 700; color: {{ $tone[1] }};">{{ $tone[0] }}</span>
                                · {{ $row['booked'] }}% booked @if ($row['ly'] !== null) · LY {{ $row['ly'] }}% @endif
                            </span>
                        </div>
                        <div style="height: 8px; border-radius: 4px; background: var(--vy-hairline); position: relative;">
                            <div style="height: 100%; width: {{ $row['booked'] }}%; border-radius: 4px; background: {{ $row['tone'] === 'behind' ? '#B4400F' : 'var(--vy-accent)' }};"></div>
                            @if ($row['ly'] !== null)
                                <div style="position: absolute; top: -2px; bottom: -2px; width: 2px; background: var(--vy-ink); left: {{ min(100, $row['ly']) }}%;" title="LY {{ $row['ly'] }}%"></div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="vy-empty">No forward bookings yet.</div>
                @endforelse
            </div>
        </div>

        {{-- ── "Worth a look" — REAL DERIVED observations only (Expedia refused, honest) ──────────── --}}
        <div class="vy-card vy-fade" style="margin-top: 18px;">
            <div class="vy-detail-head" style="margin-bottom: 4px;">
                <h2 class="vy-card-title">Worth a look</h2>
                <span class="vy-eyebrow">What the numbers suggest — decisions stay yours.</span>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-top: 12px;">
                @forelse ($insights as $card)
                    <div class="vy-card" style="padding: 14px;">
                        <div style="font-size: 14px; font-weight: 700; color: var(--vy-ink); margin-bottom: 6px;">{{ $card['title'] }}</div>
                        <p style="font-size: 12.5px; line-height: 1.5; color: var(--vy-muted); margin: 0 0 10px;">{{ $card['body'] }}</p>
                        @if ($card['link'])
                            <a class="vy-arr-link" href="{{ $card['link']['url'] }}">{{ $card['link']['label'] }} →</a>
                        @endif
                    </div>
                @empty
                    <div class="vy-empty" style="grid-column: 1 / -1;">Nothing stands out in this range yet — more booking history will surface real patterns.</div>
                @endforelse
                {{-- HONEST: channel-level pace (the wireframe's Expedia card) needs a connected channel manager;
                     Expedia isn't connected and there's no OTA-brand data, so no euro figure is invented (#39). --}}
                <div class="vy-card" style="padding: 14px; border-style: dashed;">
                    <div style="font-size: 14px; font-weight: 700; color: var(--vy-ink); margin-bottom: 6px;">Channel-level pace</div>
                    <p style="font-size: 12.5px; line-height: 1.5; color: var(--vy-muted); margin: 0 0 10px;">Per-channel gap analysis needs a connected channel manager. Vyrobel won’t estimate a figure it can’t measure.</p>
                    <a class="vy-arr-link" href="{{ route('app.channels') }}">Connect a channel →</a>
                </div>
            </div>
        </div>

        <div class="vy-ins-grid vy-fade" style="margin-top: 18px;">
            {{-- ── By property (Occ/ADR/RevPAR) ───────────────────────────────────────────────────── --}}
            <div class="vy-card vy-card--flush">
                <h2 class="vy-card-title" style="padding: 14px 16px 0;">By property</h2>
                <p style="font-size: 12px; color: var(--vy-muted); margin: 2px 16px 0;">{{ $activeRange['label'] }}.</p>
                <div class="vy-utable-wrap" style="margin-top: 10px;">
                    <table class="vy-utable">
                        <thead><tr><th>Property</th><th style="text-align: right;">Occ</th><th style="text-align: right;">ADR</th><th style="text-align: right;">RevPAR</th></tr></thead>
                        <tbody>
                            @foreach ($byProperty as $row)
                                <tr @if ($row['group']) style="font-weight: 700;" @endif>
                                    <td>{{ $row['name'] }}</td>
                                    <td style="text-align: right;">{{ $row['occ'] }}</td>
                                    <td style="text-align: right;">{{ $row['adr'] }}</td>
                                    <td style="text-align: right;">{{ $row['revpar'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Channel mix — REAL sources only (no OTA brand) ──────────────────────────────────── --}}
            <div class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">Channel mix</h2>
                <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 12px;">Share of room-nights, {{ $activeRange['label'] }}.</p>
                @forelse ($channelMix as $ch)
                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 3px;">
                            <span style="color: var(--vy-ink);">{{ $ch['label'] }}</span>
                            <span class="vy-muted">{{ $ch['pct'] }}%</span>
                        </div>
                        <div style="height: 7px; border-radius: 4px; background: var(--vy-hairline); overflow: hidden;">
                            <div style="height: 100%; width: {{ $ch['pct'] }}%; background: var(--vy-accent);"></div>
                        </div>
                    </div>
                @empty
                    <div class="vy-empty">No bookings in this range yet.</div>
                @endforelse
                <p class="vy-recon" style="margin-top: 8px;">Real booking sources. OTA bookings show as “OTA” — the per-brand split (Booking.com/Airbnb/Expedia) isn’t modelled.</p>
            </div>
        </div>

        {{-- ── Nights that fill first (day-of-week occupancy) ─────────────────────────────────────── --}}
        <div class="vy-card vy-fade" style="margin-top: 18px;">
            <h2 class="vy-card-title" style="margin-bottom: 4px;">Nights that fill first</h2>
            <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 12px;">Average occupancy by night of week.</p>
            @if ($weekday['has_data'])
                <div style="display: flex; gap: 8px;">
                    @foreach ($weekday['weekdays'] as $w)
                        <div style="flex: 1; text-align: center;">
                            <div style="border-radius: 8px; padding: 14px 0; color: {{ $w['pct'] >= 85 ? '#fff' : 'var(--vy-ink)' }}; background: rgba(217,102,46,{{ max(0.08, min(0.95, $w['pct'] / 100)) }}); font-family: 'Sora', sans-serif; font-weight: 700;">{{ $w['pct'] }}</div>
                            <div style="font-size: 11px; color: var(--vy-muted); margin-top: 4px;">{{ $w['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="vy-empty">Not enough stays yet to show a weekly pattern.</div>
            @endif
        </div>

    </x-operator.shell>
</div>
