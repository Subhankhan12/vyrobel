{{--
    Owner Expenses (Wave 1 · sub-gate 1) — the owner's read-only view of the CERTIFIED owner-expense
    backend, wired to LIVE owner-scoped data. Shell from <x-owner.shell>.

    HONESTY MAP (rule locked in DECISIONS.md 2026-07-11; see App\Livewire\Owner\OwnerExpenses):
      • REAL      — every history row: date, category (the real cleaning/maintenance/other enum),
                    description, source (from the polymorphic source_type), amount, the `deductible`
                    TRI-STATE, the `voided_at` state, the `flag` reason, and the statement the row
                    landed on (attributed_ledger_id → owner_ledger.statement_id → statement period;
                    stamped at finalize only → otherwise an honest "Not yet on a statement").
      • TRI-STATE — deductible is NULLABLE: Deductible / Not deductible / Pending review. NULL is a
                    real third state and is NEVER coerced to "Not deductible".
      • DERIVED   — the KPIs and the "By category" totals are Σ of those real rows, VOIDED EXCLUDED
                    (the voided row is still shown, badged). Totals are PER CURRENCY — never summed
                    across currencies (no silent FX, invariant #17).
      • NO BACKEND— Export (CSV) and the owner-side repair request: disabled "Soon", never faked.
      • OMITTED   — the approve/query workflow ("Needs your approval" / Approve / Query / "Query sent")
                    is not rendered AT ALL: there is no owner-approval state machine, no approval
                    columns, no query channel. Even a disabled control would assert a state that does
                    not exist. Deductibility is resolved from the management agreement, not by the owner.
--}}
<x-owner.shell active="expenses" title="Expenses" :viewer="$viewer">
    <div class="vy-cols">
        {{-- ── Main ── --}}
        <div class="vy-stack">
            {{-- KPIs — DERIVED from the real rows (voided excluded); money is display-currency only --}}
            <div class="vy-kpi-grid vy-fade" style="animation-delay:.02s">
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Total expenses</div>
                    <div class="vy-kpi-val">{{ $metrics['total_display'] }}</div>
                    <div class="vy-kpi-sub">
                        {{ $metrics['total_count'] }} {{ \Illuminate\Support\Str::plural('expense', $metrics['total_count']) }}@if ($multiCurrency) · {{ $currency }} only @endif
                    </div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Deductible</div>
                    <div class="vy-kpi-val">{{ $metrics['deductible_display'] }}</div>
                    <div class="vy-kpi-sub">Charged to your statements</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Pending review</div>
                    <div class="vy-kpi-val">{{ $metrics['pending_count'] }}</div>
                    <div class="vy-kpi-sub">Deductibility not yet resolved</div>
                </div>
            </div>

            {{-- Expense history — REAL rows; search + category filter are client-side over the real data --}}
            <section class="vy-card vy-fade" style="animation-delay:.06s" x-data="{ q: '', cat: 'all' }">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:10px">
                    <h2 class="vy-card-title">Expense history</h2>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <input type="search" x-model="q" placeholder="Search description or category" aria-label="Search expenses"
                               style="padding:8px 12px;border-radius:var(--vy-r-sm);border:1px solid var(--vy-glass-border);background:rgba(255,255,255,.6);font-size:13px;font-family:var(--vy-sans)">
                        {{-- NO BACKEND: no CSV export exists → disabled, never a fake download link --}}
                        <button type="button" class="vy-btn vy-btn--ghost is-soon" style="opacity:.6;cursor:not-allowed" disabled aria-disabled="true" title="Export — coming soon">Export</button>
                    </div>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                    <button type="button" class="vy-pill" :class="{ 'is-active': cat === 'all' }" @click="cat = 'all'">All</button>
                    @foreach ($categories as $key => $label)
                        <button type="button" class="vy-pill" :class="{ 'is-active': cat === @js($key) }" @click="cat = @js($key)">{{ $label }}</button>
                    @endforeach
                </div>

                <div style="overflow-x:auto">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Deductible</th>
                                <th>Statement</th>
                                <th class="vy-num">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expenses as $e)
                                <tr x-show="(cat === 'all' || cat === @js($e['category'])) && (q === '' || @js(mb_strtolower($e['category_label'].' '.$e['description'].' '.$e['deductible']['label'])).includes(q.toLowerCase()))">
                                    <td class="vy-muted" style="white-space:nowrap">{{ $e['date_display'] }}</td>
                                    <td>
                                        <span style="font-weight:600">{{ $e['category_label'] }}</span>
                                        {{-- REAL: the record this expense came from (polymorphic source), when there is one --}}
                                        @if ($e['source_label'])
                                            <div class="vy-row-sub">{{ $e['source_label'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $e['description'] }}
                                        {{-- REAL: a void is an append-only reversal — the row stays, honestly badged --}}
                                        @if ($e['voided'])
                                            <span class="vy-status vy-status--draft" data-voided="1" style="margin-left:8px"><span class="vy-dot"></span>Voided</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- REAL tri-state: true / false / NULL — never coerced --}}
                                        <span class="vy-status vy-status--{{ $e['deductible']['tone'] }}" data-deductible="{{ $e['deductible']['key'] }}"><span class="vy-dot"></span>{{ $e['deductible']['label'] }}</span>
                                        @if ($e['flag_note'])
                                            <div class="vy-row-sub">{{ $e['flag_note'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($e['statement'])
                                            <a href="{{ route('owner.statements.show', $e['statement']['id']) }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">{{ $e['statement']['label'] }}</a>
                                        @else
                                            <span class="vy-muted" style="font-size:12.5px">Not yet on a statement</span>
                                        @endif
                                    </td>
                                    <td class="vy-num vy-mono">{{ $e['amount_display'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="vy-empty">No expenses yet.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($metrics['voided_count'] > 0)
                    <div class="vy-fx-note">
                        {{ $metrics['voided_count'] }} voided {{ \Illuminate\Support\Str::plural('expense', $metrics['voided_count']) }} shown for the record — reversed, and excluded from every total.
                    </div>
                @endif
            </section>
        </div>

        {{-- ── Right rail ── --}}
        <div class="vy-stack">
            {{-- By category — DERIVED: Σ of the real rows, voided excluded, per currency --}}
            <section class="vy-card vy-fade" style="animation-delay:.05s">
                <span class="vy-eyebrow">By category</span>
                @forelse ($byCurrency as $block)
                    <div style="margin-top:12px">
                        @if ($multiCurrency)
                            <div class="vy-eyebrow" style="display:block;margin-bottom:2px">{{ $block['currency'] }}</div>
                        @endif
                        {{-- Polish Batch 6 — each REAL category as a proportional bar (share of the block total),
                             via the SHARED .vy-track (same treatment + ÷total basis as the statement by-unit split). --}}
                        @foreach ($block['categories'] as $cat)
                            <div style="margin-top:12px">
                                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px">
                                    <span>
                                        <span style="font-size:13px;font-weight:500">{{ $cat['label'] }}</span>
                                        <span class="vy-line-sub" style="display:block">{{ $cat['count'] }} {{ \Illuminate\Support\Str::plural('expense', $cat['count']) }} · {{ $cat['bar_width'] }}% of total</span>
                                    </span>
                                    <span style="font-family:var(--vy-display);font-weight:600;font-variant-numeric:tabular-nums">{{ $cat['total_display'] }}</span>
                                </div>
                                <div class="vy-track" style="margin-top:8px"><span style="width:{{ $cat['bar_width'] }}%"></span></div>
                            </div>
                        @endforeach
                        <div class="vy-list-row" style="border-top:1.5px solid rgba(120,70,40,.16)">
                            <span style="font-size:13px;font-weight:600">Total</span>
                            <span style="font-family:var(--vy-display);font-weight:600;font-variant-numeric:tabular-nums;color:var(--vy-accent)">{{ $block['total_display'] }}</span>
                        </div>
                    </div>
                @empty
                    <div class="vy-empty">No expenses yet.</div>
                @endforelse

                <div class="vy-fx-note">
                    {{-- NB: a Blade directive whose `@` follows a word character is NOT parsed (Blade's
                         `\B` guard — the rule that lets an email address render literally), so the
                         conditional tail is a ternary rather than an inline @if. --}}
                    Totals exclude voided expenses{{ $multiCurrency ? ' and are shown per currency — never converted' : '' }}.
                </div>
            </section>

            {{-- Deductibility — a plain description of the REAL backend behaviour (no figures, no state) --}}
            <section class="vy-card vy-fade" style="animation-delay:.09s">
                <span class="vy-eyebrow">How this works</span>
                <div style="margin-top:10px;font-size:12.5px;color:var(--vy-muted);line-height:1.7">
                    A <strong style="color:var(--vy-ink)">deductible</strong> cost is charged to your statement for the period it falls in.
                    A <strong style="color:var(--vy-ink)">non-deductible</strong> cost is your operator's and never reaches your ledger.
                    <strong style="color:var(--vy-ink)">Pending review</strong> means deductibility has not been resolved yet.
                    Which is which is set by your management agreement.
                </div>
            </section>

            {{-- Repair request — NO BACKEND (no owner-side maintenance request path): disabled, not faked --}}
            <section class="vy-card vy-fade" style="animation-delay:.12s">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <span class="vy-eyebrow">Need something fixed?</span>
                    <span class="vy-soon-tag">Soon</span>
                </div>
                <div class="vy-line-sub">Raising a repair request from the portal isn't available yet — contact your property manager in the meantime.</div>
                <button type="button" class="vy-btn vy-btn--ghost is-soon" style="margin-top:12px;opacity:.6;cursor:not-allowed" disabled aria-disabled="true" title="Coming soon">Request a repair</button>
            </section>
        </div>
    </div>
</x-owner.shell>
