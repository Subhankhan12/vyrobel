{{-- Operator HOUSEKEEPING (#88 exact-match) — the operator MANAGING the real HousekeepingTask (the owner sees
     the same model read-only). Board + List views, crew panel with real load, assign / assign-all (real writes),
     room-detail MODAL with real proof photos + elapsed. Every mutation is a guarded controller POST.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div x-data="{
        view: localStorage.getItem('vy:hk:view') || 'list',
        density: localStorage.getItem('vy:hk:density') || 'cozy',
        detail: null, assign: null, assignAllOpen: false,
        setView(v) { this.view = v; localStorage.setItem('vy:hk:view', v); },
        setDensity(d) { this.density = d; localStorage.setItem('vy:hk:density', d); },
        assignUrl(id) { return '{{ route('app.housekeeping.assign', '__ID__') }}'.replace('__ID__', id); },
        statusUrl(id) { return '{{ route('app.housekeeping.status', '__ID__') }}'.replace('__ID__', id); },
        photoUrl(id) { return '{{ route('app.housekeeping.photos.store', '__ID__') }}'.replace('__ID__', id); },
        {{-- GATE 8.1 — drag-lane-move. FORWARD-ONLY through the real status enum (a backward drop is a no-op:
             the enum is a forward workflow, and updateStatus only accepts in_progress/clean/inspected). The
             drop POSTs the SAME certified, gated status route the buttons use — no new backend, no date math. --}}
        csrf: '{{ csrf_token() }}', hkOrder: ['pending', 'in_progress', 'clean', 'inspected'],
        dragging: false, dragTask: '', dragIdx: -1,
        hkStart(e, id, status) { this.dragTask = id; this.dragIdx = this.hkOrder.indexOf(status); this.dragging = true; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', id); },
        hkEnd() { this.dragging = false; this.dragTask = ''; this.dragIdx = -1; },
        // Only the IMMEDIATE next lane is a valid target — the drag is a 1:1 stand-in for the forward button
        // (never a multi-step skip that would leave started_at unset). Requires an ACTIVE in-page card drag.
        hkForward(laneKey) { return this.dragging && this.hkOrder.indexOf(laneKey) === this.dragIdx + 1; },
        hkDrop(laneKey) {
            const task = this.dragTask;   // capture BEFORE hkEnd() clears it
            const ok = this.dragging && task && this.hkOrder.indexOf(laneKey) === this.dragIdx + 1;
            this.hkEnd();
            if (! ok) return;   // aborted/stale drag, non-adjacent, or an external source → no-op
            const f = document.createElement('form');
            f.method = 'POST'; f.action = this.statusUrl(task);
            const add = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
            add('_token', this.csrf); add('status', laneKey);
            document.body.appendChild(f); f.submit();
        },
        {{-- GATE 8.3 — collapsible lanes. Per-lane collapse state, persisted; the "done" lanes (clean/
             inspected) default collapsed so the eye lands on what still needs work. A collapsed lane keeps its
             header (and stays a drop target for the drag). --}}
        collapsed: (() => { try { return JSON.parse(localStorage.getItem('vy:hk:collapsed')) || { clean: true, inspected: true }; } catch (e) { return { clean: true, inspected: true }; } })(),
        toggleLane(k) { this.collapsed = { ...this.collapsed, [k]: ! this.collapsed[k] }; localStorage.setItem('vy:hk:collapsed', JSON.stringify(this.collapsed)); },
        laneOpen(k) { return ! this.collapsed[k]; },
     }">
    <x-operator.shell active="housekeeping" title="Housekeeping" subtitle="Today’s cleaning across your rooms" :viewer="$viewer">

        {{-- GATE 8.4 — the assign / assign-all toast with a REAL Undo (a POST to the gated unassign route —
             a genuine server-side revert, never a fabricated client-only "undo"). --}}
        @if (session('hk_notice'))
            @php $hkUndo = session('hk_undo'); @endphp
            <div class="vy-flash is-ok vy-fade" style="display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                <span>{{ session('hk_notice') }}</span>
                @if (is_array($hkUndo) && ! empty($hkUndo['url']))
                    <form method="POST" action="{{ $hkUndo['url'] }}" style="margin: 0; flex: none;">
                        @csrf
                        @foreach ($hkUndo['ids'] ?? [] as $undoId)<input type="hidden" name="task_ids[]" value="{{ $undoId }}">@endforeach
                        @if (! empty($hkUndo['restore_priority']))<input type="hidden" name="restore_priority" value="{{ $hkUndo['restore_priority'] }}">@endif
                        <button type="submit" class="vy-btn vy-btn--ghost" style="padding: 3px 14px; font-size: 12px;">Undo</button>
                    </form>
                @endif
            </div>
        @endif
        @if (session('hk_error'))
            <div class="vy-flash is-err vy-fade">{{ session('hk_error') }}</div>
        @endif

        {{-- ── Control row — Board/List toggle · density · Assign all ─────────────────────────────── --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div class="vy-seg">
                    <button type="button" class="vy-seg-opt" :class="view === 'board' && 'is-checked'" @click="setView('board')">Board</button>
                    <button type="button" class="vy-seg-opt" :class="view === 'list' && 'is-checked'" @click="setView('list')">List</button>
                </div>
                <div class="vy-seg" x-show="view === 'board'" x-cloak>
                    <button type="button" class="vy-seg-opt" :class="density === 'cozy' && 'is-checked'" @click="setDensity('cozy')">Cozy</button>
                    <button type="button" class="vy-seg-opt" :class="density === 'compact' && 'is-checked'" @click="setDensity('compact')">Compact</button>
                </div>
                <form method="GET" action="{{ route('app.housekeeping') }}" class="vy-input-group" style="max-width: 260px;">
                    <input class="vy-input" type="search" name="q" value="{{ $query }}" placeholder="Search rooms…" aria-label="Search rooms">
                    @if ($crewFilter) <input type="hidden" name="crew" value="{{ $crewFilter }}"> @endif
                </form>
            </div>
            <div class="vy-right">
                @if ($canManage ?? $canAssign)
                    @if ($unassignedCount > 0)
                        <button type="button" class="vy-btn vy-btn--accent" @click="assignAllOpen = true">Assign all · {{ $unassignedCount }}</button>
                    @else
                        <span class="vy-btn vy-btn--ghost" style="opacity: .55; cursor: default;">All assigned</span>
                    @endif
                @endif
            </div>
        </div>

        {{-- ── Status summary strip (5 real tallies) ──────────────────────────────────────────────── --}}
        <div class="vy-seg vy-fade" style="margin: 4px 0 18px; flex-wrap: wrap; gap: 6px; background: none; padding: 0;">
            {{-- The app's real status vocabulary (Pending = the wireframe's "Dirty"; kept consistent with the
                 lanes + the existing view test, a documented divergence). --}}
            @php
                $chips = [
                    ['k' => 'pending', 'label' => 'Pending', 'dot' => '#E8703A'],
                    ['k' => 'in_progress', 'label' => 'In progress', 'dot' => '#E0A63A'],
                    ['k' => 'clean', 'label' => 'Clean', 'dot' => '#33A0AC'],
                    ['k' => 'inspected', 'label' => 'Inspected', 'dot' => '#43A06B'],
                    ['k' => 'out_of_order', 'label' => 'Out of order', 'dot' => '#8A8079'],
                ];
            @endphp
            @foreach ($chips as $chip)
                <span class="vy-card" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $chip['dot'] }};"></span>
                    <span style="font-size: 12.5px; color: var(--vy-muted);">{{ $chip['label'] }}</span>
                    <span style="font-family: 'Sora', sans-serif; font-weight: 700; color: var(--vy-ink);">{{ $tallies[$chip['k']] ?? 0 }}</span>
                </span>
            @endforeach
        </div>

        {{-- ── Crew filter row (real housekeepers + Unassigned) ───────────────────────────────────── --}}
        <div class="vy-seg vy-fade" style="margin-bottom: 16px; flex-wrap: wrap;">
            <a class="vy-seg-opt @if ($crewFilter === null) is-checked @endif" href="{{ route('app.housekeeping', array_filter(['q' => $query])) }}">All housekeepers</a>
            @foreach ($crew as $member)
                <a class="vy-seg-opt @if ($member['active']) is-checked @endif" href="{{ route('app.housekeeping', array_filter(['crew' => $member['id'], 'q' => $query])) }}">
                    {{ $member['name'] }} <span class="vy-muted">{{ $member['load'] }}</span>
                </a>
            @endforeach
            <a class="vy-seg-opt @if ($crewFilter === 'unassigned') is-checked @endif" href="{{ route('app.housekeeping', array_filter(['crew' => 'unassigned', 'q' => $query])) }}">Unassigned</a>
        </div>

        {{-- ═══ BOARD VIEW ═══ --}}
        <div x-show="view === 'board'" x-cloak>
            {{-- Crew panel --}}
            <div class="vy-card vy-fade" style="margin-bottom: 16px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <span class="vy-eyebrow">Crew</span>
                @forelse ($crew as $member)
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="vy-avatar" style="width: 30px; height: 30px; font-size: 12px;">{{ $member['initials'] }}</span>
                        <div>
                            <div style="font-size: 13px; font-weight: 600; color: var(--vy-ink);">{{ $member['name'] }}</div>
                            <div style="font-size: 11px; color: var(--vy-muted);">{{ $member['load'] }} {{ \Illuminate\Support\Str::plural('room', $member['load']) }} open</div>
                        </div>
                    </div>
                @empty
                    <span class="vy-muted" style="font-size: 12.5px;">No housekeepers on the roster yet — add staff with the Housekeeping role.</span>
                @endforelse
                <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
                    <span class="vy-tag tone-tag"><span class="vy-dot"></span>Out of order · {{ $outOfOrder }}</span>
                    <a class="vy-arr-link" href="{{ route('app.schedule') }}">Schedule →</a>
                </div>
            </div>

            {{-- Lanes --}}
            <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; align-items: start;">
                @foreach ($columns as $column)
                    {{-- A lane is a forward-only drop target while a card is in flight (a card is dropped here to
                         advance its status). Backward lanes never highlight and reject the drop. --}}
                    <div class="vy-card vy-fade vy-hk-lane" style="padding: 12px;"
                         :class="(hkForward('{{ $column['key'] }}') ? 'is-drop ' : '') + (laneOpen('{{ $column['key'] }}') ? '' : 'is-collapsed')"
                         @dragover.prevent @drop.prevent="hkDrop('{{ $column['key'] }}')">
                        {{-- The lane header toggles collapse (a collapsed lane stays a drop target). --}}
                        <div class="vy-detail-head" style="margin-bottom: 10px; cursor: pointer; user-select: none;" @click="toggleLane('{{ $column['key'] }}')">
                            <span style="font-size: 13px; font-weight: 700; color: var(--vy-ink);">
                                <span class="vy-tag tone-{{ $column['tone'] }}" style="padding: 2px 8px;"><span class="vy-dot"></span>{{ $column['label'] }}</span>
                            </span>
                            <span style="display: inline-flex; align-items: center; gap: 7px;">
                                <span class="vy-muted" style="font-size: 12px;">{{ count($column['tasks']) }}</span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="vy-muted" style="transition: transform .15s;" :style="laneOpen('{{ $column['key'] }}') ? 'transform: rotate(180deg)' : ''"><path d="M6 9l6 6 6-6"/></svg>
                            </span>
                        </div>
                        <div x-show="laneOpen('{{ $column['key'] }}')">
                            @forelse ($column['tasks'] as $card)
                                @include('livewire.partials.hk-card', ['card' => $card])
                            @empty
                                <div class="vy-empty" style="padding: 14px; font-size: 12px;">Empty</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ═══ LIST VIEW ═══ --}}
        <div x-show="view === 'list'" x-cloak>
            <div class="vy-card vy-card--flush vy-fade">
                <div class="vy-utable-wrap">
                    <table class="vy-utable">
                        <thead><tr><th>Room</th><th>Housekeeper</th><th>Stage</th><th>Ready</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($rows as $card)
                                <tr>
                                    <td>
                                        <button type="button" class="vy-u-code vy-arr-link" style="border: none; background: none; cursor: pointer;" @click="detail = {{ \Illuminate\Support\Js::from($card) }}">{{ $card['unit'] }}</button>
                                        <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap; margin-top: 3px;">
                                            @if ($card['turnover']) <span class="vy-tag tone-vip"><span class="vy-dot"></span>Turnover</span> @endif
                                            @if ($card['urgency']) <span class="vy-tag tone-{{ $card['urgency']['tone'] === 'red' ? 'risk' : 'confirmed' }}"><span class="vy-dot"></span>{{ $card['urgency']['label'] }}</span> @endif
                                            @if ($card['guest_vip']) <span class="vy-tag tone-vip"><span class="vy-dot"></span>VIP</span> @endif
                                        </div>
                                        <div class="vy-u-sub">{{ $card['type'] }}@foreach ($card['guest_preferences'] as $p) · {{ $p }}@endforeach</div>
                                    </td>
                                    <td>
                                        @if ($card['assignee'])
                                            <span style="display: inline-flex; align-items: center; gap: 6px;"><span class="vy-avatar" style="width: 24px; height: 24px; font-size: 10px;">{{ $card['assignee_initials'] }}</span>{{ $card['assignee'] }}</span>
                                        @elseif ($canManage ?? $canAssign)
                                            <button type="button" class="vy-btn vy-btn--ghost" style="padding: 4px 12px; font-size: 12px;" @click="assign = {{ \Illuminate\Support\Js::from(['id' => $card['id'], 'unit' => $card['unit'], 'type' => $card['type']]) }}">Assign</button>
                                        @else
                                            <span class="vy-muted">Unassigned</span>
                                        @endif
                                    </td>
                                    <td><span class="vy-tag tone-{{ $card['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $card['status_meta']['label'] }}</span></td>
                                    <td>
                                        @if ($card['status'] === 'inspected')
                                            <span class="vy-tag tone-in-house"><span class="vy-dot"></span>Ready</span>
                                        @elseif ($card['elapsed'])
                                            <span class="vy-u-sub">{{ $card['elapsed']['minutes'] }}m / {{ $card['elapsed']['expected'] }}m</span>
                                        @else
                                            <span class="vy-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">
                                        @if (($canManage ?? $canAssign) && $card['next'] !== [])
                                            <form method="POST" action="{{ route('app.housekeeping.status', $card['id']) }}" style="display: inline;">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $card['next'][0]['to'] }}">
                                                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 5px 12px; font-size: 12px;">{{ $card['next'][0]['label'] }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="vy-empty">No rooms match this view.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── ASSIGN modal ───────────────────────────────────────────────────────────────────────── --}}
        <div x-show="assign" x-cloak @keydown.escape.window="assign = null"
             style="position: fixed; inset: 0; z-index: 50; background: rgba(43,35,32,.4); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 20px;">
            <div @click.outside="assign = null" class="vy-card vy-fade" style="width: 100%; max-width: 440px;" x-show="assign">
                <h2 class="vy-card-title" style="margin-bottom: 2px;">Assign housekeeper</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;" x-text="assign ? (assign.unit + ' · ' + assign.type) : ''"></p>
                <form method="POST" :action="assign ? assignUrl(assign.id) : ''">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 6px; max-height: 320px; overflow: auto;">
                        @foreach ($crew as $i => $member)
                            <label style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 9px 12px; border: 1px solid var(--vy-hairline); border-radius: 8px; cursor: pointer;">
                                <span style="display: flex; align-items: center; gap: 8px;">
                                    {{-- WAVE 3 — "Suggested" now means genuinely LEAST-LOADED, not "first in the
                                         list". It badged and pre-selected $i === 0 (name order) while showing
                                         that person's real load right beside it — a suggestion contradicting
                                         its own number (#39). --}}
                                    <input type="radio" name="user_id" value="{{ $member['id'] }}" @checked($member['id'] === $suggestedCrewId)>
                                    <span class="vy-avatar" style="width: 26px; height: 26px; font-size: 11px;">{{ $member['initials'] }}</span>
                                    {{ $member['name'] }}
                                    @if ($member['id'] === $suggestedCrewId) <span class="vy-tag tone-vip" style="padding: 1px 7px;">Suggested</span> @endif
                                </span>
                                <span class="vy-muted" style="font-size: 12px;">{{ $member['load'] }} today</span>
                            </label>
                        @endforeach
                    </div>
                    <label class="vy-field-label" style="display: flex; align-items: center; gap: 8px; text-transform: none; letter-spacing: 0; margin-top: 12px;">
                        <input type="checkbox" name="priority" value="1" checked> Clean next · priority
                    </label>
                    <p style="font-size: 11px; color: var(--vy-faint); margin: 6px 0 0;">The room appears in their task list. (Device push isn’t wired — no fabricated notification.)</p>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="assign = null">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Assign</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── ASSIGN-ALL confirm (the balance is computed + applied server-side) ──────────────────── --}}
        <div x-show="assignAllOpen" x-cloak @keydown.escape.window="assignAllOpen = false"
             style="position: fixed; inset: 0; z-index: 50; background: rgba(43,35,32,.4); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; padding: 20px;">
            <div @click.outside="assignAllOpen = false" class="vy-card vy-fade" style="width: 100%; max-width: 420px;">
                <h2 class="vy-card-title" style="margin-bottom: 2px;">Assign all unassigned</h2>
                <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">Balanced by load &amp; who has the fewest open rooms — waiting-guest rooms first.</p>
                <p style="font-size: 13px; color: var(--vy-ink); margin: 0 0 16px;">{{ $unassignedCount }} unassigned {{ \Illuminate\Support\Str::plural('room', $unassignedCount) }} will be shared across {{ count($crew) }} {{ \Illuminate\Support\Str::plural('housekeeper', count($crew)) }}.</p>
                <form method="POST" action="{{ route('app.housekeeping.assign-all') }}">
                    @csrf
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="assignAllOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Assign {{ $unassignedCount }} {{ \Illuminate\Support\Str::plural('room', $unassignedCount) }}</button>
                    </div>
                </form>
                <p style="font-size: 11px; color: var(--vy-faint); margin: 10px 0 0;">Everyone sees their new rooms in their task list. Queues re-sort automatically.</p>
            </div>
        </div>

        {{-- ── ROOM DETAIL modal (GATE 8.2 — was a right-side drawer; now a centered modal via the shared
             .vy-modal-backdrop, whose grid centering survives x-show's inline-display toggle). ── --}}
        <div x-show="detail" x-cloak @keydown.escape.window="detail = null" class="vy-modal-backdrop" style="z-index: 50;">
            <div @click.outside="detail = null" class="vy-card vy-fade" style="width: 100%; max-width: 440px; max-height: 90vh; overflow: auto;" x-show="detail">
                <template x-if="detail">
                    <div>
                        <div class="vy-detail-head" style="margin-bottom: 12px;">
                            <div>
                                <div class="vy-detail-ref" x-text="detail.unit"></div>
                                <div class="vy-u-sub" x-text="detail.type"></div>
                            </div>
                            <button type="button" class="vy-btn vy-btn--ghost" style="padding: 4px 10px;" @click="detail = null">✕</button>
                        </div>

                        {{-- Stage pipeline --}}
                        <div style="display: flex; align-items: center; gap: 4px; margin-bottom: 16px;">
                            <template x-for="(step, i) in ['Dirty','In progress','Clean','Inspected']" :key="step">
                                <div style="flex: 1; text-align: center;">
                                    <div :style="'height:4px;border-radius:2px;background:' + (['pending','in_progress','clean','inspected'].indexOf(detail.status) >= i ? '#C2410C' : 'var(--vy-hairline)')"></div>
                                    <div style="font-size: 10px; color: var(--vy-muted); margin-top: 4px;" x-text="step"></div>
                                </div>
                            </template>
                        </div>

                        {{-- Info --}}
                        <div class="vy-facts" style="margin-bottom: 14px;">
                            <div class="vy-fact-label">Housekeeper</div>
                            <div class="vy-fact-val"><span x-text="detail.assignee || 'Unassigned'"></span></div>
                            <template x-if="detail.elapsed">
                                <div class="vy-fact-label">Elapsed</div>
                            </template>
                            <template x-if="detail.elapsed">
                                <div class="vy-fact-val"><span x-text="detail.elapsed.minutes + 'm / ' + detail.elapsed.expected + 'm'"></span></div>
                            </template>
                        </div>
                        <template x-if="detail.urgency">
                            <div class="vy-recon" style="margin-top: 0; margin-bottom: 12px;" x-text="detail.urgency.label"></div>
                        </template>

                        {{-- Reassign-from-detail (GATE 8.2) — opens the shared, gated assign modal for THIS task
                             (reuses the certified HousekeepingService::assign route; real-housekeeper-only). --}}
                        @if ($canManage ?? $canAssign)
                            <button type="button" class="vy-btn vy-btn--ghost" style="width: 100%; margin-bottom: 14px;"
                                    @click="assign = { id: detail.id, unit: detail.unit, type: detail.type }; detail = null"
                                    x-text="detail && detail.assignee ? 'Reassign housekeeper' : 'Assign housekeeper'"></button>
                        @endif

                        {{-- Photos --}}
                        <h3 style="font-size: 13px; font-weight: 700; color: var(--vy-ink); margin: 6px 0 8px;">Photos</h3>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                            <template x-for="photo in detail.photos" :key="photo.id">
                                <img :src="photo.url" alt="Proof photo" style="width: 72px; height: 72px; object-fit: cover; border-radius: 8px; border: 1px solid var(--vy-hairline);">
                            </template>
                            <template x-if="detail.photos.length === 0">
                                <span class="vy-muted" style="font-size: 12px;">Attach proof of the clean, or flag an issue.</span>
                            </template>
                        </div>
                        @if ($canManage ?? $canAssign)
                            <form method="POST" :action="detail ? photoUrl(detail.id) : ''" enctype="multipart/form-data">
                                @csrf
                                <label class="vy-btn vy-btn--ghost" style="cursor: pointer; display: inline-flex;">
                                    Capture / upload
                                    <input type="file" name="photo" accept="image/*" capture="environment" style="display: none;" onchange="this.form.submit()">
                                </label>
                            </form>

                            {{-- Action --}}
                            <template x-if="detail.next.length">
                                <form method="POST" :action="detail ? statusUrl(detail.id) : ''" style="margin-top: 14px;">
                                    @csrf
                                    <input type="hidden" name="status" :value="detail.next[0].to">
                                    <button type="submit" class="vy-btn vy-btn--accent" style="width: 100%;" x-text="detail.next[0].label"></button>
                                </form>
                            </template>
                        @endif
                        <template x-if="detail.status === 'inspected'">
                            <div class="vy-tag tone-in-house" style="margin-top: 14px; display: inline-flex;"><span class="vy-dot"></span>Guest-ready</div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

    </x-operator.shell>
</div>

@once
    <style>[x-cloak] { display: none !important; }</style>
@endonce
