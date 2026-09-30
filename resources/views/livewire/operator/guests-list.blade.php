{{-- Operator GUESTS LIST — the CRM (#87), rebuilt 2026-09-29 on the founder's report (design approved the same day).
     Real guest profiles (operator-scoped, FULL contact — the CRM is the ONE PII surface, locked decision 4). One toolbar
     (search · All / In-house / Upcoming / VIP · Select · Add guest), a table that sorts by Guest, Stays and Last stay and
     pages 25 / 50 / 100, a menu on every row (Open profile · Edit · Erase personal data), and the bulk bar. See
     App\Livewire\Operator\GuestsList for the REAL / DERIVED map.

     ⚠️ PII SURFACE, the ONLY one. Email/phone are encrypted casts decrypted here deliberately. The component holds NO
     public props, so none of this round-trips over /livewire/update. Selection is CLIENT-SIDE (Alpine) keyed on ULID
     guest ids; each bulk form posts the selected ids to a real controller.

     ⚠️ EVERY SETTING IS A LINK. Filters, sorting, page size and pages are ordinary GET links that work with no script.
     The script below only makes them smooth: it fetches the SAME URL and swaps the two marked regions in place, so every
     change still runs every middleware (invariant #20).

     ⚠️ DIALOGS CENTRE BY CLASS. `.vy-modal-backdrop` carries the display; an inline `display: flex` beside x-show is
     erased the first time the dialog is hidden — which is how every dialog here opened in the top-left corner.
     XShowInlineDisplayContractTest keeps it that way. Each panel is also a `vy-modal-card` (near-opaque), so its labels
     clear AA over the scrim (DO-STATE-1).

     RAW-BLADE RULE: never write `word@directive` — every `@` below follows a space, newline, tag or quote. Guest EMAILS
     contain a literal `@` but are echoed through Blade as data, never written into the template. --}}
<div class="vy-gst">
    <x-operator.shell active="guests" title="Guests" :subtitle="$subtitle" :viewer="$viewer">

        @if (session('guest_notice'))
            <div class="vy-flash is-ok vy-fade">{{ session('guest_notice') }}</div>
        @endif
        @if (session('guest_error'))
            <div class="vy-flash is-err vy-fade">{{ session('guest_error') }}</div>
        @endif
        @if ($errors->any())
            <div class="vy-flash is-err vy-fade">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="vy-gl-root" x-data="vyGuests(@js($boot))" :class="selectMode && 'is-selecting'">

            {{-- ── Stat strip — DERIVED, recomputed from real rows (no design constant ported) ─────────── --}}
            <div class="vy-dash-kpis vy-dash-kpis--3 vy-fade">
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Guest profiles</div>
                    <div class="vy-dk-val">{{ $stats['profiles'] }}</div>
                    <div class="vy-dk-sub">in this workspace @if ($stats['anonymized'] > 0) · {{ $stats['anonymized'] }} anonymized @endif</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">In-house tonight</div>
                    <div class="vy-dk-val">{{ $stats['in_house'] }}</div>
                    <div class="vy-dk-sub">guests checked in · across every property</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Repeat guests</div>
                    @if ($stats['repeat_pct'] === null)
                        <div class="vy-dk-val">—</div>
                        <div class="vy-dk-sub">nobody has stayed yet</div>
                    @else
                        <div class="vy-dk-val">{{ $stats['repeat_pct'] }}<span class="vy-dk-unit">%</span></div>
                        <div class="vy-dk-sub">{{ $stats['repeat'] }} of {{ $stats['with_stays'] }} {{ \Illuminate\Support\Str::plural('guest', $stats['with_stays']) }} who have stayed booked again</div>
                    @endif
                </div>
            </div>

            {{-- ── The toolbar — search · filters · Select · Add guest ─────────────────────────────────── --}}
            <div class="vy-gl-bar vy-fade">
                {{-- Without script this is a plain GET form that keeps the other settings (the hidden fields); with it, the
                     results follow the typing. --}}
                <form class="vy-gl-search" method="GET" action="{{ route('app.guests') }}" role="search" @submit.prevent="search()">
                    @foreach ($keep as $keepName => $keepValue)
                        <input type="hidden" name="{{ $keepName }}" value="{{ $keepValue }}">
                    @endforeach
                    <svg class="vy-gl-search-ico" x-show="! loading" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    <span class="vy-gl-spinner" x-show="loading" x-cloak aria-hidden="true"></span>
                    <label class="vy-sr-only" for="vy-gl-q">Search guests</label>
                    <input id="vy-gl-q" class="vy-gl-search-in" type="search" name="q" value="{{ $query }}" x-model="q" x-ref="q"
                           @input.debounce.300ms="search()" placeholder="Search name, city or exact email" maxlength="100" autocomplete="off" spellcheck="false">
                    <button type="button" class="vy-gl-clear" x-show="q !== ''" x-cloak @click="clearSearch()" aria-label="Clear search">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                    <span class="vy-gl-tipwrap">
                        <button type="button" class="vy-gl-tip-btn" aria-label="What search can find" aria-controls="vy-gl-tip"
                                :aria-expanded="tip ? 'true' : 'false'" @click="tip = ! tip" @keydown.escape="tip = false">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5v.01"/></svg>
                        </button>
                        <span class="vy-gl-tip" id="vy-gl-tip" role="tooltip" x-show="tip" x-cloak x-transition.opacity @click.outside="tip = false">
                            Finds names and cities, and an email address typed in full. Phone numbers and partial emails are encrypted, so they can’t be searched.
                        </span>
                    </span>
                </form>

                <div class="vy-gl-chips" data-gl-region="chips" role="group" aria-label="Show">
                    @foreach ($chips as $chip)
                        <a class="vy-gl-chip {{ $chip['active'] ? 'is-on' : '' }}" href="{{ $chip['href'] }}" data-gl-key="chip-{{ $chip['key'] }}" @if ($chip['active']) aria-current="page" @endif>{{ $chip['label'] }}<span class="vy-gl-count">{{ $chip['count'] }}</span></a>
                    @endforeach
                </div>

                <div class="vy-gl-tools">
                    <button type="button" class="vy-btn vy-btn--ghost vy-gl-select" :class="selectMode && 'is-on'" :aria-pressed="selectMode ? 'true' : 'false'" @click="toggleMode()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/></svg>
                        <span x-text="selectMode ? 'Done' : 'Select'">Select</span>
                    </button>
                    {{-- WAVE 2 · #18 — gate the CONTROL on the same permission as the route ("never invite a 403"). --}}
                    @can('guests.manage')
                        <button type="button" class="vy-btn vy-btn--accent" @click="addOpen = true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            Add guest
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Said after every change, for screen readers — the new position in the list. --}}
            <p class="vy-sr-only" aria-live="polite" x-text="live"></p>

            {{-- ── The results — swapped in place by the script, and a whole page without it ──────────── --}}
            <div class="vy-gl-results" data-gl-region="results" :class="loading && 'is-loading'">
                @if ($total === 0)
                    <div class="vy-card vy-gl-empty vy-fade">
                        <span class="vy-gl-empty-ico" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M16 11.5a3 3 0 1 0 0-6M21 20c0-2.6-1.6-4.8-4-5.6"/></svg>
                        </span>
                        <h2 class="vy-gl-empty-title">No guests yet</h2>
                        <p class="vy-gl-empty-text">A guest profile is created with their first booking — or add one now.</p>
                        @can('guests.manage')
                            <button type="button" class="vy-btn vy-btn--accent" @click="addOpen = true">Add guest</button>
                        @endcan
                    </div>
                @elseif ($rows->total() === 0)
                    <div class="vy-card vy-gl-empty vy-fade">
                        <span class="vy-gl-empty-ico" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                        </span>
                        <h2 class="vy-gl-empty-title">
                            @if ($query !== '') No guests match “{{ $query }}” @else No guests in this view @endif
                        </h2>
                        <p class="vy-gl-empty-text">
                            Search matches name, city and an exact email address — phone and partial email are encrypted at rest, so they cannot be searched.
                            {{ $total }} {{ \Illuminate\Support\Str::plural('guest', $total) }} in total.
                        </p>
                        <a class="vy-btn vy-btn--ghost" href="{{ route('app.guests') }}" data-gl-key="clear-all">Clear search and filters</a>
                    </div>
                @else
                    <div class="vy-card vy-gl-card vy-fade">
                        <div class="vy-gl-progress" aria-hidden="true"></div>
                        <div class="vy-gl-scroll">
                            <table class="vy-gl-table vy-utable--stick-action">
                                <caption class="vy-sr-only">Guests, {{ $sortedBy }}</caption>
                                <thead>
                                    <tr>
                                        <th class="vy-gl-check" x-show="selectMode" x-cloak>
                                            <input type="checkbox" aria-label="Select every guest on this page"
                                                   :checked="allOnPage()" x-effect="$el.indeterminate = someOnPage()" @change="selectPage($event.target.checked)">
                                        </th>
                                        <th aria-sort="{{ $sorts['name']['aria'] }}">
                                            <a class="vy-gl-sort {{ $sorts['name']['active'] ? 'is-on is-'.$sorts['name']['dir'] : '' }}" href="{{ $sorts['name']['href'] }}" data-gl-key="sort-name">Guest<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                        </th>
                                        <th>Nationality</th>
                                        <th class="is-num" aria-sort="{{ $sorts['stays']['aria'] }}">
                                            <a class="vy-gl-sort {{ $sorts['stays']['active'] ? 'is-on is-'.$sorts['stays']['dir'] : '' }}" href="{{ $sorts['stays']['href'] }}" data-gl-key="sort-stays">Stays<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                        </th>
                                        <th aria-sort="{{ $sorts['last_stay']['aria'] }}">
                                            <a class="vy-gl-sort {{ $sorts['last_stay']['active'] ? 'is-on is-'.$sorts['last_stay']['dir'] : '' }}" href="{{ $sorts['last_stay']['href'] }}" data-gl-key="sort-last_stay">Last stay<span class="vy-gl-arrow" aria-hidden="true"></span></a>
                                        </th>
                                        {{-- Not sortable: lifetime is per currency, and ranking EUR against USD would invent an exchange rate. --}}
                                        <th class="is-num"><span title="Billed per currency — not sortable across currencies">Lifetime</span></th>
                                        <th>Status</th>
                                        <th class="vy-gl-actcol"><span class="vy-sr-only">Actions</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $row)
                                        @php
                                            $menu = ['id' => $row['id'], 'name' => $row['name'], 'profile' => route('app.guests.show', $row['id']),
                                                'edit' => $row['edit'], 'erase' => $canErase && ! $row['anonymized'], 'erased' => $row['anonymized']];
                                        @endphp
                                        <tr data-gl-id="{{ $row['id'] }}" class="{{ $row['anonymized'] ? 'is-erased' : '' }}" :class="sel['{{ $row['id'] }}'] && 'is-selected'">
                                            <td class="vy-gl-check" x-show="selectMode" x-cloak>
                                                <input type="checkbox" x-model="sel['{{ $row['id'] }}']" aria-label="Select {{ $row['name'] }}">
                                            </td>
                                            <td class="vy-gl-guestcell">
                                                <div class="vy-gl-guest">
                                                    <span class="vy-gl-avatar tone-{{ $row['tone'] }}" aria-hidden="true">{{ $row['initials'] }}</span>
                                                    <div class="vy-gl-who">
                                                        <div class="vy-gl-nameline">
                                                            <a class="vy-gl-name" href="{{ route('app.guests.show', $row['id']) }}">{{ $row['name'] }}</a>
                                                            @if ($row['is_vip']) <span class="vy-tag tone-vip"><span class="vy-dot"></span>VIP</span> @endif
                                                            @if ($row['risk_flag']) <span class="vy-tag tone-risk"><span class="vy-dot"></span>At risk</span> @endif
                                                            @foreach ($row['tags'] as $tag) <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ $tag }}</span> @endforeach
                                                        </div>
                                                        @if ($row['email'] !== null)
                                                            <div class="vy-gl-contact"><a href="mailto:{{ $row['email'] }}">{{ $row['email'] }}</a>@if ($row['phone'] !== null) <span class="vy-gl-sep">·</span> <span class="vy-gl-phone">{{ $row['phone'] }}</span>@endif</div>
                                                        @elseif ($row['phone'] !== null)
                                                            <div class="vy-gl-contact"><span class="vy-gl-phone">{{ $row['phone'] }}</span></div>
                                                        @elseif ($row['anonymized'])
                                                            <div class="vy-gl-contact">Erased</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>@if ($row['nationality'] !== null) {{ $row['nationality'] }} @else <span class="vy-gl-none">—</span> @endif</td>
                                            <td class="is-num"><span class="vy-gl-fig">{{ $row['stays'] }}</span></td>
                                            <td>
                                                @if ($row['last_stay'] === null)
                                                    <span class="vy-gl-none">—</span>
                                                @elseif ($row['last_stay'] === 'In house')
                                                    <span class="vy-gl-when is-live">In house</span>
                                                @else
                                                    <span class="vy-gl-when">{{ $row['last_stay'] }}</span>
                                                @endif
                                            </td>
                                            <td class="is-num">
                                                @forelse ($row['billed'] as $line)
                                                    <div class="vy-gl-fig">{{ $line }}</div>
                                                @empty
                                                    <span class="vy-gl-none">—</span>
                                                @endforelse
                                                @foreach ($row['balance'] as $line)
                                                    <div class="vy-gl-due">{{ $line }} due</div>
                                                @endforeach
                                            </td>
                                            <td>
                                                @if ($row['stage_meta'] !== null)
                                                    <span class="vy-tag tone-{{ $row['stage_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['stage_meta']['label'] }}</span>
                                                @else
                                                    <span class="vy-gl-none">No bookings</span>
                                                @endif
                                            </td>
                                            <td class="vy-gl-actcol">
                                                <button type="button" class="vy-gl-more" aria-label="Actions for {{ $row['name'] }}" aria-controls="vy-gl-menu"
                                                        :aria-expanded="menuFor === '{{ $row['id'] }}' ? 'true' : 'false'" @click="openMenu($event, @js($menu))">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5.5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="18.5" cy="12" r="1.7"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- The footer — where you are, how many per page, and the way to the next page. OUTSIDE the table: the
                             pager used to render inside <tbody>, which is not valid markup. --}}
                        <div class="vy-gl-foot" data-gl-foot>
                            <p class="vy-gl-summary" data-gl-summary tabindex="-1"><strong>{{ $rows->firstItem() }}–{{ $rows->lastItem() }}</strong> of <strong>{{ $rows->total() }}</strong> {{ \Illuminate\Support\Str::plural('guest', $rows->total()) }}<span class="vy-gl-sorted"> · {{ $sortedBy }}</span></p>
                            <div class="vy-gl-paging">
                                <span class="vy-gl-rows-label" id="vy-gl-rows">Rows</span>
                                <div class="vy-gl-sizes" role="group" aria-labelledby="vy-gl-rows">
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
                        </div>
                    </div>
                @endif
            </div>

            {{-- ── The row menu — ONE, placed beside the button that opened it. It lives outside the table card on purpose:
                 the card's blur and its entrance transform would otherwise become the box a fixed menu is placed in. ── --}}
            <div class="vy-gl-menu" id="vy-gl-menu" x-ref="menu" x-show="menu.open" x-cloak :class="menu.up && 'is-up'"
                 :style="{ top: menu.y + 'px', left: menu.x + 'px' }"
                 @click.outside="closeMenu(false)" @keydown.escape.prevent.stop="closeMenu(true)" @focusout="leaveMenu($event)">
                <a class="vy-gl-menu-item" :href="menu.row ? menu.row.profile : '#'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/></svg>
                    Open profile
                </a>
                <button type="button" class="vy-gl-menu-item" x-show="menu.row && menu.row.edit" @click="startEdit()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                    Edit details
                </button>
                <button type="button" class="vy-gl-menu-item is-danger" x-show="menu.row && menu.row.erase" @click="startErase()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                    Erase personal data
                </button>
                <p class="vy-gl-menu-note" x-show="menu.row && menu.row.erased">Personal data erased — there is nothing to edit.</p>
            </div>

            {{-- ── The bulk bar — select mode, one or more selected ───────────────────────────────────── --}}
            <div class="vy-gl-bulk" x-show="selectMode && count()" x-cloak x-transition role="region" aria-label="Selected guests">
                <span class="vy-gl-bulk-count"><strong x-text="count()"></strong> selected</span>
                <button type="button" class="vy-gl-bulk-clear" @click="clear()">Clear</button>
                <span class="vy-gl-bulk-sep" aria-hidden="true"></span>
                @can('guests.manage')
                    <button type="button" class="vy-gl-bulk-act" @click="msgOpen = true; marketing = false">Message</button>
                    <button type="button" class="vy-gl-bulk-act" @click="tagOpen = true">Add tag</button>
                @endcan
                {{-- Export — a real audited CSV download of exactly what this reader can already see --}}
                <form method="POST" action="{{ route('app.guests.bulk.export') }}">
                    @csrf
                    <template x-for="id in ids()" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                    <button type="submit" class="vy-gl-bulk-act">Export</button>
                </form>
                <button type="button" class="vy-gl-bulk-act is-danger" @click="delOpen = true; bulkTyped = ''">Erase</button>
            </div>

            {{-- ── MESSAGE (bulk) ────────────────────────────────────────────────────────────────────── --}}
            @can('guests.manage')
                <div class="vy-modal-backdrop" x-cloak x-show="msgOpen" x-transition.opacity @keydown.escape.window="msgOpen = false" @click.self="msgOpen = false">
                    <div class="vy-modal vy-modal-card" x-show="msgOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-gl-msg-t" tabindex="-1" x-trap.noscroll="msgOpen">
                        <form method="POST" action="{{ route('app.guests.bulk.message') }}" data-guard-submit>
                            @csrf
                            <template x-for="id in ids()" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16v10H8l-4 4z"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-gl-msg-t">Message <span x-text="count()"></span> <span x-text="count() === 1 ? 'guest' : 'guests'"></span></h3>
                                    <p class="vy-modal-sub">Sent by email · replies land in your inbox.</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="msgOpen = false" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-gl-msg-body">Message</label>
                                    <textarea id="vy-gl-msg-body" class="vy-input vy-gl-textarea" name="body" rows="5" maxlength="2000" required placeholder="Write your message…" autofocus></textarea>
                                </div>
                                <label class="vy-gl-switch">
                                    <input type="checkbox" name="marketing" value="1" x-model="marketing">
                                    <span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>This is a marketing message</strong><span>Only guests who agreed to marketing get it — the rest are skipped.</span></span>
                                </label>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="msgOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Send to <span x-text="count()"></span> <span x-text="count() === 1 ? 'guest' : 'guests'"></span></button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── ADD TAG (bulk) — reuses the real per-guest tag write ─────────────────────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="tagOpen" x-transition.opacity @keydown.escape.window="tagOpen = false" @click.self="tagOpen = false">
                    <div class="vy-modal vy-modal-card" x-show="tagOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-gl-tag-t" tabindex="-1" x-trap.noscroll="tagOpen">
                        <form method="POST" action="{{ route('app.guests.bulk.tag') }}" data-guard-submit>
                            @csrf
                            <template x-for="id in ids()" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.4"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-gl-tag-t">Tag <span x-text="count()"></span> <span x-text="count() === 1 ? 'guest' : 'guests'"></span></h3>
                                    <p class="vy-modal-sub">Tags are shared across this workspace — a new name joins the list.</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="tagOpen = false" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vy-field" style="margin-bottom: 0;">
                                    <label class="vy-field-label" for="vy-gl-tag-name">Tag</label>
                                    <input id="vy-gl-tag-name" class="vy-input" type="text" name="name" maxlength="255" required placeholder="e.g. Corporate" autofocus>
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="tagOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Add tag</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

            {{-- ── ERASE (bulk — GDPR) — HONEST copy: personal data erased, bookings and money kept ────────
                 ⚠️⚠️ D2 — THE DIALOG CONTRACT. `x-trap` moves focus in on open, traps it while open and returns it to the
                 trigger on close; focus lands on the panel (`tabindex="-1"`), never on the destructive button. It does NOT
                 make the rest of the page inert — stated rather than implied. --}}
            <div class="vy-modal-backdrop" x-cloak x-show="delOpen" x-transition.opacity @keydown.escape.window="delOpen = false" @click.self="delOpen = false">
                <div class="vy-modal vy-modal-card" x-show="delOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-erase-t" tabindex="-1" x-trap.noscroll="delOpen">
                    <div class="vy-modal-head">
                        <span class="vy-modal-ico is-danger">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                        </span>
                        <div class="vy-gl-head-text">
                            <h3 class="vy-modal-title" id="vy-dlg-erase-t">Erase <span x-text="count()"></span> <span x-text="count() === 1 ? 'guest' : 'guests'"></span>?</h3>
                            <p class="vy-modal-sub">Their personal data is erased for good. This can’t be undone.</p>
                        </div>
                        <button type="button" class="vy-modal-x" @click="delOpen = false" aria-label="Close">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                    @can('guests.erase')
                        <form method="POST" action="{{ route('app.guests.bulk.anonymize') }}" data-guard-submit
                              onsubmit="return confirm('Permanently erase the selected guests? Their personal data cannot be recovered.');">
                            @csrf
                            <template x-for="id in ids()" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                            <div class="vy-modal-body">
                                @include('livewire.operator.partials.guest-erase-ledger')
                                <div class="vy-field" style="margin-bottom: 0;">
                                    <label class="vy-field-label" for="bulk_confirm">Type ERASE to confirm</label>
                                    <input class="vy-input" type="text" id="bulk_confirm" name="confirm" x-model="bulkTyped" autocomplete="off" spellcheck="false" placeholder="ERASE" autofocus>
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="delOpen = false">Keep them</button>
                                <button type="submit" class="vy-btn vy-btn--danger" disabled :disabled="bulkTyped.trim() !== 'ERASE'">Erase <span x-text="count()"></span></button>
                            </div>
                        </form>
                    @else
                        <div class="vy-modal-body">
                            <p class="vy-gl-note">Erasing a guest needs the erase permission (Property Owner or Group Admin).</p>
                        </div>
                        <div class="vy-modal-foot">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="delOpen = false">Close</button>
                        </div>
                    @endcan
                </div>
            </div>

            @can('guests.manage')
                {{-- ── ADD GUEST — a real create ─────────────────────────────────────────────────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="addOpen" x-transition.opacity @keydown.escape.window="addOpen = false" @click.self="addOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide" x-show="addOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-gl-add-t" tabindex="-1" x-trap.noscroll="addOpen">
                        <form method="POST" action="{{ route('app.guests.store') }}" data-guard-submit>
                            @csrf
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10" cy="8" r="3.5"/><path d="M3.5 20c0-3.6 2.9-6.5 6.5-6.5 1.4 0 2.7.4 3.7 1.2M18 14v6M15 17h6"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-gl-add-t">Add guest</h3>
                                    <p class="vy-modal-sub">Their profile is created now — bookings attach to it later.</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="addOpen = false" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vy-gl-fields">
                                    <div class="vy-field"><label class="vy-field-label" for="a_first">First name</label><input class="vy-input" id="a_first" type="text" name="first_name" maxlength="255" autocomplete="off" autofocus></div>
                                    <div class="vy-field"><label class="vy-field-label" for="a_last">Last name</label><input class="vy-input" id="a_last" type="text" name="last_name" maxlength="255" required autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="a_email">Email</label><input class="vy-input" id="a_email" type="email" name="email" maxlength="255" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="a_phone">Phone</label><input class="vy-input" id="a_phone" type="tel" name="phone" maxlength="64" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="a_city">City / country</label><input class="vy-input" id="a_city" type="text" name="city" maxlength="255" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="a_nat">Nationality <span class="vy-fl-note">· 2 letters</span></label><input class="vy-input vy-gl-code" id="a_nat" type="text" name="nationality" maxlength="2" pattern="[A-Za-z]{2}" title="Two letters, e.g. IE" placeholder="IE" autocomplete="off"></div>
                                </div>
                                <div class="vy-gl-switches">
                                    <label class="vy-gl-switch"><input type="checkbox" name="is_vip" value="1"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>VIP guest</strong><span>Flagged on arrivals and at the front desk</span></span></label>
                                    <label class="vy-gl-switch"><input type="checkbox" name="prefers_email" value="1" checked><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Prefers email</strong><span>How they like to be contacted</span></span></label>
                                    <label class="vy-gl-switch"><input type="checkbox" name="marketing_consent" value="1"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Marketing consent</strong><span>They agreed to marketing email</span></span></label>
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="addOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Add guest</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── EDIT (one guest, from its row) — the profile's fields, as they are now. The stay preferences are not
                     shown here, and `keep_preferences` tells the server to leave them as they are. Comes back to this list. ── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="editOpen" x-transition.opacity @keydown.escape.window="editOpen = false" @click.self="editOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide" x-show="editOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-gl-edit-t" tabindex="-1" x-trap.noscroll="editOpen">
                        <form method="POST" :action="link(urls.edit, edit.id)" data-guard-submit @submit="$refs.rqEdit.value = location.search.slice(1)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="" x-ref="rqEdit">
                            <input type="hidden" name="keep_preferences" value="1">
                            <input type="hidden" name="_guest" :value="edit.id">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-gl-edit-t">Edit guest</h3>
                                    <p class="vy-modal-sub" x-text="edit.name"></p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="editOpen = false" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                {{-- An edit the server refused comes back open (GuestsList::editRetry) — with its reason here,
                                     not only in the page's flash behind the dialog. --}}
                                @if ($errors->any() && is_string(old('_guest')))
                                    <div class="vy-gl-errors" role="alert">
                                        @foreach ($errors->all() as $error)
                                            <div>{{ $error }}</div>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="vy-gl-fields">
                                    <div class="vy-field"><label class="vy-field-label" for="vy-gl-e-first">First name</label><input class="vy-input" id="vy-gl-e-first" type="text" name="first_name" maxlength="255" x-model="edit.first_name" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="vy-gl-e-last">Last name</label><input class="vy-input" id="vy-gl-e-last" type="text" name="last_name" maxlength="255" x-model="edit.last_name" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="vy-gl-e-email">Email</label><input class="vy-input" id="vy-gl-e-email" type="email" name="email" maxlength="255" x-model="edit.email" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="vy-gl-e-phone">Phone</label><input class="vy-input" id="vy-gl-e-phone" type="tel" name="phone" maxlength="64" x-model="edit.phone" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="vy-gl-e-city">City / country</label><input class="vy-input" id="vy-gl-e-city" type="text" name="city" maxlength="255" x-model="edit.city" autocomplete="off"></div>
                                    <div class="vy-field"><label class="vy-field-label" for="vy-gl-e-nat">Nationality <span class="vy-fl-note">· 2 letters</span></label><input class="vy-input vy-gl-code" id="vy-gl-e-nat" type="text" name="nationality" maxlength="2" pattern="[A-Za-z]{2}" title="Two letters, e.g. IE" placeholder="IE" x-model="edit.nationality" autocomplete="off"></div>
                                </div>
                                <div class="vy-gl-switches">
                                    <label class="vy-gl-switch"><input type="checkbox" name="is_vip" value="1" x-model="edit.is_vip"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>VIP guest</strong><span>Flagged on arrivals and at the front desk</span></span></label>
                                    <label class="vy-gl-switch"><input type="checkbox" name="risk_flag" value="1" x-model="edit.risk_flag"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>At risk</strong><span>Flag this guest for the team</span></span></label>
                                    <label class="vy-gl-switch"><input type="checkbox" name="prefers_email" value="1" x-model="edit.prefers_email"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Prefers email</strong><span>How they like to be contacted</span></span></label>
                                    <label class="vy-gl-switch"><input type="checkbox" name="marketing_consent" value="1" x-model="edit.marketing_consent"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Marketing consent</strong><span>They agreed to marketing email</span></span></label>
                                </div>
                                <p class="vy-gl-note">Stay preferences, notes and tags stay as they are — change them on the <a class="vy-gl-link" :href="link(urls.profile, edit.id)">guest’s profile</a>.</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="editOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

            @can('guests.erase')
                {{-- ── ERASE (one guest, from its row) — typed ERASE, checked here as you type and again on the server ── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="eraseOpen" x-transition.opacity @keydown.escape.window="eraseOpen = false" @click.self="eraseOpen = false">
                    <div class="vy-modal vy-modal-card" x-show="eraseOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-gl-erase-t" tabindex="-1" x-trap.noscroll="eraseOpen">
                        <form method="POST" :action="link(urls.erase, erase.id)" data-guard-submit @submit="$refs.rqErase.value = location.search.slice(1)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="" x-ref="rqErase">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico is-danger">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                                </span>
                                <div class="vy-gl-head-text">
                                    <h3 class="vy-modal-title" id="vy-gl-erase-t">Erase <span x-text="erase.name"></span>?</h3>
                                    <p class="vy-modal-sub">Their personal data is erased for good. This can’t be undone.</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="eraseOpen = false" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                @include('livewire.operator.partials.guest-erase-ledger')
                                <div class="vy-field" style="margin-bottom: 0;">
                                    <label class="vy-field-label" for="vy-gl-erase-word">Type ERASE to confirm</label>
                                    <input class="vy-input" type="text" id="vy-gl-erase-word" name="confirm" x-model="typed" autocomplete="off" spellcheck="false" placeholder="ERASE" autofocus>
                                    <span class="vy-rm-check" :class="typed.trim() === 'ERASE' && 'is-ok'" aria-live="polite"
                                          x-text="typed.trim() === 'ERASE' ? 'Ready to erase' : (typed.trim() === '' ? '' : 'Type ERASE in capitals')"></span>
                                </div>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="eraseOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--danger" disabled :disabled="typed.trim() !== 'ERASE'">Erase personal data</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan
        </div>
    </x-operator.shell>

    {{-- The page's controller — the shared list behaviour (selection, the row menu, search and the smooth links, in
         partials/operator-list-script) plus this page's own dialogs. Every link and form works without it. --}}
    @include('partials.operator-list-script')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('vyGuests', (boot) => Object.assign(window.vyListBehaviour(boot, { empty: 'No guests in this view' }), {
                msgOpen: false, marketing: false, tagOpen: false, delOpen: false, addOpen: false,
                editOpen: false, eraseOpen: false,
                edit: {}, erase: { id: '', name: '' }, typed: '', bulkTyped: '',

                start() {
                    this.edit = this.blank();
                    if (boot.retry) {
                        this.edit = Object.assign(this.blank(), boot.retry);
                        this.editOpen = true;
                    }
                },
                blank() {
                    return { id: '', name: '', first_name: '', last_name: '', email: '', phone: '', city: '', nationality: '',
                        is_vip: false, risk_flag: false, prefers_email: false, marketing_consent: false };
                },
                startEdit() {
                    const row = this.menu.row;
                    this.closeMenu(true);   // focus goes back to the row's button, so closing the dialog returns it there
                    this.edit = Object.assign(this.blank(), row.edit);
                    this.editOpen = true;
                },
                startErase() {
                    const row = this.menu.row;
                    this.closeMenu(true);
                    this.erase = { id: row.id, name: row.name };
                    this.typed = '';
                    this.eraseOpen = true;
                },
            }));
        });
    </script>
</div>

@once
    <style>
        [x-cloak] { display: none !important; }
    </style>
@endonce
