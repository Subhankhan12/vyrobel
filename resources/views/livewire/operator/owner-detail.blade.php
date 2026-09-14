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
       • BUILT (#93) — Edit owner (commission → a new effective-dated agreement, "applies from next
                    statement"; portal access; PAUSE PAYOUTS via the real OwnerPayoutPreference the engine
                    respects), Remove owner (a careful ARCHIVE-not-destroy, type-the-name confirm), the
                    MASKED IBAN (····last4, CAP_PAYOUT only, never the full number, no fake "verified"), a
                    real note timeline, the net-to-owner breakdown (reconciles at the real 18%), and
                    paid-this-year + occupancy. All writes controller-routed + audited + read-only-gated.
       • OMITTED  — any estimated next-payout DATE, "Scheduled" statuses, and the design's demo figures —
                    no backing data (never fabricated). Nothing is greyed out; it is simply absent.
       • NOT LINKED — statement rows carry no link: the statement detail is on the OWNER-scoped route,
                    which an operator session cannot pass (it would 404).

     RAW-BLADE RULE: never write `word@directive` — Blade's \B guard would ship the raw source to the
     page as ordinary text. Every `@` below follows a space, a newline or a tag. --}}
<div x-data="{ editOpen: false, removeOpen: false, closeOpen: false }">
    <x-operator.shell active="owners" :title="$owner['name']"
        :subtitle="$owner['type_label'] . ' · Paid in ' . $owner['payout_currency']" :viewer="$viewer">

        @if (session('owners_notice')) <div class="vy-flash is-ok vy-fade">{{ session('owners_notice') }}</div> @endif
        @if (session('owners_error')) <div class="vy-flash is-err vy-fade">{{ session('owners_error') }}</div> @endif

        {{-- Toolbar: back to the real Owners list + Edit / Remove owner --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                <a class="vy-convo-back" href="{{ route('app.owners') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                    All owners
                </a>
            </div>
            <div class="vy-right" style="gap: 8px;">
                @if ($payoutPaused)<span class="vy-tag" style="background: rgba(224,146,30,.16); color: #8A6410;">Payouts paused</span>@endif
                <span class="vy-tag tone-{{ $owner['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $owner['status_meta']['label'] }}</span>
                @if ($canManage)
                    <button type="button" class="vy-btn vy-btn--ghost" @click="editOpen = true">Edit owner</button>
                @endif
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
                <div class="vy-dk-label">Paid this year</div>
                <div class="vy-dk-val">{{ $paidYear['display'] }}</div>
                <div class="vy-dk-sub">{{ $paidYear['count'] }} {{ \Illuminate\Support\Str::plural('payout', $paidYear['count']) }} · {{ $occupancy }}% avg occupancy</div>
            </div>
        </div>

        {{-- Net to owner — the latest finalized statement's REAL breakdown; credits − deductions == net (#17, 18%) --}}
        @if ($netToOwner)
            <div class="vy-card vy-fade" style="margin-top: 16px;">
                <div class="vy-detail-head" style="margin-bottom: 10px;">
                    <h2 class="vy-card-title">Net to owner · {{ $netToOwner['period'] }}</h2>
                    <span class="vy-eyebrow">reconciles to the cent</span>
                </div>
                <div class="vyo-net-grid">
                    <div>
                        <div class="vyo-net-label">Credited</div>
                        @foreach ($netToOwner['credits'] as $c)
                            <div class="vyo-net-row"><span>{{ $c['label'] }}</span><span class="vy-u-code">{{ $c['display'] }}</span></div>
                        @endforeach
                        <div class="vyo-net-row" style="font-weight: 700; border-top: 1px solid var(--vy-hairline);"><span>Credited</span><span class="vy-u-code">{{ $netToOwner['credited_display'] }}</span></div>
                    </div>
                    <div>
                        <div class="vyo-net-label">Deductions <span class="vy-muted">· incl. {{ $commissionPct ?? '—' }}% commission</span></div>
                        @foreach ($netToOwner['deductions'] as $d)
                            <div class="vyo-net-row"><span>{{ $d['label'] }}</span><span class="vy-u-code">{{ $d['display'] }}</span></div>
                        @endforeach
                        <div class="vyo-net-row" style="font-weight: 700; border-top: 1px solid var(--vy-hairline);"><span>Deductions</span><span class="vy-u-code">{{ $netToOwner['deducted_display'] }}</span></div>
                    </div>
                </div>
                <div class="vyo-net-total"><span>Net to owner</span><span class="vy-u-code">{{ $netToOwner['net_display'] }}</span></div>
            </div>
        @endif

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

                {{-- Rate proposals (Wave C · GATE 1) — propose a rate change to the owner (they approve/decline in
                     their portal). rates.manage-gated; the owner must own the room type exclusively (the service refuses
                     a shared one). Money parsed server-side (#17). --}}
                @can('rates.manage')
                    <div class="vy-card vy-fade" style="margin-top:16px" x-data="{ open: false }">
                        <div class="vy-detail-head">
                            <h2 class="vy-card-title">Rate proposals</h2>
                            @if (count($roomTypes) > 0)<button type="button" class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12px" @click="open = !open">Propose a change</button>@endif
                        </div>
                        @if (session('rate_notice'))<div class="vy-empty" style="border-left:3px solid var(--vy-accent);text-align:left;padding:10px 14px;margin-bottom:10px">{{ session('rate_notice') }}</div>@endif
                        @if (session('rate_error'))<div class="vy-empty" style="border-left:3px solid #b8860b;text-align:left;padding:10px 14px;margin-bottom:10px">{{ session('rate_error') }}</div>@endif

                        @if (count($roomTypes) > 0)
                            <form x-show="open" x-cloak method="POST" action="{{ route('app.rate-proposals.store') }}" style="border:1px solid var(--vy-hairline);border-radius:12px;padding:14px;margin-bottom:12px">
                                @csrf
                                <input type="hidden" name="owner_id" value="{{ $ownerId }}">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                                    <label style="font-size:12px;color:var(--vy-muted)">Room type
                                        <select name="room_type_id" class="vy-input" required>@foreach ($roomTypes as $rt)<option value="{{ $rt['id'] }}">{{ $rt['name'] }}</option>@endforeach</select>
                                    </label>
                                    <label style="font-size:12px;color:var(--vy-muted)">Proposed nightly rate ({{ $rateCurrencySymbol }})<input type="text" name="rate" class="vy-input" placeholder="185" required></label>
                                    <label style="font-size:12px;color:var(--vy-muted)">Weekend uplift %<input type="text" name="weekend_uplift" class="vy-input" placeholder="optional"></label>
                                    <label style="font-size:12px;color:var(--vy-muted)">Min stay<input type="text" name="min_stay" class="vy-input" placeholder="optional"></label>
                                    <label style="font-size:12px;color:var(--vy-muted)">From<input type="date" name="from_date" class="vy-input" value="{{ now()->toDateString() }}" required></label>
                                    <label style="font-size:12px;color:var(--vy-muted)">To<input type="date" name="to_date" class="vy-input" value="{{ now()->addYear()->subDay()->toDateString() }}" required></label>
                                </div>
                                <label style="font-size:12px;color:var(--vy-muted);display:block;margin-top:10px">Note to owner (optional)<input type="text" name="note" class="vy-input" maxlength="500"></label>
                                <div style="text-align:right;margin-top:12px"><button type="submit" class="vy-btn vy-btn--accent" style="padding:7px 16px;font-size:12.5px">Send proposal</button></div>
                            </form>
                        @endif

                        @forelse ($proposals as $p)
                            <div class="vy-unit-row">
                                <div>
                                    <div class="vy-um-name">{{ $p['room_type'] }} · {{ $p['summary'] }}</div>
                                    {{-- ⚠️⚠️ THE COUNTER REACHES THE SCREEN (audit M-14 · F1). This line read only
                                         "Status: Countered" while the headline above showed the operator's OWN ask —
                                         a stale number rendered confidently beside a label saying it was superseded. --}}
                                    <div class="vy-um-sub">
                                        Status: {{ ucfirst($p['state']) }}
                                        @if ($p['countered'])
                                            · owner countered at <strong>{{ $p['counter'] }}</strong> (you proposed {{ $p['proposed'] }})
                                        @endif
                                    </div>
                                </div>
                                @if ($p['pending'])
                                    <form method="POST" action="{{ route('app.rate-proposals.withdraw', $p['id']) }}" style="margin:0">
                                        @csrf
                                        <button type="submit" class="vy-btn vy-btn--ghost" style="padding:5px 11px;font-size:11.5px">Withdraw</button>
                                    </form>
                                @elseif ($p['countered'])
                                    {{-- ⚠️⚠️ THE MISSING FRONT DOOR. `app.rate-proposals.accept-counter` was built and
                                         gated `can:rates.manage`, and NO view reached it — so the owner's counter could be
                                         neither seen nor taken. ⚠️ MONEY PATH: accepting applies the OWNER'S rate through
                                         the certified `RateProposalService::acceptCounter()`, which refuses a proposal that
                                         is not countered and audits the applied figure. This adds the door, not the
                                         permission. ⚠️ The amount is ON the button, so the decision names its own number. --}}
                                    <form method="POST" action="{{ route('app.rate-proposals.accept-counter', $p['id']) }}" style="margin:0">
                                        @csrf
                                        <button type="submit" class="vy-btn vy-btn--accent" style="padding:5px 11px;font-size:11.5px">Accept {{ $p['counter'] }}</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div class="vy-empty" style="font-size:12.5px">No rate proposals yet.</div>
                        @endforelse
                    </div>
                @endcan

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
                        <div style="display:flex;align-items:center;gap:10px">
                            <span class="vy-eyebrow">{{ count($statements) }}</span>
                            {{-- ⚠️ AUDIT H-2 — the front door for `app.owners.close-period`. The route, the
                                 permission and OwnerStatementService::finalize() all existed; NO view reached
                                 them, so outside the demo seeder no statement could be finalised and no payout
                                 could ever be created. Gated on the SAME permission as the route
                                 (`payouts.manage`, via $canPayout) so the control and the gate cannot diverge. --}}
                            @if ($canPayout)
                                <button type="button" class="vy-btn vy-btn--ghost" @click="closeOpen = true">Close a period…</button>
                            @endif
                        </div>
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
                        <div class="vy-empty">
                            No statements for this owner yet.
                            @if ($canPayout)
                                <div style="margin-top:6px;font-size:12.5px">Close a period to produce the first one — a payout can only be created from a finalised statement.</div>
                            @endif
                        </div>
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
                        {{-- IBAN — MASKED (····4821), CAP_PAYOUT only, never the full number. The owner entered it
                             themselves in the portal; the operator sees it masked + never enters it. NO fake "verified". --}}
                        @if ($ibanMasked)
                            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex: none;"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                                <span style="font-variant-numeric: tabular-nums;">IBAN {{ $ibanMasked }} <span class="vy-muted">· on file</span></span>
                            </div>
                        @elseif ($canPayout)
                            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--vy-faint);">No payout method on file — the owner adds their IBAN in the portal.</div>
                        @endif

                        {{-- The design pairs the contact block with an action row (Email · Message).
                             ⚠️ Only EMAIL is reproduced, and only when an address actually exists: a `mailto:`
                             is genuinely functional — it opens the operator's own mail client against the
                             address already shown above, so it adds an affordance, not a claim.
                             ⚠️ The design's "Message" button is NOT reproduced. An owner↔operator thread is a
                             real concept (`message_threads.owner_id`), but reaching THIS owner's thread from
                             here would need either a deep link that does not exist or a thread-start WRITE
                             (`app.inbox.start`) — a button that opens an unrelated inbox would misstate what
                             it does. Logged, not faked. --}}
                        @if ($owner['email'])
                            <div class="vyod-contact-actions">
                                <a class="vyod-contact-btn" href="mailto:{{ $owner['email'] }}">Email</a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Notes — a REAL operator-internal timeline (#93): authored + timestamped `noteEntries`, never
                     shown in the owner portal. Add-note is a controller POST (audited). Plus the legacy single note. --}}
                <div class="vy-card vy-fade">
                    <span class="vy-eyebrow">Notes</span>
                    @if ($owner['notes'])
                        <div style="margin-top: 10px; font-size: 13px; line-height: 1.55; white-space: pre-line; padding-bottom: 8px; border-bottom: 1px solid var(--vy-hairline);">{{ $owner['notes'] }}</div>
                    @endif
                    @foreach ($noteEntries as $n)
                        <div style="margin-top: 10px; font-size: 13px; line-height: 1.5;">
                            {{ $n['body'] }}
                            <div style="font-size: 11px; color: var(--vy-faint); margin-top: 2px;">{{ $n['author'] }} · {{ $n['when'] }}</div>
                        </div>
                    @endforeach
                    @if ($canManage)
                        <form method="POST" action="{{ route('app.owners.note', $ownerId) }}" style="margin-top: 12px;">
                            @csrf
                            <label class="vy-sr-only" for="vy-lbl-owner-detail-body">Add an internal note…</label>
                            <textarea id="vy-lbl-owner-detail-body" name="body" rows="2" class="vy-input" placeholder="Add an internal note…" required></textarea>
                            <div style="display: flex; justify-content: flex-end; margin-top: 8px;"><button type="submit" class="vy-btn vy-btn--ghost">Add note</button></div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══ EDIT OWNER modal — commission (→ new agreement) · pause payouts · portal access ══ --}}
        @if ($canManage)
        <div x-show="editOpen" x-cloak class="vyo-scrim" @click.self="editOpen = false">
            <div class="vyo-modal">
                <h2 class="vy-card-title">Edit owner</h2>
                <p class="vy-muted" style="font-size: 12.5px; margin: 2px 0 14px;">{{ $owner['name'] }} · commission changes apply from the next statement.</p>
                <form method="POST" action="{{ route('app.owners.update', $ownerId) }}">
                    @csrf
                    <label class="vy-field-label" for="comm">Management commission (%)</label>
                    <input id="comm" type="number" name="commission_pct" class="vy-input" min="0" max="100" step="0.5" value="{{ $commissionPct }}">
                    <label class="vyo-toggle" style="margin-top: 14px;">
                        <input type="checkbox" name="portal_active" value="1" @checked($portal['tone'] === 'confirmed')>
                        <span>Owner portal access <span class="vy-muted">· statements, payouts & occupancy</span></span>
                    </label>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px;">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="editOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Save changes</button>
                    </div>
                </form>
                {{-- Pause payouts — REAL (OwnerPayoutPreference.paused; the engine skips a paused owner). --}}
                <form method="POST" action="{{ route('app.owners.pause-payouts', $ownerId) }}" style="border-top: 1px solid var(--vy-hairline); margin-top: 16px; padding-top: 14px;">
                    @csrf
                    <input type="hidden" name="paused" value="{{ $payoutPaused ? '0' : '1' }}">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div><strong style="font-size: 13.5px;">{{ $payoutPaused ? 'Payouts are paused' : 'Pause payouts' }}</strong><br><span class="vy-muted" style="font-size: 12px;">Amounts accumulate and carry forward until resumed.</span></div>
                        <button type="submit" class="vy-btn vy-btn--ghost">{{ $payoutPaused ? 'Resume' : 'Pause' }}</button>
                    </div>
                </form>
                <div style="border-top: 1px solid var(--vy-hairline); margin-top: 16px; padding-top: 14px; text-align: center;">
                    <button type="button" class="vy-btn vy-btn--ghost" style="color: #B4400F;" @click="editOpen = false; removeOpen = true">Remove owner…</button>
                </div>
            </div>
        </div>

        {{-- ══ REMOVE OWNER modal — a careful archive-not-destroy (type the name to confirm) ══ --}}
        <div x-show="removeOpen" x-cloak class="vyo-scrim" @click.self="removeOpen = false">
            <div class="vyo-modal">
                <h2 class="vy-card-title">Remove {{ $owner['name'] }}</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 4px 0 12px;">Removing {{ $owner['name'] }} means:</p>
                <ul style="font-size: 12.5px; color: var(--vy-ink); line-height: 1.7; padding-left: 18px; margin: 0 0 14px;">
                    <li>@if (count($removeUnitNames)){{ implode(', ', $removeUnitNames) }} become @else Their units become @endif unassigned — <strong>bookings stay intact</strong></li>
                    {{-- ⚠️ THIS LINE USED TO BE FALSE. It said "Portal access ends immediately" while the owner's
                         TEAM kept theirs — measured: an active sub-user still reached /owner, and a payout-preference
                         write still LANDED on the removed owner. Removal now cascades to the team, so the claim is
                         true — and it is spelled out rather than merely restored, because an operator deserves to
                         know the team goes with the owner. --}}
                    <li>Portal access ends immediately — for {{ $owner['name'] }} <strong>and everyone on their team</strong>@if ($removeTeamCount > 0) ({{ $removeTeamCount }} {{ $removeTeamCount === 1 ? 'person' : 'people' }})@endif</li>
                    {{-- ⚠️ THIS LINE USED TO BE TOO NARROW. It said "Anyone on the TEAM", because the sparing rule
                         (OwnerLoginLifecycle — a login survives while the person still holds a back-office role)
                         was applied to sub-users only; the owner's OWN login was deactivated unconditionally.
                         That let an actor with `users.manage` but not `roles.manage` strip an org of role
                         management by removing the owner its sole role-manager was linked to. The rule now
                         governs both, so the copy names both. --}}
                    <li>Anyone who also works for you — <strong>including {{ $owner['name'] }}</strong> — keeps their own login; only their access to this owner ends</li>
                    <li>All statements &amp; payout records <strong>stay archived for accounting</strong></li>
                    <li>Any scheduled final payout is honored before the account closes</li>
                </ul>
                <form method="POST" action="{{ route('app.owners.remove', $ownerId) }}">
                    @csrf
                    <label class="vy-field-label" for="conf">Type the owner's name to confirm</label>
                    <input id="conf" type="text" name="confirm_name" class="vy-input" placeholder="{{ $owner['name'] }}" autocomplete="off">
                    {{-- ⚠️ RE-AUTH (G2): the name above is printed on this page, so a hijacked session can simply read it.
                         The password is the factor such a session may not have. --}}
                    <label class="vy-field-label" for="remove_owner_pw" style="margin-top:12px">Confirm with your password</label>
                    <input id="remove_owner_pw" type="password" name="current_password" class="vy-input"
                           autocomplete="current-password" required aria-label="Current password">
                    @error('current_password') <span style="color:#B4400F;font-size:11.5px">{{ $message }}</span> @enderror
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="removeOpen = false">Keep owner</button>
                        <button type="submit" class="vy-btn vy-btn--accent" style="background: #B4400F;">Remove owner permanently</button>
                    </div>
                </form>
                <p style="font-size: 11px; color: var(--vy-faint); margin: 12px 0 0; text-align: center;">This can't be undone · re-adding them later starts a fresh owner record.</p>
            </div>
        </div>
        @endif

        {{-- ══ CLOSE A PERIOD modal (audit H-2) ══════════════════════════════════════════════════════
             ⚠️ EVERY CLAIM BELOW WAS VERIFIED AGAINST THE CODE BEFORE IT WAS WRITTEN:
               · finalize() sets `locked = true` and the statement cannot be edited afterwards;
               · PeriodLockGuard marks a later line dated inside a sealed period `prior_period_adjustment`,
                 which carries onto the NEXT statement and never touches the sealed one;
               · the collection gate leaves uncollected revenue unclaimed for a later statement;
               · closing does NOT pay anyone — a payout is created separately on the Payout run screen.
             ⚠️ It deliberately promises NO timing and does not claim the owner is emailed: a notification
             fires only when the owner has a linked user account, so the portal is the unconditional truth. --}}
        @if ($canPayout)
        <div x-show="closeOpen" x-cloak class="vyo-scrim" @click.self="closeOpen = false">
            <div class="vyo-modal">
                <h2 class="vy-card-title">Close a period for {{ $owner['name'] }}</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 4px 0 12px;">This finalises a statement. It cannot be undone.</p>
                <ul style="font-size: 12.5px; color: var(--vy-ink); line-height: 1.7; padding-left: 18px; margin: 0 0 14px;">
                    <li>The statement is <strong>sealed</strong> — its figures can no longer change</li>
                    <li>Anything that arrives later dated inside this period becomes a <strong>prior-period adjustment</strong> on the next statement</li>
                    <li>Revenue not yet collected from guests <strong>stays unclaimed</strong> and appears on a later statement</li>
                    <li>{{ $owner['name'] }} can see the statement in their owner portal</li>
                    <li><strong>Nothing is paid by this.</strong> A payout is created separately on the Payout run screen</li>
                </ul>
                @if ($closePeriod['lockedThrough'])
                    <p style="font-size:12px;color:var(--vy-muted);margin:0 0 12px;">Already sealed for this owner up to <strong>{{ $closePeriod['lockedThrough'] }}</strong>.</p>
                @endif
                <form method="POST" action="{{ route('app.owners.close-period', $ownerId) }}">
                    @csrf
                    <div style="display:flex;gap:10px">
                        <div style="flex:1">
                            <label class="vy-field-label" for="period_start">Period start</label>
                            <input id="period_start" type="date" name="period_start" class="vy-input" value="{{ $closePeriod['start'] }}" @if ($closePeriod['minStart']) min="{{ $closePeriod['minStart'] }}" @endif required>
                        </div>
                        <div style="flex:1">
                            <label class="vy-field-label" for="period_end">Period end</label>
                            <input id="period_end" type="date" name="period_end" class="vy-input" value="{{ $closePeriod['end'] }}" @if ($closePeriod['minStart']) min="{{ $closePeriod['minStart'] }}" @endif required>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="closeOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Close period &amp; finalise statement</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </x-operator.shell>

    <style>
        [x-cloak] { display: none !important; }
        /* Contact action row (the design pairs the contact block with actions). */
        .vyod-contact-actions { display: flex; gap: 8px; margin-top: 12px; }
        .vyod-contact-btn { flex: 1; text-align: center; padding: 9px 14px; border-radius: var(--vy-r-md); border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.6); color: var(--vy-ink); font-size: 12.5px; font-weight: 600; text-decoration: none; }
        .vyod-contact-btn:hover { border-color: var(--vy-accent-border, var(--vy-hairline)); background: rgba(255, 255, 255, 0.85); }
        .vyo-net-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .vyo-net-label { font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--vy-faint); margin-bottom: 6px; }
        .vyo-net-row { display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; font-size: 12.5px; }
        .vyo-net-total { display: flex; justify-content: space-between; border-top: 2px solid var(--vy-ink); margin-top: 12px; padding-top: 10px; font-weight: 700; font-size: 14px; }
        .vyo-scrim { position: fixed; inset: 0; z-index: 60; background: rgba(43,35,32,.42); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 20px; }
        .vyo-modal { width: 440px; max-width: 100%; background: var(--vy-surface, #FFFCF8); border-radius: 16px; padding: 22px; box-shadow: 0 24px 60px rgba(150,90,50,.24); max-height: 90vh; overflow-y: auto; }
        .vyo-toggle { display: flex; gap: 9px; align-items: center; font-size: 13px; cursor: pointer; }
        @media (max-width: 640px) { .vyo-net-grid { grid-template-columns: 1fr; } }
    </style>
</div>
