{{--
    Owner Statement (design pass · Screen 1) — the certified owner-statement projection, styled from
    the catalogued "Owner Statement" design and wired to LIVE owner-scoped data. Shell (sidebar +
    topbar) comes from <x-owner.shell>.

    HONESTY MAP (also on App\Livewire\Owner\OwnerStatementView; rule locked in DECISIONS.md 2026-07-11):
      • REAL      — net-payable hero, the breakdown (the actual owner_statement_lines, which reconcile
                    to the cent), statement history + net total, period navigation, matched payout.
      • DERIVED   — the "% of gross" sublabel, computed from the statement's own real figures.
      • DEFERRED  — by-unit split + occupancy chart: visually stubbed, labelled "not yet available".
      • REAL      — three branded owner-scoped PDFs, all reconciling to these figures to the cent and all
                    export-gated (CAP_EXPORTS): the statement PDF, the booking-summary PDF, and the
                    remittance-advice PDF (offered only once a payout is matched — otherwise "once paid").
    There is deliberately NO "payment / card-processing fee" line — the design's invented line has no
    backing attribution component, so the breakdown only ever shows real, engine-computed components.
--}}
<x-owner.shell active="statements" title="Statement" :viewer="$viewer">
    {{-- Period selector — real navigation to the previous / next statement by period --}}
    <div class="vy-period" style="margin-bottom:22px">
        @if ($nav['prev'])
            <a class="vy-period-btn" href="{{ route('owner.statements.show', $nav['prev']['id']) }}" title="{{ $nav['prev']['label'] }}" aria-label="Previous period, {{ $nav['prev']['label'] }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
            </a>
        @else
            <span class="vy-period-btn" aria-disabled="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></span>
        @endif

        <span class="vy-period-cur">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg>
            {{ $statement['period_label'] }}
        </span>

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
            {{-- Hero --}}
            <section class="vy-card vy-card--accent vy-fade" style="animation-delay:.02s">
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

            {{-- Breakdown — the REAL statement lines (sum to the cent) --}}
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
            {{-- Next payout (real amount + status; no IBAN / estimated date — not backed) --}}
            <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.05s">
                <span class="vy-eyebrow">Next payout</span>
                <div class="vy-dark-figure">{{ $payout['amount_display'] }}</div>
                <span class="vy-status vy-status--{{ $payout['status_meta']['tone'] }}" style="margin-top:6px">
                    <span class="vy-dot"></span>{{ $payout['status_meta']['label'] }}
                </span>
                <div style="margin-top:14px;font-size:12px;color:rgba(255,255,255,.6);line-height:1.6">
                    @if ($payout['paid_at'])Paid {{ $payout['paid_at'] }}@if ($payout['rail']) · {{ $payout['rail'] }}@endif
                    @elseif ($payout['rail']){{ $payout['rail'] }}
                    @else Payout method setup is coming soon.@endif
                </div>
            </section>

            {{-- Occupancy — DEFERRED (derivable from availabilityRange; not built this pass) --}}
            <section class="vy-card vy-fade" style="animation-delay:.1s">
                <span class="vy-eyebrow">Occupancy</span>
                <div class="vy-deferred" style="margin-top:10px">
                    <span class="vy-eyebrow">Not yet available</span>
                    Per-period occupancy trend is derivable from live availability — wiring it is a later step.
                    <div style="display:flex;gap:5px;align-items:flex-end;height:44px;margin-top:12px">
                        @foreach ([40,55,48,62,58,70,66] as $h)
                            <div class="vy-skeleton" style="flex:1;height:{{ $h }}%;border-radius:4px"></div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- More documents — three REAL owner-scoped PDFs (statement · booking summary · remittance
                 advice), each reconciling to the on-screen figures to the cent. 🔒 SG2 — exports are
                 co-owner + accountant only: a view-only sub-user is "no exports", so the whole card is
                 hidden here AND every route is export-gated. --}}
            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
            <section class="vy-card vy-fade" style="animation-delay:.14s">
                <span class="vy-eyebrow">More documents</span>
                <div style="margin-top:12px;display:flex;flex-direction:column;gap:10px">
                    {{-- Booking summary — the REAL bookings behind this statement's gross; always available. --}}
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="font-size:13px;font-weight:500">Booking summary</div>
                            <div class="vy-line-sub">PDF export</div>
                        </div>
                        <a href="{{ route('owner.statements.bookings', $statement['id']) }}" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:12px" title="Download the bookings behind this statement as a PDF">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                            PDF
                        </a>
                    </div>
                    {{-- Remittance advice — a REAL PDF, available once a payout is matched to this statement.
                         No matched payout yet ⇒ an honest "once paid" note, never a broken link or a stub. --}}
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="font-size:13px;font-weight:500">Remittance advice</div>
                            <div class="vy-line-sub">PDF export</div>
                        </div>
                        @if ($payout['id'])
                            <a href="{{ route('owner.payouts.remittance', $payout['id']) }}" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:12px" title="Download the remittance advice for this payout as a PDF">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                PDF
                            </a>
                        @else
                            <span class="vy-line-sub" title="Available once your payout is issued">Once paid</span>
                        @endif
                    </div>
                </div>
                {{-- The statement PDF itself — owner-scoped, reconciles to the on-screen figures to the cent. --}}
                <a href="{{ route('owner.statements.pdf', $statement['id']) }}" class="vy-btn vy-btn--accent" style="margin-top:16px;width:100%;justify-content:center" title="Download this statement as a PDF">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                    Download statement
                </a>
            </section>
            @endownercan
        </div>
    </div>

    {{-- ── Statement history (full width) — REAL rows + year filter (client-side over real data) ── --}}
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
                <button type="button" class="vy-btn vy-btn--ghost is-soon" disabled aria-disabled="true" title="Export — coming soon">Export CSV</button>
                <button type="button" class="vy-btn vy-btn--ghost is-soon" disabled aria-disabled="true" title="Export — coming soon">Export PDF</button>
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
                                <span class="vy-icobtn is-soon" aria-disabled="true" title="Download — coming soon" style="display:inline-grid;opacity:.4">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ── By unit (full width) — REAL per-unit split (Batch B · GATE 1). Pure ledger arithmetic: the exact
         lines this statement CLAIMED (owner_ledger.statement_id, stamped at finalize), grouped by unit_id and
         netted through the shared OwnerLedgerAttribution primitive, so Σ per-unit net (+ any unattributed
         remainder) == the statement net to the cent (#17). Occupancy/nights via the shared PerformanceMetrics
         over this statement's own period. Owner's OWN units only (#28). No fabrication (#39): a draft with no
         claimed lines shows an honest "available once finalized" state, never a fake split. ── --}}
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
                                <div class="vy-line-label" style="font-weight:600">{{ $u['name'] }}</div>
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

@script
<script>
    (function () {
        function fmt(minor, symbol) {
            var neg = minor < 0, v = Math.abs(minor) / 100;
            var s = v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            return (neg ? '−' : '') + symbol + s;
        }
        function run() {
            var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            document.querySelectorAll('[data-countup]').forEach(function (el) {
                if (el.dataset.cuDone) return;
                el.dataset.cuDone = '1';
                var target = parseInt(el.dataset.countup, 10), symbol = el.dataset.symbol || '';
                if (reduce || isNaN(target)) return;   // real value is already rendered server-side
                var dur = 900, start = null;
                (function frame(ts) {
                    if (!start) start = ts;
                    var p = Math.min((ts - start) / dur, 1), eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = fmt(Math.round(target * eased), symbol);
                    if (p < 1) requestAnimationFrame(frame); else el.textContent = fmt(target, symbol);
                })(performance.now());
            });
        }
        run();
    })();
</script>
@endscript
