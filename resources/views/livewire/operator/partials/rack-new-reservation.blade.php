{{-- The Calendar's NEW RESERVATION dialog — rebuilt 2026-10-04 (the founder: "the design is messed, guest first name, last
     name, and their boxes are not well aligned"). 🔬 The guest row was a three-column grid holding five pieces, so "Last name"
     sat on the first line and its box on the second. Now the Owners dialogs' structure: the shared solid card, each field its
     label above its box, a foot that stays in view.

     Two phases, #20-safe: the stay and party (a GET "Check rack" through the tenant middleware) → the REAL free units
     (UnitAvailabilityService) → Confirm, a POST to the certified booking write (createBooking's row lock). The page never
     sends a price; the server re-derives it.
     ⚠️⚠️ THE GUEST'S NAME NEVER RIDES THE GET (#28): it stays in this tab (sessionStorage) across "Check rack" and only travels
     in the booking's POST. ⚠️ Nothing here claims what it does not do: every booking from here creates a new guest, and no
     email is asked for, so none is sent. RAW-BLADE RULE: Alpine uses x-on:/x-bind (no `@click`). --}}
@php
    $__rnr = $newRes;
    $__rnrPre = collect($__rnr['units'])->firstWhere('unit_id', $__rnr['preselect']);
    $__rnrDates = \Carbon\CarbonImmutable::parse($__rnr['form']['check_in'])->format('j M').' → '
        .\Carbon\CarbonImmutable::parse($__rnr['form']['check_out'])->format('j M');
    $__rnrNights = $__rnr['nights'] === 1 ? '1 night' : $__rnr['nights'].' nights';
    $__rnrBack = '?start='.$window['start'].'&days='.$window['selected_days'];
@endphp
<div x-data="{
        open: true, dirty: false, booking: false, nameMissing: false,
        key: 'vyrobel:rack-new-reservation:guest',
        who: @js(substr(hash('sha256', (string) auth()->id()), 0, 16)),
        gf: '', gl: '',
        adults: {{ (int) $__rnr['form']['adults'] }},
        source: @js($__rnr['form']['source']),
        sel: @js($__rnr['preselect']), selTotal: @js($__rnrPre['total_display'] ?? ''), selLabel: @js($__rnrPre !== null ? $__rnrPre['room_type_name'].' · '.$__rnrPre['unit_label'] : ''),
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
            // back to a page that had already sent its booking: start again rather than a stuck Booking state
            window.addEventListener('pageshow', (e) => { if (e.persisted && this.booking) location.reload(); });
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
        close() { this.open = false; this.forget(); history.replaceState({}, '', location.pathname + @js($__rnrBack)); },
    }">
    <div class="vy-modal-backdrop" x-show="open" x-transition.opacity x-on:click.self="close()" x-on:keydown.escape.window="close()">
        <div class="vy-modal vy-modal-card vy-modal--wide vyo-dlg vy-rnr" x-show="open" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-rnr-title" tabindex="-1" x-trap.noscroll="open">
            <div class="vy-modal-head">
                <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4M12 13v5M9.5 15.5h5"/></svg></span>
                <div class="vyo-dlg-head">
                    <h3 class="vy-modal-title" id="vy-rnr-title">New reservation</h3>
                    <p class="vyo-dlg-sub">{{ $property['name'] }} · a quick booking — extras and payment are in the full form</p>
                </div>
                <button type="button" class="vy-modal-x" x-on:click="close()" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
            </div>

            @if ($__rnr['rooms'] === 0)
                {{-- ⚠️ Nothing to book here — said, rather than a form that can only ever answer "nothing is free". --}}
                <div class="vy-modal-body">
                    <div class="vy-rnr-empty is-block">
                        @if ($__rnr['rooms_all'] === 0)
                            <strong>{{ $property['name'] }} has no rooms yet</strong>
                            <span>A booking needs a unit to stay in. Add this property’s units, then book them here.</span>
                        @else
                            <strong>None of {{ $property['name'] }}’s units can take a booking</strong>
                            <span>They’re all paused or out of service. Reopen one on the Units page, then book it here.</span>
                        @endif
                    </div>
                </div>
                <div class="vy-modal-foot">
                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="close()">Close</button>
                    <a class="vy-btn vy-btn--accent" x-on:click="forget()" href="{{ route('app.units') }}">Go to Units</a>
                </div>
            @else
                <div class="vy-modal-body">
                    {{-- Phase 1 — who and when (a GET; the names stay in this tab and are not part of it) --}}
                    <form method="GET" action="{{ route('app.calendar') }}" class="vy-rnr-stay" x-on:submit="keep()">
                        <input type="hidden" name="start" value="{{ $window['start'] }}">
                        <input type="hidden" name="days" value="{{ $window['selected_days'] }}">
                        <input type="hidden" name="new" value="1">
                        <input type="hidden" name="adults" x-bind:value="adults">
                        <input type="hidden" name="source" x-bind:value="source">
                        <input type="hidden" name="unit" x-bind:value="sel">
                        <input type="hidden" name="kept" value="1">

                        <div class="vyo-pair">
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-rnr-gf">First name</label>
                                <input id="vy-rnr-gf" class="vy-input" type="text" x-model="gf" maxlength="80" autocomplete="off" autofocus>
                            </div>
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-rnr-gl">Last name</label>
                                <input id="vy-rnr-gl" class="vy-input" type="text" x-model="gl" maxlength="80" autocomplete="off">
                            </div>
                        </div>
                        <p class="vy-rnr-problem" x-show="nameMissing" x-cloak role="alert">Enter the guest’s first or last name.</p>

                        <div class="vy-rnr-when">
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-rnr-ci">Check-in</label>
                                <input id="vy-rnr-ci" class="vy-input" type="date" name="ci" value="{{ $__rnr['form']['check_in'] }}" min="{{ $__rnr['today'] }}" autocomplete="off" x-on:change="dirty = true">
                            </div>
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-rnr-co">Check-out</label>
                                <input id="vy-rnr-co" class="vy-input" type="date" name="co" value="{{ $__rnr['form']['check_out'] }}" min="{{ $__rnr['today'] }}" autocomplete="off" x-on:change="dirty = true">
                            </div>
                            <div class="vy-field">
                                <span class="vy-field-label" id="vy-rnr-adults-l">Adults</span>
                                <div class="vy-stepper vy-rnr-stepper" role="group" aria-labelledby="vy-rnr-adults-l">
                                    <button type="button" x-on:click="adults = Math.max(1, adults - 1)" x-bind:disabled="adults &lt;= 1" aria-label="Fewer adults">&minus;</button>
                                    <span x-text="adults" aria-live="polite">{{ (int) $__rnr['form']['adults'] }}</span>
                                    <button type="button" x-on:click="adults = Math.min(12, adults + 1)" x-bind:disabled="adults &gt;= 12" aria-label="More adults">+</button>
                                </div>
                            </div>
                        </div>

                        @if ($__rnr['date_problem'] !== null)
                            <p class="vy-rnr-problem" role="alert">{{ $__rnr['date_problem'] }}</p>
                        @endif
                        <p class="vy-rnr-problem" x-show="dirty" x-cloak role="status">The dates changed — check the rack again to see what’s free.</p>
                        <button type="submit" class="vy-btn vy-btn--ghost vy-rnr-check">Check rack</button>
                    </form>

                    {{-- Phase 2 — what is free, its price, and the source --}}
                    @if ($__rnr['checked'])
                        <div class="vy-rnr-free">
                            <div class="vy-field-label" id="vy-rnr-free-l">What’s free · {{ $__rnrDates }} · {{ $__rnrNights }}</div>
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
                                        <span class="vy-rnr-unit-sub">{{ $u['room_type_name'] }} · {{ $__rnrNights }}{{ $u['sleeps'] > 0 ? ' · sleeps '.$u['sleeps'] : '' }}</span>
                                    </span>
                                    <span class="vy-rnr-unit-rate">{{ $u['total_display'] }}</span>
                                    <span class="vy-rnr-radio" aria-hidden="true"></span>
                                </button>
                                {{-- the certified per-unit POST (hidden); the total is NOT sent — the server re-derives it --}}
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
                                </form>
                            @endforeach
                            </div>
                            @foreach ($__rnr['waiting'] as $w)
                                {{-- ⚠️ said, never skipped: free units, no price for these nights --}}
                                <div class="vy-rnr-waiting">
                                    <strong>{{ $w['name'] }}</strong>
                                    @if ($w['has_plan'])
                                        <span>{{ $w['units'] }} free · No price for these nights yet.@if ($__rnr['can_set_price']) <a class="vy-rnr-link" x-on:click="forget()" href="{{ $__rnr['full_form_url'] }}">Set a price in the full form</a>@else Ask someone who manages rates to set one.@endif</span>
                                    @else
                                        {{-- ⚠️ no plan at all: the full form cannot price it either — Rates is where a plan is added --}}
                                        <span>{{ $w['units'] }} free · It has no rate plan yet, so no price.@if ($__rnr['can_set_price']) <a class="vy-rnr-link" x-on:click="forget()" href="{{ route('app.rates') }}">Add a rate plan on the Rates page</a>@else Someone who manages rates can add one.@endif</span>
                                    @endif
                                </div>
                            @endforeach
                            @if ($__rnr['units'] === [] && $__rnr['waiting'] === [])
                                <p class="vy-rnr-empty">Nothing is free for these dates — try other dates.</p>
                            @endif
                        </div>

                        @if ($__rnr['units'] !== [])
                            <fieldset class="vy-rnr-sources">
                                <legend class="vy-field-label">Source</legend>
                                <div class="vy-rnr-pills">
                                    @foreach ($__rnr['sources'] as $val => $label)
                                        <button type="button" class="vy-source-pill" x-bind:class="source === @js($val) ? 'on' : ''" x-bind:aria-pressed="String(source === @js($val))" x-on:click="source = @js($val)">{{ $label }}</button>
                                    @endforeach
                                </div>
                            </fieldset>
                            <div class="vy-rnr-total">
                                <span class="vy-rnr-total-lbl" x-text="sel ? selLabel : 'Pick a unit above'">{{ $__rnrPre !== null ? $__rnrPre['room_type_name'].' · '.$__rnrPre['unit_label'] : 'Pick a unit above' }}</span>
                                <span class="vy-rnr-total-amt" x-text="sel ? selTotal : '—'">{{ $__rnrPre['total_display'] ?? '—' }}</span>
                            </div>
                            @if ($__rnr['adds_charges'])
                                <p class="vy-rnr-fine">Room only — the property’s taxes and fees are added on the folio.</p>
                            @endif
                        @endif
                    @endif

                    <p class="vy-rnr-fine">Books it straight away, as a new guest — no extras, payment or email from here; if a stay rule such as a minimum stay applies, the full form opens to sort it out. <a class="vy-rnr-link" x-on:click="forget()" href="{{ $__rnr['full_form_url'] }}">Open the full form</a> for those.</p>
                </div>

                <div class="vy-modal-foot">
                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="close()">Cancel</button>
                    @if ($__rnr['checked'] && $__rnr['units'] !== [])
                        {{-- one Confirm: submits the SELECTED unit's certified form (its x-bind values flushed by the earlier pick) --}}
                        <button type="button" class="vy-btn vy-btn--accent" x-bind:disabled="! sel || dirty || booking" x-on:click="confirm()">
                            <span x-text="booking ? 'Booking…' : 'Confirm reservation'">Confirm reservation</span>
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
