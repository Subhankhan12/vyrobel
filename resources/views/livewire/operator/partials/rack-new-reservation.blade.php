{{-- The Calendar's NEW RESERVATION dialog — rebuilt 2026-10-04 (the founder: "the design is messed, guest first name, last
     name, and their boxes are not well aligned"). 🔬 The guest row was a three-column grid holding five pieces, so "Last name"
     sat on the first line and its box on the second. Now the Owners dialogs' structure: the shared solid card, each field its
     label above its box, a foot that stays in view.

     Two phases, #20-safe: the stay and party (a GET "Check rack" through the tenant middleware) → the REAL free units
     (UnitAvailabilityService) → Confirm, a POST to the certified booking write (createBooking's row lock). The page never
     sends a price; the server re-derives it.
     ⚠️⚠️ THE GUEST'S NAME NEVER RIDES THE GET (#28): it stays in this tab (sessionStorage) across "Check rack" and only travels
     in the booking's POST. ⚠️ Nothing here claims what it does not do: every booking from here creates a new guest, and no
     email is asked for, so none is sent. RAW-BLADE RULE: Alpine uses x-on:/x-bind (no `@click`).

     ⚠️⚠️ 2026-10-04, AGAIN (his next report: "it is not possible to do reservation" — on production his rooms had no price and his
     €200 "Winter" season was saved but never applied): a room with no price is fixed from in here, as on the New reservation
     page — Set price (the Rates write's own rules) or Apply the saved season (behind the Rates page's own disclosure, one
     partial) — and each comes back INTO this dialog on the same stay, the clicked unit picked, the name back from the tab
     ({@see \App\Support\Operator\CalendarReturn}). A property with no rooms offers the ones that have some. --}}
@php
    $__rnr = $newRes;
    $__rnrPre = collect($__rnr['units'])->firstWhere('unit_id', $__rnr['preselect']);
    $__rnrDates = \Carbon\CarbonImmutable::parse($__rnr['form']['check_in'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M')).' '.\App\Support\I18n\Locales::forward().' '
        .\Carbon\CarbonImmutable::parse($__rnr['form']['check_out'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M'));
    $__rnrNights = trans_choice('livewire/operator/partials/rack-new-reservation.nights', $__rnr['nights'], ['count' => $__rnr['nights']]);
    $__rnrBack = '?start='.$window['start'].'&days='.$window['selected_days'].$window['rt_q'];
    // the "Open the full form" link inside its sentence (rule 13: the Alpine attribute stays out of the translation)
    $__rnrFullFormHtml = '<a class="vy-rnr-link" x-on:click="forget()" href="'.e($__rnr['full_form_url']).'">'.e(__('livewire/operator/partials/rack-new-reservation.open_full_form')).'</a>';
@endphp
<div x-data="{
        open: true, dirty: false, booking: false, nameMissing: false, applying: false,
        key: 'vyrobel:rack-new-reservation:guest',
        who: @js(substr(hash('sha256', (string) auth()->id()), 0, 16)),
        gf: '', gl: '',
        adults: {{ (int) $__rnr['form']['adults'] }},
        source: @js($__rnr['form']['source']),
        sel: @js($__rnr['preselect']), selTotal: @js($__rnrPre['total_display'] ?? ''), selLabel: @js($__rnrPre !== null ? $__rnrPre['room_type_name'].' · '.$__rnrPre['unit_label'] : ''),
        confirmSeason: null, seasons: {}, priceErr: {}, priceBusy: -1,
        init() {
            // ⚠️ The name comes back ONLY across this dialog's own Check rack (the kept marker it sends — never the name);
            //    any other way in starts empty and forgets it, so a guest's name never fills the NEXT booking (the review).
            //    And only for the person who typed it: sign-out does not clear this tab, so the next person to sign in here
            //    could otherwise reach it through the history (the review).
            if (new URLSearchParams(location.search).get('kept') === '1') {
                try { const g = JSON.parse(sessionStorage.getItem(this.key) || 'null'); if (g && g.who === this.who) { this.gf = String(g.gf || ''); this.gl = String(g.gl || ''); } else { this.forget(); } } catch (e) {}
            } else { this.forget(); }
            this.$watch('gf', () => { this.keep(); this.nameMissing = false; }); this.$watch('gl', () => { this.keep(); this.nameMissing = false; });
            // a browser that restores the date boxes on reload must not leave Confirm booking the dates checked before
            const ci = document.getElementById('vy-rnr-ci'); const co = document.getElementById('vy-rnr-co');
            if (ci && co && (ci.value !== @js($__rnr['form']['check_in'] ?? '') || co.value !== @js($__rnr['form']['check_out'] ?? ''))) this.dirty = true;
            // the facts each Apply view shows — data, never parsed back out of the page
            try { this.seasons = JSON.parse((document.getElementById('vy-rnr-seasons') || {}).textContent || '{}') || {}; } catch (e) {}
            // back to a page that had already sent its booking, a price or a season: start again rather than a stuck busy state
            window.addEventListener('pageshow', (e) => { if (e.persisted && (this.booking || this.applying || this.priceBusy !== -1)) location.reload(); });
        },
        keep() { try { sessionStorage.setItem(this.key, JSON.stringify({ who: this.who, gf: this.gf, gl: this.gl })); } catch (e) {} },
        forget() { try { sessionStorage.removeItem(this.key); } catch (e) {} },
        pick(el) { this.sel = el.dataset.unit; this.selTotal = el.dataset.total; this.selLabel = el.dataset.label; },
        confirm() {
            if (! this.sel || this.dirty || this.booking) return;
            // the write refuses a guest with no name — say so here, beside the boxes, rather than on another page
            if (this.gf.trim() === '' && this.gl.trim() === '') { this.nameMissing = true; document.getElementById('vy-rnr-gf').focus(); return; }
            this.booking = true;
            this.forget();   // the POST carries the names; a refusal returns them on the full form
            document.getElementById('vy-rnr-f-' + this.sel).requestSubmit();
        },
        // ⚠️ From the Apply view, Escape, the cross and a click outside step back to the booking, as the New reservation page's does —
        //    they closed the whole dialog and forgot the guest's name (the review, L4)
        close() { if (this.confirmSeason) { this.closeSeason(); return; } this.open = false; this.forget(); history.replaceState({}, '', location.pathname + @js($__rnrBack)); },
        // ⚠️ Opens the Apply view — the Rates page's own disclosure; only its own button applies. The name typed so far is kept
        //    in the tab, to come back with the dialog.
        openSeason(id) {
            this.keep(); this.confirmSeason = this.seasons[id] || null;
            this.$nextTick(() => requestAnimationFrame(() => requestAnimationFrame(() => { const b = document.getElementById('vy-rnr-back-' + id); if (b) b.focus(); })));
        },
        closeSeason() {
            const id = this.confirmSeason ? this.confirmSeason.id : ''; this.confirmSeason = null;
            this.$nextTick(() => requestAnimationFrame(() => requestAnimationFrame(() => { const b = document.getElementById('vy-rnr-apply-' + id); if (b) b.focus(); })));
        },
        // the server's own money reader (LocalNumber/MoneyInput, D-MONEY-COMMA): 150,50 and 150.50 are both cents
        priceSubmit(e, i) {
            const field = document.getElementById('vy-rnr-rate-' + i); const raw = field ? field.value : '';
            if (window.vyRnrPriceMinor(raw) === null) { e.preventDefault(); this.priceErr[i] = @js(__('livewire/operator/partials/rack-new-reservation.price_err_invalid')); return; }
            this.keep(); this.priceBusy = i;
        },
    }">
    <div class="vy-modal-backdrop" x-show="open" x-transition.opacity x-on:click.self="close()" x-on:keydown.escape.window="close()">
        <div class="vy-modal vy-modal-card vy-modal--wide vyo-dlg vy-rnr" x-show="open" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-rnr-title" tabindex="-1" x-trap.noscroll="open">
            <div class="vy-modal-head">
                <span class="vy-modal-ico" aria-hidden="true" x-show="! confirmSeason"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4M12 13v5M9.5 15.5h5"/></svg></span>
                <span class="vy-modal-ico is-danger" aria-hidden="true" x-show="confirmSeason" x-cloak><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg></span>
                <div class="vyo-dlg-head">
                    <h3 class="vy-modal-title" id="vy-rnr-title" x-text="confirmSeason ? @js(__('livewire/operator/partials/rack-new-reservation.apply_season_title')) : @js(__('livewire/operator/partials/rack-new-reservation.title'))">{{ __('livewire/operator/partials/rack-new-reservation.title') }}</h3>
                    <p class="vyo-dlg-sub" x-show="! confirmSeason">{{ __('livewire/operator/partials/rack-new-reservation.subtitle', ['property' => $property['name']]) }}</p>
                    <p class="vyo-dlg-sub" x-show="confirmSeason" x-cloak x-text="confirmSeason ? confirmSeason.name + ' · ' + confirmSeason.nightly + ' · ' + confirmSeason.from + ' ' + @js(\App\Support\I18n\Locales::forward()) + ' ' + confirmSeason.to : ''"></p>
                </div>
                <button type="button" class="vy-modal-x" x-on:click="close()" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
            </div>

            @if ($__rnr['rooms'] === 0)
                {{-- ⚠️ Nothing to book here — said, rather than a form that can only ever answer "nothing is free". --}}
                <div class="vy-modal-body">
                    {{-- a price or a season sent from the dialog, which came back here (the working property changed meanwhile) --}}
                    @if ($__rnr['error'] !== null)
                        <p class="vy-rnr-msg is-err" role="alert">{{ $__rnr['error'] }}</p>
                    @elseif ($__rnr['ok'] !== null)
                        <p class="vy-rnr-msg is-ok" role="status">{{ $__rnr['ok'] }}</p>
                    @endif
                    <div class="vy-rnr-empty is-block">
                        @if ($__rnr['rooms_all'] === 0)
                            <strong>{{ __('livewire/operator/partials/rack-new-reservation.no_rooms_title', ['property' => $property['name']]) }}</strong>
                            <span>{{ __('livewire/operator/partials/rack-new-reservation.no_rooms_body') }}</span>
                        @else
                            <strong>{{ __('livewire/operator/partials/rack-new-reservation.none_bookable_title', ['property' => $property['name']]) }}</strong>
                            <span>{{ __('livewire/operator/partials/rack-new-reservation.none_bookable_body') }}</span>
                        @endif
                    </div>
                    {{-- ⚠️⚠️ 2026-10-04 (his workspace): "Go to Units" was the only way on, while his other property had three rooms. Choosing
                         one switches the working property (this session only) and reopens this dialog there. --}}
                    @if ($__rnr['others'] !== [])
                        <form method="POST" action="{{ route('app.property.switch') }}" class="vy-rnr-others" aria-labelledby="vy-rnr-others-h">
                            @csrf
                            <h4 id="vy-rnr-others-h" class="vy-rnr-others-h">{{ __('livewire/operator/partials/rack-new-reservation.book_other_property') }}</h4>
                            @foreach ($__rnr['others'] as $__p)
                                <button type="submit" name="property_id" value="{{ $__p['id'] }}" class="vy-rnr-other">
                                    <svg class="vy-rnr-other-ico" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>
                                    <span class="vy-rnr-other-name">{{ $__p['name'] }}</span>
                                    <span class="vy-rnr-other-rooms">{{ trans_choice('livewire/operator/partials/rack-new-reservation.rooms', $__p['rooms'], ['count' => $__p['rooms']]) }}</span>
                                    <svg class="vy-rnr-other-go" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                                </button>
                            @endforeach
                        </form>
                    @endif
                </div>
                <div class="vy-modal-foot">
                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="close()">{{ __('common.close') }}</button>
                    <a class="vy-btn vy-btn--accent" x-on:click="forget()" href="{{ route('app.units') }}">{{ __('livewire/operator/partials/rack-new-reservation.go_to_units') }}</a>
                </div>
            @else
                <div class="vy-modal-body" x-show="! confirmSeason">
                    {{-- what a price set or a season applied from here said — inside the dialog it came back to --}}
                    @if ($__rnr['error'] !== null)
                        <p class="vy-rnr-msg is-err" role="alert">{{ $__rnr['error'] }}</p>
                    @elseif ($__rnr['ok'] !== null)
                        {{-- what comes next, from what the dialog shows — the unit clicked may already be picked --}}
                        <p class="vy-rnr-msg is-ok" role="status">{{ $__rnr['ok'] }}{{ $__rnrPre !== null ? ' '.__('livewire/operator/partials/rack-new-reservation.unit_picked', ['unit' => $__rnrPre['unit_label']]) : ($__rnr['units'] !== [] ? ' '.__('livewire/operator/partials/rack-new-reservation.pick_unit_below') : '') }}</p>
                    @endif

                    {{-- Phase 1 — who and when (a GET; the names stay in this tab and are not part of it) --}}
                    <form method="GET" action="{{ route('app.calendar') }}" class="vy-rnr-stay" x-on:submit="keep()">
                        <input type="hidden" name="start" value="{{ $window['start'] }}">
                        <input type="hidden" name="days" value="{{ $window['selected_days'] }}">
                        @if ($window['rt'] !== '')
                            <input type="hidden" name="rt" value="{{ $window['rt'] }}">
                        @endif
                        <input type="hidden" name="new" value="1">
                        <input type="hidden" name="adults" x-bind:value="adults">
                        <input type="hidden" name="source" x-bind:value="source">
                        <input type="hidden" name="unit" x-bind:value="sel || @js($__rnr['clicked'])">
                        <input type="hidden" name="kept" value="1">

                        <div class="vyo-pair">
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-rnr-gf">{{ __('livewire/operator/partials/rack-new-reservation.first_name') }}</label>
                                <input id="vy-rnr-gf" class="vy-input" type="text" x-model="gf" maxlength="80" autocomplete="off" autofocus>
                            </div>
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-rnr-gl">{{ __('livewire/operator/partials/rack-new-reservation.last_name') }}</label>
                                <input id="vy-rnr-gl" class="vy-input" type="text" x-model="gl" maxlength="80" autocomplete="off">
                            </div>
                        </div>
                        <p class="vy-rnr-problem" x-show="nameMissing" x-cloak role="alert">{{ __('livewire/operator/partials/rack-new-reservation.name_missing') }}</p>

                        <div class="vy-rnr-when">
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-rnr-ci">{{ __('livewire/operator/partials/rack-new-reservation.check_in') }}</label>
                                <input id="vy-rnr-ci" class="vy-input" type="date" name="ci" value="{{ $__rnr['form']['check_in'] }}" min="{{ $__rnr['today'] }}" autocomplete="off" x-on:change="dirty = true">
                            </div>
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-rnr-co">{{ __('livewire/operator/partials/rack-new-reservation.check_out') }}</label>
                                <input id="vy-rnr-co" class="vy-input" type="date" name="co" value="{{ $__rnr['form']['check_out'] }}" min="{{ $__rnr['today'] }}" autocomplete="off" x-on:change="dirty = true">
                            </div>
                            <div class="vy-field">
                                <span class="vy-field-label" id="vy-rnr-adults-l">{{ __('livewire/operator/partials/rack-new-reservation.adults') }}</span>
                                <div class="vy-stepper vy-rnr-stepper" role="group" aria-labelledby="vy-rnr-adults-l">
                                    <button type="button" x-on:click="adults = Math.max(1, adults - 1)" x-bind:disabled="adults &lt;= 1" aria-label="{{ __('livewire/operator/partials/rack-new-reservation.fewer_adults') }}">&minus;</button>
                                    <span x-text="adults" aria-live="polite">{{ (int) $__rnr['form']['adults'] }}</span>
                                    <button type="button" x-on:click="adults = Math.min(12, adults + 1)" x-bind:disabled="adults &gt;= 12" aria-label="{{ __('livewire/operator/partials/rack-new-reservation.more_adults') }}">+</button>
                                </div>
                            </div>
                        </div>

                        @if ($__rnr['date_problem'] !== null)
                            <p class="vy-rnr-problem" role="alert">{{ $__rnr['date_problem'] }}</p>
                        @endif
                        <p class="vy-rnr-problem" x-show="dirty" x-cloak role="status">{{ __('livewire/operator/partials/rack-new-reservation.dates_changed') }}</p>
                        <button type="submit" class="vy-btn vy-btn--ghost vy-rnr-check">{{ __('livewire/operator/partials/rack-new-reservation.check_rack') }}</button>
                    </form>

                    {{-- Phase 2 — what is free, its price, and the source --}}
                    @if ($__rnr['checked'])
                        <div class="vy-rnr-free">
                            <div class="vy-field-label" id="vy-rnr-free-l">{{ __('livewire/operator/partials/rack-new-reservation.whats_free', ['dates' => $__rnrDates, 'nights' => $__rnrNights]) }}</div>
                            <div class="vy-rnr-units" role="radiogroup" aria-labelledby="vy-rnr-free-l">
                            @foreach ($__rnr['units'] as $u)
                                {{-- data-* carries the (possibly apostrophe'd) room name into Alpine, never into a JS literal --}}
                                <button type="button" class="vy-rnr-unit" role="radio"
                                        data-unit="{{ $u['unit_id'] }}" data-total="{{ $u['total_display'] }}" data-label="{{ $u['room_type_name'] }} · {{ $u['unit_label'] }}"
                                        x-bind:class="sel === @js($u['unit_id']) ? 'is-sel' : ''" x-bind:aria-checked="String(sel === @js($u['unit_id']))"
                                        x-on:click="pick($event.currentTarget)">
                                    <span class="vy-rnr-unit-ico" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/></svg></span>
                                    <span class="vy-rnr-unit-info">
                                        {{-- the unit's code first: on a phone the line is cut, and the code is the part that matters --}}
                                        <span class="vy-rnr-unit-name">{{ $u['unit_label'] }}</span>
                                        <span class="vy-rnr-unit-sub">{{ $u['room_type_name'] }} · {{ $__rnrNights }}{{ $u['sleeps'] > 0 ? ' · '.__('livewire/operator/partials/rack-new-reservation.sleeps', ['guests' => $u['sleeps']]) : '' }}</span>
                                    </span>
                                    <span class="vy-rnr-unit-rate">{{ $u['total_display'] }}</span>
                                    <span class="vy-rnr-radio" aria-hidden="true"></span>
                                </button>
                                {{-- the certified per-unit POST (hidden); the total is NOT sent — the server re-derives it. ⚠️ With the way back to
                                     THIS Calendar (2026-10-04): a booking made here comes back here, not to the full form's done screen. --}}
                                <form method="POST" action="{{ route('app.reservations.store') }}" id="vy-rnr-f-{{ $u['unit_id'] }}" class="vy-rnr-hidden" data-guard-submit>
                                    @csrf
                                    <input type="hidden" name="check_in" value="{{ $__rnr['form']['check_in'] }}">
                                    <input type="hidden" name="check_out" value="{{ $__rnr['form']['check_out'] }}">
                                    <input type="hidden" name="room_type_id" value="{{ $u['room_type_id'] }}">
                                    <input type="hidden" name="unit_id" value="{{ $u['unit_id'] }}">
                                    <input type="hidden" name="adults" x-bind:value="adults">
                                    <input type="hidden" name="source" x-bind:value="source">
                                    <input type="hidden" name="guest_first_name" x-bind:value="gf">
                                    <input type="hidden" name="guest_last_name" x-bind:value="gl">
                                    @include('livewire.operator.partials.calendar-return-fields', ['__stay' => false])
                                </form>
                            @endforeach
                            </div>
                            @foreach ($__rnr['waiting'] as $w)
                                {{-- ⚠️ said, never skipped: free units, no price for these nights — and, for whoever manages rates, fixed here --}}
                                <div class="vy-rnr-waiting">
                                    <strong>{{ $w['name'] }}</strong>
                                    @if ($w['has_plan'])
                                        <span>{{ $w['unpriced'] >= $__rnr['nights'] ? trans_choice('livewire/operator/partials/rack-new-reservation.no_price_all', $__rnr['nights'], ['count' => $__rnr['nights'], 'units' => $w['units']]) : trans_choice('livewire/operator/partials/rack-new-reservation.no_price_some', $__rnr['nights'], ['count' => $__rnr['nights'], 'units' => $w['units'], 'unpriced' => $w['unpriced']]) }}@if (! $__rnr['can_set_price'] && $w['seasons'] === []) {{ __('livewire/operator/partials/rack-new-reservation.ask_rates_manager_price') }}@endif</span>
                                        {{-- ⚠️⚠️ A SAVED SEASON, NEVER APPLIED — his "Winter" on production. Said, and applied from here by whoever
                                             manages rates: the button only opens the Rates page's own disclosure (below), never applies. --}}
                                        @foreach ($w['seasons'] as $season)
                                            <div class="vy-rnr-season">
                                                <p class="vy-rnr-season-why">{!! trans_choice($season['prices'] === $season['missing'] ? 'livewire/operator/partials/rack-new-reservation.season_saved_all' : 'livewire/operator/partials/rack-new-reservation.season_saved_some', $season['missing'], ['count' => e($season['missing']), 'season' => e($season['name']), 'nightly' => e($season['nightly']), 'from' => e($season['from']), 'to' => e($season['to']), 'prices' => e($season['prices'])]) !!}</p>
                                                @if ($__rnr['can_set_price'])
                                                    <button type="button" class="vy-btn vy-btn--accent vy-rnr-season-go" id="vy-rnr-apply-{{ $season['id'] }}" x-bind:disabled="dirty" x-on:click="openSeason(@js($season['id']))">{{ __('livewire/operator/partials/rack-new-reservation.apply_season_button', ['season' => $season['name']]) }}</button>
                                                @else
                                                    <p class="vy-rnr-fine vy-rnr-fine--tight">{{ $season['prices'] === $season['missing'] ? __('livewire/operator/partials/rack-new-reservation.manager_can_apply_then_book') : __('livewire/operator/partials/rack-new-reservation.manager_can_apply') }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                        @if ($__rnr['can_set_price'])
                                            {{-- ⚠️⚠️ THE STAY'S MISSING PRICE, SET HERE — the Rates write with the same rules, for exactly these nights on the
                                                 room type's main plan, then back into this dialog. It was a link to the full form that left the booking. --}}
                                            <form method="POST" action="{{ route('app.rates.update') }}" class="vy-rnr-setprice" data-guard-submit x-on:submit="priceSubmit($event, {{ $loop->index }})">
                                                @csrf
                                                <input type="hidden" name="room_type_id" value="{{ $w['room_type_id'] }}">
                                                <input type="hidden" name="start_date" value="{{ $__rnr['form']['check_in'] }}">
                                                <input type="hidden" name="end_date" value="{{ $__rnr['last_night'] }}">
                                                <input type="hidden" name="set_rate" value="1">
                                                @include('livewire.operator.partials.calendar-return-fields', ['__stay' => true])
                                                <label class="vy-field-label" for="vy-rnr-rate-{{ $loop->index }}">{!! $w['seasons'] !== [] ? trans_choice('livewire/operator/partials/rack-new-reservation.price_label_or', $__rnr['nights'], ['room_type' => e($w['name'])]) : __('livewire/operator/partials/rack-new-reservation.price_label', ['room_type' => e($w['name'])]) !!}</label>
                                                <div class="vy-rnr-setprice-row">
                                                    <span class="vy-rnr-money">
                                                        <span class="vy-rnr-money-sym" aria-hidden="true">{{ $w['symbol'] }}</span>
                                                        <input id="vy-rnr-rate-{{ $loop->index }}" class="vy-input" type="text" name="rate" inputmode="decimal" placeholder="{{ __('livewire/operator/partials/rack-new-reservation.price_placeholder') }}" autocomplete="off" aria-describedby="vy-rnr-rate-err-{{ $loop->index }}" x-bind:aria-invalid="priceErr[{{ $loop->index }}] ? 'true' : 'false'" x-on:input="priceErr[{{ $loop->index }}] = ''">
                                                    </span>
                                                    <button type="submit" class="vy-btn {{ $w['seasons'] !== [] ? 'vy-btn--ghost' : 'vy-btn--accent' }}" aria-label="{{ __('livewire/operator/partials/rack-new-reservation.set_price_for', ['room_type' => $w['name']]) }}" x-bind:disabled="dirty || priceBusy === {{ $loop->index }}" x-text="priceBusy === {{ $loop->index }} ? @js(__('livewire/operator/partials/rack-new-reservation.setting')) : @js(__('livewire/operator/partials/rack-new-reservation.set_price'))">{{ __('livewire/operator/partials/rack-new-reservation.set_price') }}</button>
                                                </div>
                                                <p class="vy-rnr-problem vy-rnr-problem--tight" id="vy-rnr-rate-err-{{ $loop->index }}" x-show="priceErr[{{ $loop->index }}]" x-text="priceErr[{{ $loop->index }}]" x-cloak role="alert"></p>
                                                @if ($__rnr['nights'] === 1)
                                                    <p class="vy-rnr-fine vy-rnr-fine--tight">{{ $w['plan_name'] !== '' ? __('livewire/operator/partials/rack-new-reservation.price_goes_one_plan', ['date' => \Carbon\CarbonImmutable::parse($__rnr['form']['check_in'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M')), 'plan' => $w['plan_name']]) : __('livewire/operator/partials/rack-new-reservation.price_goes_one', ['date' => \Carbon\CarbonImmutable::parse($__rnr['form']['check_in'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M'))]) }}</p>
                                                @else
                                                    <p class="vy-rnr-fine vy-rnr-fine--tight">{{ $w['plan_name'] !== '' ? __('livewire/operator/partials/rack-new-reservation.price_goes_many_plan', ['from' => \Carbon\CarbonImmutable::parse($__rnr['form']['check_in'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M')), 'to' => \Carbon\CarbonImmutable::parse($__rnr['last_night'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M')), 'plan' => $w['plan_name']]) : __('livewire/operator/partials/rack-new-reservation.price_goes_many', ['from' => \Carbon\CarbonImmutable::parse($__rnr['form']['check_in'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M')), 'to' => \Carbon\CarbonImmutable::parse($__rnr['last_night'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M'))]) }}</p>
                                                @endif
                                            </form>
                                        @endif
                                    @else
                                        {{-- ⚠️ no plan at all: the full form cannot price it either — Rates is where a plan is added --}}
                                        <span>{{ __('livewire/operator/partials/rack-new-reservation.no_rate_plan', ['units' => $w['units']]) }}@if ($__rnr['can_set_price']) <a class="vy-rnr-link" x-on:click="forget()" href="{{ route('app.rates') }}">{{ __('livewire/operator/partials/rack-new-reservation.add_rate_plan_link') }}</a>@else {{ __('livewire/operator/partials/rack-new-reservation.manager_can_add_plan') }}@endif</span>
                                    @endif
                                </div>
                            @endforeach
                            @if ($__rnr['units'] === [] && $__rnr['waiting'] === [])
                                <p class="vy-rnr-empty">{{ __('livewire/operator/partials/rack-new-reservation.nothing_free') }}</p>
                            @endif
                        </div>

                        @if ($__rnr['units'] !== [])
                            <fieldset class="vy-rnr-sources">
                                <legend class="vy-field-label">{{ __('livewire/operator/partials/rack-new-reservation.source') }}</legend>
                                <div class="vy-rnr-pills">
                                    @foreach ($__rnr['sources'] as $val => $label)
                                        <button type="button" class="vy-source-pill" x-bind:class="source === @js($val) ? 'on' : ''" x-bind:aria-pressed="String(source === @js($val))" x-on:click="source = @js($val)">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </fieldset>
                            <div class="vy-rnr-total">
                                <span class="vy-rnr-total-lbl" x-text="sel ? selLabel : @js(__('livewire/operator/partials/rack-new-reservation.pick_unit_above'))">{{ $__rnrPre !== null ? $__rnrPre['room_type_name'].' · '.$__rnrPre['unit_label'] : __('livewire/operator/partials/rack-new-reservation.pick_unit_above') }}</span>
                                <span class="vy-rnr-total-amt" x-text="sel ? selTotal : '—'">{{ $__rnrPre['total_display'] ?? '—' }}</span>
                            </div>
                            @if ($__rnr['adds_charges'])
                                <p class="vy-rnr-fine">{{ __('livewire/operator/partials/rack-new-reservation.room_only') }}</p>
                            @endif
                        @endif
                    @endif

                    <p class="vy-rnr-fine">{!! __('livewire/operator/partials/rack-new-reservation.books_straight_away', ['link' => $__rnrFullFormHtml]) !!}</p>
                </div>

                <div class="vy-modal-foot" x-show="! confirmSeason">
                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="close()">{{ __('common.cancel') }}</button>
                    @if ($__rnr['checked'] && $__rnr['units'] !== [])
                        {{-- one Confirm: submits the SELECTED unit's certified form (its x-bind values flushed by the earlier pick) --}}
                        <button type="button" class="vy-btn vy-btn--accent" x-bind:disabled="! sel || dirty || booking" x-on:click="confirm()">
                            <span x-text="booking ? @js(__('livewire/operator/partials/rack-new-reservation.booking_busy')) : @js(__('livewire/operator/partials/rack-new-reservation.confirm_reservation'))">{{ __('livewire/operator/partials/rack-new-reservation.confirm_reservation') }}</span>
                        </button>
                    @endif
                </div>

                {{-- ⚠️⚠️ "APPLY THIS SEASON?" — the Rates page's own disclosure (one partial), inside this dialog in place of the booking:
                     what is overwritten, that it cannot be undone, what somebody negotiated. Nothing is written until its own button. --}}
                @foreach ($__rnr['seasons'] as $season)
                    <div class="vy-modal-body vyr-prose vy-rnr-confirm" x-show="confirmSeason !== null &amp;&amp; confirmSeason.id === @js($season['id'])" x-cloak>
                        @include('livewire.operator.partials.season-apply-disclosure')
                        <p>{{ trans_choice('livewire/operator/partials/rack-new-reservation.for_this_booking', $season['missing'], ['count' => $season['missing'], 'prices' => $season['prices']]) }}</p>
                    </div>
                    <form method="POST" action="{{ $season['apply_url'] }}" class="vy-modal-foot" x-show="confirmSeason !== null &amp;&amp; confirmSeason.id === @js($season['id'])" x-cloak data-guard-submit x-on:submit="keep(); applying = true">
                        @csrf
                        @include('livewire.operator.partials.calendar-return-fields', ['__stay' => true])
                        <button type="button" class="vy-btn vy-btn--ghost" id="vy-rnr-back-{{ $season['id'] }}" x-on:click="closeSeason()">{{ __('common.back') }}</button>
                        <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/partials/rack-new-reservation.apply_and_overwrite') }}</button>
                    </form>
                @endforeach
                {{-- the price read the New reservation page's way (its toMinor) — in a script block, where a typed currency sign may be named
                     (the currency guard reads tenant-facing markup, not scripts) --}}
                @once @include('partials.local-number-script') @endonce
                <script>
                    window.vyRnrPriceMinor = function (raw) { return window.vyLocalNumber.toMinor(raw); };   // D-MONEY-COMMA — the one reader
                </script>
                <script type="application/json" id="vy-rnr-seasons">{!! json_encode((object) $__rnr['seasons'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>
            @endif
        </div>
    </div>
</div>
