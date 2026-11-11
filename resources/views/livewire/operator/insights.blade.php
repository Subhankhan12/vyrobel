{{-- Operator INSIGHTS (#88 exact-match) — portfolio performance over the REAL SG1 metric layer, scoped by the
     property context + a real range. EVERY figure real/derived (PerformanceMetrics/BookingCadence); vs-LY shown
     only where prior-year data exists; the "Worth a look" cards are REAL DERIVED observations shown only when
     the data supports them; the Expedia/brand fabrications are REFUSED (honest note). "Decisions stay yours."
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div class="vy-ins">
    <x-operator.shell active="insights" :title="__('livewire/operator/insights.page_title')" :subtitle="$scopeLabel . ' · ' . $activeRange['label']" :viewer="$viewer">

        {{-- Range presets (real GET over real data) --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left" style="flex-wrap: wrap; gap: 6px;">
                @foreach ($ranges as $r)
                    <a class="vy-pill @if ($r['active']) is-on @endif" href="?range={{ $r['key'] }}">{{ $r['label'] }}</a>
                @endforeach

                {{-- D3-1 — a CUSTOM window beside the presets. A plain GET form, like every preset link, so the
                     range rides the URL and is shareable/bookmarkable — and so it works without JavaScript,
                     matching how the presets already behave.
                     ⚠️ Everything downstream takes only start/end, so this feeds the SAME metric path, the
                     SAME span-shifted last-year comparison and therefore D3-4's coverage guard. --}}
                <form method="GET" class="vy-ins-range">
                    <x-date-field name="from" id="ins-from" :value="$customFrom" :label="__('livewire/operator/insights.range_start_label')" :placeholder="__('livewire/operator/insights.range_start_placeholder')" />
                    <span class="vy-ins-range-sep" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                    <x-date-field name="to" id="ins-to" :value="$customTo" :label="__('livewire/operator/insights.range_end_label')" :placeholder="__('livewire/operator/insights.range_end_placeholder')" />
                    <button type="submit" class="vy-pill @if ($activeRange['key'] === 'custom') is-on @endif">{{ __('common.apply') }}</button>
                </form>
            </div>
            <div class="vy-right"><span class="vy-topbar-sub" style="font-size: 12px;">{{ __('livewire/operator/insights.switch_property_hint') }}</span></div>
        </div>

        {{-- D3-1 — ⚠️ AN UNUSABLE WINDOW SAYS SO. Falling back to a preset silently would answer a different
             question than the one the fields still show. The reasons are the real ones the resolver enforces. --}}
        @if ($customRejected)
            <div class="vy-flash vy-fade" role="status">{{ trans_choice('livewire/operator/insights.custom_rejected', $customMaxMonths, ['count' => $customMaxMonths, 'range' => $activeRange['label']]) }}</div>
        @endif

        @if ($multiCurrency)
            <div class="vy-flash vy-fade" role="status">{{ __('livewire/operator/insights.multi_currency', ['range' => $activeRange['label']]) }}</div>
        @endif

        {{-- ── KPI cards (4) with real vs-LY deltas ───────────────────────────────────────────────── --}}
        <div class="vy-dash-kpis vy-dash-kpis--4 vy-fade">
            @foreach ($cards as $c)
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ $c['label'] }}</div>
                    <div style="display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;">
                        <div class="vy-dk-val">{{ $c['value'] }}</div>
                        @if ($c['delta'] !== null)
                            {{-- The design sets the delta as a pill beside the figure. The up/down colour pair
                                 is kept (the design has one styling; dropping it would lose the direction). --}}
                            <span class="vy-ins-delta {{ $c['delta_dir'] === 'down' ? 'is-down' : '' }}">{{ $c['delta'] }}</span>
                        @endif
                    </div>
                    <div class="vy-dk-sub">{{ $c['sub'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- The design lays this page out as a main column with a 300px right rail. --}}
        <div class="vy-ins-cols vy-fade" style="margin-top: 18px;">
          <div class="vy-ins-main">
            {{-- ── Occupancy by month (this-year + on-the-books + real LY) ─────────────────────────── --}}
            <div class="vy-card">
                <div class="vy-detail-head" style="margin-bottom: 4px;">
                    <h2 class="vy-card-title">{{ __('livewire/operator/insights.occupancy_by_month') }}</h2>
                    <div style="display: flex; align-items: center; gap: 12px; font-size: 11px; color: var(--vy-muted);">
                        <span><span style="display: inline-block; width: 9px; height: 9px; border-radius: 2px; background: var(--vy-accent);"></span> {{ __('livewire/operator/insights.legend_this_year') }}</span>
                        <span><span style="display: inline-block; width: 9px; height: 9px; border-radius: 2px; background: repeating-linear-gradient(45deg, var(--vy-accent), var(--vy-accent) 2px, #fff 2px, #fff 4px);"></span> {{ __('livewire/operator/insights.legend_on_the_books') }}</span>
                        @if ($occByMonth['has_ly'])
                            <span><span style="display: inline-block; width: 9px; height: 9px; border-radius: 2px; background: rgba(120,70,40,.22);"></span> {{ __('livewire/operator/insights.legend_last_year') }}</span>
                        @endif
                    </div>
                </div>
                @if ($occByMonth['enough'])
                    {{-- Axis is a true 0–100%: occupancy IS a percentage, so the bar height is the figure
                         itself, never rescaled to the series max. A genuinely 0% month therefore draws
                         NOTHING — the old `max(2, …)` floor put a visible stub under a "0%" label (#39). --}}
                    <div class="vy-ins-chart">
                        @foreach ($occByMonth['months'] as $m)
                            <div class="vy-ins-chart-col">
                                <div class="vy-ins-chart-plot">
                                    @if ($m['ly'] !== null)
                                        <div class="vy-ins-bar is-ly" title="{{ __('livewire/operator/insights.ly_percent', ['percent' => $m['ly']]) }}" style="height: {{ $m['ly'] }}%;"></div>
                                    @endif
                                    <div class="vy-ins-bar {{ $m['on_books'] ? 'is-otb' : 'is-ty' }}" title="{{ $m['occ'] }}%" style="height: {{ $m['occ'] }}%;"></div>
                                </div>
                                <div class="vy-ins-chart-lbl">{{ $m['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="vy-empty">{{ __('livewire/operator/insights.not_enough_data') }}</div>
                @endif
            </div>

            {{-- ── Pace by property (forward 30d vs LY) ────────────────────────────────────────────── --}}
            <div class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/insights.pace_by_property') }}</h2>
                <p style="font-size: 12px; color: var(--vy-muted); margin: 0 0 12px;">{{ __('livewire/operator/insights.pace_intro') }}</p>
                @forelse ($pace as $row)
                    <div style="margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                            <span style="font-size: 13px; color: var(--vy-ink);">{{ $row['name'] }}</span>
                            @php $tone = ['ahead' => [__('livewire/operator/insights.tone_ahead'), 'var(--vy-accent)'], 'behind' => [__('livewire/operator/insights.tone_behind'), '#8F3110'], 'onpace' => [__('livewire/operator/insights.tone_on_pace'), 'var(--vy-muted)'], 'flat' => ['—', 'var(--vy-faint)']][$row['tone']]; @endphp
                            <span style="font-size: 11.5px; color: var(--vy-muted);">
                                <span style="font-weight: 700; color: {{ $tone[1] }};">{{ $tone[0] }}</span>
                                · @if ($row['ly'] !== null) {{ __('livewire/operator/insights.pace_booked_with_ly', ['percent' => $row['booked'], 'ly_percent' => $row['ly']]) }} @else {{ __('livewire/operator/insights.pace_booked', ['percent' => $row['booked']]) }} @endif
                            </span>
                        </div>
                        <div style="height: 8px; border-radius: 4px; background: var(--vy-hairline); position: relative;">
                            <div style="height: 100%; width: {{ $row['booked'] }}%; border-radius: 4px; background: {{ $row['tone'] === 'behind' ? '#B4400F' : 'var(--vy-accent)' }};"></div>
                            @if ($row['ly'] !== null)
                                <div style="position: absolute; top: -2px; bottom: -2px; width: 2px; background: var(--vy-ink); left: {{ min(100, $row['ly']) }}%;" title="{{ __('livewire/operator/insights.ly_percent', ['percent' => $row['ly']]) }}"></div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="vy-empty">{{ __('livewire/operator/insights.no_forward_bookings') }}</div>
                @endforelse
            </div>

            {{-- ── "Worth a look" — REAL DERIVED observations only (Expedia refused, honest) ──────── --}}
            <div class="vy-card">
                <div class="vy-detail-head" style="margin-bottom: 2px;">
                    <h2 class="vy-card-title">{{ __('livewire/operator/insights.worth_a_look') }}</h2>
                    <span class="vy-eyebrow">{{ __('livewire/operator/insights.worth_a_look_eyebrow') }}</span>
                </div>
                <div class="vy-ins-obs">
                    @forelse ($insights as $card)
                        <div class="vy-ins-ob">
                            <span class="vy-ins-ob-ico" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M17 7h4v4"/></svg>
                            </span>
                            <div style="min-width: 0;">
                                <div class="vy-ins-ob-t">{{ $card['title'] }}</div>
                                <p class="vy-ins-ob-b">{{ $card['body'] }}</p>
                                @if ($card['link'])
                                    <a class="vy-ins-ob-a" href="{{ $card['link']['url'] }}">{{ $card['link']['label'] }} <span class="vy-flip-rtl" aria-hidden="true">→</span></a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="vy-empty">{{ __('livewire/operator/insights.worth_a_look_empty') }}</div>
                    @endforelse
                    {{-- HONEST: channel-level pace (the wireframe's Expedia card) needs a connected channel manager;
                         Expedia isn't connected and there's no OTA-brand data, so no euro figure is invented (#39). --}}
                    <div class="vy-ins-ob is-deferred">
                        <span class="vy-ins-ob-ico" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.5 2.4 4 5.6 4 9s-1.5 6.6-4 9c-2.5-2.4-4-5.6-4-9s1.5-6.6 4-9z"/></svg>
                        </span>
                        <div style="min-width: 0;">
                            <div class="vy-ins-ob-t">{{ __('livewire/operator/insights.channel_pace_title') }}</div>
                            <p class="vy-ins-ob-b">{{ __('livewire/operator/insights.channel_pace_body') }}</p>
                            <a class="vy-ins-ob-a" href="{{ route('app.channels') }}">{{ __('livewire/operator/insights.connect_a_channel') }}</a>
                        </div>
                    </div>
                </div>
            </div>
          </div>

          <div class="vy-ins-rail">
            {{-- ── By property (Occ/ADR/RevPAR) — the design's compact rail table ──────────────────── --}}
            <div class="vy-card">
                <h2 class="vy-card-title">{{ __('livewire/operator/insights.by_property') }}</h2>
                <p class="vy-ins-sub">{{ $activeRange['label'] }}.</p>
                <div class="vy-ins-bp">
                    <span></span>
                    <span class="vy-ins-bp-h">{{ __('livewire/operator/insights.col_occ') }}</span>
                    <span class="vy-ins-bp-h">{{ __('livewire/operator/insights.col_adr') }}</span>
                    <span class="vy-ins-bp-h">{{ __('livewire/operator/insights.col_revpar') }}</span>
                    @foreach ($byProperty as $row)
                        <span class="vy-ins-bp-c is-name @if ($row['group']) is-group @endif">{{ $row['name'] }}</span>
                        <span class="vy-ins-bp-c @if ($row['group']) is-group @endif">{{ $row['occ'] }}</span>
                        <span class="vy-ins-bp-c @if ($row['group']) is-group @endif">{{ $row['adr'] }}</span>
                        <span class="vy-ins-bp-c @if ($row['group']) is-group @endif">{{ $row['revpar'] }}</span>
                    @endforeach
                </div>
            </div>

            {{-- ── Channel mix — REAL sources only (no OTA brand) ──────────────────────────────────── --}}
            <div class="vy-card">
                <h2 class="vy-card-title">{{ __('livewire/operator/insights.channel_mix') }}</h2>
                <p class="vy-ins-sub">{{ __('livewire/operator/insights.channel_mix_sub', ['range' => $activeRange['label']]) }}</p>
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
                    <div class="vy-empty">{{ __('livewire/operator/insights.no_bookings') }}</div>
                @endforelse
                <p class="vy-recon" style="margin-top: 8px;">{{ __('livewire/operator/insights.channel_mix_note') }}</p>
            </div>

            {{-- ── Nights that fill first (day-of-week occupancy) ──────────────────────────────────── --}}
            <div class="vy-card">
                <h2 class="vy-card-title">{{ __('livewire/operator/insights.nights_fill_first') }}</h2>
                <p class="vy-ins-sub">{{ __('livewire/operator/insights.nights_fill_first_sub') }}</p>
                @if ($weekday['has_data'])
                    {{-- The tile shade tracks the value, but the value is PRINTED in the tile — the shade is
                         never the only carrier of the reading (WCAG 1.4.1). --}}
                    <div class="vy-ins-nights">
                        @foreach ($weekday['weekdays'] as $w)
                            <div class="vy-ins-night">
                                <div class="vy-ins-night-tile" style="color: {{ $w['pct'] >= 85 ? '#fff' : 'var(--vy-ink)' }}; background: rgba(217,102,46,{{ max(0.08, min(0.95, $w['pct'] / 100)) }});">{{ $w['pct'] }}%</div>
                                <div class="vy-ins-night-lbl">{{ $w['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="vy-empty">{{ __('livewire/operator/insights.no_weekly_pattern') }}</div>
                @endif
            </div>
          </div>
        </div>

    </x-operator.shell>
</div>
