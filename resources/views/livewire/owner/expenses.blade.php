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
      • REAL (Wave C · #68) — the owner APPROVE / QUERY workflow + the "Awaiting your approval" section/KPI:
                    a pending DEDUCTIBLE expense is NOT charged until you APPROVE (posts the real ledger
                    deduction → net drops, reconciling #17) or QUERY (nothing charged + opens the relay),
                    CAP_ACTIONS-gated + audited. The read-only "Pending review" count (the `deductible`
                    tri-state — deductibility is set by the management agreement, not owner-approved) is a
                    SEPARATE axis, shown alongside. Vendor is a REAL column (owner_expenses.vendor) — the
                    supplier a cost was paid to, shown when captured, honestly "—" otherwise.

    RAW-BLADE RULE: every `@` follows a space, newline or tag; inline conditions are ternaries in {{ }}.
--}}
<x-owner.shell active="expenses" :title="__('livewire/owner/expenses.title')" :subtitle="__('livewire/owner/expenses.subtitle')" :viewer="$viewer">
    <style>
        /* PAGE-SCOPED contrast overrides. The KPI band and the rows are translucent over the hero gradient,
           so these shell greys composite onto a saturated peach (≈236,153,110) and measure 3.05–4.35 — the
           O14/O17 case where no grey reaches AA on a tint. The shell's own definitions are NOT touched:
           retuning them would change every owner page. Hierarchy stays with size + weight. */
        .vy-kpi-label, .vy-kpi-sub, .vy-line-sub, .vy-row-sub { color: var(--vy-ink); }
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
                    <div class="vy-kpi-label">{{ __('livewire/owner/expenses.spent_this_year') }}</div>
                    <div class="vy-kpi-val">{{ $metrics['spent_display'] }}</div>
                    <div class="vy-kpi-sub">{{ trans_choice($multiCurrency ? 'livewire/owner/expenses.spent_sub_currency' : 'livewire/owner/expenses.spent_sub', $metrics['spent_count'], ['count' => $metrics['spent_count'], 'year' => $metrics['year'], 'currency' => $currency]) }}</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">{{ __('livewire/owner/expenses.awaiting_approval') }}</div>
                    <div class="vy-kpi-val">{{ $metrics['awaiting_display'] }}</div>
                    <div class="vy-kpi-sub">{{ __('livewire/owner/expenses.awaiting_sub', ['number' => $metrics['awaiting_count']]) }}</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">{{ __('livewire/owner/expenses.charged_to_payouts') }}</div>
                    <div class="vy-kpi-val">{{ $metrics['charged_display'] }}</div>
                    <div class="vy-kpi-sub">{{ __('livewire/owner/expenses.charged_sub') }}</div>
                </div>
            </div>

            {{-- Needs your approval (Wave C · GATE 2) — REAL pending-approval expenses. Approve → charged to your
                 payout (reduces net); Query → held out of net + opens the owner↔operator relay. CAP_ACTIONS. --}}
            @if ($approvalRows)
                <section class="vy-card vy-fade" style="animation-delay:.03s;border-left:3px solid var(--vy-amber)">
                    <h2 class="vy-card-title">{{ __('livewire/owner/expenses.needs_approval') }}</h2>
                    <div class="vy-line-sub" style="margin-bottom:8px">{{ __('livewire/owner/expenses.needs_approval_body') }}</div>
                    @foreach ($approvalRows as $a)
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-top:1px solid var(--vy-hairline);flex-wrap:wrap">
                            <div style="min-width:0">
                                <div style="font-weight:600;font-size:13px">{{ $a['description'] }} <span class="vy-mono" style="color:var(--vy-ink)">{{ $a['amount_display'] }}</span></div>
                                <div class="vy-row-sub">{{ $a['date_display'] }} · {{ $a['category_label'] }} · {{ $a['unit']['name'] }}@if ($a['vendor']) · {{ $a['vendor'] }}@endif</div>
                            </div>
                            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                <div style="display:flex;gap:8px;flex-shrink:0" x-data="{ q: false }">
                                    <form method="POST" action="{{ route('owner.expenses.approve', $a['id']) }}" style="margin:0">
                                        @csrf
                                        <button type="submit" class="vy-btn vy-btn--accent" style="padding:6px 14px;font-size:12px">{{ __('livewire/owner/expenses.approve') }}</button>
                                    </form>
                                    <button type="button" class="vy-btn vy-btn--ghost" style="padding:6px 14px;font-size:12px" @click="q = !q">{{ __('livewire/owner/expenses.query') }}</button>
                                    <form method="POST" action="{{ route('owner.expenses.query', $a['id']) }}" x-show="q" x-cloak style="margin:0;display:flex;gap:6px;align-items:center">
                                        @csrf
                                        <label class="vy-sr-only" for="vy-lbl-expenses-note">{{ __('livewire/owner/expenses.question_placeholder') }}</label>
                                        <input id="vy-lbl-expenses-note" type="text" name="note" placeholder="{{ __('livewire/owner/expenses.question_placeholder') }}" maxlength="1000" class="vy-input" style="font-size:12px;max-width:220px">
                                        <button type="submit" class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12px">{{ __('livewire/owner/expenses.send') }}</button>
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
                    <h2 class="vy-card-title">{{ __('livewire/owner/expenses.pending_review') }}</h2>
                    <div class="vy-line-sub" style="margin-bottom:6px">{{ __('livewire/owner/expenses.pending_review_body') }}</div>
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
                    <h2 class="vy-card-title">{{ __('livewire/owner/expenses.expense_history') }}</h2>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <input type="search" x-model="q" placeholder="{{ __('livewire/owner/expenses.search_placeholder') }}" aria-label="{{ __('livewire/owner/expenses.search_label') }}"
                               style="padding:8px 12px;border-radius:var(--vy-r-sm);border:1px solid var(--vy-glass-border);background:rgba(255,255,255,.6);font-size:13px;font-family:var(--vy-sans)">
                        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                            <a class="vy-btn vy-btn--ghost" href="{{ route('owner.expenses.export') }}" title="{{ __('livewire/owner/expenses.export_title') }}">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                {{ __('common.export') }}
                            </a>
                        @endownercan
                    </div>
                </div>

                {{-- Unit filter — REAL owner units that have expenses --}}
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                    <button type="button" class="vy-pill" :class="{ 'is-active': unit === 'all' }" @click="unit = 'all'">{{ __('livewire/owner/expenses.all_units') }}</button>
                    @foreach ($units as $u)
                        <button type="button" class="vy-pill" :class="{ 'is-active': unit === @js($u['id']) }" @click="unit = @js($u['id'])">{{ $u['name'] }}</button>
                    @endforeach
                </div>
                {{-- Category filter — the REAL enum --}}
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                    <button type="button" class="vy-pill" :class="{ 'is-active': cat === 'all' }" @click="cat = 'all'">{{ __('common.all') }}</button>
                    @foreach ($categories as $key => $label)
                        <button type="button" class="vy-pill" :class="{ 'is-active': cat === @js($key) }" @click="cat = @js($key)">{{ $label }}</button>
                    @endforeach
                </div>

                <div style="overflow-x:auto">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('livewire/owner/expenses.col_description') }}</th>
                                <th>{{ __('livewire/owner/expenses.col_vendor') }}</th>
                                <th>{{ __('livewire/owner/expenses.col_unit') }}</th>
                                <th class="vy-num">{{ __('livewire/owner/expenses.col_amount') }}</th>
                                <th>{{ __('common.status') }}</th>
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
                                            <span class="vy-status vy-status--draft" data-voided="1" style="margin-top:4px"><span class="vy-dot"></span>{{ __('livewire/owner/expenses.voided') }}</span>
                                        @endif
                                    </td>
                                    <td style="white-space:nowrap">@if ($e['vendor']){{ $e['vendor'] }}@else<span class="vy-muted">—</span>@endif</td>
                                    <td style="white-space:nowrap">{{ $e['unit']['name'] }}</td>
                                    <td class="vy-num vy-mono">{{ $e['amount_display'] }}</td>
                                    <td>
                                        {{-- REAL tri-state: true / false / NULL — never coerced --}}
                                        <span class="vy-status vy-status--{{ $e['deductible']['tone'] }}" data-deductible="{{ $e['deductible']['key'] }}"><span class="vy-dot"></span>{{ $e['deductible']['label'] }}</span>
                                        @if ($e['statement'])
                                            <div class="vy-row-sub"><a href="{{ route('owner.statements.show', $e['statement']['id']) }}" style="color:var(--vy-accent-text);font-weight:600">{{ $e['statement']['label'] }}</a></div>
                                        @elseif ($e['flag_note'])
                                            <div class="vy-row-sub">{{ $e['flag_note'] }}</div>
                                        @else
                                            <div class="vy-row-sub vy-muted">{{ __('livewire/owner/expenses.not_on_statement') }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="vy-empty">{{ __('livewire/owner/expenses.no_expenses') }}</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($metrics['voided_count'] > 0)
                    <div class="vy-fx-note">
                        {{ trans_choice('livewire/owner/expenses.voided_note', $metrics['voided_count'], ['count' => $metrics['voided_count']]) }}
                    </div>
                @endif
            </section>
        </div>

        {{-- ── Right rail ── --}}
        <div class="vy-stack">
            {{-- By category — DERIVED: Σ of the real rows, voided excluded, per currency (Batch-B shared bars) --}}
            <section class="vy-card vy-fade" style="animation-delay:.05s">
                <span class="vy-eyebrow">{{ __('livewire/owner/expenses.by_category') }}</span>
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
                                        <span class="vy-line-sub" style="display:block">{{ trans_choice('livewire/owner/expenses.category_sub', $cat['count'], ['count' => $cat['count'], 'percent' => $cat['bar_width']]) }}</span>
                                    </span>
                                    <span style="font-family:var(--vy-display);font-weight:600;font-variant-numeric:tabular-nums">{{ $cat['total_display'] }}</span>
                                </div>
                                <div class="vy-track" style="margin-top:8px"><span style="width:{{ $cat['bar_width'] }}%"></span></div>
                            </div>
                        @endforeach
                        <div class="vy-list-row" style="border-top:1.5px solid rgba(120,70,40,.16)">
                            <span style="font-size:13px;font-weight:600">{{ __('common.total') }}</span>
                            <span style="font-family:var(--vy-display);font-weight:600;font-variant-numeric:tabular-nums;color:var(--vy-accent-text)">{{ $block['total_display'] }}</span>
                        </div>
                    </div>
                @empty
                    <div class="vy-empty">{{ __('livewire/owner/expenses.no_expenses') }}</div>
                @endforelse

                <div class="vy-fx-note">
                    {{ $multiCurrency ? __('livewire/owner/expenses.totals_note_multi') : __('livewire/owner/expenses.totals_note') }}
                </div>
            </section>

            {{-- How expenses work — a plain description of the REAL backend behaviour (no figures, no state) --}}
            <section class="vy-card vy-fade" style="animation-delay:.09s">
                <span class="vy-eyebrow">{{ __('livewire/owner/expenses.how_it_works') }}</span>
                <div style="margin-top:10px;font-size:12.5px;color:var(--vy-muted);line-height:1.7">
                    {!! __('livewire/owner/expenses.how_it_works_body') !!}
                </div>
            </section>

            {{-- Need something fixed? — a REAL relay into the owner↔operator channel (never an expense write) --}}
            <section class="vy-card vy-ex-relay vy-fade" style="animation-delay:.12s" x-data="{ open: false }">
                <span class="vy-eyebrow">{{ __('livewire/owner/expenses.need_fixed') }}</span>
                <div class="vy-line-sub" style="margin-top:6px">{{ __('livewire/owner/expenses.need_fixed_body') }}</div>
                <div x-show="!open" style="margin-top:12px">
                    <button type="button" class="vy-btn vy-btn--accent" @click="open = true" style="gap:6px">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2-2z"/></svg>
                        {{ __('livewire/owner/expenses.request_repair') }}
                    </button>
                </div>
                <form x-show="open" x-cloak method="POST" action="{{ route('owner.messages.start') }}" data-guard-submit style="margin-top:12px">
                    @csrf
                    <input type="hidden" name="subject" value="Repair request">
                    <label class="vy-sr-only" for="vy-lbl-expenses-body">{{ __('livewire/owner/expenses.repair_placeholder') }}</label>
                    <textarea id="vy-lbl-expenses-body" name="body" rows="3" required x-ref="repairBody" placeholder="{{ __('livewire/owner/expenses.repair_placeholder') }}"></textarea>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:9px">
                        @foreach ([__('livewire/owner/expenses.chip_plumber'), __('livewire/owner/expenses.chip_air_conditioning'), __('livewire/owner/expenses.chip_appliances')] as $chip)
                            <button type="button" @click="$refs.repairBody.value = @js($chip)" style="padding:5px 10px;border-radius:var(--vy-r-pill);background:rgba(255,255,255,.7);border:1px solid rgba(120,70,40,.16);color:#7A2E08;font-family:var(--vy-sans);font-size:11px;font-weight:500;cursor:pointer">{{ $chip }}</button>
                        @endforeach
                    </div>
                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-top:12px">
                        <button type="button" @click="open = false" style="padding:7px 14px;border-radius:var(--vy-r-pill);background:transparent;border:1px solid rgba(120,70,40,.16);color:#6B5D54;font-family:var(--vy-sans);font-size:12.5px;font-weight:500;cursor:pointer">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-btn vy-btn--accent" style="padding:7px 16px;font-size:12.5px;gap:6px">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                            {{ __('livewire/owner/expenses.send_request') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-owner.shell>
