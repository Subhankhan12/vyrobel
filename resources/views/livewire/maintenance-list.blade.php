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
    <x-operator.shell active="maintenance" :title="__('livewire/maintenance-list.title')" :subtitle="__('livewire/maintenance-list.subtitle')" :viewer="$viewer">

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
                    <div class="vy-dk-label">{{ __('livewire/maintenance-list.kpi_active') }}</div>
                    <div class="vy-dk-val">{{ $kpis['open'] }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/maintenance-list.kpi_active_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/maintenance-list.kpi_urgent') }}</div>
                    <div class="vy-dk-val">{{ $kpis['urgent'] }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/maintenance-list.kpi_urgent_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/maintenance-list.awaiting_parts') }}</div>
                    <div class="vy-dk-val">{{ $kpis['parts'] }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/maintenance-list.kpi_parts_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/maintenance-list.out_of_order') }}</div>
                    <div class="vy-dk-val">{{ $kpis['ooo'] }}</div>
                    <div class="vy-dk-sub">{{ trans_choice('livewire/maintenance-list.kpi_ooo_sub', $kpis['ooo']) }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/maintenance-list.resolved') }}</div>
                    <div class="vy-dk-val">{{ $kpis['done'] }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/maintenance-list.kpi_done_sub') }}</div>
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
                    <label class="vy-sr-only" for="vy-mt-q">{{ __('livewire/maintenance-list.search_label') }}</label>
                    <input id="vy-mt-q" class="vy-gl-search-in" type="search" name="q" value="{{ $query }}" x-model="q"
                           @input.debounce.300ms="search()" placeholder="{{ __('livewire/maintenance-list.search_placeholder') }}" maxlength="80" autocomplete="off" spellcheck="false">
                    <button type="button" class="vy-gl-clear" x-show="q !== ''" x-cloak @click="clearSearch()" aria-label="{{ __('livewire/maintenance-list.clear_search') }}">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </form>

                <div class="vy-gl-tools">
                    {{-- WAVE 2 · #18 — both pages are gated maintenance.manage, so the links are too ("never invite a 403"). --}}
                    @if ($canManage)
                        <a class="vy-btn vy-btn--ghost" href="{{ route('app.maintenance.costs') }}">{{ __('livewire/maintenance-list.costs_report') }}</a>
                        <a class="vy-btn vy-btn--ghost" href="{{ route('app.maintenance.settings') }}">{{ __('livewire/maintenance-list.settings') }}</a>
                        <button type="button" class="vy-btn vy-btn--ghost vy-gl-select" :class="selectMode && 'is-on'" :aria-pressed="selectMode ? 'true' : 'false'" @click="toggleMode()">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/></svg>
                            <span x-text="selectMode ? @js(__('common.done')) : @js(__('livewire/maintenance-list.select'))">{{ __('livewire/maintenance-list.select') }}</span>
                        </button>
                    @endif
                    @if ($canWrite)
                        <button type="button" class="vy-btn vy-btn--accent" @click="nwoOpen = true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            {{ __('livewire/maintenance-list.new_work_order') }}
                        </button>
                    @endif
                </div>

                <div class="vy-gl-chips vy-mt-chips" data-gl-region="chips" role="group" aria-label="{{ __('livewire/maintenance-list.chips_label') }}">
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
                            <h2 class="vy-gl-empty-title">{{ __('livewire/maintenance-list.empty_title') }}</h2>
                            <p class="vy-gl-empty-text">{{ __('livewire/maintenance-list.empty_text') }}</p>
                            @if ($canWrite)
                                <button type="button" class="vy-btn vy-btn--accent" @click="nwoOpen = true">{{ __('livewire/maintenance-list.new_work_order') }}</button>
                            @endif
                        </div>
                    @elseif ($rows->total() === 0)
                        <div class="vy-card vy-gl-empty vy-fade">
                            <span class="vy-gl-empty-ico" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                            </span>
                            <h2 class="vy-gl-empty-title">
                                @if ($query !== '') {{ __('livewire/maintenance-list.no_match', ['query' => $query]) }} @else {{ __('livewire/maintenance-list.empty_view') }} @endif
                            </h2>
                            <p class="vy-gl-empty-text">{{ trans_choice('livewire/maintenance-list.no_match_text', $windowTotal) }}</p>
                            <a class="vy-btn vy-btn--ghost" href="{{ route('app.maintenance') }}" data-gl-key="clear-all">{{ __('livewire/maintenance-list.clear_all') }}</a>
                        </div>
                    @else
                        <div class="vy-card vy-gl-card vy-fade">
                            <div class="vy-gl-progress" aria-hidden="true"></div>
                            <div class="vy-gl-scroll">
                                <table class="vy-gl-table vy-mt-table">
                                    <caption class="vy-sr-only">{{ __('livewire/maintenance-list.caption', ['sorted' => $sortedBy]) }}</caption>
                                    <thead>
                                        <tr>
                                            @if ($canManage)
                                                <th class="vy-gl-check" x-show="selectMode" x-cloak>
                                                    <input type="checkbox" aria-label="{{ __('livewire/maintenance-list.select_page') }}"
                                                           :checked="allOnPage()" x-effect="$el.indeterminate = someOnPage()" @change="selectPage($event.target.checked)">
                                                </th>
                                            @endif
                                            <th>{{ __('livewire/maintenance-list.col_work_order') }}</th>
                                            <th aria-sort="{{ $sorts['unit']['aria'] }}">
                                                <a class="vy-gl-sort {{ $sorts['unit']['active'] ? 'is-on is-'.$sorts['unit']['dir'] : '' }}" href="{{ $sorts['unit']['href'] }}" data-gl-key="sort-unit">{{ __('livewire/maintenance-list.unit') }}<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                            </th>
                                            <th aria-sort="{{ $sorts['priority']['aria'] }}">
                                                <a class="vy-gl-sort {{ $sorts['priority']['active'] ? 'is-on is-'.$sorts['priority']['dir'] : '' }}" href="{{ $sorts['priority']['href'] }}" data-gl-key="sort-priority">{{ __('livewire/maintenance-list.priority') }}<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                            </th>
                                            <th>{{ __('common.status') }}</th>
                                            <th class="vy-mt-c-who">{{ __('livewire/maintenance-list.assigned_to') }}</th>
                                            <th aria-sort="{{ $sorts['due']['aria'] }}">
                                                <a class="vy-gl-sort {{ $sorts['due']['active'] ? 'is-on is-'.$sorts['due']['dir'] : '' }}" href="{{ $sorts['due']['href'] }}" data-gl-key="sort-due">{{ __('livewire/maintenance-list.time_to_target') }}<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                            </th>
                                            <th aria-sort="{{ $sorts['reported']['aria'] }}">
                                                <a class="vy-gl-sort {{ $sorts['reported']['active'] ? 'is-on is-'.$sorts['reported']['dir'] : '' }}" href="{{ $sorts['reported']['href'] }}" data-gl-key="sort-reported">{{ __('livewire/maintenance-list.reported') }}<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                            </th>
                                            <th class="vy-gl-actcol"><span class="vy-sr-only">{{ __('common.actions') }}</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rows as $row)
                                            <tr data-gl-id="{{ $row['id'] }}" data-mt-actions="{{ implode(' ', $row['actions']) }}" :class="sel['{{ $row['id'] }}'] && 'is-selected'">
                                                @if ($canManage)
                                                    <td class="vy-gl-check" x-show="selectMode" x-cloak>
                                                        <input type="checkbox" x-model="sel['{{ $row['id'] }}']" aria-label="{{ __('livewire/maintenance-list.select_row', ['title' => $row['title']]) }}">
                                                    </td>
                                                @endif
                                                <td class="vy-mt-ordercell">
                                                    <div class="vy-gl-nameline">
                                                        <button type="button" class="vy-gl-name vy-rowopen" @click="openDetail('{{ $row['id'] }}')">{{ $row['title'] }}</button>
                                                        @if ($row['oos']) <span class="vy-tag tone-risk"><span class="vy-dot"></span>{{ __('livewire/maintenance-list.out_of_order') }}</span> @endif
                                                        @if ($row['awaiting_parts']) <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ __('livewire/maintenance-list.awaiting_parts') }}</span> @endif
                                                    </div>
                                                    <div class="vy-gl-contact">{{ $row['category'] }}</div>
                                                </td>
                                                <td class="vy-mt-c-unit" data-label="{{ __('livewire/maintenance-list.unit') }}"><span class="vy-mt-unit">{{ $row['unit'] }}</span></td>
                                                <td class="vy-mt-c-prio" data-label="{{ __('livewire/maintenance-list.priority') }}"><span class="vy-mt-prio is-{{ $row['priority'] }}">{{ $row['priority_label'] }}</span></td>
                                                <td class="vy-mt-c-status" data-label="{{ __('common.status') }}"><span class="vy-tag tone-{{ $row['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['status_meta']['label'] }}</span></td>
                                                <td class="vy-mt-c-who" data-label="{{ __('livewire/maintenance-list.assigned_to') }}">
                                                    @if ($row['assignee'] !== null)
                                                        <span class="vy-mt-tech"><span class="vy-gl-avatar tone-{{ strlen($row['assignee']) % 4 + 1 }}" aria-hidden="true">{{ $row['assignee_initials'] }}</span>{{ $row['assignee'] }}</span>
                                                    @else
                                                        <span class="vy-gl-none">{{ __('livewire/maintenance-list.unassigned') }}</span>
                                                    @endif
                                                </td>
                                                <td class="vy-mt-c-due" data-label="{{ __('livewire/maintenance-list.time_to_target') }}">
                                                    @if ($row['status'] === 'resolved')
                                                        <span class="vy-gl-none">{{ __('livewire/maintenance-list.resolved_on', ['date' => $row['resolved']]) }}</span>
                                                    @elseif ($row['sla']['label'] !== '')
                                                        {{-- AXIS 3 (DataConditionalContrastTest): both warning colours are the AA text tokens. --}}
                                                        <span class="vy-mt-due" style="color: {{ $row['sla']['breached'] ? 'var(--vy-accent-text)' : ($row['sla']['due_soon'] ? 'var(--vy-amber-text)' : 'var(--vy-ink)') }};">{{ $row['sla']['label'] }}</span>
                                                    @else
                                                        <span class="vy-gl-none">—</span>
                                                    @endif
                                                </td>
                                                <td class="vy-mt-c-when" data-label="{{ __('livewire/maintenance-list.reported') }}"><span class="vy-gl-when">{{ $row['reported_ago'] }}</span></td>
                                                <td class="vy-gl-actcol">
                                                    <button type="button" class="vy-gl-more" aria-label="{{ __('livewire/maintenance-list.row_actions', ['title' => $row['title']]) }}" aria-controls="vy-mt-menu"
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
                                <p class="vy-gl-summary" data-gl-summary tabindex="-1">{!! trans_choice('livewire/maintenance-list.summary', $rows->total(), ['first' => e($rows->firstItem()), 'last' => e($rows->lastItem()), 'count' => e($rows->total()), 'sorted' => e($sortedBy)]) !!}</p>
                                <div class="vy-gl-paging">
                                    <span class="vy-gl-rows-label" id="vy-mt-rows">{{ __('livewire/maintenance-list.rows') }}</span>
                                    <div class="vy-gl-sizes" role="group" aria-labelledby="vy-mt-rows">
                                        @foreach ($sizes as $size)
                                            <a class="vy-gl-size {{ $size['active'] ? 'is-on' : '' }}" href="{{ $size['href'] }}" data-gl-key="per-{{ $size['size'] }}" @if ($size['active']) aria-current="true" @endif>{{ $size['size'] }}</a>
                                        @endforeach
                                    </div>
                                    @if ($rows->lastPage() > 1)
                                        <nav class="vy-gl-pages" aria-label="{{ __('livewire/maintenance-list.pages_label') }}">
                                            @if ($pages['prev'] !== null)
                                                <a class="vy-gl-page" href="{{ $pages['prev'] }}" data-gl-key="page-prev" rel="prev" aria-label="{{ __('livewire/maintenance-list.prev_page') }}">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                                                </a>
                                            @else
                                                <span class="vy-gl-page is-off" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></span>
                                            @endif
                                            <span class="vy-gl-pagepos">{{ __('livewire/maintenance-list.page_of', ['current' => $rows->currentPage(), 'last' => $rows->lastPage()]) }}</span>
                                            @if ($pages['next'] !== null)
                                                <a class="vy-gl-page" href="{{ $pages['next'] }}" data-gl-key="page-next" rel="next" aria-label="{{ __('livewire/maintenance-list.next_page') }}">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                                                </a>
                                            @else
                                                <span class="vy-gl-page is-off" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></span>
                                            @endif
                                        </nav>
                                    @endif
                                </div>
                                <p class="vy-mt-window">{{ __('livewire/maintenance-list.window_note') }}@if ($canManage) {!! __('livewire/maintenance-list.window_costs', ['url' => e(route('app.maintenance.costs'))]) !!}@endif</p>
                            </div>
                        </div>
                        {{-- Each row on this page, once — what the row menu and the drawer open with. JSON, never run. --}}
                        <script type="application/json" data-mt-rows>@json($rowsById)</script>
                    @endif
                </div>

                {{-- ── The rail — the out-of-order hero, technicians and parts (the whole board, not the filter) ─── --}}
                <aside class="vy-mt-rail" aria-label="{{ __('livewire/maintenance-list.rail_label') }}">
                    <div class="vy-tonight vy-mt-oos vy-fade">
                        <div class="vy-dk-label vy-mt-oos-label">{{ __('livewire/maintenance-list.out_of_order') }}</div>
                        <p class="vy-mt-oos-count">{!! trans_choice('livewire/maintenance-list.oos_blocked', $oosHero['count'], ['count' => e($oosHero['count'])]) !!}</p>
                        <p class="vy-mt-oos-units">{{ $oosHero['units'] !== '' ? $oosHero['units'] : __('livewire/maintenance-list.oos_none') }}</p>
                        @if ($oosHero['on_hold'] !== null)
                            <div class="vy-mt-oos-hold">
                                <strong>{{ __('livewire/maintenance-list.per_night', ['amount' => $oosHero['on_hold']]) }}</strong>
                                {{-- tonight's standard rate for each blocked unit — what they are not selling tonight, not a "revenue" figure (2026-10-05) --}}
                                <span>{{ __('livewire/maintenance-list.tonight_rate') }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="vy-card vy-mt-panel vy-fade">
                        <h2 class="vy-card-title">{{ __('livewire/maintenance-list.technicians') }}</h2>
                        @forelse ($technicians as $tech)
                            <div class="vy-mt-panel-row">
                                <span class="vy-mt-tech"><span class="vy-gl-avatar tone-{{ strlen($tech['name']) % 4 + 1 }}" aria-hidden="true">{{ $tech['initials'] }}</span><span>
                                    <span class="vy-mt-panel-name">{{ $tech['name'] }}</span>
                                    <span class="vy-mt-panel-sub">@if ($tech['urgent'] > 0) {{ __('livewire/maintenance-list.tech_open_urgent', ['open' => $tech['load'], 'urgent' => $tech['urgent']]) }} @else {{ __('livewire/maintenance-list.tech_open', ['open' => $tech['load']]) }} @endif</span>
                                </span></span>
                            </div>
                        @empty
                            <p class="vy-mt-panel-empty">{{ __('livewire/maintenance-list.tech_empty') }}</p>
                        @endforelse
                    </div>

                    <div class="vy-card vy-mt-panel vy-fade">
                        <h2 class="vy-card-title">{{ __('livewire/maintenance-list.parts_on_order') }}</h2>
                        @forelse ($partsOnOrder as $part)
                            <div class="vy-mt-panel-row">
                                <span>
                                    <span class="vy-mt-panel-name">{{ $part['part_name'] ?? __('livewire/maintenance-list.part_fallback') }}</span>
                                    <span class="vy-mt-panel-sub">@if ($part['part_eta']) {{ __('livewire/maintenance-list.part_arrives', ['unit' => $part['unit'], 'date' => $part['part_eta']]) }} @else {{ $part['unit'] }} @endif</span>
                                </span>
                                <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ __('livewire/maintenance-list.on_order') }}</span>
                            </div>
                        @empty
                            <p class="vy-mt-panel-empty">{{ __('livewire/maintenance-list.nothing_on_order') }}</p>
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
                    {{ __('livewire/maintenance-list.open_details') }}
                </button>
                <a class="vy-gl-menu-item" :href="menu.row ? link(urls.show, menu.row.id) : '#'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 4h6v6M20 4l-8 8M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
                    {{ __('livewire/maintenance-list.open_full_record') }}
                </a>
                @if ($canWrite)
                    <span class="vy-gl-menu-sep" x-show="can('edit')" aria-hidden="true"></span>
                    <button type="button" class="vy-gl-menu-item" x-show="can('edit')" @click="menuAct('edit')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                        {{ __('livewire/maintenance-list.edit_details') }}
                    </button>
                    <button type="button" class="vy-gl-menu-item" x-show="can('assign')" @click="menuAct('assign')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="8" r="3.5"/><path d="M3.5 20c0-3.6 2.9-6.5 6.5-6.5 1.4 0 2.7.4 3.7 1.2M16 17l2 2 4-4"/></svg>
                        <span x-text="menu.row && menu.row.assignee ? @js(__('livewire/maintenance-list.change_technician')) : @js(__('livewire/maintenance-list.assign_technician'))">{{ __('livewire/maintenance-list.assign_technician') }}</span>
                    </button>
                    <form method="POST" x-show="can('start')" :action="menu.row ? link(urls.start, menu.row.id) : '#'" data-guard-submit @submit="stamp($event.target)">
                        @csrf
                        <input type="hidden" name="return" value="list">
                        <input type="hidden" name="return_query" value="">
                        <button type="submit" class="vy-gl-menu-item">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M10 8.5l5 3.5-5 3.5z"/></svg>
                            {{ __('livewire/maintenance-list.start_work') }}
                        </button>
                    </form>
                    <button type="button" class="vy-gl-menu-item" x-show="can('resolve')" @click="menuAct('resolve')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 12.5l5 5 10-10"/></svg>
                        {{ __('livewire/maintenance-list.mark_resolved') }}
                    </button>
                    <button type="button" class="vy-gl-menu-item is-danger" x-show="can('cancel')" @click="menuAct('cancel')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/></svg>
                        {{ __('livewire/maintenance-list.cancel_work_order') }}
                    </button>
                @endif
            </div>

            {{-- ── The bulk bar — select mode, one or more selected (maintenance.manage, like every bulk route) ───── --}}
            @if ($canManage)
                <div class="vy-gl-bulk" x-show="selectMode && count()" x-cloak x-transition role="region" aria-label="{{ __('livewire/maintenance-list.bulk_label') }}">
                    <span class="vy-gl-bulk-count">{!! __('livewire/maintenance-list.bulk_selected') !!}</span>
                    <button type="button" class="vy-gl-bulk-clear" @click="clear()">{{ __('common.clear') }}</button>
                    <span class="vy-gl-bulk-sep" aria-hidden="true"></span>
                    {{-- Export — a CSV of exactly the selected work orders (no guest data on a work order) --}}
                    <form method="POST" action="{{ route('app.maintenance.bulk.export') }}">
                        @csrf
                        <template x-for="id in ids()" :key="id"><input type="hidden" name="ticket_ids[]" :value="id"></template>
                        <button type="submit" class="vy-gl-bulk-act">{{ __('common.export') }}</button>
                    </form>
                    @if ($canWrite)
                        <button type="button" class="vy-gl-bulk-act" @click="bulkAssign()">{{ __('livewire/maintenance-list.assign') }}</button>
                        <button type="button" class="vy-gl-bulk-act" @click="bulkResolve()">{{ __('livewire/maintenance-list.bulk_resolve') }}</button>
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
                                    <h3 class="vy-modal-title" id="vy-mt-new-t">{{ __('livewire/maintenance-list.new_work_order') }}</h3>
                                    <p class="vy-modal-sub">{{ __('livewire/maintenance-list.new_sub') }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="nwoOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-mt-n-title">{{ __('livewire/maintenance-list.issue') }}</label>
                                    <input class="vy-input" id="vy-mt-n-title" type="text" name="title" maxlength="160" required placeholder="{{ __('livewire/maintenance-list.issue_placeholder') }}" autocomplete="off" autofocus>
                                </div>
                                @if ($unitOptions === [])
                                    <p class="vy-gl-errors">{{ __('livewire/maintenance-list.no_units') }}</p>
                                @else
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-mt-n-unit">{{ __('livewire/maintenance-list.unit') }}</label>
                                        <x-select name="unit_id" id="vy-mt-n-unit" :options="$unitOptions" :value="$unitOptions[0]['value']" :search-placeholder="__('livewire/maintenance-list.search_units')" :empty-text="__('livewire/maintenance-list.no_unit_match')" />
                                    </div>
                                @endif
                                <div class="vy-mt-cats">
                                    <x-operator.choices name="category" :label="__('livewire/maintenance-list.category')" :options="$categoryChoices" selected="plumbing" />
                                </div>
                                <x-operator.choices name="priority" :label="__('livewire/maintenance-list.priority')" :options="$priorityChoices" selected="normal" variant="segmented" />
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-mt-n-desc">{!! __('livewire/maintenance-list.description_optional') !!}</label>
                                    <textarea class="vy-input vy-gl-textarea" id="vy-mt-n-desc" name="description" rows="3" maxlength="2000" placeholder="{{ __('livewire/maintenance-list.description_placeholder') }}"></textarea>
                                </div>
                                <label class="vy-gl-switch">
                                    <input type="checkbox" name="takes_out_of_service" value="1" x-model="nwoOoo">
                                    <span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/maintenance-list.ooo_switch') }}</strong><span>{{ __('livewire/maintenance-list.ooo_switch_hint') }}</span></span>
                                </label>
                                <p class="vy-mt-warn" x-show="nwoOoo" x-cloak>{{ __('livewire/maintenance-list.ooo_warn') }}</p>
                                <p class="vy-gl-note">{{ __('livewire/maintenance-list.photos_note') }}</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="nwoOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent" @if ($unitOptions === []) disabled @endif><span x-text="nwoOoo ? @js(__('livewire/maintenance-list.create_and_block')) : @js(__('livewire/maintenance-list.create_work_order'))">{{ __('livewire/maintenance-list.create_work_order') }}</span></button>
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
                                    <h3 class="vy-modal-title" id="vy-mt-edit-t">{{ __('livewire/maintenance-list.edit_title') }}</h3>
                                    <p class="vy-modal-sub" x-text="edit.ref"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="editOpen = false" aria-label="{{ __('common.close') }}">
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
                                    <label class="vy-field-label" for="vy-mt-e-title">{{ __('livewire/maintenance-list.issue') }}</label>
                                    <input class="vy-input" id="vy-mt-e-title" type="text" name="title" maxlength="160" required x-model="edit.title" autocomplete="off">
                                </div>
                                <div class="vy-mt-cats">
                                    <x-operator.choices name="category" :label="__('livewire/maintenance-list.category')" :options="$categoryChoices" model="edit.category" />
                                </div>
                                <x-operator.choices name="priority" :label="__('livewire/maintenance-list.priority')" :options="$priorityChoices" model="edit.priority" variant="segmented" />
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-mt-e-desc">{!! __('livewire/maintenance-list.description_optional') !!}</label>
                                    <textarea class="vy-input vy-gl-textarea" id="vy-mt-e-desc" name="description" rows="3" maxlength="2000" x-model="edit.description"></textarea>
                                </div>
                                <p class="vy-gl-note">{{ __('livewire/maintenance-list.edit_note') }}</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="editOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent">{{ __('common.save_changes') }}</button>
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
                                    <h3 class="vy-modal-title" id="vy-mt-assign-t">{{ __('livewire/maintenance-list.assign_technician') }}</h3>
                                    <p class="vy-modal-sub" x-text="assign.bulk ? counts.assign_selected[count()] : assign.title"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="assignOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <fieldset class="vy-gl-fieldset">
                                    <legend class="vy-field-label">{{ __('livewire/maintenance-list.technician') }}</legend>
                                    <div class="vy-mt-people">
                                    @foreach ($staff as $member)
                                        <label class="vy-mt-person">
                                            <input type="radio" name="user_id" value="{{ $member['id'] }}" x-model="pick">
                                            <span class="vy-mt-person-face">
                                                <span class="vy-gl-avatar tone-{{ strlen($member['name']) % 4 + 1 }}" aria-hidden="true">{{ $member['initials'] }}</span>
                                                <span class="vy-mt-person-name">{{ $member['name'] }}</span>
                                                <span class="vy-mt-person-now" x-show="! assign.bulk && assign.current === '{{ $member['id'] }}'" x-cloak>{{ __('livewire/maintenance-list.current') }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                    <label class="vy-mt-person">
                                        <input type="radio" name="user_id" value="" x-model="pick">
                                        <span class="vy-mt-person-face">
                                            <span class="vy-gl-avatar tone-erased" aria-hidden="true">–</span>
                                            <span class="vy-mt-person-name">{{ __('livewire/maintenance-list.no_one') }}</span>
                                        </span>
                                    </label>
                                    </div>
                                </fieldset>
                                <p class="vy-gl-note">{{ __('livewire/maintenance-list.assign_note') }}</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="assignOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent" :disabled="pick === null || (! assign.bulk && pick === assign.current)">{{ __('livewire/maintenance-list.assign') }}</button>
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
                                    <h3 class="vy-modal-title" id="vy-mt-resolve-t" x-text="resolve.bulk ? counts.resolve_n_title[count()] : @js(__('livewire/maintenance-list.mark_resolved'))">{{ __('livewire/maintenance-list.mark_resolved') }}</h3>
                                    <p class="vy-modal-sub" x-text="resolve.bulk ? @js(__('livewire/maintenance-list.resolve_bulk_sub')) : resolve.title"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="resolveOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <template x-if="resolve.bulk">
                                    <p class="vy-gl-note vy-mt-first">{{ __('livewire/maintenance-list.resolve_bulk_note') }}</p>
                                </template>
                                <template x-if="! resolve.bulk">
                                    <div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vy-mt-r-notes">{!! __('livewire/maintenance-list.what_was_done') !!}</label>
                                            <textarea class="vy-input vy-gl-textarea" id="vy-mt-r-notes" name="resolution" rows="3" maxlength="2000" placeholder="{{ __('livewire/maintenance-list.what_was_done_placeholder') }}"></textarea>
                                        </div>
                                        <div class="vy-gl-fields">
                                            <div class="vy-field">
                                                <label class="vy-field-label" for="vy-mt-r-labor">{{ __('livewire/maintenance-list.labour_cost') }} <span class="vy-fl-note" x-text="'· ' + resolve.currency"></span></label>
                                                <input class="vy-input" id="vy-mt-r-labor" type="number" inputmode="decimal" step="0.01" min="0" name="labor" placeholder="0.00">
                                            </div>
                                            <div class="vy-field">
                                                <label class="vy-field-label" for="vy-mt-r-parts">{{ __('livewire/maintenance-list.parts_cost') }} <span class="vy-fl-note" x-text="'· ' + resolve.currency"></span></label>
                                                <input class="vy-input" id="vy-mt-r-parts" type="number" inputmode="decimal" step="0.01" min="0" name="parts" placeholder="0.00">
                                            </div>
                                        </div>
                                        <p class="vy-gl-note vy-mt-first">{{ __('livewire/maintenance-list.owner_charge_note') }}</p>
                                        {{-- Tell whoever reported it — the same switch as the work order page, on unless turned off. The board's
                                             form used to send nothing here, which the server reads as "do not notify". --}}
                                        <template x-if="resolve.reporter">
                                            <div class="vy-mt-sale">
                                                <label class="vy-gl-switch">
                                                    <input type="checkbox" name="notify" value="1" x-model="notify">
                                                    <span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/maintenance-list.notify_front_desk') }}</strong><span x-text="@js(__('livewire/maintenance-list.notify_hint')).replace(':name', () => resolve.reporter)"></span></span>
                                                </label>
                                            </div>
                                        </template>
                                        <template x-if="resolve.oos">
                                            <div class="vy-mt-sale">
                                                <input type="hidden" name="return_to_sale" :value="ret ? '1' : '0'">
                                                <label class="vy-gl-switch">
                                                    <input type="checkbox" x-model="ret">
                                                    <span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong x-text="@js(__('livewire/maintenance-list.back_on_sale')).replace(':unit', () => resolve.unit)"></strong><span x-text="ret ? @js(__('livewire/maintenance-list.back_on_sale_yes')) : @js(__('livewire/maintenance-list.back_on_sale_no'))"></span></span>
                                                </label>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="resolveOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent" x-text="resolve.bulk ? @js(__('livewire/maintenance-list.resolve_count')).replace(':n', count()) : @js(__('livewire/maintenance-list.mark_resolved'))">{{ __('livewire/maintenance-list.mark_resolved') }}</button>
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
                                    <h3 class="vy-modal-title" id="vy-mt-cancel-t">{{ __('livewire/maintenance-list.cancel_title') }}</h3>
                                    <p class="vy-modal-sub" x-text="cancel.title"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="cancelOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <ul class="vy-mt-facts-list">
                                    <li>{{ __('livewire/maintenance-list.cancel_fact_kept') }}</li>
                                    <li>{{ __('livewire/maintenance-list.cancel_fact_no_cost') }}</li>
                                    <li x-show="cancel.oos" x-text="@js(__('livewire/maintenance-list.cancel_fact_sale')).replace(':unit', () => cancel.unit)"></li>
                                </ul>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="cancelOpen = false">{{ __('livewire/maintenance-list.keep_it') }}</button>
                                <button type="submit" class="vy-btn vy-btn--danger">{{ __('livewire/maintenance-list.cancel_work_order') }}</button>
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
                                <button type="button" class="vy-modal-x" @click="closeDetail()" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <ol class="vy-mt-steps" aria-label="{{ __('livewire/maintenance-list.progress_label') }}">
                                    <template x-for="step in steps(detail)" :key="step.code">
                                        <li class="vy-mt-step" :class="step.done && 'is-done'"><span class="vy-mt-step-bar" aria-hidden="true"></span><span x-text="step.label"></span><span class="vy-sr-only" x-text="step.done ? @js(__('livewire/maintenance-list.step_done')) : @js(__('livewire/maintenance-list.step_not_yet'))"></span></li>
                                    </template>
                                </ol>
                                <div class="vy-mt-tags">
                                    <span class="vy-mt-prio" :class="'is-' + detail.priority" x-text="@js(__('livewire/maintenance-list.priority_tag')).replace(':priority', () => detail.priority_label)"></span>
                                    <span class="vy-tag" :class="'tone-' + detail.status_meta.tone"><span class="vy-dot"></span><span x-text="detail.status_meta.label"></span></span>
                                    <template x-if="detail.oos"><span class="vy-tag tone-risk"><span class="vy-dot"></span>{{ __('livewire/maintenance-list.out_of_order') }}</span></template>
                                </div>
                                <dl class="vy-mt-facts">
                                    <div><dt>{{ __('livewire/maintenance-list.technician') }}</dt><dd x-text="detail.assignee || @js(__('livewire/maintenance-list.unassigned'))"></dd></div>
                                    <div><dt>{{ __('livewire/maintenance-list.reported') }}</dt><dd x-text="detail.reported_ago"></dd></div>
                                    <template x-if="detail.sla.label && detail.status !== 'resolved'">
                                        <div><dt>{{ __('livewire/maintenance-list.time_to_target') }}</dt><dd :class="detail.sla.breached ? 'is-breached' : (detail.sla.due_soon ? 'is-soon' : '')" x-text="detail.sla.label"></dd></div>
                                    </template>
                                    <template x-if="detail.resolved">
                                        <div><dt>{{ __('livewire/maintenance-list.resolved') }}</dt><dd x-text="detail.resolved"></dd></div>
                                    </template>
                                    <template x-if="detail.cost">
                                        <div><dt>{{ __('livewire/maintenance-list.cost') }}</dt><dd><span x-text="detail.cost"></span> <span class="vy-mt-muted" x-text="detail.labor ? @js(__('livewire/maintenance-list.cost_breakdown')).replace(':parts', () => (detail.parts_cost || '0')).replace(':labor', () => detail.labor) : ''"></span></dd></div>
                                    </template>
                                </dl>
                                <template x-if="detail.description">
                                    <p class="vy-mt-desc" x-text="detail.description"></p>
                                </template>
                                <template x-if="detail.awaiting_parts">
                                    <p class="vy-mt-part"><span x-text="(detail.part_eta ? @js(__('livewire/maintenance-list.waiting_on_arrives')).replace(':date', () => detail.part_eta) : @js(__('livewire/maintenance-list.waiting_on'))).replace(':part', () => (detail.part_name || @js(__('livewire/maintenance-list.a_part'))))"></span></p>
                                </template>

                                <h4 class="vy-mt-h">{{ __('livewire/maintenance-list.photos') }}</h4>
                                <div class="vy-mt-photos">
                                    <template x-for="photo in detail.photos" :key="photo.id">
                                        <img :src="photo.url" alt="{{ __('livewire/maintenance-list.photo_alt') }}" class="vy-mt-photo" loading="lazy">
                                    </template>
                                    <template x-if="detail.photos.length === 0"><span class="vy-mt-muted">{{ __('livewire/maintenance-list.no_photos') }}</span></template>
                                </div>

                                <template x-if="detail.activity && detail.activity.length">
                                    <div>
                                        <h4 class="vy-mt-h">{{ __('livewire/maintenance-list.activity') }}</h4>
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
                                                    <button type="submit" class="vy-btn vy-btn--accent vy-mt-wide">{{ __('livewire/maintenance-list.start_work') }}</button>
                                                </form>
                                            </template>
                                            <template x-if="detail.actions.includes('resolve')">
                                                {{-- the main button is the next step: Start work while it has not started, Mark resolved once it has --}}
                                                <button type="button" class="vy-btn vy-mt-wide" :class="detail.actions.includes('start') ? 'vy-btn--ghost' : 'vy-btn--accent'" @click="fromDrawer('resolve')">{{ __('livewire/maintenance-list.mark_resolved') }}</button>
                                            </template>
                                            <button type="button" class="vy-btn vy-btn--ghost" @click="fromDrawer('edit')">{{ __('livewire/maintenance-list.edit_details') }}</button>
                                            <button type="button" class="vy-btn vy-btn--ghost" @click="fromDrawer('assign')" x-text="detail.assignee ? @js(__('livewire/maintenance-list.change_technician')) : @js(__('livewire/maintenance-list.assign_technician'))">{{ __('livewire/maintenance-list.assign_technician') }}</button>
                                            <button type="button" class="vy-btn vy-btn--ghost vy-mt-danger vy-mt-wide" @click="fromDrawer('cancel')">{{ __('livewire/maintenance-list.cancel_work_order') }}</button>
                                        </div>
                                    </template>
                                    <form method="POST" :action="link(urls.photo, detail.id)" enctype="multipart/form-data" class="vy-mt-photo-form">
                                        @csrf
                                        {{-- back to the board it was added from, not the full record (2026-10-05); the types the server takes --}}
                                        <input type="hidden" name="return" value="list">
                                        <input type="hidden" name="return_query" value="">
                                        <label class="vy-btn vy-btn--ghost vy-mt-photo-btn">
                                            {{ __('livewire/maintenance-list.add_photo') }}
                                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" capture="environment" class="vy-mt-file" @change="stamp($el.form); $el.form.submit()">
                                        </label>
                                    </form>
                                @endif
                                <a class="vy-btn vy-btn--ghost" :href="link(urls.show, detail.id)">{{ __('livewire/maintenance-list.open_full_record') }}</a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </x-operator.shell>

    {{-- The page's controller — the shared list behaviour (selection, the row menu, search and the smooth links, in
         partials/operator-list-script) plus this page's own dialogs and drawer. Every link and form works without it. --}}
    @php
        // The bulk dialogs' sentences, translated here with their real plural for every count the script can show (rule 12): a
        // selection is of the rows on one page (the list script clears it on every page change), so 0 … the largest page size.
        $vyCountTexts = ['assign_selected' => [], 'resolve_n_title' => []];
        foreach (range(0, max(\App\Support\Maintenance\MaintenanceListParams::PER_PAGE_OPTIONS)) as $n) {
            $vyCountTexts['assign_selected'][$n] = trans_choice('livewire/maintenance-list.assign_selected', $n, ['count' => $n]);
            $vyCountTexts['resolve_n_title'][$n] = trans_choice('livewire/maintenance-list.resolve_n_title', $n, ['count' => $n]);
        }
    @endphp
    @include('partials.operator-list-script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vyMaint', (boot) => Object.assign(window.vyListBehaviour(boot, { empty: @js(__('livewire/maintenance-list.empty_view')) }), {
                // the bulk dialogs' sentences, one per count (a selection is of the rows on one page, so 0 … the largest page size)
                counts: @js($vyCountTexts),
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
                        { code: 'logged', label: @js(__('livewire/maintenance-list.step_logged')), done: true },
                        { code: 'assigned', label: @js(__('livewire/maintenance-list.step_assigned')), done: !! d.assignee_id },
                        { code: 'in_progress', label: @js(__('livewire/maintenance-list.step_in_progress')), done: d.status === 'in_progress' || d.status === 'resolved' },
                        { code: 'resolved', label: @js(__('livewire/maintenance-list.resolved')), done: d.status === 'resolved' },
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
