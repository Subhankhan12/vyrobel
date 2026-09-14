{{-- Operator Work Order detail (#108) — exact-match to "Operator Work Order.html" on the shared operator
     design system. The ticket, timeline (real audit_events ∪ real notes), SLA (the ONE MaintenanceSla helper),
     out-of-order impact (real condition_status + real affected bookings) and cost (real owner_expense) are all
     REAL + tenant-scoped. The page READS; every mutation POSTs to the real MaintenanceActionController →
     MaintenanceService (guarded, read-only-respecting, audited). Honesty: the wireframe's Area / sequential
     WO-#### / technician specialty / "On site" / availability / outcome chips / "€/night on hold" are NOT
     modelled and are OMITTED (never fabricated, #39). --}}
<div>
    <x-operator.shell active="maintenance" title="Work order"
        :subtitle="'Unit ' . $ticket['unit'] . ' · ' . $ticket['category']" :viewer="$viewer">

        <div x-data="{ mr: false, rt: false }">

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

            {{-- breadcrumb — honest reference tail (a real id fragment, NOT a fabricated sequential WO-####) --}}
            <div class="vy-fade" style="display:flex;align-items:center;gap:6px;font-size:12.5px;margin-bottom:10px;">
                <a href="{{ route('app.maintenance') }}" style="display:inline-flex;align-items:center;gap:4px;color:var(--vy-muted);text-decoration:none;font-weight:500;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                    Maintenance
                </a>
                <span style="color:var(--vy-faint);">/</span>
                <span style="color:var(--vy-faint);font-variant-numeric:tabular-nums;">#{{ strtoupper(substr($ticket['id'], -6)) }}</span>
            </div>

            {{-- title row: h1 + badges + actions --}}
            <div class="vy-fade" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px;">
                <div style="min-width:0;">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        {{-- ⚠️ F-12 — an h2, not an h1. The operator shell already renders the page h1 ("Work order"), exactly
                             as it does on every other screen in this realm, so a second h1 here made TWO on one page —
                             confirmed across six audit phases. The ticket title is a heading UNDER that page title, which
                             is what h2 means; the styling is unchanged. --}}
                        <h2 style="margin:0;font-family:var(--vy-display);font-weight:600;font-size:27px;letter-spacing:-.02em;">{{ $ticket['title'] }}</h2>
                        <span class="vy-tag tone-{{ $ticket['status_tone'] }}"><span class="vy-dot"></span>{{ $ticket['status_label'] }}</span>
                        <span class="vy-prio tone-{{ $ticket['priority_tone'] }}">{{ $ticket['priority_label'] }}</span>
                        @if ($ticket['is_oos'])
                            <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:30px;background:rgba(217,102,46,.13);color:#B4400F;font-size:11.5px;font-weight:500;white-space:nowrap;">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/></svg>
                                Out of order
                            </span>
                        @endif
                    </div>
                    <div style="font-size:12.5px;color:var(--vy-faint);margin-top:4px;">
                        {{ $ticket['unit'] }}@if ($ticket['unit_name']) · {{ $ticket['unit_name'] }}@endif · {{ $ticket['category'] }} · reported {{ $ticket['reported_day'] }} {{ $ticket['reported_time'] }}
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    {{-- ⚠️ OPERATOR FIX 1 (audit Finding 4) — gated on the SAME condition that wraps the
                         composer this anchor targets. Ungated it was a DEAD affordance: an unentitled role
                         clicked "Add update" and scrolled to a read-only timeline with no composer. --}}
                    @if ($canManage && ! $readOnly)
                    <a href="#wo-composer" class="vy-btn vy-btn--ghost" style="padding:9px 16px;font-size:12.5px;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                        Add update
                    </a>
                    @endif
                    @if ($ticket['can_start'] && ! $readOnly)
                        <form method="POST" action="{{ route('app.maintenance.start', $ticket['id']) }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding:9px 18px;font-size:13px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3l14 9-14 9z"/></svg>
                                Start work
                            </button>
                        </form>
                    @endif
                    @if ($ticket['can_resolve'] && ! $readOnly)
                        <button type="button" class="vy-btn vy-btn--accent" style="padding:9px 18px;font-size:13px;" @click="mr = true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                            Mark resolved
                        </button>
                    @endif
                </div>
            </div>

            {{-- two columns --}}
            <div style="display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:18px;align-items:start;" class="vy-wo-grid">

                {{-- LEFT --}}
                <div style="display:flex;flex-direction:column;gap:18px;min-width:0;">

                    {{-- details (real facts; Area / WO-#### / specialty OMITTED — not modelled, #39) --}}
                    <div class="vy-card vy-fade">
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px 16px;">
                            <div>
                                <div class="vy-wo-flabel">Unit</div>
                                <div class="vy-wo-fval">{{ $ticket['unit'] }}</div>
                                @if ($ticket['unit_name'])<div class="vy-wo-fsub">{{ $ticket['unit_name'] }}</div>@endif
                            </div>
                            <div>
                                <div class="vy-wo-flabel">Category</div>
                                <div class="vy-wo-fval">{{ $ticket['category'] }}</div>
                            </div>
                            <div>
                                <div class="vy-wo-flabel">Priority</div>
                                <div class="vy-wo-fval" style="color:var(--vy-accent);">{{ $ticket['priority_label'] }}</div>
                                @if ($sla['same_day'])<div class="vy-wo-fsub">Same-day target</div>@endif
                            </div>
                            <div>
                                <div class="vy-wo-flabel">Reported</div>
                                <div class="vy-wo-fval" style="font-variant-numeric:tabular-nums;">{{ $ticket['reported_day'] }} · {{ $ticket['reported_time'] }}</div>
                                @if ($ticket['reporter'])<div class="vy-wo-fsub">by {{ $ticket['reporter'] }}</div>@endif
                            </div>
                            <div>
                                <div class="vy-wo-flabel">Assignee</div>
                                <div class="vy-wo-fval">{{ $ticket['assignee'] ?? 'Unassigned' }}</div>
                            </div>
                            <div>
                                <div class="vy-wo-flabel">Property</div>
                                <div class="vy-wo-fval">{{ $ticket['property_name'] }}</div>
                            </div>
                            <div>
                                <div class="vy-wo-flabel">Open for</div>
                                <div class="vy-wo-fval" style="font-variant-numeric:tabular-nums;">{{ $ticket['open_for'] }}</div>
                                <div class="vy-wo-fsub">since {{ $ticket['reported_time'] }}</div>
                            </div>
                            @if ($ticket['resolved'])
                                <div>
                                    <div class="vy-wo-flabel">Resolved</div>
                                    <div class="vy-wo-fval" style="font-variant-numeric:tabular-nums;">{{ $ticket['resolved'] }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- reported issue + real photos --}}
                    <div class="vy-card vy-fade">
                        <h2 class="vy-card-title" style="margin:0 0 10px;">Reported issue</h2>
                        @if ($ticket['description'])
                            <p style="margin:0 0 14px;font-size:13.5px;line-height:1.6;color:var(--vy-ink);white-space:pre-line;text-wrap:pretty;">{{ $ticket['description'] }}</p>
                        @else
                            <div class="vy-empty" style="padding:12px 0;margin-bottom:12px;">No description was provided when this ticket was opened.</div>
                        @endif
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            @foreach ($photos as $photo)
                                <div style="position:relative;">
                                    <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" style="width:92px;height:68px;object-fit:cover;border-radius:12px;display:block;">
                                    @if ($canManage && ! $readOnly)
                                        <form method="POST" action="{{ route('app.maintenance.photos.destroy', [$ticket['id'], $photo['id']]) }}" style="margin:0;position:absolute;top:4px;right:4px;" onsubmit="return confirm('Remove this photo?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Remove" style="width:20px;height:20px;border-radius:50%;border:none;background:rgba(43,35,32,.6);color:#fff;font-size:11px;line-height:1;cursor:pointer;display:grid;place-items:center;">×</button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                            @if ($canManage && ! $readOnly)
                                <form method="POST" action="{{ route('app.maintenance.photos.store', $ticket['id']) }}" enctype="multipart/form-data" style="margin:0;">
                                    @csrf
                                    <label style="width:92px;height:68px;border-radius:12px;background:rgba(120,70,40,.06);border:1px dashed rgba(120,70,40,.20);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;color:var(--vy-faint);cursor:pointer;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                        <span style="font-size:10px;font-weight:500;">Add photo</span>
                                        <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" style="display:none;" onchange="this.form.submit()">
                                    </label>
                                </form>
                            @elseif (count($photos) === 0)
                                <div class="vy-empty" style="padding:8px 0;">No photos.</div>
                            @endif
                        </div>
                    </div>

                    {{-- activity timeline + composer --}}
                    <div class="vy-card vy-fade" id="wo-composer">
                        <h2 class="vy-card-title" style="margin:0 0 16px;">Activity</h2>

                        @forelse ($timeline as $event)
                            <div style="display:flex;gap:14px;">
                                <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;padding-top:2px;">
                                    <span style="width:11px;height:11px;border-radius:50%;background:{{ $event['dot'] }};box-shadow:0 0 0 4px {{ $event['ring'] }};flex-shrink:0;"></span>
                                    @if ($event['show_line'])
                                        <span style="flex:1;width:2px;min-height:26px;background:rgba(120,70,40,.14);margin:4px 0;"></span>
                                    @endif
                                </div>
                                <div style="min-width:0;flex:1;padding-bottom:18px;">
                                    <div style="display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;">
                                        <span style="font-size:13.5px;font-weight:500;">{{ $event['label'] }}</span>
                                        <span style="font-size:11.5px;color:var(--vy-faint);font-variant-numeric:tabular-nums;">{{ $event['time'] }}</span>
                                    </div>
                                    <div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px;">{{ $event['actor'] }}</div>
                                    @if ($event['has_note'] && $event['note'])
                                        <div style="font-size:12.5px;color:var(--vy-ink);line-height:1.5;margin-top:8px;padding:10px 13px;border-radius:12px;background:rgba(120,70,40,.05);border:1px solid rgba(120,70,40,.10);text-wrap:pretty;white-space:pre-line;">{{ $event['note'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="vy-empty" style="padding:12px 0;">No activity recorded yet.</div>
                        @endforelse

                        {{-- composer — real note store (Note / Progress / Part ordered) --}}
                        @if ($canManage && ! $readOnly)
                            <form method="POST" action="{{ route('app.maintenance.notes.store', $ticket['id']) }}"
                                  x-data="{ kind: 'note', body: '' }"
                                  style="display:flex;gap:11px;margin-top:4px;padding-top:16px;border-top:1px solid rgba(120,70,40,.10);">
                                @csrf
                                <input type="hidden" name="kind" :value="kind">
                                <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(140deg,#F0A57A,#D9662E);display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:500;flex-shrink:0;">{{ $viewer['initials'] ?? 'You' }}</div>
                                <div style="flex:1;min-width:0;">
                                    <div style="display:inline-grid;grid-auto-flow:column;gap:4px;padding:4px;border-radius:12px;background:rgba(120,70,40,.06);border:1px solid rgba(120,70,40,.10);margin-bottom:10px;">
                                        @foreach ($noteKinds as $k)
                                            <button type="button" @click="kind = '{{ $k['key'] }}'"
                                                    :style="kind === '{{ $k['key'] }}' ? 'background:rgba(255,255,255,.9);color:#C2410C;box-shadow:0 2px 8px rgba(150,90,50,.16);font-weight:600' : 'background:transparent;color: var(--vy-muted);font-weight:500'"
                                                    style="padding:6px 14px;border-radius:9px;border:none;font-family:inherit;font-size:11.5px;cursor:pointer;white-space:nowrap;transition:background .16s,color .16s;">{{ $k['label'] }}</button>
                                        @endforeach
                                    </div>
                                    <label class="vy-sr-only" for="vy-lbl-maintenance-ticket-detail-body">Add an update for the team…</label>
                                    <textarea id="vy-lbl-maintenance-ticket-detail-body" name="body" x-model="body" maxlength="2000" placeholder="Add an update for the team…" required
                                              style="width:100%;min-height:58px;resize:vertical;border:1px solid rgba(120,70,40,.14);background:rgba(255,255,255,.7);border-radius:14px;padding:11px 14px;font-family:inherit;font-size:13px;line-height:1.5;color:var(--vy-ink);outline:none;box-sizing:border-box;"></textarea>
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:10px;">
                                        <button type="submit" class="vy-btn vy-btn--accent" style="padding:9px 18px;font-size:12.5px;" :style="body.trim().length ? '' : 'opacity:.5;'">
                                            Post update
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- RIGHT --}}
                <div style="display:flex;flex-direction:column;gap:18px;min-width:0;">

                    {{-- dark status hero (the prototype's "€/night on hold" figure is REFUSED — no revenue-on-hold calc, #39) --}}
                    <div class="vy-tonight vy-fade">
                        <span class="vy-eyebrow" style="color:#B9ABA0;">Status</span>
                        <div style="display:flex;align-items:center;gap:8px;margin-top:10px;">
                            <span style="width:9px;height:9px;border-radius:50%;background:var(--vy-accent);box-shadow:0 0 0 4px rgba(232,112,58,.25);"></span>
                            <span style="font-family:var(--vy-display);font-weight:600;font-size:22px;letter-spacing:-.01em;color:#fff;">{{ $ticket['status_label'] }}</span>
                        </div>
                        <div style="font-size:12.5px;color:#B9ABA0;margin-top:6px;">{{ $ticket['priority_label'] }} priority · Unit {{ $ticket['unit'] }}@if ($ticket['is_oos']) · out of order @endif</div>
                        @if ($ticket['can_resolve'] && ! $readOnly)
                            <button type="button" @click="mr = true" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;margin-top:16px;">Mark resolved</button>
                        @endif
                    </div>

                    {{-- assignee --}}
                    <div class="vy-card vy-fade" style="padding:18px 20px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;">
                            <div class="vy-eyebrow">Assigned to</div>
                            @if ($ticket['can_assign'] && ! $readOnly)
                                <button type="button" @click="rt = true" style="font-family:inherit;font-size:12px;font-weight:500;color:var(--vy-accent);background:transparent;border:none;padding:0;cursor:pointer;">Reassign</button>
                            @endif
                        </div>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(140deg,#F0A57A,#D9662E);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;font-weight:500;flex-shrink:0;">{{ $ticket['assignee_initials'] ?? '—' }}</div>
                            <div style="min-width:0;flex:1;">
                                <div style="font-size:14px;font-weight:500;">{{ $ticket['assignee'] ?? 'Unassigned' }}</div>
                                <div style="font-size:11.5px;color:var(--vy-faint);margin-top:1px;">{{ $ticket['assignee'] ? 'Technician' : 'No one assigned yet' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- SLA timing — the SAME real MaintenanceSla the board reads --}}
                    @if ($sla['has_target'])
                        <div class="vy-card vy-fade" style="padding:18px 20px;">
                            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:10px;margin-bottom:10px;">
                                <div class="vy-eyebrow">{{ $sla['same_day'] ? 'Same-day target' : 'Resolution target' }}</div>
                                @php $slaColor = $sla['breached'] ? '#C2410C' : ($sla['due_soon'] ? '#8F6414' : '#1a7f4b'); @endphp
                                <div style="font-size:12px;font-weight:500;color:{{ $slaColor }};font-variant-numeric:tabular-nums;">{{ $sla['label'] }}</div>
                            </div>
                            <div style="height:8px;border-radius:5px;background:rgba(120,70,40,.10);overflow:hidden;margin-bottom:10px;">
                                <div style="height:100%;width:{{ $sla['progress_pct'] }}%;border-radius:5px;background:linear-gradient(90deg,#F0A57A,#C2410C);"></div>
                            </div>
                            <div style="display:flex;align-items:center;justify-content:space-between;font-size:11.5px;color:var(--vy-faint);font-variant-numeric:tabular-nums;">
                                <span>Reported {{ $sla['reported_time'] }}</span>
                                @if ($sla['due_by'])<span>Due by {{ $sla['due_by'] }}</span>@endif
                            </div>
                        </div>
                    @endif

                    {{-- out-of-order impact — real block + real affected bookings + the real rack --}}
                    @if ($oos['active'])
                        <div class="vy-card vy-fade" style="padding:18px 20px;">
                            <div class="vy-eyebrow" style="margin-bottom:12px;">Out of order impact</div>
                            <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;">
                                <span style="width:30px;height:30px;border-radius:9px;background:rgba(217,102,46,.13);color:#B4400F;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M3 9.5h18"/><path d="M8 2.5v4"/><path d="M16 2.5v4"/></svg>
                                </span>
                                <div style="min-width:0;flex:1;">
                                    <div style="font-size:12.5px;font-weight:500;">{{ $oos['impact_label'] }}</div>
                                    <div style="font-size:11px;color:var(--vy-faint);margin-top:1px;">{{ $ticket['unit'] }}@if ($ticket['unit_name']) · {{ $ticket['unit_name'] }}@endif is blocked from sale</div>
                                </div>
                            </div>
                            @if ($oos['bookings_to_move'] > 0)
                                <a href="{{ route('app.rack') }}" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;color:var(--vy-accent);text-decoration:none;">
                                    Open the rack to reassign
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                                </a>
                            @endif
                        </div>
                    @endif

                    {{-- cost destination — real owner_expense (or operator cost), when resolved --}}
                    @if ($ticket['status'] === 'resolved' && ($ticket['cost'] !== null || $ticket['labor_hours_label']))
                        <div class="vy-card vy-fade" style="padding:18px 20px;">
                            <span class="vy-eyebrow">Cost</span>
                            @if ($ticket['cost'] !== null)
                                <div style="font-family:var(--vy-display);font-size:22px;font-weight:700;margin-top:8px;">{{ $ticket['cost'] }}</div>
                            @endif
                            @if ($ticket['labor_hours_label'])
                                <div style="font-size:12px;color:var(--vy-faint);margin-top:{{ $ticket['cost'] !== null ? '2px' : '8px' }};">{{ $ticket['labor_hours_label'] }} logged</div>
                            @endif
                            @if ($ticket['cost'] !== null)
                                @if ($expense)
                                    <div style="font-size:12px;color:var(--vy-muted);margin-top:4px;line-height:1.5;">Charged to <strong>{{ $expense['owner'] }}</strong> as a {{ $expense['deductible'] ? 'deductible' : 'non-deductible' }} expense — posted to their owner ledger.</div>
                                @else
                                    <div style="font-size:12px;color:var(--vy-muted);margin-top:4px;line-height:1.5;">Operator cost — this unit isn’t owner-managed, so no owner expense was posted.</div>
                                @endif
                            @endif
                        </div>
                    @endif

                    {{-- honest extra controls the wireframe doesn't depict (open/active states): Parts + Cancel --}}
                    @if (($ticket['can_parts'] || $ticket['can_cancel']) && ! $readOnly)
                        <div class="vy-card vy-fade" style="padding:18px 20px;">
                            <span class="vy-eyebrow">Manage</span>
                            @if ($ticket['can_parts'])
                                @if ($ticket['awaiting_parts'])
                                    <div style="font-size:13px;font-weight:500;margin-top:8px;">Waiting on: {{ $ticket['part_name'] ?? 'a part' }}</div>
                                    @if ($ticket['part_eta_label'])<div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px;">Expected {{ $ticket['part_eta_label'] }}.</div>@endif
                                    <form method="POST" action="{{ route('app.maintenance.parts', $ticket['id']) }}" style="margin-top:10px;">
                                        @csrf
                                        <input type="hidden" name="awaiting_parts" value="0">
                                        <button type="submit" class="vy-btn vy-btn--ghost" style="width:100%;justify-content:center;font-size:12px;">Parts arrived — clear</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('app.maintenance.parts', $ticket['id']) }}" style="margin-top:10px;display:flex;flex-direction:column;gap:8px;">
                                        @csrf
                                        <input type="hidden" name="awaiting_parts" value="1">
                                        <label class="vy-sr-only" for="vy-lbl-maintenance-ticket-detail-part-name">Part needed (e.g. thermostat)</label>
                                        <input id="vy-lbl-maintenance-ticket-detail-part-name" class="vy-input" type="text" name="part_name" maxlength="160" placeholder="Part needed (e.g. thermostat)">
                                        <input class="vy-input" type="date" name="part_eta" aria-label="Expected arrival">
                                        <button type="submit" class="vy-btn vy-btn--ghost" style="width:100%;justify-content:center;font-size:12px;">Mark awaiting parts</button>
                                    </form>
                                @endif
                            @endif
                            @if ($ticket['can_cancel'])
                                <form method="POST" action="{{ route('app.maintenance.cancel', $ticket['id']) }}" style="margin-top:10px;" onsubmit="return confirm('Cancel this work order?');">
                                    @csrf
                                    <button type="submit" class="vy-btn vy-btn--ghost" style="width:100%;justify-content:center;font-size:12px;color:#B0322A;">Cancel work order</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Reassign technician modal (real staff + real load; reason/notify added in GATE 2) ────── --}}
            @if ($ticket['can_assign'] && ! $readOnly)
                <div x-show="rt" x-cloak style="position:fixed;inset:0;z-index:50;background:rgba(43,35,32,.38);backdrop-filter:blur(7px);display:flex;align-items:center;justify-content:center;padding:24px;" @click="rt = false">
                    <form method="POST" action="{{ route('app.maintenance.assign', $ticket['id']) }}" x-data="{ sel: '{{ $ticket['assignee_id'] }}', reason: '', notify: true }" @click.stop
                          class="vy-card" style="width:470px;max-width:100%;max-height:92vh;overflow-y:auto;padding:24px;">
                        @csrf
                        <input type="hidden" name="origin" value="detail">
                        <input type="hidden" name="user_id" :value="sel">
                        <input type="hidden" name="reason" :value="reason">
                        <input type="hidden" name="notify" :value="notify ? '1' : '0'">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:18px;">
                            <div style="display:flex;align-items:center;gap:11px;">
                                <div style="width:38px;height:38px;border-radius:11px;background:linear-gradient(140deg,#E8703A,#C2410C);display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2l4 4-4 4"/><path d="M21 6H9a4 4 0 0 0-4 4v1"/><path d="M7 22l-4-4 4-4"/><path d="M3 18h12a4 4 0 0 0 4-4v-1"/></svg>
                                </div>
                                <div>
                                    <div style="font-family:var(--vy-display);font-weight:600;font-size:19px;letter-spacing:-.01em;">Reassign technician</div>
                                    <div style="font-size:12.5px;color:var(--vy-faint);margin-top:2px;">Currently with {{ $ticket['assignee'] ?? 'no one' }}</div>
                                </div>
                            </div>
                            <button type="button" @click="rt = false" style="width:32px;height:32px;border-radius:50%;background:rgba(120,70,40,.07);border:none;color:var(--vy-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
                            </button>
                        </div>
                        <div class="vy-eyebrow" style="margin-bottom:9px;">Reassign to</div>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:18px;">
                            @foreach ($staff as $person)
                                <button type="button" @click="sel = '{{ $person['id'] }}'"
                                        :style="sel === '{{ $person['id'] }}' ? 'background:rgba(217,102,46,.10);border:1px solid rgba(232,112,58,.45)' : 'background:rgba(255,255,255,.5);border:1px solid rgba(120,70,40,.12)'"
                                        style="display:flex;align-items:center;gap:12px;width:100%;text-align:left;padding:12px 14px;border-radius:14px;cursor:pointer;font-family:inherit;transition:background .14s,border-color .14s;">
                                    <span style="width:38px;height:38px;border-radius:50%;background:linear-gradient(140deg,#F0A57A,#D9662E);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:500;flex-shrink:0;">{{ $person['initials'] }}</span>
                                    <span style="min-width:0;flex:1;">
                                        <span style="display:flex;align-items:center;gap:8px;min-width:0;">
                                            <span style="font-size:13.5px;font-weight:500;color:var(--vy-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0;">{{ $person['name'] }}</span>
                                            @if ($person['is_current'])<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:30px;background:rgba(120,70,40,.09);color:var(--vy-muted);font-size:9.5px;font-weight:500;white-space:nowrap;flex-shrink:0;">Current</span>@endif
                                        </span>
                                        <span style="display:block;font-size:11.5px;color:var(--vy-faint);margin-top:2px;">{{ $person['load'] }}</span>
                                    </span>
                                    <span :style="sel === '{{ $person['id'] }}' ? 'background:linear-gradient(140deg,#E8703A,#C2410C);border:none' : 'background:rgba(255,255,255,.6);border:2px solid rgba(120,70,40,.22)'"
                                          style="width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-sizing:border-box;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" x-show="sel === '{{ $person['id'] }}'"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                                    </span>
                                </button>
                            @endforeach
                            <button type="button" @click="sel = ''"
                                    :style="sel === '' ? 'background:rgba(217,102,46,.10);border:1px solid rgba(232,112,58,.45)' : 'background:rgba(255,255,255,.5);border:1px solid rgba(120,70,40,.12)'"
                                    style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:14px;cursor:pointer;font-family:inherit;font-size:12.5px;color:var(--vy-muted);">Leave unassigned</button>
                        </div>
                        {{-- reason (optional) — a real recorded field on the assign event, never a chip that persists nothing --}}
                        <div class="vy-eyebrow" style="margin-bottom:9px;">Reason <span style="text-transform:none;letter-spacing:0;color:var(--vy-faint);">· optional</span></div>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px;">
                            @foreach (['Balancing load', 'Specialty match', 'Unavailable', 'Escalation'] as $r)
                                <button type="button" @click="reason = (reason === '{{ $r }}' ? '' : '{{ $r }}')"
                                        :style="reason === '{{ $r }}' ? 'background:rgba(217,102,46,.13);color:#B4400F;border:1px solid rgba(232,112,58,.30)' : 'background:rgba(120,70,40,.05);color: var(--vy-muted);border:1px solid rgba(120,70,40,.12)'"
                                        style="padding:7px 13px;border-radius:30px;font-family:inherit;font-size:11.5px;font-weight:500;cursor:pointer;transition:background .14s,color .14s;">{{ $r }}</button>
                            @endforeach
                        </div>
                        {{-- notify both technicians --}}
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;border:1px solid rgba(120,70,40,.10);background:rgba(255,255,255,.45);border-radius:14px;margin-bottom:18px;">
                            <div style="min-width:0;">
                                <div style="font-size:13px;font-weight:500;">Notify both technicians</div>
                                <div style="font-size:11px;color:var(--vy-faint);margin-top:1px;">The new assignee gets the order; any prior assignee is told they’re off it</div>
                            </div>
                            <button type="button" @click="notify = !notify" :style="notify ? 'background:linear-gradient(140deg,#E8703A,#C2410C)' : 'background:rgba(120,70,40,.18)'" style="width:38px;height:22px;border-radius:30px;border:none;cursor:pointer;position:relative;flex-shrink:0;transition:background .16s;padding:0;">
                                <span :style="notify ? 'left:19px' : 'left:3px'" style="position:absolute;top:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 2px 6px rgba(60,30,10,.25);transition:left .16s;"></span>
                            </button>
                        </div>
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;padding:13px 0;font-size:14px;" :disabled="sel === '{{ $ticket['assignee_id'] }}'" :style="sel === '{{ $ticket['assignee_id'] }}' ? 'opacity:.45;cursor:default' : ''">Reassign</button>
                        <button type="button" @click="rt = false" class="vy-btn vy-btn--ghost" style="width:100%;justify-content:center;margin-top:10px;padding:11px 0;">Cancel</button>
                    </form>
                </div>
            @endif

            {{-- ── Mark-resolved modal — the full resolution flow (real cost #17 + hours + notes + return-to-sale + notify) ── --}}
            @if ($ticket['can_resolve'] && ! $readOnly)
                <div x-show="mr" x-cloak style="position:fixed;inset:0;z-index:50;background:rgba(43,35,32,.38);backdrop-filter:blur(7px);display:flex;align-items:center;justify-content:center;padding:24px;" @click="mr = false">
                    <form method="POST" action="{{ route('app.maintenance.resolve', $ticket['id']) }}" @click.stop x-data="{ ret: true, notify: true }"
                          class="vy-card" style="width:480px;max-width:100%;max-height:92vh;overflow-y:auto;padding:24px;">
                        @csrf
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:18px;">
                            <div style="display:flex;align-items:center;gap:11px;">
                                <div style="width:38px;height:38px;border-radius:11px;background:linear-gradient(140deg,#E8703A,#C2410C);display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                                </div>
                                <div>
                                    <div style="font-family:var(--vy-display);font-weight:600;font-size:19px;letter-spacing:-.01em;">Mark resolved</div>
                                    <div style="font-size:12.5px;color:var(--vy-faint);margin-top:2px;">{{ $ticket['title'] }} · {{ $ticket['unit'] }}</div>
                                </div>
                            </div>
                            <button type="button" @click="mr = false" style="width:32px;height:32px;border-radius:50%;background:rgba(120,70,40,.07);border:none;color:var(--vy-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
                            </button>
                        </div>
                        {{-- what was done → the ticket's resolution record + a timeline note --}}
                        <div style="margin-bottom:14px;">
                            <div class="vy-eyebrow" style="margin-bottom:7px;">What was done</div>
                            <textarea id="vy-lbl-maintenance-ticket-detail-resolution" name="resolution" maxlength="2000" placeholder="e.g. Replaced the trap and seal — tested, no leak." style="width:100%;min-height:66px;resize:vertical;border:1px solid rgba(120,70,40,.14);background:rgba(255,255,255,.75);border-radius:14px;padding:11px 14px;font-family:inherit;font-size:13px;line-height:1.5;color:var(--vy-ink);outline:none;box-sizing:border-box;"></textarea>
                        </div>
                        {{-- time spent (hours, informational) + parts cost (€, bills the owner, #17) --}}
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
                            <div>
                                {{-- ⚠️⚠️ D3 · MONEY-ADJACENT. `parts` posts a REAL owner-ledger charge and `time_spent` its labour
                                 counterpart, and both were named only by an adjacent `<div>` — visible, but
                                 associated with nothing. Two unlabelled number boxes side by side, one of which
                                 moves money: an operator relying on a screen reader had reading order alone to
                                 tell them apart, and a cost entered in the wrong box charges the wrong thing.
                                 ⚠️ The label TEXT already existed and is unchanged — only the ELEMENT changed,
                                 `<div>` → `<label for>`, so the page looks exactly as before.
                                 ⚠️ NO POSTED KEY MOVED: `name="parts"` and `name="time_spent"` are untouched. --}}
                                <label class="vy-eyebrow" style="margin-bottom:7px;" for="vy-mt-time">Time spent</label>
                                <div style="display:flex;align-items:center;gap:8px;border:1px solid rgba(120,70,40,.14);background:rgba(255,255,255,.75);border-radius:14px;padding:11px 14px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#6C645F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    <input type="number" inputmode="decimal" step="0.25" min="0" name="time_spent" id="vy-mt-time" placeholder="e.g. 1.5" style="flex:1;min-width:0;border:none;outline:none;background:transparent;font-family:inherit;font-size:13.5px;color:var(--vy-ink);padding:0;font-variant-numeric:tabular-nums;">
                                    <span style="color:var(--vy-faint);font-size:12px;flex-shrink:0;">h</span>
                                </div>
                            </div>
                            <div>
                                <label class="vy-eyebrow" style="margin-bottom:7px;" for="vy-mt-parts">Parts cost</label>
                                <div style="display:flex;align-items:center;gap:8px;border:1px solid rgba(120,70,40,.14);background:rgba(255,255,255,.75);border-radius:14px;padding:11px 14px;">
                                    <span style="color:var(--vy-faint);font-size:13.5px;flex-shrink:0;">{{ $currencySymbol }}</span>
                                    <input type="number" inputmode="decimal" step="0.01" min="0" name="parts" id="vy-mt-parts" placeholder="0.00" style="flex:1;min-width:0;border:none;outline:none;background:transparent;font-family:inherit;font-size:13.5px;color:var(--vy-ink);padding:0;font-variant-numeric:tabular-nums;">
                                </div>
                            </div>
                        </div>
                        @if ($ticket['is_oos'])
                            {{-- return to sale — genuinely un-blocks the unit (default ON = today's behaviour) --}}
                            <input type="hidden" name="return_to_sale" :value="ret ? '1' : '0'">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 16px;border-radius:14px;background:rgba(217,102,46,.08);border:1px solid rgba(232,112,58,.28);margin-bottom:12px;">
                                <div style="display:flex;align-items:flex-start;gap:11px;min-width:0;">
                                    <span style="width:32px;height:32px;border-radius:9px;background:rgba(217,102,46,.14);color:#B4400F;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M3 9.5h18"/><path d="M8 2.5v4"/><path d="M16 2.5v4"/><path d="m9 15 2 2 4-4"/></svg>
                                    </span>
                                    <div style="min-width:0;">
                                        <div style="font-size:13px;font-weight:500;">Return {{ $ticket['unit'] }} to sale</div>
                                        <div style="font-size:11px;margin-top:2px;line-height:1.45;" :style="ret ? 'color:#B4400F' : 'color: var(--vy-faint)'" x-text="ret ? 'The unit goes back on sale immediately.' : 'Stays out of order — lift the block later from the rack.'"></div>
                                    </div>
                                </div>
                                <button type="button" @click="ret = !ret" :style="ret ? 'background:linear-gradient(140deg,#E8703A,#C2410C)' : 'background:rgba(120,70,40,.18)'" style="width:38px;height:22px;border-radius:30px;border:none;cursor:pointer;position:relative;flex-shrink:0;transition:background .16s;padding:0;">
                                    <span :style="ret ? 'left:19px' : 'left:3px'" style="position:absolute;top:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 2px 6px rgba(60,30,10,.25);transition:left .16s;"></span>
                                </button>
                            </div>
                        @endif
                        {{-- notify the front desk — a real, preference-gated ping to the reporter --}}
                        <input type="hidden" name="notify" :value="notify ? '1' : '0'">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;border:1px solid rgba(120,70,40,.10);background:rgba(255,255,255,.45);border-radius:14px;margin-bottom:18px;">
                            <div>
                                <div style="font-size:13px;font-weight:500;">Notify the front desk</div>
                                <div style="font-size:11px;color:var(--vy-faint);margin-top:1px;">Lets {{ $ticket['reporter'] ?? 'the reporter' }} know it’s fixed and back online</div>
                            </div>
                            <button type="button" @click="notify = !notify" :style="notify ? 'background:linear-gradient(140deg,#E8703A,#C2410C)' : 'background:rgba(120,70,40,.18)'" style="width:38px;height:22px;border-radius:30px;border:none;cursor:pointer;position:relative;flex-shrink:0;transition:background .16s;padding:0;">
                                <span :style="notify ? 'left:19px' : 'left:3px'" style="position:absolute;top:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 2px 6px rgba(60,30,10,.25);transition:left .16s;"></span>
                            </button>
                        </div>
                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;padding:13px 0;font-size:14px;">
                            @if ($ticket['is_oos'])<span x-text="ret ? 'Resolve & return to sale' : 'Mark resolved'"></span>@else Mark resolved @endif
                        </button>
                        <button type="button" @click="mr = false" class="vy-btn vy-btn--ghost" style="width:100%;justify-content:center;margin-top:10px;padding:11px 0;">Cancel</button>
                    </form>
                </div>
            @endif
        </div>

    </x-operator.shell>
</div>
