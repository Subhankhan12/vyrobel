{{-- Operator OWNER'S PAGE — rebuilt 2026-10-03 (the founder: "modal design and buttons are mess … confirm with your password and
     its box is not well aligned … all the sort of work you have done in the previous pages … in this module … make sure
     everything works very well").

     The operator's TENANT-scoped lens on ONE owner (App\Livewire\Operator\OperatorOwnerDetail holds the full REAL / DERIVED /
     NOT OFFERED map): who they are and how to reach them, the actions on them in one bar, the money (net to owner, statements,
     payouts), the agreement, their units and rate proposals — and beside it how they are paid, their portal access and the
     team's notes.

     Writes, each a POST through every middleware (#20), each offered only where its route would accept it: the commission
     (CommissionChange — the latest change wins), pause / resume payouts, close a period, remove (an archive: the owner's name
     and your password), a note; a waiting invite sent again or cancelled; a rate proposal. ⚠️ A refused write reopens its own
     dialog with the reason beside the field (`_form` names it) — before 2026-10-03 it closed and the page said nothing.

     ⚠️ Every dialog is the shared centred one and every display is a CLASS — x-show erases an inline display. ⚠️ No control a
     role may not use reaches that role's page: a Hotel Manager reads this page and gets no owner write — only the rate proposal
     controls its `rates.manage` allows.
     ⚠️ RAW-BLADE RULE: never `word@directive`. Alpine handlers use `x-on:` / `x-bind:`. --}}
<div>
    {{-- The page heading is the kind of page, as on a work order; the owner's name heads the card below it, once. --}}
    <x-operator.shell active="owners" title="Owner" subtitle="Their statements, payouts, agreement and units" :viewer="$viewer">
        @php
            // Which dialog a refused write reopens: the form says which it was (`_form`), and what was typed comes back.
            $vyForm = (string) old('_form');
            $vyRefused = $errors->any() || session('owners_error') || session('rate_error');
            $vyAllowed = array_values(array_filter([
                $canManage ? 'commission' : null, $canPayout ? 'close' : null, $canManage ? 'remove' : null,
                $canManage && $invite !== null ? 'resend' : null, count($roomTypes) > 0 ? 'propose' : null,
            ]));
            $vyOpen = $vyRefused && in_array($vyForm, $vyAllowed, true) ? $vyForm : '';
            // A dialog that reopens shows its own refusal; the page-level flash then stays quiet.
            $vyOwnersErrorTop = session('owners_error') && ! in_array($vyOpen, ['commission', 'close', 'remove', 'resend'], true);
            $vyRateErrorTop = session('rate_error') && $vyOpen !== 'propose';
            $vyNoteError = $vyForm === 'note' ? $errors->first('body') : '';
            $vyStrayErrors = $vyOpen === '' && $vyForm !== 'note' ? $errors->all() : [];
        @endphp

        @if (session('owners_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                {{ session('owners_notice') }}
            </div>
        @endif
        @if ($vyOwnersErrorTop || $vyStrayErrors !== [])
            <div class="vy-flash is-err vyo-flash vy-fade" role="alert">
                @if ($vyOwnersErrorTop)
                    <div>{{ session('owners_error') }}</div>
                @endif
                @foreach ($vyStrayErrors as $vyError)
                    <div>{{ $vyError }}</div>
                @endforeach
            </div>
        @endif

        <div class="vyo-root vyo-ownerpage" x-data="vyOwnerPage({
                commissionOpen: @js($vyOpen === 'commission'), pauseOpen: false, closeOpen: @js($vyOpen === 'close'),
                removeOpen: @js($vyOpen === 'remove'), resendOpen: @js($vyOpen === 'resend'), cancelOpen: false,
                proposeOpen: @js($vyOpen === 'propose'),
                start: @js((string) old('period_start', $closePeriod['start'])), end: @js((string) old('period_end', $closePeriod['end'])),
            })">

            <a class="vy-convo-back vyo-back" href="{{ route('app.owners') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                All owners
            </a>

            {{-- ══ WHO — and every action on them, in one bar ══════════════════════════════════════════════════════════════ --}}
            <section class="vy-card vyo-hero vy-fade" aria-labelledby="vyod-name">
                <div class="vyo-hero-id">
                    <span class="vy-owner-avatar vyo-hero-avatar" aria-hidden="true">{{ $owner['initials'] }}</span>
                    <div class="vyo-hero-who">
                        <h2 class="vyo-hero-name" id="vyod-name">{{ $owner['name'] }}</h2>
                        @if ($owner['legal_name'])
                            <div class="vyo-hero-legal">{{ $owner['legal_name'] }}</div>
                        @endif
                        <div class="vyo-hero-tags">
                            <span class="vy-tag tone-{{ $owner['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $owner['status_meta']['label'] }}</span>
                            <span class="vyo-hero-fact">{{ $owner['type_label'] }}</span>
                            <span class="vyo-hero-fact">Paid in {{ $owner['payout_currency'] }}</span>
                            @if ($payoutPaused)
                                <span class="vyo-tag-paused">Payouts paused</span>
                            @endif
                        </div>
                        {{-- The operator's own record of a counterparty they invited (tax id and address are never read). --}}
                        <div class="vyo-hero-contact">
                            @if ($owner['email'])
                                <a class="vyo-hero-link" href="mailto:{{ $owner['email'] }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>
                                    <span>{{ $owner['email'] }}</span>
                                </a>
                            @else
                                <span class="vyo-hero-none">No email on record</span>
                            @endif
                            @if ($owner['phone'])
                                <a class="vyo-hero-link" href="tel:{{ preg_replace('/[^0-9+]/', '', $owner['phone']) }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/></svg>
                                    <span class="vyo-num">{{ $owner['phone'] }}</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($canManage || $canPayout)
                    <div class="vyo-hero-actions">
                        @if ($canManage)
                            <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="commissionOpen = true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 5L5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                                Change commission
                            </button>
                            <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="pauseOpen = true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">@if ($payoutPaused)<path d="M8 5l11 7-11 7z"/>@else<path d="M9 5v14M15 5v14"/>@endif</svg>
                                {{ $payoutPaused ? 'Resume payouts' : 'Pause payouts' }}
                            </button>
                        @endif
                        @if ($canPayout)
                            <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="closeOpen = true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M9 15l2 2 4-4"/></svg>
                                Close a period
                            </button>
                        @endif
                        @if ($canManage)
                            <button type="button" class="vy-btn vy-btn--ghost vyo-sm is-danger" x-on:click="removeOpen = true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg>
                                Remove owner
                            </button>
                        @endif
                    </div>
                @endif
            </section>

            {{-- ══ The figures — DERIVED from the real ledger and payouts, per currency, never summed across (#17) ═════════ --}}
            <div class="vy-dash-kpis vyo-kpis vy-fade">
                <div class="vy-dash-kpi is-feature">
                    <div class="vy-dk-label">Balance</div>
                    @foreach ($kpis['balance'] as $line)
                        <div class="vy-dk-val {{ $loop->first ? '' : 'vyo-kpi-extra' }}">{{ $line }}</div>
                    @endforeach
                    <div class="vy-dk-sub">Every owner-ledger line, netted{{ $kpis['multi_currency'] ? ' · per currency, never summed across' : '' }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Paid out</div>
                    @foreach ($kpis['paid'] as $line)
                        <div class="vy-dk-val {{ $loop->first ? '' : 'vyo-kpi-extra' }}">{{ $line }}</div>
                    @endforeach
                    <div class="vy-dk-sub">Payouts marked paid</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Waiting to be paid</div>
                    @foreach ($kpis['pending'] as $line)
                        <div class="vy-dk-val {{ $loop->first ? '' : 'vyo-kpi-extra' }}">{{ $line }}</div>
                    @endforeach
                    <div class="vy-dk-sub">Payouts created, not paid yet</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Paid this year</div>
                    <div class="vy-dk-val">{{ $paidYear['display'] }}</div>
                    <div class="vy-dk-sub">{{ $paidYear['count'] }} {{ \Illuminate\Support\Str::plural('payout', $paidYear['count']) }} · {{ $occupancy }}% occupancy</div>
                </div>
            </div>

            <div class="vyo-layout">
                <div class="vyo-main">

                    {{-- Net to owner — the latest finalised statement's REAL breakdown; credited − deducted == net (#17). --}}
                    @if ($netToOwner)
                        <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-net-t">
                            <div class="vy-detail-head vyo-card-head">
                                <h2 class="vy-card-title" id="vyod-net-t">Net to owner · {{ $netToOwner['period'] }}</h2>
                                <span class="vy-eyebrow">reconciles to the cent</span>
                            </div>
                            <div class="vyo-net-grid">
                                <div>
                                    <div class="vyo-net-label">Credited</div>
                                    @foreach ($netToOwner['credits'] as $c)
                                        <div class="vyo-net-row"><span>{{ $c['label'] }}</span><span class="vy-u-code">{{ $c['display'] }}</span></div>
                                    @endforeach
                                    <div class="vyo-net-row is-sum"><span>Credited</span><span class="vy-u-code">{{ $netToOwner['credited_display'] }}</span></div>
                                </div>
                                <div>
                                    <div class="vyo-net-label">Deducted <span class="vyo-net-note">incl. {{ $netToOwner['commission_pct'] ?? '—' }}% commission</span></div>
                                    @foreach ($netToOwner['deductions'] as $d)
                                        <div class="vyo-net-row"><span>{{ $d['label'] }}</span><span class="vy-u-code">{{ $d['display'] }}</span></div>
                                    @endforeach
                                    <div class="vyo-net-row is-sum"><span>Deducted</span><span class="vy-u-code">{{ $netToOwner['deducted_display'] }}</span></div>
                                </div>
                            </div>
                            <div class="vyo-net-total"><span>Net to owner</span><span class="vy-u-code">{{ $netToOwner['net_display'] }}</span></div>
                        </section>
                    @endif

                    {{-- Statements — REAL rows. No link: the statement page is the OWNER's, and an operator would get a 404. --}}
                    <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-st-t">
                        <div class="vy-detail-head vyo-card-head">
                            <h2 class="vy-card-title" id="vyod-st-t">Statements <span class="vyo-count">{{ count($statements) }}</span></h2>
                            {{-- ⚠️ AUDIT H-2 — the front door for `app.owners.close-period`, gated on the route's own permission. --}}
                            @if ($canPayout)
                                <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="closeOpen = true">Close a period</button>
                            @endif
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
                                                <td class="vyo-num">{{ $st['gross'] }}</td>
                                                <td class="vyo-num">{{ $st['commission'] }}</td>
                                                <td class="vy-u-code">{{ $st['net'] }}</td>
                                                <td>
                                                    <span class="vy-tag tone-{{ $st['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $st['status_meta']['label'] }}</span>
                                                    @if ($st['has_unverified'] || $st['locked'])
                                                        <div class="vyo-flags">
                                                            @if ($st['has_unverified'])<span class="vy-ledger-flag">Unverified lines</span>@endif
                                                            @if ($st['locked'])<span class="vy-ledger-flag">Locked</span>@endif
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="vy-empty vyo-empty">
                                No statements for this owner yet.
                                @if ($canPayout)
                                    <p class="vyo-empty-sub">Close a period to produce the first one — a payout can only be created from a finalised statement.</p>
                                @endif
                            </div>
                        @endif
                    </section>

                    {{-- Payouts — REAL rows; the method from the SHARED RailLabel map, a failure with its real reason. --}}
                    <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-po-t">
                        <div class="vy-detail-head vyo-card-head">
                            <h2 class="vy-card-title" id="vyod-po-t">Payouts <span class="vyo-count">{{ count($payouts) }}</span></h2>
                            @if ($canPayout)
                                <a class="vyo-card-link" href="{{ route('app.payouts.run') }}">Payout run</a>
                            @endif
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
                                <div class="vy-empty vyo-empty">No payouts for this owner yet.</div>
                            @endforelse
                        </div>
                    </section>

                    {{-- Management agreement — the terms in effect today (the model's certified effectiveOn); "Not set", never "0%". --}}
                    <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-ag-t">
                        <div class="vy-detail-head vyo-card-head">
                            <h2 class="vy-card-title" id="vyod-ag-t">Management agreement</h2>
                            @if ($agreement)
                                <span class="vy-tag tone-{{ $agreement['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $agreement['status_meta']['label'] }}</span>
                            @endif
                        </div>
                        @if ($agreement)
                            <div class="vy-eyebrow vyo-sublabel">In effect today</div>
                            <div class="vy-facts">
                                <div>
                                    <div class="vy-fact-label">Commission</div>
                                    <div class="vy-fact-val">{{ $agreement['commission_display'] ?? 'Not set' }}</div>
                                </div>
                                <div>
                                    <div class="vy-fact-label">Management fee</div>
                                    <div class="vy-fact-val">{{ $agreement['fee_display'] ?? 'Not set' }}{{ $agreement['fee_display'] && $agreement['fee_interval'] ? ' · ' . $agreement['fee_interval'] : '' }}</div>
                                </div>
                                <div>
                                    <div class="vy-fact-label">Payout schedule</div>
                                    <div class="vy-fact-val">{{ $agreement['schedule'] ?? 'Not set' }}</div>
                                </div>
                                <div>
                                    <div class="vy-fact-label">Payout currency</div>
                                    <div class="vy-fact-val">{{ $agreement['payout_currency'] ?? 'Not set' }}</div>
                                    @if ($agreement['payout_currency_source'])
                                        {{-- ⚠️ `.vy-um-sub` is sized only inside a unit row; here it printed at the body size --}}
                                        <div class="vyo-fact-note">{{ $agreement['payout_currency_source'] }}</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="vy-fact-label">Scope</div>
                                    <div class="vy-fact-val">{{ $agreement['scope_label'] }}</div>
                                </div>
                                <div>
                                    <div class="vy-fact-label">Effective</div>
                                    <div class="vy-fact-val">{{ $agreement['from_display'] ?? '—' }} → {{ $agreement['to_display'] ?? 'Open-ended' }}</div>
                                </div>
                            </div>
                        @else
                            <div class="vy-empty vyo-empty">No management agreement is in effect for this owner today.</div>
                        @endif

                        {{-- Other and earlier terms — REAL rows; one not in effect carries a factual DATE note, never an invented status. --}}
                        @if (count($agreementHistory) > 0)
                            <div class="vy-eyebrow vyo-sublabel is-spaced">Other terms on record</div>
                            <div class="vy-utable-wrap">
                                <table class="vy-utable vyo-terms-table">
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
                                                <td>{{ $h['never_applied'] ? 'Never applied' : ($h['from_display'] ?? '—') . ' → ' . ($h['to_display'] ?? 'Open-ended') }}</td>
                                                <td><span class="vy-tag tone-{{ $h['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $h['status_meta']['label'] }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>

                    {{-- Units — REAL, filtered by owner_id --}}
                    <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-un-t">
                        <div class="vy-detail-head vyo-card-head">
                            <h2 class="vy-card-title" id="vyod-un-t">Units <span class="vyo-count">{{ count($units) }}</span></h2>
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
                            <div class="vy-empty vyo-empty">No units are assigned to this owner yet.</div>
                        @endforelse
                    </section>

                    {{-- Rate proposals — propose a change; the owner approves, counters or declines it in their portal. Money is
                         parsed on the server (#17); the owner must own the room type outright (the service refuses a shared one). --}}
                    @can('rates.manage')
                        <section class="vy-card vyo-card vy-fade" aria-labelledby="vyod-rp-t">
                            <div class="vy-detail-head vyo-card-head">
                                <h2 class="vy-card-title" id="vyod-rp-t">Rate proposals</h2>
                                @if (count($roomTypes) > 0)
                                    <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="proposeOpen = true">Propose a change</button>
                                @endif
                            </div>
                            @if (session('rate_notice'))
                                <div class="vyo-inline-note is-ok" role="status">{{ session('rate_notice') }}</div>
                            @endif
                            @if ($vyRateErrorTop)
                                <div class="vyo-inline-note is-err" role="alert">{{ session('rate_error') }}</div>
                            @endif

                            @forelse ($proposals as $p)
                                <div class="vy-unit-row">
                                    <div>
                                        <div class="vy-um-name">{{ $p['room_type'] }} · {{ $p['summary'] }}</div>
                                        {{-- ⚠️⚠️ THE COUNTER REACHES THE SCREEN (audit M-14 · F1) — the owner's figure is the headline, the
                                             operator's own ask kept beside it. --}}
                                        <div class="vy-um-sub">
                                            Status: {{ ucfirst($p['state']) }}
                                            @if ($p['countered'])
                                                · owner countered at <strong>{{ $p['counter'] }}</strong> (you proposed {{ $p['proposed'] }})
                                            @endif
                                        </div>
                                    </div>
                                    @if ($p['pending'])
                                        <form method="POST" action="{{ route('app.rate-proposals.withdraw', $p['id']) }}" class="vyo-inline-form" data-guard-submit>
                                            @csrf
                                            <button type="submit" class="vy-btn vy-btn--ghost vyo-sm">Withdraw</button>
                                        </form>
                                    @elseif ($p['countered'])
                                        {{-- ⚠️⚠️ MONEY PATH: accepting applies the OWNER'S rate through the certified
                                             RateProposalService::acceptCounter(). The amount is on the button. --}}
                                        <form method="POST" action="{{ route('app.rate-proposals.accept-counter', $p['id']) }}" class="vyo-inline-form" data-guard-submit>
                                            @csrf
                                            <button type="submit" class="vy-btn vy-btn--accent vyo-sm">Accept {{ $p['counter'] }}</button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <div class="vy-empty vyo-empty">No rate proposals yet.</div>
                            @endforelse
                        </section>
                    @endcan
                </div>

                {{-- ══ RAIL — how they're paid · the owner portal · notes ═══════════════════════════════════════════════════ --}}
                <aside class="vyo-rail">
                    <section class="vy-card vyo-terms vy-fade" aria-labelledby="vyod-pay-t">
                        <h2 class="vy-card-title" id="vyod-pay-t">How they’re paid</h2>
                        <dl class="vyo-facts">
                            <div>
                                <dt>Commission</dt>
                                <dd>
                                    <strong class="vyo-terms-big">{{ $commission['now'] ?? 'Not set' }}</strong>
                                    @if ($commission['next'])
                                        <span class="vyo-terms-next">Changes to {{ $commission['next']['display'] }} on {{ $commission['next']['from'] }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt>Payouts</dt>
                                @if ($payoutPaused)
                                    {{-- DEC-n (2026-10-02): a pause stops EVERY payout — the payout service refuses a paused owner. --}}
                                    <dd><strong class="vyo-paused-word">Paused.</strong> No payout is created or sent until resumed — closed statements stay owed.</dd>
                                @else
                                    <dd>Not paused — paid from the payout run.</dd>
                                @endif
                            </div>
                            <div>
                                <dt>Schedule</dt>
                                <dd>{{ $agreement['schedule'] ?? 'Not set' }} · in {{ $agreement['payout_currency'] ?? $owner['payout_currency'] }}</dd>
                            </div>
                            {{-- MASKED, payouts.manage only, never the full number, never entered here (#28). --}}
                            @if ($canPayout)
                                <div>
                                    <dt>Payout account</dt>
                                    @if ($ibanMasked)
                                        <dd class="vyo-num">IBAN {{ $ibanMasked }} <span class="vyo-dd-note">on file</span></dd>
                                    @else
                                        <dd>None yet — the owner adds it in their portal.</dd>
                                    @endif
                                </div>
                            @endif
                        </dl>
                    </section>

                    {{-- Portal access — the REAL state: a live invite is the pending invite it is; "active" needs an active login. --}}
                    <section class="vy-card vyo-portalcard vy-fade" aria-labelledby="vyod-portal-t">
                        <div class="vy-detail-head vyo-card-head">
                            <h2 class="vy-card-title" id="vyod-portal-t">Owner portal</h2>
                            <span class="vy-tag tone-{{ $portal['tone'] }}"><span class="vy-dot"></span>{{ $portal['label'] }}</span>
                        </div>
                        @if ($portal['detail'])
                            <p class="vyo-card-sub">{{ $portal['detail'] }}</p>
                        @endif
                        @if ($canManage && $invite !== null)
                            <p class="vyo-card-sub">The invite went to <strong>{{ $invite['email'] }}</strong>.</p>
                            <div class="vyo-portal-actions">
                                <button type="button" class="vy-btn vy-btn--ghost vyo-sm" x-on:click="resendOpen = true">Send invite again</button>
                                @if ($invite['cancellable'])
                                    <button type="button" class="vy-btn vy-btn--ghost vyo-sm is-danger" x-on:click="cancelOpen = true">Cancel invite</button>
                                @endif
                            </div>
                        @else
                            <a class="vyo-card-link" href="{{ route('app.owners') }}">All owners and invites</a>
                        @endif
                    </section>

                    {{-- Notes — the team's own timeline, never shown in the owner portal. --}}
                    <section class="vy-card vyo-notes vy-fade" aria-labelledby="vyod-notes-t">
                        <h2 class="vy-card-title" id="vyod-notes-t">Notes</h2>
                        @if ($owner['notes'])
                            <div class="vyo-note vyo-note-legacy">{{ $owner['notes'] }}</div>
                        @endif
                        @foreach ($noteEntries as $n)
                            <div class="vyo-note">
                                <div class="vyo-note-body">{{ $n['body'] }}</div>
                                <div class="vyo-note-meta">{{ $n['author'] }} · {{ $n['when'] }}</div>
                            </div>
                        @endforeach
                        @if (! $owner['notes'] && count($noteEntries) === 0)
                            <p class="vyo-card-sub">No notes yet.</p>
                        @endif
                        @if ($canManage)
                            <form method="POST" action="{{ route('app.owners.note', $ownerId) }}" class="vyo-note-form" data-guard-submit>
                                @csrf
                                <input type="hidden" name="_form" value="note">
                                <label class="vy-sr-only" for="vyod-note">Add a note for your team</label>
                                <textarea id="vyod-note" name="body" rows="2" class="vy-input" placeholder="Add a note for your team…" maxlength="2000" required>{{ $vyForm === 'note' ? old('body') : '' }}</textarea>
                                @if ($vyNoteError !== '')
                                    <div class="vyo-fielderr">{{ $vyNoteError }}</div>
                                @endif
                                <div class="vyo-note-foot">
                                    <span class="vyo-hint">Only your team sees notes.</span>
                                    <button type="submit" class="vy-btn vy-btn--ghost vyo-sm">Add note</button>
                                </div>
                            </form>
                        @endif
                    </section>
                </aside>
            </div>

            {{-- ══════════════════════════════════════════ THE DIALOGS ══════════════════════════════════════════════════════ --}}
            @if ($canManage)
                {{-- ══ CHANGE THE COMMISSION — the ONE rule in CommissionChange: from the first of next month, the latest wins ══ --}}
                <div class="vy-modal-backdrop" x-cloak x-show="commissionOpen" x-transition.opacity x-on:click.self="commissionOpen = false" x-on:keydown.escape.window="commissionOpen = false">
                    <div class="vy-modal vy-modal-card vyo-dlg" x-show="commissionOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-commission-t" tabindex="-1" x-trap.noscroll="commissionOpen">
                        <form method="POST" action="{{ route('app.owners.update', $ownerId) }}" data-guard-submit>
                            @csrf
                            <input type="hidden" name="_form" value="commission">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 5L5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyod-commission-t">Change the commission</h3>
                                    <p class="vyo-dlg-sub">{{ $owner['name'] }} · now {{ $commission['now'] ?? 'not set' }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="commissionOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($vyOpen === 'commission' && session('owners_error'))
                                    <div class="vyo-errors" role="alert">{{ session('owners_error') }}</div>
                                @endif
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vyod-rate">Commission from {{ $commission['starts_today'] ? 'today' : $commission['starts_on'] }}</label>
                                    <div class="vyo-rate">
                                        <input id="vyod-rate" class="vy-input" type="text" inputmode="decimal" name="commission_pct" maxlength="6" required autofocus
                                               value="{{ $vyForm === 'commission' ? old('commission_pct') : $commission['prefill'] }}" placeholder="e.g. 18">
                                        <span class="vyo-rate-unit" aria-hidden="true">%</span>
                                    </div>
                                    @if ($vyForm === 'commission')
                                        @error('commission_pct')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <ul class="vyo-factlist">
                                    @if ($commission['starts_today'])
                                        <li>It starts today — {{ $owner['name'] }} has no agreement in effect.</li>
                                    @else
                                        <li>It starts on {{ $commission['starts_on'] }}.</li>
                                    @endif
                                    {{-- ⚠️ "worked out", not "booked" — earnings are worked out when a booking is made, later for one imported
                                         without them, and again when a booking moves to another owner's unit (the review) --}}
                                    <li>It applies to bookings whose owner earnings are worked out after you save — usually as they are booked. Bookings already worked out keep their rate, and closed statements don’t change.</li>
                                    @if ($commission['next'] && $commission['next']['replaced_by_save'])
                                        <li>It replaces the change to {{ $commission['next']['display'] }} waiting for {{ $commission['next']['from'] }}.</li>
                                    @endif
                                    @if ($commission['starts_today'])
                                        {{-- CommissionChange's defaults when there is no agreement to carry terms from --}}
                                        <li>It is a new agreement, paid monthly in {{ $owner['payout_currency'] }}. Recorded in the audit log.</li>
                                    @else
                                        <li>Every other term of the agreement stays as it is. Recorded in the audit log.</li>
                                    @endif
                                </ul>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="commissionOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Save commission</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ══ PAUSE / RESUME PAYOUTS — DEC-n (2026-10-02): a pause stops EVERY payout; the payout service refuses a paused
                     owner, so the dialog can say so plainly. ════════════════════════════════════════════════════════════════ --}}
                <div class="vy-modal-backdrop" x-cloak x-show="pauseOpen" x-transition.opacity x-on:click.self="pauseOpen = false" x-on:keydown.escape.window="pauseOpen = false">
                    <div class="vy-modal vy-modal-card vyo-dlg" x-show="pauseOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-pause-t" tabindex="-1" x-trap.noscroll="pauseOpen">
                        <form method="POST" action="{{ route('app.owners.pause-payouts', $ownerId) }}" data-guard-submit>
                            @csrf
                            <input type="hidden" name="paused" value="{{ $payoutPaused ? '0' : '1' }}">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico {{ $payoutPaused ? '' : 'is-danger' }}" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyod-pause-t">{{ $payoutPaused ? 'Resume payouts to ' : 'Pause payouts to ' }}{{ $owner['name'] }}?</h3>
                                    <p class="vyo-dlg-sub">{{ $payoutPaused ? 'Their payouts can go out again.' : 'No payout goes out to them until you resume.' }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="pauseOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                <ul class="vyo-factlist">
                                    @if ($payoutPaused)
                                        <li>Every closed statement still owed can be paid again from the payout run{{ $engineOn ? ', and by the next automatic run' : '' }}.</li>
                                    @else
                                        <li>No payout is created or sent to them — not by the automatic run, not by hand on the payout run page.</li>
                                        <li>Nothing they are owed is lost — each closed statement stays owed until it is paid.</li>
                                        <li>Statements still close as usual.</li>
                                    @endif
                                    <li>Recorded in the audit log.</li>
                                </ul>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="pauseOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn {{ $payoutPaused ? 'vy-btn--accent' : 'vy-btn--danger' }}">{{ $payoutPaused ? 'Resume payouts' : 'Pause payouts' }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ══ REMOVE OWNER — an ARCHIVE, not a delete: the owner's name and your password (G2) ══════════════════════════
                     The founder's report: the password box sat under a shouted label with no border, and Keep and Remove were two
                     sizes. Now the password has its own row and both buttons share the foot at one height. --}}
                <div class="vy-modal-backdrop" x-cloak x-show="removeOpen" x-transition.opacity x-on:click.self="removeOpen = false" x-on:keydown.escape.window="removeOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide vyo-dlg" x-show="removeOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-remove-t" tabindex="-1" x-trap.noscroll="removeOpen">
                        <form method="POST" action="{{ route('app.owners.remove', $ownerId) }}" data-guard-submit>
                            @csrf
                            <input type="hidden" name="_form" value="remove">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico is-danger" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyod-remove-t">Remove {{ $owner['name'] }}?</h3>
                                    <p class="vyo-dlg-sub">An archive, not a delete — bookings, statements and payouts are all kept.</p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="removeOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($vyOpen === 'remove' && session('owners_error'))
                                    <div class="vyo-errors" role="alert">{{ session('owners_error') }}</div>
                                @endif
                                <ul class="vyo-factlist vyo-factlist--spaced">
                                    <li>@if (count($removeUnitNames)){{ implode(', ', $removeUnitNames) }} become @else Their units become @endif unassigned — <strong>bookings stay intact</strong></li>
                                    {{-- ⚠️ Removal cascades to the owner's team (2026-08-31) — so the claim is true, and spelled out. --}}
                                    <li>Portal access ends immediately — for {{ $owner['name'] }} <strong>and everyone on their team</strong>@if ($removeTeamCount > 0) ({{ $removeTeamCount }} {{ $removeTeamCount === 1 ? 'person' : 'people' }})@endif</li>
                                    {{-- ⚠️ OwnerLoginLifecycle spares a person who still holds a back-office role — the owner too. --}}
                                    <li>Anyone who also works for you — <strong>including {{ $owner['name'] }}</strong> — keeps their own login; only their access to this owner ends</li>
                                    <li>All statements &amp; payout records <strong>stay archived for accounting</strong></li>
                                    <li>It can’t be undone — adding them again later starts a fresh owner record</li>
                                </ul>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vyod-remove-name">Type {{ $owner['name'] }} to confirm</label>
                                    <input id="vyod-remove-name" class="vy-input" type="text" name="confirm_name" value="{{ $vyForm === 'remove' ? old('confirm_name') : '' }}"
                                           autocomplete="off" required @if ($vyOpen === 'remove' && ! $errors->has('current_password')) autofocus @endif>
                                    @if ($vyForm === 'remove')
                                        @error('confirm_name')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                {{-- ⚠️ RE-AUTH (G2): the name is printed on this page, so a hijacked session can read it; the password is the
                                     factor such a session may not have. Its own row — the founder's report. --}}
                                <div class="vyo-confirm">
                                    {{-- the password-manager pattern: which account this password is for — unnamed, so never posted, and hidden from everyone --}}
                                    <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly hidden aria-hidden="true" tabindex="-1">
                                    <label class="vy-field-label" for="vyod-remove-pw">Confirm it’s you</label>
                                    <input id="vyod-remove-pw" class="vy-input" type="password" name="current_password" autocomplete="current-password" required placeholder="Your password"
                                           @if ($vyOpen === 'remove' && $errors->has('current_password')) autofocus @endif>
                                    @if ($vyForm === 'remove' && $errors->has('current_password'))
                                        <div class="vyo-fielderr">{{ $errors->first('current_password') }}</div>
                                    @else
                                        <p class="vyo-hint">Removing an owner ends access to their money and their units, so your password confirms it’s you.</p>
                                    @endif
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="removeOpen = false">Keep owner</button>
                                <button type="submit" class="vy-btn vy-btn--danger">Remove owner</button>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($invite !== null)
                    {{-- ══ SEND THE INVITE AGAIN — a fresh link is a way in, so it asks for your password, as sending did ══════════ --}}
                    <div class="vy-modal-backdrop" x-cloak x-show="resendOpen" x-transition.opacity x-on:click.self="resendOpen = false" x-on:keydown.escape.window="resendOpen = false">
                        <div class="vy-modal vy-modal-card vyo-dlg" x-show="resendOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-resend-t" tabindex="-1" x-trap.noscroll="resendOpen">
                            <form method="POST" action="{{ route('app.owners.invites.resend', $invite['id']) }}" data-guard-submit>
                                @csrf
                                <input type="hidden" name="_form" value="resend">
                                <input type="hidden" name="return" value="owner">
                                <div class="vy-modal-head">
                                    <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg></span>
                                    <div class="vyo-dlg-head">
                                        <h3 class="vy-modal-title" id="vyod-resend-t">Send the invite again?</h3>
                                        <p class="vyo-dlg-sub">A fresh link goes to {{ $invite['email'] }}. The old one stops working.</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" x-on:click="resendOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                                <div class="vy-modal-body">
                                    @if ($vyOpen === 'resend' && session('owners_error'))
                                        <div class="vyo-errors" role="alert">{{ session('owners_error') }}</div>
                                    @endif
                                    <div class="vyo-confirm">
                                        <input type="text" value="{{ auth()->user()?->email }}" autocomplete="username" readonly hidden aria-hidden="true" tabindex="-1">
                                        <label class="vy-field-label" for="vyod-resend-pw">Confirm it’s you</label>
                                        <input id="vyod-resend-pw" class="vy-input" type="password" name="current_password" autocomplete="current-password" required placeholder="Your password" autofocus>
                                        @if ($vyForm === 'resend')
                                            @error('current_password')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                </div>
                                <div class="vy-modal-foot">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="resendOpen = false">Cancel</button>
                                    <button type="submit" class="vy-btn vy-btn--accent">Send again</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    @if ($invite['cancellable'])
                        {{-- ══ CANCEL THE INVITE — takes access away, so no password; an owner never activated leaves the list ═════ --}}
                        <div class="vy-modal-backdrop" x-cloak x-show="cancelOpen" x-transition.opacity x-on:click.self="cancelOpen = false" x-on:keydown.escape.window="cancelOpen = false">
                            <div class="vy-modal vy-modal-card vyo-dlg" x-show="cancelOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-cancel-t" tabindex="-1" x-trap.noscroll="cancelOpen">
                                <form method="POST" action="{{ route('app.owners.invites.cancel', $invite['id']) }}" data-guard-submit>
                                    @csrf
                                    <input type="hidden" name="return" value="owner">
                                    <div class="vy-modal-head">
                                        <span class="vy-modal-ico is-danger" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg></span>
                                        <div class="vyo-dlg-head">
                                            <h3 class="vy-modal-title" id="vyod-cancel-t">Cancel the invite?</h3>
                                            <p class="vyo-dlg-sub">The link sent to {{ $invite['email'] }} stops working.</p>
                                        </div>
                                        <button type="button" class="vy-modal-x" x-on:click="cancelOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                    </div>
                                    <div class="vy-modal-body">
                                        <ul class="vyo-factlist">
                                            @if ($inviteCancelRemoves)
                                                <li>{{ $owner['name'] }} never accepted, so they leave your owners list too.</li>
                                            @endif
                                            <li>You can invite them again later.</li>
                                        </ul>
                                    </div>
                                    <div class="vy-modal-foot">
                                        <button type="button" class="vy-btn vy-btn--ghost" x-on:click="cancelOpen = false">Keep invite</button>
                                        <button type="submit" class="vy-btn vy-btn--danger">Cancel invite</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                @endif
            @endif

            @if ($canPayout)
                {{-- ══ CLOSE A PERIOD (audit H-2) ══════════════════════════════════════════════════════════════════════════════
                     ⚠️ EVERY CLAIM BELOW WAS VERIFIED AGAINST THE CODE: finalize() seals the statement; PeriodLockGuard makes a later
                     line dated inside a sealed period a prior-period adjustment on the NEXT statement; the collection gate leaves
                     uncollected revenue for a later one; closing pays no one. No timing is promised and no email is claimed. --}}
                <div class="vy-modal-backdrop" x-cloak x-show="closeOpen" x-transition.opacity x-on:click.self="closeOpen = false" x-on:keydown.escape.window="closeOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide vyo-dlg" x-show="closeOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-close-t" tabindex="-1" x-trap.noscroll="closeOpen">
                        <form method="POST" action="{{ route('app.owners.close-period', $ownerId) }}" data-guard-submit>
                            @csrf
                            <input type="hidden" name="_form" value="close">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4M9 15l2 2 4-4"/></svg></span>
                                <div class="vyo-dlg-head">
                                    <h3 class="vy-modal-title" id="vyod-close-t">Close a period for {{ $owner['name'] }}?</h3>
                                    <p class="vyo-dlg-sub">This finalises a statement. It can’t be undone.</p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="closeOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($vyOpen === 'close' && session('owners_error'))
                                    <div class="vyo-errors" role="alert">{{ session('owners_error') }}</div>
                                @endif
                                @if ($closePeriod['lockedThroughWords'])
                                    <p class="vyo-closed-upto">Closed up to {{ $closePeriod['lockedThroughWords'] }} — a new period starts the day after.</p>
                                @endif
                                <div class="vyo-pair">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyod-start">First day</label>
                                        <input id="vyod-start" class="vy-input" type="date" name="period_start" x-model="start" value="{{ old('period_start', $closePeriod['start']) }}"
                                               @if ($closePeriod['minStart']) min="{{ $closePeriod['minStart'] }}" @endif required>
                                        @if ($vyForm === 'close')
                                            @error('period_start')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyod-end">Last day</label>
                                        <input id="vyod-end" class="vy-input" type="date" name="period_end" x-model="end" value="{{ old('period_end', $closePeriod['end']) }}"
                                               @if ($closePeriod['minStart']) min="{{ $closePeriod['minStart'] }}" @endif required>
                                        @if ($vyForm === 'close')
                                            @error('period_end')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                </div>
                                <p class="vyo-span" x-text="span()" aria-live="polite"></p>
                                <ul class="vyo-factlist">
                                    <li>The statement is <strong>sealed</strong> — its figures can no longer change</li>
                                    <li>Anything that arrives later dated inside this period becomes a <strong>prior-period adjustment</strong> on the next statement</li>
                                    <li>Revenue not yet collected from guests <strong>stays unclaimed</strong> and appears on a later statement</li>
                                    <li>{{ $owner['name'] }} can see the statement in their owner portal</li>
                                    <li><strong>Nothing is paid by this.</strong> A payout is created separately on the payout run page</li>
                                </ul>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="closeOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Close period</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if (count($roomTypes) > 0)
                @can('rates.manage')
                    {{-- ══ PROPOSE A RATE CHANGE — the owner approves, counters or declines it; nothing changes until they do ═══════ --}}
                    <div class="vy-modal-backdrop" x-cloak x-show="proposeOpen" x-transition.opacity x-on:click.self="proposeOpen = false" x-on:keydown.escape.window="proposeOpen = false">
                        <div class="vy-modal vy-modal-card vy-modal--wide vyo-dlg" x-show="proposeOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyod-propose-t" tabindex="-1" x-trap.noscroll="proposeOpen">
                            <form method="POST" action="{{ route('app.rate-proposals.store') }}" data-guard-submit>
                                @csrf
                                <input type="hidden" name="_form" value="propose">
                                <input type="hidden" name="owner_id" value="{{ $ownerId }}">
                                <div class="vy-modal-head">
                                    <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg></span>
                                    <div class="vyo-dlg-head">
                                        <h3 class="vy-modal-title" id="vyod-propose-t">Propose a rate change</h3>
                                        <p class="vyo-dlg-sub">{{ $owner['name'] }} approves, counters or declines it in their portal. Nothing changes until they do.</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" x-on:click="proposeOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                                <div class="vy-modal-body">
                                    @if ($vyOpen === 'propose' && session('rate_error'))
                                        <div class="vyo-errors" role="alert">{{ session('rate_error') }}</div>
                                    @endif
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyod-rt">Room type</label>
                                        <select id="vyod-rt" name="room_type_id" class="vy-input" required>
                                            @foreach ($roomTypes as $rt)
                                                <option value="{{ $rt['id'] }}" @selected($vyForm === 'propose' && old('room_type_id') === $rt['id'])>{{ $rt['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="vyo-trio">
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyod-prate">Nightly rate ({{ $rateCurrencySymbol }})</label>
                                            <input id="vyod-prate" class="vy-input" type="text" inputmode="decimal" name="rate" maxlength="20" required placeholder="185" autofocus
                                                   value="{{ $vyForm === 'propose' ? old('rate') : '' }}">
                                            @if ($vyForm === 'propose')
                                                @error('rate')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                            @endif
                                        </div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyod-uplift">Weekend uplift <span class="vy-fl-note">% · optional</span></label>
                                            <input id="vyod-uplift" class="vy-input" type="text" inputmode="decimal" name="weekend_uplift" maxlength="6" placeholder="e.g. 10"
                                                   value="{{ $vyForm === 'propose' ? old('weekend_uplift') : '' }}">
                                            @if ($vyForm === 'propose')
                                                @error('weekend_uplift')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                            @endif
                                        </div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyod-minstay">Minimum stay <span class="vy-fl-note">nights · optional</span></label>
                                            <input id="vyod-minstay" class="vy-input" type="text" inputmode="numeric" name="min_stay" maxlength="3" placeholder="e.g. 2"
                                                   value="{{ $vyForm === 'propose' ? old('min_stay') : '' }}">
                                            @if ($vyForm === 'propose')
                                                @error('min_stay')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                            @endif
                                        </div>
                                    </div>
                                    <div class="vyo-pair">
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyod-pfrom">First night</label>
                                            <input id="vyod-pfrom" class="vy-input" type="date" name="from_date" required
                                                   value="{{ $vyForm === 'propose' ? old('from_date') : now()->toDateString() }}">
                                            @if ($vyForm === 'propose')
                                                @error('from_date')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                            @endif
                                        </div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyod-pto">Last night</label>
                                            <input id="vyod-pto" class="vy-input" type="date" name="to_date" required
                                                   value="{{ $vyForm === 'propose' ? old('to_date') : now()->addYear()->subDay()->toDateString() }}">
                                            @if ($vyForm === 'propose')
                                                @error('to_date')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                            @endif
                                        </div>
                                    </div>
                                    <div class="vy-field vyo-field-last">
                                        <label class="vy-field-label" for="vyod-pnote">Note to {{ $owner['name'] }} <span class="vy-fl-note">optional</span></label>
                                        <input id="vyod-pnote" class="vy-input" type="text" name="note" maxlength="500" value="{{ $vyForm === 'propose' ? old('note') : '' }}">
                                        @if ($vyForm === 'propose')
                                            @error('note')<div class="vyo-fielderr">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                </div>
                                <div class="vy-modal-foot">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="proposeOpen = false">Cancel</button>
                                    <button type="submit" class="vy-btn vy-btn--accent">Send proposal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endcan
            @endif
        </div>
    </x-operator.shell>

    {{-- The page's dialogs open and close here, so they need this script; the note and the rate-proposal row forms post without
         it. The period line reads the two dates in words. --}}
    <script>
        document.addEventListener('alpine:init', () => {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const day = (iso) => {
                const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || '');
                return m ? { y: +m[1], m: +m[2] - 1, d: +m[3], t: Date.UTC(+m[1], +m[2] - 1, +m[3]) } : null;
            };
            Alpine.data('vyOwnerPage', (state) => Object.assign({
                span() {
                    const a = day(this.start), b = day(this.end);
                    if (! a || ! b) return '';
                    if (b.t < a.t) return 'The period has to end on or after the day it starts.';
                    const days = Math.round((b.t - a.t) / 86400000) + 1;
                    const from = a.d + ' ' + months[a.m] + (a.y !== b.y ? ' ' + a.y : '');
                    return from + ' – ' + b.d + ' ' + months[b.m] + ' ' + b.y + ' · ' + days + (days === 1 ? ' day' : ' days');
                },
            }, state));
        });
    </script>
</div>
