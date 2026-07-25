{{--
    Owner Archive (Wave 1 · Sub-gate 1) — the owner's FINALIZED statement history, styled from the
    catalogued "Owner Archive" design and wired to LIVE owner-scoped rows. Shell from <x-owner.shell>.

    HONESTY MAP (rule locked in DECISIONS.md 2026-07-11; see App\Livewire\Owner\OwnerArchive):
      • REAL      — the finalized/paid statement rows: the real per-owner gap-free `sequence_no`
                    ("No. 7"; omitted when null, never faked), the real period, net payable, the status
                    badge from the REAL enum ONLY (paid → "Paid", finalized → "Payout pending"), the
                    real `has_unverified` flag, the statement link, and TWO REAL owner-scoped downloads —
                    the statement PDF and the booking-summary PDF ("Bookings", the real attributed
                    room_revenue behind that period's gross). Both reconcile to the cent, so real
                    downloads, not stubs.
                    A DRAFT is not archive and is excluded (it has no number and can still move).
      • DERIVED   — "Net earned · lifetime" = Σ net payable over those real rows, totalled PER PAYOUT
                    CURRENCY and rendered per currency — NEVER a cross-currency sum (invariant #17).
                    Year grouping, per-year net + count, the covered-month span, the statement count and
                    the "since …" span all come from the real rows. The per-year bars render ONLY in the
                    single-currency case. Search narrows the REAL rows client-side; it invents nothing.
      • REAL      — the per-year annual tax summary PDF (OwnerAnnualTaxDocument via owner.tax.annual),
                    export-gated, the same document the Tax Center offers. Linked only for years with
                    committed statements (exactly what annualSummary() sums), so it never 404s.
      • NO BACKEND— the full-archive ZIP (no ZIP builder): a disabled "Soon", never a fake download link.
      • NOT PORTED— the design's static "3 years · 34 documents", "since June 2023", its 7-year
                    retention promise and its "OS-2025-09" reference format are all fiction with no
                    backing row or policy. Counts + span are derived from real rows, the real reference
                    is `sequence_no`, and the retention claim is dropped.
--}}
<x-owner.shell active="statements" title="Statements archive" :viewer="$viewer">
    <div x-data="{ q: '' }">
        {{-- Sub-header — REAL counts + REAL span (the design's "3 years · 34 documents" is not ported) --}}
        <div class="vy-line-sub" style="font-size:13px;margin-bottom:20px">
            @if ($summary['count'] > 0)
                Your finalized history · {{ $summary['years'] }} {{ \Illuminate\Support\Str::plural('year', $summary['years']) }} · {{ $summary['count'] }} {{ \Illuminate\Support\Str::plural('statement', $summary['count']) }}
            @else
                Statements are archived here the moment they are finalized.
            @endif
        </div>

        {{-- Lifetime earnings — DERIVED: Σ net payable of the REAL finalized rows, per payout currency --}}
        <section class="vy-card vy-card--accent vy-fade" style="animation-delay:.02s">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap">
                <div>
                    <span class="vy-eyebrow">Net earned · lifetime</span>
                    @forelse ($lifetime as $total)
                        @if (count($lifetime) === 1)
                            <div class="vy-hero-figure" style="font-size:44px" data-countup="{{ $total['minor'] }}" data-symbol="{{ $total['symbol'] }}">{{ $total['display'] }}</div>
                        @else
                            <div class="vy-hero-figure" style="font-size:32px">{{ $total['display'] }} <span class="vy-line-sub" style="font-size:12px">{{ $total['currency'] }}</span></div>
                        @endif
                    @empty
                        <div class="vy-hero-figure" style="font-size:44px;color:var(--vy-faint)">—</div>
                    @endforelse
                </div>
                @if ($summary['since'])
                    <span class="vy-eyebrow">since {{ $summary['since'] }}</span>
                @endif
            </div>

            @if (count($lifetime) > 1)
                <div class="vy-fx-note">Totalled per payout currency — balances in different currencies are never combined.</div>
            @elseif ($lifetime === [])
                <div class="vy-hero-sub">No finalized statements yet — your first one will appear here.</div>
            @endif

            {{-- Per-year bars — DERIVED from the real per-year totals; single-currency only --}}
            @if ($bars !== [])
                <div class="vy-chart" style="height:120px;margin-top:18px">
                    @foreach ($bars as $i => $bar)
                        <div class="vy-chart-col" title="{{ $bar['label'] }} · {{ $bar['amount_display'] }}">
                            <div class="vy-bar {{ $i === count($bars) - 1 ? 'is-last' : '' }}" style="height:{{ max(4, $bar['pct']) }}%"></div>
                            <div class="vy-bar-label">{{ $bar['label'] }}</div>
                            <div class="vy-bar-label" style="font-weight:600;color:var(--vy-ink)">{{ $bar['amount_display'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Search — client-side over the REAL rows only (same idiom as the payouts screen) --}}
        @if ($summary['count'] > 0)
            <div style="display:flex;align-items:center;gap:8px;margin-top:22px">
                <input type="search" x-model="q" placeholder="Search your statements — month, year, status or number" aria-label="Search statements"
                       style="flex:1;padding:10px 14px;border-radius:var(--vy-r-sm);border:1px solid var(--vy-glass-border);background:rgba(255,255,255,.6);font-size:13px;font-family:var(--vy-sans)">
                <button type="button" class="vy-btn vy-btn--ghost" x-show="q !== ''" @click="q = ''" style="display:none">Clear</button>
            </div>

            {{-- No results — over the REAL rows; the design's "OS-2025-09" reference format is fiction, the real reference is the statement number --}}
            <div class="vy-empty" x-show="q !== '' && !@js($haystacks).some(h => h.includes(q.toLowerCase()))" style="display:none">
                No statements match “<span x-text="q"></span>”.
                <div class="vy-line-sub" style="margin-top:6px">Try a month (“September”), a year (“2026”), a status (“Paid”) or a statement number (“No. 1”).</div>
            </div>
        @endif

        {{-- ── Archive, grouped by the year of the REAL period_start ── --}}
        @forelse ($groups as $group)
            <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.06s"
                     x-show="q === '' || @js($group['haystacks']).some(h => h.includes(q.toLowerCase()))">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:8px">
                    <div>
                        <h2 class="vy-card-title">
                            {{ $group['year'] }}
                            @if ($group['part_label'])<span class="vy-line-sub" style="margin-left:8px">{{ $group['part_label'] }}</span>@endif
                        </h2>
                        <div class="vy-line-sub" style="margin-top:4px">
                            {{ $group['count'] }} {{ \Illuminate\Support\Str::plural('statement', $group['count']) }} · net
                            @foreach ($group['nets'] as $net){{ $net['display'] }}@if (! $loop->last) · @endif @endforeach
                            @if (count($group['nets']) > 1)<span class="vy-line-sub">(per payout currency)</span>@endif
                        </div>
                    </div>
                    {{-- Annual tax summary — a REAL owner-scoped PDF for this year (OwnerAnnualTaxDocument
                         via owner.tax.annual), the same export the Tax Center offers. Export-gated; this
                         year is in the archive because it has committed statements — exactly what
                         annualSummary() sums — so the link never 404s. --}}
                    @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                    <a href="{{ route('owner.tax.annual', $group['year']) }}" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:12px" title="Download the {{ $group['year'] }} annual tax summary as a PDF">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                        Tax summary
                    </a>
                    @endownercan
                </div>

                <div style="overflow-x:auto">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>Statement</th>
                                <th>Status</th>
                                <th class="vy-num">Net payable</th>
                                <th class="vy-num" aria-label="Links"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['rows'] as $row)
                                <tr x-show="q === '' || @js($row['search']).includes(q.toLowerCase())">
                                    <td>
                                        <a class="vy-row-link" href="{{ route('owner.statements.show', $row['id']) }}">
                                            <div class="vy-row-month">{{ $row['period_label'] }}</div>
                                            {{-- The REAL statement number (stamped at finalize); omitted when there is none --}}
                                            @if ($row['number_label'])<div class="vy-row-sub">{{ $row['number_label'] }}</div>@endif
                                        </a>
                                    </td>
                                    <td>
                                        <span class="vy-status vy-status--{{ $row['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['status_meta']['label'] }}</span>
                                        @if ($row['has_unverified'])
                                            <span class="vy-provisional" style="margin-left:6px" title="Some lines are still pending channel reconciliation">Contains unverified lines</span>
                                        @endif
                                    </td>
                                    <td class="vy-num vy-mono vy-net">{{ $row['net_display'] }}</td>
                                    <td class="vy-num" style="white-space:nowrap">
                                        <a href="{{ route('owner.statements.show', $row['id']) }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">View</a>
                                        {{-- REAL downloads — owner-scoped PDFs. 🔒 SG2 — a view-only sub-user is
                                             "no exports": both are hidden (and the routes are export-gated). --}}
                                        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                                        <a href="{{ route('owner.statements.pdf', $row['id']) }}" class="vy-btn vy-btn--ghost" style="margin-left:10px;padding:5px 10px;font-size:12px" title="Download this statement as a PDF">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                            PDF
                                        </a>
                                        <a href="{{ route('owner.statements.bookings', $row['id']) }}" class="vy-btn vy-btn--ghost" style="margin-left:6px;padding:5px 10px;font-size:12px" title="Download the bookings behind this statement as a PDF">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                            Bookings
                                        </a>
                                        @endownercan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.06s">
                <div class="vy-empty">No finalized statements yet. A statement is archived here once it is finalized.</div>
            </section>
        @endforelse

        {{-- Full-archive ZIP — NO BACKEND (no ZIP builder): disabled, never a fake download --}}
        <section class="vy-card vy-fade" style="margin-top:22px;animation-delay:.1s;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
            <div>
                <div style="font-family:var(--vy-display);font-weight:600;font-size:14.5px">Download the full archive</div>
                <div class="vy-line-sub">One ZIP of every statement PDF. Each statement above already downloads on its own.</div>
            </div>
            <button type="button" class="vy-btn vy-btn--ghost is-soon" style="opacity:.6;cursor:not-allowed" disabled aria-disabled="true" title="Coming soon">Download ZIP</button>
        </section>
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
