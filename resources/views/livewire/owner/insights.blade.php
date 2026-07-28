{{--
    Owner Insights (exact-match pass · invariant #61) — the owner's OWN units' performance over the Batch-B metric
    layer, rebuilt to the decoded "Owner Insights" wireframe (2-column). Shell from <x-owner.shell active="insights">.
    OWNER-SCOPED (#28); every figure is a REAL aggregate; a thin range / a missing prior year shows an honest
    empty-or-omitted state — never a fabricated comparison or narrative (#39).

    HONESTY MAP:
      • REAL — KPIs (occupancy / avg nightly / next-90-days / median lead), occupancy-by-month (this year +
        on-the-books), rate-performance (occupancy + ADR per unit), lead-time distribution, day-of-week occupancy
        (+ a REAL derived caption), and "Worth a look" REAL DERIVED observations (decisions stay yours).
      • REAL — the design's "vs last year" deltas are now shown per month WHERE prior-year data genuinely
                exists (GATE 4); omitted (never fabricated) for a month with no prior-year history (#39).
      • REFUSED — the design's "N rate proposals waiting / Review proposals / proposed −8%" (no proposal entity,
        refused in the My Units audit) → an honest "open your Rates" link instead (no fabricated proposal count).
    RAW-BLADE RULE: every `@` follows a space, newline or tag.
--}}
<x-owner.shell active="insights" title="Occupancy & pricing insights" :subtitle="'How your villas are filling, what your rates are earning, and where the headroom is.'" :viewer="$viewer">
    <style>
        .vy-ins-occ { display:grid; grid-template-columns:repeat({{ max(1, count($occupancyByMonth['months'])) }},1fr); gap:5px; align-items:end; height:150px; margin-top:14px; }
        .vy-ins-occ .bar { width:100%; max-width:22px; margin:0 auto; border-radius:5px 5px 0 0; }
        .vy-ins-legend { display:flex; gap:14px; flex-wrap:wrap; font-size:11px; color:var(--vy-muted); }
        .vy-ins-swatch { width:10px; height:10px; border-radius:3px; display:inline-block; vertical-align:middle; margin-right:4px; }
        .vy-ins-rate { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:11px 0; border-top:1px solid rgba(120,70,40,.1); }
        .vy-ins-rate:first-of-type { border-top:none; }
        .vy-ins-look { display:flex; gap:11px; padding:12px 0; border-top:1px solid rgba(120,70,40,.1); }
        .vy-ins-look:first-of-type { border-top:none; }
        .vy-ins-cta { background:var(--vy-grad-dark); color:#fff; }
    </style>

    {{-- Range selector (real GET links over real data) --}}
    <div class="vy-quick vy-fade" style="margin-bottom:18px">
        @foreach ($ranges as $r)
            <a class="vy-pill {{ $r['active'] ? 'is-active' : '' }}" href="{{ route('owner.insights', ['range' => $r['key']]) }}">{{ $r['label'] }}</a>
        @endforeach
    </div>

    @if ($unitsCount === 0)
        <div class="vy-card vy-fade"><div class="vy-empty">No units linked to your account yet.</div></div>
    @else
        <div class="vy-cols">
            {{-- ── LEFT ── --}}
            <div class="vy-stack">
                {{-- KPIs --}}
                <div class="vy-kpi-grid vy-kpi-grid--4 vy-fade" style="animation-delay:.02s">
                    @foreach ($cards as $c)
                        <div class="vy-kpi">
                            <div class="vy-kpi-label">{{ $c['label'] }}</div>
                            <div class="vy-kpi-val">{{ $c['value'] }}</div>
                            <div class="vy-kpi-sub">{{ $c['sub'] }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Occupancy by month — REAL this-year + on-the-books (no fabricated last-year series) --}}
                <section class="vy-card vy-fade" style="animation-delay:.05s">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
                        <div>
                            <h2 class="vy-card-title">Occupancy by month</h2>
                            <div class="vy-line-sub">{{ $activeRange['label'] }} · achieved and on the books</div>
                        </div>
                        <div class="vy-ins-legend">
                            <span><span class="vy-ins-swatch" style="background:var(--vy-accent)"></span>Achieved</span>
                            <span><span class="vy-ins-swatch" style="background:rgba(232,112,58,.35)"></span>On the books</span>
                        </div>
                    </div>
                    @if ($occupancyByMonth['has_data'])
                        <div class="vy-ins-occ">
                            @foreach ($occupancyByMonth['months'] as $m)
                                <div style="display:flex;flex-direction:column;align-items:center;gap:6px;height:100%;justify-content:flex-end" title="{{ $m['label'] }} · {{ $m['occ_pct'] }}%{{ $m['forward'] ? ' (on the books)' : '' }}{{ $m['ly_pct'] !== null ? ' · last year '.$m['ly_pct'].'%' : '' }}">
                                    {{-- REAL "vs last year" delta (GATE 4) — only where prior-year data exists, never fabricated (#39) --}}
                                    @if ($m['delta_pts'] !== null)
                                        <span style="font-size:9.5px;font-weight:700;color:{{ $m['delta_pts'] >= 0 ? '#15803d' : '#b91c1c' }}">{{ $m['delta_pts'] >= 0 ? '▲' : '▼' }}{{ abs($m['delta_pts']) }}</span>
                                    @endif
                                    <div class="bar" style="height:{{ max(2, $m['occ_pct']) }}%;background:{{ $m['forward'] ? 'rgba(232,112,58,.35)' : 'var(--vy-grad)' }}"></div>
                                    <span style="font-size:10px;color:var(--vy-muted)">{{ $m['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="vy-empty" style="margin-top:12px">No occupancy in this period yet.</div>
                    @endif
                    @if ($occupancyByMonth['has_ly'])
                        <div class="vy-line-sub" style="margin-top:10px;font-size:11px">▲/▼ shows the change in points versus the same month last year — real, from your own history.</div>
                    @endif
                </section>

                {{-- Booking pace — the REAL forward (on-the-books) months (no vs-last-year, honest-omitted) --}}
                @php $pace = array_slice(array_values(array_filter($occupancyByMonth['months'], fn ($m) => $m['forward'])), 0, 3); @endphp
                @if ($pace !== [])
                    <section class="vy-card vy-fade" style="animation-delay:.08s">
                        <h2 class="vy-card-title">Booking pace · next months</h2>
                        <div class="vy-line-sub" style="margin-bottom:6px">Occupancy already on the books ahead.</div>
                        @foreach ($pace as $m)
                            <div style="margin-top:10px">
                                <div style="display:flex;justify-content:space-between;font-size:13px">
                                    <span style="font-weight:500">{{ $m['label'] }}</span>
                                    <span class="vy-muted">{{ $m['occ_pct'] }}% on the books</span>
                                </div>
                                <div class="vy-track" style="margin-top:6px"><span style="width:{{ max(2, $m['occ_pct']) }}%"></span></div>
                            </div>
                        @endforeach
                    </section>
                @endif

                {{-- Worth a look — REAL DERIVED observations (decisions stay yours), never fabricated advice --}}
                @if ($insights !== [])
                    <section class="vy-card vy-fade" style="animation-delay:.11s">
                        <h2 class="vy-card-title">Worth a look</h2>
                        <div class="vy-line-sub">What the numbers suggest — decisions stay yours.</div>
                        @foreach ($insights as $i)
                            <div class="vy-ins-look">
                                <span style="flex:none;font-size:18px;line-height:1.4">{{ $i['icon'] }}</span>
                                <div>
                                    <div style="font-weight:600;font-size:13.5px">{{ $i['title'] }}</div>
                                    <div class="vy-line-sub" style="margin-top:2px">{{ $i['body'] }}</div>
                                    <a href="{{ $i['href'] }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600;display:inline-block;margin-top:6px">{{ $i['cta'] }} →</a>
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endif
            </div>

            {{-- ── RIGHT ── --}}
            <div class="vy-stack">
                {{-- Rate performance — REAL occupancy + ADR per unit (no fabricated vs-LY delta) --}}
                <section class="vy-card vy-fade" style="animation-delay:.05s">
                    <h2 class="vy-card-title">Rate performance</h2>
                    <div class="vy-line-sub" style="margin-bottom:4px">Achieved nightly rate, before fees.</div>
                    @forelse ($ratePerformance as $r)
                        <div class="vy-ins-rate">
                            <div>
                                <div style="font-size:13.5px;font-weight:600">{{ $r['name'] }}</div>
                                <div class="vy-line-sub">occupancy {{ $r['occ_pct'] }}%</div>
                            </div>
                            <div style="text-align:right">
                                <div class="vy-mono" style="font-weight:600">{{ $r['adr'] }}</div>
                                <div class="vy-line-sub">ADR</div>
                            </div>
                        </div>
                    @empty
                        <div class="vy-empty" style="margin-top:10px">No units yet.</div>
                    @endforelse
                </section>

                {{-- When guests book — REAL lead-time distribution --}}
                <section class="vy-card vy-fade" style="animation-delay:.08s">
                    <h2 class="vy-card-title">When guests book</h2>
                    <div class="vy-line-sub" style="margin-bottom:6px">Share of bookings by lead time, all units.</div>
                    @if ($leadTime['has_data'])
                        <div style="display:flex;flex-direction:column;gap:12px;margin-top:8px">
                            @foreach ($leadTime['buckets'] as $b)
                                <div>
                                    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:5px">
                                        <span style="font-weight:500">{{ $b['label'] }}</span>
                                        <span class="vy-muted">{{ $b['pct'] }}%</span>
                                    </div>
                                    <div class="vy-track"><span style="width:{{ max(2, $b['pct']) }}%"></span></div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="vy-empty">Not enough booking history yet.</div>
                    @endif
                </section>

                {{-- Nights that fill first — REAL day-of-week occupancy + a REAL derived caption --}}
                <section class="vy-card vy-fade" style="animation-delay:.11s">
                    <h2 class="vy-card-title">Nights that fill first</h2>
                    <div class="vy-line-sub" style="margin-bottom:6px">Average occupancy by night of the week.</div>
                    @if ($weekday['has_data'])
                        <x-charts.bars :bars="$weekday['weekdays']" height="120px" :accentLast="false" />
                        @if ($observation)
                            <div class="vy-fx-note">{{ $observation }}</div>
                        @endif
                    @else
                        <div class="vy-empty">No bookings in this range yet.</div>
                    @endif
                </section>

                {{-- Turn insight into rate — HONEST link to the real Rates page (NO fabricated proposals, #39) --}}
                <section class="vy-card vy-ins-cta vy-fade" style="animation-delay:.14s">
                    <div style="display:flex;align-items:center;gap:8px">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--vy-peach)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4 12 22l-9-9V3h10l7.6 7.6a1.8 1.8 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.4"/></svg>
                        <h2 class="vy-card-title" style="color:#fff">Turn insight into rate</h2>
                    </div>
                    <div style="margin-top:8px;font-size:12.5px;color:rgba(255,255,255,.75);line-height:1.6">Your rates are yours to set. When a pattern here is worth acting on, open your Rates page and adjust — the change flows straight to your live availability.</div>
                    <a href="{{ route('owner.rates') }}" class="vy-btn vy-btn--ghost" style="margin-top:12px;background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.3);color:#fff">Open your Rates</a>
                </section>
            </div>
        </div>
    @endif
</x-owner.shell>
