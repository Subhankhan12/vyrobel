{{-- Operator Payout Run (Wave 1 · Sub-gate 4) — the operator's TENANT-scoped disbursement desk, styled on
     the shared operator design system via <x-operator.shell>. The page READS; the two money writes POST to
     the real PayoutRunActionController → the certified OwnerPayoutService → PRG. See
     App\Livewire\Operator\PayoutRun for the authoritative REAL / DERIVED / DEFERRED / OMITTED map.

     Honesty map (short form):
       • REAL     — owner names; statement no./period/net payable; payout amount, rail (the SHARED RailLabel
                    map), the real status enum, paid/failed dates, failure reason, external ref; the bound
                    rail read from the container.
       • DERIVED  — the ready set (mirrors createFromStatement() exactly); the stat strip (Σ ready, Σ
                    pending, Σ paid + counts) PER CURRENCY, never summed across (invariant #17); "—" for an
                    empty bucket, never a zero in a guessed currency.
       • DEFERRED — Stripe Connect: send() throws by design. Stated as not connected; if it were ever the
                    bound rail the Pay controls disable instead of offering a button that can only fail.
       • OMITTED  — the design's batch-export control, its bank-detail columns and its whole "awaiting bank
                    details" hold rail (no such column exists in the schema), carry-forward as a button (it
                    happens inside the service — it is not an operator action), its select-statement
                    checkboxes, and every one of its demo constants. The component docblock names each.
       • NOT RENDERED — the four lifecycle words the design invented. Neither real enum holds them, so this
                    file must never contain them AT ALL: the OperatorPayoutRunTest asserts their literal
                    absence from the response, and a Blade comment is the one place a banned word could
                    hide from that test today (comments are stripped at compile time) and leak tomorrow if
                    it were ever converted to an HTML comment, which is NOT stripped. So the full triage
                    lives in App\Livewire\Operator\PayoutRun's docblock, which never renders at all.

     RAW-BLADE RULE: never write `word@directive` — Blade's \B guard would ship the raw source to the page
     as ordinary text, invisible to assertSee. Every `@` below follows a space, a newline or a tag. --}}
<div>
    <x-operator.shell active="payout-run" title="Payout run" :subtitle="$subtitle" :viewer="$viewer">

        @if (session('payout_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                {{ session('payout_notice') }}
            </div>
        @endif
        @if (session('payout_error'))
            <div class="vy-flash is-err vy-fade" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 16h.01M10.3 3.9L2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                {{ session('payout_error') }}
            </div>
        @endif

        {{-- TEST-MODE banner — the certified engine boundary (#71): in test mode NO real money moves. --}}
        @if ($mode !== 'live')
            <x-owner.payout-test-mode />
        @endif

        {{-- Breadcrumb — the design's `‹ Owners / Payout run`. Reuses the SHAPE already established on the
             work-order detail rather than writing a second breadcrumb rule (anti-drift). The design's
             period tail ("· Sep 2025") is NOT reproduced: a payout run here is not scoped to a month, and
             stamping one on would state a period this page does not actually filter by (#39). --}}
        <div class="vy-fade" style="display:flex;align-items:center;gap:6px;font-size:12.5px;margin-bottom:10px;">
            <a href="{{ route('app.owners') }}" style="display:inline-flex;align-items:center;gap:4px;color:var(--vy-muted);text-decoration:none;font-weight:500;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                Owners
            </a>
            <span style="color:var(--vy-faint);">/</span>
            <span style="color:var(--vy-faint);">Payout run</span>
        </div>

        {{-- Run header + the REAL persisted run + the run action (certified PayoutRunService, test-mode) --}}
        <div class="vy-rack-toolbar vy-fade" style="margin-bottom: 12px;">
            <div class="vy-left">
                @if ($latestRun)
                    <span class="vy-range">Last run · {{ ucfirst($latestRun['status']) }} <span class="vy-muted">· {{ $latestRun['created'] }} created · {{ $latestRun['paid'] }} paid · {{ $latestRun['held'] }} held · {{ $latestRun['when'] }}</span></span>
                @else
                    <span class="vy-range">No payout run yet this period</span>
                @endif
            </div>
            <div class="vy-right" style="gap: 8px;">
                {{-- Export SEPA — HONEST-DISABLED: no SEPA generator exists; the real disbursement is the test-mode
                     rail. Never a fabricated bank file (#39). --}}
                <span class="vy-pill is-soon" aria-disabled="true" title="No SEPA export yet — the disbursement rail is test-mode">Export SEPA · Soon</span>
                @if ($canPayout)
                    <form method="POST" action="{{ route('app.payouts.run-now') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" class="vy-btn vy-btn--accent">Run payout ({{ $mode }} mode)</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- ── Stat strip — DERIVED per currency. An empty bucket is "—": a zero must be a zero of SOME
             currency, and this page spans every owner in the tenant, so there is none to pick. ──────── --}}
        {{-- ⚠️ `vy-dash-kpis` is a FOUR-column grid; this strip has THREE tiles, so it was laying them out
             across 3 of 4 columns and leaving 292px of dead space at 1440 (measured, not eyeballed — the
             class name reads fine in the source). The design's three tiles span the full content width, so
             this takes the existing `--3` modifier. --}}
        <div class="vy-dash-kpis vy-dash-kpis--3 vy-fade">
            {{-- The design's three stat tiles are all LIGHT glass; its one dark surface is the run-summary
                 card in the right rail, which is where the dark treatment now lives. --}}
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Ready to pay</div>
                @forelse ($kpis['ready']['lines'] as $line)
                    <div class="vy-dk-val" @if (! $loop->first) style="font-size: 22px;" @endif>{{ $line }}</div>
                @empty
                    <div class="vy-dk-val">—</div>
                @endforelse
                <div class="vy-dk-sub">
                    {{ $kpis['ready']['count'] }} finalized {{ \Illuminate\Support\Str::plural('statement', $kpis['ready']['count']) }} awaiting a payout
                    {{ count($kpis['ready']['lines']) > 1 ? '· shown per currency (never summed across)' : '' }}
                </div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Pending transfers</div>
                @forelse ($kpis['pending']['lines'] as $line)
                    <div class="vy-dk-val" @if (! $loop->first) style="font-size: 20px;" @endif>{{ $line }}</div>
                @empty
                    <div class="vy-dk-val">—</div>
                @endforelse
                <div class="vy-dk-sub">{{ $kpis['pending']['count'] }} {{ \Illuminate\Support\Str::plural('payout', $kpis['pending']['count']) }} created, not yet paid</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Paid</div>
                @forelse ($kpis['paid']['lines'] as $line)
                    <div class="vy-dk-val" @if (! $loop->first) style="font-size: 20px;" @endif>{{ $line }}</div>
                @empty
                    <div class="vy-dk-val">—</div>
                @endforelse
                <div class="vy-dk-sub">{{ $kpis['paid']['count'] }} {{ \Illuminate\Support\Str::plural('payout', $kpis['paid']['count']) }} genuinely marked paid</div>
            </div>
        </div>

        <div class="vy-detail-cols" style="margin-top: 22px;">
            {{-- ═══════════════ LEFT: ready to pay · pending transfers · recent transfers ═════════ --}}
            <div class="vy-stack">

                {{-- Ready to pay — DERIVED with the SERVICE'S OWN predicate: finalized + locked (a draft
                     throws), a positive net (zero/negative return null), and no pending/paid payout yet
                     (the service is idempotent). Every row here can genuinely be created. --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head">
                        <h2 class="vy-card-title">Ready to pay</h2>
                        @if (count($ready) > 0 && auth()->user()?->can('payouts.manage'))
                            <form method="POST" action="{{ route('app.payouts.create') }}">
                                @csrf
                                <button type="submit" class="vy-btn vy-btn--accent">
                                    Create {{ count($ready) }} {{ \Illuminate\Support\Str::plural('payout', count($ready)) }}
                                </button>
                            </form>
                        @else
                            <span class="vy-eyebrow">0</span>
                        @endif
                    </div>

                    @if (count($ready) > 0)
                        <div class="vy-utable-wrap">
                            <table class="vy-utable">
                                <thead>
                                    <tr>
                                        <th>Owner</th>
                                        <th>Statement</th>
                                        <th>Period</th>
                                        <th>Net payable</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ready as $row)
                                        <tr>
                                            <td>
                                                @if ($row['owner_id'])
                                                    <a class="vy-assign-link" href="{{ route('app.owners.show', $row['owner_id']) }}">{{ $row['owner_name'] }}</a>
                                                @else
                                                    {{ $row['owner_name'] }}
                                                @endif
                                            </td>
                                            <td class="vy-u-code">{{ $row['sequence_no'] !== null ? '#' . $row['sequence_no'] : '—' }}</td>
                                            <td>
                                                <div>{{ $row['period'] }}</div>
                                                @if ($row['finalized'])
                                                    <div class="vy-u-sub">Finalized {{ $row['finalized'] }}</div>
                                                @endif
                                            </td>
                                            <td class="vy-u-code">{{ $row['net'] }}</td>
                                            <td>
                                                {{-- payouts.manage (2026-07-17): money OUT. Held by Property
                                                     Owner / Group Admin / Accountant, not Hotel Manager. --}}
                                                @can('payouts.manage')
                                                <form method="POST" action="{{ route('app.payouts.create') }}">
                                                    @csrf
                                                    <input type="hidden" name="statement_id" value="{{ $row['statement_id'] }}">
                                                    <button type="submit" class="vy-act vy-act--accent">Create payout</button>
                                                </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="vy-empty">No finalized statement is waiting for a payout.</div>
                    @endif
                </div>

                {{-- Pending transfers — REAL payout rows awaiting the rail. --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head">
                        <h2 class="vy-card-title">Pending transfers</h2>
                        @if (count($pending) > 0 && $rail['can_send'] && auth()->user()?->can('payouts.manage'))
                            <form method="POST" action="{{ route('app.payouts.pay') }}">
                                @csrf
                                <button type="submit" class="vy-btn vy-btn--accent">
                                    Mark {{ count($pending) }} paid
                                </button>
                            </form>
                        @else
                            <span class="vy-eyebrow">{{ count($pending) }}</span>
                        @endif
                    </div>

                    @if (count($pending) > 0)
                        <div class="vy-utable-wrap">
                            <table class="vy-utable">
                                <thead>
                                    <tr>
                                        <th>Owner</th>
                                        <th>Statement</th>
                                        <th>Method</th>
                                        <th>Amount</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pending as $row)
                                        <tr>
                                            {{-- The design's row anatomy: an initial disc beside the owner name.
                                                 Initials are derived from the owner's REAL name only — a row with
                                                 no resolvable owner renders "—" and gets no disc rather than an
                                                 invented placeholder. --}}
                                            <td>
                                                <div class="vy-po-who">
                                                    @if ($row['owner_name'] !== '—')
                                                        <span class="vy-mini-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::of($row['owner_name'])->explode(' ')->filter()->take(2)->map(fn ($w) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($w, 0, 1)))->implode('') }}</span>
                                                    @endif
                                                    <div>
                                                        @if ($row['owner_id'])
                                                            <a class="vy-assign-link" href="{{ route('app.owners.show', $row['owner_id']) }}">{{ $row['owner_name'] }}</a>
                                                        @else
                                                            {{ $row['owner_name'] }}
                                                        @endif
                                                        <div class="vy-u-sub">
                                                            <span class="vy-tag tone-{{ $row['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['status_meta']['label'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="vy-u-code">{{ $row['sequence_no'] !== null ? '#' . $row['sequence_no'] : '—' }}</td>
                                            <td>{{ $row['method'] }}@if ($row['owner_id'] && ($ibanByOwner[$row['owner_id']] ?? null))<div class="vy-u-sub" style="font-variant-numeric: tabular-nums;">{{ $ibanByOwner[$row['owner_id']] }}</div>@endif</td>
                                            <td class="vy-u-code">{{ $row['amount'] }}</td>
                                            <td>
                                                @if ($rail['can_send'] && auth()->user()?->can('payouts.manage'))
                                                    <form method="POST" action="{{ route('app.payouts.pay') }}">
                                                        @csrf
                                                        <input type="hidden" name="payout_id" value="{{ $row['payout_id'] }}">
                                                        <button type="submit" class="vy-act vy-act--accent">Mark paid</button>
                                                    </form>
                                                @else
                                                    {{-- The bound rail cannot disburse — offering the button would be offering a
                                                         guaranteed failure. The rail card explains why. --}}
                                                    <span class="vy-act is-soon" aria-disabled="true">Rail not connected</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="vy-empty">No transfer is waiting to be paid.</div>
                    @endif
                </div>

                {{-- Recent transfers — every SETTLED payout: paid, failed and reversed are all real enum
                     values, so a failure is never softened or hidden and always carries its real reason. --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head">
                        <h2 class="vy-card-title">Recent transfers</h2>
                        <span class="vy-eyebrow">{{ count($recent) }}</span>
                    </div>
                    <div class="vy-ledger">
                        @forelse ($recent as $row)
                            <div class="vy-ledger-row">
                                <div class="vy-ledger-main">
                                    <div class="vy-ledger-label">
                                        @if ($row['owner_id'])
                                            <a class="vy-assign-link" href="{{ route('app.owners.show', $row['owner_id']) }}">{{ $row['owner_name'] }}</a>
                                        @else
                                            {{ $row['owner_name'] }}
                                        @endif
                                        <span class="vy-tag tone-{{ $row['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['status_meta']['label'] }}</span>
                                    </div>
                                    <div class="vy-ledger-sub">
                                        {{ $row['method'] }}
                                        @if ($row['sequence_no'] !== null)
                                            <span class="vy-sep">·</span> Statement #{{ $row['sequence_no'] }}
                                        @endif
                                        @if ($row['paid_at'])
                                            <span class="vy-sep">·</span> Paid {{ $row['paid_at'] }}
                                        @elseif ($row['failed_at'])
                                            <span class="vy-sep">·</span> Failed {{ $row['failed_at'] }}
                                        @endif
                                        @if ($row['external_ref'])
                                            <span class="vy-sep">·</span> Ref {{ $row['external_ref'] }}
                                        @endif
                                    </div>
                                    @if ($row['failure_reason'])
                                        <div class="vy-ledger-sub">{{ $row['failure_reason'] }}</div>
                                    @endif
                                </div>
                                <div class="vy-ledger-amt tone-{{ $row['status_meta']['tone'] === 'confirmed' ? 'paid' : 'muted' }}">{{ $row['amount'] }}</div>
                            </div>
                        @empty
                            <div class="vy-empty">No payout has settled yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- ═══════════════ RIGHT RAIL: run summary · the run's rail · not payable ═══════════ --}}
            <div class="vy-stack">

                {{-- Run summary — the design's DARK card at the top of the rail. Structure matched; content
                     is the REAL run state.

                     ⚠️ THE DESIGN'S HEADLINE FIGURE IS DELIBERATELY NOT REPRODUCED. Its "€96,400" is a
                     cross-bucket total (ready + held). Every money figure on this page is formatted PER
                     CURRENCY by the component and handed here as a STRING — so a combined total cannot be
                     assembled in Blade without re-deriving money in the view, which is exactly the
                     second-definition drift this project keeps consolidating (#17). Building it properly
                     means a per-currency run total in the component: logged as a backend gap, not faked.
                     What IS shown here is real: the persisted run's own status and counts, or an honest
                     statement that no run has happened yet. --}}
                <div class="vy-card vy-fade vy-runsum">
                    <div class="vy-runsum-label">Run summary</div>

                    @if ($latestRun)
                        <div class="vy-runsum-head">{{ ucfirst($latestRun['status']) }}</div>
                        <div class="vy-runsum-sub">
                            {{ $latestRun['created'] }} created · {{ $latestRun['paid'] }} paid · {{ $latestRun['held'] }} held
                            @if ($latestRun['when'])
                                <span class="vy-sep">·</span> {{ $latestRun['when'] }}
                            @endif
                        </div>
                        @if ($latestRun['mode'] !== 'live')
                            <div class="vy-runsum-note">Ran in {{ $latestRun['mode'] }} mode — no real funds moved.</div>
                        @endif
                    @else
                        <div class="vy-runsum-head">No run yet</div>
                        <div class="vy-runsum-sub">Nothing has been run this period.</div>
                    @endif

                    {{-- The real composition of what a run would touch. Counts are real integers, so they can
                         be shown together; the AMOUNTS stay in the per-currency stat strip above. --}}
                    <div class="vy-runsum-grid">
                        <div>
                            <div class="vy-runsum-k">Ready</div>
                            <div class="vy-runsum-v">{{ $kpis['ready']['count'] }}</div>
                        </div>
                        <div>
                            <div class="vy-runsum-k">Pending</div>
                            <div class="vy-runsum-v">{{ $kpis['pending']['count'] }}</div>
                        </div>
                        <div>
                            <div class="vy-runsum-k">Held</div>
                            <div class="vy-runsum-v">{{ count($heldRows) }}</div>
                        </div>
                    </div>
                </div>

                {{-- How this run pays — the rail is read from the CONTAINER, so this is what the write will
                     really use, not a config guess. --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head">
                        <h2 class="vy-card-title">How this run pays</h2>
                    </div>
                    <div class="vy-facts">
                        <div>
                            <div class="vy-fact-label">Rail</div>
                            <div class="vy-fact-val">{{ $rail['label'] }}</div>
                        </div>
                    </div>

                    @if ($rail['name'] === 'manual')
                        <div class="vy-deferred-note" style="margin-top: 14px;">
                            Vyrobel does not move this money. Marking a transfer paid RECORDS a bank transfer you
                            have already made from your own bank — it does not send one.
                        </div>
                    @else
                        <div class="vy-deferred-note" style="margin-top: 14px;">
                            This rail cannot disburse, so the Mark-paid controls are disabled rather than offered
                            as a guaranteed failure.
                        </div>
                    @endif

                    {{-- DEFERRED — true whichever rail is bound: Connect has never been connected. --}}
                    <div style="margin-top: 14px; display: flex; align-items: center; gap: 8px;">
                        <span class="vy-btn is-soon" aria-disabled="true">Pay via Stripe Connect</span>
                        <span class="vy-soon-tag">Soon</span>
                    </div>
                    <div class="vy-deferred-note" style="margin-top: 10px;">
                        Stripe Connect disbursement is not connected — connected-account onboarding and KYC are an
                        unverified live checkpoint, so no money can leave Vyrobel through it.
                    </div>
                </div>

                {{-- On hold — the REAL holds (#93): the certified TAX-hold (`held` payouts) + owners with a
                     positive-net ready statement but NO payout method on file (can't be sent). The masked IBAN
                     shows where one is on file; "Request IBAN" is a real prompt. NO fabricated "verified IBAN". --}}
                @if (count($heldRows) > 0 || count($noMethodRows) > 0)
                    <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                        <div class="vy-detail-head">
                            <h2 class="vy-card-title">On hold</h2>
                            <span class="vy-eyebrow">{{ count($heldRows) + count($noMethodRows) }}</span>
                        </div>
                        @foreach ($heldRows as $row)
                            <div class="vy-unit-row">
                                <div>
                                    <div class="vy-um-name">@if ($row['owner_id'])<a class="vy-assign-link" href="{{ route('app.owners.show', $row['owner_id']) }}">{{ $row['owner_name'] }}</a>@else {{ $row['owner_name'] }} @endif</div>
                                    <div class="vy-um-sub">{{ $row['reason'] }}@if ($row['iban']) <span class="vy-sep">·</span> IBAN {{ $row['iban'] }} @endif</div>
                                </div>
                                <div class="vy-u-code">{{ $row['amount'] }}</div>
                            </div>
                        @endforeach
                        @foreach ($noMethodRows as $row)
                            <div class="vy-unit-row">
                                <div>
                                    <div class="vy-um-name">@if ($row['owner_id'])<a class="vy-assign-link" href="{{ route('app.owners.show', $row['owner_id']) }}">{{ $row['owner_name'] }}</a>@else {{ $row['owner_name'] }} @endif</div>
                                    <div class="vy-um-sub">No payout method on file — the owner adds their IBAN in the portal, verified before the first payout.</div>
                                </div>
                                <div style="text-align: right;"><div class="vy-u-code">{{ $row['amount'] }}</div><span class="vy-act is-soon" aria-disabled="true">Request IBAN</span></div>
                            </div>
                        @endforeach
                        <div class="vy-empty" style="padding: 8px 0 0; font-size: 11.5px;">Held amounts stay in the owner's ledger and carry forward until the block clears (#P2-1).</div>
                    </div>
                @endif

                {{-- Not payable — the HONEST replacement for the design's bank-details hold rail (no such
                     column exists anywhere, so nothing is ever held awaiting one). What is real: a zero net
                     owes nothing, and a negative net is never charged back — the service carries it
                     forward. That is the policy, stated; it is not a button. --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head">
                        <h2 class="vy-card-title">Not payable</h2>
                        <span class="vy-eyebrow">{{ count($notPayable) }}</span>
                    </div>
                    @forelse ($notPayable as $row)
                        <div class="vy-unit-row">
                            <div>
                                <div class="vy-um-name">
                                    @if ($row['owner_id'])
                                        <a class="vy-assign-link" href="{{ route('app.owners.show', $row['owner_id']) }}">{{ $row['owner_name'] }}</a>
                                    @else
                                        {{ $row['owner_name'] }}
                                    @endif
                                </div>
                                <div class="vy-um-sub">
                                    {{ $row['sequence_no'] !== null ? '#' . $row['sequence_no'] : '—' }}
                                    <span class="vy-sep">·</span> {{ $row['period'] }}
                                </div>
                                <div class="vy-um-sub">
                                    @if ($row['negative'])
                                        Nothing payable — a negative net carries forward to offset this owner's next statement.
                                    @else
                                        Nothing payable — the net for this period is zero.
                                    @endif
                                </div>
                            </div>
                            <div class="vy-u-code">{{ $row['net'] }}</div>
                        </div>
                    @empty
                        <div class="vy-empty">Every finalized statement has a positive net payable.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-operator.shell>
</div>
