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
<x-owner.shell active="payouts" title="Payouts" :viewer="$viewer">
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
        .vy-po-pref-label { font-size: 11px; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; color: #9C9089; margin: 14px 0 7px; }
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

    {{-- Flash --}}
    @if (session('payout_pref_notice'))
        <div class="vy-flash is-ok" style="margin-bottom:18px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            {{ session('payout_pref_notice') }}
        </div>
    @endif

    {{-- On-hold banner — REAL tax-hold only (a pending payout held for missing tax details); never a fake
         "verification pending". Shown only when a real hold exists. --}}
    @if ($hold)
        <div class="vy-po-hold vy-fade">
            <span class="vy-po-hold-ic">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
            </span>
            <div class="vy-po-hold-body">
                <div class="vy-po-hold-title">Your {{ $hold['amount_display'] }} payout is on hold</div>
                <div class="vy-po-hold-sub">{{ $hold['reason'] }}</div>
                <a class="vy-po-hold-cta" href="{{ $hold['cta_route'] }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    {{ $hold['cta_label'] }}
                </a>
            </div>
        </div>
    @endif

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

            {{-- Payout history — REAL rows; search + status filter client-side; Export = a real CSV (CAP_EXPORTS) --}}
            <section class="vy-card vy-fade" style="animation-delay:.06s" x-data="{ q: '', status: 'all' }">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:10px">
                    <h2 class="vy-card-title">Payout history</h2>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <input type="search" x-model="q" placeholder="Search month or status" aria-label="Search payouts"
                               style="padding:8px 12px;border-radius:var(--vy-r-sm);border:1px solid var(--vy-glass-border);background:rgba(255,255,255,.6);font-size:13px;font-family:var(--vy-sans)">
                        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                            <a class="vy-btn vy-btn--ghost" href="{{ route('owner.payouts.export') }}" title="Export your payout history as CSV">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                                Export
                            </a>
                        @endownercan
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
            {{-- Next payout — REAL amount + status + masked IBAN (CAP_PAYOUT) + lifecycle (real states) + breakdown --}}
            @if ($nextPayout)
                <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.05s">
                    <span class="vy-eyebrow">Next payout @if ($nextPayout['period_label'])· {{ $nextPayout['period_label'] }}@endif</span>
                    <div class="vy-dark-figure" data-countup="{{ $nextPayout['amount_minor'] }}" data-symbol="{{ $nextPayout['currency_symbol'] }}">{{ $nextPayout['amount_display'] }}</div>
                    <span class="vy-status vy-status--{{ $nextPayout['status_meta']['tone'] }}" style="margin-top:6px"><span class="vy-dot"></span>{{ $nextPayout['status_meta']['label'] }} · {{ $nextPayout['rail_label'] }}</span>
                    @if ($payoutAccount['iban_masked'])
                        <div style="margin-top:12px">
                            <span class="vy-ov-iban">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg>
                                IBAN {{ $payoutAccount['iban_masked'] }}
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
                        <a href="{{ route('owner.statements.show', $nextPayout['statement_id']) }}" style="display:inline-block;margin-top:14px;font-size:12.5px;font-weight:600;color:var(--vy-peach)">View statement →</a>
                    @endif
                </section>
            @else
                <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.05s">
                    <span class="vy-eyebrow">Next payout</span>
                    <div style="margin-top:10px;font-size:13px;color:rgba(255,255,255,.7)">No payouts yet. Your first payout appears once a statement is finalized.</div>
                </section>
            @endif

            {{-- Payout account — REAL default bank account (masked IBAN #28, CAP_PAYOUT only). Honest empty otherwise. --}}
            <section class="vy-card vy-fade" style="animation-delay:.09s">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span class="vy-eyebrow">Payout account</span>
                    @if ($payoutAccount['can_manage'])
                        <a href="{{ route('owner.payout-method') }}" style="font-size:12.5px;font-weight:600;color:var(--vy-accent)">Manage</a>
                    @endif
                </div>
                @if ($payoutAccount['has_method'])
                    <div class="vy-po-acct">
                        <span class="vy-po-acct-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg></span>
                        <div>
                            <div class="vy-po-acct-name">{{ $payoutAccount['label'] }}</div>
                            <div class="vy-po-acct-sub">@if ($payoutAccount['iban_masked'])IBAN {{ $payoutAccount['iban_masked'] }}@endif@if ($payoutAccount['country']) · {{ $payoutAccount['country'] }}@endif</div>
                        </div>
                    </div>
                @elseif ($payoutAccount['can_manage'])
                    <div class="vy-line-sub" style="margin-top:8px">No bank account yet. <a href="{{ route('owner.payout-method') }}" style="color:var(--vy-accent);font-weight:600">Add one →</a></div>
                @else
                    <div style="font-size:13.5px;font-weight:500;margin-top:8px">Bank transfer</div>
                    <div class="vy-line-sub">Managed by the account owner.</div>
                @endif
            </section>

            {{-- Upcoming schedule — HONEST: no projection/forecast engine exists (#39). --}}
            <section class="vy-card vy-fade" style="animation-delay:.12s">
                <span class="vy-eyebrow">Upcoming schedule</span>
                <div class="vy-deferred" style="margin-top:10px">
                    <span class="vy-eyebrow">Not yet available</span>
                    Projected future payouts will appear here once scheduling is wired.
                </div>
            </section>

            {{-- Payout preferences — a REAL owner-scoped store. Editable by a payout-capable owner (CAP_PAYOUT) via a
                 controller POST (#20); read-only for others. A PREFERENCE, not a schedule — labelled so (#39). --}}
            <section class="vy-card vy-fade" style="animation-delay:.15s">
                <span class="vy-eyebrow">Payout preferences</span>
                @if ($preferences['can_manage'])
                    <form method="POST" action="{{ route('owner.payouts.preferences') }}" style="margin-top:6px">
                        @csrf
                        <div class="vy-po-pref-label">Frequency</div>
                        <div class="vy-po-seg">
                            @foreach (['monthly' => 'Monthly', 'twice_monthly' => 'Twice monthly', 'weekly' => 'Weekly'] as $val => $label)
                                <label class="vy-po-seg-opt"><input type="radio" name="frequency" value="{{ $val }}" @checked($preferences['frequency'] === $val)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>

                        <div class="vy-po-pref-label">Payout day <span style="text-transform:none;letter-spacing:0;color:var(--vy-faint)">(business day of month)</span></div>
                        <div class="vy-po-seg">
                            @foreach ([1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th', 5 => '5th'] as $val => $label)
                                <label class="vy-po-seg-opt"><input type="radio" name="payout_day" value="{{ $val }}" @checked($preferences['payout_day'] === $val)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>

                        <div class="vy-po-pref-label">Minimum payout</div>
                        <div class="vy-po-seg">
                            @foreach (['0' => 'None', '500' => '€500', '1000' => '€1k', '2500' => '€2.5k'] as $val => $label)
                                <label class="vy-po-seg-opt"><input type="radio" name="minimum" value="{{ $val }}" @checked($preferences['minimum_minor'] === (int) $val * 100)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>

                        <div class="vy-po-pref-label">Payout currency</div>
                        <div class="vy-po-pref-ro" style="border:none;padding-top:0"><span style="color:var(--vy-muted)">Set by your settlement region</span><span class="v vy-mono">{{ $currency }}</span></div>

                        <div class="vy-po-toggle-row">
                            <div>
                                <div style="font-size:13px;font-weight:500">Pause payouts</div>
                                <div class="vy-line-sub">Hold funds in your Vyrobel balance</div>
                            </div>
                            <label class="vy-po-switch"><input type="checkbox" name="paused" value="1" @checked($preferences['paused'])><span></span></label>
                        </div>

                        <div class="vy-po-pref-note">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none;margin-top:1px"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
                            <span>Your stated preference. The actual payout date + release are confirmed with your operator and the payout rail.</span>
                        </div>

                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;margin-top:14px">Save preferences</button>
                    </form>
                @else
                    {{-- Read-only for a non-payout role (accountant / view-only): the values, not the editor. --}}
                    <div style="margin-top:8px">
                        <div class="vy-po-pref-ro"><span style="color:var(--vy-muted)">Frequency</span><span class="v">{{ $preferences['frequency_label'] }}</span></div>
                        <div class="vy-po-pref-ro"><span style="color:var(--vy-muted)">Payout day</span><span class="v">{{ $preferences['payout_day'] }} business day</span></div>
                        <div class="vy-po-pref-ro"><span style="color:var(--vy-muted)">Minimum payout</span><span class="v">{{ $preferences['minimum_display'] }}</span></div>
                        <div class="vy-po-pref-ro"><span style="color:var(--vy-muted)">Payout currency</span><span class="v vy-mono">{{ $currency }}</span></div>
                        <div class="vy-po-pref-ro"><span style="color:var(--vy-muted)">Payouts</span><span class="v">{{ $preferences['paused'] ? 'Paused' : 'Active' }}</span></div>
                    </div>
                    <div class="vy-po-pref-note">Managed by the account owner.</div>
                @endif
            </section>
        </div>
    </div>
</x-owner.shell>
