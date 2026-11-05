{{-- Operator UNITS — the organisation's physical units and the room types they are sold as. Rebuilt 2026-09-30 on the
     founder's report ("in Add inventory the text and labels are a mess") and his request to give it the Guests and
     Maintenance treatment (design approved the same day): figures, one toolbar (search · filter chips with counts · Export
     · Select · Add units), a table that sorts by unit (natural order), room type and sleeps and pages 25 / 50 / 100, a menu
     on every row, room types as cards, a setup guide while something is missing, and centred dialogs. Every write is a
     guarded controller POST; an action started here comes back here, as it was.

     ⚠️⚠️ 2026-10-05 — HIS SECOND REPORT ("there must be an option to edit and delete the unit. And Add inventory section
     design and functionality is not good. It is so confusing"): ROOM TYPES COME FIRST now, as compact cards with Edit and
     Delete on them, what keeps each from being booked, and their photos in a dialog; then the UNITS, with Edit in plain
     sight on every row and Delete in its menu. Delete removes only what nothing has recorded — each row and card says
     beforehand whether it can, and why not (App\Support\Unit\InventoryDeletion). Units are added several at once
     ("101-110, 120"), a room type can bring its units in the same dialog, the setup guide stays until there is a price, and
     under "All properties" Add room type asks which property instead of silently using the first.

     ⚠️ THE ADD FORMS ARE DIALOGS, AND THE FIRST REPORT'S CAUSES ARE GONE: `.vy-label` styled nothing (16 px body text),
     every field carried a 15 px margin its button did not (so "Add room type" sat 15 px low), and Kind was a native
     dropdown whose size each browser decides. Labels are `.vy-field-label`, Kind is four tiles, buttons sit in a footer.

     ⚠️ ONE NAME FOR ONE THING: the switch that stops new bookings is PAUSE / RESUME (the owner portal's words for the same
     `is_active`). "Out of service" is the maintenance condition in the next column.

     ⚠️ ARRIVAL INFO AS STATUS, NEVER AS VALUES (CLUSTER E): the rows say whether a door code exists, never what it is.
     Stored values appear only after the audited "Reveal", for that one unit.

     ⚠️ DIALOGS CENTRE BY CLASS (`.vy-modal-backdrop`) — an inline display beside x-show is erased on the first hide, which
     is how the four old dialogs here would reopen in the top-left corner.

     RAW-BLADE RULE: never write `word@directive` — every `@` below follows a space, newline, tag, quote or bracket. --}}
<div class="vy-units">
    <x-operator.shell active="units" :title="__('livewire/units-list.shell_title')" :subtitle="__('livewire/units-list.shell_subtitle')" :viewer="$viewer">

        @if (session('units_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">{{ session('units_notice') }}</div>
        @endif
        @if (session('units_error'))
            <div class="vy-flash is-err vy-fade" role="alert">{{ session('units_error') }}</div>
        @endif
        @if ($errors->any())
            <div class="vy-flash is-err vy-fade" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="vy-gl-root" x-data="vyUnits(@js($boot))" :class="selectMode && 'is-selecting'">

            {{-- ── The figures — real counts over every unit here, whatever is filtered below ──────────────── --}}
            <div class="vy-dash-kpis vy-dash-kpis--5 vy-fade">
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/units-list.kpi_units') }}</div>
                    <div class="vy-dk-val">{{ $kpis['total'] }}</div>
                    <div class="vy-dk-sub">{{ trans_choice('livewire/units-list.kpi_room_types_sub', count($roomTypeCards), ['count' => count($roomTypeCards)]) }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/units-list.kpi_bookable') }}</div>
                    <div class="vy-dk-val">{{ $kpis['bookable'] }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/units-list.kpi_bookable_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/units-list.paused') }}</div>
                    <div class="vy-dk-val">{{ $kpis['paused'] }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/units-list.kpi_paused_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/units-list.kpi_to_clean') }}</div>
                    <div class="vy-dk-val">{{ $kpis['dirty'] }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/units-list.kpi_to_clean_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/units-list.kpi_out_of_order') }}</div>
                    <div class="vy-dk-val">{{ $kpis['ooo'] }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/units-list.kpi_ooo_sub') }}</div>
                </div>
            </div>

            {{-- ── Setting up — until something can be booked: a room type, a unit and a price. A real sequence, so it is
                 numbered. 📖 Before 2026-10-05 it left once a room type and a unit existed — priced or not — and its third
                 step never showed as done. ─────────────────────────────────────────────────────────────────────────── --}}
            @if ($setup['room_types'] === 0 || $setup['units'] === 0 || $setup['priced'] === 0)
                <section class="vy-card vy-ul-setup vy-fade" data-ul-setup aria-labelledby="vy-ul-setup-t">
                    <h2 class="vy-ul-setup-title" id="vy-ul-setup-t">{{ __('livewire/units-list.setup_title') }}</h2>
                    <p class="vy-ul-setup-sub">{{ __('livewire/units-list.setup_sub') }}</p>
                    <ol class="vy-ul-steps">
                        <li data-step="1" class="vy-ul-step {{ $setup['room_types'] > 0 ? 'is-done' : 'is-current' }}">
                            <span class="vy-ul-step-n" aria-hidden="true">{{ $setup['room_types'] > 0 ? '✓' : '1' }}</span>
                            <span class="vy-ul-step-text"><strong>{{ __('livewire/units-list.step_room_type_title') }}</strong><span>{{ __('livewire/units-list.step_room_type_text') }}</span></span>
                            @if ($canWrite && $setup['room_types'] === 0)
                                <button type="button" class="vy-btn vy-btn--accent" @click="openAddRoomType()">{{ __('livewire/units-list.add_room_type') }}</button>
                            @endif
                        </li>
                        <li data-step="2" class="vy-ul-step {{ $setup['units'] > 0 ? 'is-done' : ($setup['room_types'] > 0 ? 'is-current' : '') }}">
                            <span class="vy-ul-step-n" aria-hidden="true">{{ $setup['units'] > 0 ? '✓' : '2' }}</span>
                            <span class="vy-ul-step-text"><strong>{{ __('livewire/units-list.step_units_title') }}</strong><span>{{ __('livewire/units-list.step_units_text') }}</span></span>
                            @if ($canWrite && $setup['room_types'] > 0 && $setup['units'] === 0)
                                <button type="button" class="vy-btn vy-btn--accent" @click="openAddUnit('')">{{ __('livewire/units-list.add_units') }}</button>
                            @endif
                        </li>
                        <li data-step="3" class="vy-ul-step {{ $setup['priced'] > 0 ? 'is-done' : ($setup['units'] > 0 ? 'is-current' : '') }}">
                            <span class="vy-ul-step-n" aria-hidden="true">{{ $setup['priced'] > 0 ? '✓' : '3' }}</span>
                            <span class="vy-ul-step-text"><strong>{{ __('livewire/units-list.step_prices_title') }}</strong><span>{{ __('livewire/units-list.step_prices_text') }}</span></span>
                            @if ($setup['priced'] === 0)
                                <a class="vy-btn {{ $setup['units'] > 0 ? 'vy-btn--accent' : 'vy-btn--ghost' }}" href="{{ route('app.rates') }}">{{ __('livewire/units-list.set_prices') }}</a>
                            @endif
                        </li>
                    </ol>
                    {{-- only when the reason IS a permission — a paused workspace has the shell's own banner --}}
                    @unless ($canManageUnits)
                        <p class="vy-gl-note">{{ __('livewire/units-list.setup_no_permission') }}</p>
                    @endunless
                </section>
            @endif

            {{-- ── Room types — what guests book, first: each with its real units, what keeps it from being booked, and Edit
                 and Delete on the card. Photos open in their own dialog. ────────────────────────────────────────────── --}}
            @if (count($roomTypeCards) > 0)
                <section class="vy-ul-section vy-fade" aria-labelledby="vy-ul-types-t">
                    <div class="vy-ul-sechead">
                        <div class="vy-ul-sechead-text">
                            <h2 class="vy-ul-sectitle" id="vy-ul-types-t">{{ __('livewire/units-list.room_types_title') }}</h2>
                            <p class="vy-ul-secsub">{{ __('livewire/units-list.room_types_sub') }}</p>
                        </div>
                        @if ($canWrite)
                            <button type="button" class="vy-btn vy-btn--ghost" @click="openAddRoomType()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                {{ __('livewire/units-list.add_room_type') }}
                            </button>
                        @endif
                    </div>
                    <div class="vy-ul-types-grid">
                        @foreach ($roomTypeCards as $rt)
                            <article class="vy-card vy-ul-rt" data-rt="{{ $rt['id'] }}">
                                <div class="vy-ul-rt-top">
                                    @if ($rt['cover'])
                                        <img src="{{ $rt['cover'] }}" alt="" class="vy-ul-rt-thumb" loading="lazy">
                                    @else
                                        <span class="vy-ul-rt-thumb is-empty" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $kindIcons[$rt['kind_key']] ?? $kindIcons['room'] !!}</svg></span>
                                    @endif
                                    <div class="vy-ul-rt-id">
                                        <h3 class="vy-ul-rt-name">{{ $rt['name'] }}</h3>
                                        @php
                                            $vyAdults = trans_choice('livewire/units-list.adults_count', $rt['adults'], ['count' => $rt['adults']]);
                                            $vyGuests = $rt['children'] > 0
                                                ? __('livewire/units-list.adults_plus_children', ['adults' => $vyAdults, 'children' => trans_choice('livewire/units-list.children_count', $rt['children'], ['count' => $rt['children']])])
                                                : $vyAdults;
                                        @endphp
                                        <p class="vy-ul-rt-meta">@if ($rt['property'] !== null){{ __('livewire/units-list.rt_meta_property', ['kind' => $rt['kind'], 'guests' => $vyGuests, 'property' => $rt['property']]) }}@else{{ __('livewire/units-list.rt_meta', ['kind' => $rt['kind'], 'guests' => $vyGuests]) }}@endif</p>
                                    </div>
                                    @if ($canWrite)
                                        <div class="vy-ul-rt-acts">
                                            <button type="button" class="vy-ul-iconbtn" title="{{ __('common.edit') }}" aria-label="{{ __('livewire/units-list.edit_room_type_aria', ['name' => $rt['name']]) }}" @click="openEditRoomType('{{ $rt['id'] }}')">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                            </button>
                                            <button type="button" class="vy-ul-iconbtn is-danger" title="{{ __('common.delete') }}" aria-label="{{ __('livewire/units-list.delete_room_type_aria', ['name' => $rt['name']]) }}" @click="openDeleteRoomType('{{ $rt['id'] }}')">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                                <p class="vy-ul-rt-count">{!! trans_choice('livewire/units-list.rt_count', $rt['units'], ['count' => e($rt['units']), 'bookable' => e($rt['bookable'])]) !!}</p>
                                <p class="vy-ul-rt-state is-{{ $rt['state_tone'] }}"><span class="vy-ul-rt-dot" aria-hidden="true"></span>{{ $rt['state'] }}@if ($rt['needs_prices'] && $canManageRates) <a href="{{ route('app.rates') }}">{{ __('livewire/units-list.set_prices') }}</a>@endif</p>
                                @if ($canWrite || $canManageUnits)
                                    <div class="vy-ul-rt-foot">
                                        @if ($canWrite)
                                            <button type="button" class="vy-btn vy-btn--ghost vy-ul-rt-btn" @click="openAddUnit('{{ $rt['id'] }}')">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                                {{ __('livewire/units-list.add_units') }}
                                            </button>
                                        @endif
                                        @if ($canManageUnits)
                                            <button type="button" class="vy-btn vy-btn--ghost vy-ul-rt-btn" @click="photosFor = '{{ $rt['id'] }}'">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M21 17l-5-5-9 7"/></svg>
                                                {{ __('livewire/units-list.photos') }}<span class="vy-ul-rt-n">{{ count($rt['photos']) }}</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    {{-- Each card's facts, once — what the room type dialogs open with. JSON, never run. --}}
                    <script type="application/json" data-ul-types>@json($roomTypesById)</script>
                </section>
            @endif

            {{-- ── The units — the real rooms: the toolbar (search · Export · Select · Add units), then the filters ─────── --}}
            <section class="vy-ul-section" aria-labelledby="vy-ul-units-t">
            <div class="vy-ul-sechead vy-fade">
                <div class="vy-ul-sechead-text">
                    <h2 class="vy-ul-sectitle" id="vy-ul-units-t">{{ __('livewire/units-list.units_title') }}</h2>
                    <p class="vy-ul-secsub">{{ __('livewire/units-list.units_sub') }}</p>
                </div>
            </div>
            <div class="vy-gl-bar vy-ul-bar vy-fade">
                <form class="vy-gl-search" method="GET" action="{{ route('app.units') }}" role="search" @submit.prevent="search()">
                    @foreach ($keep as $keepName => $keepValue)
                        <input type="hidden" name="{{ $keepName }}" value="{{ $keepValue }}">
                    @endforeach
                    <svg class="vy-gl-search-ico" x-show="! loading" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    <span class="vy-gl-spinner" x-show="loading" x-cloak aria-hidden="true"></span>
                    <label class="vy-sr-only" for="vy-ul-q">{{ __('livewire/units-list.search_label') }}</label>
                    <input id="vy-ul-q" class="vy-gl-search-in" type="search" name="q" value="{{ $query }}" x-model="q"
                           @input.debounce.300ms="search()" placeholder="{{ __('livewire/units-list.search_placeholder') }}" maxlength="80" autocomplete="off" spellcheck="false">
                    <button type="button" class="vy-gl-clear" x-show="q !== ''" x-cloak @click="clearSearch()" aria-label="{{ __('livewire/units-list.clear_search') }}">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </form>

                <div class="vy-gl-tools">
                    {{-- WAVE 2 · #18 — each control gated on the permission its route requires ("never invite a 403"). --}}
                    @if ($canExport)
                        {{-- ⚠️ outside the swapped regions, so it takes the view's address when clicked — a filter or sort changed in
                             place would otherwise export the view the page was LOADED with (A12, 2026-10-02; as on Owners) --}}
                        <a class="vy-btn vy-btn--ghost" href="{{ $exportUrl }}" data-export-base="{{ route('app.units.export') }}"
                           x-on:click="$el.href = $el.dataset.exportBase + location.search">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>
                            {{ __('common.export') }}
                        </a>
                    @endif
                    @if ($canSelect && $total > 0)
                        <button type="button" class="vy-btn vy-btn--ghost vy-gl-select" :class="selectMode && 'is-on'" :aria-pressed="selectMode ? 'true' : 'false'" @click="toggleMode()">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/></svg>
                            <span x-text="selectMode ? @js(__('common.done')) : @js(__('livewire/units-list.select'))">{{ __('livewire/units-list.select') }}</span>
                        </button>
                    @endif
                    @if ($canWrite && count($roomTypeOptions) > 0)
                        <button type="button" class="vy-btn vy-btn--accent" @click="openAddUnit('')">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            {{ __('livewire/units-list.add_units') }}
                        </button>
                    @endif
                </div>

                <div class="vy-gl-chips vy-ul-chips" data-gl-region="chips" role="group" aria-label="{{ __('livewire/units-list.chips_aria') }}">
                    @foreach ($chips as $chip)
                        <a class="vy-gl-chip {{ $chip['active'] ? 'is-on' : '' }}" href="{{ $chip['href'] }}" data-gl-key="chip-{{ $chip['key'] }}" @if ($chip['active']) aria-current="page" @endif>{{ $chip['label'] }}<span class="vy-gl-count">{{ $chip['count'] }}</span></a>
                    @endforeach
                </div>
            </div>

            {{-- Said after every change, for screen readers — the new position in the list. --}}
            <p class="vy-sr-only" aria-live="polite" x-text="live"></p>

            {{-- ── The units — swapped in place by the script, and a whole page without it ─────────────────── --}}
            <div class="vy-gl-results" data-gl-region="results" :class="loading && 'is-loading'">
                @if ($total === 0)
                    <div class="vy-card vy-gl-empty vy-fade">
                        <span class="vy-gl-empty-ico" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6M3 18h18M3 18v2M21 18v2M7 10V7a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v3"/></svg>
                        </span>
                        <h2 class="vy-gl-empty-title">{{ __('livewire/units-list.empty_title') }}</h2>
                        <p class="vy-gl-empty-text">@if (count($roomTypeOptions) > 0){{ __('livewire/units-list.empty_text_with_types') }}@else{{ __('livewire/units-list.empty_text_no_types') }}@endif</p>
                    </div>
                @elseif ($rows->total() === 0)
                    <div class="vy-card vy-gl-empty vy-fade">
                        <span class="vy-gl-empty-ico" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                        </span>
                        <h2 class="vy-gl-empty-title">
                            @if ($query !== '') {{ __('livewire/units-list.no_match_query', ['query' => $query]) }} @else {{ __('livewire/units-list.empty_view') }} @endif
                        </h2>
                        <p class="vy-gl-empty-text">{{ trans_choice('livewire/units-list.no_match_text', $total, ['count' => $total]) }}</p>
                        <a class="vy-btn vy-btn--ghost" href="{{ route('app.units') }}" data-gl-key="clear-all">{{ __('livewire/units-list.clear_all') }}</a>
                    </div>
                @else
                    <div class="vy-card vy-gl-card vy-fade">
                        <div class="vy-gl-progress" aria-hidden="true"></div>
                        <div class="vy-gl-scroll">
                            <table class="vy-gl-table vy-ul-table">
                                <caption class="vy-sr-only">{{ __('livewire/units-list.table_caption', ['sorted' => $sortedBy]) }}</caption>
                                <thead>
                                    <tr>
                                        @if ($canSelect)
                                            <th class="vy-gl-check" x-show="selectMode" x-cloak>
                                                <input type="checkbox" aria-label="{{ __('livewire/units-list.select_page_aria') }}"
                                                       :checked="allOnPage()" x-effect="$el.indeterminate = someOnPage()" @change="selectPage($event.target.checked)">
                                            </th>
                                        @endif
                                        <th aria-sort="{{ $sorts['unit']['aria'] }}">
                                            <a class="vy-gl-sort {{ $sorts['unit']['active'] ? 'is-on is-'.$sorts['unit']['dir'] : '' }}" href="{{ $sorts['unit']['href'] }}" data-gl-key="sort-unit">{{ __('livewire/units-list.col_unit') }}<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                        </th>
                                        <th aria-sort="{{ $sorts['room_type']['aria'] }}">
                                            <a class="vy-gl-sort {{ $sorts['room_type']['active'] ? 'is-on is-'.$sorts['room_type']['dir'] : '' }}" href="{{ $sorts['room_type']['href'] }}" data-gl-key="sort-room_type">{{ __('livewire/units-list.room_type') }}<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                        </th>
                                        <th class="is-num" aria-sort="{{ $sorts['sleeps']['aria'] }}">
                                            <a class="vy-gl-sort {{ $sorts['sleeps']['active'] ? 'is-on is-'.$sorts['sleeps']['dir'] : '' }}" href="{{ $sorts['sleeps']['href'] }}" data-gl-key="sort-sleeps">{{ __('livewire/units-list.sleeps') }}<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                        </th>
                                        <th>{{ __('livewire/units-list.col_housekeeping') }}</th>
                                        <th>{{ __('livewire/units-list.col_condition') }}</th>
                                        <th>{{ __('livewire/units-list.col_owner') }}</th>
                                        <th class="vy-gl-actcol"><span class="vy-sr-only">{{ __('common.actions') }}</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $row)
                                        <tr data-gl-id="{{ $row['id'] }}" data-ul-actions="{{ implode(' ', $row['actions']) }}" class="{{ $row['is_active'] ? '' : 'is-paused' }}" :class="sel['{{ $row['id'] }}'] && 'is-selected'">
                                            @if ($canSelect)
                                                <td class="vy-gl-check" x-show="selectMode" x-cloak>
                                                    <input type="checkbox" x-model="sel['{{ $row['id'] }}']" aria-label="{{ __('livewire/units-list.select_unit_aria', ['code' => $row['code']]) }}">
                                                </td>
                                            @endif
                                            <td class="vy-ul-unitcell">
                                                <div class="vy-ul-unit">
                                                    @if ($row['cover'])
                                                        <img src="{{ $row['cover'] }}" alt="" class="vy-ul-thumb" loading="lazy">
                                                    @else
                                                        <span class="vy-ul-thumb is-empty" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $kindIcons[$row['kind_key'] === 'dorm_bed' ? 'dorm' : $row['kind_key']] ?? $kindIcons['room'] !!}</svg></span>
                                                    @endif
                                                    <div class="vy-ul-who">
                                                        <div class="vy-gl-nameline"><span class="vy-ul-code">{{ $row['code'] }}</span>@if (! $row['is_active']) <span class="vy-tag tone-paused">{{ __('livewire/units-list.unit_paused_tag') }}</span>@endif</div>
                                                        {{-- the second line only when it has something to say (an empty one pushed the code off centre) --}}
                                                        @php
                                                            $vySub = array_values(array_filter([
                                                                $row['name'] !== null && $row['name'] !== $row['code'] ? $row['name'] : null,
                                                                $row['arrival']['has_code'] ? __('livewire/units-list.door_code_set') : null,
                                                                $row['arrival']['ssid'] !== null ? __('livewire/units-list.wifi_line', ['ssid' => $row['arrival']['ssid']]) : null,
                                                            ]));
                                                        @endphp
                                                        @if ($vySub !== [])
                                                            <div class="vy-gl-contact">@foreach ($vySub as $vyLine)<span>{{ $vyLine }}</span>@endforeach</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="vy-ul-c-type" data-label="{{ __('livewire/units-list.room_type') }}"><span class="vy-ul-type">{{ $row['room_type'] }}</span><span class="vy-ul-kind">{{ $row['kind'] }}</span></td>
                                            <td class="is-num" data-label="{{ __('livewire/units-list.sleeps') }}"><span class="vy-gl-fig">{{ $row['occupancy'] }}</span></td>
                                            <td data-label="{{ __('livewire/units-list.col_housekeeping') }}"><span class="vy-tag tone-{{ $row['hk_tone'] }}"><span class="vy-dot"></span>{{ $row['hk_label'] }}</span></td>
                                            <td data-label="{{ __('livewire/units-list.col_condition') }}"><span class="vy-tag tone-{{ $row['condition_tone'] }}"><span class="vy-dot"></span>{{ $row['condition_label'] }}</span></td>
                                            <td data-label="{{ __('livewire/units-list.col_owner') }}">
                                                @if ($row['owner'])
                                                    <span class="vy-u-owner"><span class="vy-mini-avatar">{{ strtoupper(mb_substr($row['owner'], 0, 1)) }}</span>{{ $row['owner'] }}</span>
                                                @else
                                                    <span class="vy-gl-none">{{ __('livewire/units-list.operator_owned') }}</span>
                                                @endif
                                            </td>
                                            <td class="vy-gl-actcol">
                                                <div class="vy-ul-rowacts">
                                                    @if (in_array('edit', $row['actions'], true))
                                                        <button type="button" class="vy-ul-iconbtn" title="{{ __('common.edit') }}" aria-label="{{ __('livewire/units-list.edit_unit_aria', ['code' => $row['code']]) }}" @click="act('edit', row('{{ $row['id'] }}'))">
                                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                                        </button>
                                                    @endif
                                                    <button type="button" class="vy-gl-more" aria-label="{{ __('livewire/units-list.actions_for_unit', ['code' => $row['code']]) }}" aria-controls="vy-ul-menu"
                                                            :aria-expanded="menuFor === '{{ $row['id'] }}' ? 'true' : 'false'" @click="openMenu($event, row('{{ $row['id'] }}'))">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5.5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="18.5" cy="12" r="1.7"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="vy-gl-foot" data-gl-foot>
                            <p class="vy-gl-summary" data-gl-summary tabindex="-1">{!! trans_choice('livewire/units-list.summary', $rows->total(), ['first' => e($rows->firstItem()), 'last' => e($rows->lastItem()), 'count' => e($rows->total()), 'sorted' => e($sortedBy)]) !!}</p>
                            <div class="vy-gl-paging">
                                <span class="vy-gl-rows-label" id="vy-ul-rows">{{ __('livewire/units-list.rows_label') }}</span>
                                <div class="vy-gl-sizes" role="group" aria-labelledby="vy-ul-rows">
                                    @foreach ($sizes as $size)
                                        <a class="vy-gl-size {{ $size['active'] ? 'is-on' : '' }}" href="{{ $size['href'] }}" data-gl-key="per-{{ $size['size'] }}" @if ($size['active']) aria-current="true" @endif>{{ $size['size'] }}</a>
                                    @endforeach
                                </div>
                                @if ($rows->lastPage() > 1)
                                    <nav class="vy-gl-pages" aria-label="{{ __('livewire/units-list.pages_aria') }}">
                                        @if ($pages['prev'] !== null)
                                            <a class="vy-gl-page" href="{{ $pages['prev'] }}" data-gl-key="page-prev" rel="prev" aria-label="{{ __('livewire/units-list.previous_page') }}">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                                            </a>
                                        @else
                                            <span class="vy-gl-page is-off" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></span>
                                        @endif
                                        <span class="vy-gl-pagepos">{{ __('livewire/units-list.page_of', ['page' => $rows->currentPage(), 'pages' => $rows->lastPage()]) }}</span>
                                        @if ($pages['next'] !== null)
                                            <a class="vy-gl-page" href="{{ $pages['next'] }}" data-gl-key="page-next" rel="next" aria-label="{{ __('livewire/units-list.next_page') }}">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                                            </a>
                                        @else
                                            <span class="vy-gl-page is-off" aria-hidden="true"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></span>
                                        @endif
                                    </nav>
                                @endif
                            </div>
                        </div>
                    </div>
                    {{-- Each row on this page, once — what the row menu and the dialogs open with. JSON, never run; it carries
                         no door code, wifi password or note (UnitRows reports only whether each exists). --}}
                    <script type="application/json" data-ul-rows>@json($rowsById)</script>

                    {{-- D18-5 · "Price individually" — the ONE <x-rates.unit-price-form>, N=1, for each unit on THIS page. It lives in
                         the results region so a page change brings its units' forms with it, and OUTSIDE the card, whose blur would
                         otherwise become the box a fixed dialog is placed in. The money path is UnitRateOverrideService, never a
                         direct write. --}}
                    @if ($canPrice)
                        @foreach ($rows as $vyU)
                            <div class="vy-modal-backdrop" x-cloak x-show="unitPriceFor === '{{ $vyU['id'] }}'" x-transition.opacity
                                 @keydown.escape.window="unitPriceFor = null" @click.self="unitPriceFor = null">
                                <div class="vy-modal vy-modal-card vy-modal--wide vy-ul-price" x-show="unitPriceFor === '{{ $vyU['id'] }}'" x-transition
                                     x-data="{ unitMode: 'set', unitRt: '{{ $vyU['room_type_id'] }}' }" role="dialog" aria-modal="true" tabindex="-1"
                                     x-trap.noscroll="unitPriceFor === '{{ $vyU['id'] }}'" aria-labelledby="vy-dlg-ul-unitprice-{{ $vyU['id'] }}">
                                    <x-rates.unit-price-form
                                        heading-id="vy-dlg-ul-unitprice-{{ $vyU['id'] }}"
                                        :room-types="$unitPriceRoomTypes"
                                        :units="$unitPriceUnits"
                                        :preselect="$vyU['id']"
                                        :from="now()->toDateString()"
                                        :to="now()->addDays(6)->toDateString()"
                                        :heading="__('livewire/units-list.price_heading')"
                                        :subheading="__('livewire/units-list.price_subheading')">
                                        <x-slot:actions>
                                            <button type="button" class="vy-btn vy-btn--ghost" @click="unitPriceFor = null">{{ __('common.cancel') }}</button>
                                        </x-slot:actions>
                                    </x-rates.unit-price-form>
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endif
            </div>
            </section>

            {{-- ── The row menu — ONE, placed beside the button that opened it; its entries follow the row's `actions`. ── --}}
            <div class="vy-gl-menu" id="vy-ul-menu" x-show="menu.open" x-cloak :class="menu.up && 'is-up'"
                 :style="{ top: menu.y + 'px', left: menu.x + 'px' }"
                 @click.outside="closeMenu(false)" @keydown.escape.prevent.stop="closeMenu(true)" @focusout="leaveMenu($event)">
                @if ($canWrite)
                    <button type="button" class="vy-gl-menu-item" x-show="can('edit')" @click="menuAct('edit')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                        {{ __('livewire/units-list.edit_unit') }}
                    </button>
                @endif
                @if ($canPrice)
                    <button type="button" class="vy-gl-menu-item" x-show="can('price')" @click="menuAct('price')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8zM7.5 7.5h.01"/></svg>
                        {{ __('livewire/units-list.price_individually') }}
                    </button>
                @endif
                @if ($canManageUnits)
                    <button type="button" class="vy-gl-menu-item" x-show="can('arrival')" @click="menuAct('arrival')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3"/></svg>
                        {{ __('livewire/units-list.arrival_info') }}
                    </button>
                @endif
                <a class="vy-gl-menu-item" :href="menu.row ? menu.row.work_orders : '#'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3.5 17.5a2.1 2.1 0 0 0 3 3l5.8-5.8a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg>
                    {{ __('livewire/units-list.see_work_orders') }}
                </a>
                @if ($canWrite)
                    <span class="vy-gl-menu-sep" x-show="can('pause') || can('resume') || can('delete')" aria-hidden="true"></span>
                    <button type="button" class="vy-gl-menu-item" x-show="can('pause')" @click="menuAct('pause')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/></svg>
                        {{ __('livewire/units-list.pause_this_unit') }}
                    </button>
                    <button type="button" class="vy-gl-menu-item" x-show="can('resume')" @click="menuAct('resume')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M10 8.5l5 3.5-5 3.5z"/></svg>
                        {{ __('livewire/units-list.resume_this_unit') }}
                    </button>
                    <button type="button" class="vy-gl-menu-item is-danger" x-show="can('delete')" @click="menuAct('delete')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                        {{ __('livewire/units-list.delete_unit') }}
                    </button>
                @endif
            </div>

            {{-- ── The bulk bar — select mode, one or more selected ───────────────────────────────────────────── --}}
            @if ($canSelect)
                <div class="vy-gl-bulk" x-show="selectMode && count()" x-cloak x-transition role="region" aria-label="{{ __('livewire/units-list.selected_units_aria') }}">
                    <span class="vy-gl-bulk-count">{!! __('livewire/units-list.bulk_selected') !!}</span>
                    <button type="button" class="vy-gl-bulk-clear" @click="clear()">{{ __('common.clear') }}</button>
                    <span class="vy-gl-bulk-sep" aria-hidden="true"></span>
                    @if ($canExport)
                        <form method="POST" action="{{ route('app.units.export-selected') }}">
                            @csrf
                            <template x-for="id in ids()" :key="id"><input type="hidden" name="unit_ids[]" :value="id"></template>
                            <button type="submit" class="vy-gl-bulk-act">{{ __('common.export') }}</button>
                        </form>
                    @endif
                    @if ($canWrite)
                        <button type="button" class="vy-gl-bulk-act" @click="bulkPause(false)">{{ __('livewire/units-list.pause') }}</button>
                        <button type="button" class="vy-gl-bulk-act" @click="bulkPause(true)">{{ __('livewire/units-list.resume') }}</button>
                    @endif
                </div>
            @endif

            @if ($canWrite)
                {{-- ── ADD ROOM TYPE — and, optionally, its units in the same step ─────────────────────────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="rtOpen" x-transition.opacity @keydown.escape.window="rtOpen = false" @click.self="rtOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide" x-show="rtOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-ul-rt-t" tabindex="-1" x-trap.noscroll="rtOpen">
                        <form method="POST" action="{{ route('app.inventory.room-types.store') }}" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <input type="hidden" name="_form" value="room_type">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-7 9 7M5 10v10h14V10M10 20v-5h4v5"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-ul-rt-t">{{ __('livewire/units-list.add_room_type') }}</h3>
                                    <p class="vy-modal-sub">{{ __('livewire/units-list.add_room_type_sub') }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="rtOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($errors->any() && old('_form') === 'room_type')
                                    <div class="vy-gl-errors" role="alert">
                                        @foreach ($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($propertyChoice)
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-ul-rt-prop">{{ __('livewire/units-list.property') }}</label>
                                        <x-select name="property_id" id="vy-ul-rt-prop" :options="array_merge([['value' => '', 'label' => __('livewire/units-list.choose_property')]], $propertyOptions)" :value="''" :search-placeholder="__('livewire/units-list.search_properties')" :empty-text="__('livewire/units-list.no_property_matches')" x-model="rtForm.property_id" />
                                    </div>
                                @else
                                    <p class="vy-ul-addsto">{!! __('livewire/units-list.adds_to', ['property' => e($addsTo)]) !!}</p>
                                @endif
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-ul-rt-name">{{ __('livewire/units-list.room_type_name_label') }}</label>
                                    <input class="vy-input" id="vy-ul-rt-name" type="text" name="name" maxlength="255" required placeholder="{{ __('livewire/units-list.room_type_name_placeholder') }}" x-model="rtForm.name" autocomplete="off">
                                </div>
                                <div class="vy-ul-kinds">
                                    <x-operator.choices name="kind" :label="__('livewire/units-list.kind')" :options="$kindChoices" model="rtForm.kind" />
                                </div>
                                <div class="vy-gl-fields">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-ul-rt-adults">{{ __('livewire/units-list.adults') }}</label>
                                        <input class="vy-input" id="vy-ul-rt-adults" type="number" name="max_adults" min="1" max="30" required x-model="rtForm.max_adults">
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-ul-rt-children">{{ __('livewire/units-list.children') }}</label>
                                        <input class="vy-input" id="vy-ul-rt-children" type="number" name="max_children" min="0" max="30" required x-model="rtForm.max_children">
                                    </div>
                                </div>
                                <p class="vy-gl-note vy-ul-first" x-text="@js(__('livewire/units-list.sleeps_note')).replace(':sleeps', () => sleepsText(rtForm))"></p>
                                <div class="vy-field vy-ul-last vy-ul-codesfield">
                                    <label class="vy-field-label" for="vy-ul-rt-codes">{!! __('livewire/units-list.its_units_optional') !!}</label>
                                    <input class="vy-input" id="vy-ul-rt-codes" type="text" name="unit_codes" maxlength="2000" placeholder="101-110, 120" x-model="rtForm.unit_codes" autocomplete="off" spellcheck="false" aria-describedby="vy-ul-rt-codes-note">
                                    <p class="vy-ul-codes-note" id="vy-ul-rt-codes-note" :class="'is-' + codesNote(rtForm.unit_codes, rtForm.property_id || boot.home).tone"
                                       x-text="codesNote(rtForm.unit_codes, rtForm.property_id || boot.home).text || @js(__('livewire/units-list.its_units_hint'))"></p>
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="rtOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/units-list.add_room_type') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── ADD UNITS — one, a list or a range, all to one room type ─────────────────────────────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="unitOpen" x-transition.opacity @keydown.escape.window="unitOpen = false" @click.self="unitOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide" x-show="unitOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-ul-unit-t" tabindex="-1" x-trap.noscroll="unitOpen">
                        <form method="POST" action="{{ route('app.inventory.units.store') }}" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <input type="hidden" name="_form" value="unit">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6M3 18h18M3 18v2M21 18v2M7 10V7a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v3"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-ul-unit-t">{{ __('livewire/units-list.add_units') }}</h3>
                                    <p class="vy-modal-sub">{{ __('livewire/units-list.add_units_sub') }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="unitOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($errors->any() && old('_form') === 'unit')
                                    <div class="vy-gl-errors" role="alert">
                                        @foreach ($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                                @if (count($roomTypeOptions) > 0)
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-ul-u-rt">{{ __('livewire/units-list.room_type') }}</label>
                                        <x-select name="room_type_id" id="vy-ul-u-rt" :options="$roomTypeOptions" :value="$roomTypeOptions[0]['value']" :search-placeholder="__('livewire/units-list.search_room_types')" :empty-text="__('livewire/units-list.no_room_type_matches')" x-model="unitForm.room_type_id" />
                                    </div>
                                @endif
                                <div class="vy-field vy-ul-codesfield">
                                    <label class="vy-field-label" for="vy-ul-u-code">{{ __('livewire/units-list.unit_codes') }}</label>
                                    <input class="vy-input" id="vy-ul-u-code" type="text" name="code" maxlength="2000" required placeholder="{{ __('livewire/units-list.unit_codes_placeholder') }}" x-model="unitForm.code" autocomplete="off" spellcheck="false" aria-describedby="vy-ul-u-code-note">
                                    <p class="vy-ul-codes-note" id="vy-ul-u-code-note" :class="'is-' + codesNote(unitForm.code, boot.roomTypeProperty[unitForm.room_type_id]).tone"
                                       x-text="codesNote(unitForm.code, boot.roomTypeProperty[unitForm.room_type_id]).text || @js(__('livewire/units-list.unit_codes_hint'))"></p>
                                </div>
                                <div class="vy-gl-fields">
                                    <div class="vy-field vy-ul-last" x-show="codeCount(unitForm.code) <= 1">
                                        <label class="vy-field-label" for="vy-ul-u-name">{!! __('livewire/units-list.name_optional') !!}</label>
                                        <input class="vy-input" id="vy-ul-u-name" type="text" name="name" maxlength="255" placeholder="{{ __('livewire/units-list.unit_name_placeholder') }}" x-model="unitForm.name" autocomplete="off" :disabled="codeCount(unitForm.code) > 1">
                                    </div>
                                    <div class="vy-field vy-ul-last">
                                        <label class="vy-field-label" for="vy-ul-u-occ">{!! __('livewire/units-list.sleeps_optional') !!}</label>
                                        <input class="vy-input" id="vy-ul-u-occ" type="number" name="max_occupancy" min="1" max="30" x-model="unitForm.max_occupancy" :placeholder="roomTypeSleeps(unitForm.room_type_id)">
                                    </div>
                                </div>
                                <p class="vy-gl-note">{{ __('livewire/units-list.sleeps_default_note') }}</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="unitOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent" x-text="unitSubmitLabel()">{{ __('livewire/units-list.add_unit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── EDIT UNIT — code, name and sleeps: none of them can reach a stay already sold (F-6). Not the room
                     type and not the owner: those move capacity and money, and keep their own paths. ─────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="editOpen" x-transition.opacity @keydown.escape.window="editOpen = false" @click.self="editOpen = false">
                    <div class="vy-modal vy-modal-card" x-show="editOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-ul-edit-t" tabindex="-1" x-trap.noscroll="editOpen">
                        <form method="POST" :action="link(urls.edit, edit.id)" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <input type="hidden" name="_unit" :value="edit.id">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-ul-edit-t">{!! __('livewire/units-list.edit_unit_title', ['code' => '<span x-text="edit.was"></span>']) !!}</h3>
                                    <p class="vy-modal-sub">{{ __('livewire/units-list.edit_unit_sub') }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="editOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($errors->any() && is_string(old('_unit')))
                                    <div class="vy-gl-errors" role="alert">
                                        @foreach ($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="vy-gl-fields">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-ul-e-code">{{ __('livewire/units-list.unit_code') }}</label>
                                        <input class="vy-input" id="vy-ul-e-code" type="text" name="code" maxlength="60" required x-model="edit.code" autocomplete="off">
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-ul-e-occ">{{ __('livewire/units-list.sleeps') }}</label>
                                        <input class="vy-input" id="vy-ul-e-occ" type="number" name="max_occupancy" min="1" max="30" x-model="edit.max_occupancy">
                                    </div>
                                </div>
                                <div class="vy-field vy-ul-last">
                                    <label class="vy-field-label" for="vy-ul-e-name">{!! __('livewire/units-list.name_optional') !!}</label>
                                    <input class="vy-input" id="vy-ul-e-name" type="text" name="name" maxlength="255" x-model="edit.name" autocomplete="off">
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost vy-ul-foot-del" @click="editOpen = false; act('delete', row(edit.id))">{{ __('livewire/units-list.delete_ellipsis') }}</button>
                                <button type="button" class="vy-btn vy-btn--ghost" @click="editOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent">{{ __('common.save_changes') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── DELETE UNIT — only one nothing has recorded; for any other it says what is on record and offers Pause.
                     The server asks the same question again at the moment of the delete. ──────────────────────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="delOpen" x-transition.opacity @keydown.escape.window="delOpen = false" @click.self="delOpen = false">
                    <div class="vy-modal vy-modal-card" x-show="delOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-ul-del-t" tabindex="-1" x-trap.noscroll="delOpen">
                        <form method="POST" :action="link(urls.destroy, del.id)" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico is-danger">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-ul-del-t" x-text="(del.deletable ? @js(__('livewire/units-list.delete_unit_q_code')) : @js(__('livewire/units-list.unit_cannot_delete'))).replace(':code', () => del.code)">{{ __('livewire/units-list.delete_unit_q') }}</h3>
                                    <p class="vy-modal-sub" x-text="del.deletable ? @js(__('livewire/units-list.cannot_be_undone')) : @js(__('livewire/units-list.has_history'))"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="delOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <ul class="vy-ul-facts" x-show="del.deletable">
                                    <li>{{ __('livewire/units-list.unit_fact_nothing') }}</li>
                                    <li>{{ __('livewire/units-list.unit_fact_sells_fewer') }}</li>
                                    <li>{{ __('livewire/units-list.audit_logged') }}</li>
                                </ul>
                                <div class="vy-ul-why" x-show="! del.deletable">
                                    <p>{!! __('livewire/units-list.on_record', ['items' => '<strong x-text="sentence(del.blockers)"></strong>']) !!}</p>
                                    <p class="vy-gl-note">{{ __('livewire/units-list.unit_delete_would_erase') }}
                                        <span x-text="del.active ? @js(__('livewire/units-list.pause_instead_hint')) : @js(__('livewire/units-list.already_paused_hint'))"></span></p>
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="delOpen = false" x-text="del.deletable ? @js(__('common.cancel')) : @js(__('common.close'))">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--danger" x-show="del.deletable">{{ __('livewire/units-list.delete_unit') }}</button>
                                <button type="button" class="vy-btn vy-btn--accent" x-show="! del.deletable && del.active" @click="delOpen = false; act('pause', row(del.id))">{{ __('livewire/units-list.pause_instead') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── EDIT ROOM TYPE — its name and who it sleeps; its kind only while it has no units (they copied it). ── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="rtEditOpen" x-transition.opacity @keydown.escape.window="rtEditOpen = false" @click.self="rtEditOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide" x-show="rtEditOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-ul-rte-t" tabindex="-1" x-trap.noscroll="rtEditOpen">
                        <form method="POST" :action="link(urls.rtUpdate, rtEdit.id)" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <input type="hidden" name="_rt" :value="rtEdit.id">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-ul-rte-t">{!! __('livewire/units-list.edit_room_type_title', ['name' => '<span x-text="rtEdit.was"></span>']) !!}</h3>
                                    <p class="vy-modal-sub">{{ __('livewire/units-list.edit_room_type_sub') }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="rtEditOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($errors->any() && is_string(old('_rt')))
                                    <div class="vy-gl-errors" role="alert">
                                        @foreach ($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-ul-rte-name">{{ __('livewire/units-list.room_type_name_label') }}</label>
                                    <input class="vy-input" id="vy-ul-rte-name" type="text" name="name" maxlength="255" required x-model="rtEdit.name" autocomplete="off">
                                </div>
                                <div class="vy-ul-kinds" x-show="rtEdit.unit_rows === 0">
                                    <x-operator.choices name="kind" :label="__('livewire/units-list.kind')" :options="$kindChoices" model="rtEdit.kind" />
                                </div>
                                <p class="vy-ul-kindlock" x-show="rtEdit.unit_rows > 0">
                                    <span class="vy-field-label">{{ __('livewire/units-list.kind') }}</span>
                                    <span x-text="@js(__('livewire/units-list.kind_locked')).replace(':kind', () => rtEdit.kindLabel)"></span>
                                </p>
                                <div class="vy-gl-fields">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-ul-rte-adults">{{ __('livewire/units-list.adults') }}</label>
                                        <input class="vy-input" id="vy-ul-rte-adults" type="number" name="max_adults" min="1" max="30" required x-model="rtEdit.max_adults">
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-ul-rte-children">{{ __('livewire/units-list.children') }}</label>
                                        <input class="vy-input" id="vy-ul-rte-children" type="number" name="max_children" min="0" max="30" required x-model="rtEdit.max_children">
                                    </div>
                                </div>
                                <p class="vy-gl-note vy-ul-first" x-text="@js(__('livewire/units-list.sleeps_note')).replace(':sleeps', () => sleepsText(rtEdit))"></p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost vy-ul-foot-del" @click="rtEditOpen = false; openDeleteRoomType(rtEdit.id)">{{ __('livewire/units-list.delete_ellipsis') }}</button>
                                <button type="button" class="vy-btn vy-btn--ghost" @click="rtEditOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent">{{ __('common.save_changes') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── DELETE ROOM TYPE — only an empty one; its rate plans and prices go with it. ─────────────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="rtDelOpen" x-transition.opacity @keydown.escape.window="rtDelOpen = false" @click.self="rtDelOpen = false">
                    <div class="vy-modal vy-modal-card" x-show="rtDelOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-ul-rtd-t" tabindex="-1" x-trap.noscroll="rtDelOpen">
                        <form method="POST" :action="link(urls.rtDestroy, rtDel.id)" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico is-danger">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-ul-rtd-t" x-text="(rtDel.deletable ? @js(__('livewire/units-list.delete_room_type_q_name')) : @js(__('livewire/units-list.room_type_cannot_delete'))).replace(':name', () => rtDel.name)">{{ __('livewire/units-list.delete_room_type_q') }}</h3>
                                    <p class="vy-modal-sub" x-text="rtDel.deletable ? @js(__('livewire/units-list.cannot_be_undone')) : (rtDel.units > 0 ? @js(__('livewire/units-list.room_type_needs_no_units')) : @js(__('livewire/units-list.has_history')))"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="rtDelOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <ul class="vy-ul-facts" x-show="rtDel.deletable">
                                    <li>{{ __('livewire/units-list.rt_fact_empty') }}</li>
                                    <li>{{ __('livewire/units-list.rt_fact_deleted_with') }}</li>
                                    <li>{{ __('livewire/units-list.audit_logged') }}</li>
                                </ul>
                                <div class="vy-ul-why" x-show="! rtDel.deletable">
                                    <p>{!! __('livewire/units-list.on_record', ['items' => '<strong x-text="sentence(rtDel.blockers)"></strong>']) !!}</p>
                                    <p class="vy-gl-note" x-text="rtDel.units > 0 ? @js(__('livewire/units-list.rt_units_first')) : @js(__('livewire/units-list.rt_delete_would_erase'))"></p>
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="rtDelOpen = false" x-text="rtDel.deletable ? @js(__('common.cancel')) : @js(__('common.close'))">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--danger" x-show="rtDel.deletable">{{ __('livewire/units-list.delete_room_type') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── PAUSE / RESUME — one unit from its row, or the selection. The copy states what it does and what it
                     does NOT do (a control may not imply more than it does). ───────────────────────────────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="pauseOpen" x-transition.opacity @keydown.escape.window="pauseOpen = false" @click.self="pauseOpen = false">
                    <div class="vy-modal vy-modal-card" x-show="pauseOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-ul-pause-t" tabindex="-1" x-trap.noscroll="pauseOpen">
                        <form method="POST" :action="pause.bulk ? urls.bulkActive : link(urls.active, pause.id)" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <input type="hidden" name="active" :value="pause.resume ? '1' : '0'">
                            <template x-if="pause.bulk">
                                <div><template x-for="id in ids()" :key="id"><input type="hidden" name="unit_ids[]" :value="id"></template></div>
                            </template>
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico" :class="! pause.resume && 'is-danger'">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-ul-pause-t" x-text="pauseTitle()">{{ __('livewire/units-list.pause_unit_q') }}</h3>
                                    <p class="vy-modal-sub" x-text="pause.resume ? @js(__('livewire/units-list.resume_starts')) : @js(__('livewire/units-list.pause_stops'))"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="pauseOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <ul class="vy-ul-facts">
                                    <li x-show="! pause.resume">{{ __('livewire/units-list.pause_fact_kept') }}</li>
                                    <li x-show="! pause.resume">{{ __('livewire/units-list.pause_fact_nothing_deleted') }}</li>
                                    <li x-show="pause.resume">{{ __('livewire/units-list.resume_fact_offered') }}</li>
                                    <li>{{ __('livewire/units-list.audit_logged') }}</li>
                                </ul>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="pauseOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn" :class="pause.resume ? 'vy-btn--accent' : 'vy-btn--danger'" x-text="pause.resume ? @js(__('livewire/units-list.resume')) : @js(__('livewire/units-list.pause'))">{{ __('livewire/units-list.pause') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if ($canManageUnits)
                {{-- ── PHOTOS — one room type's, in a dialog of their own (they used to fill every card). Shown on the booking
                     storefront and beside each unit. After any change the page comes back with this dialog open. ─────── --}}
                @foreach ($roomTypeCards as $rt)
                    <div class="vy-modal-backdrop" x-cloak x-show="photosFor === '{{ $rt['id'] }}'" x-transition.opacity @keydown.escape.window="photosFor = null" @click.self="photosFor = null">
                        <div class="vy-modal vy-modal-card vy-modal--wide vy-ul-photos" x-show="photosFor === '{{ $rt['id'] }}'" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-ul-photos-t-{{ $rt['id'] }}" tabindex="-1" x-trap.noscroll="photosFor === '{{ $rt['id'] }}'">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M21 17l-5-5-9 7"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-ul-photos-t-{{ $rt['id'] }}">{{ __('livewire/units-list.photos_title', ['name' => $rt['name']]) }}</h3>
                                    <p class="vy-modal-sub">{{ __('livewire/units-list.photos_sub') }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="photosFor = null" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                @if ($errors->has('photo') && old('_photos') === $rt['id'])
                                    <div class="vy-gl-errors" role="alert">{{ $errors->first('photo') }}</div>
                                @endif
                                @if (count($rt['photos']) > 0)
                                    <div class="vy-ul-photo-grid">
                                        @foreach ($rt['photos'] as $i => $photo)
                                            <figure class="vy-ul-photo @if ($photo['is_cover']) is-cover @endif">
                                                <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}" loading="lazy">
                                                @if ($photo['is_cover'])<span class="vy-pm-cover-badge">{{ __('livewire/units-list.cover') }}</span>@endif
                                                @if ($canWrite)
                                                    <figcaption class="vy-ul-photo-ctrls">
                                                        @unless ($photo['is_cover'])
                                                            <form method="POST" action="{{ route('app.room-types.photos.cover', [$rt['id'], $photo['id']]) }}">@csrf<button class="vy-pm-btn" title="{{ __('livewire/units-list.set_as_cover') }}" aria-label="{{ __('livewire/units-list.set_as_cover') }}">★</button></form>
                                                        @endunless
                                                        <form method="POST" action="{{ route('app.room-types.photos.move', [$rt['id'], $photo['id']]) }}">@csrf<input type="hidden" name="direction" value="up"><button class="vy-pm-btn" title="{{ __('livewire/units-list.move_earlier') }}" aria-label="{{ __('livewire/units-list.move_earlier') }}" @if ($i === 0) disabled @endif>‹</button></form>
                                                        <form method="POST" action="{{ route('app.room-types.photos.move', [$rt['id'], $photo['id']]) }}">@csrf<input type="hidden" name="direction" value="down"><button class="vy-pm-btn" title="{{ __('livewire/units-list.move_later') }}" aria-label="{{ __('livewire/units-list.move_later') }}" @if ($i === count($rt['photos']) - 1) disabled @endif>›</button></form>
                                                        <form method="POST" action="{{ route('app.room-types.photos.destroy', [$rt['id'], $photo['id']]) }}" onsubmit="return confirm(@js(__('livewire/units-list.remove_photo_confirm')))">@csrf @method('DELETE')<button class="vy-pm-btn vy-pm-btn--danger" title="{{ __('common.remove') }}" aria-label="{{ __('common.remove') }}">✕</button></form>
                                                    </figcaption>
                                                @endif
                                            </figure>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="vy-pm-empty">{{ __('livewire/units-list.no_photos') }}</p>
                                @endif
                                @if ($canWrite)
                                    <form method="POST" action="{{ route('app.room-types.photos.store', $rt['id']) }}" enctype="multipart/form-data" class="vy-ul-rt-upload">
                                        @csrf
                                        <input type="hidden" name="_photos" value="{{ $rt['id'] }}">
                                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required
                                               id="pm-{{ $rt['id'] }}" class="vy-pm-file" onchange="this.form.submit()">
                                        <label for="pm-{{ $rt['id'] }}" class="vy-btn vy-btn--ghost vy-pm-add">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M21 17l-5-5-9 7"/></svg>
                                            {{ __('livewire/units-list.add_photo') }}
                                        </label>
                                        <span class="vy-gl-note">{{ __('livewire/units-list.photo_formats') }}</span>
                                    </form>
                                @else
                                    <p class="vy-gl-note">{{ __('livewire/units-list.photos_paused') }}</p>
                                @endif
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="photosFor = null">{{ __('common.close') }}</button>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- ── ARRIVAL INFO — CLUSTER E. The two secrets are WRITE-ONLY here: the page never renders a stored door
                     code or wifi password back (it would sit in every response, cache and screenshot). A blank field keeps
                     what is stored; clearing is explicit. Reading them back is a separate, audited action — and the one
                     part left while the workspace is paused (since 2026-10-05 the save is refused then, as every write). ─── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="arrivalOpen" x-transition.opacity @keydown.escape.window="arrivalOpen = false" @click.self="arrivalOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide" x-show="arrivalOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-ul-arrival-t" tabindex="-1" x-trap.noscroll="arrivalOpen">
                        <form method="POST" :action="link(urls.arrival, arrival.id)" data-guard-submit @submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-ul-arrival-t">{!! __('livewire/units-list.arrival_title', ['unit' => '<span x-text="arrival.label"></span>']) !!}</h3>
                                    <p class="vy-modal-sub">{{ trans_choice('livewire/units-list.arrival_lead', \App\Support\Guest\ArrivalPack::LEAD_DAYS, ['count' => \App\Support\Guest\ArrivalPack::LEAD_DAYS]) }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="arrivalOpen = false" aria-label="{{ __('common.close') }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                @unless ($canWrite)
                                    <p class="vy-ul-paused">{{ __('livewire/units-list.arrival_paused') }}</p>
                                @endunless
                                <div class="vy-gl-fields">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-ul-a-code">{{ __('livewire/units-list.door_code') }}</label>
                                        <input class="vy-input" id="vy-ul-a-code" type="text" name="door_code" maxlength="64" autocomplete="off" spellcheck="false" @disabled(! $canWrite)
                                               :placeholder="arrival.has_code ? @js(__('livewire/units-list.set_keep_blank')) : @js(__('livewire/units-list.not_set'))">
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-ul-a-ssid">{{ __('livewire/units-list.wifi_network') }}</label>
                                        <input class="vy-input" id="vy-ul-a-ssid" type="text" name="wifi_ssid" maxlength="64" autocomplete="off" x-model="arrival.ssid" @disabled(! $canWrite)>
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-ul-a-pass">{{ __('livewire/units-list.wifi_password') }}</label>
                                        <input class="vy-input" id="vy-ul-a-pass" type="text" name="wifi_password" maxlength="128" autocomplete="off" spellcheck="false" @disabled(! $canWrite)
                                               :placeholder="arrival.has_wifi ? @js(__('livewire/units-list.set_keep_blank')) : @js(__('livewire/units-list.not_set'))">
                                    </div>
                                </div>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-ul-a-notes">{{ __('livewire/units-list.arrival_notes') }}</label>
                                    <textarea class="vy-input vy-gl-textarea" id="vy-ul-a-notes" name="arrival_notes" rows="3" maxlength="2000" @disabled(! $canWrite)
                                              :placeholder="arrival.has_notes ? @js(__('livewire/units-list.notes_set_keep_blank')) : @js(__('livewire/units-list.arrival_notes_placeholder'))"></textarea>
                                </div>
                                <p class="vy-gl-note vy-ul-first">{!! __('livewire/units-list.blank_keeps', ['clear' => e(\App\Services\ArrivalInfoService::CLEAR)]) !!}</p>
                                @if ($reveal !== null)
                                    <div class="vy-ul-revealed" x-show="arrival.id === '{{ $reveal['unit'] }}'" role="status">
                                        <div>{!! __('livewire/units-list.revealed_door_code', ['code' => e($reveal['door_code'] ?? '—')]) !!}</div>
                                        <div>{!! __('livewire/units-list.revealed_wifi', ['network' => e($reveal['wifi_ssid'] ?? '—'), 'password' => e($reveal['wifi_password'] ?? '—')]) !!}</div>
                                    </div>
                                @endif
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="arrivalOpen = false">{{ $canWrite ? __('common.cancel') : __('common.close') }}</button>
                                @if ($canWrite)
                                    <button type="submit" class="vy-btn vy-btn--accent">{{ __('common.save') }}</button>
                                @endif
                            </div>
                        </form>
                        <form method="POST" :action="link(urls.reveal, arrival.id)" class="vy-ul-reveal" @submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <button type="submit" class="vy-btn vy-btn--ghost">{{ __('livewire/units-list.reveal_stored') }}</button>
                            <span class="vy-gl-note">{{ __('livewire/units-list.reveal_note') }}</span>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </x-operator.shell>

    {{-- The page's controller — the shared list behaviour (selection, the row menu, search and the smooth links, in
         partials/operator-list-script) plus this page's own dialogs. Every link and form works without it. --}}
    @include('partials.operator-list-script')
    {{-- Every counted phrase the script shows, worked out here for each count it can show (a language may have more than
         two plural forms, so the script never picks "unit" or "units" itself). The fallbacks cover a number typed past a
         field's limit, which the server refuses anyway. --}}
    @php
        $vyWords = ['adults' => [], 'children' => [], 'adds' => [], 'addN' => [], 'pauseN' => [], 'resumeN' => []];
        foreach (range(0, 30) as $vyN) {
            $vyWords['adults'][$vyN] = trans_choice('livewire/units-list.adults_count', $vyN, ['count' => $vyN]);
            $vyWords['children'][$vyN] = trans_choice('livewire/units-list.children_count', $vyN, ['count' => $vyN]);
        }
        foreach (range(1, 100) as $vyN) {
            $vyWords['adds'][$vyN] = trans_choice('livewire/units-list.adds_n_units', $vyN, ['count' => $vyN, 'codes' => ':codes']);
            $vyWords['addN'][$vyN] = trans_choice('livewire/units-list.add_n_units', $vyN, ['count' => $vyN]);
        }
        foreach (range(0, max(\App\Support\Unit\UnitListParams::PER_PAGE_OPTIONS)) as $vyN) {
            $vyWords['pauseN'][$vyN] = trans_choice('livewire/units-list.pause_n_units_q', $vyN, ['count' => $vyN]);
            $vyWords['resumeN'][$vyN] = trans_choice('livewire/units-list.resume_n_units_q', $vyN, ['count' => $vyN]);
        }
        $vyWords['adultsMany'] = trans_choice('livewire/units-list.adults_count', 2, ['count' => ':n']);
        $vyWords['childrenMany'] = trans_choice('livewire/units-list.children_count', 2, ['count' => ':n']);
        $vyWords['addsMany'] = trans_choice('livewire/units-list.adds_n_units', 2, ['count' => ':n', 'codes' => ':codes']);
        $vyWords['addNMany'] = trans_choice('livewire/units-list.add_n_units', 2, ['count' => ':n']);
        $vyWords['pauseNMany'] = trans_choice('livewire/units-list.pause_n_units_q', 2, ['count' => ':n']);
        $vyWords['resumeNMany'] = trans_choice('livewire/units-list.resume_n_units_q', 2, ['count' => ':n']);
    @endphp
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vyUnits', (boot) => Object.assign(window.vyListBehaviour(boot, { empty: @js(__('livewire/units-list.empty_view')) }), {
                boot,
                words: @js($vyWords),
                unitPriceFor: null,
                rtOpen: false, rtForm: {},
                unitOpen: false, unitForm: {},
                editOpen: false, edit: {},
                delOpen: false, del: { id: '', code: '', deletable: false, blockers: [], active: true },
                rtEditOpen: false, rtEdit: {},
                rtDelOpen: false, rtDel: { id: '', name: '', deletable: false, blockers: [], units: 0 },
                photosFor: null,
                pauseOpen: false, pause: { bulk: false, resume: false, id: '', code: '' },
                arrivalOpen: false, arrival: {},

                start() {
                    this.rtForm = this.blankRoomType();
                    this.unitForm = this.blankUnit('');
                    this.edit = { id: '', was: '', code: '', name: '', max_occupancy: '' };
                    this.rtEdit = { id: '', was: '', name: '', kind: 'room', kindLabel: '', max_adults: 2, max_children: 0, unit_rows: 0 };
                    this.arrival = { id: '', label: '', has_code: false, ssid: '', has_wifi: false, has_notes: false };
                    const retry = boot.addRetry;
                    if (retry && retry.form === 'room_type') {
                        this.rtForm = Object.assign(this.blankRoomType(), retry);
                        this.rtOpen = true;
                    } else if (retry && retry.form === 'unit') {
                        this.unitForm = Object.assign(this.blankUnit(retry.room_type_id), retry);
                        this.unitOpen = true;
                    } else if (boot.retry) {
                        const row = this.row(boot.retry.id);
                        this.edit = Object.assign({ was: row ? row.code : '' }, boot.retry);
                        this.editOpen = true;
                    } else if (boot.rtRetry) {
                        this.openEditRoomType(boot.rtRetry.id);
                        this.rtEdit = Object.assign(this.rtEdit, boot.rtRetry);
                    } else if (boot.photos) {
                        this.photosFor = boot.photos;
                    } else if (boot.reveal) {
                        // the audited reveal comes back to the unit it was asked for, with its values shown once
                        this.act('arrival', this.row(boot.reveal) || { id: boot.reveal, code: '', name: null, arrival: {} });
                    }
                },
                blankRoomType() { return { name: '', kind: 'room', max_adults: 2, max_children: 0, property_id: '', unit_codes: '' }; },
                blankUnit(roomTypeId) {
                    const first = this.roomTypes()[0];
                    return { room_type_id: roomTypeId || (first ? first.value : ''), code: '', name: '', max_occupancy: '' };
                },
                roomTypes() { return @js($roomTypeOptions); },
                roomTypeSleeps(id) {
                    const rt = this.roomTypes().find((o) => o.value === id);
                    return rt ? String(rt.sleeps) : '';
                },
                sleepsText(form) {
                    const a = parseInt(form.max_adults, 10) || 0, c = parseInt(form.max_children, 10) || 0;
                    const adults = this.counted(this.words.adults, this.words.adultsMany, a);
                    return c > 0 ? @js(__('livewire/units-list.adults_and_children')).replace(':adults', () => adults)
                        .replace(':children', () => this.counted(this.words.children, this.words.childrenMany, c)) : adults;
                },
                // a counted phrase for n — from the server's list, or its general form for a number outside it
                counted(list, many, n) { return list[n] !== undefined ? list[n] : many.replace(':n', () => String(n)); },
                sentence(items) {
                    const list = items || [];
                    return list.length <= 1 ? (list[0] || '') : @js(__('livewire/units-list.list_and')).replace(':items', () => list.slice(0, -1).join(', ')).replace(':last', () => list[list.length - 1]);
                },

                // ── Unit codes — the server's UnitCodes, mirrored only to preview what will be added (the server decides).
                parseCodes(text) {
                    const parts = String(text || '').split(/[,;\r\n]+/).map((p) => p.trim()).filter((p) => p !== '');
                    const specs = parts.map((p) => {
                        const m = p.match(/^(.*?)(\d{1,9})\s*[-–—]\s*(?:\1)?(\d{1,9})$/);
                        if (m && parseInt(m[3], 10) > parseInt(m[2], 10)) {
                            return { prefix: m[1], from: parseInt(m[2], 10), width: m[2].length > 1 && m[2][0] === '0' ? m[2].length : 0,
                                count: parseInt(m[3], 10) - parseInt(m[2], 10) + 1 };
                        }
                        return { single: p, count: 1 };
                    });
                    const total = specs.reduce((n, s) => n + s.count, 0);
                    if (total > 100) return { codes: [], error: @js(__('livewire/units-list.too_many_codes')).replace(':total', () => total.toLocaleString('en')) };
                    const codes = [], seen = {};
                    for (const s of specs) {
                        const list = s.single !== undefined ? [s.single]
                            : Array.from({ length: s.count }, (_, i) => s.prefix + (s.width ? String(s.from + i).padStart(s.width, '0') : String(s.from + i)));
                        for (const c of list) {
                            if (c.length > 60) return { codes: [], error: @js(__('livewire/units-list.code_too_long')).replace(':code', () => c.slice(0, 24)) };
                            if (seen[c.toLowerCase()]) return { codes: [], error: @js(__('livewire/units-list.code_listed_twice')).replace(':code', () => c) };
                            seen[c.toLowerCase()] = true;
                            codes.push(c);
                        }
                    }
                    return { codes, error: '' };
                },
                codeCount(text) { return this.parseCodes(text).codes.length; },
                codeSummary(codes) {
                    const out = [];
                    let run = null;
                    const fmt = (r, n) => r.prefix + (r.width ? String(n).padStart(r.width, '0') : String(n));
                    const close = () => { if (run) out.push(run.end === run.start ? run.first : (run.end === run.start + 1 ? run.first + ', ' + fmt(run, run.end) : run.first + '–' + fmt(run, run.end))); };
                    for (const c of codes) {
                        const m = c.match(/^(.*?)(\d{1,9})$/);
                        if (m && run && m[1] === run.prefix && parseInt(m[2], 10) === run.end + 1 && fmt(run, run.end + 1) === c) { run.end++; continue; }
                        close();
                        run = m ? { prefix: m[1], width: m[2].length > 1 && m[2][0] === '0' ? m[2].length : 0, start: parseInt(m[2], 10), end: parseInt(m[2], 10), first: c } : null;
                        if (! m) out.push(c);
                    }
                    close();
                    const shown = out.slice(0, 6).join(', ');
                    return out.length > 6 ? @js(__('livewire/units-list.codes_and_more')).replace(':list', () => shown).replace(':number', () => String(out.length - 6)) : shown;
                },
                codesNote(text, propertyId) {
                    const p = this.parseCodes(text);
                    if (p.error) return { tone: 'err', text: p.error };
                    if (! p.codes.length) return { tone: 'hint', text: '' };
                    const taken = ((boot.taken || {})[propertyId] || []).map((c) => String(c).toLowerCase());
                    const clash = p.codes.filter((c) => taken.includes(c.toLowerCase()));
                    if (clash.length === 1) return { tone: 'err', text: @js(__('livewire/units-list.code_taken')).replace(':code', () => clash[0]) };
                    if (clash.length > 1) return { tone: 'err', text: @js(__('livewire/units-list.codes_taken')).replace(':codes', () => this.codeSummary(clash)) };
                    const n = p.codes.length;
                    return { tone: 'ok', text: this.counted(this.words.adds, this.words.addsMany, n).replace(':codes', () => this.codeSummary(p.codes)) };
                },
                unitSubmitLabel() { const n = this.codeCount(this.unitForm.code); return n > 1 ? this.counted(this.words.addN, this.words.addNMany, n) : @js(__('livewire/units-list.add_unit')); },

                // This page's rows, read from the JSON beside the table — swapped with the table, so it always describes
                // the rows on screen.
                row(id) {
                    const island = this.find('[data-ul-rows]');
                    if (! island) return null;
                    try { return JSON.parse(island.textContent)[id] || null; } catch (e) { return null; }
                },
                type(id) {
                    const island = this.find('[data-ul-types]');
                    if (! island) return null;
                    try { return JSON.parse(island.textContent)[id] || null; } catch (e) { return null; }
                },
                can(action) { return !! (this.menu.row && this.menu.row.actions && this.menu.row.actions.indexOf(action) !== -1); },
                // A form started here comes back to the page as it is now (the server rebuilds this from an allow-list).
                stamp(form) { const field = form.querySelector('input[name="return_query"]'); if (field) field.value = location.search.slice(1); },

                openAddRoomType() { this.closeMenu(false); this.rtForm = this.blankRoomType(); this.rtOpen = true; },
                openAddUnit(roomTypeId) { this.closeMenu(false); this.unitForm = this.blankUnit(roomTypeId); this.unitOpen = true; },
                openEditRoomType(id) {
                    const t = this.type(id);
                    if (! t) return;
                    this.rtEdit = { id: t.id, was: t.name, name: t.name, kind: t.kind_key, kindLabel: t.kind,
                        max_adults: t.adults, max_children: t.children, unit_rows: t.unit_rows };
                    this.rtEditOpen = true;
                },
                openDeleteRoomType(id) {
                    const t = this.type(id);
                    if (! t) return;
                    this.rtDel = { id: t.id, name: t.name, deletable: !! t.deletable, blockers: t.blockers || [], units: t.unit_rows };
                    this.rtDelOpen = true;
                },
                pauseTitle() {
                    if (this.pause.bulk) {
                        const n = this.count();
                        return this.pause.resume ? this.counted(this.words.resumeN, this.words.resumeNMany, n) : this.counted(this.words.pauseN, this.words.pauseNMany, n);
                    }
                    return (this.pause.resume ? @js(__('livewire/units-list.resume_unit_code_q')) : @js(__('livewire/units-list.pause_unit_code_q'))).replace(':code', () => this.pause.code);
                },

                // From the row menu: close it (focus goes back to the row's button, so a dialog closing returns it there).
                menuAct(action) { const row = this.menu.row; this.closeMenu(true); this.act(action, row); },
                act(action, row) {
                    if (! row) return;
                    if (action === 'edit') {
                        this.edit = { id: row.id, was: row.code, code: row.code, name: row.name || '', max_occupancy: row.occupancy };
                        this.editOpen = true;
                    } else if (action === 'price') {
                        this.unitPriceFor = row.id;
                    } else if (action === 'arrival') {
                        const a = row.arrival || {};
                        this.arrival = { id: row.id, label: row.name || row.code, has_code: !! a.has_code, ssid: a.ssid || '', has_wifi: !! a.has_wifi, has_notes: !! a.has_notes };
                        this.arrivalOpen = true;
                    } else if (action === 'pause' || action === 'resume') {
                        this.pause = { bulk: false, resume: action === 'resume', id: row.id, code: row.code };
                        this.pauseOpen = true;
                    } else if (action === 'delete') {
                        this.del = { id: row.id, code: row.code, deletable: !! row.deletable, blockers: row.blockers || [], active: !! row.is_active };
                        this.delOpen = true;
                    }
                },
                bulkPause(resume) { this.pause = { bulk: true, resume, id: '', code: '' }; this.pauseOpen = true; },
            }));
        });
    </script>
</div>

@once
    <style>
        [x-cloak] { display: none !important; }
    </style>
@endonce
