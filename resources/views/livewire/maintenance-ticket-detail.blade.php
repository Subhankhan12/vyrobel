{{-- Operator Maintenance — the work order (design pass · Maintenance UI). Styled on the shared operator
     design system via <x-operator.shell>. The ticket, timeline (real audit events), OOS impact and cost
     destination are all REAL and tenant-scoped. The page READS; the lifecycle actions POST to the real
     MaintenanceActionController → MaintenanceService (guarded) and persist. Presentation only otherwise. --}}
<div>
    <x-operator.shell active="maintenance" title="Work order"
        :subtitle="'Unit ' . $ticket['unit'] . ' · ' . $ticket['category']" :viewer="$viewer">

        @if (session('mt_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                {{ session('mt_notice') }}
            </div>
        @endif
        @if (session('mt_error'))
            <div class="vy-flash is-err vy-fade" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 16h.01M10.3 3.9L2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                {{ session('mt_error') }}
            </div>
        @endif

        <a href="{{ route('app.maintenance') }}" class="vy-eyebrow vy-fade" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;margin-bottom:14px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Maintenance board
        </a>

        <div class="vy-detail-cols vy-fade">
            {{-- MAIN: facts · reported issue · activity timeline --}}
            <div class="vy-stack">
                <div class="vy-card">
                    <div class="vy-detail-head">
                        <span class="vy-detail-ref">{{ $ticket['title'] }}</span>
                        <span class="vy-tag tone-{{ $ticket['status_tone'] }}"><span class="vy-dot"></span>{{ $ticket['status_label'] }}</span>
                    </div>
                    <div class="vy-facts" style="margin-top: 14px;">
                        <div><div class="vy-fact-label">Unit</div><div class="vy-fact-val">{{ $ticket['unit'] }}@if ($ticket['unit_name']) · {{ $ticket['unit_name'] }}@endif</div></div>
                        <div><div class="vy-fact-label">Category</div><div class="vy-fact-val">{{ $ticket['category'] }}</div></div>
                        <div><div class="vy-fact-label">Priority</div><div class="vy-fact-val"><span class="vy-prio tone-{{ $ticket['priority_tone'] }}">{{ $ticket['priority_label'] }}</span></div></div>
                        <div><div class="vy-fact-label">Assignee</div><div class="vy-fact-val">{{ $ticket['assignee'] ?? 'Unassigned' }}</div></div>
                        <div><div class="vy-fact-label">Reported by</div><div class="vy-fact-val">{{ $ticket['reporter'] ?? '—' }}</div></div>
                        <div><div class="vy-fact-label">Opened</div><div class="vy-fact-val">{{ $ticket['opened'] }}</div></div>
                        @if ($ticket['resolved'])
                            <div><div class="vy-fact-label">Resolved</div><div class="vy-fact-val">{{ $ticket['resolved'] }}</div></div>
                        @endif
                    </div>
                </div>

                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 8px;">Reported issue</h2>
                    @if ($ticket['description'])
                        <p style="font-size: 13.5px; line-height: 1.6; color: var(--vy-ink); white-space: pre-line;">{{ $ticket['description'] }}</p>
                    @else
                        <div class="vy-empty" style="padding: 14px 0;">No description was provided when this ticket was opened.</div>
                    @endif
                </div>

                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 6px;">Activity</h2>
                    @if (count($timeline) === 0)
                        <div class="vy-empty" style="padding: 14px 0;">No activity recorded.</div>
                    @else
                        <div class="vy-timeline">
                            @foreach ($timeline as $event)
                                <div class="vy-tl-item">
                                    <div class="vy-tl-rail"><span class="vy-tl-dot"></span><span class="vy-tl-line"></span></div>
                                    <div class="vy-tl-body">
                                        <div class="vy-tl-label">
                                            {{ $event['label'] }}@if ($event['detail']) <span class="vy-tl-who">{{ $event['detail'] }}</span>@endif
                                        </div>
                                        <div class="vy-tl-meta">{{ $event['actor'] }} · {{ $event['at'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT RAIL: lifecycle (state-gated) · OOS impact · cost destination --}}
            <div class="vy-stack">
                {{-- Lifecycle — each action offered ONLY when the real state allows the transition. --}}
                <div class="vy-card vy-card--accent">
                    <span class="vy-eyebrow">Status &amp; actions</span>
                    <div style="margin: 10px 0 4px;">
                        <span class="vy-tag tone-{{ $ticket['status_tone'] }}"><span class="vy-dot"></span>{{ $ticket['status_label'] }}</span>
                    </div>

                    @if ($ticket['can_start'])
                        <form method="POST" action="{{ route('app.maintenance.start', $ticket['id']) }}" style="margin-top: 12px;">
                            @csrf
                            <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3l14 9-14 9z"/></svg>
                                Start work
                            </button>
                        </form>
                    @endif

                    @if ($ticket['can_resolve'])
                        {{-- Resolve with an optional real cost (property currency). On an OWNED unit a cost
                             posts a deductible owner expense via the certified path; the service decides. --}}
                        <form method="POST" action="{{ route('app.maintenance.resolve', $ticket['id']) }}" class="vy-resolve-form">
                            @csrf
                            <label class="vy-field-label" for="mt-cost">Parts / repair cost <span class="vy-fl-note">· optional</span></label>
                            <div class="vy-input-group">
                                <input class="vy-input" type="number" inputmode="decimal" step="0.01" min="0" name="cost" id="mt-cost" placeholder="{{ $currencySymbol }}0.00">
                                <button type="submit" class="vy-btn vy-btn--accent">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                    Mark resolved
                                </button>
                            </div>
                            <div class="vy-recon" style="margin-top: 8px;">Resolving returns the unit to sale (if this ticket was blocking it). A cost on an owner’s unit posts a deductible expense.</div>
                        </form>
                    @endif

                    @if ($ticket['can_assign'])
                        <div class="vy-hk-actions" style="margin-top: 12px; border-top: none; padding-top: 0;">
                            <details class="vy-assign">
                                <summary>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 20c0-3.3 2.7-5 6-5s6 1.7 6 5M18 14v6M21 17h-6"/></svg>
                                    {{ $ticket['assignee'] ? 'Reassign' : 'Assign' }}
                                </summary>
                                <form class="vy-assign-pop" method="POST" action="{{ route('app.maintenance.assign', $ticket['id']) }}">
                                    @csrf
                                    <input type="hidden" name="origin" value="detail">
                                    <select name="user_id" aria-label="Assign to">
                                        <option value="">— Unassigned —</option>
                                        @foreach ($staff as $person)
                                            <option value="{{ $person['id'] }}" @selected($ticket['assignee_id'] === $person['id'])>{{ $person['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="vy-act vy-act--accent" style="justify-content: center;">Save</button>
                                </form>
                            </details>
                        </div>
                    @endif

                    @if ($ticket['can_cancel'])
                        <form method="POST" action="{{ route('app.maintenance.cancel', $ticket['id']) }}" style="margin-top: 10px;"
                              onsubmit="return confirm('Cancel this maintenance ticket?');">
                            @csrf
                            <button type="submit" class="vy-act vy-act--ghost" style="width:100%;justify-content:center;">Cancel ticket</button>
                        </form>
                    @endif

                    @unless ($ticket['can_start'] || $ticket['can_resolve'] || $ticket['can_cancel'])
                        <div class="vy-recon" style="margin-top: 12px;">This ticket is {{ strtolower($ticket['status_label']) }} — no further actions.</div>
                    @endunless
                </div>

                {{-- INTEGRATION 1 — out of service impact (real condition_status) + the manual-move flag. --}}
                @if ($oos['active'])
                    <div class="vy-card">
                        <span class="vy-eyebrow">Out of service</span>
                        <div class="vy-callout is-oos" style="margin-top: 10px;">
                            <div class="vy-callout-title">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9L2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                                Unit blocked from sale
                            </div>
                            <div style="margin-top: 6px;">This ticket has taken unit {{ $ticket['unit'] }} out of service — not bookable until resolved.</div>
                            @if ($oos['bookings_to_move'] > 0)
                                <div style="margin-top: 9px; font-weight: 600;">
                                    {{ $oos['bookings_to_move'] }} {{ \Illuminate\Support\Str::plural('booking', $oos['bookings_to_move']) }} on this unit {{ $oos['bookings_to_move'] === 1 ? 'needs' : 'need' }} to be moved manually.
                                </div>
                                <a href="{{ route('app.rack') }}" class="vy-callout-move">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg>
                                    Open the rack to move them
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- INTEGRATION 2 — cost destination, read from the REAL owner_expense (or operator cost). --}}
                @if ($ticket['status'] === 'resolved' && $ticket['cost'] !== null)
                    <div class="vy-card">
                        <span class="vy-eyebrow">Cost</span>
                        <div class="vy-callout is-cost" style="margin-top: 10px;">
                            <div class="vy-cost-amt">{{ $ticket['cost'] }}</div>
                            @if ($expense)
                                <div style="margin-top: 6px;">
                                    Charged to <strong>{{ $expense['owner'] }}</strong> as a {{ $expense['deductible'] ? 'deductible' : 'non-deductible' }} expense — posted to their owner ledger.
                                </div>
                            @else
                                <div style="margin-top: 6px;">Operator cost — this unit isn’t owner-managed, so no owner expense was posted.</div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </x-operator.shell>
</div>
