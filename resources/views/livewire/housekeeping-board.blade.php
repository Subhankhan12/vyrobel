{{-- Operator HOUSEKEEPING (#88 exact-match) — the operator MANAGING the real HousekeepingTask (the owner sees
     the same model read-only). Board + List views, crew panel with real load, assign / assign-all (real writes),
     room-detail MODAL with real proof photos + elapsed. Every mutation is a guarded controller POST.
     ⚠️ 2026-10-05 (his "text is not visible completely … search box … its symbol not well-aligned … Board and list buttons design
     is not good"): rebuilt on the shared list kit — the shared 40 px search, Board | List as one segmented control, the list a
     size container that stacks its rows when narrow, every dialog on the shared solid card, each room's DAY said (the page lists
     every day's cleaning), and the words only what the page does. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div class="vy-hk" x-data="{
        view: localStorage.getItem('vy:hk:view') || 'list',
        detail: @js($detail), assign: null, assignAllOpen: false,
        setView(v) { this.view = v; localStorage.setItem('vy:hk:view', v); },
        assignUrl(id) { return '{{ route('app.housekeeping.assign', '__ID__') }}'.replace('__ID__', id); },
        statusUrl(id) { return '{{ route('app.housekeeping.status', '__ID__') }}'.replace('__ID__', id); },
        photoUrl(id) { return '{{ route('app.housekeeping.photos.store', '__ID__') }}'.replace('__ID__', id); },
        {{-- ⚠️ THE UNDO FOR THE LINE ABOVE. 🔬 Measured 2026-09-22: three photo families exist in this
             app (housekeeping · maintenance · room-types) and TWO of the three already offer a delete
             control on the thumbnail. Housekeeping was the exception: a clean could be photographed
             and the photo shown, and no operator could ever remove one — a wrong or accidental shot
             was permanent. The route existed, fully written and gated, and had no way in. --}}
        photoDeleteUrl(taskId, mediaId) { return '{{ route('app.housekeeping.photos.destroy', ['__TASK__', '__MEDIA__']) }}'.replace('__TASK__', taskId).replace('__MEDIA__', mediaId); },
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
    <x-operator.shell active="housekeeping" :title="__('livewire/housekeeping-board.page_title')" :subtitle="__('livewire/housekeeping-board.page_subtitle')" :viewer="$viewer">

        {{-- ⚠️ F-13 — THE BOARD USED TO SAY NOTHING to a role with `housekeeping.view` and not `housekeeping.manage` (Front Desk,
             Maintenance, Guest Support Agent): every task, zero controls, no reason. The controls stay absent; this says why, and
             who can change it. ⚠️ Above the flashes on purpose — at 375 px they sit below the first screen. No script needed. --}}
        @unless ($canManage)
            <div class="vy-hk-notice" role="status">
                {{ __('livewire/housekeeping-board.view_only_notice') }}
            </div>
        @endunless

        {{-- GATE 8.4 — the assign / assign-all toast with a REAL Undo (a POST to the gated unassign route —
             a genuine server-side revert, never a fabricated client-only "undo"). --}}
        @if (session('hk_notice'))
            @php $hkUndo = session('hk_undo'); @endphp
            <div class="vy-flash is-ok vy-fade vy-hk-flash">
                <span>{{ session('hk_notice') }}</span>
                @if (is_array($hkUndo) && ! empty($hkUndo['url']))
                    <form method="POST" action="{{ $hkUndo['url'] }}" class="vy-hk-undo">
                        @csrf
                        @foreach ($hkUndo['ids'] ?? [] as $undoId)<input type="hidden" name="task_ids[]" value="{{ $undoId }}">@endforeach
                        @if (! empty($hkUndo['restore_priority']))<input type="hidden" name="restore_priority" value="{{ $hkUndo['restore_priority'] }}">@endif
                        <button type="submit" class="vy-btn vy-btn--ghost">{{ __('livewire/housekeeping-board.undo') }}</button>
                    </form>
                @endif
            </div>
        @endif
        @if (session('hk_error'))
            <div class="vy-flash is-err vy-fade">{{ session('hk_error') }}</div>
        @endif

        {{-- ⚠️ 2026-10-05 (🖥 the walk) — Assign needs someone to assign: with nobody on the roster every room offered "Assign" and the
             toolbar "Assign all", each opening a dialog with no one to pick. Offered only when it can work; otherwise said once. --}}
        @php $canAssign = $canManage && $crew !== []; @endphp
        @if ($canManage && $crew === [])
            <div class="vy-hk-notice is-info" role="status">
                {{ __('livewire/housekeeping-board.no_roster_notice') }}
                @if ($canStaff)
                    {!! __('livewire/housekeeping-board.no_roster_grant_link', ['url' => e(route('app.roles'))]) !!}
                @else
                    {{ __('livewire/housekeeping-board.no_roster_ask') }}
                @endif
            </div>
        @endif

        {{-- ── Status summary strip (5 real tallies) ──────────────────────────────────────────────── --}}
        <div class="vy-hk-tally vy-fade">
            {{-- The app's real status vocabulary (Pending = the wireframe's "Dirty"; kept consistent with the
                 lanes + the existing view test, a documented divergence). Each dot's colour now comes from
                 the shared --vy-hk-* palette; the status WORD is always printed beside it, so colour is
                 never the sole carrier of the state (WCAG 1.4.1). --}}
            @php
                $chips = [
                    ['k' => 'pending', 'label' => __('livewire/housekeeping-board.status_pending'), 'dot' => 'var(--vy-hk-pending)'],
                    ['k' => 'in_progress', 'label' => __('livewire/housekeeping-board.status_in_progress'), 'dot' => 'var(--vy-hk-progress)'],
                    ['k' => 'clean', 'label' => __('livewire/housekeeping-board.status_clean'), 'dot' => 'var(--vy-hk-clean)'],
                    ['k' => 'inspected', 'label' => __('livewire/housekeeping-board.status_inspected'), 'dot' => 'var(--vy-hk-inspected)'],
                    ['k' => 'out_of_order', 'label' => __('livewire/housekeeping-board.status_out_of_order'), 'dot' => 'var(--vy-hk-ooo)'],
                ];
            @endphp
            @foreach ($chips as $chip)
                @php $vyN = $tallies[$chip['k']] ?? 0; @endphp
                <span class="vy-hk-tal @if ($vyN === 0) is-zero @endif">
                    <span class="vy-hk-dot" style="background: {{ $chip['dot'] }};"></span>
                    <span class="vy-hk-tal-label">{{ $chip['label'] }}</span>
                    <span class="vy-hk-tal-n">{{ $vyN }}</span>
                </span>
            @endforeach
        </div>

        {{-- ── The toolbar, on the shared list kit: search · Board | List · Assign all, the housekeeper chips beneath ───────── --}}
        <div class="vy-gl-bar vy-hk-bar vy-fade">
            <form class="vy-gl-search" method="GET" action="{{ route('app.housekeeping') }}" role="search">
                {{-- Searching must not silently drop the active crew filter (#20 — a scoped read rides the GET link). --}}
                @if ($crewFilter) <input type="hidden" name="crew" value="{{ $crewFilter }}"> @endif
                <svg class="vy-gl-search-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                <label class="vy-sr-only" for="vy-hk-q">{{ __('livewire/housekeeping-board.search_label') }}</label>
                <input id="vy-hk-q" class="vy-gl-search-in" type="search" name="q" value="{{ $query }}" placeholder="{{ __('livewire/housekeeping-board.search_placeholder') }}" maxlength="80" autocomplete="off">
                @if ($query !== '')
                    <a class="vy-gl-clear" href="{{ route('app.housekeeping', array_filter(['crew' => $crewFilter])) }}" aria-label="{{ __('livewire/housekeeping-board.clear_search') }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </a>
                @endif
            </form>
            <div class="vy-gl-tools">
                <div class="vy-hk-views" role="group" aria-label="{{ __('livewire/housekeeping-board.view_switch_label') }}">
                    <button type="button" class="vy-hk-view" :class="view === 'list' && 'is-on'" :aria-pressed="view === 'list' ? 'true' : 'false'" @click="setView('list')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>
                        <span>{{ __('livewire/housekeeping-board.view_list') }}</span>
                    </button>
                    <button type="button" class="vy-hk-view" :class="view === 'board' && 'is-on'" :aria-pressed="view === 'board' ? 'true' : 'false'" @click="setView('board')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="5" height="16" rx="1.5"/><rect x="10" y="4" width="5" height="11" rx="1.5"/><rect x="17" y="4" width="4" height="7" rx="1.5"/></svg>
                        <span>{{ __('livewire/housekeeping-board.view_board') }}</span>
                    </button>
                </div>
                @if ($canAssign && $unassignedCount > 0)
                    <button type="button" class="vy-btn vy-btn--accent" @click="assignAllOpen = true">{{ __('livewire/housekeeping-board.assign_all_button', ['number' => $unassignedCount]) }}</button>
                @endif
            </div>
            <div class="vy-gs-filters">
                <div class="vy-gl-chips" role="group" aria-label="{{ __('livewire/housekeeping-board.housekeeper') }}">
                    <a class="vy-gl-chip @if ($crewFilter === null) is-on @endif" href="{{ route('app.housekeeping', array_filter(['q' => $query])) }}" data-gl-key="crew-all" @if ($crewFilter === null) aria-current="page" @endif>{{ __('livewire/housekeeping-board.everyone') }}<span class="vy-gl-count">{{ $chipCounts['all'] }}</span></a>
                    @foreach ($crew as $member)
                        <a class="vy-gl-chip @if ($member['active']) is-on @endif" href="{{ route('app.housekeeping', array_filter(['crew' => $member['id'], 'q' => $query])) }}" data-gl-key="crew-{{ $member['id'] }}" @if ($member['active']) aria-current="page" @endif>{{ $member['name'] }}<span class="vy-gl-count">{{ $member['count'] }}</span></a>
                    @endforeach
                    <a class="vy-gl-chip @if ($crewFilter === 'unassigned') is-on @endif" href="{{ route('app.housekeeping', array_filter(['crew' => 'unassigned', 'q' => $query])) }}" data-gl-key="crew-unassigned" @if ($crewFilter === 'unassigned') aria-current="page" @endif>{{ __('livewire/housekeeping-board.unassigned') }}<span class="vy-gl-count">{{ $chipCounts['unassigned'] }}</span></a>
                </div>
            </div>
        </div>

        {{-- ═══ BOARD VIEW ═══ --}}
        <div x-show="view === 'board'" x-cloak>
            {{-- Crew panel --}}
            <div class="vy-card vy-fade vy-hk-crew">
                <span class="vy-eyebrow">{{ __('livewire/housekeeping-board.crew') }}</span>
                @forelse ($crew as $member)
                    <div class="vy-hk-crew-who">
                        <span class="vy-avatar vy-hk-avatar">{{ $member['initials'] }}</span>
                        <div>
                            <div class="vy-hk-crew-name">{{ $member['name'] }}</div>
                            <div class="vy-hk-crew-load">{{ trans_choice('livewire/housekeeping-board.rooms_open', $member['load'], ['count' => $member['load']]) }}</div>
                        </div>
                    </div>
                @empty
                    <span class="vy-hk-crew-none">{{ __('livewire/housekeeping-board.no_housekeepers') }}</span>
                @endforelse
                <div class="vy-hk-crew-end">
                    <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ __('livewire/housekeeping-board.out_of_order_count', ['number' => $outOfOrder]) }}</span>
                    <a class="vy-arr-link" href="{{ route('app.schedule') }}">{{ __('livewire/housekeeping-board.schedule_link') }}</a>
                </div>
            </div>

            {{-- Lanes — the house `.vy-kanban` grid (D10; it narrows to two columns at 1,100 px and one at 700 px). The inline gap
                 stays: the shared class uses 16 px and the desk layout must not move. --}}
            <div class="vy-kanban" style="gap: 14px;">
                @foreach ($columns as $column)
                    {{-- A lane is a forward-only drop target while a card is in flight (a card is dropped here to
                         advance its status). Backward lanes never highlight and reject the drop. --}}
                    <div class="vy-card vy-fade vy-hk-lane"
                         :class="(hkForward('{{ $column['key'] }}') ? 'is-drop ' : '') + (laneOpen('{{ $column['key'] }}') ? '' : 'is-collapsed')"
                         @dragover.prevent @drop.prevent="hkDrop('{{ $column['key'] }}')">
                        {{-- The whole head is the native button (phrasing content only), so a keyboard can collapse a lane too. --}}
                        <button type="button" class="vy-hk-lane-head" @click="toggleLane('{{ $column['key'] }}')" :aria-expanded="laneOpen('{{ $column['key'] }}') ? 'true' : 'false'">
                            <span class="vy-tag tone-{{ $column['tone'] }}"><span class="vy-dot"></span>{{ $column['label'] }}</span>
                            <span class="vy-hk-lane-end">
                                <span class="vy-hk-lane-n">{{ count($column['tasks']) }}</span>
                                <svg class="vy-hk-chev" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="laneOpen('{{ $column['key'] }}') && 'is-open'" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                            </span>
                        </button>
                        <div x-show="laneOpen('{{ $column['key'] }}')">
                            @forelse ($column['tasks'] as $card)
                                @include('livewire.partials.hk-card', ['card' => $card])
                            @empty
                                <div class="vy-hk-lane-empty">{{ __('livewire/housekeeping-board.lane_empty') }}</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ═══ LIST VIEW ═══ --}}
        {{-- ⚠️⚠️ DELIBERATELY NOT CLOAKED — THE BRANCH VISIBLE AT INIT (`view` defaults to 'list'). Cloaking it too left BOTH branches
             hidden when Alpine did not arrive (a bundle 404, a CSP refusal) — 13 real POST forms unreachable. Measured. The status and
             assign forms are server-rendered POSTs that work without Alpine. ⚠️ NEVER CLOAK WHAT IS VISIBLE IN THE INITIAL STATE. --}}
        <div x-show="view === 'list'">
            {{-- ⚠️ 2026-10-05 — a SIZE CONTAINER, as the Guests list: the card's own width decides, and a narrow card stacks each room
                 (its facts named) instead of a 720 px table running past it — 🔬 Ready and the action were cut between ~860 and
                 ~1,010 px windows, and the action sat behind a sideways scroll on a phone. --}}
            <div class="vy-card vy-gl-card vy-fade">
                <div class="vy-gl-scroll">
                    <table class="vy-gl-table vy-hk-table">
                        <thead>
                            <tr>
                                <th>{{ __('livewire/housekeeping-board.col_room') }}</th>
                                <th class="vy-hk-c-day">{{ __('livewire/housekeeping-board.col_day') }}</th>
                                <th class="vy-hk-c-who">{{ __('livewire/housekeeping-board.housekeeper') }}</th>
                                <th class="vy-hk-c-stage">{{ __('livewire/housekeeping-board.col_stage') }}</th>
                                <th class="vy-hk-c-ready">{{ __('livewire/housekeeping-board.col_ready') }}</th>
                                <th class="vy-hk-c-act"><span class="vy-sr-only">{{ __('livewire/housekeeping-board.col_next_step') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $card)
                                <tr>
                                    <td class="vy-hk-c-room">
                                        <button type="button" class="vy-hk-room" @click="detail = {{ \Illuminate\Support\Js::from($card) }}">{{ $card['unit'] }}</button>
                                        @if ($card['turnover'] || $card['urgency'] || $card['guest_vip'])
                                            <div class="vy-hk-tags">
                                                @if ($card['turnover']) <span class="vy-tag tone-vip"><span class="vy-dot"></span>{{ __('livewire/housekeeping-board.turnover') }}</span> @endif
                                                @if ($card['urgency']) <span class="vy-tag tone-{{ $card['urgency']['tone'] === 'red' ? 'risk' : 'confirmed' }}"><span class="vy-dot"></span>{{ $card['urgency']['label'] }}</span> @endif
                                                @if ($card['guest_vip']) <span class="vy-tag tone-vip"><span class="vy-dot"></span>{{ __('livewire/housekeeping-board.vip') }}</span> @endif
                                            </div>
                                        @endif
                                        <div class="vy-hk-sub">{{ $card['type'] }}@foreach ($card['guest_preferences'] as $p) · {{ $p }}@endforeach</div>
                                    </td>
                                    <td class="vy-hk-c-day" data-label="{{ __('livewire/housekeeping-board.col_day') }}"><span class="vy-hk-day is-{{ $card['day_tone'] }}">{{ $card['day'] }}</span></td>
                                    <td class="vy-hk-c-who" data-label="{{ __('livewire/housekeeping-board.housekeeper') }}">
                                        @if ($card['assignee'])
                                            <span class="vy-hk-who"><span class="vy-avatar vy-hk-avatar is-sm">{{ $card['assignee_initials'] }}</span>{{ $card['assignee'] }}</span>
                                        @elseif ($canAssign)
                                            <button type="button" class="vy-btn vy-btn--ghost vy-hk-small" @click="assign = {{ \Illuminate\Support\Js::from(['id' => $card['id'], 'unit' => $card['unit'], 'type' => $card['type']]) }}">{{ __('livewire/housekeeping-board.assign') }}</button>
                                        @else
                                            <span class="vy-hk-none">{{ __('livewire/housekeeping-board.unassigned') }}</span>
                                        @endif
                                    </td>
                                    <td class="vy-hk-c-stage" data-label="{{ __('livewire/housekeeping-board.col_stage') }}"><span class="vy-tag tone-{{ $card['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $card['status_meta']['label'] }}</span></td>
                                    {{-- D8-2 — the READY column: the hour a guest may actually turn up at THIS room (its property's published
                                         check-in time), only when a real reservation arrives here on this date; otherwise the honest "—". --}}
                                    <td class="vy-hk-c-ready" data-label="{{ __('livewire/housekeeping-board.col_ready') }}">
                                        @if ($card['status'] === 'inspected')
                                            <span class="vy-tag tone-in-house"><span class="vy-dot"></span>{{ __('livewire/housekeeping-board.ready_tag') }}</span>
                                        @elseif ($card['elapsed'])
                                            <span class="vy-hk-sub">{{ __('livewire/housekeeping-board.elapsed_of_expected', ['minutes' => $card['elapsed']['minutes'], 'expected' => $card['elapsed']['expected']]) }}</span>
                                            @if ($card['ready_by'])
                                                <span class="vy-hk-sub">{{ __('livewire/housekeeping-board.ready_by_time', ['time' => $card['ready_by']]) }}</span>
                                            @endif
                                        @elseif ($card['ready_by'])
                                            <span class="vy-hk-sub">{{ __('livewire/housekeeping-board.ready_by_time', ['time' => $card['ready_by']]) }}</span>
                                        @else
                                            <span class="vy-hk-none">—</span>
                                        @endif
                                    </td>
                                    <td class="vy-hk-c-act">
                                        @if (($canManage) && $card['next'] !== [])
                                            <form method="POST" action="{{ route('app.housekeeping.status', $card['id']) }}" class="vy-hk-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $card['next'][0]['to'] }}">
                                                <button type="submit" class="vy-btn vy-btn--accent vy-hk-small">{{ $card['next'][0]['label'] }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="vy-empty">{{ __('livewire/housekeeping-board.no_rooms_match') }}</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── ASSIGN — the shared solid card (2026-10-05: its hand-made backdrop carried an inline display beside x-show, so it
             opened as `block`, top-left — A8). Both Assign dialogs exist only when someone can be assigned. ── --}}
        @if ($canAssign)
        <div x-show="assign" x-cloak class="vy-modal-backdrop" @click.self="assign = null" @keydown.escape.window="assign = null">
            <div class="vy-modal vy-modal-card vy-hk-dlg" role="dialog" aria-modal="true" aria-labelledby="vy-hk-assign-t" tabindex="-1" x-trap.noscroll="assign">
                <form method="POST" :action="assign ? assignUrl(assign.id) : ''">
                    @csrf
                    <div class="vy-modal-head">
                        <span class="vy-modal-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M19 8v6M16 11h6"/></svg></span>
                        <div class="vy-hk-head-text">
                            <h2 class="vy-modal-title" id="vy-hk-assign-t">{{ __('livewire/housekeeping-board.assign_housekeeper') }}</h2>
                            <p class="vy-modal-sub" x-text="assign ? (assign.unit + ' · ' + assign.type) : ''"></p>
                        </div>
                        <button type="button" class="vy-modal-x" @click="assign = null" aria-label="{{ __('common.close') }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                    </div>
                    <div class="vy-modal-body">
                        <fieldset class="vy-hk-pick">
                            <legend class="vy-field-label">{{ __('livewire/housekeeping-board.housekeeper') }}</legend>
                            <div class="vy-hk-options">
                                @forelse ($crew as $member)
                                    <label class="vy-hk-option">
                                        {{-- WAVE 3 — "Suggested" means genuinely LEAST-LOADED, not the first name in the list (#39). --}}
                                        <input type="radio" name="user_id" value="{{ $member['id'] }}" @checked($member['id'] === $suggestedCrewId)>
                                        <span class="vy-avatar vy-hk-avatar">{{ $member['initials'] }}</span>
                                        <span class="vy-hk-option-name">{{ $member['name'] }}@if ($member['id'] === $suggestedCrewId) <span class="vy-tag tone-vip">{{ __('livewire/housekeeping-board.suggested') }}</span>@endif</span>
                                        <span class="vy-hk-option-load">{{ trans_choice('livewire/housekeeping-board.open_load', $member['load'], ['count' => $member['load']]) }}</span>
                                    </label>
                                @empty
                                    <p class="vy-hk-crew-none">{{ __('livewire/housekeeping-board.no_housekeepers') }}</p>
                                @endforelse
                            </div>
                        </fieldset>
                        <label class="vy-hk-check"><input type="checkbox" name="priority" value="1" checked> {{ __('livewire/housekeeping-board.clean_next_priority') }}</label>
                        <p class="vy-hk-hint">{{ __('livewire/housekeeping-board.assign_hint') }}</p>
                    </div>
                    <div class="vy-modal-foot">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="assign = null">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/housekeeping-board.assign') }}</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── ASSIGN ALL (the balance is computed + applied server-side) — the shared solid card ── --}}
        <div x-show="assignAllOpen" x-cloak class="vy-modal-backdrop" @click.self="assignAllOpen = false" @keydown.escape.window="assignAllOpen = false">
            <div class="vy-modal vy-modal-card vy-hk-dlg" role="dialog" aria-modal="true" aria-labelledby="vy-hk-assignall-t" tabindex="-1" x-trap.noscroll="assignAllOpen">
                <form method="POST" action="{{ route('app.housekeeping.assign-all') }}">
                    @csrf
                    <div class="vy-modal-head">
                        <span class="vy-modal-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="8" r="3.5"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20a6 6 0 0 1 12 0M14 20a4.5 4.5 0 0 1 8 0"/></svg></span>
                        <div class="vy-hk-head-text">
                            <h2 class="vy-modal-title" id="vy-hk-assignall-t">{{ __('livewire/housekeeping-board.assign_all_title') }}</h2>
                            <p class="vy-modal-sub">{{ __('livewire/housekeeping-board.assign_all_sub') }}</p>
                        </div>
                        <button type="button" class="vy-modal-x" @click="assignAllOpen = false" aria-label="{{ __('common.close') }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                    </div>
                    <div class="vy-modal-body">
                        <p class="vy-hk-lead">{{ trans_choice('livewire/housekeeping-board.assign_all_lead', $unassignedCount, ['count' => $unassignedCount, 'housekeepers' => trans_choice('livewire/housekeeping-board.housekeepers_count', count($crew), ['count' => count($crew)])]) }}</p>
                        <p class="vy-hk-hint">{{ __('livewire/housekeeping-board.assign_all_hint') }}</p>
                    </div>
                    <div class="vy-modal-foot">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="assignAllOpen = false">{{ __('common.cancel') }}</button>
                        <button type="submit" class="vy-btn vy-btn--accent">{{ trans_choice('livewire/housekeeping-board.assign_rooms_button', $unassignedCount, ['count' => $unassignedCount]) }}</button>
                    </div>
                </form>
            </div>
        </div>

        @endif

        {{-- ── ROOM DETAIL — the shared solid card. ⚠️ 2026-10-05: it opens on load when the address names a task (`?task=` — where
             the photo routes send you back), so adding or removing a photo lands back on the room, not on the bare list. ── --}}
        <div x-show="detail" x-cloak class="vy-modal-backdrop" @click.self="detail = null" @keydown.escape.window="detail = null">
            <div class="vy-modal vy-modal-card vy-hk-dlg" role="dialog" aria-modal="true" aria-label="{{ __('livewire/housekeeping-board.task_detail') }}" tabindex="-1" x-trap.noscroll="detail">
                <template x-if="detail">
                    <div>
                        <div class="vy-modal-head">
                            <span class="vy-modal-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5"/></svg></span>
                            <div class="vy-hk-head-text">
                                <h2 class="vy-modal-title" x-text="detail.unit"></h2>
                                <p class="vy-modal-sub" x-text="detail.type + ' · ' + detail.day"></p>
                            </div>
                            <button type="button" class="vy-modal-x" @click="detail = null" aria-label="{{ __('common.close') }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                        </div>
                        <div class="vy-modal-body">
                            {{-- Stage pipeline — the page's own words for the stages --}}
                            <ol class="vy-hk-steps" aria-label="{{ __('livewire/housekeeping-board.col_stage') }}">
                                <template x-for="(step, i) in [@js(__('livewire/housekeeping-board.status_pending')), @js(__('livewire/housekeeping-board.status_in_progress')), @js(__('livewire/housekeeping-board.status_clean')), @js(__('livewire/housekeeping-board.status_inspected'))]" :key="i">
                                    <li class="vy-hk-step" :class="['pending', 'in_progress', 'clean', 'inspected'].indexOf(detail.status) >= i && 'is-done'"><span class="vy-hk-step-bar"></span><span x-text="step"></span></li>
                                </template>
                            </ol>

                            <dl class="vy-hk-facts">
                                <dt>{{ __('livewire/housekeeping-board.housekeeper') }}</dt><dd x-text="detail.assignee || @js(__('livewire/housekeeping-board.unassigned'))"></dd>
                                <template x-if="detail.elapsed"><dt>{{ __('livewire/housekeeping-board.elapsed') }}</dt></template>
                                <template x-if="detail.elapsed"><dd x-text="@js(__('livewire/housekeeping-board.elapsed_of_expected', ['minutes' => ':minutes', 'expected' => ':expected'])).replace(':minutes', detail.elapsed.minutes).replace(':expected', detail.elapsed.expected)"></dd></template>
                                <template x-if="detail.ready_by"><dt>{{ __('livewire/housekeeping-board.ready_by') }}</dt></template>
                                <template x-if="detail.ready_by"><dd x-text="detail.ready_by"></dd></template>
                            </dl>
                            <template x-if="detail.urgency">
                                <div class="vy-hk-urgent" x-text="detail.urgency.label"></div>
                            </template>

                            {{-- Reassign-from-detail (GATE 8.2) — the shared, gated assign dialog for THIS task. --}}
                            @if ($canAssign)
                                <button type="button" class="vy-btn vy-btn--ghost vy-hk-wide"
                                        @click="assign = { id: detail.id, unit: detail.unit, type: detail.type }; detail = null"
                                        x-text="detail && detail.assignee ? @js(__('livewire/housekeeping-board.reassign_housekeeper')) : @js(__('livewire/housekeeping-board.assign_housekeeper'))"></button>
                            @endif

                            {{-- Photos --}}
                            <h3 class="vy-hk-h3">{{ __('livewire/housekeeping-board.photos') }}</h3>
                            <div class="vy-hk-photos">
                                <template x-for="photo in detail.photos" :key="photo.id">
                                    <div class="vy-hk-photo">
                                        <img :src="photo.url" alt="{{ __('livewire/housekeeping-board.proof_photo') }}">
                                        @if ($canManage)
                                            {{-- ⚠️ The SAME permission as the upload and the route (`housekeeping.manage`) — #30: the UI never
                                                 invites a 403. Same × and confirm as the maintenance ticket's photos. --}}
                                            <form method="POST" :action="photoDeleteUrl(detail.id, photo.id)" class="vy-hk-photo-x"
                                                  onsubmit="return confirm(@js(__('livewire/housekeeping-board.remove_photo_confirm')));">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="{{ __('livewire/housekeeping-board.remove_photo') }}" aria-label="{{ __('livewire/housekeeping-board.remove_photo') }}">&times;</button>
                                            </form>
                                        @endif
                                    </div>
                                </template>
                                <template x-if="detail.photos.length === 0">
                                    <span class="vy-hk-none">{{ __('livewire/housekeeping-board.add_photo_hint') }}</span>
                                </template>
                            </div>
                            @if ($canManage)
                                <form method="POST" :action="detail ? photoUrl(detail.id) : ''" enctype="multipart/form-data">
                                    @csrf
                                    <label class="vy-btn vy-btn--ghost vy-hk-upload">
                                        {{ __('livewire/housekeeping-board.take_or_upload_photo') }}
                                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" capture="environment" class="vy-sr-only" onchange="this.form.submit()">
                                    </label>
                                </form>
                            @endif
                        </div>
                        <div class="vy-modal-foot">
                            @if ($canManage)
                                <template x-if="detail.next.length">
                                    <form method="POST" :action="detail ? statusUrl(detail.id) : ''" class="vy-hk-foot-form">
                                        @csrf
                                        <input type="hidden" name="status" :value="detail.next[0].to">
                                        <button type="submit" class="vy-btn vy-btn--accent" x-text="detail.next[0].label"></button>
                                    </form>
                                </template>
                            @endif
                            <template x-if="detail.status === 'inspected'">
                                <span class="vy-tag tone-in-house"><span class="vy-dot"></span>{{ __('livewire/housekeeping-board.guest_ready') }}</span>
                            </template>
                            <button type="button" class="vy-btn vy-btn--ghost" @click="detail = null">{{ __('common.close') }}</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </x-operator.shell>
</div>
