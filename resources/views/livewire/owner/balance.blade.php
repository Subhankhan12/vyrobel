{{--
    Owner Balance (exact-match pass) — the owner's append-only `owner_ledger` as a clearing view, rebuilt to the
    decoded "Owner Balance" wireframe and wired to LIVE owner-scoped data. Shell from <x-owner.shell>.

    ⚠️ REAL MONEY — nothing faked. HONESTY MAP (see App\Livewire\Owner\OwnerBalance):
      • REAL — the CLEARING model Available / On hold / In transit + Paid, reconciling to the Net position to the
        cent (#17): Available = unstamped ledger net; On hold = tax-held pending payouts (the REAL tax-hold);
        In transit = released pending payouts. The balance-activity ledger (real rows + running balance + a
        per-row state chip). The masked IBAN (CAP_PAYOUT, #28). The auto-payout cadence + Export CSV.
      • REAL HOLD — the "Why is X held?" card shows ONLY the real tax-hold + the real inline Add-tax-details path.
      • ACCOUNT-GATED — "Withdraw early" is instant money-movement; there is NO owner-initiated transfer rail, so
        the button is honestly disabled (automated month-end payout is how funds move) — NEVER a fake withdrawal /
        transfer / "in transit". "In transit" is a REAL figure (€0 while held), never a staged movement.

    Page-scoped CSS (.vy-bl-*) lives here. RAW-BLADE RULE: every `@` follows a space, newline or tag.
--}}
<x-owner.shell active="balance" title="Balance & withdrawals" :subtitle="'What’s cleared, what’s held, and where every payment is on its way to.'" :viewer="$viewer">
    <style>
        .vy-bl-kpi--hold { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, .1); }
        /* ⚠️ On this tint NO grey token clears AA (--vy-on-grad tops out at 4.31), so the label and
           sub take --vy-ink (6.80 / 6.57). The value stays larger + bolder, so the hierarchy is
           carried by size and weight rather than by a colour that cannot be read. */
        .vy-bl-kpi--hold .vy-kpi-label, .vy-bl-kpi--hold .vy-kpi-sub { color: var(--vy-ink); }
        .vy-bl-withdraw { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .vy-bl-withdraw-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 20px; border-radius: var(--vy-r-pill); background: var(--vy-grad); color: #fff; font-family: var(--vy-sans); font-size: 13.5px; font-weight: 600; border: none; opacity: .55; cursor: not-allowed; box-shadow: var(--vy-shadow-accent); }
        .vy-bl-held-ic { flex: none; width: 42px; height: 42px; border-radius: var(--vy-r-md); background: var(--vy-grad); color: #fff; display: grid; place-items: center; box-shadow: 0 5px 14px rgba(194, 65, 12, .3); }
        .vy-bl-held-cta { display: inline-flex; align-items: center; gap: 6px; margin-top: 12px; padding: 9px 15px; border-radius: var(--vy-r-pill); background: var(--vy-grad); color: #fff; font-size: 12.5px; font-weight: 500; text-decoration: none; box-shadow: 0 5px 14px rgba(194, 65, 12, .3); }
        .vy-bl-held-cta:hover { transform: translateY(-1px); }
        .vy-bl-iban { display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 12px; color: var(--vy-muted); font-variant-numeric: tabular-nums; }
        .vy-bl-clears { background: var(--vy-grad-dark); color: rgba(255,255,255,.9); }
    </style>

    {{-- TEST-MODE boundary — the automated payout engine runs in test mode; never implies real money moves. --}}
    <x-owner.payout-test-mode :show="$testMode" />

    <div class="vy-cols">
        {{-- ── Main ── --}}
        <div class="vy-stack">
            {{-- Clearing KPIs — Available / On hold / In transit (reconcile to Net + Paid, #17) --}}
            <div class="vy-kpi-grid vy-fade" style="animation-delay:.02s">
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Available</div>
                    <div class="vy-kpi-val">{{ $available['display'] }}</div>
                    <div class="vy-kpi-sub">cleared this period · {{ $available['currency'] }}</div>
                </div>
                <div class="vy-kpi {{ $onHold['minor'] !== 0 ? 'vy-bl-kpi--hold' : '' }}">
                    <div class="vy-kpi-label">On hold</div>
                    <div class="vy-kpi-val">{{ $onHold['display'] }}</div>
                    <div class="vy-kpi-sub">{{ $onHold['minor'] !== 0 ? 'tax details needed' : 'nothing held' }}</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">In transit</div>
                    <div class="vy-kpi-val">{{ $inTransit['display'] }}</div>
                    <div class="vy-kpi-sub">{{ $inTransit['minor'] !== 0 ? 'on its way' : 'nothing in transit' }}</div>
                </div>
            </div>

            {{-- Carried forward (#17 — P2-1): shown ONLY when a refunded/negative statement was carried into the
                 next period (no payout). It's part of Net position, so the clearing identity stays exact. Hidden
                 (€0) in the normal case — never a fabricated line. --}}
            @if ($carriedForward['minor'] !== 0)
                <section class="vy-card vy-fade" style="animation-delay:.03s">
                    <div class="vy-eyebrow">Carried forward</div>
                    <div class="vy-kpi-val" style="margin-top:6px">{{ $carriedForward['display'] }}</div>
                    <p class="vy-line-sub" style="font-size:12.5px;margin-top:6px;max-width:480px">A refunded or negative statement is carried into your next payout period — it offsets a future payout instead of being charged back, and it’s already included in your Net position.</p>
                </section>
            @endif

            {{-- Withdraw early — ACCOUNT-GATED: no owner-initiated transfer rail exists, so this is honestly
                 disabled (never a fake withdrawal/transfer/in-transit). The masked IBAN is real (CAP_PAYOUT). --}}
            <section class="vy-card vy-fade" style="animation-delay:.05s">
                <div class="vy-bl-withdraw">
                    <div style="max-width:440px">
                        <h2 class="vy-card-title" style="margin-bottom:4px">Withdraw early</h2>
                        <div class="vy-line-sub" style="font-size:12.5px">
                            Anything cleared is paid out automatically{{ $schedule ? ' on your '.strtolower($schedule['label']).' schedule' : '' }}. Instant self-serve withdrawal activates with the live payments connection.
                        </div>
                        @if ($ibanMasked)
                            <div class="vy-bl-iban">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M4 10h16M12 3 3 7v3h18V7zM6 10v8M10 10v8M14 10v8M18 10v8"/></svg>
                                to IBAN {{ $ibanMasked }}
                            </div>
                        @endif
                    </div>
                    <button type="button" class="vy-bl-withdraw-btn" disabled aria-disabled="true" title="Instant withdrawal activates with the live payments connection">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                        Withdraw{{ $available['minor'] > 0 ? ' '.$available['display'] : '' }}
                    </button>
                </div>
            </section>

            {{-- Balance activity — REAL ledger rows, newest first, DERIVED running balance + a per-row state chip --}}
            <section class="vy-card vy-fade" style="animation-delay:.08s">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:10px">
                    <div>
                        <h2 class="vy-card-title">Balance activity</h2>
                        <div class="vy-line-sub">Grouped by month, newest first — open a month for every movement in it</div>
                    </div>
                    @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                        <a class="vy-btn vy-btn--ghost" href="{{ route('owner.balance.export') }}" title="Export your balance activity as CSV">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 11l4 4 4-4M5 21h14"/></svg>
                            Export
                        </a>
                    @endownercan
                </div>

                <div style="overflow-x:auto">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activity</th>
                                <th class="vy-num">Amount</th>
                                <th class="vy-num">Balance</th>
                            </tr>
                        </thead>
                        {{-- ⚠️ OWNER FIX 3 — ROLLED UP BY DEFAULT, EXPANDABLE TO THE REAL LINES.
                             The summary row's figure is the SUM of the very rows revealed beneath it (the
                             component groups already-computed values; it never re-derives one), so the two
                             views cannot disagree. The expanded rows are byte-for-byte the list this table
                             rendered before — same order, same amounts, same running balance, same provenance.
                             ⚠️ The BALANCE on a summary is the group's most recent line's running balance, NOT
                             a sum: a running balance is a position, and summing positions is meaningless. --}}
                        {{-- ⚠️ ONE <tbody> PER GROUP — and that is load-bearing, not tidiness. The first cut put
                             `x-data` on the summary <tr> with the line <tr>s as SIBLINGS: `open` was then out of
                             scope for them, every `x-show` threw "Illegal invocation", and the rows rendered
                             EXPANDED at all times. The tests still passed (they assert the data), so only opening
                             the page caught it. HTML5 permits multiple tbody elements in one table; this puts a
                             summary and its lines inside a single Alpine scope. --}}
                        @forelse ($activityGroups as $group)
                            <tbody x-data="{ open: false }">
                                <tr>
                                    <td class="vy-muted" style="white-space:nowrap">{{ $group['span'] }}</td>
                                    <td>
                                        <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'"
                                                aria-controls="vy-bal-{{ $loop->index }}"
                                                style="display:flex;align-items:center;gap:7px;background:none;border:0;padding:0;cursor:pointer;text-align:left;font:inherit;color:inherit">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"
                                                 :style="open ? 'transform:rotate(90deg);transition:transform .15s' : 'transition:transform .15s'"><path d="m9 18 6-6-6-6"/></svg>
                                            <span>
                                                <span class="vy-row-month">{{ $group['label'] }}</span>
                                                <span class="vy-row-sub" style="display:block">
                                                    <span x-text="open ? 'Hide the {{ $group['count_label'] }}' : 'Show the {{ $group['count_label'] }}'">Show the {{ $group['count_label'] }}</span>
                                                    @if ($group['prior_period'])<span class="vy-provisional" style="margin-left:6px">Prior-period adjustment</span>@endif
                                                    @if ($group['unverified'])<span class="vy-provisional" style="margin-left:6px">Unverified</span>@endif
                                                </span>
                                            </span>
                                        </button>
                                    </td>
                                    <td class="vy-num vy-mono @if ($group['is_negative']) vy-net @endif">{{ $group['net_display'] }}</td>
                                    <td class="vy-num"><div class="vy-mono">{{ $group['running_display'] }}</div></td>
                                </tr>

                                @foreach ($group['lines'] as $entry)
                                    <tr x-show="open" x-cloak id="vy-bal-{{ $loop->parent->index }}" style="background:rgba(255,255,255,.35)">
                                        <td class="vy-muted" style="white-space:nowrap;padding-left:26px">{{ $entry['date'] }}</td>
                                        <td>
                                            <div class="vy-row-month">{{ $entry['description'] }}</div>
                                            <div class="vy-row-sub">
                                                {{ $entry['type_label'] }}
                                                @if ($entry['statement_id'])
                                                    · <a href="{{ route('owner.statements.show', $entry['statement_id']) }}" style="color:var(--vy-accent-text);font-weight:600">on a statement</a>
                                                @else
                                                    · <span class="vy-muted">not yet on a statement</span>
                                                @endif
                                                @if ($entry['prior_period'])<span class="vy-provisional" style="margin-left:6px">Prior-period adjustment</span>@endif
                                                @if ($entry['unverified'])<span class="vy-provisional" style="margin-left:6px">Unverified</span>@endif
                                            </div>
                                        </td>
                                        <td class="vy-num vy-mono @if ($entry['is_negative']) vy-net @endif">{{ $entry['amount_display'] }}</td>
                                        <td class="vy-num">
                                            <div class="vy-mono">{{ $entry['running_display'] }}</div>
                                            <span class="vy-status vy-status--{{ $entry['state']['tone'] }}" style="margin-top:4px"><span class="vy-dot"></span>{{ $entry['state']['label'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @empty
                            <tbody>
                                <tr><td colspan="4"><div class="vy-empty">No balance activity yet.</div></td></tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>

                @if ($net['multi'])
                    <div class="vy-fx-note">The running balance accumulates separately for each currency.</div>
                @endif
            </section>

            {{-- Per-currency totals — only when the owner's rows span >1 currency (never a cross-currency sum) --}}
            @if ($byCurrency)
                <section class="vy-card vy-fade" style="animation-delay:.1s">
                    <h2 class="vy-card-title">Balances by currency</h2>
                    <div style="overflow-x:auto;margin-top:10px">
                        <table class="vy-table">
                            <thead>
                                <tr><th>Currency</th><th class="vy-num">Net position</th><th class="vy-num">In transit</th><th class="vy-num">Paid to date</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($byCurrency as $row)
                                    <tr>
                                        <td class="vy-row-month">{{ $row['currency'] }}</td>
                                        <td class="vy-num vy-mono">{{ $row['net_display'] }}</td>
                                        <td class="vy-num vy-mono">{{ $row['transit_display'] }}</td>
                                        <td class="vy-num vy-mono">{{ $row['paid_display'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="vy-fx-note">Each currency is held and totalled separately — balances are never converted.</div>
                </section>
            @endif
        </div>

        {{-- ── Right rail ── --}}
        <div class="vy-stack">
            {{-- Why held? — REAL tax-hold only (a held pending payout + no tax details). Shown only on a real hold. --}}
            @if ($held)
                <section class="vy-card vy-card--accent vy-fade" style="animation-delay:.05s">
                    <div style="display:flex;align-items:center;gap:12px">
                        <span class="vy-bl-held-ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg></span>
                        <div class="vy-card-title" style="font-size:15px">Why is {{ $held['display'] }} held?</div>
                    </div>
                    <div class="vy-line-sub" style="margin-top:10px;font-size:12.5px">Your payout can’t be sent until a valid tax ID is on file — a legal requirement before we can transfer rental income.</div>
                    <a class="vy-bl-held-cta" href="{{ route('owner.tax') }}">
                        Add tax details
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                </section>
            @endif

            {{-- Auto-payout schedule — REAL (the current agreement's cadence). The real next-scheduled-payout DATE
                 (payout engine · PayoutSchedule) is shown on the Payouts page; this card states the cadence only. --}}
            @if ($schedule)
                <section class="vy-card vy-fade" style="animation-delay:.09s">
                    <span class="vy-eyebrow">Auto-payout schedule</span>
                    <div class="vy-list-row">
                        <span style="font-size:13.5px;font-weight:500">{{ $schedule['label'] }}</span>
                        <a href="{{ route('owner.payouts') }}" style="color:var(--vy-accent-text);font-size:12.5px;font-weight:600">Payouts →</a>
                    </div>
                    <div class="vy-line-sub">Everything cleared by the schedule goes out automatically — no action needed.</div>
                </section>
            @endif

            {{-- How money clears — descriptive, real. --}}
            <section class="vy-card vy-bl-clears vy-fade" style="animation-delay:.12s">
                <div style="display:flex;align-items:center;gap:8px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--vy-peach)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg><span class="vy-card-title" style="font-size:15px;color:#fff">How money clears</span></div>
                <div style="margin-top:10px;font-size:12.5px;color:rgba(255,255,255,.72);line-height:1.6">Guest payments clear once the stay begins and the statement is finalised. Refunds and approved expenses are netted before anything reaches your balance.</div>
            </section>
        </div>
    </div>
</x-owner.shell>
