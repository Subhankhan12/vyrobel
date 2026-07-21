{{--
    Owner Payouts (design pass · Screen 3) — a NEW owner-scoped page for the certified Payout backend,
    styled from the catalogued "Owner Payouts" design and wired to LIVE owner-scoped data. Shell from
    <x-owner.shell>.

    HONESTY MAP (rule locked in DECISIONS.md 2026-07-11; see App\Livewire\Owner\OwnerPayouts):
      • REAL      — payout history (amount / date / rail=Bank transfer / status / statement link),
                    paid-this-year, next/pending totals, payout currency. The per-row REMITTANCE link
                    (Wave 1 · Sub-gate 2) streams a real owner-scoped PDF built from this payout + its
                    source statement's real lines — no longer "Soon".
      • DERIVED   — paid-this-year = Σ real paid payouts; next-payout breakdown = the source statement's
                    real lines.
      • LIFECYCLE — REAL states only (Finalized → Pending → Paid, + Failed/Reversed). NO "Scheduled" /
                    "On hold" — those are not modelled and are never invented.
      • NO BACKEND— on-hold/verification banner, IBAN/payout account, estimated dates, projections,
                    bulk exports, and the non-existent preference controls: omitted or disabled "Soon".
--}}
<x-owner.shell active="payouts" title="Payouts" :viewer="$viewer">
    <div class="vy-cols">
        {{-- ── Main ── --}}
        <div class="vy-stack">
            {{-- KPIs — all REAL --}}
            <div class="vy-kpi-grid vy-fade" style="animation-delay:.02s">
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Paid this year</div>
                    <div class="vy-kpi-val">{{ $metrics['paid_this_year_display'] }}</div>
                    <div class="vy-kpi-sub">{{ $metrics['paid_count'] }} {{ \Illuminate\Support\Str::plural('payout', $metrics['paid_count']) }}@if ($metrics['paid_year']) · {{ $metrics['paid_year'] }}@endif</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Next payout</div>
                    <div class="vy-kpi-val">{{ $metrics['next_payout_display'] }}</div>
                    <div class="vy-kpi-sub">{{ $metrics['pending_count'] > 0 ? 'Awaiting disbursement' : 'Nothing pending' }}</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Pending</div>
                    <div class="vy-kpi-val">{{ $metrics['pending_total_display'] }}</div>
                    <div class="vy-kpi-sub">{{ $metrics['pending_count'] }} {{ \Illuminate\Support\Str::plural('payout', $metrics['pending_count']) }}</div>
                </div>
            </div>

            {{-- Payout history — REAL rows; search + status filter are client-side over the real data --}}
            <section class="vy-card vy-fade" style="animation-delay:.06s" x-data="{ q: '', status: 'all' }">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:10px">
                    <h2 class="vy-card-title">Payout history</h2>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <input type="search" x-model="q" placeholder="Search month or status" aria-label="Search payouts"
                               style="padding:8px 12px;border-radius:var(--vy-r-sm);border:1px solid var(--vy-glass-border);background:rgba(255,255,255,.6);font-size:13px;font-family:var(--vy-sans)">
                        <button type="button" class="vy-btn vy-btn--ghost is-soon" disabled aria-disabled="true" title="Export — coming soon">Export</button>
                    </div>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                    <button type="button" class="vy-pill" :class="{ 'is-active': status === 'all' }" @click="status = 'all'">All</button>
                    @foreach ($statuses as $st)
                        <button type="button" class="vy-pill" :class="{ 'is-active': status === '{{ $st }}' }" @click="status = '{{ $st }}'">{{ ucfirst($st) }}</button>
                    @endforeach
                </div>

                <div style="overflow-x:auto">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Rail</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="vy-num">Amount</th>
                                <th aria-label="Links"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payouts as $p)
                                <tr x-show="(status === 'all' || status === '{{ $p['status'] }}') && (q === '' || '{{ strtolower($p['month_label'].' '.$p['status'].' '.$p['rail_label']) }}'.includes(q.toLowerCase()))">
                                    <td class="vy-row-month" style="font-weight:600">{{ $p['month_label'] }}</td>
                                    <td><span class="vy-rail"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h18M5 10V7l7-4 7 4v3M5 10v8m14-8v8M9 10v8m6-8v8M4 18h16"/></svg>{{ $p['rail_label'] }}</span></td>
                                    <td class="vy-muted">{{ $p['date_display'] }}</td>
                                    <td><span class="vy-status vy-status--{{ $p['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $p['status_meta']['label'] }}</span></td>
                                    <td class="vy-num vy-mono">{{ $p['amount_display'] }}</td>
                                    <td class="vy-num" style="white-space:nowrap">
                                        @if ($p['statement_id'])
                                            <a href="{{ route('owner.statements.show', $p['statement_id']) }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">Statement</a>
                                        @endif
                                        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                                        <a href="{{ route('owner.payouts.remittance', $p['id']) }}" class="vy-btn vy-btn--ghost" style="margin-left:8px;padding:5px 10px;font-size:12px" title="Download the remittance advice for this payout as a PDF">Remittance</a>
                                        @endownercan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="vy-empty">No payouts yet.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- ── Right rail ── --}}
        <div class="vy-stack">
            {{-- Next payout — REAL amount + status + lifecycle (real states only) + real breakdown --}}
            @if ($nextPayout)
                <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.05s">
                    <span class="vy-eyebrow">Next payout @if ($nextPayout['period_label'])· {{ $nextPayout['period_label'] }}@endif</span>
                    <div class="vy-dark-figure" data-countup="{{ $nextPayout['amount_minor'] }}" data-symbol="{{ $nextPayout['currency_symbol'] }}">{{ $nextPayout['amount_display'] }}</div>
                    <span class="vy-status vy-status--{{ $nextPayout['status_meta']['tone'] }}" style="margin-top:6px"><span class="vy-dot"></span>{{ $nextPayout['status_meta']['label'] }} · {{ $nextPayout['rail_label'] }}</span>

                    {{-- Lifecycle — Finalized → Pending → Paid (REAL states; no Scheduled/On hold) --}}
                    <div class="vy-steps">
                        @foreach ($lifecycle['steps'] as $step)
                            <div class="vy-step is-{{ $step['state'] }}"><span class="vy-step-dot"></span><span class="vy-step-label">{{ $step['label'] }}</span></div>
                            @if (! $loop->last)<span class="vy-step-line"></span>@endif
                        @endforeach
                    </div>
                    @if ($lifecycle['note'])
                        <div style="margin-top:10px;font-size:11.5px;color:rgba(255,255,255,.6)">{{ $lifecycle['note'] }}</div>
                    @endif

                    {{-- Breakdown — the source statement's REAL lines (no invented fee line) --}}
                    @if (! empty($nextPayout['breakdown']))
                        <div style="margin-top:16px;border-top:1px solid rgba(255,255,255,.12);padding-top:12px">
                            @foreach ($nextPayout['breakdown'] as $line)
                                <div style="display:flex;justify-content:space-between;gap:12px;padding:5px 0;font-size:12.5px;{{ $line['is_net'] ? 'font-weight:600;color:#fff;margin-top:4px;border-top:1px solid rgba(255,255,255,.12);padding-top:9px' : 'color:rgba(255,255,255,.7)' }}">
                                    <span>{{ $line['label'] }}</span>
                                    <span style="font-variant-numeric:tabular-nums;{{ $line['is_negative'] ? 'color:var(--vy-peach)' : '' }}">{{ $line['amount_display'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($nextPayout['statement_id'])
                        <a href="{{ route('owner.statements.show', $nextPayout['statement_id']) }}" style="display:inline-block;margin-top:14px;font-size:12.5px;font-weight:600;color:var(--vy-peach)">View statement →</a>
                    @endif
                </section>
            @else
                <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.05s">
                    <span class="vy-eyebrow">Next payout</span>
                    <div style="margin-top:10px;font-size:13px;color:rgba(255,255,255,.7)">No payouts yet. Your first payout appears once a statement is finalized.</div>
                </section>
            @endif

            {{-- Payout account — NO BACKEND (no payout-method model): method is real, no fake IBAN --}}
            <section class="vy-card vy-fade" style="animation-delay:.09s">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <span class="vy-eyebrow">Payout account</span>
                    <span class="vy-soon-tag">Manage · Soon</span>
                </div>
                <div style="font-size:13.5px;font-weight:500">Bank transfer</div>
                <div class="vy-line-sub">Account details and Stripe Connect onboarding are coming soon.</div>
            </section>

            {{-- Upcoming schedule — DEFERRED (no projection engine) --}}
            <section class="vy-card vy-fade" style="animation-delay:.12s">
                <span class="vy-eyebrow">Upcoming schedule</span>
                <div class="vy-deferred" style="margin-top:10px">
                    <span class="vy-eyebrow">Not yet available</span>
                    Projected future payouts will appear here once scheduling is wired.
                </div>
            </section>

            {{-- Payout preferences — currency is REAL; the rest have no field → disabled "Soon" --}}
            <section class="vy-card vy-fade" style="animation-delay:.15s">
                <span class="vy-eyebrow">Payout preferences</span>
                <div class="vy-list-row" style="border-bottom:1px solid var(--vy-hairline)">
                    <span style="font-size:13px;font-weight:500">Payout currency</span>
                    <span class="vy-mono" style="font-weight:600">{{ $currency }}</span>
                </div>
                @foreach (['Frequency', 'Payout day', 'Minimum payout', 'Pause payouts'] as $pref)
                    <div class="vy-list-row">
                        <span style="font-size:13px;color:var(--vy-muted)">{{ $pref }}</span>
                        <span class="vy-soon-tag">Soon</span>
                    </div>
                @endforeach
            </section>
        </div>
    </div>
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
