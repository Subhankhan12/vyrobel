{{--
    Owner Statement (exact-match pass) — the certified owner-statement projection, rebuilt to the decoded
    "Owner Statement" wireframe and wired to LIVE, owner-scoped data. Shell (sidebar + topbar) from <x-owner.shell>.

    HONESTY MAP (also on App\Livewire\Owner\OwnerStatementView; rule locked in DECISIONS.md):
      • REAL      — net-payable hero, the breakdown (the actual owner_statement_lines, reconciling to the cent),
                    the month switcher, statement history + net total, the by-unit split (Σ per-unit net == the
                    statement net, #17), the occupancy card + mini bar chart (shared OwnerOccupancyTrend), and the
                    masked payout IBAN (CAP_PAYOUT only).
      • DERIVED   — the "% of gross" + "N nights · M units" sublabels, computed from the statement's own real figures.
      • REAL PDFs — statement · booking-summary · remittance-advice + the statement-history CSV/PDF, all reconciling
                    and all CAP_EXPORTS-gated (co-owner + accountant); the remittance offered only once a payout exists.
    There is deliberately NO "payment / card-processing fee" line and NO estimated payout DATE — the engine computes
    no such component and there is no scheduled-date field, so neither is ever faked (#39).

    Page-scoped CSS (.vy-st-*) lives in THIS file, NOT the shared owner layout — its class names/comments must not
    leak into every owner page's <style>, where owner-isolation tests assert the ABSENCE of certain words.
--}}
<x-owner.shell active="statements" title="Statement" :viewer="$viewer">
    <style>
        /* Month "jump to" popover (the header switcher). */
        .vy-st-mwrap { position: relative; }
        .vy-st-mbtn { cursor: pointer; }
        .vy-st-mbtn svg.vy-st-mchev { width: 14px; height: 14px; color: var(--vy-faint); transition: transform .18s ease; }
        .vy-st-mbtn[aria-expanded="true"] svg.vy-st-mchev { transform: rotate(180deg); }
        .vy-st-menu { position: absolute; top: calc(100% + 8px); left: 0; z-index: 40; min-width: 260px; max-height: 320px; overflow-y: auto; padding: 6px; border-radius: var(--vy-r-md); background: rgba(255, 252, 248, 0.98); border: 1px solid rgba(255, 255, 255, 0.85); box-shadow: 0 18px 48px rgba(60, 30, 10, .2); backdrop-filter: blur(24px) saturate(1.15); -webkit-backdrop-filter: blur(24px) saturate(1.15); }
        .vy-st-menu-h { padding: 7px 10px 5px; font-size: 9.5px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #B0A49C; }
        .vy-st-menu-item { display: flex; align-items: center; justify-content: space-between; gap: 16px; width: 100%; padding: 9px 11px; border-radius: var(--vy-r-sm); text-decoration: none; color: var(--vy-ink); transition: background .12s ease; }
        .vy-st-menu-item:hover { background: rgba(120, 70, 40, .06); }
        .vy-st-menu-item.is-current { background: rgba(232, 112, 58, .1); }
        .vy-st-menu-m { font-size: 13px; font-weight: 500; }
        .vy-st-menu-v { font-size: 12.5px; font-weight: 600; color: var(--vy-accent); font-variant-numeric: tabular-nums; }

        /* Occupancy card mini bar chart (the wireframe's Mar→Sep bars). */
        .vy-st-occ-top { display: flex; align-items: baseline; gap: 9px; margin-top: 8px; }
        .vy-st-occ-pct { font-family: var(--vy-display); font-weight: 700; font-size: 30px; letter-spacing: -.02em; }
        .vy-st-occ-delta { font-size: 13px; font-weight: 600; }
        .vy-st-occ-delta.is-up { color: var(--vy-accent); }
        .vy-st-occ-delta.is-down { color: var(--vy-amber); }
        .vy-st-occ-bars { display: flex; align-items: flex-end; gap: 5px; height: 46px; margin-top: 14px; }
        .vy-st-occ-bar { flex: 1; min-height: 3px; border-radius: 4px; background: var(--vy-grad-peach); }
        .vy-st-occ-bar.is-last { background: var(--vy-grad); }
        .vy-st-occ-axis { display: flex; align-items: center; justify-content: space-between; margin-top: 7px; font-size: 10.5px; color: var(--vy-faint); }

        /* Masked IBAN chip on the dark rail (reuses the Overview .vy-ov-iban treatment). */
        .vy-st-dl { margin-top: 2px; }
    </style>

    {{-- Period selector — prev/next by period + a "jump to month" popover over the owner's real statements. --}}
    <div class="vy-period" style="margin-bottom:22px">
        @if ($nav['prev'])
            <a class="vy-period-btn" href="{{ route('owner.statements.show', $nav['prev']['id']) }}" title="{{ $nav['prev']['label'] }}" aria-label="Previous period, {{ $nav['prev']['label'] }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
            </a>
        @else
            <span class="vy-period-btn" aria-disabled="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></span>
        @endif

        <div class="vy-st-mwrap" x-data="{ mopen: false }" @keydown.escape.window="mopen = false">
            <button type="button" class="vy-period-cur vy-st-mbtn" @click="mopen = !mopen" :aria-expanded="mopen ? 'true' : 'false'" aria-haspopup="true" aria-label="Jump to another month">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg>
                {{ $statement['period_label'] }}
                <svg class="vy-st-mchev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div x-cloak x-show="mopen" x-transition.opacity.duration.150ms @click.outside="mopen = false" class="vy-st-menu" role="menu" aria-label="Jump to month">
                <div class="vy-st-menu-h">Jump to month</div>
                @foreach ($monthOptions as $opt)
                    <a class="vy-st-menu-item {{ $opt['is_current'] ? 'is-current' : '' }}" href="{{ route('owner.statements.show', $opt['id']) }}" role="menuitem">
                        <span class="vy-st-menu-m">{{ $opt['label'] }}</span>
                        <span class="vy-st-menu-v">{{ $opt['net_display'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        @if ($nav['next'])
            <a class="vy-period-btn" href="{{ route('owner.statements.show', $nav['next']['id']) }}" title="{{ $nav['next']['label'] }}" aria-label="Next period, {{ $nav['next']['label'] }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
            </a>
        @else
            <span class="vy-period-btn" aria-disabled="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></span>
        @endif
    </div>

    <div class="vy-cols">
        {{-- ── Left: statement + breakdown ── --}}
        <div class="vy-stack">
            {{-- Hero — plain glass (wireframe), real net payable --}}
            <section class="vy-card vy-fade" style="animation-delay:.02s">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
                    <span class="vy-eyebrow">{{ strtoupper($statement['period_label']) }} · {{ $unitsCount }} {{ \Illuminate\Support\Str::plural('unit', $unitsCount) }}</span>
                    <span class="vy-status vy-status--{{ $statement['status_meta']['tone'] }}">
                        <span class="vy-dot"></span>{{ $statement['status_meta']['label'] }}
                    </span>
                </div>
                <div class="vy-hero-sub" style="margin-top:14px">Net payable</div>
                <div class="vy-hero-figure" data-countup="{{ $statement['net_payable_minor'] }}" data-symbol="{{ $statement['currency_symbol'] }}">{{ $statement['net_payable_display'] }}</div>
                @if ($statement['fx_rate'] !== null)
                    <div class="vy-fx-note">Converted from {{ $statement['source_currency'] }} at {{ rtrim(rtrim($statement['fx_rate'], '0'), '.') }} @if ($statement['fx_source'])· {{ $statement['fx_source'] }}@endif</div>
                @endif
                @if ($statement['has_unverified'])
                    <div class="vy-fx-note"><span class="vy-provisional">Provisional</span> Some lines are pending channel reconciliation.</div>
                @endif
            </section>

            {{-- Breakdown — the REAL statement lines (sum to the cent); gross carries a real "N nights · M units" sub --}}
            <section class="vy-card vy-fade" style="animation-delay:.08s">
                <h2 class="vy-card-title" style="margin-bottom:6px">Breakdown</h2>
                @foreach ($lines as $line)
                    <div class="vy-line {{ $line['is_net'] ? 'vy-line--net' : '' }}">
                        <div>
                            <div class="vy-line-label">
                                {{ $line['description'] }}
                                @if ($line['unverified'])<span class="vy-provisional" style="margin-left:6px">Provisional</span>@endif
                            </div>
                            @if ($line['sublabel'])<div class="vy-line-sub">{{ $line['sublabel'] }}</div>@endif
                        </div>
                        <div class="vy-line-amt {{ $line['is_negative'] ? 'is-neg' : '' }}">{{ $line['amount_display'] }}</div>
                    </div>
                @endforeach
            </section>
        </div>

        {{-- ── Right rail ── --}}
        <div class="vy-stack">
            {{-- Next payout (real amount + status + masked IBAN [CAP_PAYOUT only]; no estimated date — not backed) --}}
            <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.05s">
                <span class="vy-eyebrow">Next payout</span>
                <div class="vy-dark-figure">{{ $payout['amount_display'] }}</div>
                <span class="vy-status vy-status--{{ $payout['status_meta']['tone'] }}" style="margin-top:6px">
                    <span class="vy-dot"></span>{{ $payout['status_meta']['label'] }}
                </span>
                @if ($rail['iban_masked'])
                    <div class="vy-st-dl">
                        <span class="vy-ov-iban">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg>
                            IBAN {{ $rail['iban_masked'] }}
                        </span>
                    </div>
                @endif
                <div style="margin-top:14px;font-size:12px;color:rgba(255,255,255,.6);line-height:1.6">
                    @if ($payout['paid_at'])
                        Paid {{ $payout['paid_at'] }}@if ($payout['rail']) · {{ $payout['rail'] }}@endif
                    @elseif ($rail['paused'])
                        Held until your tax details are on file.
                    @elseif ($payout['rail'])
                        {{ $payout['rail'] }}
                    @else
                        Bank transfer
                    @endif
                </div>
            </section>

            {{-- Occupancy — REAL owner-scoped %, MoM delta + a mini bar chart (shared OwnerOccupancyTrend) --}}
            <section class="vy-card vy-fade" style="animation-delay:.1s">
                <span class="vy-eyebrow">Occupancy</span>
                <div class="vy-st-occ-top">
                    <span class="vy-st-occ-pct">{{ $occupancy['pct_display'] }}</span>
                    @if ($occupancy['delta'])
                        <span class="vy-st-occ-delta {{ $occupancy['delta']['positive'] ? 'is-up' : 'is-down' }}">{{ $occupancy['delta']['label'] }}</span>
                    @endif
                </div>
                <div class="vy-st-occ-bars" aria-hidden="true">
                    @foreach ($occupancy['months'] as $i => $m)
                        <span class="vy-st-occ-bar {{ $loop->last ? 'is-last' : '' }}" style="height:{{ max(6, $m['pct']) }}%" title="{{ $m['label'] }} · {{ $m['pct'] }}%"></span>
                    @endforeach
                </div>
                <div class="vy-st-occ-axis">
                    <span>{{ $occupancy['months'][0]['label'] ?? '' }}</span>
                    <span>{{ $occupancy['months'][count($occupancy['months']) - 1]['label'] ?? '' }}</span>
                </div>
            </section>

            {{-- The three REAL owner-scoped PDFs, all reconciling + CAP_EXPORTS-gated (co-owner + accountant). A
                 view-only sub-user is "no exports": the whole block is hidden AND every route is export-gated. --}}
            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                {{-- Download statement — the statement PDF itself, standalone (the wireframe's rail button). --}}
                <a href="{{ route('owner.statements.pdf', $statement['id']) }}" class="vy-ov-dl vy-fade" style="animation-delay:.13s" title="Download this statement as a PDF">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                    Download statement
                </a>

                {{-- More documents — Remittance advice (once a payout is matched) + Booking summary. --}}
                <section class="vy-card vy-fade" style="animation-delay:.16s">
                    <span class="vy-eyebrow">More documents</span>
                    <div style="margin-top:12px;display:flex;flex-direction:column;gap:12px">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <div>
                                <div style="font-size:13px;font-weight:500">Remittance advice</div>
                                <div class="vy-line-sub">@if ($payout['id'])RA · {{ $statement['period_label'] }}@else Once paid @endif</div>
                            </div>
                            @if ($payout['id'])
                                <a href="{{ route('owner.payouts.remittance', $payout['id']) }}" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:12px" title="Download the remittance advice for this payout as a PDF">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                    PDF
                                </a>
                            @else
                                <span class="vy-line-sub" title="Available once your payout is issued">—</span>
                            @endif
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <div>
                                <div style="font-size:13px;font-weight:500">Booking summary</div>
                                <div class="vy-line-sub">The bookings behind this statement</div>
                            </div>
                            <a href="{{ route('owner.statements.bookings', $statement['id']) }}" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:12px" title="Download the bookings behind this statement as a PDF">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                PDF
                            </a>
                        </div>
                    </div>
                </section>
            @endownercan
        </div>
    </div>

    {{-- ── Statement history (full width) — REAL rows + year filter (client-side) + REAL exports (CAP_EXPORTS) ── --}}
    <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.12s" x-data="{ year: 'all' }">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:6px">
            <div>
                <h2 class="vy-card-title">Statement history</h2>
                <div class="vy-line-sub" style="margin-top:4px">{{ $totals['count'] }} {{ \Illuminate\Support\Str::plural('statement', $totals['count']) }} · {{ $totals['net_total_display'] }} net total</div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <button type="button" class="vy-pill" :class="{ 'is-active': year === 'all' }" @click="year = 'all'">All</button>
                @foreach ($years as $y)
                    <button type="button" class="vy-pill" :class="{ 'is-active': year === {{ $y }} }" @click="year = {{ $y }}">{{ $y }}</button>
                @endforeach
                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                    {{-- REAL exports: all history, or the selected year (the same client-side filter drives ?year=). --}}
                    <a class="vy-btn vy-btn--ghost" :href="'{{ route('owner.statements.history.csv') }}' + (year === 'all' ? '' : '?year=' + year)" title="Export the statement history as CSV">Export CSV</a>
                    <a class="vy-btn vy-btn--ghost" :href="'{{ route('owner.statements.history.pdf') }}' + (year === 'all' ? '' : '?year=' + year)" title="Export the statement history as PDF">Export PDF</a>
                @endownercan
            </div>
        </div>

        <div style="overflow-x:auto">
            <table class="vy-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="vy-num">Gross</th>
                        <th class="vy-num">Net payable</th>
                        <th>Status</th>
                        <th class="vy-num" aria-label="Download"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($history as $h)
                        <tr class="{{ $h['is_current'] ? 'is-current' : '' }}" x-show="year === 'all' || year === {{ $h['year'] }}">
                            <td>
                                <a class="vy-row-link" href="{{ route('owner.statements.show', $h['id']) }}">
                                    <div class="vy-row-month">{{ $h['month_label'] }}</div>
                                    <div class="vy-row-sub">{{ $h['period_range'] }}</div>
                                </a>
                            </td>
                            <td class="vy-num vy-mono">{{ $h['gross_display'] }}</td>
                            <td class="vy-num vy-mono vy-net">{{ $h['net_display'] }}</td>
                            <td>
                                <span class="vy-status vy-status--{{ $h['status_meta']['tone'] }}">
                                    <span class="vy-dot"></span>{{ $h['status_meta']['label'] }}
                                </span>
                            </td>
                            <td class="vy-num">
                                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                                    <a class="vy-icobtn" href="{{ route('owner.statements.pdf', $h['id']) }}" title="Download this statement as a PDF" aria-label="Download {{ $h['month_label'] }} statement" style="display:inline-grid">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                    </a>
                                @endownercan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ── By unit (full width) — REAL per-unit split (shared OwnerStatementBreakdown). Σ per-unit net == the
         statement net to the cent (#17); owner's OWN units only (#28); a draft shows an honest "available once
         finalized". Each unit name links to its Unit Detail. ── --}}
    <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.16s">
        <div style="display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:8px;margin-bottom:14px">
            <h2 class="vy-card-title">By unit</h2>
            @if ($byUnit['available'])
                <span class="vy-line-sub">{{ $byUnit['period_label'] }} · {{ $byUnit['unit_count'] }} {{ \Illuminate\Support\Str::plural('unit', $byUnit['unit_count']) }}</span>
            @endif
        </div>

        @if ($byUnit['available'])
            <div style="display:flex;flex-direction:column;gap:16px">
                @foreach ($byUnit['units'] as $u)
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px">
                            <div>
                                {{-- The unit name links to Unit Detail ONLY for a role that can reach it (CAP_CALENDAR:
                                     co-owner + view-only). An accountant can view this statement but not Unit Detail, so
                                     they get the name as plain text — never a link that 403s (#30 "gate the control too"). --}}
                                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_CALENDAR)
                                    <a class="vy-line-label" style="font-weight:600;display:inline-flex;align-items:center;gap:5px" href="{{ route('owner.units.show', $u['unit_id']) }}">
                                        {{ $u['name'] }}
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" style="color:var(--vy-faint)"><path d="M9 6l6 6-6 6"/></svg>
                                    </a>
                                @else
                                    <div class="vy-line-label" style="font-weight:600">{{ $u['name'] }}</div>
                                @endownercan
                                <div class="vy-line-sub">{{ $u['nights'] }} {{ \Illuminate\Support\Str::plural('night', $u['nights']) }} · {{ $u['occupancy_pct'] }}% occupancy</div>
                            </div>
                            <div style="text-align:right">
                                <div class="vy-line-amt vy-net" style="font-weight:600">{{ $u['net_display'] }}</div>
                                <div class="vy-line-sub">{{ $u['gross_display'] }} gross · {{ $u['pct_of_net'] }}% of net</div>
                            </div>
                        </div>
                        <div class="vy-track" style="margin-top:8px"><span style="width:{{ $u['bar_width'] }}%"></span></div>
                    </div>
                @endforeach

                @if ($byUnit['unattributed'])
                    {{-- Unit-less lines (e.g. a flat management fee) — an honest remainder so the split still
                         reconciles to the whole. Absent when every line is unit-attributed (the demo case). --}}
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px">
                            <div>
                                <div class="vy-line-label" style="font-weight:600">Whole portfolio</div>
                                <div class="vy-line-sub">Not attributed to a single unit</div>
                            </div>
                            <div style="text-align:right">
                                <div class="vy-line-amt vy-net" style="font-weight:600">{{ $byUnit['unattributed']['net_display'] }}</div>
                                <div class="vy-line-sub">{{ $byUnit['unattributed']['pct'] }}% of net</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="vy-line vy-line--net" style="margin-top:16px">
                <div class="vy-line-label" style="font-weight:600">Total net earnings</div>
                <div class="vy-line-amt vy-net" style="font-weight:700">{{ $byUnit['total_net_display'] }}</div>
            </div>
        @else
            <div class="vy-deferred">
                <span class="vy-eyebrow">Available once finalized</span>
                A per-unit split of this statement (net · gross · occupancy) appears here once the statement is
                finalized — it is derived from the exact ledger lines the statement claims.
            </div>
        @endif
    </section>
</x-owner.shell>
