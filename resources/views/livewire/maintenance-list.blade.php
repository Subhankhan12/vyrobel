{{-- Operator Maintenance — the work-order kanban (design pass · Maintenance UI). Styled on the shared
     operator design system via <x-operator.shell>. Tickets + statuses are REAL and tenant-scoped; the
     3 lanes ARE the real active-status enum (open / in_progress / resolved). The board only READS; the
     two mutations it offers — open a ticket ("+ New ticket") and assign — POST to the real actions
     (MaintenanceActionController → MaintenanceService) and persist. Alpine (bundled with Livewire) drives
     ONLY the New-ticket modal's presentation; per-card Assign is a native <details> disclosure. --}}
<div>
    <x-operator.shell active="maintenance" title="Maintenance" subtitle="Work orders" :viewer="$viewer">
        <div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">

        {{-- Flash: success / graceful error --}}
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

        {{-- KPI band — DERIVED real aggregates over the org's own tickets, as uniform light tiles (the
             shared .vy-dash-kpis band — default repeat(4) = exactly these 4 tiles — matching the prototype's
             KPI row and the Dashboard/Rates precedent). These are the app's FOUR REAL stats; the prototype's
             extra "Awaiting parts" tile is refused (no such status exists; a test asserts it never renders). --}}
        <div class="vy-dash-kpis vy-fade">
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Open</div>
                <div class="vy-dk-val">{{ $summary['open'] }}</div>
                <div class="vy-dk-sub">work orders awaiting action</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">In progress</div>
                <div class="vy-dk-val">{{ $summary['in_progress'] }}</div>
                <div class="vy-dk-sub">being worked on now</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Resolved · month</div>
                <div class="vy-dk-val">{{ $summary['resolved_month'] }}</div>
                <div class="vy-dk-sub">closed this calendar month</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Units out of service</div>
                <div class="vy-dk-val">{{ $summary['out_of_service'] }}</div>
                <div class="vy-dk-sub">blocked from sale until resolved</div>
            </div>
        </div>

        {{-- Toolbar — actions only (the summary moved up into the KPI band). Honest "Filters · Soon". --}}
        <div class="vy-rack-toolbar vy-fade" style="justify-content: flex-end;">
            <div class="vy-right">
                <span class="vy-pill is-soon" aria-disabled="true">Filters <span class="vy-soon-tag">Soon</span></span>
                @can('maintenance.manage')
                    <a class="vy-btn vy-btn--ghost" href="{{ route('app.maintenance.costs') }}" style="margin-right: 8px;">Costs</a>
                    <a class="vy-btn vy-btn--ghost" href="{{ route('app.maintenance.settings') }}" style="margin-right: 8px;">Settings</a>
                @endcan
                <button type="button" class="vy-btn vy-btn--accent" @click="open = true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    New ticket
                </button>
            </div>
        </div>

        {{-- Units out of service — REAL attention rail (real condition_status, links to the governing ticket) --}}
        @if (count($oosUnits) > 0)
            <div class="vy-oos-rail vy-fade">
                <span class="vy-oos-ico">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9L2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                </span>
                <div class="vy-oos-rail-body">
                    <div class="vy-oos-rail-title">{{ count($oosUnits) }} {{ \Illuminate\Support\Str::plural('unit', count($oosUnits)) }} out of service — not bookable until resolved</div>
                    <div class="vy-oos-list">
                        @foreach ($oosUnits as $u)
                            @if ($u['ticket_id'])
                                <a class="vy-oos-item" href="{{ route('app.maintenance.show', $u['ticket_id']) }}" title="{{ $u['ticket_title'] }}">
                                    <span class="vy-oos-code">{{ $u['code'] }}</span>@if ($u['name'])<span class="vy-mt-when">{{ $u['name'] }}</span>@endif
                                </a>
                            @else
                                <span class="vy-oos-item"><span class="vy-oos-code">{{ $u['code'] }}</span>@if ($u['name'])<span class="vy-mt-when">{{ $u['name'] }}</span>@endif</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @php $total = $summary['open'] + $summary['in_progress'] + count($columns[2]['tickets']); @endphp
        @if ($total === 0)
            <div class="vy-card vy-fade"><div class="vy-empty" style="padding: 40px 20px;">No maintenance tickets yet — open one with “New ticket”.</div></div>
        @else
            <div class="vy-kanban vy-fade">
                @foreach ($columns as $col)
                    <div class="vy-col tone-{{ $col['tone'] }}">
                        <div class="vy-col-head">
                            <span class="vy-col-title"><span class="vy-col-dot"></span>{{ $col['label'] }}</span>
                            <span class="vy-col-count">{{ count($col['tickets']) }}</span>
                        </div>
                        <div class="vy-col-body">
                            @forelse ($col['tickets'] as $ticket)
                                <div class="vy-hk-card">
                                    <div class="vy-hk-top">
                                        <a class="vy-mt-title" href="{{ route('app.maintenance.show', $ticket['id']) }}">{{ $ticket['title'] }}</a>
                                    </div>
                                    <div class="vy-mt-sub">
                                        <span class="vy-hk-unit">Unit {{ $ticket['unit'] }}</span>
                                        <span class="vy-mt-cat">· {{ $ticket['category'] }}</span>
                                        @if ($ticket['oos'])
                                            <span class="vy-tag tone-oos"><span class="vy-dot"></span>Unit OOS</span>
                                        @endif
                                    </div>
                                    <div class="vy-hk-meta">
                                        <span class="vy-prio tone-{{ $ticket['priority_tone'] }}">{{ $ticket['priority_label'] }}</span>
                                        @if ($ticket['assignee'])
                                            <span class="vy-hk-assignee">
                                                <span class="vy-mini-avatar">{{ strtoupper(mb_substr($ticket['assignee'], 0, 1)) }}</span>
                                                {{ $ticket['assignee'] }}
                                            </span>
                                        @else
                                            <span class="vy-hk-unassigned">Unassigned</span>
                                        @endif
                                    </div>

                                    @if ($ticket['cost'] !== null)
                                        <div class="vy-mt-foot">
                                            <span class="vy-mt-cost">{{ $ticket['cost'] }}</span>
                                            @if ($ticket['resolved'])<span class="vy-mt-when">Resolved {{ $ticket['resolved'] }}</span>@endif
                                        </div>
                                    @endif

                                    {{-- Assign — REAL (MaintenanceService::assign) via POST → PRG. Only offered on
                                         active tickets (open/in_progress); resolved tickets are terminal. --}}
                                    @if ($col['key'] !== 'resolved')
                                        <div class="vy-hk-actions">
                                            <details class="vy-assign">
                                                <summary>
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 20c0-3.3 2.7-5 6-5s6 1.7 6 5M18 14v6M21 17h-6"/></svg>
                                                    {{ $ticket['assignee'] ? 'Reassign' : 'Assign' }}
                                                </summary>
                                                <form class="vy-assign-pop" method="POST" action="{{ route('app.maintenance.assign', $ticket['id']) }}">
                                                    @csrf
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
                                </div>
                            @empty
                                <div class="vy-hk-col-empty">None</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ============================ NEW TICKET MODAL ============================ --}}
        <div class="vy-modal-backdrop" x-cloak x-show="open" x-transition.opacity
             @keydown.escape.window="open = false" @click.self="open = false">
            <div class="vy-modal" x-show="open" x-transition
                 role="dialog" aria-modal="true" aria-labelledby="vy-mt-title">
                <form method="POST" action="{{ route('app.maintenance.store') }}">
                    @csrf

                    <div class="vy-modal-head">
                        <span class="vy-modal-ico">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z"/></svg>
                        </span>
                        <div style="flex: 1;">
                            <h3 class="vy-modal-title" id="vy-mt-title">New maintenance ticket</h3>
                            <p class="vy-modal-sub">Opens a work order on one of your units. Marking it out of service blocks the unit from sale until it’s resolved.</p>
                        </div>
                        <button type="button" class="vy-modal-x" @click="open = false" aria-label="Close">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="vy-modal-body">
                        {{-- Unit — the org's OWN units only (tenant-scoped; the server re-validates) --}}
                        <div class="vy-field">
                            <label class="vy-field-label" for="vy-mt-unit">Unit</label>
                            @if (count($units) > 0)
                                <select id="vy-mt-unit" name="unit_id" class="vy-input" required>
                                    <option value="" disabled {{ old('unit_id') ? '' : 'selected' }}>Select a unit…</option>
                                    @foreach ($units as $u)
                                        <option value="{{ $u['id'] }}" @selected(old('unit_id') === $u['id'])>{{ $u['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('unit_id') <div class="vy-help" style="color: var(--vy-accent);">{{ $message }}</div> @enderror
                            @else
                                <div class="vy-help">No units yet — add a unit first.</div>
                            @endif
                        </div>

                        {{-- Title --}}
                        <div class="vy-field">
                            <label class="vy-field-label" for="vy-mt-name">Title</label>
                            <div class="vy-inwrap">
                                <span class="vy-in-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                                <input type="text" id="vy-mt-name" name="title" class="vy-input" placeholder="e.g. Leaking mixer tap"
                                       value="{{ old('title') }}" maxlength="160" required>
                            </div>
                            @error('title') <div class="vy-help" style="color: var(--vy-accent);">{{ $message }}</div> @enderror
                        </div>

                        {{-- Category + Priority — real enums --}}
                        <div class="vy-field" style="display: flex; gap: 12px;">
                            <div style="flex: 1;">
                                <label class="vy-field-label" for="vy-mt-cat">Category</label>
                                <select id="vy-mt-cat" name="category" class="vy-input">
                                    @foreach ($categories as $c)
                                        <option value="{{ $c['value'] }}" @selected(old('category', 'other') === $c['value'])>{{ $c['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label class="vy-field-label" for="vy-mt-prio">Priority</label>
                                <select id="vy-mt-prio" name="priority" class="vy-input">
                                    @foreach ($priorities as $p)
                                        <option value="{{ $p['value'] }}" @selected(old('priority', 'normal') === $p['value'])>{{ $p['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="vy-field">
                            <label class="vy-field-label" for="vy-mt-desc">Description <span class="vy-fl-note">· optional</span></label>
                            <textarea id="vy-mt-desc" name="description" class="vy-input" rows="3" maxlength="2000" placeholder="What’s wrong, access notes, etc.">{{ old('description') }}</textarea>
                        </div>

                        {{-- Takes out of service — the REAL availability toggle --}}
                        <div class="vy-field" style="margin-bottom: 4px;">
                            <label class="vy-unitpill" style="display: inline-flex;">
                                <input type="checkbox" name="takes_out_of_service" value="1" {{ old('takes_out_of_service') ? 'checked' : '' }}>
                                <span class="vy-ubox">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                </span>
                                Take the unit out of service (blocks it from sale until resolved)
                            </label>
                        </div>
                    </div>

                    <div class="vy-modal-foot">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="open = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                            Open ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>

        </div>
    </x-operator.shell>
</div>
