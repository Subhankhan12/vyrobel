{{--
    Owner Balance (Wave 1 · Sub-gate 1) — the owner's append-only `owner_ledger` as a running-balance
    view, styled from the catalogued "Owner Balance" design and wired to LIVE owner-scoped data. Shell
    from <x-owner.shell>.

    HONESTY MAP (rule locked in DECISIONS.md 2026-07-11; see App\Livewire\Owner\OwnerBalance):
      • REAL      — every ledger row (date / description / type / signed amount / statement link /
                    Prior-period-adjustment + Unverified badges); In transit = Σ pending payouts;
                    Paid to date = Σ paid payouts; the auto-payout cadence (agreement payout_schedule).
      • DERIVED   — Net position = Σ signedMinor() over ALL the owner's rows, and each row's RUNNING
                    BALANCE = the same cumulative sum in chronological order. Reconciles to the cent.
      • CURRENCY  — ledger = booking currency, payouts = payout currency. Totals + the running balance
                    accumulate PER CURRENCY; nothing is summed or converted across currencies.
      • NO BACKEND— Withdraw (no owner-initiated payout rail exists) and Export (CSV deferral):
                    disabled "Soon". NO IBAN, NO ETA, NO estimated arrival — no backing field.
      • OMITTED   — the design's "Available / On hold / In transit" CLEARING state machine, the tax-ID
                    hold explainer, "Add tax details" and "How money clears": no such concept exists,
                    so they are dropped rather than stubbed (rendering them would imply fake state).
--}}
<x-owner.shell active="balance" title="Balance" :viewer="$viewer">
    <div class="vy-cols">
        {{-- ── Main ── --}}
        <div class="vy-stack">
            {{-- Headline trio — Net position DERIVED from real rows; In transit / Paid to date REAL --}}
            <div class="vy-kpi-grid vy-fade" style="animation-delay:.02s">
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Net position</div>
                    <div class="vy-kpi-val">{{ $net['display'] }}</div>
                    <div class="vy-kpi-sub">{{ $net['count'] }} ledger {{ \Illuminate\Support\Str::plural('entry', $net['count']) }} · {{ $net['currency'] }}</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">In transit</div>
                    <div class="vy-kpi-val">{{ $inTransit['display'] }}</div>
                    <div class="vy-kpi-sub">{{ $inTransit['count'] }} pending {{ \Illuminate\Support\Str::plural('payout', $inTransit['count']) }} · {{ $inTransit['currency'] }}</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Paid to date</div>
                    <div class="vy-kpi-val">{{ $paid['display'] }}</div>
                    <div class="vy-kpi-sub">{{ $paid['count'] }} {{ \Illuminate\Support\Str::plural('payout', $paid['count']) }} paid · {{ $paid['currency'] }}</div>
                </div>
            </div>

            {{-- Per-currency totals — only when the owner's rows span >1 currency (never a cross-currency sum) --}}
            @if ($byCurrency)
                <section class="vy-card vy-fade" style="animation-delay:.04s">
                    <h2 class="vy-card-title">Balances by currency</h2>
                    <div style="overflow-x:auto;margin-top:10px">
                        <table class="vy-table">
                            <thead>
                                <tr>
                                    <th>Currency</th>
                                    <th class="vy-num">Net position</th>
                                    <th class="vy-num">In transit</th>
                                    <th class="vy-num">Paid to date</th>
                                </tr>
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

            {{-- Balance activity — REAL ledger rows, newest first, with a DERIVED running balance --}}
            <section class="vy-card vy-fade" style="animation-delay:.06s">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:10px">
                    <div>
                        <h2 class="vy-card-title">Balance activity</h2>
                        <div class="vy-line-sub">Every movement, newest first</div>
                    </div>
                    <button type="button" class="vy-btn vy-btn--ghost is-soon" style="opacity:.6;cursor:not-allowed" disabled aria-disabled="true" title="Coming soon">Export</button>
                </div>

                <div style="overflow-x:auto">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activity</th>
                                <th>Statement</th>
                                <th class="vy-num">Amount</th>
                                <th class="vy-num">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entries as $entry)
                                <tr>
                                    <td class="vy-muted" style="white-space:nowrap">{{ $entry['date'] }}</td>
                                    <td>
                                        <div class="vy-row-month">{{ $entry['description'] }}</div>
                                        <div class="vy-row-sub">
                                            {{ $entry['type_label'] }}
                                            @if ($entry['prior_period'])
                                                <span class="vy-provisional" style="margin-left:6px">Prior-period adjustment</span>
                                            @endif
                                            @if ($entry['unverified'])
                                                <span class="vy-provisional" style="margin-left:6px">Unverified</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="white-space:nowrap">
                                        @if ($entry['statement_id'])
                                            <a href="{{ route('owner.statements.show', $entry['statement_id']) }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">Statement</a>
                                        @else
                                            <span class="vy-muted" style="font-size:11.5px">Not yet on a statement</span>
                                        @endif
                                    </td>
                                    <td class="vy-num vy-mono @if ($entry['is_negative']) vy-net @endif">{{ $entry['amount_display'] }}</td>
                                    <td class="vy-num vy-mono">{{ $entry['running_display'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="vy-empty">No balance activity yet.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($net['multi'])
                    <div class="vy-fx-note">The running balance accumulates separately for each currency.</div>
                @endif
            </section>
        </div>

        {{-- ── Right rail ── --}}
        <div class="vy-stack">
            {{-- Withdraw — NO BACKEND: there is no owner-initiated payout rail. Disabled, never faked;
                 no IBAN, no ETA, no estimated arrival date. --}}
            <section class="vy-card vy-fade" style="animation-delay:.05s">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <span class="vy-eyebrow">Withdraw</span>
                    <span class="vy-soon-tag">Soon</span>
                </div>
                <button type="button" class="vy-btn vy-btn--ghost is-soon" style="width:100%;justify-content:center;opacity:.6;cursor:not-allowed" disabled aria-disabled="true" title="Coming soon">Withdraw</button>
                <div class="vy-line-sub" style="margin-top:10px">
                    Payouts are issued by your operator on the agreed schedule — self-serve withdrawals are not available yet.
                </div>
            </section>

            {{-- Auto-payout schedule — REAL (the current management agreement's payout_schedule). The
                 design's payout DAY and "next run" have no backing field and are not rendered. --}}
            @if ($schedule)
                <section class="vy-card vy-fade" style="animation-delay:.09s">
                    <span class="vy-eyebrow">Auto-payout schedule</span>
                    <div class="vy-list-row">
                        <span style="font-size:13.5px;font-weight:500">{{ $schedule['label'] }}</span>
                        <a href="{{ route('owner.payouts') }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">Payouts →</a>
                    </div>
                    <div class="vy-line-sub">Your agreed statement and payout cadence.</div>
                </section>
            @endif
        </div>
    </div>
</x-owner.shell>
