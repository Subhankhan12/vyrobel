{{-- Operator MAINTENANCE board (#89 exact-match) — the work-order list over the real MaintenanceTicket (the
     same model the Dashboard reads; undisturbed). Real KPIs + SLA bar + technicians + parts-on-order + the
     out-of-order hero with real revenue-on-hold. Every mutation is a guarded controller POST.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div x-data="{
        nwoOpen: false, nwoOoo: false,
        nwoPriority: 'normal', nwoCategory: @js($categories[0]['value'] ?? 'other'),
        assign: null, detail: {{ \Illuminate\Support\Js::from($detail) }},
        assignUrl(id) { return '{{ route('app.maintenance.assign', '__ID__') }}'.replace('__ID__', id); },
        selecting: false, sel: [],
        inSel(id) { return this.sel.includes(id); },
        toggleSel(id) { const i = this.sel.indexOf(id); i < 0 ? this.sel.push(id) : this.sel.splice(i, 1); },
        selAll(ids) { this.sel = (this.sel.length === ids.length) ? [] : [...ids]; },
        endSelect() { this.selecting = false; this.sel = []; },
        bulkSubmit(formId) { if (this.sel.length) document.getElementById(formId).requestSubmit(); },
     }">
    <x-operator.shell active="maintenance" title="Maintenance" subtitle="Work orders across your units" :viewer="$viewer">

        @if (session('mt_notice'))
            <div class="vy-flash is-ok vy-fade">{{ session('mt_notice') }}</div>
        @endif
        @if (session('mt_error'))
            <div class="vy-flash is-err vy-fade">{{ session('mt_error') }}</div>
        @endif
        @if ($errors->any())
            <div class="vy-flash is-err vy-fade">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div>
        @endif

        {{-- Control row --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left" style="flex: 1; display: flex; gap: 8px; align-items: center;">
                <form method="GET" action="{{ route('app.maintenance') }}" class="vy-input-group" style="max-width: 300px;">
                    <input class="vy-input" type="search" name="q" value="{{ $query }}" placeholder="Search work orders…" aria-label="Search work orders">
                    @if ($filter) <input type="hidden" name="filter" value="{{ $filter }}"> @endif
                </form>
                {{-- Status filter DROPDOWN — a finer status filter that shares the SAME ?filter= param + the ONE
                     matches() rule the KPI chips use (no drift; a blank value clears the filter). --}}
                @php $statusOpts = ['' => 'All statuses', 'active' => 'All open', 'open' => 'Open — not started', 'inprog' => 'In progress', 'urgent' => 'Urgent', 'parts' => 'Awaiting parts', 'ooo' => 'Out of order', 'done' => 'Resolved · 7d']; @endphp
                <form method="GET" action="{{ route('app.maintenance') }}">
                    @if ($query !== '') <input type="hidden" name="q" value="{{ $query }}"> @endif
                    <select name="filter" class="vy-input" onchange="this.form.submit()" aria-label="Filter by status" style="min-width: 160px;">
                        @foreach ($statusOpts as $val => $label)
                            <option value="{{ $val }}" @selected(($filter ?? '') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="vy-right" style="display: flex; gap: 8px;">
                <a class="vy-btn vy-btn--ghost" href="{{ route('app.maintenance.costs') }}">Costs report</a>
                <a class="vy-btn vy-btn--ghost" href="{{ route('app.maintenance.settings') }}">Settings</a>
                @if ($canManage && count($rows) > 0)
                    <button type="button" class="vy-btn vy-btn--ghost" @click="selecting ? endSelect() : selecting = true" x-text="selecting ? 'Done' : 'Select'">Select</button>
                @endif
                @if ($canManage)
                    <button type="button" class="vy-btn vy-btn--accent" @click="nwoOpen = true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        New work order
                    </button>
                @endif
            </div>
        </div>

        {{-- GATE 6.2 — BULK toolbar (select mode). Each action posts the SELECTED ids to a bulk route that
             loops the certified MaintenanceService; the count-gated buttons are inert with 0 selected. --}}
        @if ($canManage)
            @php $rowIds = collect($rows)->pluck('id')->values()->all(); @endphp
            <div x-show="selecting" x-cloak class="vy-card vy-fade" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; margin-bottom: 14px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;">
                    <input type="checkbox" @change="selAll(@js($rowIds))" :checked="sel.length > 0 && sel.length === {{ count($rows) }}">
                    Select all
                </label>
                <span style="font-size: 13px; font-weight: 600; color: var(--vy-ink);" x-text="sel.length + ' selected'"></span>
                <div style="flex: 1;"></div>
                <button type="button" class="vy-btn vy-btn--ghost" :style="sel.length ? '' : 'opacity:.45; pointer-events:none;'"
                        @click="bulkSubmit('bulkExportForm')" x-text="sel.length ? ('Export ' + sel.length) : 'Export'">Export</button>
                <button type="button" class="vy-btn vy-btn--ghost" :style="sel.length ? '' : 'opacity:.45; pointer-events:none;'"
                        @click="assign = { bulk: true, title: '' }" x-text="sel.length ? ('Assign ' + sel.length) : 'Assign'">Assign</button>
                <button type="button" class="vy-btn vy-btn--accent" :style="sel.length ? '' : 'opacity:.45; pointer-events:none;'"
                        @click="if (sel.length && confirm('Resolve ' + sel.length + ' work order(s)? Each returns its unit to sale.')) bulkSubmit('bulkResolveForm')"
                        x-text="sel.length ? ('Resolve ' + sel.length) : 'Resolve'">Resolve</button>
                <button type="button" class="vy-btn vy-btn--ghost" @click="endSelect()">Cancel</button>
            </div>
        @endif

        {{-- KPI filter chips --}}
        @php
            $chips = [
                ['k' => 'active', 'label' => 'Open', 'v' => $kpis['open'], 'dot' => '#8A8079'],
                ['k' => 'urgent', 'label' => 'Urgent', 'v' => $kpis['urgent'], 'dot' => '#E8703A'],
                ['k' => 'parts', 'label' => 'Awaiting parts', 'v' => $kpis['parts'], 'dot' => '#33A0AC'],
                ['k' => 'ooo', 'label' => 'Out of order', 'v' => $kpis['ooo'], 'dot' => '#B4400F'],
                ['k' => 'done', 'label' => 'Resolved · 7d', 'v' => $kpis['done'], 'dot' => '#43A06B'],
            ];
        @endphp
        <div class="vy-dash-kpis vy-dash-kpis--5 vy-fade" style="margin-bottom: 18px;">
            @foreach ($chips as $chip)
                <a class="vy-dash-kpi @if ($filter === $chip['k']) is-feature @endif" style="text-decoration: none;"
                   href="{{ route('app.maintenance', array_filter([($filter === $chip['k'] ? null : 'filter') => $chip['k'], 'q' => $query])) }}">
                    <div class="vy-dk-label"><span style="display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: {{ $chip['dot'] }}; margin-right: 5px;"></span>{{ $chip['label'] }}</div>
                    <div class="vy-dk-val">{{ $chip['v'] }}</div>
                </a>
            @endforeach
        </div>

        <div class="vy-detail-cols" style="align-items: start;">
            {{-- ── Work-orders list ───────────────────────────────────────────────────────────────── --}}
            <div class="vy-stack">
                <div class="vy-card vy-card--flush vy-fade">
                    <div class="vy-detail-head" style="padding: 14px 16px 4px;">
                        <h2 class="vy-card-title">Work orders</h2>
                        @if ($filter) <a class="vy-eyebrow vy-arr-link" href="{{ route('app.maintenance') }}">Clear filter</a> @endif
                    </div>
                    @forelse ($rows as $row)
                        <div class="vy-unit-row" style="cursor: pointer; align-items: flex-start;" :class="selecting && inSel('{{ $row['id'] }}') ? 'is-sel' : ''"
                             @click="selecting ? toggleSel('{{ $row['id'] }}') : (detail = {{ \Illuminate\Support\Js::from($row) }})">
                            <div x-show="selecting" x-cloak style="flex: none; padding-top: 3px;">
                                <input type="checkbox" class="vy-mt-check" @click.stop.prevent="toggleSel('{{ $row['id'] }}')" :checked="inSel('{{ $row['id'] }}')" aria-label="Select {{ $row['title'] }}">
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="width: 8px; height: 8px; border-radius: 2px; background: var(--vy-accent); flex: none;"></span>
                                    <span class="vy-um-name">{{ $row['title'] }}</span>
                                    @if ($row['oos']) <span class="vy-tag tone-risk"><span class="vy-dot"></span>Out of order</span> @endif
                                    @if ($row['awaiting_parts']) <span class="vy-tag tone-tag"><span class="vy-dot"></span>Awaiting parts</span> @endif
                                </div>
                                <div class="vy-um-sub">{{ $row['unit'] }} · {{ $row['category'] }} · {{ $row['reported_ago'] }}</div>
                                @if ($row['sla']['label'] !== '' && $row['status'] !== 'resolved')
                                    <div style="font-size: 11px; margin-top: 3px; color: {{ $row['sla']['breached'] ? 'var(--vy-accent-text)' : ($row['sla']['due_soon'] ? 'var(--vy-amber-text)' : 'var(--vy-muted)') }};">
                                        {{ $row['sla']['breached'] ? '⚠ ' : '' }}{{ $row['sla']['label'] }}
                                    </div>
                                @endif
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px; flex: none;">
                                <span class="vy-tag tone-{{ $row['priority_tone'] }}">{{ $row['priority_label'] }}</span>
                                @if ($row['assignee'])
                                    <span class="vy-avatar" style="width: 26px; height: 26px; font-size: 10px;" title="{{ $row['assignee'] }}">{{ $row['assignee_initials'] }}</span>
                                @elseif ($canManage)
                                    <button type="button" class="vy-btn vy-btn--ghost" style="padding: 3px 10px; font-size: 12px;" @click.stop="assign = {{ \Illuminate\Support\Js::from(['id' => $row['id'], 'title' => $row['title']]) }}">Assign</button>
                                @endif
                                <span class="vy-tag tone-{{ $row['status_meta']['tone'] }}" style="min-width: 92px;"><span class="vy-dot"></span>{{ $row['status_meta']['label'] }}</span>
                            </div>
                        </div>
                    @empty
                        {{-- The design's empty state names a way OUT, not just the absence. Two cases, kept
                             distinct: a filter/search that hides real rows (offer the recovery the design
                             spells out) versus a genuinely empty board (say so plainly — never imply a filter
                             is hiding work orders that do not exist). --}}
                        <div class="vy-empty" style="margin: 12px;">
                            @if ($query !== '' || $filter !== null)
                                <strong style="display: block; font-size: 14px; color: var(--vy-ink); margin-bottom: 5px;">No work orders match</strong>
                                Try a different status filter, or search by unit, issue, or technician.
                            @else
                                <strong style="display: block; font-size: 14px; color: var(--vy-ink); margin-bottom: 5px;">No work orders yet</strong>
                                Nothing needs fixing right now — raise one with “New work order”.
                            @endif
                        </div>
                    @endforelse
                    <div class="vy-fl-note" style="padding: 12px 16px; border-top: 1px solid var(--vy-hairline);">Showing {{ count($rows) }} {{ \Illuminate\Support\Str::plural('order', count($rows)) }} from the last 7 days · older history lives in the costs report.</div>
                </div>
            </div>

            {{-- ── Right rail ─────────────────────────────────────────────────────────────────────── --}}
            <div class="vy-stack">
                {{-- Out-of-order hero --}}
                <div class="vy-tonight" style="padding: 20px;">
                    <div class="vy-dk-label" style="color: rgba(255,255,255,.7);">Out of order</div>
                    <div style="font-family: 'Sora', sans-serif; font-weight: 700; color: #fff; font-size: 30px;">{{ $oosHero['count'] }}<span style="font-size: 15px; font-weight: 600; color: rgba(255,255,255,.7);"> units blocked</span></div>
                    <div style="font-size: 12.5px; color: rgba(255,255,255,.7); margin-top: 4px;">{{ $oosHero['units'] !== '' ? $oosHero['units'] : 'No units blocked — all clear' }}</div>
                    @if ($oosHero['on_hold'] !== null)
                        <div style="border-top: 1px solid rgba(255,255,255,.14); margin-top: 14px; padding-top: 12px;">
                            <span style="color: #F6C6B4; font-weight: 700;">{{ $oosHero['on_hold'] }} / night</span>
                            <div style="font-size: 11.5px; color: rgba(255,255,255,.6);">revenue on hold</div>
                        </div>
                    @endif
                </div>

                {{-- Technicians --}}
                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 10px;">Technicians</h2>
                    @forelse ($technicians as $tech)
                        <div class="vy-unit-row" style="padding: 8px 0;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="vy-avatar" style="width: 28px; height: 28px; font-size: 11px;">{{ $tech['initials'] }}</span>
                                <div>
                                    <div style="font-size: 13px; color: var(--vy-ink);">{{ $tech['name'] }}</div>
                                    <div style="font-size: 11px; color: var(--vy-muted);">{{ $tech['load'] }} open @if ($tech['urgent'] > 0) · {{ $tech['urgent'] }} urgent @endif</div>
                                </div>
                            </div>
                            <span class="vy-tag tone-{{ $tech['status'] === 'busy' ? 'risk' : 'in-house' }}"><span class="vy-dot"></span>{{ ucfirst($tech['status']) }}</span>
                        </div>
                    @empty
                        <div class="vy-empty" style="padding: 12px;">No technicians have open work right now.</div>
                    @endforelse
                </div>

                {{-- Parts on order --}}
                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 10px;">Parts on order</h2>
                    @forelse ($partsOnOrder as $p)
                        <div class="vy-unit-row" style="padding: 8px 0;">
                            <div>
                                <div style="font-size: 13px; color: var(--vy-ink);">{{ $p['part_name'] ?? 'Part' }}</div>
                                <div style="font-size: 11px; color: var(--vy-muted);">{{ $p['unit'] }}@if ($p['part_eta']) · arrives {{ $p['part_eta'] }} @endif</div>
                            </div>
                            <span class="vy-tag tone-tag"><span class="vy-dot"></span>On order</span>
                        </div>
                    @empty
                        <div class="vy-empty" style="padding: 12px;">Nothing on order.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── NEW WORK ORDER modal ───────────────────────────────────────────────────────────────── --}}
        @if ($canManage)
            <div x-show="nwoOpen" x-cloak @keydown.escape.window="nwoOpen = false"
                 style="position: fixed; inset: 0; z-index: 50; background: rgba(43,35,32,.4); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 20px;">
                <div @click.outside="nwoOpen = false" class="vy-card vy-fade" style="width: 100%; max-width: 480px; max-height: 90vh; overflow: auto;">
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">New work order</h2>
                    <form method="POST" action="{{ route('app.maintenance.store') }}">
                        @csrf
                        <label class="vy-field-label" for="nwo_title">Issue</label>
                        <input class="vy-input" id="nwo_title" type="text" name="title" maxlength="160" required placeholder="e.g. AC not cooling" style="width: 100%; margin-bottom: 10px;">
                        <label class="vy-field-label" for="nwo_unit">Unit</label>
                        <select class="vy-input" id="nwo_unit" name="unit_id" required style="width: 100%; margin-bottom: 12px;">
                            @foreach ($units as $u) <option value="{{ $u['id'] }}">{{ $u['label'] }}</option> @endforeach
                        </select>

                        {{-- Priority — a SEGMENTED control (was a <select>); the pick feeds the POST via a hidden input. --}}
                        <label class="vy-field-label">Priority</label>
                        <input type="hidden" name="priority" :value="nwoPriority">
                        <div class="vy-seg" style="margin-bottom: 12px;">
                            @foreach ($priorities as $p)
                                <button type="button" class="vy-seg-opt" :class="nwoPriority === '{{ $p['value'] }}' ? 'is-checked' : ''" @click="nwoPriority = '{{ $p['value'] }}'">{{ $p['label'] }}</button>
                            @endforeach
                        </div>

                        {{-- Category — chip buttons (was a <select>); the pick feeds the POST via a hidden input. --}}
                        <label class="vy-field-label">Category</label>
                        <input type="hidden" name="category" :value="nwoCategory">
                        <div class="vy-newres-sources" style="margin-bottom: 4px;">
                            @foreach ($categories as $c)
                                <button type="button" class="vy-source-pill" :class="nwoCategory === '{{ $c['value'] }}' ? 'on' : ''" @click="nwoCategory = '{{ $c['value'] }}'">{{ $c['label'] }}</button>
                            @endforeach
                        </div>
                        <label class="vy-field-label" for="nwo_desc" style="margin-top: 10px;">Description</label>
                        <textarea class="vy-input" id="nwo_desc" name="description" rows="3" maxlength="2000" placeholder="What’s wrong, where exactly, anything the tech should know…" style="width: 100%; resize: vertical;"></textarea>
                        <p style="font-size: 11px; color: var(--vy-faint); margin: 6px 0 0;">Photos can be added from the work order once it’s created.</p>

                        <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0; margin-top: 12px;">
                            <input type="checkbox" name="takes_out_of_service" value="1" x-model="nwoOoo"> Mark room out of order
                            <span style="color: var(--vy-faint); font-weight: 500;">· blocks the unit from selling until resolved</span>
                        </label>
                        <p x-show="nwoOoo" x-cloak style="font-size: 11.5px; color: #B4400F; margin: 6px 0 0;">Any nights already booked on this unit will need moving — check the rack first.</p>

                        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="nwoOpen = false">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent"><span x-text="nwoOoo ? 'Create & block room' : 'Create work order'">Create work order</span></button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── ASSIGN modal ───────────────────────────────────────────────────────────────────── --}}
            <div x-show="assign" x-cloak @keydown.escape.window="assign = null"
                 style="position: fixed; inset: 0; z-index: 50; background: rgba(43,35,32,.4); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 20px;">
                <div @click.outside="assign = null" class="vy-card vy-fade" style="width: 100%; max-width: 420px;" x-show="assign">
                    <h2 class="vy-card-title" style="margin-bottom: 2px;">Assign technician</h2>
                    <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;" x-text="assign ? (assign.bulk ? (sel.length + ' work order(s) selected') : assign.title) : ''"></p>
                    {{-- Shared modal: BULK posts the selected ids to bulk.assign; single posts to that ticket's route. --}}
                    <form method="POST" :action="assign && assign.bulk ? '{{ route('app.maintenance.bulk.assign') }}' : (assign ? assignUrl(assign.id) : '')">
                        @csrf
                        <template x-if="assign && assign.bulk">
                            <template x-for="id in sel" :key="id"><input type="hidden" name="ticket_ids[]" :value="id"></template>
                        </template>
                        <div style="display: flex; flex-direction: column; gap: 6px; max-height: 320px; overflow: auto;">
                            @foreach ($staff as $i => $member)
                                <label style="display: flex; align-items: center; gap: 8px; padding: 9px 12px; border: 1px solid var(--vy-hairline); border-radius: 8px; cursor: pointer;">
                                    <input type="radio" name="user_id" value="{{ $member['id'] }}" @if ($i === 0) checked @endif>
                                    {{ $member['name'] }}
                                </label>
                            @endforeach
                        </div>
                        <p style="font-size: 11px; color: var(--vy-faint); margin: 8px 0 0;">The work order appears in their task list. (Device push isn’t wired — no fabricated notification.)</p>
                        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="assign = null">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent">Assign</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Hidden BULK forms — the ids are bound from the reactive `sel` (flushed before a later button click). --}}
            <form id="bulkResolveForm" method="POST" action="{{ route('app.maintenance.bulk.resolve') }}" class="vy-newres-hidden">
                @csrf
                <template x-for="id in sel" :key="id"><input type="hidden" name="ticket_ids[]" :value="id"></template>
            </form>
            <form id="bulkExportForm" method="POST" action="{{ route('app.maintenance.bulk.export') }}" class="vy-newres-hidden">
                @csrf
                <template x-for="id in sel" :key="id"><input type="hidden" name="ticket_ids[]" :value="id"></template>
            </form>
        @endif

        {{-- ── DETAIL drawer ──────────────────────────────────────────────────────────────────────── --}}
        <div x-show="detail" x-cloak @keydown.escape.window="detail = null"
             style="position: fixed; inset: 0; z-index: 50; background: rgba(43,35,32,.4); backdrop-filter: blur(3px); display: flex; align-items: stretch; justify-content: flex-end;">
            <div @click.outside="detail = null" class="vy-card vy-fade" style="width: 100%; max-width: 440px; height: 100%; border-radius: 0; overflow: auto;" x-show="detail">
                <template x-if="detail">
                    <div>
                        <div class="vy-detail-head" style="margin-bottom: 12px;">
                            <div>
                                <div class="vy-detail-ref" x-text="detail.title"></div>
                                <div class="vy-u-sub"><span x-text="detail.unit"></span> · <span x-text="detail.category"></span></div>
                            </div>
                            <button type="button" class="vy-btn vy-btn--ghost" style="padding: 4px 10px;" @click="detail = null">✕</button>
                        </div>

                        {{-- Pipeline --}}
                        <div style="display: flex; align-items: center; gap: 4px; margin-bottom: 14px;">
                            <template x-for="(step, i) in ['Logged','Assigned','In progress','Resolved']" :key="step">
                                <div style="flex: 1; text-align: center;">
                                    <div :style="'height:4px;border-radius:2px;background:' + (
                                        (i===0) || (i===1 && detail.assignee_id) || (i===2 && (detail.status==='in_progress'||detail.status==='resolved')) || (i===3 && detail.status==='resolved')
                                        ? '#C2410C' : 'var(--vy-hairline)')"></div>
                                    <div style="font-size: 10px; color: var(--vy-muted); margin-top: 4px;" x-text="step"></div>
                                </div>
                            </template>
                        </div>

                        {{-- SLA + badges --}}
                        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px;">
                            <span class="vy-tag" :class="'tone-' + detail.priority_tone" x-text="detail.priority_label + ' priority'"></span>
                            <template x-if="detail.oos"><span class="vy-tag tone-risk"><span class="vy-dot"></span>Out of order</span></template>
                            <template x-if="detail.sla.label && detail.status !== 'resolved'"><span class="vy-tag" :class="detail.sla.breached ? 'tone-risk' : 'tone-tag'" x-text="detail.sla.label"></span></template>
                        </div>

                        {{-- Facts --}}
                        <div class="vy-facts" style="margin-bottom: 12px;">
                            <div class="vy-fact-label">Assigned</div>
                            <div class="vy-fact-val"><span x-text="detail.assignee || 'Unassigned'"></span></div>
                            <div class="vy-fact-label">Reported</div>
                            <div class="vy-fact-val"><span x-text="detail.reported_ago"></span></div>
                            <template x-if="detail.cost"><div class="vy-fact-label">Cost</div></template>
                            <template x-if="detail.cost"><div class="vy-fact-val"><span x-text="detail.cost"></span> <span style="color: var(--vy-faint); font-size: 11px;" x-text="detail.labor ? '(' + detail.labor + ' labor · ' + detail.parts_cost + ' parts)' : ''"></span></div></template>
                        </div>
                        <template x-if="detail.description">
                            <p style="font-size: 13px; color: var(--vy-ink); margin: 0 0 12px; white-space: pre-line;" x-text="detail.description"></p>
                        </template>

                        {{-- Parts --}}
                        <template x-if="detail.awaiting_parts">
                            <div class="vy-recon" style="margin: 0 0 12px;"><span x-text="'Awaiting: ' + (detail.part_name || 'a part')"></span><span x-text="detail.part_eta ? ' · arrives ' + detail.part_eta : ''"></span></div>
                        </template>

                        {{-- Photos --}}
                        <h3 style="font-size: 13px; font-weight: 700; color: var(--vy-ink); margin: 6px 0 8px;">Photos</h3>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                            <template x-for="photo in detail.photos" :key="photo.id">
                                <img :src="photo.url" alt="Maintenance photo" style="width: 72px; height: 72px; object-fit: cover; border-radius: 8px; border: 1px solid var(--vy-hairline);">
                            </template>
                            <template x-if="detail.photos.length === 0"><span class="vy-muted" style="font-size: 12px;">No photos yet.</span></template>
                        </div>

                        {{-- Activity --}}
                        <template x-if="detail.activity && detail.activity.length">
                            <div style="margin-bottom: 12px;">
                                <h3 style="font-size: 13px; font-weight: 700; color: var(--vy-ink); margin: 6px 0 8px;">Activity</h3>
                                <template x-for="a in detail.activity" :key="a.label + a.when">
                                    <div class="vy-um-sub" style="margin-bottom: 3px;"><span x-text="a.label"></span> <span class="vy-sep">·</span> <span x-text="a.when"></span></div>
                                </template>
                            </div>
                        </template>

                        @if ($canManage)
                            <div style="display: flex; flex-direction: column; gap: 8px; border-top: 1px solid var(--vy-hairline); padding-top: 14px;">
                                {{-- Photo upload --}}
                                <form method="POST" :action="'{{ route('app.maintenance.photos.store', '__ID__') }}'.replace('__ID__', detail.id)" enctype="multipart/form-data">
                                    @csrf
                                    <label class="vy-btn vy-btn--ghost" style="cursor: pointer; display: inline-flex;">Add photo<input type="file" name="photo" accept="image/*" capture="environment" style="display: none;" onchange="this.form.submit()"></label>
                                </form>
                                {{-- Start --}}
                                <template x-if="detail.status === 'open'">
                                    <form method="POST" :action="'{{ route('app.maintenance.start', '__ID__') }}'.replace('__ID__', detail.id)"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="vy-btn vy-btn--accent" style="width: 100%;">Start work</button></form>
                                </template>
                                {{-- Resolve (with labor/parts) --}}
                                <template x-if="detail.status === 'open' || detail.status === 'in_progress'">
                                    <form method="POST" :action="'{{ route('app.maintenance.resolve', '__ID__') }}'.replace('__ID__', detail.id)">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px;">
                                            <div><label class="vy-field-label" style="font-size: 10px;">Labor</label><input class="vy-input" type="number" step="0.01" min="0" name="labor" placeholder="0.00" style="width: 100%;"></div>
                                            <div><label class="vy-field-label" style="font-size: 10px;">Parts</label><input class="vy-input" type="number" step="0.01" min="0" name="parts" placeholder="0.00" style="width: 100%;"></div>
                                        </div>
                                        <button type="submit" class="vy-btn vy-btn--accent" style="width: 100%;">Mark resolved</button>
                                    </form>
                                </template>
                                <a class="vy-btn vy-btn--ghost" style="text-align: center;" :href="'{{ route('app.maintenance.show', '__ID__') }}'.replace('__ID__', detail.id)">Open full record</a>
                            </div>
                        @endif
                    </div>
                </template>
            </div>
        </div>

    </x-operator.shell>
</div>

@once
    <style>[x-cloak] { display: none !important; } .vy-dash-kpis--5 { grid-template-columns: repeat(5, minmax(0, 1fr)); } @media (max-width: 900px) { .vy-dash-kpis--5 { grid-template-columns: repeat(2, 1fr); } }</style>
@endonce
