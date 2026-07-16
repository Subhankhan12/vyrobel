{{--
    Owner Agreement (Wave 1 · Sub-gate 1) — the owner's READ-ONLY view of the terms the operator set,
    styled from the catalogued "Owner Agreement" design and wired to LIVE, EXPLICITLY owner-scoped data.
    Shell from <x-owner.shell>.

    ⚠️ management_agreements (and owners) are NOT owner-scoped — OwnerScope does not protect them. Every
    row here is scoped explicitly by App\Livewire\Owner\OwnerAgreement via OwnerContext::ownerIds(), and
    fails closed to the empty state. See that class docblock.

    HONESTY MAP (rule locked in DECISIONS.md 2026-07-11; see App\Livewire\Owner\OwnerAgreement):
      • REAL      — commission %, management fee + interval, payout schedule + currency, scope
                    (portfolio-wide vs a real owner-scoped unit), effective dates, status, and the
                    attribution_rules overrides verbatim.
      • DERIVED   — "in effect today" via the model's certified effectiveOn scope; the effective payout
                    currency (agreement override ?? owner default), always labelled with its source.
      • NULLS     — "Not set" / "Open-ended" / "Standard attribution". NEVER "0%", never an invented
                    figure, date or document.
      • STATUS    — the real enum only (Active | Ended). Rows not in effect carry a factual DATE note,
                    never an invented phase.
      • OMITTED   — e-signature, amendments, "Review & sign", "Preview", auto-renew, term + notice
                    period: no model, no column, no signing state → not rendered at all, not even
                    disabled.
      • NO BACKEND— "Download agreement" (no stored signed PDF) and "Message your manager" (no owner
                    messaging surface): disabled "Soon".
--}}
<x-owner.shell active="agreement" title="Agreement" :viewer="$viewer">
    <div class="vy-cols">
        {{-- ── Main ── --}}
        <div class="vy-stack">
            {{-- Active agreement — the REAL agreement in effect today (certified effectiveOn scope) --}}
            @if ($active)
                <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.02s">
                    <span class="vy-eyebrow">Active agreement</span>
                    <div class="vy-dark-figure">{{ $active['commission_display'] ?? 'Not set' }}</div>
                    <div style="font-size:12.5px;color:rgba(255,255,255,.65)">
                        {{ $active['commission_display'] ? 'Commission on your gross booking revenue' : 'No commission rate is set on your agreement' }}
                    </div>

                    <span class="vy-status vy-status--{{ $active['status_meta']['tone'] }}" style="margin-top:14px">
                        <span class="vy-dot"></span>{{ $active['status_meta']['label'] }} · {{ $active['scope_label'] }}
                    </span>
                    @if ($active['owner_label'])
                        <div style="margin-top:8px;font-size:11.5px;color:rgba(255,255,255,.6)">{{ $active['owner_label'] }}</div>
                    @endif

                    {{-- REAL columns only. No term / notice period / auto-renew — those have no field. --}}
                    <div style="margin-top:18px;border-top:1px solid rgba(255,255,255,.12);padding-top:12px">
                        @php
                            $heroRows = [
                                ['Started', $active['from_display'] ?? 'Not set'],
                                ['Runs until', $active['to_display'] ?? 'Open-ended'],
                                ['Management fee', $active['fee_display'] ?? 'Not set'],
                                ['Payout schedule', $active['schedule'] ?? 'Not set'],
                            ];
                        @endphp
                        @foreach ($heroRows as [$label, $value])
                            <div style="display:flex;justify-content:space-between;gap:12px;padding:5px 0;font-size:12.5px;color:rgba(255,255,255,.7)">
                                <span>{{ $label }}</span>
                                <span style="font-variant-numeric:tabular-nums;color:#fff;font-weight:600">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @else
                <section class="vy-card vy-fade" style="animation-delay:.02s">
                    <h2 class="vy-card-title">Active agreement</h2>
                    <div class="vy-empty">No active agreement on file.</div>
                </section>
            @endif

            {{-- Honest note: the owner never edits terms here — the operator sets them. --}}
            <div class="vy-fx-note" style="margin-top:-8px">
                Your terms are set by your operator. Any change they make appears here automatically — this
                page is read-only.
            </div>

            {{-- Other terms in effect — REAL per-unit overrides (and, for a user linked to more than one
                 owner, the other owners' current terms). Rendered only when they exist. --}}
            @if (! empty($others))
                <section class="vy-card vy-fade" style="animation-delay:.06s">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:10px">
                        <h2 class="vy-card-title">Also in effect</h2>
                        <span class="vy-line-sub" style="margin-top:0">{{ $currentCount }} {{ \Illuminate\Support\Str::plural('agreement', $currentCount) }} in effect today</span>
                    </div>

                    <div style="overflow-x:auto">
                        <table class="vy-table">
                            <thead>
                                <tr>
                                    <th>Applies to</th>
                                    <th>Since</th>
                                    <th>Commission</th>
                                    <th>Management fee</th>
                                    <th class="vy-num">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($others as $row)
                                    <tr>
                                        <td>
                                            <span class="vy-row-month">{{ $row['scope_label'] }}</span>
                                            @if ($row['owner_label'])<div class="vy-row-sub">{{ $row['owner_label'] }}</div>@endif
                                        </td>
                                        <td class="vy-muted">{{ $row['from_display'] ?? '—' }}</td>
                                        <td class="vy-mono">{{ $row['commission_display'] ?? 'Not set' }}</td>
                                        <td class="vy-mono">{{ $row['fee_display'] ?? 'Not set' }}</td>
                                        <td class="vy-num">
                                            <span class="vy-status vy-status--{{ $row['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['status_meta']['label'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            {{-- Agreement history — REAL earlier / ended / upcoming terms. The Status column carries the
                 real enum ONLY; the period sub-line is factual dates, never an invented phase. --}}
            @if (! empty($history))
                <section class="vy-card vy-fade" style="animation-delay:.1s">
                    <div style="margin-bottom:10px">
                        <h2 class="vy-card-title">Agreement history</h2>
                        <div class="vy-line-sub">Terms that are not in effect today.</div>
                    </div>

                    <div style="overflow-x:auto">
                        <table class="vy-table">
                            <thead>
                                <tr>
                                    <th>Applies to</th>
                                    <th>Period</th>
                                    <th>Commission</th>
                                    <th>Management fee</th>
                                    <th class="vy-num">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($history as $row)
                                    <tr>
                                        <td>
                                            <span class="vy-row-month">{{ $row['scope_label'] }}</span>
                                            @if ($row['owner_label'])<div class="vy-row-sub">{{ $row['owner_label'] }}</div>@endif
                                        </td>
                                        <td class="vy-muted">
                                            {{ $row['from_display'] ?? '—' }} – {{ $row['to_display'] ?? 'Open-ended' }}
                                            @if ($row['timing_note'])<div class="vy-row-sub">{{ $row['timing_note'] }}</div>@endif
                                        </td>
                                        <td class="vy-mono">{{ $row['commission_display'] ?? 'Not set' }}</td>
                                        <td class="vy-mono">{{ $row['fee_display'] ?? 'Not set' }}</td>
                                        <td class="vy-num">
                                            <span class="vy-status vy-status--{{ $row['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['status_meta']['label'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        {{-- ── Right rail ── --}}
        <div class="vy-stack">
            {{-- Key terms — REAL fields of the agreement in effect; every null says so --}}
            @if ($active)
                <section class="vy-card vy-fade" style="animation-delay:.05s">
                    <span class="vy-eyebrow">Key terms</span>

                    <div class="vy-list-row">
                        <span style="font-size:13px;color:var(--vy-muted)">Applies to</span>
                        <span style="font-size:13px;font-weight:600">{{ $active['scope_label'] }}</span>
                    </div>
                    <div class="vy-list-row">
                        <span style="font-size:13px;color:var(--vy-muted)">Commission</span>
                        <span class="vy-mono" style="font-weight:600">{{ $active['commission_display'] ?? 'Not set' }}</span>
                    </div>
                    <div class="vy-list-row">
                        <span style="font-size:13px;color:var(--vy-muted)">Management fee</span>
                        <span class="vy-mono" style="font-weight:600">{{ $active['fee_display'] ?? 'Not set' }}</span>
                    </div>
                    @if ($active['fee_interval'])
                        <div class="vy-list-row">
                            <span style="font-size:13px;color:var(--vy-muted)">Fee charged</span>
                            <span style="font-size:13px;font-weight:600">{{ $active['fee_interval'] }}</span>
                        </div>
                    @endif
                    <div class="vy-list-row">
                        <span style="font-size:13px;color:var(--vy-muted)">Payout schedule</span>
                        <span style="font-size:13px;font-weight:600">{{ $active['schedule'] ?? 'Not set' }}</span>
                    </div>
                    <div class="vy-list-row">
                        <span style="font-size:13px;color:var(--vy-muted)">Payout currency</span>
                        <span class="vy-mono" style="font-weight:600">{{ $active['payout_currency'] ?? 'Not set' }}</span>
                    </div>
                    @if ($active['payout_currency_source'])
                        <div class="vy-fx-note">{{ $active['payout_currency_source'] }}.</div>
                    @endif
                </section>

                {{-- Attribution — the REAL overrides stored on the agreement, or the honest "no overrides" --}}
                <section class="vy-card vy-fade" style="animation-delay:.08s">
                    <span class="vy-eyebrow">Attribution</span>
                    @if ($overrides)
                        <div class="vy-line-sub" style="margin-bottom:2px">Agreed exceptions to the standard rules.</div>
                        @foreach ($overrides as $rule)
                            <div class="vy-list-row">
                                <span style="font-size:13px;color:var(--vy-muted)">{{ $rule['label'] }}</span>
                                <span style="font-size:13px;font-weight:600">{{ $rule['value'] }}</span>
                            </div>
                        @endforeach
                    @else
                        <div class="vy-line-sub" style="margin-top:8px;font-size:12.5px">
                            Standard attribution — your agreement carries no exceptions to the standard rules
                            for who bears OTA costs, cleaning fees and add-ons.
                        </div>
                    @endif
                </section>
            @endif

            {{-- Documents — NO BACKEND: no signed agreement artifact is stored. Never a fake download. --}}
            <section class="vy-card vy-fade" style="animation-delay:.12s">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <span class="vy-eyebrow">Documents</span>
                    <span class="vy-soon-tag">Soon</span>
                </div>
                <div class="vy-deferred">
                    <span class="vy-eyebrow">Not yet available</span>
                    No signed agreement document is stored against your account yet.
                </div>
                <button type="button" class="vy-btn vy-btn--ghost is-soon" style="margin-top:12px;width:100%;justify-content:center;opacity:.6;cursor:not-allowed" disabled aria-disabled="true" title="Coming soon">
                    Download agreement
                </button>
            </section>

            {{-- Change your terms — NO BACKEND (no owner messaging surface): disabled "Soon" --}}
            <section class="vy-card vy-fade" style="animation-delay:.15s">
                <span class="vy-eyebrow">Want to change your terms?</span>
                <div class="vy-line-sub" style="margin-top:8px;font-size:12.5px">
                    Commission, add-ons and ending the agreement are handled by your operator — speak to them
                    directly for now.
                </div>
                <button type="button" class="vy-btn vy-btn--ghost is-soon" style="margin-top:12px;width:100%;justify-content:center;opacity:.6;cursor:not-allowed" disabled aria-disabled="true" title="Coming soon">
                    Message your manager
                </button>
            </section>
        </div>
    </div>
</x-owner.shell>
