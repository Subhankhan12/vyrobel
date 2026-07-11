{{--
    Owner Statement (design pass · Screen 1) — the certified owner-statement projection, styled from
    the catalogued "Owner Statement" design and wired to LIVE owner-scoped data.

    HONESTY MAP (also documented on App\Livewire\Owner\OwnerStatementView):
      • REAL      — net-payable hero, the breakdown (the actual owner_statement_lines, which reconcile
                    to the cent), statement history + net total, period navigation, matched payout.
      • DEFERRED  — by-unit split + occupancy chart: visually stubbed, labelled "not yet available".
      • NO BACKEND— CSV/PDF export, document downloads: disabled "Soon" affordances, never a fake action.
    There is deliberately NO "payment / card-processing fee" line — the design's invented line has no
    backing attribution component, so the breakdown only ever shows real, engine-computed components.
--}}
@php
    $navGroups = [
        ['OWNER PORTAL', [
            ['Overview', 'M3 10.5 12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5', route('owner.dashboard'), false],
            ['My units', 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z', null, false],
            ['Calendar', 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4', null, false],
        ]],
        ['EARNINGS', [
            ['Statements', 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', null, true],
            ['Payouts', 'M3 7h18v12H3zM3 11h18M15 15h3', null, false],
            ['Balance', 'M12 3v18M6 21h12M5 8h14l-3 5H8z', null, false],
            ['Expenses', 'M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6', null, false],
            ['Tax center', 'M6 6h.01M18 18h.01M6 18 18 6', null, false],
        ]],
        ['MANAGE', [
            ['Reports', 'M4 20V4M4 20h16M8 16v-4M12 16V8M16 16v-6', null, false],
            ['Insights', 'M4 15l4-5 4 3 6-8', null, false],
            ['Rates', 'M4 4h9l7 7-9 9-7-7zM8 8h.01', null, false],
            ['Reviews', 'M12 4l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.6-4.8 2.6.9-5.4-3.9-3.8 5.4-.8z', null, false],
            ['Agreement', 'M5 4h11l3 3v13H5zM8 12c3-1 4 2 6 0M8 16h8', null, false],
            ['Messages', 'M4 5h16v11H9l-5 4z', null, false],
        ]],
        ['ACCOUNT', [
            ['Settings', 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2', null, false],
            ['Security', 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z', null, false],
            ['Team & access', 'M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM2 20c0-3 3-5 6-5s6 2 6 5M16 11a3 3 0 1 0 0-6M22 20c0-3-3-5-6-5', null, false],
            ['Help & support', 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM9.6 9a2.5 2.5 0 0 1 4 2c0 1.6-2 2-2 3.4M12 17h.01', null, false],
        ]],
    ];
@endphp

<div class="vy-shell">
    {{-- ─────────────────────────── Sidebar (shell — Overview/Statements navigate; other items are
         non-interactive placeholders for screens not built yet, never faked as working) ─────────── --}}
    <aside class="vy-side">
        <div class="vy-brand">
            <span class="vy-brand-mark">V</span>
            <span class="vy-brand-name">Vyrobel</span>
        </div>

        @foreach ($navGroups as [$group, $items])
            <nav class="vy-nav-group" aria-label="{{ $group }}">
                <span class="vy-nav-label">{{ $group }}</span>
                @foreach ($items as [$label, $icon, $href, $active])
                    @if ($active)
                        <span class="vy-nav-item is-active" aria-current="page">
                            <svg class="vy-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </span>
                    @elseif ($href)
                        <a class="vy-nav-item" href="{{ $href }}">
                            <svg class="vy-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </a>
                    @else
                        <span class="vy-nav-item" aria-disabled="true" title="Coming soon">
                            <svg class="vy-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </span>
                    @endif
                @endforeach
            </nav>
        @endforeach
    </aside>

    {{-- ─────────────────────────── Main ─────────────────────────── --}}
    <div class="vy-main">
        <div class="vy-topbar">
            <h1>Statement</h1>
            <div class="vy-topbar-actions">
                <span class="vy-bell" title="Notifications — coming soon" aria-label="Notifications">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6M10 20a2 2 0 0 0 4 0"/></svg>
                </span>
                <span class="vy-chip">
                    <span class="vy-avatar">{{ $viewer['initials'] }}</span>
                    <span class="vy-chip-name">{{ $viewer['name'] }}</span>
                </span>
            </div>
        </div>

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
                <section class="vy-card vy-card--accent vy-fade vy-swap-next" style="animation-delay:.02s">
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

                {{-- Documents — NO BACKEND (no PDF/CSV generation): disabled, never a fake download --}}
                <section class="vy-card vy-fade" style="animation-delay:.14s">
                    <span class="vy-eyebrow">More documents</span>
                    <div style="margin-top:12px;display:flex;flex-direction:column;gap:10px">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <div>
                                <div style="font-size:13px;font-weight:500">Remittance advice</div>
                                <div class="vy-line-sub">PDF export</div>
                            </div>
                            <span class="vy-soon-tag">Soon</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <div>
                                <div style="font-size:13px;font-weight:500">Booking summary</div>
                                <div class="vy-line-sub">PDF export</div>
                            </div>
                            <span class="vy-soon-tag">Soon</span>
                        </div>
                    </div>
                    <button type="button" class="vy-btn vy-btn--ghost is-soon" style="margin-top:16px;width:100%;justify-content:center" disabled aria-disabled="true" title="Statement download — coming soon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                        Download statement
                    </button>
                </section>
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

        {{-- ── By unit (full width) — DEFERRED (derivable from owner_ledger.unit_id; not this pass) ── --}}
        <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.16s">
            <h2 class="vy-card-title" style="margin-bottom:12px">By unit</h2>
            <div class="vy-deferred">
                <span class="vy-eyebrow">Not yet available</span>
                A per-unit split of this statement (gross / net / occupancy) is derivable from the ledger's
                <code>unit_id</code> on each line. Wiring the per-unit projection is a later step in the design pass.
                <div style="margin-top:14px;display:flex;flex-direction:column;gap:12px">
                    @foreach ([70, 58, 64] as $w)
                        <div>
                            <div class="vy-skeleton" style="width:{{ $w + 20 }}%;height:9px;margin-bottom:8px"></div>
                            <div class="vy-track"><span style="width:{{ $w }}%"></span></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</div>

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
