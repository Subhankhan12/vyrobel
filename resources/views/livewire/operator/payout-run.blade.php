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
        {{-- 2026-10-03: every write below records money owed or paid, so each asks first — what it records, and that it moves
             no money (`data-confirm` on the form, the one dialog at the end). The forms still post without the script. --}}
        <div class="vyo-root vyo-pr" x-data="vyPayoutRun()">

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
        <nav class="vyo-pr-crumbs vy-fade" aria-label="Breadcrumb">
            <a class="vyo-pr-crumb" href="{{ route('app.owners') }}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                Owners
            </a>
            <span class="vyo-pr-sep" aria-hidden="true">/</span>
            <span class="vyo-pr-here" aria-current="page">Payout run</span>
        </nav>

        {{-- Run header + the REAL persisted run + the run action (certified PayoutRunService, test-mode) --}}
        <div class="vy-rack-toolbar vyo-pr-bar vy-fade">
            <div class="vy-left">
                {{-- ⚠️ THIS organisation's figures only (2026-10-02): a run row carries no organisation, so the latest run could be
                     another's — the component reads what this organisation's own payouts record instead. --}}
                @if ($latestRun)
                    <span class="vy-range">Last run here <span class="vy-muted">· {{ $latestRun['created'] }} created · {{ $latestRun['paid'] }} paid · {{ $latestRun['held'] }} held · {{ $latestRun['when'] }}</span></span>
                @else
                    <span class="vy-range">No payout run yet here</span>
                @endif
            </div>
            <div class="vy-right vyo-pr-actions">
                {{-- Export SEPA — HONEST-DISABLED: no SEPA generator exists; the real disbursement is the test-mode
                     rail. Never a fabricated bank file (#39). --}}
                <span class="vy-pill is-soon" aria-disabled="true" title="No SEPA export yet — the disbursement rail is test-mode">Export SEPA · Soon</span>
                @if ($canPayout && $engineOn)
                    <form method="POST" action="{{ route('app.payouts.run-now') }}" class="vyo-inline-form" x-on:submit="ask($event)" data-guard-submit
                          data-confirm="{{ json_encode(['title' => 'Run payouts now?', 'sub' => 'In '.$mode.' mode.', 'button' => 'Run payouts', 'facts' => array_values(array_filter([
                              'Creates a payout for every owner here with a statement ready to pay, and pays each one by '.mb_strtolower($rail['label']).'.',
                              $rail['name'] === 'manual' ? 'A bank transfer is recorded, not sent: each payout is marked paid — make the transfers from your own bank.' : null,
                              'An owner with no tax details on file is held until they add them. Owners whose payouts are paused are skipped.',
                              'Recorded as a payout run.',
                          ]))], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}">
                        @csrf
                        <button type="submit" class="vy-btn vy-btn--accent">Run payouts now</button>
                    </form>
                @elseif ($canPayout)
                    {{-- ⚠️ D-PAYRUN-BUTTON (2026-10-03): offered while the engine was off, the button could only be refused. --}}
                    <button type="button" class="vy-btn vy-btn--accent" disabled aria-describedby="vyo-pr-off">Run payouts now</button>
                @endif
            </div>
        </div>
        @if ($canPayout && ! $engineOn)
            <p class="vyo-pr-off vy-fade" id="vyo-pr-off">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                The automatic payout engine is switched off, so payouts are not run in one go — create them and mark them paid below.
            </p>
        @endif

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
                    <div class="vy-dk-val {{ $loop->first ? '' : 'vyo-kpi-extra' }}">{{ $line }}</div>
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
                    <div class="vy-dk-val {{ $loop->first ? '' : 'vyo-kpi-extra' }}">{{ $line }}</div>
                @empty
                    <div class="vy-dk-val">—</div>
                @endforelse
                <div class="vy-dk-sub">{{ $kpis['pending']['count'] }} {{ \Illuminate\Support\Str::plural('payout', $kpis['pending']['count']) }} created, not yet paid</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Paid</div>
                @forelse ($kpis['paid']['lines'] as $line)
                    <div class="vy-dk-val {{ $loop->first ? '' : 'vyo-kpi-extra' }}">{{ $line }}</div>
                @empty
                    <div class="vy-dk-val">—</div>
                @endforelse
                <div class="vy-dk-sub">{{ $kpis['paid']['count'] }} {{ \Illuminate\Support\Str::plural('payout', $kpis['paid']['count']) }} genuinely marked paid</div>
            </div>
        </div>

        <div class="vy-detail-cols vyo-pr-cols">
            {{-- ═══════════════ LEFT: ready to pay · pending transfers · recent transfers ═════════ --}}
            <div class="vy-stack">

                {{-- Ready to pay — DERIVED with the SERVICE'S OWN predicate: finalized + locked (a draft
                     throws), a positive net (zero/negative return null), and no pending/paid payout yet
                     (the service is idempotent). Every row here can genuinely be created. --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head">
                        <h2 class="vy-card-title">Ready to pay</h2>
                        @if (count($ready) > 0 && auth()->user()?->can('payouts.manage'))
                            <form method="POST" action="{{ route('app.payouts.create') }}" x-on:submit="ask($event)" data-guard-submit
                                  data-confirm="{{ json_encode(['title' => 'Create '.count($ready).' '.\Illuminate\Support\Str::plural('payout', count($ready)).'?',
                                      'sub' => 'One for each finalised statement ready to pay.',
                                      'button' => 'Create '.count($ready).' '.\Illuminate\Support\Str::plural('payout', count($ready)),
                                      'facts' => array_values(array_filter([
                                          'Each records what you owe that owner — it moves no money.',
                                          $engineOn ? 'Each waits under Pending transfers until it is paid — by you, or by the next automatic run.' : 'Each waits under Pending transfers until you mark it paid.',
                                          count($pausedReady) > 0 ? 'Owners whose payouts are paused are skipped.' : null,
                                          'Recorded in the audit log.',
                                      ]))], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}">
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
                            {{-- ⚠️ `--stick-action`: this table's last column carries a MONEY action ("Create payout" /
                                 "Mark paid") inside a 720px scroller, so at 375px it rendered outside the viewport —
                                 the same defect as the housekeeping list. Found by the class guard, not by looking. --}}
                            <table class="vy-utable vy-utable--stick-action">
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
                                                <form method="POST" action="{{ route('app.payouts.create') }}" x-on:submit="ask($event)" data-guard-submit
                                                      data-confirm="{{ json_encode(['title' => 'Create a payout for '.$row['owner_name'].'?',
                                                          'sub' => ($row['sequence_no'] !== null ? 'Statement #'.$row['sequence_no'].' · ' : '').$row['period'].' · '.$row['net'],
                                                          'button' => 'Create payout',
                                                          'facts' => ['It records what you owe them — it moves no money.', $engineOn ? 'It waits under Pending transfers until it is paid — by you, or by the next automatic run.' : 'It waits under Pending transfers until you mark it paid.', 'Recorded in the audit log.']], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}">
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

                {{-- Payouts paused (DEC-n, 2026-10-02) — a pause stops every payout, so these statements are not offered a
                     button that could only be refused; the way to pay them is to resume the owner. --}}
                @if (count($pausedReady) > 0)
                    <div class="vy-card vy-fade">
                        <div class="vy-detail-head">
                            <h2 class="vy-card-title">Payouts paused</h2>
                            <span class="vy-eyebrow">{{ count($pausedReady) }}</span>
                        </div>
                        <div class="vy-deferred-note vyo-pr-note">No payout is created or sent for these owners — not by the automatic run, not by hand — until their payouts are resumed on the owner’s page.</div>
                        <div class="vy-utable-wrap">
                            <table class="vy-utable vy-utable--stick-action">
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
                                    @foreach ($pausedReady as $row)
                                        <tr>
                                            <td>
                                                @if ($row['owner_id'])
                                                    <a class="vy-assign-link" href="{{ route('app.owners.show', $row['owner_id']) }}">{{ $row['owner_name'] }}</a>
                                                @else
                                                    {{ $row['owner_name'] }}
                                                @endif
                                            </td>
                                            <td class="vy-u-code">{{ $row['sequence_no'] !== null ? '#' . $row['sequence_no'] : '—' }}</td>
                                            <td>{{ $row['period'] }}</td>
                                            <td class="vy-u-code">{{ $row['net'] }}</td>
                                            <td>
                                                @if ($row['owner_id'])
                                                    <a class="vy-act" href="{{ route('app.owners.show', $row['owner_id']) }}">Open owner</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Pending transfers — REAL payout rows awaiting the rail. --}}
                <div class="vy-card vy-fade">
                    <div class="vy-detail-head">
                        <h2 class="vy-card-title">Pending transfers</h2>
                        @if ($pendingPayable > 0 && $rail['can_send'] && auth()->user()?->can('payouts.manage'))
                            <form method="POST" action="{{ route('app.payouts.pay') }}" x-on:submit="ask($event)" data-guard-submit
                                  data-confirm="{{ json_encode(['title' => 'Mark '.$pendingPayable.' '.\Illuminate\Support\Str::plural('payout', $pendingPayable).' paid?',
                                      'sub' => 'Do this once every one of these transfers has left your own bank.',
                                      'button' => 'Mark '.$pendingPayable.' paid',
                                      'facts' => ['Vyrobel records each payout and its statement as paid — it does not send money.', 'Owners whose payouts are paused are skipped.', 'Recorded in the audit log.']], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}">
                                @csrf
                                <button type="submit" class="vy-btn vy-btn--accent">
                                    Mark {{ $pendingPayable }} paid
                                </button>
                            </form>
                        @else
                            <span class="vy-eyebrow">{{ count($pending) }}</span>
                        @endif
                    </div>

                    @if (count($pending) > 0)
                        <div class="vy-utable-wrap">
                            {{-- ⚠️ `--stick-action`: this table's last column carries a MONEY action ("Create payout" /
                                 "Mark paid") inside a 720px scroller, so at 375px it rendered outside the viewport —
                                 the same defect as the housekeeping list. Found by the class guard, not by looking. --}}
                            <table class="vy-utable vy-utable--stick-action">
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
                                            <td>{{ $row['method'] }}@if ($row['owner_id'] && ($ibanByOwner[$row['owner_id']] ?? null))<div class="vy-u-sub vyo-num">{{ $ibanByOwner[$row['owner_id']] }}</div>@endif</td>
                                            <td class="vy-u-code">{{ $row['amount'] }}</td>
                                            <td>
                                                @if ($row['paused'])
                                                    {{-- DEC-n — the service refuses it; resuming the owner makes it payable again --}}
                                                    <span class="vy-act is-soon" aria-disabled="true" title="Payouts to this owner are paused — resume them on the owner’s page">Paused</span>
                                                @elseif ($rail['can_send'] && auth()->user()?->can('payouts.manage'))
                                                    <form method="POST" action="{{ route('app.payouts.pay') }}" x-on:submit="ask($event)" data-guard-submit
                                                          data-confirm="{{ json_encode(['title' => 'Mark '.$row['amount'].' to '.$row['owner_name'].' as paid?',
                                                              'sub' => 'Do this once the transfer has left your own bank.',
                                                              'button' => 'Mark paid',
                                                              'facts' => ['Vyrobel records the payout and its statement as paid — it does not send money.', 'Recorded in the audit log.']], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}">
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
                        <div class="vy-runsum-head">Last run here</div>
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
                        <div class="vy-deferred-note vyo-pr-note--top">
                            Vyrobel does not move this money. Marking a transfer paid RECORDS a bank transfer you
                            have already made from your own bank — it does not send one.
                        </div>
                    @else
                        <div class="vy-deferred-note vyo-pr-note--top">
                            This rail cannot disburse, so the Mark-paid controls are disabled rather than offered
                            as a guaranteed failure.
                        </div>
                    @endif

                    {{-- DEFERRED — true whichever rail is bound: Connect has never been connected. --}}
                    <div class="vyo-pr-soon">
                        <span class="vy-btn is-soon" aria-disabled="true">Pay via Stripe Connect</span>
                        <span class="vy-soon-tag">Soon</span>
                    </div>
                    <div class="vy-deferred-note vyo-pr-note--tight">
                        Stripe Connect disbursement is not connected — connected-account onboarding and KYC are an
                        unverified live checkpoint, so no money can leave Vyrobel through it.
                    </div>
                </div>

                {{-- On hold — the REAL holds (#93): the certified TAX-hold (`held` payouts) + owners with a
                     positive-net ready statement but NO payout method on file (can't be sent). The masked IBAN
                     shows where one is on file; "Request IBAN" is a real prompt. NO fabricated "verified IBAN". --}}
                @if (count($heldRows) > 0 || count($noMethodRows) > 0)
                    <div class="vy-card vy-fade">
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
                                <div class="vyo-pr-end"><div class="vy-u-code">{{ $row['amount'] }}</div><span class="vy-act is-soon" aria-disabled="true">Request IBAN</span></div>
                            </div>
                        @endforeach
                        <div class="vyo-pr-holdnote">Held amounts stay in the owner's ledger and carry forward until the block clears (#P2-1).</div>
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

            {{-- ══ THE CONFIRM — one dialog for every write on the page; the form it came from says what to show ══════════════ --}}
            <div class="vy-modal-backdrop" x-cloak x-show="confirmOpen" x-transition.opacity x-on:click.self="confirmOpen = false" x-on:keydown.escape.window="confirmOpen = false">
                <div class="vy-modal vy-modal-card vyo-dlg" x-show="confirmOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vyo-pr-confirm-t" tabindex="-1" x-trap.noscroll="confirmOpen">
                    <div class="vy-modal-head">
                        <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9.5v5M18 9.5v5"/></svg></span>
                        <div class="vyo-dlg-head">
                            <h3 class="vy-modal-title" id="vyo-pr-confirm-t" x-text="sure.title"></h3>
                            <p class="vyo-dlg-sub" x-text="sure.sub"></p>
                        </div>
                        <button type="button" class="vy-modal-x" x-on:click="confirmOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                    </div>
                    <div class="vy-modal-body">
                        <ul class="vyo-factlist">
                            <template x-for="fact in sure.facts" x-bind:key="fact"><li x-text="fact"></li></template>
                        </ul>
                    </div>
                    <div class="vy-modal-foot">
                        {{-- the focus lands on Cancel: an Enter pressed twice must not record money as owed or paid --}}
                        <button type="button" class="vy-btn vy-btn--ghost" x-on:click="confirmOpen = false" autofocus>Cancel</button>
                        <button type="button" class="vy-btn vy-btn--accent" x-on:click="go()" x-text="sure.button"></button>
                    </div>
                </div>
            </div>
        </div>
    </x-operator.shell>

    {{-- The confirm step: a form posts only once its own dialog is answered; without the script it posts as it always did. --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vyPayoutRun', () => ({
                confirmOpen: false,
                form: null,
                sure: { title: '', sub: '', facts: [], button: '' },
                ask(event) {
                    const form = event.target;
                    if (form.dataset.vySure === '1') return;   // answered — let it post; the submit guard takes it from here
                    // ⚠️ never fails open (the review): text that cannot be read still asks, in plain words
                    let sure;
                    try { sure = JSON.parse(form.dataset.confirm || ''); } catch (e) { sure = { title: 'Go ahead?', button: 'Go ahead', facts: [] }; }
                    event.preventDefault();
                    this.sure = Object.assign({ sub: '', facts: [] }, sure);
                    this.form = form;
                    this.confirmOpen = true;
                },
                go() {
                    const form = this.form;
                    if (! form) return;
                    form.dataset.vySure = '1';
                    this.confirmOpen = false;
                    if (typeof form.requestSubmit === 'function') { form.requestSubmit(); } else { form.submit(); }
                },
            }));
        });
    </script>
</div>
