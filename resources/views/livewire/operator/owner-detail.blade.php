{{-- Operator Owner Detail (Wave 1 · Sub-gate 3) — the operator's TENANT-scoped, READ-ONLY lens on ONE
     owner: their real record, units, commercial terms, statements, payouts and ledger balance. Styled on
     the shared operator design system via <x-operator.shell>. Presentation only — nothing on this page
     mutates anything, and every figure is a certified read (see App\Livewire\Operator\OperatorOwnerDetail
     for the full REAL / DERIVED / OMITTED honesty map).

     Honesty map (short form — the component docblock is authoritative):
       • REAL     — name, legal name, type + status (real enums), payout currency, email, phone, notes;
                    the owner's units; the agreement terms; statement + payout rows (real enums only).
       • DERIVED  — the KPI band: Σ signedMinor(owner_ledger), Σ paid payouts, Σ pending payouts, unit
                    count — all PER CURRENCY, never summed across currencies (invariant #17).
       • NULLS    — "Not set" / "Open-ended" / "—" for genuinely empty columns; NEVER "0%".
       • PII      — email + phone ARE shown (the operator's own CRM record for a counterparty they
                    invited). tax_id + the raw address JSON are OMITTED entirely, never read.
       • OMITTED  — the "Edit owner" modal (commission stepper = a write; its 3 toggles have NO columns),
                    "Remove owner", IBAN / bank details, any estimated next-payout date, the "Workspace
                    paused" billing banner, "Scheduled" statuses, the "Add note" write, the Email/Message
                    buttons, and the design's demo figures. Nothing is greyed out — it is simply absent.
       • NOT LINKED — statement rows carry no link: the statement detail is on the OWNER-scoped route,
                    which an operator session cannot pass (it would 404).

     RAW-BLADE RULE: never write `word@directive` — Blade's \B guard would ship the raw source to the
     page as ordinary text. Every `@` below follows a space, a newline or a tag. --}}
<div>
    <x-operator.shell active="owners" :title="$owner['name']"
        :subtitle="$owner['type_label'] . ' · Paid in ' . $owner['payout_currency']" :viewer="$viewer">

        {{-- Toolbar: back to the real Owners list (the invite/re-invite WRITE lives there, not here) --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                <a class="vy-convo-back" href="{{ route('app.owners') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                    All owners
                </a>
            </div>
            <div class="vy-right">
                <span class="vy-tag tone-{{ $owner['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $owner['status_meta']['label'] }}</span>
            </div>
        </div>

        {{-- ── KPI band — DERIVED from the real ledger + payouts, per currency ──────────────────── --}}
        <div class="vy-dash-kpis vy-fade">
            <div class="vy-dash-kpi is-feature">
                <div class="vy-dk-label">Balance</div>
                @foreach ($kpis['balance'] as $line)
                    <div class="vy-dk-val" @if (! $loop->first) style="font-size: 22px;" @endif>{{ $line }}</div>
                @endforeach
                <div class="vy-dk-sub">
                    Net of every owner-ledger line
                    {{ $kpis['multi_currency'] ? '· shown per currency (never summed across)' : '' }}
                </div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Paid out</div>
                @foreach ($kpis['paid'] as $line)
                    <div class="vy-dk-val" @if (! $loop->first) style="font-size: 20px;" @endif>{{ $line }}</div>
                @endforeach
                <div class="vy-dk-sub">Payouts genuinely marked paid</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Pending payouts</div>
                @foreach ($kpis['pending'] as $line)
                    <div class="vy-dk-val" @if (! $loop->first) style="font-size: 20px;" @endif>{{ $line }}</div>
                @endforeach
                <div class="vy-dk-sub">Awaiting the payout rail</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Units</div>
                <div class="vy-dk-val">{{ $kpis['units'] }}</div>
                <div class="vy-dk-sub">{{ \Illuminate\Support\Str::plural('unit', $kpis['units']) }} owned by this owner</div>
            </div>
        </div>

        <div class="vy-detail-cols" style="margin-top: 22px;">
            {{-- ══════════════════════ LEFT: units · agreement · statements · payouts ═══════════ --}}
            <div class="vy-stack">

                {{-- Units — REAL, filtered by owner_id --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head">
                        <h2 class="vy-card-title">Units</h2>
                        <span class="vy-eyebrow">{{ count($units) }}</span>
                    </div>
                    @forelse ($units as $u)
                        <div class="vy-unit-row">
                            <div>
                                <div class="vy-um-name">{{ $u['code'] }}{{ $u['name'] ? ' · ' . $u['name'] : '' }}</div>
                                <div class="vy-um-sub">
                                    {{ $u['kind_label'] }}
                                    @if ($u['room_type'])
                                        <span class="vy-sep">·</span> {{ $u['room_type'] }}
                                    @endif
                                    {{-- units.max_occupancy is NOT NULL (default 2) — always a real fact. --}}
                                    <span class="vy-sep">·</span> Sleeps {{ $u['sleeps'] }}
                                </div>
                            </div>
                            @unless ($u['active'])
                                <span class="vy-u-inactive">Inactive</span>
                            @endunless
                        </div>
                    @empty
                        <div class="vy-empty">No units are assigned to this owner yet.</div>
                    @endforelse
                </div>

                {{-- Management agreement — the REAL terms in effect today (via the certified effectiveOn
                     scope). Honest nulls throughout: "Not set", never "0%". --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head">
                        <h2 class="vy-card-title">Management agreement</h2>
                        @if ($agreement)
                            <span class="vy-tag tone-{{ $agreement['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $agreement['status_meta']['label'] }}</span>
                        @endif
                    </div>

                    @if ($agreement)
                        <div class="vy-eyebrow" style="margin-bottom: 14px;">In effect today</div>
                        <div class="vy-facts">
                            <div>
                                <div class="vy-fact-label">Commission</div>
                                <div class="vy-fact-val">{{ $agreement['commission_display'] ?? 'Not set' }}</div>
                            </div>
                            <div>
                                <div class="vy-fact-label">Management fee</div>
                                <div class="vy-fact-val">
                                    {{ $agreement['fee_display'] ?? 'Not set' }}{{ $agreement['fee_display'] && $agreement['fee_interval'] ? ' · ' . $agreement['fee_interval'] : '' }}
                                </div>
                            </div>
                            <div>
                                <div class="vy-fact-label">Payout schedule</div>
                                <div class="vy-fact-val">{{ $agreement['schedule'] ?? 'Not set' }}</div>
                            </div>
                            <div>
                                <div class="vy-fact-label">Payout currency</div>
                                <div class="vy-fact-val">{{ $agreement['payout_currency'] ?? 'Not set' }}</div>
                                @if ($agreement['payout_currency_source'])
                                    <div class="vy-um-sub">{{ $agreement['payout_currency_source'] }}</div>
                                @endif
                            </div>
                            <div>
                                <div class="vy-fact-label">Scope</div>
                                <div class="vy-fact-val">{{ $agreement['scope_label'] }}</div>
                            </div>
                            <div>
                                <div class="vy-fact-label">Effective</div>
                                <div class="vy-fact-val">
                                    {{ $agreement['from_display'] ?? '—' }} → {{ $agreement['to_display'] ?? 'Open-ended' }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="vy-empty">No management agreement is in effect for this owner today.</div>
                    @endif

                    {{-- Other / earlier terms — REAL rows; a row not in effect carries a factual DATE
                         note, never an invented status. --}}
                    @if (count($agreementHistory) > 0)
                        <div class="vy-eyebrow" style="margin: 20px 0 6px;">Other terms on record</div>
                        <div class="vy-utable-wrap">
                            <table class="vy-utable" style="min-width: 0;">
                                <thead>
                                    <tr>
                                        <th>Scope</th>
                                        <th>Commission</th>
                                        <th>Fee</th>
                                        <th>Effective</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($agreementHistory as $h)
                                        <tr>
                                            <td>
                                                <div class="vy-u-code">{{ $h['scope_label'] }}</div>
                                                @if ($h['timing_note'])
                                                    <div class="vy-u-sub">{{ $h['timing_note'] }}</div>
                                                @elseif ($h['in_effect'])
                                                    <div class="vy-u-sub">In effect today</div>
                                                @endif
                                            </td>
                                            <td>{{ $h['commission_display'] ?? 'Not set' }}</td>
                                            <td>{{ $h['fee_display'] ?? 'Not set' }}</td>
                                            <td>{{ $h['from_display'] ?? '—' }} → {{ $h['to_display'] ?? 'Open-ended' }}</td>
                                            <td><span class="vy-tag tone-{{ $h['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $h['status_meta']['label'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Statements — REAL rows. NO link: the statement detail is owner-scoped and would 404
                     for an operator session, and a link that always 404s is worse than no link. --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head">
                        <h2 class="vy-card-title">Statements</h2>
                        <span class="vy-eyebrow">{{ count($statements) }}</span>
                    </div>
                    @if (count($statements) > 0)
                        <div class="vy-utable-wrap">
                            <table class="vy-utable">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Period</th>
                                        <th>Gross</th>
                                        <th>Commission</th>
                                        <th>Net payable</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($statements as $st)
                                        <tr>
                                            <td class="vy-u-code">{{ $st['sequence_no'] ?? '—' }}</td>
                                            <td>
                                                <div>{{ $st['period'] }}</div>
                                                @if ($st['finalized'])
                                                    <div class="vy-u-sub">Finalized {{ $st['finalized'] }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $st['gross'] }}</td>
                                            <td>{{ $st['commission'] }}</td>
                                            <td class="vy-u-code">{{ $st['net'] }}</td>
                                            <td>
                                                <span class="vy-tag tone-{{ $st['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $st['status_meta']['label'] }}</span>
                                                @if ($st['has_unverified'])
                                                    <div style="margin-top: 5px;"><span class="vy-ledger-flag">Unverified lines</span></div>
                                                @endif
                                                @if ($st['locked'])
                                                    <div style="margin-top: 5px;"><span class="vy-ledger-flag">Locked</span></div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="vy-empty">No statements for this owner yet.</div>
                    @endif
                </div>

                {{-- Payouts — REAL rows; the method comes from the SHARED RailLabel map, and a failure is
                     reported honestly with its real reason. --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head">
                        <h2 class="vy-card-title">Payouts</h2>
                        <span class="vy-eyebrow">{{ count($payouts) }}</span>
                    </div>
                    <div class="vy-ledger">
                        @forelse ($payouts as $p)
                            <div class="vy-ledger-row">
                                <div class="vy-ledger-main">
                                    <div class="vy-ledger-label">
                                        {{ $p['method'] }}
                                        <span class="vy-tag tone-{{ $p['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $p['status_meta']['label'] }}</span>
                                    </div>
                                    <div class="vy-ledger-sub">
                                        @if ($p['paid_at'])
                                            Paid {{ $p['paid_at'] }}
                                        @elseif ($p['failed_at'])
                                            Failed {{ $p['failed_at'] }}
                                        @else
                                            Not paid yet
                                        @endif
                                        @if ($p['external_ref'])
                                            <span class="vy-sep">·</span> Ref {{ $p['external_ref'] }}
                                        @endif
                                    </div>
                                    @if ($p['failure_reason'])
                                        <div class="vy-ledger-sub">{{ $p['failure_reason'] }}</div>
                                    @endif
                                </div>
                                <div class="vy-ledger-amt tone-{{ $p['status_meta']['tone'] === 'confirmed' ? 'paid' : 'muted' }}">{{ $p['amount'] }}</div>
                            </div>
                        @empty
                            <div class="vy-empty">No payouts for this owner yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ══════════════════════ RIGHT RAIL: identity · portal access · contact · notes ════ --}}
            <div class="vy-stack">

                {{-- Identity — REAL columns only --}}
                <div class="vy-card vy-fade">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                        <span class="vy-owner-avatar">{{ $owner['initials'] }}</span>
                        <div style="min-width: 0;">
                            <div class="vy-detail-ref">{{ $owner['name'] }}</div>
                            @if ($owner['legal_name'])
                                <div class="vy-um-sub">{{ $owner['legal_name'] }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="vy-facts">
                        <div>
                            <div class="vy-fact-label">Type</div>
                            <div class="vy-fact-val">{{ $owner['type_label'] }}</div>
                        </div>
                        <div>
                            <div class="vy-fact-label">Status</div>
                            <div class="vy-fact-val">{{ $owner['status_meta']['label'] }}</div>
                        </div>
                        <div>
                            <div class="vy-fact-label">Payout currency</div>
                            <div class="vy-fact-val">{{ $owner['payout_currency'] }}</div>
                        </div>
                    </div>
                </div>

                {{-- Portal access — the REAL state. `user_id` alone does NOT mean access: an invited owner
                     is already linked to an INACTIVE login, so a live invite is reported as the pending
                     invite it is, and "active" requires the linked login to genuinely be active. The
                     invite/re-invite WRITE lives on the Owners list — linked, never duplicated here. --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head" style="margin-bottom: 12px;">
                        <span class="vy-eyebrow">Portal access</span>
                        <span class="vy-tag tone-{{ $portal['tone'] }}"><span class="vy-dot"></span>{{ $portal['label'] }}</span>
                    </div>
                    @if ($portal['detail'])
                        <div class="vy-um-sub">{{ $portal['detail'] }}</div>
                    @endif
                    <a class="vy-arr-link" href="{{ route('app.owners') }}" style="display: inline-block; margin-top: 10px;">Manage invites →</a>
                </div>

                {{-- Contact — the operator's own CRM record for a counterparty they invited. tax_id and the
                     raw address JSON are deliberately NOT rendered (and not even read). --}}
                <div class="vy-card vy-fade">
                    <span class="vy-eyebrow">Contact</span>
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px; font-size: 13px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex: none;"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>
                            <span style="min-width: 0; overflow-wrap: anywhere;">{{ $owner['email'] ?? 'Not set' }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; font-size: 13px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex: none;"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/></svg>
                            <span style="font-variant-numeric: tabular-nums;">{{ $owner['phone'] ?? 'Not set' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Notes — the REAL `owners.notes` column, read-only. The design's "Add note" write has no
                     backend path, so no composer is offered (omitted, not greyed out). --}}
                @if ($owner['notes'])
                    <div class="vy-card vy-fade">
                        <span class="vy-eyebrow">Notes</span>
                        <div style="margin-top: 10px; font-size: 13px; line-height: 1.55; white-space: pre-line;">{{ $owner['notes'] }}</div>
                    </div>
                @endif
            </div>
        </div>

    </x-operator.shell>
</div>
