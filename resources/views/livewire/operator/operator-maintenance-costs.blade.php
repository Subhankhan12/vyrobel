{{-- Operator MAINTENANCE COSTS (#89 exact-match) — every figure REAL/derived from resolved work orders
     (MaintenanceCostService). #89 makes the labor/parts split real (labor+parts=total, #17); the billable
     totals reconcile to what the owner is billed. Real exports (PDF/CSV/XLSX) + email-to-self.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div x-data="{ exOpen: false }">
    <x-operator.shell active="maintenance" :title="__('livewire/operator/operator-maintenance-costs.page_title')" :subtitle="__('livewire/operator/operator-maintenance-costs.page_subtitle')" :viewer="$viewer">

        @if (session('mt_notice')) <div class="vy-flash is-ok vy-fade">{{ session('mt_notice') }}</div> @endif
        @if (session('mt_error')) <div class="vy-flash is-err vy-fade">{{ session('mt_error') }}</div> @endif

        {{-- Header row --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                <a class="vy-convo-back" href="{{ route('app.maintenance') }}">{{ __('livewire/operator/operator-maintenance-costs.back_to_board') }}</a>
                {{-- PERIOD SELECTOR (auth/isolation pass rider) — the report was ALL-TIME with no way to narrow
                     it. Plain GET links, so every switch re-runs the property-context middleware (a Livewire
                     action would bypass it). The EXPORT links below carry the same `period`, so the PDF/CSV
                     always covers the window on screen (#17). --}}
                <span style="display:inline-flex;gap:4px">
                    @foreach ($period['options'] as $opt)
                        <a href="{{ route('app.maintenance.costs', ['period' => $opt]) }}"
                           style="font-size:12px;padding:4px 10px;border-radius:999px;text-decoration:none;{{ $period['active'] === $opt ? 'background:var(--vy-accent);color:#fff;font-weight:600' : 'color:var(--vy-muted);border:1px solid var(--vy-hairline)' }}">
                            {{ match ($opt) {
                                'all' => __('livewire/operator/operator-maintenance-costs.period_all'),
                                '3m' => __('livewire/operator/operator-maintenance-costs.period_3m'),
                                '6m' => __('livewire/operator/operator-maintenance-costs.period_6m'),
                                '12m' => __('livewire/operator/operator-maintenance-costs.period_12m'),
                                default => strtoupper($opt),
                            } }}
                        </a>
                    @endforeach
                </span>
            </div>
            <div class="vy-right" style="position: relative;">
                <button type="button" class="vy-btn vy-btn--accent" @click="exOpen = !exOpen">{{ __('livewire/operator/operator-maintenance-costs.export_button') }}</button>
                <div x-show="exOpen" x-cloak @click.outside="exOpen = false"
                     style="position: absolute; right: 0; top: 42px; z-index: 30; width: 250px; background: var(--vy-glass); border: 1px solid var(--vy-glass-border); border-radius: 12px; box-shadow: 0 14px 40px rgba(150,90,50,.16); padding: 6px;">
                    <a href="{{ route('app.maintenance.costs.pdf', ['period' => $period['active']]) }}" class="vy-menu-item" style="display: block; padding: 9px 12px; border-radius: 8px; text-decoration: none; color: var(--vy-ink); font-size: 13px;">{{ __('livewire/operator/operator-maintenance-costs.export_pdf') }} <span style="color: var(--vy-faint); font-size: 11px;">{{ __('livewire/operator/operator-maintenance-costs.export_pdf_note') }}</span></a>
                    <a href="{{ route('app.maintenance.costs.csv', ['period' => $period['active']]) }}" class="vy-menu-item" style="display: block; padding: 9px 12px; border-radius: 8px; text-decoration: none; color: var(--vy-ink); font-size: 13px;">{{ __('livewire/operator/operator-maintenance-costs.export_csv') }} <span style="color: var(--vy-faint); font-size: 11px;">{{ __('livewire/operator/operator-maintenance-costs.export_csv_note') }}</span></a>
                    {{-- D12b-1 — real now (it needed a spreadsheet library, which D4-1 added). ⚠️ It carries the
                         SAME `period` as the PDF/CSV above, so the workbook exports what the page is showing. --}}
                    <a href="{{ route('app.maintenance.costs.xlsx', ['period' => $period['active']]) }}" class="vy-menu-item" style="display: block; padding: 9px 12px; border-radius: 8px; text-decoration: none; color: var(--vy-ink); font-size: 13px;">{{ __('livewire/operator/operator-maintenance-costs.export_xlsx') }} <span style="color: var(--vy-faint); font-size: 11px;">{{ __('livewire/operator/operator-maintenance-costs.export_xlsx_note') }}</span></a>
                    <form method="POST" action="{{ route('app.maintenance.costs.email') }}" style="border-top: 1px solid var(--vy-hairline); margin-top: 4px; padding-top: 4px;">
                        @csrf
                        <button type="submit" class="vy-menu-item" style="display: block; width: 100%; text-align: left; padding: 9px 12px; border-radius: 8px; border: none; background: none; cursor: pointer; color: var(--vy-ink); font-size: 13px;">{{ __('livewire/operator/operator-maintenance-costs.export_email') }} <span style="color: var(--vy-faint); font-size: 11px;">{{ __('livewire/operator/operator-maintenance-costs.export_email_note') }}</span></button>
                    </form>
                </div>
            </div>
        </div>

        @unless ($hasData)
            <div class="vy-card vy-fade"><div class="vy-empty">{{ __('livewire/operator/operator-maintenance-costs.empty_state') }}</div></div>
        @else
            {{-- KPIs --}}
            <div class="vy-dash-kpis vy-dash-kpis--4 vy-fade">
                <div class="vy-dash-kpi"><div class="vy-dk-label">{{ __('livewire/operator/operator-maintenance-costs.kpi_total_spend') }}</div><div class="vy-dk-val">{{ $money($report['total_minor']) }}</div></div>
                <div class="vy-dash-kpi"><div class="vy-dk-label">{{ __('livewire/operator/operator-maintenance-costs.kpi_orders_closed') }}</div><div class="vy-dk-val">{{ $report['orders_closed'] }}</div></div>
                <div class="vy-dash-kpi"><div class="vy-dk-label">{{ __('livewire/operator/operator-maintenance-costs.kpi_avg_per_order') }}</div><div class="vy-dk-val">{{ $money($report['avg_minor']) }}</div></div>
                <div class="vy-dash-kpi"><div class="vy-dk-label">{{ __('livewire/operator/operator-maintenance-costs.kpi_parts_on_order') }}</div><div class="vy-dk-val">{{ $report['parts_on_order'] }}</div><div class="vy-dk-sub">{{ __('livewire/operator/operator-maintenance-costs.kpi_parts_on_order_sub') }}</div></div>
            </div>

            <div class="vy-ins-grid vy-fade" style="margin-top: 18px;">
                <div class="vy-stack">
                    {{-- Spend by category --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 12px;">{{ __('livewire/operator/operator-maintenance-costs.spend_by_category') }}</h2>
                        @php $catMax = collect($report['by_category'])->max('total_minor') ?: 1; @endphp
                        @foreach ($report['by_category'] as $cat)
                            <div style="margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 3px;">
                                    <span style="color: var(--vy-ink);">{{ $categoryLabel($cat['category']) }} <span class="vy-muted">· {{ $cat['count'] }}</span></span>
                                    <span class="vy-u-code">{{ $money($cat['total_minor']) }}</span>
                                </div>
                                <div style="height: 7px; border-radius: 4px; background: var(--vy-hairline); overflow: hidden;"><div style="height: 100%; width: {{ (int) round($cat['total_minor'] / $catMax * 100) }}%; background: var(--vy-accent);"></div></div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Billable work orders --}}
                    <div class="vy-card vy-card--flush">
                        <h2 class="vy-card-title" style="padding: 14px 16px 0;">{{ __('livewire/operator/operator-maintenance-costs.billable_heading') }}</h2>
                        <div class="vy-utable-wrap" style="margin-top: 8px;">
                            <table class="vy-utable">
                                <thead><tr><th>{{ __('common.date') }}</th><th>{{ __('livewire/operator/operator-maintenance-costs.col_work_order') }}</th><th style="text-align: right;">{{ __('livewire/operator/operator-maintenance-costs.col_labor') }}</th><th style="text-align: right;">{{ __('livewire/operator/operator-maintenance-costs.col_parts') }}</th><th style="text-align: right;">{{ __('common.total') }}</th></tr></thead>
                                <tbody>
                                    @foreach ($report['billable'] as $row)
                                        <tr>
                                            <td class="vy-u-sub">{{ $row['date'] }}</td>
                                            <td><a class="vy-arr-link" href="{{ route('app.maintenance.show', $row['id']) }}">{{ $row['title'] }}</a><div class="vy-u-sub">{{ $row['unit'] }} · {{ $categoryLabel($row['category']) }}</div></td>
                                            <td style="text-align: right;">{{ $money($row['labor_minor']) }}</td>
                                            <td style="text-align: right;">{{ $money($row['parts_minor']) }}</td>
                                            <td style="text-align: right;"><span class="vy-u-code">{{ $money($row['total_minor']) }}</span></td>
                                        </tr>
                                    @endforeach
                                    <tr style="border-top: 2px solid var(--vy-ink); font-weight: 700;">
                                        <td colspan="2">{{ __('common.total') }}</td>
                                        <td style="text-align: right;">{{ $money($report['labor_minor']) }}</td>
                                        <td style="text-align: right;">{{ $money($report['parts_minor']) }}</td>
                                        <td style="text-align: right; color: var(--vy-accent);">{{ $money($report['total_minor']) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        {{-- ⚠️ THE PREVIEW SAYS IT IS A PREVIEW. This page legitimately caps its detail at 60 rows
                             — a screen should not render thousands — but the Total row above sums EVERY resolved
                             ticket in the period, so above 60 the two silently disagreed and a reader summing the
                             rows found a discrepancy that looked like a bug. The downloads were made complete
                             instead; a page cannot be, so it states the gap rather than hiding it. --}}
                        @if (($report['billable_total'] ?? 0) > count($report['billable']))
                            <div class="vy-recon" style="padding: 12px 16px;">{{ __('livewire/operator/operator-maintenance-costs.preview_note', ['shown' => count($report['billable']), 'total' => $report['billable_total']]) }}</div>
                        @endif
                        <div class="vy-recon" style="padding: 12px 16px;">{{ __('livewire/operator/operator-maintenance-costs.reconcile_note') }}</div>
                    </div>
                </div>

                <div class="vy-stack">
                    {{-- Dark spend hero --}}
                    <div class="vy-tonight" style="padding: 20px;">
                        <div class="vy-dk-label" style="color: rgba(255,255,255,.7);">{{ __('livewire/operator/operator-maintenance-costs.hero_label') }}</div>
                        <div style="font-family: 'Sora', sans-serif; font-weight: 700; color: #fff; font-size: 30px;">{{ $money($report['total_minor']) }}</div>
                        <div style="font-size: 12.5px; color: rgba(255,255,255,.7); margin-top: 4px;">{{ trans_choice('livewire/operator/operator-maintenance-costs.hero_sub', $report['orders_closed'], ['count' => $report['orders_closed'], 'avg' => $money($report['avg_minor'])]) }}</div>
                        <div style="border-top: 1px solid rgba(255,255,255,.14); margin-top: 14px; padding-top: 12px; font-size: 12px; color: rgba(255,255,255,.85);">
                            <div>{{ __('livewire/operator/operator-maintenance-costs.owner_billed', ['amount' => $money($report['owner_units_minor'])]) }}</div>
                            <div style="color: rgba(255,255,255,.6);">{{ __('livewire/operator/operator-maintenance-costs.operator_borne', ['amount' => $money($report['operator_units_minor'])]) }}</div>
                        </div>
                    </div>

                    {{-- Labor vs parts --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 10px;">{{ __('livewire/operator/operator-maintenance-costs.labour_vs_parts') }}</h2>
                        @php $lp = max(1, $report['labor_minor'] + $report['parts_minor']); $laborPct = (int) round($report['labor_minor'] / $lp * 100); @endphp
                        <div style="display: flex; height: 12px; border-radius: 6px; overflow: hidden;">
                            <div style="width: {{ $laborPct }}%; background: var(--vy-accent);"></div>
                            <div style="width: {{ 100 - $laborPct }}%; background: #E0A63A;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px; margin-top: 6px;">
                            <span>{{ __('livewire/operator/operator-maintenance-costs.labour_pct', ['pct' => $laborPct]) }} <span class="vy-muted">{{ $money($report['labor_minor']) }}</span></span>
                            <span>{{ __('livewire/operator/operator-maintenance-costs.parts_pct', ['pct' => 100 - $laborPct]) }} <span class="vy-muted">{{ $money($report['parts_minor']) }}</span></span>
                        </div>
                    </div>

                    {{-- 6-month trend --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 12px;">{{ __('livewire/operator/operator-maintenance-costs.spend_by_month') }}</h2>
                        {{-- Rendered through the SHARED <x-charts.bars>, not a second hand-rolled bar chart.
                             Two reasons beyond consistency: the local copy printed its figure only in a
                             `title` tooltip (invisible to anyone not hovering, and to touch entirely), and it
                             floored every bar at `max(3, …)` — so a genuinely €0 month drew a visible stub
                             that reads as real spend. The shared component draws nothing for a true zero,
                             which is the #39 fix already recorded in its own comments. --}}
                        @php
                            $months = array_reverse($report['by_month']);
                            $mMax = collect($months)->max('total_minor') ?: 1;
                            $monthBars = array_map(fn (array $m): array => [
                                'label' => $monthLabel($m['month']),
                                'display' => $money($m['total_minor']),
                                'pct' => (int) round($m['total_minor'] / $mMax * 100),
                            ], $months);
                        @endphp
                        @if ($monthBars === [])
                            <div class="vy-empty">{{ __('livewire/operator/operator-maintenance-costs.no_monthly_history') }}</div>
                        @else
                            <x-charts.bars :bars="$monthBars" height="100px" />
                        @endif
                    </div>

                    {{-- Top units --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 10px;">{{ __('livewire/operator/operator-maintenance-costs.top_units') }}</h2>
                        @php $uMax = collect($report['top_units'])->max('total_minor') ?: 1; @endphp
                        @foreach ($report['top_units'] as $u)
                            <div style="margin-bottom: 8px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 3px;"><span>{{ $u['code'] }}</span><span class="vy-u-code">{{ $money($u['total_minor']) }}</span></div>
                                <div style="height: 6px; border-radius: 4px; background: var(--vy-hairline); overflow: hidden;"><div style="height: 100%; width: {{ (int) round($u['total_minor'] / $uMax * 100) }}%; background: var(--vy-accent);"></div></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endunless

    </x-operator.shell>
</div>

@once <style>[x-cloak] { display: none !important; }</style> @endonce
