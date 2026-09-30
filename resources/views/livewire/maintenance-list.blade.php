{{-- Operator MAINTENANCE board (#89 exact-match), rebuilt 2026-09-29 on the founder's request ("the same sort of things"
     as the Guests page; design approved the same day). The work-order list over the real MaintenanceTicket (the same
     model the Dashboard reads; undisturbed): real KPIs, one toolbar (search · filter chips with counts · Select · New
     work order), a table that sorts by priority, time to target, reported and unit and pages 25 / 50 / 100, a menu on
     every row, the detail drawer, and the out-of-order hero, technicians and parts panels. Every write is a guarded
     controller POST through the certified MaintenanceService; an action started here comes back here, as you had it.

     ⚠️ EACH ROW OFFERS ONLY WHAT IT MAY DO. `data-mt-actions` and the row's `actions` come from the component, by the
     ticket's state and the viewer's permission — nothing that writes on a closed ticket (the old board offered Assign on
     a resolved one, which the service refuses), and nothing at all to a role without maintenance.manage or while the
     workspace is read-only.

     ⚠️ EVERY SETTING IS A LINK. Filters, sorting, page size and pages are ordinary GET links that work with no script;
     the shared list script (partials/operator-list-script) only makes them smooth, so every change still runs every
     middleware (invariant #20).

     ⚠️ DIALOGS CENTRE BY CLASS, and the drawer sits on the right by class (`.vy-drawer-backdrop`). An inline `display`
     beside x-show is erased the first time the element is hidden — which is how every dialog here opened in the
     top-left corner and the drawer on the left. XShowInlineDisplayContractTest keeps it that way.

     RAW-BLADE RULE: never write `word@directive` — every `@` below follows a space, newline, tag, quote or bracket. --}}
<div class="vy-mnt">
    <x-operator.shell active="maintenance" title="Maintenance" subtitle="Work orders across your units" :viewer="$viewer">

        @if (session('mt_notice'))
            <div class="vy-flash is-ok vy-fade">{{ session('mt_notice') }}</div>
        @endif
        @if (session('mt_error'))
            <div class="vy-flash is-err vy-fade">{{ session('mt_error') }}</div>
        @endif
        @if ($errors->any())
            <div class="vy-flash is-err vy-fade">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="vy-gl-root" x-data="vyMaint(@js($boot))" :class="selectMode && 'is-selecting'">

            {{-- ── The figures — real counts over the whole board, whatever is filtered below ──────────────── --}}
            <div class="vy-dash-kpis vy-dash-kpis--5 vy-fade">
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Active</div>
                    <div class="vy-dk-val">{{ $kpis['open'] }}</div>
                    <div class="vy-dk-sub">not started or in progress</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Urgent</div>
                    <div class="vy-dk-val">{{ $kpis['urgent'] }}</div>
                    <div class="vy-dk-sub">still open</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Awaiting parts</div>
                    <div class="vy-dk-val">{{ $kpis['parts'] }}</div>
                    <div class="vy-dk-sub">waiting on a part</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Out of order</div>
                    <div class="vy-dk-val">{{ $kpis['ooo'] }}</div>
                    <div class="vy-dk-sub">{{ \Illuminate\Support\Str::plural('unit', $kpis['ooo']) }} off sale</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Resolved</div>
                    <div class="vy-dk-val">{{ $kpis['done'] }}</div>
                    <div class="vy-dk-sub">in the last 7 days</div>
                </div>
            </div>

            {{-- ── The toolbar — search · Costs report · Settings · Select · New work order, then the filters ─── --}}
            <div class="vy-gl-bar vy-mt-bar vy-fade">
                {{-- Without script this is a plain GET form that keeps the other settings (the hidden fields); with it, the
                     results follow the typing. --}}
                <form class="vy-gl-search" method="GET" action="{{ route('app.maintenance') }}" role="search" @submit.prevent="search()">
                    @foreach ($keep as $keepName => $keepValue)
                        <input type="hidden" name="{{ $keepName }}" value="{{ $keepValue }}">
                    @endforeach
                    <svg class="vy-gl-search-ico" x-show="! loading" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    <span class="vy-gl-spinner" x-show="loading" x-cloak aria-hidden="true"></span>
                    <label class="vy-sr-only" for="vy-mt-q">Search work orders</label>
                    <input id="vy-mt-q" class="vy-gl-search-in" type="search" name="q" value="{{ $query }}" x-model="q"
                           @input.debounce.300ms="search()" placeholder="Search issue, unit, category or technician" maxlength="80" autocomplete="off" spellcheck="false">
                    <button type="button" class="vy-gl-clear" x-show="q !== ''" x-cloak @click="clearSearch()" aria-label="Clear search">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </form>

                <div class="vy-gl-tools">
                    {{-- WAVE 2 · #18 — both pages are gated maintenance.manage, so the links are too ("never invite a 403"). --}}
                    @if ($canManage)
                        <a class="vy-btn vy-btn--ghost" href="{{ route('app.maintenance.costs') }}">Costs report</a>
                        <a class="vy-btn vy-btn--ghost" href="{{ route('app.maintenance.settings') }}">Settings</a>
                        <button type="button" class="vy-btn vy-btn--ghost vy-gl-select" :class="selectMode && 'is-on'" :aria-pressed="selectMode ? 'true' : 'false'" @click="toggleMode()">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/></svg>
                            <span x-text="selectMode ? 'Done' : 'Select'">Select</span>
                        </button>
                    @endif
                    @if ($canWrite)
                        <button type="button" class="vy-btn vy-btn--accent" @click="nwoOpen = true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            New work order
                        </button>
                    @endif
                </div>

                <div class="vy-gl-chips vy-mt-chips" data-gl-region="chips" role="group" aria-label="Show">
                    @foreach ($chips as $chip)
                        <a class="vy-gl-chip {{ $chip['active'] ? 'is-on' : '' }}" href="{{ $chip['href'] }}" data-gl-key="chip-{{ $chip['key'] }}" @if ($chip['active']) aria-current="page" @endif>{{ $chip['label'] }}<span class="vy-gl-count">{{ $chip['count'] }}</span></a>
                    @endforeach
                </div>
            </div>

            {{-- Said after every change, for screen readers — the new position in the list. --}}
            <p class="vy-sr-only" aria-live="polite" x-text="live"></p>

            <div class="vy-mt-cols">
                {{-- ── The work orders — swapped in place by the script, and a whole page without it ─────────── --}}
                <div class="vy-gl-results" data-gl-region="results" :class="loading && 'is-loading'">
                    @if ($windowTotal === 0)
                        <div class="vy-card vy-gl-empty vy-fade">
                            <span class="vy-gl-empty-ico" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3.5 17.5a2.1 2.1 0 0 0 3 3l5.8-5.8a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg>
                            </span>
                            <h2 class="vy-gl-empty-title">No work orders</h2>
                            <p class="vy-gl-empty-text">Nothing is open, and nothing was resolved in the last 7 days.</p>
                            @if ($canWrite)
                                <button type="button" class="vy-btn vy-btn--accent" @click="nwoOpen = true">New work order</button>
                            @endif
                        </div>
                    @elseif ($rows->total() === 0)
                        <div class="vy-card vy-gl-empty vy-fade">
                            <span class="vy-gl-empty-ico" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                            </span>
                            <h2 class="vy-gl-empty-title">
                                @if ($query !== '') No work orders match “{{ $query }}” @else No work orders in this view @endif
                            </h2>
                            <p class="vy-gl-empty-text">Search looks at the issue, the unit, the category and the technician. {{ $windowTotal }} {{ \Illuminate\Support\Str::plural('work order', $windowTotal) }} on the board in all.</p>
                            <a class="vy-btn vy-btn--ghost" href="{{ route('app.maintenance') }}" data-gl-key="clear-all">Clear search and filters</a>
                        </div>
                    @else
                        <div class="vy-card vy-gl-card vy-fade">
                            <div class="vy-gl-progress" aria-hidden="true"></div>
                            <div class="vy-gl-scroll">
                                <table class="vy-gl-table vy-mt-table">
                                    <caption class="vy-sr-only">Work orders, {{ $sortedBy }}</caption>
                                    <thead>
                                        <tr>
                                            @if ($canManage)
                                                <th class="vy-gl-check" x-show="selectMode" x-cloak>
                                                    <input type="checkbox" aria-label="Select every work order on this page"
                                                           :checked="allOnPage()" x-effect="$el.indeterminate = someOnPage()" @change="selectPage($event.target.checked)">
                                                </th>
                                            @endif
                                            <th>Work order</th>
                                            <th aria-sort="{{ $sorts['unit']['aria'] }}">
                                                <a class="vy-gl-sort {{ $sorts['unit']['active'] ? 'is-on is-'.$sorts['unit']['dir'] : '' }}" href="{{ $sorts['unit']['href'] }}" data-gl-key="sort-unit">Unit<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                            </th>
                                            <th aria-sort="{{ $sorts['priority']['aria'] }}">
                                                <a class="vy-gl-sort {{ $sorts['priority']['active'] ? 'is-on is-'.$sorts['priority']['dir'] : '' }}" href="{{ $sorts['priority']['href'] }}" data-gl-key="sort-priority">Priority<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                            </th>
                                            <th>Status</th>
                                            <th>Technician</th>
                                            <th aria-sort="{{ $sorts['due']['aria'] }}">
                                                <a class="vy-gl-sort {{ $sorts['due']['active'] ? 'is-on is-'.$sorts['due']['dir'] : '' }}" href="{{ $sorts['due']['href'] }}" data-gl-key="sort-due">Time to target<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                            </th>
                                            <th aria-sort="{{ $sorts['reported']['aria'] }}">
                                                <a class="vy-gl-sort {{ $sorts['reported']['active'] ? 'is-on is-'.$sorts['reported']['dir'] : '' }}" href="{{ $sorts['reported']['href'] }}" data-gl-key="sort-reported">Reported<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                            </th>
                                            <th class="vy-gl-actcol"><span class="vy-sr-only">Actions</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rows as $row)
                                            <tr data-gl-id="{{ $row['id'] }}" data-mt-actions="{{ implode(' ', $row['actions']) }}" :class="sel['{{ $row['id'] }}'] && 'is-selected'">
                                                @if ($canManage)
                                                    <td class="vy-gl-check" x-show="selectMode" x-cloak>
                                                        <input type="checkbox" x-model="sel['{{ $row['id'] }}']" aria-label="Select {{ $row['title'] }}">
                                                    </td>
                                                @endif
                                                <td class="vy-mt-ordercell">
                                                    <div class="vy-gl-nameline">
                                                        <button type="button" class="vy-gl-name vy-rowopen" @click="openDetail('{{ $row['id'] }}')">{{ $row['title'] }}</button>
                                                        @if ($row['oos']) <span class="vy-tag tone-risk"><span class="vy-dot"></span>Out of order</span> @endif
                                                        @if ($row['awaiting_parts']) <span class="vy-tag tone-tag"><span class="vy-dot"></span>Awaiting parts</span> @endif
                                                    </div>
                                                    <div class="vy-gl-contact">{{ $row['category'] }}</div>
                                                </td>
                                                <td><span class="vy-mt-unit">{{ $row['unit'] }}</span></td>
                                                <td><span class="vy-mt-prio is-{{ $row['priority'] }}">{{ $row['priority_label'] }}</span></td>
                                                <td><span class="vy-tag tone-{{ $row['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['status_meta']['label'] }}</span></td>
                                                <td>
                                                    @if ($row['assignee'] !== null)
                                                        <span class="vy-mt-tech"><span class="vy-gl-avatar tone-{{ strlen($row['assignee']) % 4 + 1 }}" aria-hidden="true">{{ $row['assignee_initials'] }}</span>{{ $row['assignee'] }}</span>
                                                    @else
                                                        <span class="vy-gl-none">Unassigned</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($row['status'] === 'resolved')
                                                        <span class="vy-gl-none">Resolved {{ $row['resolved'] }}</span>
                                                    @elseif ($row['sla']['label'] !== '')
                                                        {{-- AXIS 3 (DataConditionalContrastTest): both warning colours are the AA text tokens. --}}
                                                        <span class="vy-mt-due" style="color: {{ $row['sla']['breached'] ? 'var(--vy-accent-text)' : ($row['sla']['due_soon'] ? 'var(--vy-amber-text)' : 'var(--vy-ink)') }};">{{ $row['sla']['label'] }}</span>
                                                    @else
                                                        <span class="vy-gl-none">—</span>
                                                    @endif
                                                </td>
                                                <td><span class="vy-gl-when">{{ $row['reported_ago'] }}</span></td>
                                                <td class="vy-gl-actcol">
                                                    <button type="button" class="vy-gl-more" aria-label="Actions for {{ $row['title'] }}" aria-controls="vy-mt-menu"
                                                            :aria-expanded="menuFor === '{{ $row['id'] }}' ? 'true' : 'false'" @click="openMenu($event, row('{{ $row['id'] }}'))">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5.5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="18.5" cy="12" r="1.7"/></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- The footer — where you are, how many per page, the way to the next page, and what the board holds.
                                 OUTSIDE the table. The old line said "from the last 7 days" while listing open work of any age. --}}
                            <div class="vy-gl-foot" data-gl-foot>
                                <p class="vy-gl-summary" data-gl-summary tabindex="-1"><strong>{{ $rows->firstItem() }}–{{ $rows->lastItem() }}</strong> of <strong>{{ $rows->total() }}</strong> {{ \Illuminate\Support\Str::plural('work order', $rows->total()) }}<span class="vy-gl-sorted"> · {{ $sortedBy }}</span></p>
                                <div class="vy-gl-paging">
                                    <span class="vy-gl-rows-label" id="vy-mt-rows">Rows</span>
                                    <div class="vy-gl-sizes" role="group" aria-labelledby="vy-mt-rows">
                                        @foreach ($sizes as $size)
                                            <a class="vy-gl-size {{ $size['active'] ? 'is-on' : '' }}" href="{{ $size['href'] }}" data-gl-key="per-{{ $size['size'] }}" @if ($size['active']) aria-current="true" @endif>{{ $size['size'] }}</a>
                                        @endforeach
                                    </div>
                                    @if ($rows->lastPage() > 1)
                                        <nav class="vy-gl-pages" aria-label="Pages">
                                            @if ($pages['prev'] !== null)
                                                <a class="vy-gl-page" href="{{ $pages['prev'] }}" data-gl-key="page-prev" rel="prev" aria-label="Previous page">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                                                </a>
                                            @else
                                                <span class="vy-gl-page is-off" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></span>
                                            @endif
                                            <span class="vy-gl-pagepos">Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }}</span>
                                            @if ($pages['next'] !== null)
                                                <a class="vy-gl-page" href="{{ $pages['next'] }}" data-gl-key="page-next" rel="next" aria-label="Next page">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                                                </a>
                                            @else
                                                <span class="vy-gl-page is-off" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></span>
                                            @endif
                                        </nav>
                                    @endif
                                </div>
                                <p class="vy-mt-window">The board shows work orders that are open, and resolved in the last 7 days.@if ($canManage) Costs from older work are in the <a class="vy-gl-link" href="{{ route('app.maintenance.costs') }}">costs report</a>.@endif</p>
                            </div>
                        </div>
                        {{-- Each row on this page, once — what the row menu and the drawer open with. JSON, never run. --}}
                        <script type="application/json" data-mt-rows>@json($rowsById)</script>
                    @endif
                </div>

                {{-- ── The rail — the out-of-order hero, technicians and parts (the whole board, not the filter) ─── --}}
                <aside class="vy-mt-rail" aria-label="Out of order, technicians and parts">
                    <div class="vy-tonight vy-mt-oos vy-fade">
                        <div class="vy-dk-label vy-mt-oos-label">Out of order</div>
                        <p class="vy-mt-oos-count"><span class="vy-mt-oos-n">{{ $oosHero['count'] }}</span> {{ \Illuminate\Support\Str::plural('unit', $oosHero['count']) }} blocked</p>
                        <p class="vy-mt-oos-units">{{ $oosHero['units'] !== '' ? $oosHero['units'] : 'No units blocked — all clear' }}</p>
                        @if ($oosHero['on_hold'] !== null)
                            <div class="vy-mt-oos-hold">
                                <strong>{{ $oosHero['on_hold'] }} / night</strong>
                                <span>revenue on hold</span>
                            </div>
                        @endif
                    </div>

                    <div class="vy-card vy-mt-panel vy-fade">
                        <h2 class="vy-card-title">Technicians</h2>
                        @forelse ($technicians as $tech)
                            <div class="vy-mt-panel-row">
                                <span class="vy-mt-tech"><span class="vy-gl-avatar tone-{{ strlen($tech['name']) % 4 + 1 }}" aria-hidden="true">{{ $tech['initials'] }}</span><span>
                                    <span class="vy-mt-panel-name">{{ $tech['name'] }}</span>
                                    <span class="vy-mt-panel-sub">{{ $tech['load'] }} open @if ($tech['urgent'] > 0) · {{ $tech['urgent'] }} urgent @endif</span>
                                </span></span>
                                <span class="vy-tag tone-{{ $tech['status'] === 'busy' ? 'risk' : 'in-house' }}"><span class="vy-dot"></span>{{ ucfirst($tech['status']) }}</span>
                            </div>
                        @empty
                            <p class="vy-mt-panel-empty">No technicians have open work right now.</p>
                        @endforelse
                    </div>

                    <div class="vy-card vy-mt-panel vy-fade">
                        <h2 class="vy-card-title">Parts on order</h2>
                        @forelse ($partsOnOrder as $part)
                            <div class="vy-mt-panel-row">
                                <span>
                                    <span class="vy-mt-panel-name">{{ $part['part_name'] ?? 'Part' }}</span>
                                    <span class="vy-mt-panel-sub">{{ $part['unit'] }}@if ($part['part_eta']) · arrives {{ $part['part_eta'] }} @endif</span>
                                </span>
                                <span class="vy-tag tone-tag"><span class="vy-dot"></span>On order</span>
                            </div>
                        @empty
                            <p class="vy-mt-panel-empty">Nothing on order.</p>
                        @endforelse
                    </div>
                </aside>
            </div>

            {{-- ── The row menu — ONE, placed beside the button that opened it, outside the table card (the card's blur
                 and entrance transform would otherwise become the box a fixed menu is placed in). Its entries follow the
                 row's `actions`, so a closed ticket or a read-only role is offered the two Open items only. ─────────── --}}
            <div class="vy-gl-menu" id="vy-mt-menu" x-show="menu.open" x-cloak :class="menu.up && 'is-up'"
                 :style="{ top: menu.y + 'px', left: menu.x + 'px' }"
                 @click.outside="closeMenu(false)" @keydown.escape.prevent.stop="closeMenu(true)" @focusout="leaveMenu($event)">
                <button type="button" class="vy-gl-menu-item" @click="menuAct('details')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M15 4v16"/></svg>
                    Open details
                </button>
                <a class="vy-gl-menu-item" :href="menu.row ? link(urls.show, menu.row.id) : '#'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-8 8M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
                    Open full record
                </a>
                @if ($canWrite)
                    <span class="vy-gl-menu-sep" x-show="can('edit')" aria-hidden="true"></span>
                    <button type="button" class="vy-gl-menu-item" x-show="can('edit')" @click="menuAct('edit')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                        Edit details
                    </button>
                    <button type="button" class="vy-gl-menu-item" x-show="can('assign')" @click="menuAct('assign')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="8" r="3.5"/><path d="M3.5 20c0-3.6 2.9-6.5 6.5-6.5 1.4 0 2.7.4 3.7 1.2M16 17l2 2 4-4"/></svg>
                        <span x-text="menu.row && menu.row.assignee ? 'Change technician' : 'Assign technician'">Assign technician</span>
                    </button>
                    <form method="POST" x-show="can('start')" :action="menu.row ? link(urls.start, menu.row.id) : '#'" data-guard-submit @submit="stamp($event.target)">
                        @csrf
                        <input type="hidden" name="return" value="list">
                        <input type="hidden" name="return_query" value="">
                        <button type="submit" class="vy-gl-menu-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M10 8.5l5 3.5-5 3.5z"/></svg>
                            Start work
                        </button>
                    </form>
                    <button type="button" class="vy-gl-menu-item" x-show="can('resolve')" @click="menuAct('resolve')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 12.5l5 5 10-10"/></svg>
                        Mark resolved
                    </button>
                    <button type="button" class="vy-gl-menu-item is-danger" x-show="can('cancel')" @click="menuAct('cancel')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg>
                        Cancel work order
                    </button>
                @endif
            </div>

            {{-- ── The bulk bar — select mode, one or more selected (maintenance.manage, like every bulk route) ───── --}}
            @if ($canManage)
                <div class="vy-gl-bulk" x-show="selectMode && count()" x-cloak x-transition role="region" aria-label="Selected work orders">
                    <span class="vy-gl-bulk-count"><strong x-text="count()"></strong> selected</span>
                    <button type="button" class="vy-gl-bulk-clear" @click="clear()">Clear</button>
                    <span class="vy-gl-bulk-sep" aria-hidden="true"></span>
                    {{-- Export — a CSV of exactly the selected work orders (no guest data on a work order) --}}
                    <form method="POST" action="{{ route('app.maintenance.bulk.export') }}">
                        @csrf
                        <template x-for="id in ids()" :key="id"><input type="hidden" name="ticket_ids[]" :value="id"></template>
                        <button type="submit" class="vy-gl-bulk-act">Export</button>
                    </form>
                    @if ($canWrite)
                        <button type="button" class="vy-gl-bulk-act" @click="bulkAssign()">Assign</button>
                        <button type="button" class="vy-gl-bulk-act" @click="bulkResolve()">Resolve</button>
                    @endif
                </div>
            @endif

            @if ($canWrite)
                {{-- ── NEW WORK ORDER ─────────────────────────────────────────────────────────────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="nwoOpen" x-transition.opacity @keydown.escape.window="nwoOpen = false" @click.self="nwoOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide" x-show="nwoOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-mt-new-t" tabindex="-1" x-trap.noscroll="nwoOpen">
                        <form method="POST" action="{{ route('app.maintenance.store') }}" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3.5 17.5a2.1 2.1 0 0 0 3 3l5.8-5.8a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-mt-new-t">New work order</h3>
                                    <p class="vy-modal-sub">Logged now — assign a technician from the board once it’s created.</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="nwoOpen = false" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-mt-n-title">Issue</label>
                                    <input class="vy-input" id="vy-mt-n-title" type="text" name="title" maxlength="160" required placeholder="e.g. AC not cooling" autocomplete="off" autofocus>
                                </div>
                                @if ($unitOptions === [])
                                    <p class="vy-gl-errors">There are no units here yet — add one before raising a work order.</p>
                                @else
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-mt-n-unit">Unit</label>
                                        <x-operator.combobox name="unit_id" id="vy-mt-n-unit" :options="$unitOptions" :selected="$unitOptions[0]['value']" search="Search units" empty="No unit matches" />
                                    </div>
                                @endif
                                <div class="vy-mt-cats">
                                    <x-operator.choices name="category" label="Category" :options="$categoryChoices" selected="plumbing" />
                                </div>
                                <x-operator.choices name="priority" label="Priority" :options="$priorityChoices" selected="normal" variant="segmented" />
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-mt-n-desc">Description <span class="vy-fl-note">· optional</span></label>
                                    <textarea class="vy-input vy-gl-textarea" id="vy-mt-n-desc" name="description" rows="3" maxlength="2000" placeholder="What’s wrong, where exactly, anything the technician should know"></textarea>
                                </div>
                                <label class="vy-gl-switch">
                                    <input type="checkbox" name="takes_out_of_service" value="1" x-model="nwoOoo">
                                    <span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Take the room out of order</strong><span>It can’t be sold until this work order is resolved or cancelled.</span></span>
                                </label>
                                <p class="vy-mt-warn" x-show="nwoOoo" x-cloak>Nights already booked on this unit will need moving — check the rack first.</p>
                                <p class="vy-gl-note">Photos can be added from the work order once it’s created.</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="nwoOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent" @if ($unitOptions === []) disabled @endif><span x-text="nwoOoo ? 'Create & block room' : 'Create work order'">Create work order</span></button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── EDIT (one work order, from its row) — the title, description, category and priority, while the work
                     is open. Not the unit and not out-of-order: those move a room's availability and keep their own paths. ── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="editOpen" x-transition.opacity @keydown.escape.window="editOpen = false" @click.self="editOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide" x-show="editOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-mt-edit-t" tabindex="-1" x-trap.noscroll="editOpen">
                        <form method="POST" :action="link(urls.edit, edit.id)" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <input type="hidden" name="_ticket" :value="edit.id">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-mt-edit-t">Edit work order</h3>
                                    <p class="vy-modal-sub" x-text="edit.ref"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="editOpen = false" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                {{-- An edit the server refused comes back open (MaintenanceList::editRetry) — with its reason here,
                                     not only in the page's flash behind the dialog. --}}
                                @if ($errors->any() && is_string(old('_ticket')))
                                    <div class="vy-gl-errors" role="alert">
                                        @foreach ($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-mt-e-title">Issue</label>
                                    <input class="vy-input" id="vy-mt-e-title" type="text" name="title" maxlength="160" required x-model="edit.title" autocomplete="off">
                                </div>
                                <div class="vy-mt-cats">
                                    <x-operator.choices name="category" label="Category" :options="$categoryChoices" model="edit.category" />
                                </div>
                                <x-operator.choices name="priority" label="Priority" :options="$priorityChoices" model="edit.priority" variant="segmented" />
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-mt-e-desc">Description <span class="vy-fl-note">· optional</span></label>
                                    <textarea class="vy-input vy-gl-textarea" id="vy-mt-e-desc" name="description" rows="3" maxlength="2000" x-model="edit.description"></textarea>
                                </div>
                                <p class="vy-gl-note">A new priority moves the target time with it. To change the unit, cancel this work order and raise a new one.</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="editOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── ASSIGN — one work order from its row, or the selection from the bulk bar ───────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="assignOpen" x-transition.opacity @keydown.escape.window="assignOpen = false" @click.self="assignOpen = false">
                    <div class="vy-modal vy-modal-card" x-show="assignOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-mt-assign-t" tabindex="-1" x-trap.noscroll="assignOpen">
                        <form method="POST" :action="assign.bulk ? urls.bulkAssign : link(urls.assign, assign.id)" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <template x-if="assign.bulk">
                                <div><template x-for="id in ids()" :key="id"><input type="hidden" name="ticket_ids[]" :value="id"></template></div>
                            </template>
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="8" r="3.5"/><path d="M3.5 20c0-3.6 2.9-6.5 6.5-6.5 1.4 0 2.7.4 3.7 1.2M16 17l2 2 4-4"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-mt-assign-t">Assign technician</h3>
                                    <p class="vy-modal-sub" x-text="assign.bulk ? count() + ' ' + (count() === 1 ? 'work order' : 'work orders') + ' selected' : assign.title"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="assignOpen = false" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <fieldset class="vy-gl-fieldset">
                                    <legend class="vy-field-label">Technician</legend>
                                    <div class="vy-mt-people">
                                    @foreach ($staff as $member)
                                        <label class="vy-mt-person">
                                            <input type="radio" name="user_id" value="{{ $member['id'] }}" x-model="pick">
                                            <span class="vy-mt-person-face">
                                                <span class="vy-gl-avatar tone-{{ strlen($member['name']) % 4 + 1 }}" aria-hidden="true">{{ $member['initials'] }}</span>
                                                <span class="vy-mt-person-name">{{ $member['name'] }}</span>
                                                <span class="vy-mt-person-now" x-show="! assign.bulk && assign.current === '{{ $member['id'] }}'" x-cloak>Current</span>
                                            </span>
                                        </label>
                                    @endforeach
                                    <label class="vy-mt-person">
                                        <input type="radio" name="user_id" value="" x-model="pick">
                                        <span class="vy-mt-person-face">
                                            <span class="vy-gl-avatar tone-erased" aria-hidden="true">–</span>
                                            <span class="vy-mt-person-name">No one — leave unassigned</span>
                                        </span>
                                    </label>
                                    </div>
                                </fieldset>
                                <p class="vy-gl-note">The work order appears in their task list. Nothing is sent to their phone.</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="assignOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent" :disabled="pick === null || (! assign.bulk && pick === assign.current)">Assign</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── MARK RESOLVED — one work order (with its cost), or the selection (no cost, said plainly) ─────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="resolveOpen" x-transition.opacity @keydown.escape.window="resolveOpen = false" @click.self="resolveOpen = false">
                    <div class="vy-modal vy-modal-card" x-show="resolveOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-mt-resolve-t" tabindex="-1" x-trap.noscroll="resolveOpen">
                        <form method="POST" :action="resolve.bulk ? urls.bulkResolve : link(urls.resolve, resolve.id)" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <template x-if="resolve.bulk">
                                <div><template x-for="id in ids()" :key="id"><input type="hidden" name="ticket_ids[]" :value="id"></template></div>
                            </template>
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 12.5l5 5 10-10"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-mt-resolve-t" x-text="resolve.bulk ? 'Resolve ' + count() + ' ' + (count() === 1 ? 'work order' : 'work orders') : 'Mark resolved'">Mark resolved</h3>
                                    <p class="vy-modal-sub" x-text="resolve.bulk ? 'No cost is recorded for these.' : resolve.title"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="resolveOpen = false" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <template x-if="resolve.bulk">
                                    <p class="vy-gl-note vy-mt-first">Each one is marked resolved, and its unit goes back on sale unless another open work order still holds it out of order. Nobody is notified. To record a cost or tell whoever reported it, resolve a work order on its own.</p>
                                </template>
                                <template x-if="! resolve.bulk">
                                    <div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vy-mt-r-notes">What was done <span class="vy-fl-note">· optional</span></label>
                                            <textarea class="vy-input vy-gl-textarea" id="vy-mt-r-notes" name="resolution" rows="3" maxlength="2000" placeholder="e.g. Replaced the trap and seal — tested, no leak."></textarea>
                                        </div>
                                        <div class="vy-gl-fields">
                                            <div class="vy-field">
                                                <label class="vy-field-label" for="vy-mt-r-labor">Labour cost <span class="vy-fl-note" x-text="'· ' + resolve.currency"></span></label>
                                                <input class="vy-input" id="vy-mt-r-labor" type="number" inputmode="decimal" step="0.01" min="0" name="labor" placeholder="0.00">
                                            </div>
                                            <div class="vy-field">
                                                <label class="vy-field-label" for="vy-mt-r-parts">Parts cost <span class="vy-fl-note" x-text="'· ' + resolve.currency"></span></label>
                                                <input class="vy-input" id="vy-mt-r-parts" type="number" inputmode="decimal" step="0.01" min="0" name="parts" placeholder="0.00">
                                            </div>
                                        </div>
                                        <p class="vy-gl-note vy-mt-first">On an owner’s unit, a cost entered here is charged to the owner as a maintenance expense.</p>
                                        {{-- Tell whoever reported it — the same switch as the work order page, on unless turned off. The board's
                                             form used to send nothing here, which the server reads as "do not notify". --}}
                                        <template x-if="resolve.reporter">
                                            <div class="vy-mt-sale">
                                                <label class="vy-gl-switch">
                                                    <input type="checkbox" name="notify" value="1" x-model="notify">
                                                    <span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Notify the front desk</strong><span x-text="'Lets ' + resolve.reporter + ' know it’s fixed and back online'"></span></span>
                                                </label>
                                            </div>
                                        </template>
                                        <template x-if="resolve.oos">
                                            <div class="vy-mt-sale">
                                                <input type="hidden" name="return_to_sale" :value="ret ? '1' : '0'">
                                                <label class="vy-gl-switch">
                                                    <input type="checkbox" x-model="ret">
                                                    <span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong x-text="'Put ' + resolve.unit + ' back on sale'"></strong><span x-text="ret ? 'It goes back on sale now, unless another open work order still holds it.' : 'It stays out of order — lift the block later from the rack.'"></span></span>
                                                </label>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="resolveOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent" x-text="resolve.bulk ? 'Resolve ' + count() : 'Mark resolved'">Mark resolved</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── CANCEL — the honest delete: the work order is kept, as cancelled; nothing is charged ────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="cancelOpen" x-transition.opacity @keydown.escape.window="cancelOpen = false" @click.self="cancelOpen = false">
                    <div class="vy-modal vy-modal-card" x-show="cancelOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-mt-cancel-t" tabindex="-1" x-trap.noscroll="cancelOpen">
                        <form method="POST" :action="link(urls.cancel, cancel.id)" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico is-danger">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-mt-cancel-t">Cancel this work order?</h3>
                                    <p class="vy-modal-sub" x-text="cancel.title"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="cancelOpen = false" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <ul class="vy-mt-facts-list">
                                    <li>It comes off this board and is kept as cancelled, with its history — nothing is deleted.</li>
                                    <li>No cost is recorded and nothing is charged to an owner.</li>
                                    <li x-show="cancel.oos" x-text="cancel.unit + ' goes back on sale, unless another open work order still holds it out of order.'"></li>
                                </ul>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="cancelOpen = false">Keep it</button>
                                <button type="submit" class="vy-btn vy-btn--danger">Cancel work order</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- ── THE DETAIL DRAWER — on the right, from a row's title or its menu (or ?ticket= on arrival) ───────── --}}
            <div class="vy-modal-backdrop vy-drawer-backdrop" x-cloak x-show="detail" x-transition.opacity @keydown.escape.window="closeDetail()" @click.self="closeDetail()">
                <div class="vy-modal vy-modal-card vy-drawer" x-show="detail"
                     x-transition:enter="vy-drawer-move" x-transition:enter-start="vy-drawer-off" x-transition:leave="vy-drawer-move" x-transition:leave-end="vy-drawer-off"
                     role="dialog" aria-modal="true" aria-labelledby="vy-mt-detail-t" tabindex="-1" x-trap.noscroll="detail">
                    <template x-if="detail">
                        <div class="vy-drawer-in">
                            <div class="vy-modal-head">
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-mt-detail-t" x-text="detail.title"></h3>
                                    <p class="vy-modal-sub"><span x-text="detail.unit"></span> · <span x-text="detail.category"></span></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="closeDetail()" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <ol class="vy-mt-steps" aria-label="Progress">
                                    <template x-for="step in steps(detail)" :key="step.label">
                                        <li class="vy-mt-step" :class="step.done && 'is-done'"><span class="vy-mt-step-bar" aria-hidden="true"></span><span x-text="step.label"></span><span class="vy-sr-only" x-text="step.done ? ', done' : ', not yet'"></span></li>
                                    </template>
                                </ol>
                                <div class="vy-mt-tags">
                                    <span class="vy-mt-prio" :class="'is-' + detail.priority" x-text="detail.priority_label + ' priority'"></span>
                                    <span class="vy-tag" :class="'tone-' + detail.status_meta.tone"><span class="vy-dot"></span><span x-text="detail.status_meta.label"></span></span>
                                    <template x-if="detail.oos"><span class="vy-tag tone-risk"><span class="vy-dot"></span>Out of order</span></template>
                                </div>
                                <dl class="vy-mt-facts">
                                    <div><dt>Technician</dt><dd x-text="detail.assignee || 'Unassigned'"></dd></div>
                                    <div><dt>Reported</dt><dd x-text="detail.reported_ago"></dd></div>
                                    <template x-if="detail.sla.label && detail.status !== 'resolved'">
                                        <div><dt>Time to target</dt><dd :class="detail.sla.breached ? 'is-breached' : (detail.sla.due_soon ? 'is-soon' : '')" x-text="detail.sla.label"></dd></div>
                                    </template>
                                    <template x-if="detail.resolved">
                                        <div><dt>Resolved</dt><dd x-text="detail.resolved"></dd></div>
                                    </template>
                                    <template x-if="detail.cost">
                                        <div><dt>Cost</dt><dd><span x-text="detail.cost"></span> <span class="vy-mt-muted" x-text="detail.labor ? '(' + detail.labor + ' labour · ' + (detail.parts_cost || '0') + ' parts)' : ''"></span></dd></div>
                                    </template>
                                </dl>
                                <template x-if="detail.description">
                                    <p class="vy-mt-desc" x-text="detail.description"></p>
                                </template>
                                <template x-if="detail.awaiting_parts">
                                    <p class="vy-mt-part"><span x-text="'Waiting on ' + (detail.part_name || 'a part')"></span><span x-text="detail.part_eta ? ' · arrives ' + detail.part_eta : ''"></span></p>
                                </template>

                                <h4 class="vy-mt-h">Photos</h4>
                                <div class="vy-mt-photos">
                                    <template x-for="photo in detail.photos" :key="photo.id">
                                        <img :src="photo.url" alt="Photo of the issue" class="vy-mt-photo" loading="lazy">
                                    </template>
                                    <template x-if="detail.photos.length === 0"><span class="vy-mt-muted">No photos yet.</span></template>
                                </div>

                                <template x-if="detail.activity && detail.activity.length">
                                    <div>
                                        <h4 class="vy-mt-h">Activity</h4>
                                        <ol class="vy-mt-activity">
                                            <template x-for="a in detail.activity" :key="a.label + a.when">
                                                <li><span x-text="a.label"></span> <span class="vy-mt-muted" x-text="'· ' + a.when"></span></li>
                                            </template>
                                        </ol>
                                    </div>
                                </template>
                            </div>
                            <div class="vy-mt-drawer-foot">
                                @if ($canWrite)
                                    <template x-if="detail.actions.length">
                                        <div class="vy-mt-drawer-acts">
                                            <template x-if="detail.actions.includes('start')">
                                                <form method="POST" :action="link(urls.start, detail.id)" data-guard-submit @submit="stamp($event.target)">
                                                    @csrf
                                                    <input type="hidden" name="return" value="list">
                                                    <input type="hidden" name="return_query" value="">
                                                    <button type="submit" class="vy-btn vy-btn--accent vy-mt-wide">Start work</button>
                                                </form>
                                            </template>
                                            <template x-if="detail.actions.includes('resolve')">
                                                {{-- the main button is the next step: Start work while it has not started, Mark resolved once it has --}}
                                                <button type="button" class="vy-btn vy-mt-wide" :class="detail.actions.includes('start') ? 'vy-btn--ghost' : 'vy-btn--accent'" @click="fromDrawer('resolve')">Mark resolved</button>
                                            </template>
                                            <button type="button" class="vy-btn vy-btn--ghost" @click="fromDrawer('edit')">Edit details</button>
                                            <button type="button" class="vy-btn vy-btn--ghost" @click="fromDrawer('assign')" x-text="detail.assignee ? 'Change technician' : 'Assign technician'">Assign technician</button>
                                            <button type="button" class="vy-btn vy-btn--ghost vy-mt-danger vy-mt-wide" @click="fromDrawer('cancel')">Cancel work order</button>
                                        </div>
                                    </template>
                                    <form method="POST" :action="link(urls.photo, detail.id)" enctype="multipart/form-data" class="vy-mt-photo-form">
                                        @csrf
                                        <label class="vy-btn vy-btn--ghost vy-mt-photo-btn">
                                            Add photo
                                            <input type="file" name="photo" accept="image/*" capture="environment" class="vy-mt-file" onchange="this.form.submit()">
                                        </label>
                                    </form>
                                @endif
                                <a class="vy-btn vy-btn--ghost" :href="link(urls.show, detail.id)">Open full record</a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </x-operator.shell>

    {{-- The page's controller — the shared list behaviour (selection, the row menu, search and the smooth links, in
         partials/operator-list-script) plus this page's own dialogs and drawer. Every link and form works without it. --}}
    @include('partials.operator-list-script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vyMaint', (boot) => Object.assign(window.vyListBehaviour(boot, { empty: 'No work orders in this view' }), {
                nwoOpen: false, nwoOoo: false,
                editOpen: false, edit: {},
                assignOpen: false, assign: { bulk: false, id: '', title: '', current: '' }, pick: null,
                resolveOpen: false, resolve: { bulk: false, id: '', title: '', unit: '', oos: false, currency: '', reporter: null }, ret: true, notify: true,
                cancelOpen: false, cancel: { id: '', title: '', unit: '', oos: false },
                detail: null,

                start() {
                    this.edit = this.blank();
                    this.detail = boot.detail;
                    if (boot.retry) {
                        const row = this.row(boot.retry.id);
                        this.edit = Object.assign(this.blank(), boot.retry, { ref: row ? row.unit + ' · ' + row.category : '' });
                        this.editOpen = true;
                    }
                },
                blank() { return { id: '', ref: '', title: '', description: '', category: 'other', priority: 'normal' }; },

                // This page's rows, read from the JSON beside the table — it is swapped with the table, so it always
                // describes the rows on screen.
                row(id) {
                    const island = this.find('[data-mt-rows]');
                    if (! island) return null;
                    try { return JSON.parse(island.textContent)[id] || null; } catch (e) { return null; }
                },
                can(action) { return !! (this.menu.row && this.menu.row.actions && this.menu.row.actions.indexOf(action) !== -1); },
                // A form started here comes back to the board as it is now (the server rebuilds this from an allow-list).
                stamp(form) { const field = form.querySelector('input[name="return_query"]'); if (field) field.value = location.search.slice(1); },

                openDetail(id) { this.closeMenu(false); this.detail = this.row(id); },
                closeDetail() {
                    this.detail = null;
                    const url = new URL(location.href);
                    if (url.searchParams.has('ticket')) { url.searchParams.delete('ticket'); history.replaceState(null, '', url); }
                },
                steps(d) {
                    return [
                        { label: 'Logged', done: true },
                        { label: 'Assigned', done: !! d.assignee_id },
                        { label: 'In progress', done: d.status === 'in_progress' || d.status === 'resolved' },
                        { label: 'Resolved', done: d.status === 'resolved' },
                    ];
                },

                // From the row menu: close it (focus goes back to the row's button, so a dialog closing returns it there),
                // then open. From the drawer: close the drawer first, so two focus traps never hold the page at once.
                menuAct(action) { const row = this.menu.row; this.closeMenu(true); this.act(action, row); },
                fromDrawer(action) { const row = this.detail; this.closeDetail(); this.$nextTick(() => this.act(action, row)); },
                act(action, row) {
                    if (! row) return;
                    const title = row.title + ' · ' + row.unit;
                    if (action === 'details') {
                        this.detail = row;
                    } else if (action === 'edit') {
                        this.edit = { id: row.id, ref: row.unit + ' · ' + row.category, title: row.title, description: row.description,
                            category: row.category_value, priority: row.priority };
                        this.editOpen = true;
                    } else if (action === 'assign') {
                        this.assign = { bulk: false, id: row.id, title, current: row.assignee_id || '' };
                        this.pick = this.assign.current;
                        this.assignOpen = true;
                    } else if (action === 'resolve') {
                        this.resolve = { bulk: false, id: row.id, title, unit: row.unit, oos: row.oos, currency: row.currency, reporter: row.reporter };
                        this.ret = true;
                        this.notify = true;
                        this.resolveOpen = true;
                    } else if (action === 'cancel') {
                        this.cancel = { id: row.id, title, unit: row.unit, oos: row.oos };
                        this.cancelOpen = true;
                    }
                },
                bulkAssign() { this.assign = { bulk: true, id: '', title: '', current: null }; this.pick = null; this.assignOpen = true; },
                bulkResolve() { this.resolve = { bulk: true, id: '', title: '', unit: '', oos: false, currency: '', reporter: null }; this.resolveOpen = true; },
            }));
        });
    </script>
</div>

@once
    <style>
        [x-cloak] { display: none !important; }
    </style>
@endonce
