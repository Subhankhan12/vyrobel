{{--
    Owner Payouts (exact-match pass) — the certified Payout backend, rebuilt to the decoded "Owner Payouts"
    wireframe and wired to LIVE owner-scoped data. Shell from <x-owner.shell>.

    ⚠️ REAL MONEY + REAL BANKING — nothing is faked. HONESTY MAP (see App\Livewire\Owner\OwnerPayouts):
      • REAL      — payout history / KPIs / the next-payout breakdown (gross − 18% − fees − refund == net, #17) /
                    the lifecycle stepper (REAL states only — no invented "Scheduled"/"On hold") / the remittance
                    PDF per row / the Export CSV / the payout-account masked IBAN (#28, CAP_PAYOUT only) / the
                    payout preferences (a real owner-scoped store — a PREFERENCE, never a schedule/date).
      • REAL HOLD — the on-hold banner shows ONLY the real tax-hold (a pending payout + no tax details); the
                    wireframe's "verification pending" bank-verification hold is account-gated + never faked.
      • HONEST    — the upcoming-schedule PROJECTIONS have no forecast engine → an honest "not yet available".

    Page-scoped CSS (.vy-po-*) lives here, NOT the shared owner layout (class names must not leak into every
    owner page's <style>, where isolation tests assert the ABSENCE of certain words).
--}}
<x-owner.shell active="payouts" :title="__('livewire/owner/payouts.title')" :viewer="$viewer">
    <style>
        /* Held-payout banner (the real tax-hold). */
        .vy-po-hold { display: flex; align-items: flex-start; gap: 15px; background: rgba(255, 252, 248, .62); border: 1px solid var(--vy-accent-border); border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow); padding: 18px 20px; margin-bottom: 20px; }
        .vy-po-hold-ic { flex: none; width: 42px; height: 42px; border-radius: var(--vy-r-md); background: var(--vy-grad); color: #fff; display: grid; place-items: center; box-shadow: 0 5px 14px rgba(194, 65, 12, .3); }
        .vy-po-hold-body { flex: 1; min-width: 0; }
        .vy-po-hold-title { font-family: var(--vy-display); font-weight: 600; font-size: 16px; }
        .vy-po-hold-sub { font-size: 12.5px; color: var(--vy-muted); margin-top: 3px; }
        .vy-po-hold-cta { display: inline-flex; align-items: center; gap: 6px; margin-top: 12px; padding: 9px 15px; border-radius: var(--vy-r-pill); background: var(--vy-grad); color: #fff; font-size: 12.5px; font-weight: 500; text-decoration: none; box-shadow: 0 5px 14px rgba(194, 65, 12, .3); }
        .vy-po-hold-cta:hover { transform: translateY(-1px); }

        /* Payout-account card. */
        .vy-po-acct { display: flex; align-items: center; gap: 12px; margin-top: 12px; }
        .vy-po-acct-ic { flex: none; width: 40px; height: 40px; border-radius: var(--vy-r-md); background: var(--vy-grad); color: #fff; display: grid; place-items: center; }
        .vy-po-acct-name { font-size: 13.5px; font-weight: 600; }
        .vy-po-acct-sub { font-size: 12px; color: var(--vy-muted); margin-top: 2px; font-variant-numeric: tabular-nums; }

        /* Payout-preferences form — segmented radio pills. */
        /* ⚠️ was the prototype's pre-AA #9C9089 — measured 2.66 on this card. */
        .vy-po-pref-label { font-size: 11px; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); margin: 14px 0 7px; }
        .vy-po-pref-label:first-child { margin-top: 4px; }
        .vy-po-seg { display: flex; gap: 4px; padding: 4px; border-radius: var(--vy-r-pill); background: rgba(120, 70, 40, .06); border: 1px solid var(--vy-glass-border); flex-wrap: wrap; }
        .vy-po-seg-opt { flex: 1; min-width: 54px; }
        .vy-po-seg-opt input { position: absolute; opacity: 0; pointer-events: none; }
        .vy-po-seg-opt span { display: block; text-align: center; padding: 7px 10px; border-radius: var(--vy-r-pill); font-size: 12px; font-weight: 500; color: var(--vy-muted); cursor: pointer; transition: background .15s, color .15s, box-shadow .15s; white-space: nowrap; }
        .vy-po-seg-opt input:checked + span { background: #fff; color: var(--vy-ink); font-weight: 600; box-shadow: var(--vy-shadow-sm); }
        .vy-po-seg-opt input:focus-visible + span { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-po-toggle-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--vy-hairline); }
        .vy-po-switch { position: relative; width: 42px; height: 24px; flex: none; }
        .vy-po-switch input { position: absolute; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer; }
        .vy-po-switch span { position: absolute; inset: 0; border-radius: 20px; background: rgba(120, 70, 40, .2); transition: background .18s; }
        .vy-po-switch span::after { content: ""; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, .2); transition: transform .18s; }
        .vy-po-switch input:checked + span { background: var(--vy-grad); }
        .vy-po-switch input:checked + span::after { transform: translateX(18px); }
        .vy-po-pref-note { display: flex; align-items: flex-start; gap: 7px; margin-top: 14px; font-size: 11.5px; color: var(--vy-faint); line-height: 1.5; }
        .vy-po-pref-ro { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; }
        .vy-po-pref-ro:last-child { border-bottom: none; }
        .vy-po-pref-ro .v { font-weight: 600; }
    </style>

    {{-- TEST-MODE boundary — the automated payout engine runs in test mode; never implies real money moves. --}}
    <x-owner.payout-test-mode :show="$testMode" />

    {{-- Flash --}}
    @if (session('payout_pref_notice'))
        <div class="vy-flash is-ok" style="margin-bottom:18px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            {{ session('payout_pref_notice') }}
        </div>
    @endif

    {{-- DO3-3 — the retry outcome. Deliberately reports what actually happened (queued, or already on its
         way, or no longer failed) rather than a blanket success: on a money surface a message that is true
         only sometimes is worse than none. --}}
    {{-- ⚠️ ONE component decides the outcome's skin. This block used to hardcode `is-ok` + a tick for
         EVERY message, so "finish setting up your payout account first" — an instruction the owner must
         act on or no money arrives — arrived as a green completion. --}}
    @if (session('payout_retry_notice'))
        <x-owner.flash-notice :message="session('payout_retry_notice')"
                              :outcome="session('payout_retry_outcome', 'ok')" />
    @endif

    {{-- On-hold banner — REAL tax-hold only (a pending payout held for missing tax details); never a fake
         "verification pending". Shown only when a real hold exists. --}}
    @if ($hold)
        <div class="vy-po-hold vy-fade">
            <span class="vy-po-hold-ic">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
            </span>
            <div class="vy-po-hold-body">
                <div class="vy-po-hold-title">{{ __('livewire/owner/payouts.hold_title', ['amount' => $hold['amount_display']]) }}</div>
                <div class="vy-po-hold-sub">{{ $hold['reason'] }}</div>
                <a class="vy-po-hold-cta" href="{{ $hold['cta_route'] }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    {{ $hold['cta_label'] }}
                </a>
            </div>
        </div>
    @endif

    {{-- DO3-3 — FAILED-PAYOUT ALERT + RECOVERY. Rendered only for a genuinely `failed` payout; every other
         state renders nothing here. Reuses the hold banner's treatment (same tokens, so contrast is the
         measured one) rather than introducing a second alert style.
         ⚠️ WHAT IS OFFERED IS A RE-QUEUE, NOT A SEND — the copy says so, because the owner has no payout
         rail and this control gives them none.
         ⚠️ NO ETA. The wireframe's "retrying · est. {time}" has no source in the product and stays refused
         (#39); the reason line is the rail's own stored text, verbatim, or nothing.
         ⚠️ Three states, all real: already-replaced (no control — a second live payout for one statement is
         a double payment) · blocked (what to FIX, routed, instead of a doomed re-send) · retryable. --}}
    @if ($recovery)
        <div class="vy-po-hold vy-fade" data-testid="payout-failure-alert">
            <span class="vy-po-hold-ic">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
            </span>
            <div class="vy-po-hold-body">
                <div class="vy-po-hold-title">
                    @if ($recovery['period_label'])
                        {{ __('livewire/owner/payouts.failed_title_period', ['amount' => $recovery['amount_display'], 'period' => $recovery['period_label']]) }}
                    @else
                        {{ __('livewire/owner/payouts.failed_title', ['amount' => $recovery['amount_display']]) }}
                    @endif
                </div>
                <div class="vy-po-hold-sub">
                    @if ($recovery['reason'])
                        {{ $recovery['reason'] }}
                    @else
                        {{ __('livewire/owner/payouts.failed_default_reason') }}
                    @endif
                    @if ($recovery['failed_on']) <span style="white-space:nowrap">{{ __('livewire/owner/payouts.attempted_on', ['date' => $recovery['failed_on']]) }}</span>@endif
                </div>

                @if ($recovery['already_queued'])
                    {{-- A live payout already claims this statement — a further retry would be a second payment. --}}
                    <div class="vy-po-hold-sub" style="margin-top:8px">
                        {{ __('livewire/owner/payouts.already_queued') }}
                    </div>
                @elseif ($recovery['blockers'])
                    {{-- FIX-then-retry: re-sending now would fail for the same reason. --}}
                    @foreach ($recovery['blockers'] as $blocker)
                        <div class="vy-po-hold-sub" style="margin-top:8px"><strong>{{ $blocker['title'] }}</strong> — {{ $blocker['detail'] }}</div>
                        @if ($recovery['can_act'])
                            <a class="vy-po-hold-cta" href="{{ $blocker['cta_route'] }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                {{ $blocker['cta_label'] }}
                            </a>
                        @endif
                    @endforeach
                @elseif ($recovery['paused'])
                    {{-- DEC-n (2026-10-02) — a pause stops every payout, a retry included: say so, and how to undo it --}}
                    <div class="vy-po-hold-sub" style="margin-top:8px">
                        {{ __('livewire/owner/payouts.retry_paused') }}
                    </div>
                @elseif ($recovery['can_act'] && $recovery['can_retry'])
                    <div class="vy-po-hold-sub" style="margin-top:8px">
                        {{ __('livewire/owner/payouts.retry_explainer') }}
                    </div>
                    {{-- data-guard-submit disables the button on submit: defence in depth ON TOP of the
                         server-side row-lock + one-live-payout-per-statement guard, never instead of it. --}}
                    <form method="POST" action="{{ $recovery['retry_route'] }}" data-guard-submit style="margin:0">
                        @csrf
                        <button type="submit" class="vy-po-hold-cta" style="border:none;cursor:pointer">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg>
                            {{ __('livewire/owner/payouts.retry_button') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <div class="vy-cols">
        {{-- ── Main ── --}}
        <div class="vy-stack">
            {{-- KPIs — all REAL --}}
            <div class="vy-kpi-grid vy-fade" style="animation-delay:.02s">
                <div class="vy-kpi">
                    <div class="vy-kpi-label">{{ __('livewire/owner/payouts.kpi_paid_this_year') }}</div>
                    <div class="vy-kpi-val">{{ $metrics['paid_this_year_display'] }}</div>
                    <div class="vy-kpi-sub">{{ trans_choice('livewire/owner/payouts.payout_count', $metrics['paid_count'], ['count' => $metrics['paid_count']]) }}@if ($metrics['paid_year']) · {{ $metrics['paid_year'] }}@endif</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">{{ __('livewire/owner/payouts.next_payout') }}</div>
                    <div class="vy-kpi-val">{{ $metrics['next_payout_display'] }}</div>
                    <div class="vy-kpi-sub">{{ $metrics['pending_count'] > 0 ? __('livewire/owner/payouts.awaiting_disbursement') : __('livewire/owner/payouts.nothing_pending') }}</div>
                </div>
                <div class="vy-kpi">
                    {{-- The wireframe puts a state pill beside this label. It shows the REAL hold only —
                         the same $hold the banner is driven by — so it can never claim a state the row
                         is not actually in, and it is absent when nothing is held. --}}
                    <div class="vy-kpi-label" style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                        <span>{{ __('livewire/owner/payouts.kpi_pending') }}</span>
                        @if ($hold)
                            <span class="vy-status vy-status--pending" style="text-transform:none;letter-spacing:0"><span class="vy-dot"></span>{{ __('livewire/owner/payouts.on_hold') }}</span>
                        @endif
                    </div>
                    <div class="vy-kpi-val">{{ $metrics['pending_total_display'] }}</div>
                    <div class="vy-kpi-sub">{{ trans_choice('livewire/owner/payouts.payout_count', $metrics['pending_count'], ['count' => $metrics['pending_count']]) }}</div>
                </div>
            </div>

            {{-- Payout history — REAL rows; search + status filter client-side; Export = a real CSV (CAP_EXPORTS) --}}
            <section class="vy-card vy-fade" style="animation-delay:.06s" x-data="{ q: '', status: 'all' }">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;margin-bottom:10px">
                    <div>
                        <h2 class="vy-card-title">{{ __('livewire/owner/payouts.history_title') }}</h2>
                        {{-- The wireframe's summary line under the title. ⚠️ It REUSES the figures the KPI tiles
                             already show ($metrics) — it does NOT re-sum the rows, which would be a second money
                             computation of the same total (the anti-drift rule). Omitted entirely when there is
                             nothing paid and nothing pending, rather than printing a pair of zeros. --}}
                        @if ($metrics['paid_count'] > 0 || $metrics['pending_count'] > 0)
                            <div class="vy-line-sub" style="margin-top:4px">
                                @if ($metrics['pending_count'] > 0)
                                    {{ trans_choice('livewire/owner/payouts.history_summary_pending', $metrics['paid_count'], ['count' => $metrics['paid_count'], 'paid' => $metrics['paid_this_year_display'], 'pending' => $metrics['pending_total_display']]) }}
                                @else
                                    {{ trans_choice('livewire/owner/payouts.history_summary', $metrics['paid_count'], ['count' => $metrics['paid_count'], 'paid' => $metrics['paid_this_year_display']]) }}
                                @endif
                            </div>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        {{-- WAVE 3 — the YEAR filter. distinctYears() already computed the owner's REAL payout
                             years and nothing rendered them. Real years only (never a fabricated range), and
                             only when there is more than one to choose between. A plain GET link — a
                             wire-action would bypass the owner middleware (#20). --}}
                        @if (count($years) > 1)
                            <span style="display:inline-flex;gap:4px">
                                <a href="{{ route('owner.payouts') }}"
                                   style="font-size:12px;padding:5px 11px;border-radius:999px;text-decoration:none;{{ $activeYear === null ? 'background:var(--vy-accent);color:#fff;font-weight:600' : 'color:var(--vy-muted);border:1px solid var(--vy-glass-border)' }}">{{ __('common.all') }}</a>
                                @foreach ($years as $y)
                                    <a href="{{ route('owner.payouts', ['year' => $y]) }}"
                                       style="font-size:12px;padding:5px 11px;border-radius:999px;text-decoration:none;{{ $activeYear === $y ? 'background:var(--vy-accent);color:#fff;font-weight:600' : 'color:var(--vy-muted);border:1px solid var(--vy-glass-border)' }}">{{ $y }}</a>
                                @endforeach
                            </span>
                        @endif
                        <input type="search" x-model="q" placeholder="{{ __('livewire/owner/payouts.search_placeholder') }}" aria-label="{{ __('livewire/owner/payouts.search_label') }}"
                               style="padding:8px 12px;border-radius:var(--vy-r-sm);border:1px solid var(--vy-glass-border);background:rgba(255,255,255,.6);font-size:13px;font-family:var(--vy-sans)">
                        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                            <a class="vy-btn vy-btn--ghost" href="{{ route('owner.payouts.export') }}" title="{{ __('livewire/owner/payouts.export_title') }}">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                {{ __('common.export') }}
                            </a>
                        @endownercan
                    </div>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                    <button type="button" class="vy-pill" :class="{ 'is-active': status === 'all' }" @click="status = 'all'">{{ __('common.all') }}</button>
                    @foreach ($statuses as $st)
                        <button type="button" class="vy-pill" :class="{ 'is-active': status === '{{ $st }}' }" @click="status = '{{ $st }}'">{{ $statusLabels[$st] ?? ucfirst($st) }}</button>
                    @endforeach
                </div>

                <div style="overflow-x:auto">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>{{ __('livewire/owner/payouts.col_month') }}</th>
                                <th>{{ __('livewire/owner/payouts.col_rail') }}</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th class="vy-num">{{ __('livewire/owner/payouts.col_amount') }}</th>
                                <th aria-label="{{ __('livewire/owner/payouts.col_links') }}"></th>
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
                                            <a href="{{ route('owner.statements.show', $p['statement_id']) }}" style="color:var(--vy-accent-text);font-size:12.5px;font-weight:600">{{ __('livewire/owner/payouts.statement_link') }}</a>
                                        @endif
                                        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                                            <a href="{{ route('owner.payouts.remittance', $p['id']) }}" class="vy-btn vy-btn--ghost" style="margin-left:8px;padding:5px 10px;font-size:12px" title="{{ __('livewire/owner/payouts.remittance_title') }}">{{ __('livewire/owner/payouts.remittance_link') }}</a>
                                        @endownercan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="vy-empty">{{ __('livewire/owner/payouts.history_empty') }}</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        {{-- ── Right rail ── --}}
        <div class="vy-stack">
            {{-- Next payout — REAL amount + status + masked IBAN (CAP_PAYOUT) + lifecycle (real states) + breakdown --}}
            @if ($nextPayout)
                <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.05s">
                    <span class="vy-eyebrow">@if ($nextPayout['period_label']){{ __('livewire/owner/payouts.next_payout_period', ['period' => $nextPayout['period_label']]) }}@else{{ __('livewire/owner/payouts.next_payout') }}@endif</span>
                    <div class="vy-dark-figure" data-countup="{{ $nextPayout['amount_minor'] }}" data-symbol="{{ $nextPayout['currency_symbol'] }}">{{ $nextPayout['amount_display'] }}</div>
                    <span class="vy-status vy-status--{{ $nextPayout['status_meta']['tone'] }}" style="margin-top:6px"><span class="vy-dot"></span>{{ $nextPayout['status_meta']['label'] }} · {{ $nextPayout['rail_label'] }}</span>
                    @if ($payoutAccount['iban_masked'])
                        <div style="margin-top:12px">
                            <span class="vy-ov-iban">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg>
                                {{ __('livewire/owner/payouts.iban_masked', ['iban' => $payoutAccount['iban_masked']]) }}
                            </span>
                        </div>
                    @endif

                    <div class="vy-steps">
                        @foreach ($lifecycle['steps'] as $step)
                            <div class="vy-step is-{{ $step['state'] }}"><span class="vy-step-dot"></span><span class="vy-step-label">{{ $step['label'] }}</span></div>
                            @if (! $loop->last)<span class="vy-step-line"></span>@endif
                        @endforeach
                    </div>
                    @if ($lifecycle['note'])
                        <div style="margin-top:10px;font-size:11.5px;color:rgba(255,255,255,.6)">{{ $lifecycle['note'] }}</div>
                    @endif

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
                        <a href="{{ route('owner.statements.show', $nextPayout['statement_id']) }}" style="display:inline-block;margin-top:14px;font-size:12.5px;font-weight:600;color:var(--vy-peach)">{{ __('livewire/owner/payouts.view_statement') }}</a>
                    @endif
                </section>
            @else
                <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.05s">
                    <span class="vy-eyebrow">{{ __('livewire/owner/payouts.next_payout') }}</span>
                    <div style="margin-top:10px;font-size:13px;color:rgba(255,255,255,.7)">{{ __('livewire/owner/payouts.next_payout_empty') }}</div>
                </section>
            @endif

            {{-- Payout account — REAL default bank account (masked IBAN #28, CAP_PAYOUT only). Honest empty otherwise. --}}
            <section class="vy-card vy-fade" style="animation-delay:.09s">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="vy-eyebrow">{{ __('livewire/owner/payouts.payout_account') }}</span>
                    @if ($payoutAccount['can_manage'])
                        <a href="{{ route('owner.payout-method') }}" style="font-size:12.5px;font-weight:600;color:var(--vy-accent-text)">{{ __('livewire/owner/payouts.manage') }}</a>
                    @endif
                </div>
                @if ($payoutAccount['has_method'])
                    <div class="vy-po-acct">
                        <span class="vy-po-acct-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg></span>
                        <div>
                            <div class="vy-po-acct-name">{{ $payoutAccount['label'] }}</div>
                            <div class="vy-po-acct-sub">@if ($payoutAccount['iban_masked']){{ __('livewire/owner/payouts.iban_masked', ['iban' => $payoutAccount['iban_masked']]) }}@endif@if ($payoutAccount['country']) · {{ $payoutAccount['country'] }}@endif</div>
                        </div>
                    </div>
                @elseif ($payoutAccount['can_manage'])
                    <div class="vy-line-sub" style="margin-top:8px">{!! __('livewire/owner/payouts.no_bank_account', ['url' => e(route('owner.payout-method'))]) !!}</div>
                @else
                    <div style="font-size:13.5px;font-weight:500;margin-top:8px">{{ __('livewire/owner/payouts.bank_transfer') }}</div>
                    <div class="vy-line-sub">{{ __('livewire/owner/payouts.managed_by_owner') }}</div>
                @endif
            </section>

            {{-- Upcoming schedule — REAL: the automated engine's next run date for THIS owner, from their own
                 preferences via the shared PayoutSchedule (never a fabricated date, #39). TEST-MODE-labelled. --}}
            <section class="vy-card vy-fade" style="animation-delay:.12s">
                <span class="vy-eyebrow">{{ __('livewire/owner/payouts.next_scheduled') }}</span>
                <div style="margin-top:10px">
                    @if ($schedule['paused'])
                        <div class="vy-deferred">
                            <span class="vy-eyebrow">{{ __('livewire/owner/payouts.paused') }}</span>
                            {{ __('livewire/owner/payouts.schedule_paused') }}
                        </div>
                    @else
                        <div style="font-size:22px;font-weight:700;color:var(--vy-ink);letter-spacing:-.01em">{{ $schedule['next_date'] }}</div>
                        <div style="font-size:12.5px;color:var(--vy-muted);margin-top:4px">
                            {{ __('livewire/owner/payouts.schedule_runs', ['frequency' => $schedule['frequency_label']]) }}
                        </div>
                        @unless ($schedule['engine_enabled'])
                            <div style="font-size:11.5px;color:var(--vy-muted);margin-top:8px">
                                {{ __('livewire/owner/payouts.engine_off') }}
                            </div>
                        @endunless
                    @endif
                </div>
            </section>

            {{-- Payout preferences — a REAL owner-scoped store. Editable by a payout-capable owner (CAP_PAYOUT) via a
                 controller POST (#20); read-only for others. A PREFERENCE, not a schedule — labelled so (#39). --}}
            <section class="vy-card vy-fade" style="animation-delay:.15s">
                {{-- The wireframe carries a state pill beside this heading. It reflects the owner's REAL
                     stored pause preference — nothing else. --}}
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                    <span class="vy-eyebrow">{{ __('livewire/owner/payouts.preferences_title') }}</span>
                    <span class="vy-status vy-status--{{ $preferences['paused'] ? 'pending' : 'paid' }}" style="text-transform:none;letter-spacing:0">
                        <span class="vy-dot"></span>{{ $preferences['paused'] ? __('livewire/owner/payouts.paused') : __('livewire/owner/payouts.active') }}
                    </span>
                </div>
                @if ($preferences['can_manage'])
                    <form method="POST" action="{{ route('owner.payouts.preferences') }}" style="margin-top:6px">
                        @csrf
                        <div class="vy-po-pref-label">{{ __('livewire/owner/payouts.frequency') }}</div>
                        <div class="vy-po-seg">
                            {{-- WAVE 2 · #3 — ONE canonical list. These two pickers hardcoded their own arrays and had drifted:
                                 this one said "Every 2 weeks" (26 runs/yr) where the other said "Twice monthly", while
                                 PayoutSchedule actually runs the 1st and the 16th (24). --}}
                            @foreach (\App\Models\OwnerPayoutPreference::frequencyOptions() as $val => $label)
                                <label class="vy-po-seg-opt"><input type="radio" name="frequency" value="{{ $val }}" @checked($preferences['frequency'] === $val)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>

                        <div class="vy-po-pref-label">{!! __('livewire/owner/payouts.payout_day_hint') !!}</div>
                        <div class="vy-po-seg">
                            @foreach ([1 => __('livewire/owner/payouts.day_1st'), 2 => __('livewire/owner/payouts.day_2nd'), 3 => __('livewire/owner/payouts.day_3rd'), 4 => __('livewire/owner/payouts.day_4th'), 5 => __('livewire/owner/payouts.day_5th')] as $val => $label)
                                <label class="vy-po-seg-opt"><input type="radio" name="payout_day" value="{{ $val }}" @checked($preferences['payout_day'] === $val)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>

                        <div class="vy-po-pref-label">{{ __('livewire/owner/payouts.minimum_payout') }}</div>
                        <div class="vy-po-seg">
                            @foreach (['0' => __('livewire/owner/payouts.minimum_none'), '500' => $currencySymbol.'500', '1000' => $currencySymbol.'1k', '2500' => $currencySymbol.'2.5k'] as $val => $label)
                                <label class="vy-po-seg-opt"><input type="radio" name="minimum" value="{{ $val }}" @checked($preferences['minimum_minor'] === (int) $val * 100)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>

                        <div class="vy-po-pref-label">{{ __('livewire/owner/payouts.payout_currency') }}</div>
                        <div class="vy-po-pref-ro" style="border:none;padding-top:0"><span style="color:var(--vy-muted)">{{ __('livewire/owner/payouts.currency_set_by_region') }}</span><span class="v vy-mono">{{ $currency }}</span></div>

                        <div class="vy-po-toggle-row">
                            <div>
                                <div style="font-size:13px;font-weight:500">{{ __('livewire/owner/payouts.pause_payouts') }}</div>
                                <div class="vy-line-sub">{{ __('livewire/owner/payouts.pause_payouts_hint') }}</div>
                            </div>
                            <label class="vy-po-switch"><input type="checkbox" name="paused" value="1" @checked($preferences['paused'])><span></span></label>
                        </div>

                        <div class="vy-po-pref-note">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
                            <span>{{ __('livewire/owner/payouts.preference_note') }}</span>
                        </div>

                        {{-- ⚠️ RE-AUTH: pausing stops your payouts entirely, and a high minimum is a pause by another name. --}}
                        <div class="vy-field" style="margin:14px 0 0">
                            <label class="vy-field-label" for="payouts_prefs_pw">{{ __('livewire/owner/payouts.confirm_password') }}</label>
                            <input id="payouts_prefs_pw" type="password" name="current_password" class="vy-input"
                                   autocomplete="current-password" required aria-label="{{ __('livewire/owner/payouts.current_password') }}">
                            @error('current_password') <span style="color:#B4400F;font-size:11.5px">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;margin-top:14px">{{ __('livewire/owner/payouts.save_preferences') }}</button>
                    </form>
                @else
                    {{-- Read-only for a non-payout role (accountant / view-only): the values, not the editor. --}}
                    <div style="margin-top:8px">
                        <div class="vy-po-pref-ro"><span style="color:var(--vy-muted)">{{ __('livewire/owner/payouts.frequency') }}</span><span class="v">{{ $preferences['frequency_label'] }}</span></div>
                        <div class="vy-po-pref-ro"><span style="color:var(--vy-muted)">{{ __('livewire/owner/payouts.payout_day') }}</span><span class="v">{{ __('livewire/owner/payouts.payout_day_value', ['day' => $preferences['payout_day']]) }}</span></div>
                        <div class="vy-po-pref-ro"><span style="color:var(--vy-muted)">{{ __('livewire/owner/payouts.minimum_payout') }}</span><span class="v">{{ $preferences['minimum_display'] }}</span></div>
                        <div class="vy-po-pref-ro"><span style="color:var(--vy-muted)">{{ __('livewire/owner/payouts.payout_currency') }}</span><span class="v vy-mono">{{ $currency }}</span></div>
                        <div class="vy-po-pref-ro"><span style="color:var(--vy-muted)">{{ __('livewire/owner/payouts.payouts_state') }}</span><span class="v">{{ $preferences['paused'] ? __('livewire/owner/payouts.paused') : __('livewire/owner/payouts.active') }}</span></div>
                    </div>
                    <div class="vy-po-pref-note">{{ __('livewire/owner/payouts.managed_by_owner') }}</div>
                @endif
            </section>
        </div>
    </div>
</x-owner.shell>
