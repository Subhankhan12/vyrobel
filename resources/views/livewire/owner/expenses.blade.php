{{--
    Owner Expenses (exact-match pass · invariant #59) — the owner's view of the CERTIFIED owner-expense
    backend, rebuilt to the decoded "Expenses & maintenance" wireframe and wired to LIVE owner-scoped data.
    Shell from <x-owner.shell>. This screen is READ-ONLY over expenses (the ONE write is the repair relay,
    a hardened owner→operator message — never an expense mutation).

    HONESTY MAP (see App\Livewire\Owner\OwnerExpenses):
      • REAL      — every history row: date, category (cleaning/maintenance/other enum), description, the
                    REAL unit (unit_id → name ?? code), source (polymorphic source_type), amount, the
                    `deductible` TRI-STATE, `voided_at`, the `flag` reason, and the statement the row landed
                    on (attributed_ledger_id → owner_ledger.statement_id → period; stamped at finalize only).
      • KPIs      — Spent this year / Pending review (COUNT) / Charged to payouts — Σ of the real rows.
      • DERIVED   — "By category" = Σ real rows, voided excluded, per currency (no silent FX, #17).
      • REAL      — Export (CSV, CAP_EXPORTS-gated) + "Need something fixed?" (a REAL relay into the
                    owner↔operator channel, owner.messages.start — the same path as My Units).
      • REFUSED   — the design's owner APPROVE / QUERY workflow + "Awaiting your approval" (no backend, #39)
                    → the honest analog is the REAL "Pending review" count + a read-only list + the
                    "How expenses work" explainer. Vendor is now a REAL column (owner_expenses.vendor) — the
                    supplier a cost was paid to, shown when captured, honestly "—" otherwise.

    RAW-BLADE RULE: every `@` follows a space, newline or tag; inline conditions are ternaries in {{ }}.
--}}
<x-owner.shell active="expenses" title="Expenses & maintenance" :subtitle="'Every cost on your units — what’s charged to you, what’s the operator’s, and what’s still under review.'" :viewer="$viewer">
    <style>
        .vy-ex-pending { background: rgba(232,112,58,.08); border-color: var(--vy-accent-border); }
        .vy-ex-prow { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:9px 0; border-top:1px solid rgba(120,70,40,.12); }
        .vy-ex-prow:first-of-type { border-top:none; }
        .vy-ex-relay textarea { width:100%; box-sizing:border-box; resize:none; border:1px solid rgba(217,102,46,.28); border-radius:11px; background:#fff; padding:10px 12px; font-family:var(--vy-sans); font-size:12.5px; line-height:1.5; color:var(--vy-ink); outline:none; }
    </style>

    <div class="vy-cols">
        {{-- ── Main ── --}}
        <div class="vy-stack">
            {{-- KPIs — DERIVED from the real rows (voided excluded); money is display-currency, this year --}}
            <div class="vy-kpi-grid vy-fade" style="animation-delay:.02s">
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Spent this year</div>
                    <div class="vy-kpi-val">{{ $metrics['spent_display'] }}</div>
                    <div class="vy-kpi-sub">{{ $metrics['spent_count'] }} {{ \Illuminate\Support\Str::plural('expense', $metrics['spent_count']) }} in {{ $metrics['year'] }}{{ $multiCurrency ? ' · '.$currency.' only' : '' }}</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Awaiting your approval</div>
                    <div class="vy-kpi-val">{{ $metrics['awaiting_display'] }}</div>
                    <div class="vy-kpi-sub">{{ $metrics['awaiting_count'] }} to approve · not charged until you do</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Charged to payouts</div>
                    <div class="vy-kpi-val">{{ $metrics['charged_display'] }}</div>
                    <div class="vy-kpi-sub">Approved &amp; deducted year-to-date</div>
                </div>
            </div>

            {{-- Needs your approval (Wave C · GATE 2) — REAL pending-approval expenses. Approve → charged to your
                 payout (reduces net); Query → held out of net + opens the owner↔operator relay. CAP_ACTIONS. --}}
            @if ($approvalRows)
                <section class="vy-card vy-fade" style="animation-delay:.03s;border-left:3px solid var(--vy-amber)">
                    <h2 class="vy-card-title">Needs your approval</h2>
                    <div class="vy-line-sub" style="margin-bottom:8px">These deductible costs are not charged to your payout until you approve them. Approve to deduct, or query your team.</div>
                    @foreach ($approvalRows as $a)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-top:1px solid var(--vy-hairline);flex-wrap:wrap">
                            <div style="min-width:0">
                                <div style="font-weight:600;font-size:13px">{{ $a['description'] }} <span class="vy-mono" style="color:var(--vy-accent)">{{ $a['amount_display'] }}</span></div>
                                <div class="vy-row-sub">{{ $a['date_display'] }} · {{ $a['category_label'] }} · {{ $a['unit']['name'] }}@if ($a['vendor']) · {{ $a['vendor'] }}@endif</div>
                            </div>
                            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                <div style="display:flex;gap:8px;flex-shrink:0" x-data="{ q: false }">
                                    <form method="POST" action="{{ route('owner.expenses.approve', $a['id']) }}" style="margin:0">
                                        @csrf
                                        <button type="submit" class="vy-btn vy-btn--accent" style="padding:6px 14px;font-size:12px">Approve</button>
                                    </form>
                                    <button type="button" class="vy-btn vy-btn--ghost" style="padding:6px 14px;font-size:12px" @click="q = !q">Query</button>
                                    <form method="POST" action="{{ route('owner.expenses.query', $a['id']) }}" x-show="q" x-cloak style="margin:0;display:flex;gap:6px;align-items:center">
                                        @csrf
                                        <input type="text" name="note" placeholder="What's your question?" maxlength="1000" class="vy-input" style="font-size:12px;max-width:220px">
                                        <button type="submit" class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12px">Send</button>
                                    </form>
                                </div>
                            @endownercan
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- Pending review — REAL null-deductible rows, read-only. The wireframe's "needs approval" slot,
                 honestly: deductibility is resolved from the agreement, NOT by the owner — so NO action here. --}}
            @if ($pendingRows)
                <section class="vy-card vy-ex-pending vy-fade" style="animation-delay:.04s">
                    <h2 class="vy-card-title">Pending review</h2>
                    <div class="vy-line-sub" style="margin-bottom:6px">Deductibility for these is still being resolved from your management agreement — nothing for you to do.</div>
                    @foreach ($pendingRows as $p)
                        <div class="vy-ex-prow">
                            <div>
                                <div style="font-weight:600;font-size:13px">{{ $p['description'] }}</div>
                                <div class="vy-row-sub">{{ $p['date_display'] }} · {{ $p['category_label'] }} · {{ $p['unit']['name'] }}</div>
                            </div>
                            <span class="vy-mono" style="font-weight:600">{{ $p['amount_display'] }}</span>
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- Expense history — REAL rows; unit + category filters + search are client-side over real data --}}
            <section class="vy-card vy-fade" style="animation-delay:.06s" x-data="{ q: '', cat: 'all', unit: 'all' }">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:10px">
                    <h2 class="vy-card-title">Expense history</h2>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <input type="search" x-model="q" placeholder="Search description or category" aria-label="Search expenses"
                               style="padding:8px 12px;border-radius:var(--vy-r-sm);border:1px solid var(--vy-glass-border);background:rgba(255,255,255,.6);font-size:13px;font-family:var(--vy-sans)">
                        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                            <a class="vy-btn vy-btn--ghost" href="{{ route('owner.expenses.export') }}" title="Export your expenses as CSV">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                Export
                            </a>
                        @endownercan
                    </div>
                </div>

                {{-- Unit filter — REAL owner units that have expenses --}}
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                    <button type="button" class="vy-pill" :class="{ 'is-active': unit === 'all' }" @click="unit = 'all'">All units</button>
                    @foreach ($units as $u)
                        <button type="button" class="vy-pill" :class="{ 'is-active': unit === @js($u['id']) }" @click="unit = @js($u['id'])">{{ $u['name'] }}</button>
                    @endforeach
                </div>
                {{-- Category filter — the REAL enum --}}
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
                                <th>Description</th>
                                <th>Vendor</th>
                                <th>Unit</th>
                                <th class="vy-num">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($expenses as $e)
                                <tr x-show="(cat === 'all' || cat === @js($e['category'])) && (unit === 'all' || unit === @js($e['unit']['id'])) && (q === '' || @js(mb_strtolower($e['category_label'].' '.$e['description'].' '.$e['unit']['name'].' '.$e['deductible']['label'])).includes(q.toLowerCase()))">
                                    <td class="vy-muted" style="white-space:nowrap">{{ $e['date_display'] }}</td>
                                    <td>
                                        <div style="font-weight:600">{{ $e['description'] }}</div>
                                        <div class="vy-row-sub">
                                            {{ $e['category_label'] }}@if ($e['source_label']) · {{ $e['source_label'] }}@endif
                                        </div>
                                        {{-- REAL: a void is an append-only reversal — the row stays, honestly badged --}}
                                        @if ($e['voided'])
                                            <span class="vy-status vy-status--draft" data-voided="1" style="margin-top:4px"><span class="vy-dot"></span>Voided</span>
                                        @endif
                                    </td>
                                    <td style="white-space:nowrap">@if ($e['vendor']){{ $e['vendor'] }}@else<span class="vy-muted">—</span>@endif</td>
                                    <td style="white-space:nowrap">{{ $e['unit']['name'] }}</td>
                                    <td class="vy-num vy-mono">{{ $e['amount_display'] }}</td>
                                    <td>
                                        {{-- REAL tri-state: true / false / NULL — never coerced --}}
                                        <span class="vy-status vy-status--{{ $e['deductible']['tone'] }}" data-deductible="{{ $e['deductible']['key'] }}"><span class="vy-dot"></span>{{ $e['deductible']['label'] }}</span>
                                        @if ($e['statement'])
                                            <div class="vy-row-sub"><a href="{{ route('owner.statements.show', $e['statement']['id']) }}" style="color:var(--vy-accent);font-weight:600">{{ $e['statement']['label'] }}</a></div>
                                        @elseif ($e['flag_note'])
                                            <div class="vy-row-sub">{{ $e['flag_note'] }}</div>
                                        @else
                                            <div class="vy-row-sub vy-muted">Not yet on a statement</div>
                                        @endif
                                    </td>
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
            {{-- By category — DERIVED: Σ of the real rows, voided excluded, per currency (Batch-B shared bars) --}}
            <section class="vy-card vy-fade" style="animation-delay:.05s">
                <span class="vy-eyebrow">By category</span>
                @forelse ($byCurrency as $block)
                    <div style="margin-top:12px">
                        @if ($multiCurrency)
                            <div class="vy-eyebrow" style="display:block;margin-bottom:2px">{{ $block['currency'] }}</div>
                        @endif
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
                    Totals exclude voided expenses{{ $multiCurrency ? ' and are shown per currency — never converted' : '' }}.
                </div>
            </section>

            {{-- How expenses work — a plain description of the REAL backend behaviour (no figures, no state) --}}
            <section class="vy-card vy-fade" style="animation-delay:.09s">
                <span class="vy-eyebrow">How expenses work</span>
                <div style="margin-top:10px;font-size:12.5px;color:var(--vy-muted);line-height:1.7">
                    A <strong style="color:var(--vy-ink)">deductible</strong> cost is charged to your monthly statement for the period it falls in.
                    A <strong style="color:var(--vy-ink)">non-deductible</strong> cost is your operator's and never reaches your ledger.
                    <strong style="color:var(--vy-ink)">Pending review</strong> means deductibility hasn't been resolved yet.
                    Which is which is set by your management agreement — not by you.
                </div>
            </section>

            {{-- Need something fixed? — a REAL relay into the owner↔operator channel (never an expense write) --}}
            <section class="vy-card vy-ex-relay vy-fade" style="animation-delay:.12s" x-data="{ open: false }">
                <span class="vy-eyebrow">Need something fixed?</span>
                <div class="vy-line-sub" style="margin-top:6px">Send a repair request to your management team — they'll raise the work order, and the cost appears here once it's resolved.</div>
                <div x-show="!open" style="margin-top:12px">
                    <button type="button" class="vy-btn vy-btn--accent" @click="open = true" style="gap:6px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2-2z"/></svg>
                        Request a repair
                    </button>
                </div>
                <form x-show="open" x-cloak method="POST" action="{{ route('owner.messages.start') }}" data-guard-submit style="margin-top:12px">
                    @csrf
                    <input type="hidden" name="subject" value="Repair request">
                    <textarea name="body" rows="3" required x-ref="repairBody" placeholder="Describe what needs fixing (and which unit)…"></textarea>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:9px">
                        @foreach (['Please arrange a plumber', 'The air-conditioning needs a service', 'Can someone check the appliances?'] as $chip)
                            <button type="button" @click="$refs.repairBody.value = @js($chip)" style="padding:5px 10px;border-radius:var(--vy-r-pill);background:rgba(255,255,255,.7);border:1px solid rgba(120,70,40,.16);color:#7A2E08;font-family:var(--vy-sans);font-size:11px;font-weight:500;cursor:pointer">{{ $chip }}</button>
                        @endforeach
                    </div>
                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-top:12px">
                        <button type="button" @click="open = false" style="padding:7px 14px;border-radius:var(--vy-r-pill);background:transparent;border:1px solid rgba(120,70,40,.16);color:#6B5D54;font-family:var(--vy-sans);font-size:12.5px;font-weight:500;cursor:pointer">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent" style="padding:7px 16px;font-size:12.5px;gap:6px">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                            Send request
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-owner.shell>
