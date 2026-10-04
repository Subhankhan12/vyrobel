{{-- Operator NEW RESERVATION — a focused wizard on the certified createBooking path (no app shell: the operator LAYOUT supplies
     the gradient, orbs and tokens; this page draws its own top bar with a close-X to the reservations list).
     The Stay step's availability is a REAL server read (GET → UnitAvailabilityService — only genuinely-free, priced units;
     createBooking re-checks under a row lock). The other steps are client-side over that render and post as ONE form →
     ReservationCreateController, which posts the real reconciling folio lines (#17). The summary is an ESTIMATE from
     the same real prices and the same tax and fee rules; the authoritative total is the folio once created.
     GUARDS: honest availability · guest NAME-only search (#28) · NO raw PAN (card = honest "coming soon", #45).

     ⚠️⚠️ 2026-10-04 (the founder: "it is not possible to do reservation"). A booking now (1) asks which property while
     "All properties" is selected, and offers a picker in the top bar; (2) says when a property has no rooms; (3) lets a
     person who manages rates set a missing price for exactly the stay's nights, here, through the Rates write's own rules,
     and comes back ready to book; (4) keeps everything entered when it is refused and reopens at the step to fix — never
     a new guest's email or phone (#28); (5) checks each step before moving on and says what is missing; (6) prices in the
     stay's own currency, with a unit's own price where one is set.
     ⚠️⚠️ 2026-10-04, the SECOND pass (he again: "it is not possible to do reservation … more cool, advanced, minimal and
     operational"). 🔬 On production his €200 "Winter" season was saved and never applied, and the page never said so; the
     property he was in had no rooms, with only a small picker naming the way out. Now: a property with no rooms offers the
     ones that can take a booking; a saved season covering the stay can be applied from here (the Rates page's own Apply);
     SEVEN steps are FOUR (Stay · Guest · Extras, only where there are some · Confirm); the calendar carries each night's
     lowest price and what is left; house rules say "Not set" rather than a rule nobody chose; no sentence promises what the
     product does not do (directions in the confirmation, an email to a guest with no address, a check-in code a guest
     cannot use). RAW-BLADE RULE: Alpine uses x-on:/x-bind (no `@click`); every `@` follows a space, a newline or a tag.
     ⚠️ No double quote inside an attribute's script: the first one ends the attribute (the Calendar dialog lost 38 bindings that way). --}}
@php
    $__nrReadOnly = app(\App\Support\Operator\OperatorWorkspace::class)->isReadOnly();
    $__nrWizard = $hasProperty && $rooms['bookable'] > 0;   // the steps render (and their script) only when there is something to book
    $__nrOrder = array_keys($steps);                         // the step numbers this property asks, in order
    $__nrShownAt = array_flip($__nrOrder);                   // step number → its place (0-based)
    $__nrHasExtras = isset($steps[\App\Livewire\Operator\NewReservation::STEP_EXTRAS]);
@endphp
@if ($done)
    <div class="vy-nr-standalone">
        <header class="vy-nr-topbar vy-fade">
            <div class="vy-nr-brand">
                <span class="vy-nr-logo" aria-hidden="true">V</span>
                <span class="vy-nr-brand-id">
                    <h1 class="vy-nr-brand-title">Reservation confirmed</h1>
                    <span class="vy-nr-brand-sub">{{ $done['property'] !== '' ? $done['property'] : 'Direct entry' }}</span>
                </span>
            </div>
            <a class="vy-nr-close" href="{{ route('app.reservations') }}" title="Close" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
            </a>
        </header>
        @if (session('folio_notice'))
            <div class="vy-flash is-ok vy-nr-flash vy-fade" role="status">{{ session('folio_notice') }}</div>
        @endif
        <div class="vy-nr-donewrap vy-fade">
            <div class="vy-nr-donecheck" aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
            </div>
            <div class="vy-nr-donetitle">Reservation confirmed</div>
            <div class="vy-nr-doneref">Reference <span class="vy-u-code">{{ $done['ref'] }}</span> · it’s on the rack now</div>
            @if ($done['can_sign_in'])
                <div class="vy-nr-qr">{!! $done['qr'] !!}</div>
                <div class="vy-nr-qrlabel">Self check-in code</div>
                {{-- ⚠️ The "from HH:MM" clause is OMITTED when the property has published no check-in time,
                     rather than inventing one (D8-2). One expression, not @if: Livewire wraps a directive in
                     [if BLOCK] markers, which splits composed text in the delivered HTML. --}}
                <div class="vy-nr-qrsub">{{ $done['guest'] }} scans to open online check-in{{ $done['check_in_time'] !== null ? ' from '.$done['check_in_time'] : '' }} — in the guest portal (they sign in first). No check-in code is stored here.</div>
            @else
                {{-- ⚠️ 2026-10-04 — the guest portal is claimed BY EMAIL: without an address the QR led nowhere, so it is not drawn. --}}
                <p class="vy-nr-doneno">{{ $done['guest'] }} can check in online once they have an email address{{ $done['guest_url'] !== null ? ' — add one on their guest profile.' : ' — someone who manages guests can add one on their profile.' }}</p>
                @if ($done['guest_url'] !== null)
                    <a class="vy-nr-link" href="{{ $done['guest_url'] }}">Open their guest profile</a>
                @endif
            @endif
            <div class="vy-nr-doneactions">
                <a href="{{ $done['show_url'] }}" class="vy-nr-donebtn is-primary">View reservation</a>
                <a href="{{ $done['create_url'] }}" class="vy-nr-donebtn">New reservation</a>
            </div>
            {{-- ⚠️ THE DRAFT DIES WITH THE BOOKING IT BECAME — otherwise "New reservation" would open pre-filled with the
                 booking just made. Cleared here, on success, not on the Confirm click: a refused booking keeps it. --}}
            {{-- …and the Calendar dialog's guest name, kept in the tab only across its "Check rack" (it books through here too). --}}
            <script>try { sessionStorage.removeItem('vyrobel:new-reservation:draft'); localStorage.removeItem('vyrobel:new-reservation:draft'); sessionStorage.removeItem('vyrobel:rack-new-reservation:guest'); } catch (e) {}</script>
        </div>
    </div>
@else
<div class="vy-nr-standalone" @if ($__nrWizard) x-data="newReservationWizard()" @endif>
    <header class="vy-nr-topbar vy-fade">
        <div class="vy-nr-brand">
            <span class="vy-nr-logo" aria-hidden="true">V</span>
            <span class="vy-nr-brand-id">
                <h1 class="vy-nr-brand-title">New reservation</h1>
                {{-- ⚠️ This focused screen has no app shell, so no top-bar switcher: a person with several properties
                     chooses here which one the booking is for. The same POST as the app's switcher. --}}
                @if ($hasProperty && count($properties) > 1)
                    <form method="POST" action="{{ route('app.property.switch') }}" class="vy-nr-propform">
                        @csrf
                        <label class="vy-nr-proplabel" for="vy-nr-prop">Booking into</label>
                        <select id="vy-nr-prop" name="property_id" class="vy-nr-propselect" onchange="this.form.submit()">
                            @foreach ($properties as $__p)
                                <option value="{{ $__p['id'] }}" @selected($__p['id'] === $propertyId)>{{ $__p['name'] }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="vy-nr-textbtn">Switch</button></noscript>
                    </form>
                @else
                    <span class="vy-nr-brand-sub">{{ $subtitle !== '' ? $subtitle : ($chooseProperty ? 'All properties' : 'Direct entry') }}</span>
                @endif
            </span>
        </div>
        <div class="vy-nr-topright">
            @if ($__nrWizard)
                <span class="vy-nr-stepcount" x-text="'Step ' + (place() + 1) + ' of ' + order.length">Step {{ ($__nrShownAt[$initialStep] ?? 0) + 1 }} of {{ count($__nrOrder) }}</span>
            @endif
            <a class="vy-nr-close" href="{{ route('app.reservations') }}" title="Close" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
            </a>
        </div>
    </header>

    {{-- A price or a season just set comes back as "pick a unit" — its place is the Stay step, where the unit is picked
         (it used to stay above every step, all the way to payment). Only without the wizard is it shown up here. --}}
    @if (session('new_reservation_ok') && ! $__nrWizard)
        <div class="vy-flash is-ok vy-nr-flash vy-fade" role="status">{{ session('new_reservation_ok') }}</div>
    @endif
    @if (session('new_reservation_notice'))
        <div class="vy-flash is-err vy-nr-flash vy-fade" role="alert">{{ session('new_reservation_notice') }}</div>
    @endif

    @if ($__nrReadOnly)
        {{-- #84 — read-only workspace: creation is OFF (also enforced server-side at createBooking). --}}
        <div class="vy-nr-ro vy-fade" role="status">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            <span>Your workspace is paused — new bookings are off until you resume from Billing.</span>
        </div>
    @endif

    @if ($chooseProperty)
        {{-- ⚠️⚠️ "All properties" is a roll-up for READING. A booking belongs to one property, so the wizard asks —
             it used to book silently into the first one, which on production had no rooms at all. --}}
        <section class="vy-nr-card vy-nr-choose vy-fade" aria-labelledby="vy-nr-choose-h">
            <h2 id="vy-nr-choose-h" class="vy-nr-h">Which property is this booking for?</h2>
            <p class="vy-nr-lede">You’re looking at all your properties. A booking belongs to one — choose it to see what’s free.</p>
            <form method="POST" action="{{ route('app.property.switch') }}" class="vy-nr-choose-list">
                @csrf
                @foreach ($properties as $__p)
                    <button type="submit" name="property_id" value="{{ $__p['id'] }}" class="vy-nr-choose-item">
                        <svg class="vy-nr-choose-ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>
                        <span class="vy-nr-choose-name">{{ $__p['name'] }}</span>
                        <span class="vy-nr-choose-rooms">{{ $__p['rooms_all'] === 0 ? 'No rooms yet' : ($__p['rooms'] === 0 ? 'No rooms open for booking' : ($__p['rooms'] === 1 ? '1 room' : $__p['rooms'].' rooms')) }}</span>
                        <svg class="vy-nr-choose-go" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                    </button>
                @endforeach
            </form>
            <p class="vy-nr-fine">The rest of the app switches to that property too, until you choose All properties again.</p>
        </section>
    @elseif (! $hasProperty)
        <section class="vy-nr-card vy-nr-blank vy-fade">
            <h2 class="vy-nr-h">No property yet</h2>
            <p class="vy-nr-lede">Add a property before taking bookings.</p>
        </section>
    @elseif ($rooms['bookable'] === 0)
        {{-- ⚠️ Nothing to step through: a property with no rooms used to show the whole wizard and "No units are free for
             those dates" — on production, the reason no booking could be made. It says what is missing instead.
             ⚠️⚠️ 2026-10-04 (his second report) — and offers the properties that CAN take a booking, as buttons: "choose it at
             the top" named a small picker he did not see, and the page was a dead end while his other property had rooms. --}}
        <section class="vy-nr-card vy-nr-blank vy-fade" aria-labelledby="vy-nr-norooms">
            @if ($rooms['all'] === 0)
                <h2 id="vy-nr-norooms" class="vy-nr-h">{{ $subtitle }} has no rooms yet</h2>
                <p class="vy-nr-lede">A booking needs a unit to stay in. Add this property’s units first — then they can be booked here.</p>
            @else
                <h2 id="vy-nr-norooms" class="vy-nr-h">None of {{ $subtitle }}’s units can take a booking</h2>
                <p class="vy-nr-lede">They’re all paused or out of service. Reopen one on the Units page, then book it here.</p>
            @endif
            @if ($bookableElsewhere !== [])
                <form method="POST" action="{{ route('app.property.switch') }}" class="vy-nr-others" aria-labelledby="vy-nr-others-h">
                    @csrf
                    <h3 id="vy-nr-others-h" class="vy-nr-h3">Book into another property</h3>
                    @foreach ($bookableElsewhere as $__p)
                        <button type="submit" name="property_id" value="{{ $__p['id'] }}" class="vy-nr-other">
                            <svg class="vy-nr-choose-ico" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>
                            <span class="vy-nr-choose-name">{{ $__p['name'] }}</span>
                            <span class="vy-nr-choose-rooms">{{ $__p['rooms'] === 1 ? '1 room' : $__p['rooms'].' rooms' }}</span>
                            <svg class="vy-nr-choose-go" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                        </button>
                    @endforeach
                </form>
            @endif
            <div class="vy-nr-blank-actions">
                <a class="vy-btn {{ $bookableElsewhere !== [] ? 'vy-btn--ghost' : 'vy-btn--accent' }}" href="{{ route('app.units') }}">Go to Units</a>
            </div>
        </section>
    @else
        <div>
            <div class="vy-nr-draft" x-show="draftRestored" x-cloak role="status">
                <span>Picked up where you left off.</span>
                <button type="button" class="vy-nr-textbtn" x-on:click="startOver()">Start over</button>
            </div>

            <nav class="vy-wiz-stepper vy-fade" aria-label="Booking steps">
                @foreach ($steps as $__n => $__label)
                    @php $__at = $__nrShownAt[$__n]; @endphp
                    <div class="vy-wiz-node" x-bind:class="step === {{ $__n }} ? 'is-on' : (place() &gt; {{ $__at }} ? 'is-done' : '')">
                        <div class="vy-wiz-node-row">
                            <span class="vy-wiz-bar{{ $__at === 0 ? ' is-edge' : '' }}"></span>
                            <button type="button" class="vy-wiz-circle" x-on:click="goto({{ $__n }})" x-bind:aria-current="step === {{ $__n }} ? 'step' : null" aria-label="Step {{ $__at + 1 }}: {{ $__label }}">
                                <span class="vy-wiz-circle-num">{{ $__at + 1 }}</span>
                                <svg class="vy-wiz-circle-check" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                            </button>
                            <span class="vy-wiz-bar{{ $__at === count($__nrOrder) - 1 ? ' is-edge' : '' }}"></span>
                        </div>
                        <span class="vy-wiz-node-label">{{ $__label }}</span>
                    </div>
                @endforeach
            </nav>

            <div class="vy-nr-body vy-fade">
                {{-- ══ THE BOOKING ══ `novalidate`: each step is checked by the wizard itself (a browser bubble on a
                     field in a hidden step cannot even be focused). The server re-checks everything. --}}
                <form method="POST" action="{{ route('app.reservations.create') }}" class="vy-nr-main" x-ref="form" x-on:submit="submit($event)" data-guard-submit novalidate>
                    @csrf

                    {{-- ⚠️⚠️ THE RESTRICTION OVERRIDE — RENDERED ONLY AFTER A REFUSAL. There is deliberately no checkbox on a
                         first attempt: a pre-tickable override gets ticked out of habit, the block stops firing at all,
                         and the override rate — the only signal that says whether the restrictions are right — becomes
                         meaningless. The full breach list is on screen while the reason is chosen, and travels back in a
                         hidden field so the service can refuse again if the set changed meanwhile. If the operator cannot
                         override, no reason picker is offered at all — it says who can. --}}
                    @if ($restrictionLines !== [])
                        <div class="vy-nr-restrict" data-restriction-refusal>
                            <h3 class="vy-nr-restrict-h">These dates are restricted</h3>
                            <ul class="vy-nr-restrict-list">
                                @foreach ($restrictionLines as $__line)
                                    <li>{{ $__line }}</li>
                                @endforeach
                            </ul>
                            @if (session('restriction_can_override'))
                                <input type="hidden" name="restriction_override_seen" value="{{ json_encode(session('restriction_breaches')) }}">
                                <div class="vy-nr-grid2">
                                    <div class="vy-nr-field">
                                        <label class="vy-nr-label" for="vy-nr-override">Book anyway? Choose why</label>
                                        <select id="vy-nr-override" name="restriction_override_reason" class="vy-nr-input" x-model="overrideReason">
                                            <option value="">Don’t override</option>
                                            @foreach (\App\Support\Booking\RestrictionOverrideReason::labels() as $__k => $__reason)
                                                <option value="{{ $__k }}">{{ $__reason }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="vy-nr-field">
                                        <label class="vy-nr-label" for="vy-lbl-new-reservation-restriction-override-note">Note <span class="vy-nr-opt">needed if you choose Other</span></label>
                                        <input id="vy-lbl-new-reservation-restriction-override-note" type="text" name="restriction_override_note" class="vy-nr-input" maxlength="200" autocomplete="off" x-model="overrideNote">
                                    </div>
                                </div>
                                <p class="vy-nr-miss vy-nr-miss--tight" x-show="blockedStep === 4 &amp;&amp; blocked !== '' &amp;&amp; overrideProblem()" x-text="blocked" x-cloak role="alert"></p>
                                <p class="vy-nr-fine">Overriding lets this one booking through. The restriction stays — the next booking is refused just the same. Or change the dates in step 1.</p>
                            @else
                                <p class="vy-nr-fine">You can’t override a restriction. A manager or someone on the front desk can — or change the dates in step 1.</p>
                            @endif
                        </div>
                    @endif

                    <input type="hidden" name="check_in" value="{{ $checkIn }}">
                    <input type="hidden" name="check_out" value="{{ $checkOut }}">
                    <input type="hidden" name="room_type_id" x-bind:value="roomTypeId">
                    <input type="hidden" name="unit_id" x-bind:value="unitId">
                    <input type="hidden" name="rate_plan_id" x-bind:value="ratePlanId">
                    <input type="hidden" name="transfer_trip" x-bind:value="transferTrip">
                    <input type="hidden" name="apply_credit" x-bind:value="applyCredit &amp;&amp; guestMode === 'existing' ? '1' : ''">
                    <input type="hidden" name="adults" x-bind:value="adults">
                    <input type="hidden" name="children" x-bind:value="children">

                    {{-- ── STAY — the dates, the party, the unit and its plan ───────────────────────────────────────── --}}
                    <section class="vy-nr-card" x-show="step === 1" @if ($initialStep !== 1) x-cloak @endif aria-labelledby="vy-nr-h1">
                        @if (session('new_reservation_ok'))
                            <div class="vy-flash is-ok vy-nr-flash vy-nr-flash--in" role="status">{{ session('new_reservation_ok') }}</div>
                        @endif
                        <h2 id="vy-nr-h1" class="vy-nr-h" tabindex="-1">The stay</h2>
                        <p class="vy-nr-lede">Pick check-in, then check-out — what’s free shows straight away.</p>

                            <div class="vy-nr-cal">
                                <div class="vy-nr-cal-head">
                                    <button type="button" class="vy-nr-cal-nav" x-on:click="calPrev()" aria-label="Previous month">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                                    </button>
                                    <div class="vy-nr-cal-labels" aria-hidden="true"><span x-text="calLabel(0)"></span><span x-text="calLabel(1)"></span></div>
                                    <button type="button" class="vy-nr-cal-nav" x-on:click="calNext()" aria-label="Next month">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                                    </button>
                                </div>
                                <div class="vy-nr-cal-grids">
                                    <template x-for="off in [0, 1]" :key="off">
                                        <div class="vy-nr-cal-month">
                                            <div class="vy-nr-cal-mlabel" x-text="calLabel(off)"></div>
                                            <div class="vy-nr-cal-dow" aria-hidden="true"><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span></div>
                                            <div class="vy-nr-cal-days" x-on:mouseleave="cal.hover = ''">
                                                <template x-for="(d, i) in calGrid(off)" :key="off + '-' + i">
                                                    <button type="button" class="vy-nr-cal-day"
                                                            x-bind:class="{ 'is-pad': !d.date, 'is-past': d.past, 'is-weekend': d.weekend, 'is-today': d.today, 'is-full': d.full, 'is-noprice': d.noprice, 'is-start': calState(d.date) === 'start', 'is-end': calState(d.date) === 'end', 'is-in': calState(d.date) === 'in' }"
                                                            x-bind:disabled="!d.date || d.past || loadingDates"
                                                            x-bind:aria-label="d.date ? calAria(d) : null"
                                                            x-bind:aria-pressed="d.date ? String(calState(d.date) === 'start' || calState(d.date) === 'end') : null"
                                                            x-on:click="calClick(d.date)" x-on:mouseenter="if (d.date) cal.hover = d.date">
                                                        <span class="vy-nr-cal-num" x-text="d.day"></span>
                                                        <span class="vy-nr-cal-price" x-text="d.tag"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="vy-nr-cal-foot" aria-live="polite">
                                    <span class="vy-nr-cal-sel" x-text="calSelLabel()">{{ $datesValid ? $datesLabel : 'Pick the check-in date' }}</span>
                                    <button type="button" class="vy-nr-textbtn" x-show="cal.start &amp;&amp; ! loadingDates" @unless ($datesValid) x-cloak @endunless x-on:click="calClear()">Clear dates</button>
                                </div>
                                @if ($hasNightly)
                                    <p class="vy-nr-cal-legend">Under each night: the main rate of the cheapest room still free, <span class="vy-nr-cal-key">—</span> when none of them has a price yet, <span class="vy-nr-cal-key">Full</span> when nothing is left.</p>
                                @endif
                            </div>

                            <div class="vy-nr-party">
                                <div class="vy-nr-count">
                                    <span class="vy-nr-label" id="vy-nr-adults-l">Adults</span>
                                    <div class="vy-nr-counter" role="group" aria-labelledby="vy-nr-adults-l">
                                        <button type="button" x-on:click="adults = Math.max(1, adults - 1)" x-bind:disabled="adults &lt;= 1" aria-label="Fewer adults">−</button>
                                        <output x-text="adults" aria-live="polite">{{ $restored['adults'] ?? 2 }}</output>
                                        <button type="button" x-on:click="adults = Math.min(30, adults + 1)" x-bind:disabled="adults &gt;= 30" aria-label="More adults">+</button>
                                    </div>
                                </div>
                                <div class="vy-nr-count">
                                    <span class="vy-nr-label" id="vy-nr-children-l">Children</span>
                                    <div class="vy-nr-counter" role="group" aria-labelledby="vy-nr-children-l">
                                        <button type="button" x-on:click="children = Math.max(0, children - 1)" x-bind:disabled="children &lt;= 0" aria-label="Fewer children">−</button>
                                        <output x-text="children" aria-live="polite">{{ $restored['children'] ?? 0 }}</output>
                                        <button type="button" x-on:click="children = Math.min(30, children + 1)" x-bind:disabled="children &gt;= 30" aria-label="More children">+</button>
                                    </div>
                                </div>
                            </div>

                            <div class="vy-nr-availhead">
                                <h3 class="vy-nr-h3">{{ $datesValid ? 'What’s free · '.$datesLabel : 'What’s free' }}</h3>
                                {{-- opens in a new tab so the booking in progress is not lost --}}
                                <a class="vy-nr-link" href="{{ route('app.calendar') }}" target="_blank" rel="noopener">Check the rack<span class="vy-sr-only"> (opens in a new tab)</span></a>
                            </div>
                            @unless ($datesValid)
                                <p class="vy-nr-empty">Pick the dates above to see what’s free.</p>
                            @elseif ($tooLong)
                                <p class="vy-nr-empty">A booking can be at most {{ $maxNights }} nights — pick a shorter stay.</p>
                            @else
                                @forelse ($roomTypes as $rt)
                                    @php $__rtIndex = $loop->index; @endphp
                                    <div class="vy-nr-rt" x-bind:class="roomTypeId === @js($rt['room_type_id']) ? 'is-picked' : ''">
                                        <div class="vy-nr-rt-head">
                                            <span class="vy-nr-rt-name">{{ $rt['name'] }}</span>
                                            @if ($rt['priced'])
                                                <span class="vy-nr-rt-price"><strong>{{ $rt['nightly'] ?? $rt['price'] }}</strong> · {{ $rt['price'] }} for {{ $nights }} {{ $nights === 1 ? 'night' : 'nights' }}</span>
                                            @else
                                                <span class="vy-nr-rt-free">{{ $rt['unit_count'] }} free</span>
                                            @endif
                                        </div>
                                        @if ($rt['priced'])
                                            <div class="vy-nr-units" role="radiogroup" aria-label="Units in {{ $rt['name'] }}">
                                                @foreach ($rt['units'] as $unit)
                                                    <label class="vy-nr-unit" x-bind:class="unitId === @js($unit['id']) ? 'is-on' : ''">
                                                        <input type="radio" class="vy-sr-only" name="unit_pick" value="{{ $unit['id'] }}" x-bind:checked="unitId === @js($unit['id'])" x-on:change="pickUnit(@js($rt['room_type_id']), @js($unit['id']))">
                                                        <span class="vy-nr-unit-code">{{ $unit['code'] }}</span>
                                                        @if ($unit['sleeps'] > 0)
                                                            <span class="vy-nr-unit-meta">Sleeps {{ $unit['sleeps'] }}</span>
                                                        @endif
                                                        @if ($unit['own_price'] !== null)
                                                            <span class="vy-nr-unit-meta">Own price · {{ $unit['own_price'] }}</span>
                                                        @endif
                                                        <svg class="vy-nr-unit-tick" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                                                    </label>
                                                @endforeach
                                            </div>
                                            {{-- The plan — chosen with the unit now (it was a step of its own, holding only this line). REAL
                                                 active plans of the room type, priced over the stay; >1 → a choice. --}}
                                            @if (count($rt['plans']) > 1)
                                                <div class="vy-nr-plans vy-nr-plans--inline" x-show="roomTypeId === @js($rt['room_type_id'])" @if (($restored['roomTypeId'] ?? '') !== $rt['room_type_id']) x-cloak @endif role="radiogroup" aria-label="Rate plan for {{ $rt['name'] }}">
                                                    @foreach ($rt['plans'] as $__plan)
                                                        <label class="vy-nr-plan" x-bind:class="ratePlanId === @js($__plan['id']) ? 'is-on' : ''">
                                                            <input type="radio" class="vy-sr-only" name="plan_pick_{{ $__rtIndex }}" value="{{ $__plan['id'] }}" x-bind:checked="ratePlanId === @js($__plan['id'])" x-on:change="pickPlan(@js($__plan['id']))">
                                                            <span class="vy-nr-plan-name">{{ $__plan['name'] }}</span>
                                                            <span class="vy-nr-plan-price" x-text="fmt(planPrice(@js($__plan['id']))) + ' for the stay'">{{ $__plan['price'] }} for the stay</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @else
                                            {{-- ⚠️⚠️ A SAVED SEASON, NEVER APPLIED — said, and applied from here by whoever manages rates (the Rates
                                                 page's own Apply, in a form OUTSIDE the booking form, joined by script). --}}
                                            @foreach ($rt['seasons'] as $season)
                                                <div class="vy-nr-season">
                                                    <p class="vy-nr-season-why"><strong>{{ $season['name'] }} · {{ $season['nightly'] }}</strong> · {{ $season['from'] }} → {{ $season['to'] }} — saved but not applied yet{{ $season['prices'] === $season['missing'] ? ', so these nights have no price.' : '. It would price '.$season['prices'].' of the '.$season['missing'].' nights that have none.' }}</p>
                                                    @if ($canSetPrice)
                                                        {{-- ⚠️⚠️ THROUGH THE SAME "Apply this season?" DISCLOSURE AS THE RATES PAGE (the review, H2): this button
                                                             only opens it — what is overwritten, that it cannot be undone, and what somebody negotiated. --}}
                                                        <button type="button" class="vy-btn vy-btn--accent" x-on:click="openSeason(@js($season['id']))" @disabled($__nrReadOnly)>Apply {{ $season['name'] }}…</button>
                                                        <p class="vy-nr-fine">It writes {{ $season['nightly'] }} on the season’s nights{{ $rt['plan_name'] !== '' ? ' on the '.$rt['plan_name'].' plan' : '' }}, as Apply on the Rates page does — you see everything it replaces before anything is written.</p>
                                                    @else
                                                        <p class="vy-nr-noprice">Someone who manages rates can apply it on the Rates page — its free units appear here once they have.</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if ($rt['has_plan'] && $canSetPrice)
                                                {{-- ⚠️⚠️ THE STAY'S MISSING PRICE, SET HERE. It used to be a link to the Rates page that threw the
                                                     booking away. These controls belong to a form OUTSIDE the booking form (a form inside a
                                                     form is not HTML), joined by the `form` attribute; it posts the Rates write with the
                                                     same rules, for exactly these nights, and comes back here. --}}
                                                <div class="vy-nr-setprice">
                                                    <p class="vy-nr-setprice-why">@if ($rt['seasons'] !== [])<strong>Or set a price for these nights only.</strong>@else<strong>No price for {{ $rt['unpriced_nights'] >= $nights ? 'these nights' : $rt['unpriced_nights'].' of these '.$nights.' nights' }} yet.</strong> Set one and {{ $rt['unit_count'] === 1 ? 'its free unit' : 'its '.$rt['unit_count'].' free units' }} can be booked.@endif</p>
                                                    <div class="vy-nr-setprice-row">
                                                        <label class="vy-nr-label" for="vy-nr-rate-{{ $__rtIndex }}">Price a night</label>
                                                        <span class="vy-nr-money">
                                                            <span class="vy-nr-money-sym" aria-hidden="true">{{ $rt['symbol'] }}</span>
                                                            <input id="vy-nr-rate-{{ $__rtIndex }}" form="vy-nr-price-{{ $__rtIndex }}" type="text" name="rate" class="vy-nr-input" inputmode="decimal" placeholder="e.g. 120" autocomplete="off"
                                                                   x-on:input="priceErr[{{ $__rtIndex }}] = ''" x-on:keydown.enter.prevent="submitPrice({{ $__rtIndex }})">
                                                        </span>
                                                        {{-- ⚠️ type="button": a submit button here sits inside the booking form, and the shared double-submit
                                                             guard disabled IT, not Confirm, when the booking was sent (the independent review). --}}
                                                        <button type="button" class="vy-btn {{ $rt['seasons'] !== [] ? 'vy-btn--ghost' : 'vy-btn--accent' }}" x-on:click="submitPrice({{ $__rtIndex }})" x-bind:disabled="priceBusy === {{ $__rtIndex }}" @disabled($__nrReadOnly)
                                                                x-text="priceBusy === {{ $__rtIndex }} ? 'Setting…' : 'Set price'">Set price</button>
                                                    </div>
                                                    <p class="vy-nr-miss" x-show="priceErr[{{ $__rtIndex }}]" x-text="priceErr[{{ $__rtIndex }}]" x-cloak role="alert"></p>
                                                    <p class="vy-nr-fine">The same price goes on each night from {{ \Carbon\CarbonImmutable::parse($checkIn)->format('j M') }} to {{ \Carbon\CarbonImmutable::parse($lastNight)->format('j M') }}{{ $rt['plan_name'] !== '' ? ' on the '.$rt['plan_name'].' plan' : '' }} — a price already on one of them is replaced. Change it any time on the Rates page.</p>
                                                </div>
                                            @elseif ($rt['has_plan'] && $rt['seasons'] === [])
                                                <p class="vy-nr-noprice">No price is set for these nights, so {{ $rt['name'] }} can’t be booked yet. Ask someone who manages rates to set one — its free units appear here once they have.</p>
                                            @elseif (! $rt['has_plan'])
                                                <p class="vy-nr-noprice">{{ $rt['name'] }} has no rate plan yet, so it has no price.@if ($canSetPrice) <a class="vy-nr-link" href="{{ route('app.rates') }}" target="_blank" rel="noopener">Add one on the Rates page<span class="vy-sr-only"> (opens in a new tab)</span></a>@else Someone who manages rates can add one.@endif</p>
                                            @endif
                                        @endif
                                    </div>
                                @empty
                                    <p class="vy-nr-empty">Nothing is free for {{ $datesLabel }}. Try other dates, or check the rack.</p>
                                @endforelse
                            @endunless
                        <p class="vy-nr-miss" data-miss="1" x-show="blockedStep === 1 &amp;&amp; blocked !== ''" x-text="blocked" x-cloak role="alert"></p>
                    </section>

                    {{-- ── GUEST — who is staying, and when they arrive ───────────────────────────────────────────── --}}
                    <section class="vy-nr-card" x-show="step === 2" @if ($initialStep !== 2) x-cloak @endif aria-labelledby="vy-nr-h2">
                        <h2 id="vy-nr-h2" class="vy-nr-h" tabindex="-1">Who’s staying</h2>
                        <p class="vy-nr-lede">Look up a returning guest, or add a new one.</p>
                        <input type="hidden" name="existing_guest_id" x-bind:value="guestMode === 'existing' ? existingGuestId : ''">
                        <div class="vy-nr-seg" role="group" aria-label="Guest">
                            <button type="button" class="vy-nr-seg-i" x-bind:class="guestMode === 'existing' ? 'is-on' : ''" x-bind:aria-pressed="String(guestMode === 'existing')" x-on:click="guestMode = 'existing'">Returning guest</button>
                            <button type="button" class="vy-nr-seg-i" x-bind:class="guestMode === 'new' ? 'is-on' : ''" x-bind:aria-pressed="String(guestMode === 'new')" x-on:click="guestMode = 'new'">New guest</button>
                        </div>

                        <div x-show="guestMode === 'existing'" @if (($restored['guestMode'] ?? ($query !== '' ? 'existing' : 'new')) !== 'existing') x-cloak @endif>
                            {{-- A GET NAVIGATION (a form inside the booking form is not HTML): Enter or the button reloads with
                                 ?q=, and the wizard comes back at this step. Server-side search is NAME-only (#28). --}}
                            <div class="vy-nr-search">
                                <label class="vy-sr-only" for="nr-guest-q">Search a returning guest by name</label>
                                <input type="search" id="nr-guest-q" value="{{ $query }}" class="vy-nr-input" placeholder="Search by name" autocomplete="off" x-ref="guestQuery"
                                       x-on:keydown.enter.prevent="searchGuest()">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="searchGuest()">Search</button>
                            </div>
                            <p class="vy-nr-chosen" x-show="existingGuestId !== '' &amp;&amp; existingGuestName !== ''" x-cloak>Booking for <strong x-text="existingGuestName"></strong></p>
                            @if ($query === '')
                                <p class="vy-nr-empty">Search by name, then choose them here.</p>
                            @elseif ($guestMatches === [])
                                <p class="vy-nr-empty">No guests match “{{ $query }}”. Switch to New guest to add them.</p>
                            @else
                                <div class="vy-nr-guests" role="radiogroup" aria-label="Guests matching “{{ $query }}”">
                                    @foreach ($guestMatches as $match)
                                        <label class="vy-nr-guest" x-bind:class="existingGuestId === @js($match['id']) ? 'is-on' : ''">
                                            <input type="radio" class="vy-sr-only" name="guest_pick" value="{{ $match['id'] }}" x-bind:checked="existingGuestId === @js($match['id'])" x-on:change="pickGuest(@js($match['id']), @js($match['name']))">
                                            <span class="vy-nr-guest-av" aria-hidden="true">{{ $match['initials'] }}</span>
                                            <span class="vy-nr-guest-id"><span class="vy-nr-guest-name">{{ $match['name'] }}</span><span class="vy-nr-guest-meta">{{ $match['history'] }}</span></span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div x-show="guestMode === 'new'" @if (($restored['guestMode'] ?? ($query !== '' ? 'existing' : 'new')) !== 'new') x-cloak @endif>
                            <div class="vy-nr-grid2">
                                <div class="vy-nr-field"><label class="vy-nr-label" for="gf">First name</label><input id="gf" type="text" name="guest_first_name" class="vy-nr-input" x-bind:disabled="guestMode !== 'new'" x-model="newFirst" maxlength="80" autocomplete="off"></div>
                                <div class="vy-nr-field"><label class="vy-nr-label" for="gl">Last name</label><input id="gl" type="text" name="guest_last_name" class="vy-nr-input" x-bind:disabled="guestMode !== 'new'" x-model="newLast" maxlength="80" autocomplete="off"></div>
                                <div class="vy-nr-field"><label class="vy-nr-label" for="ge">Email <span class="vy-nr-opt">optional — needed to send the confirmation</span></label><input id="ge" type="email" name="guest_email" class="vy-nr-input" x-bind:disabled="guestMode !== 'new'" x-ref="email" x-model="newEmail" x-on:input="recheck()" autocomplete="off"></div>
                                <div class="vy-nr-field"><label class="vy-nr-label" for="gp">Phone <span class="vy-nr-opt">optional</span></label><input id="gp" type="tel" name="guest_phone" class="vy-nr-input" x-bind:disabled="guestMode !== 'new'" x-ref="phone" autocomplete="off"></div>
                                <div class="vy-nr-field">
                                    <label class="vy-nr-label" for="gc">Country <span class="vy-nr-opt">optional</span></label>
                                    {{-- every ISO country by name (it was a two-letter box, "e.g. PT"); the code is what the booking stores --}}
                                    <select id="gc" name="guest_country" class="vy-nr-input" x-bind:disabled="guestMode !== 'new'">
                                        <option value="">Not given</option>
                                        @foreach ($countries as $__code => $__country)
                                            <option value="{{ $__code }}" @selected(($restored['country'] ?? '') === $__code)>{{ $__country }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="vy-nr-field">
                                    <label class="vy-nr-label" for="aw">Estimated arrival <span class="vy-nr-opt">optional</span></label>
                                    <input id="aw" type="text" name="arrival_window" class="vy-nr-input" placeholder="e.g. 3–6 PM" maxlength="60" x-model="arrivalWindow" autocomplete="off">
                                </div>
                            </div>
                            @if (session('new_reservation_contact_cleared'))
                                {{-- #28 — the contact details were not sent back with the refusal, so they are asked for again. --}}
                                <p class="vy-nr-note">The email and phone you typed weren’t kept when the booking came back — enter them again.</p>
                            @endif
                            <div class="vy-nr-field">
                                <label class="vy-nr-label" for="gnote">Guest note <span class="vy-nr-opt">optional</span></label>
                                <textarea id="gnote" name="guest_note" class="vy-nr-input vy-nr-textarea" rows="2" placeholder="Preferences, allergies, access needs…" x-bind:disabled="guestMode !== 'new'">{{ $restored['guestNote'] ?? '' }}</textarea>
                            </div>
                            <div class="vy-nr-checks">
                                <label class="vy-nr-check"><input type="checkbox" name="guest_vip" value="1" @checked($restored['vip'] ?? false) x-bind:disabled="guestMode !== 'new'"> Mark as VIP</label>
                                <label class="vy-nr-check"><input type="checkbox" name="guest_marketing_consent" value="1" @checked($restored['consent'] ?? false) x-bind:disabled="guestMode !== 'new'"> The guest agreed to offers and news</label>
                            </div>
                        </div>
                        <div class="vy-nr-field vy-nr-field--gap" x-show="guestMode === 'existing'" @if (($restored['guestMode'] ?? ($query !== '' ? 'existing' : 'new')) !== 'existing') x-cloak @endif>
                            <label class="vy-nr-label" for="aw2">Estimated arrival <span class="vy-nr-opt">optional</span></label>
                            <input id="aw2" type="text" class="vy-nr-input" placeholder="e.g. 3–6 PM" maxlength="60" x-model="arrivalWindow" autocomplete="off">
                        </div>
                        {{-- ⚠️ 2026-10-04 — it said "The confirmation includes directions and parking": the confirmation says they
                             come later, in the pre-arrival email — said here from the real conditions (NewReservation::arrivalNote()). --}}
                        <p class="vy-nr-note is-calm">{{ $arrivalNote }}</p>
                        <p class="vy-nr-miss" data-miss="2" x-show="blockedStep === 2 &amp;&amp; blocked !== ''" x-text="blocked" x-cloak role="alert"></p>
                    </section>

                    @if ($__nrHasExtras)
                        {{-- ── EXTRAS — only where the property has some ───────────────────────────────────────────── --}}
                        <section class="vy-nr-card" x-show="step === 3" @if ($initialStep !== 3) x-cloak @endif aria-labelledby="vy-nr-h3">
                            <h2 id="vy-nr-h3" class="vy-nr-h" tabindex="-1">Extras</h2>
                            <p class="vy-nr-lede">Anything the guest wants — each one is a real line on the folio.</p>
                            @foreach ($extras as $ex)
                                <label class="vy-nr-extra">
                                    <span class="vy-nr-extra-text"><strong>{{ $ex['name'] }}</strong><span class="vy-nr-extra-basis"> · {{ $ex['basis_label'] }}</span></span>
                                    <span class="vy-nr-extra-price">{{ \App\Support\Money\MoneyFormatter::display($ex['price_minor'], $ex['currency']) }}</span>
                                    <input type="checkbox" class="vy-nr-switch" name="extras[]" value="{{ $ex['id'] }}" x-model="extras">
                                </label>
                                @if ($ex['is_transfer'])
                                    <div class="vy-nr-trip" x-show="extras.includes(@js($ex['id']))" @unless (in_array($ex['id'], $restored['extras'] ?? [], true)) x-cloak @endunless role="radiogroup" aria-label="{{ $ex['name'] }} — trips">
                                        <label class="vy-nr-check"><input type="radio" name="_trip" value="one" x-model="transferTrip"> One way</label>
                                        <label class="vy-nr-check"><input type="radio" name="_trip" value="return" x-model="transferTrip"> Return</label>
                                    </div>
                                @endif
                            @endforeach
                        </section>
                    @endif

                    {{-- ── CONFIRM — one look at everything, the rules, and the money ───────────────────────────────── --}}
                    <section class="vy-nr-card" x-show="step === 4" @if ($initialStep !== 4) x-cloak @endif aria-labelledby="vy-nr-h4">
                        <h2 id="vy-nr-h4" class="vy-nr-h" tabindex="-1">Confirm</h2>
                        <p class="vy-nr-lede">One look at everything before it’s booked.</p>
                        <dl class="vy-nr-review">
                            <div><dt>Guest</dt><dd x-text="guestLabel()">—</dd><button type="button" class="vy-nr-textbtn" x-on:click="goto(2)" aria-label="Change the guest">Change</button></div>
                            <div><dt>Stay</dt><dd><span>{{ $datesValid ? $datesLabel : '—' }}</span> · <span x-text="partyLabel()"></span></dd><button type="button" class="vy-nr-textbtn" x-on:click="goto(1)" aria-label="Change the stay">Change</button></div>
                            <div><dt>Unit</dt><dd x-text="unitLabel()">—</dd><button type="button" class="vy-nr-textbtn" x-on:click="goto(1)" aria-label="Change the unit">Change</button></div>
                            @if ($__nrHasExtras)
                                <div><dt>Extras</dt><dd x-text="extrasLabel()">None</dd><button type="button" class="vy-nr-textbtn" x-on:click="goto(3)" aria-label="Change the extras">Change</button></div>
                            @endif
                            <div><dt>Arrival</dt><dd x-text="arrivalWindow.trim() !== '' ? arrivalWindow : 'Not given'">Not given</dd><button type="button" class="vy-nr-textbtn" x-on:click="goto(2)" aria-label="Change the arrival time">Change</button></div>
                        </dl>

                        <div class="vy-nr-block">
                            <h3 class="vy-nr-h3">House rules</h3>
                            {{-- ⚠️⚠️ 2026-10-04 — "Not set" where the property has set nothing: it used to print "Not permitted" for
                                 pets and smoking on a property that had chosen neither, and the stored codes on one that had. An
                                 unpublished time is not set either — never an invented hour, never "00:00". --}}
                            <div class="vy-nr-rules">
                                <div><span>Check-in</span><strong>{{ $houseRules['check_in'] !== null ? 'From '.$houseRules['check_in'] : 'Not set' }}</strong></div>
                                <div><span>Check-out</span><strong>{{ $houseRules['check_out'] !== null ? 'By '.$houseRules['check_out'] : 'Not set' }}</strong></div>
                                <div><span>Pets</span><strong>{{ $houseRules['pet'] ?? 'Not set' }}</strong></div>
                                <div><span>Smoking</span><strong>{{ $houseRules['smoking'] ?? 'Not set' }}</strong></div>
                                <div><span>Minimum stay</span><strong>{{ $houseRules['min_stay'] ?? 'Not set' }}</strong></div>
                                <div><span>Cancellation</span><strong>{{ $houseRules['cancellation'] ?? 'Not set' }}</strong></div>
                            </div>
                            @if ($canEditRules && in_array(null, $houseRules, true))
                                <p class="vy-nr-fine">Rules that say “Not set” are set in <a class="vy-nr-link" href="{{ route('app.settings') }}" target="_blank" rel="noopener">Settings<span class="vy-sr-only"> (opens in a new tab)</span></a>.</p>
                            @endif
                            <label class="vy-nr-check vy-nr-check--gap"><input type="checkbox" name="rules_agreed" value="1" x-model="rulesAgreed"> The guest accepts the house rules</label>
                        </div>

                        <div class="vy-nr-block">
                            <div class="vy-nr-grid2">
                                <div class="vy-nr-field">
                                    <label class="vy-nr-label" for="src">Booking source</label>
                                    <select id="src" name="source" class="vy-nr-input">
                                        @foreach ($sources as $src)
                                            <option value="{{ $src['value'] }}" @selected(($restored['source'] ?? 'direct') === $src['value'])>{{ $src['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="vy-nr-field">
                                <label class="vy-nr-label" for="note">Note for the team <span class="vy-nr-opt">optional</span></label>
                                <textarea id="note" name="team_note" rows="2" class="vy-nr-input vy-nr-textarea">{{ $restored['teamNote'] ?? '' }}</textarea>
                            </div>
                            {{-- A hidden "0" so UNCHECKING genuinely suppresses this booking's confirmation (an unchecked box
                                 submits nothing); the checked "1" overrides it. ⚠️ 2026-10-04 — and it cannot be ticked without an
                                 address to send to: it used to sit ticked over a guest with no email at all. --}}
                            <input type="hidden" name="send_confirmation" value="0">
                            <label class="vy-nr-check"><input type="checkbox" name="send_confirmation" value="1" x-bind:checked="sendConfirmation &amp;&amp; canEmail()" x-bind:disabled="! canEmail()" x-on:change="sendConfirmation = $event.target.checked" aria-describedby="vy-nr-mail-why"> Email the confirmation to the guest</label>
                            <p class="vy-nr-fine vy-nr-fine--tight" id="vy-nr-mail-why" x-show="! canEmail() || {{ $mailSends ? 'false' : 'true' }}" x-text="mailWhy()">{{ $mailSends ? 'Needs the guest’s email address — add one in the Guest step to send it.' : 'Email isn’t being sent from Vyrobel yet, so nothing goes out for now.' }}</p>
                        </div>

                        <div class="vy-nr-block">
                            <h3 class="vy-nr-h3">Payment</h3>
                            @if ($canPay)
                                <p class="vy-nr-lede vy-nr-lede--tight">Take a deposit or the full amount now, or collect it at check-in.</p>
                                {{-- ONE hidden payment_method (no duplicate names). Card capture stays deferred (SAQ A). --}}
                                <input type="hidden" name="payment_method" x-bind:value="depositChoice === 'nothing' ? 'later' : payMethod">
                                <div class="vy-nr-deposit" role="radiogroup" aria-label="How much to collect now">
                                    <label class="vy-nr-dep" x-bind:class="depositChoice === 'nothing' ? 'is-on' : ''">
                                        <input type="radio" class="vy-sr-only" name="deposit_pick" value="nothing" x-bind:checked="depositChoice === 'nothing'" x-on:change="setDeposit('nothing')">
                                        <strong>Nothing now</strong><span>collect at check-in</span>
                                    </label>
                                    {{-- ⚠️ "—" until a unit is chosen: with nothing priced, "€0.00" would say a 30% deposit is zero. --}}
                                    <label class="vy-nr-dep" x-bind:class="depositChoice === 'deposit' ? 'is-on' : ''">
                                        <input type="radio" class="vy-sr-only" name="deposit_pick" value="deposit" x-bind:checked="depositChoice === 'deposit'" x-on:change="setDeposit('deposit')">
                                        <strong>Deposit · 30%</strong><span x-text="unitId ? fmt(Math.round(totalMinor() * 0.3)) : '—'">—</span>
                                    </label>
                                    <label class="vy-nr-dep" x-bind:class="depositChoice === 'full' ? 'is-on' : ''">
                                        <input type="radio" class="vy-sr-only" name="deposit_pick" value="full" x-bind:checked="depositChoice === 'full'" x-on:change="setDeposit('full')">
                                        <strong>Full amount</strong><span x-text="unitId ? fmt(totalMinor()) : '—'">—</span>
                                    </label>
                                </div>
                                <div x-show="depositChoice !== 'nothing'" @if (($restored['payMethod'] ?? 'later') === 'later') x-cloak @endif>
                                    <div class="vy-nr-grid2">
                                        <div class="vy-nr-field">
                                            <label class="vy-nr-label" for="pm">Paid by</label>
                                            <select id="pm" class="vy-nr-input" x-model="payMethod">
                                                <option value="cash">Cash</option>
                                                <option value="bank">Bank transfer</option>
                                                <option value="card" disabled>Card — coming soon</option>
                                            </select>
                                        </div>
                                        <div class="vy-nr-field">
                                            <label class="vy-nr-label" for="pa">Amount paid now</label>
                                            <span class="vy-nr-money">
                                                <span class="vy-nr-money-sym" aria-hidden="true" x-text="symbol()">{{ $symbol }}</span>
                                                <input id="pa" type="text" name="payment_amount" class="vy-nr-input" inputmode="decimal" autocomplete="off" x-model="payAmount" x-bind:disabled="depositChoice === 'nothing'">
                                            </span>
                                        </div>
                                    </div>
                                    <p class="vy-nr-fine vy-nr-fine--tight" x-show="(toMinor(payAmount) || 0) &gt; totalMinor()" x-cloak>That’s more than the total — only the total is recorded as paid.</p>
                                    <p class="vy-nr-note is-calm">Left to pay at check-in: <strong x-text="fmt(Math.max(0, totalMinor() - paidNow()))"></strong>. Card payments arrive with secure Stripe checkout — <strong>no card number is entered or kept here</strong>.</p>
                                </div>
                            @else
                                <p class="vy-nr-empty">Payment is taken later, from the reservation’s folio.</p>
                            @endif
                            {{-- the returning guest's wallet credit — money, so it sits with the payment (it was among the extras) --}}
                            <label class="vy-nr-extra" x-show="guestMode === 'existing'" @if (($restored['guestMode'] ?? ($query !== '' ? 'existing' : 'new')) !== 'existing') x-cloak @endif>
                                <span class="vy-nr-extra-text"><strong>Returning-guest credit</strong><span class="vy-nr-extra-basis"> · uses the guest’s wallet balance, if they have one</span></span>
                                <input type="checkbox" class="vy-nr-switch" x-model="applyCredit">
                            </label>
                        </div>
                        <p class="vy-nr-miss" data-miss="4" x-show="blockedStep === 4 &amp;&amp; blocked !== '' &amp;&amp; ! overrideProblem()" x-text="blocked" x-cloak role="alert"></p>
                        <p class="vy-nr-miss" x-show="contactWarned" x-cloak role="alert">The guest’s email and phone are empty — they weren’t kept when the booking came back. Add them in the Guest step, or press Confirm again to book without them.</p>
                    </section>

                    <div class="vy-nr-nav">
                        <button type="button" class="vy-btn vy-btn--ghost" x-show="place() &gt; 0" @if (($__nrShownAt[$initialStep] ?? 0) === 0) x-cloak @endif x-on:click="prev()">Back</button>
                        <span class="vy-nr-nav-gap"></span>
                        {{-- the running total, on a phone where the summary is far below --}}
                        <span class="vy-nr-nav-total" x-show="unitId !== ''" x-cloak aria-live="polite"><span>Estimated total</span> <strong x-text="fmt(totalMinor())"></strong></span>
                        <button type="button" class="vy-btn vy-btn--accent" x-show="step !== 4" @if ($initialStep === 4) x-cloak @endif x-on:click="next()">Next</button>
                        <button type="submit" class="vy-btn vy-btn--accent" x-show="step === 4" @if ($initialStep !== 4) x-cloak @endif data-busy-label="Booking…" @disabled($__nrReadOnly)>Confirm reservation</button>
                    </div>
                </form>

                {{-- ══ BOOKING SUMMARY — an estimate from the same real prices and the same tax and fee rules ══ --}}
                <aside class="vy-nr-summary" aria-labelledby="vy-nr-sum-h">
                    <h2 id="vy-nr-sum-h" class="vy-nr-sum-h">Booking summary</h2>
                    <dl class="vy-nr-facts">
                        <div><dt>Dates</dt><dd>{{ $datesValid ? $datesLabel : 'Not chosen yet' }}</dd></div>
                        <div><dt>Guests</dt><dd x-text="partyLabel()">{{ ($restored['adults'] ?? 2) === 1 ? '1 adult' : ($restored['adults'] ?? 2).' adults' }}</dd></div>
                        <div><dt>Unit</dt><dd x-text="unitLabel()">Not chosen yet</dd></div>
                        <div><dt>Guest</dt><dd x-text="guestLabel()">Not added yet</dd></div>
                    </dl>
                    {{-- ⚠️ "—" until a unit is chosen, NOT "€0.00": before a selection there is no price, and €0.00 states a
                         real zero. Every component line waits for a unit too — a tax computed from the dates alone would
                         be a tax on a stay with no accommodation. --}}
                    <div class="vy-nr-lines">
                        <div class="vy-nr-line"><span>Accommodation{{ $datesValid ? ' · '.$nights.' '.($nights === 1 ? 'night' : 'nights') : '' }}</span><span x-text="unitId ? fmt(roomMinor) : '—'">—</span></div>
                        <div class="vy-nr-line" x-show="unitId &amp;&amp; extrasMinor() &gt; 0" x-cloak><span>Extras</span><span x-text="fmt(extrasMinor())"></span></div>
                        <div class="vy-nr-line" x-show="unitId &amp;&amp; applyCredit &amp;&amp; guestMode === 'existing'" x-cloak><span>Returning-guest credit</span><span class="vy-nr-line-note">applied on confirm</span></div>
                        <div class="vy-nr-line" x-show="unitId &amp;&amp; taxMinor() &gt; 0" x-cloak><span>Taxes</span><span x-text="fmt(taxMinor())"></span></div>
                        <div class="vy-nr-line" x-show="unitId &amp;&amp; feeMinor() &gt; 0" x-cloak><span>Fees</span><span x-text="fmt(feeMinor())"></span></div>
                        <div class="vy-nr-line is-total"><span>Estimated total</span><span x-text="unitId ? fmt(totalMinor()) : '—'">—</span></div>
                        <div class="vy-nr-line is-sub" x-show="unitId &amp;&amp; depositChoice !== 'nothing' &amp;&amp; toMinor(payAmount) !== null" x-cloak><span>Paid now</span><span x-text="fmt(paidNow())"></span></div>
                        <div class="vy-nr-line is-sub" x-show="unitId &amp;&amp; depositChoice !== 'nothing' &amp;&amp; toMinor(payAmount) !== null" x-cloak><span>Left to pay</span><span x-text="fmt(Math.max(0, totalMinor() - paidNow()))"></span></div>
                    </div>
                    <p class="vy-nr-fine">An estimate — the exact total, with any wallet credit, is on the reservation’s folio once it’s created.</p>
                </aside>
            </div>

            {{-- The set-price and apply-season forms — OUTSIDE the booking form; their controls above reach them by script. --}}
            @foreach ($roomTypes as $rt)
                @if (! $rt['priced'] && $rt['has_plan'] && $canSetPrice)
                    <form id="vy-nr-price-{{ $loop->index }}" method="POST" action="{{ route('app.rates.update') }}" class="vy-nr-priceform" data-guard-submit x-on:submit="priceSubmit($event, {{ $loop->index }})">
                        @csrf
                        <input type="hidden" name="room_type_id" value="{{ $rt['room_type_id'] }}">
                        <input type="hidden" name="start_date" value="{{ $checkIn }}">
                        <input type="hidden" name="end_date" value="{{ $lastNight }}">
                        <input type="hidden" name="set_rate" value="1">
                        <input type="hidden" name="return" value="{{ \App\Http\Controllers\Operator\RateActionController::RETURN_WIZARD }}">
                        <input type="hidden" name="return_check_in" value="{{ $checkIn }}">
                        <input type="hidden" name="return_check_out" value="{{ $checkOut }}">
                    </form>
                    {{-- ⚠️⚠️ "APPLY THIS SEASON?" — the Rates page's own disclosure (one partial), then the same Apply. Nothing is written
                         until "Apply and overwrite those nights" (the review, H2: the first version posted on the first click). --}}
                    @foreach ($rt['seasons'] as $season)
                        <div class="vy-modal-backdrop" x-show="confirmSeason !== null &amp;&amp; confirmSeason.id === @js($season['id'])" x-cloak x-transition.opacity x-on:click.self="confirmSeason = null" x-on:keydown.escape.window="confirmSeason = null">
                            <div class="vy-modal vy-modal-card vy-modal--wide vyr-dlg" x-show="confirmSeason !== null &amp;&amp; confirmSeason.id === @js($season['id'])" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-nr-season-t-{{ $season['id'] }}" tabindex="-1" x-trap.noscroll="confirmSeason !== null &amp;&amp; confirmSeason.id === @js($season['id'])">
                                <div class="vy-modal-head">
                                    <span class="vy-modal-ico is-danger" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg></span>
                                    <div class="vyr-dlg-head">
                                        <h3 class="vy-modal-title" id="vy-nr-season-t-{{ $season['id'] }}">Apply this season?</h3>
                                        <p class="vyr-dlg-sub">{{ $season['name'] }} · {{ $season['nightly'] }} · {{ $season['from'] }} → {{ $season['to'] }}</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" x-on:click="confirmSeason = null" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                                <div class="vy-modal-body vyr-prose">
                                    @include('livewire.operator.partials.season-apply-disclosure')
                                    <p>For this booking: it prices {{ $season['prices'] }} of the {{ $season['missing'] }} {{ $season['missing'] === 1 ? 'night' : 'nights' }} of the stay that have no price.</p>
                                </div>
                                <form method="POST" action="{{ $season['apply_url'] }}" class="vy-modal-foot" data-guard-submit x-on:submit="persist()">
                                    @csrf
                                    <input type="hidden" name="return" value="{{ \App\Http\Controllers\Operator\RateActionController::RETURN_WIZARD }}">
                                    <input type="hidden" name="return_check_in" value="{{ $checkIn }}">
                                    <input type="hidden" name="return_check_out" value="{{ $checkOut }}">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="confirmSeason = null">Cancel</button>
                                    <button type="submit" class="vy-btn vy-btn--accent">Apply and overwrite those nights</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                @endif
            @endforeach
        </div>

        <script type="application/json" id="vy-nr-boot">{!! json_encode($boot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>
        <script>
            function newReservationWizard() {
                const BOOT = JSON.parse(document.getElementById('vy-nr-boot').textContent);
                const R = BOOT.restore;
                // ⚠️ THE DRAFT HOLDS NO GUEST NAME — ids, counts and amounts only (the group-booking discipline: a name
                //    left in browser storage outlives the booking on a shared front-desk machine). It is kept per
                //    property, and a unit comes back only for the same dates and only if it is still free.
                // ⚠️ sessionStorage — this TAB only (the group-booking discipline, and the independent review: a draft left in
                //    localStorage filled the NEXT booking — another day, another operator — with its extras, credit and deposit).
                //    ⚠️ What comes back across a date change is only what the Stay step shows (party size); the rest only for the
                //    same stay, with the "Picked up where you left off" line. Payment, credit and the rules answer are never restored.
                // ⚠️ v4 (2026-10-04): the steps were renumbered (seven became four) — a v3 draft's step number means another step.
                const DRAFT_KEY = 'vyrobel:new-reservation:draft';
                const DRAFT_MAX_AGE = 12 * 3600 * 1000;
                const KEPT = ['step', 'adults', 'children', 'roomTypeId', 'unitId', 'ratePlanId', 'guestMode', 'extras', 'transferTrip', 'arrivalWindow'];
                const ORDER = BOOT.steps;   // e.g. [1, 2, 4] — Extras (3) only where the property has some
                const LAST = ORDER[ORDER.length - 1];
                const rt = (id) => BOOT.roomTypes[id] || null;
                const EMAIL = /^[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+(\.[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@[A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?(\.[A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?)+$/;
                return {
                    order: ORDER,
                    step: 1, blocked: '', blockedStep: 0, contactWarned: false, loadingDates: false, draftRestored: false,
                    roomTypeId: '', unitId: '', ratePlanId: '', roomMinor: 0,
                    adults: 2, children: 0,
                    guestMode: BOOT.searched ? 'existing' : 'new',
                    existingGuestId: '', existingGuestName: '', newFirst: '', newLast: '', newEmail: '',
                    extras: [], transferTrip: 'one', applyCredit: false,
                    arrivalWindow: '', rulesAgreed: false, sendConfirmation: R ? R.sendConfirmation !== false : true,
                    depositChoice: 'nothing', payMethod: 'cash', payAmount: '',
                    priceErr: {}, priceBusy: -1, confirmSeason: null, overrideReason: '', overrideNote: '', paySuggested: '',
                    cal: { y: 2026, m: 0, start: BOOT.checkIn, end: BOOT.checkOut, hover: '' },
                    init() {
                        try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}   // a draft from before 2026-10-04 lived here — gone
                        this.calInit();
                        if (R) { this.applyRestore(R); } else { this.restoreDraft(); }
                        if (BOOT.searched) { this.guestMode = 'existing'; }
                        KEPT.forEach((k) => this.$watch(k, () => { this.persist(); this.recheck(); }));
                        ['newFirst', 'newLast', 'newEmail', 'existingGuestId', 'overrideReason', 'overrideNote', 'payAmount', 'depositChoice'].forEach((k) => this.$watch(k, () => this.recheck()));
                        // ⚠️ A deposit or "full" amount follows the total while nobody has typed over it — it used to keep the
                        //    figure of the moment it was chosen, so "Full" could record less than the folio after a change.
                        this.$watch('totalMinor()', () => { if (this.depositChoice !== 'nothing' && this.payAmount === this.paySuggested) this.setDeposit(this.depositChoice); });
                        // a refused step scrolls to what refused it (on a phone the bar keeps Next far from the message — the review);
                        // any other step change goes to the top of the page and puts focus on the step's heading
                        this.$watch('step', () => {
                            this.contactWarned = false; this.draftRestored = false;
                            this.$nextTick(() => {
                                if (this.blockedStep === this.step && this.blocked !== '') { this.showMiss(this.step); return; }
                                this.$root.scrollIntoView({ behavior: this.calm() ? 'auto' : 'smooth', block: 'start' });
                                const h = document.getElementById('vy-nr-h' + this.step);
                                if (h) h.focus({ preventScroll: true });
                            });
                        });
                        // back to this page from the browser's cache: never a button stuck on its busy label
                        window.addEventListener('pageshow', (e) => { if (e.persisted) { this.priceBusy = -1; this.loadingDates = false; this.confirmSeason = null; } });
                    },
                    calm() { return !! (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches); },

                    // ── the steps this property asks, in order ───────────────────────────────────────────────────
                    place() { return Math.max(0, ORDER.indexOf(this.step)); },
                    known(n) { return ORDER.includes(n) ? n : (n > 1 ? LAST : 1); },

                    // ── what each step still needs (the server re-checks all of it) ──────────────────────────────
                    problem(n) {
                        if (n === 1) {
                            if (! BOOT.datesValid) return 'Pick the check-in and check-out dates first.';
                            if (! this.unitId) return 'Pick a unit for these dates to continue.';
                        }
                        if (n === 2) {
                            if (this.guestMode === 'existing') return this.existingGuestId ? '' : 'Choose the guest from the list — or switch to New guest.';
                            if (! this.newFirst.trim() && ! this.newLast.trim()) return 'Enter the guest’s first or last name.';
                            // the server's own test (FILTER_VALIDATE_EMAIL) is stricter than the browser's: "jo@hotel" passes one, not the other
                            if (this.newEmail.trim() !== '' && ! EMAIL.test(this.newEmail.trim())) return 'That email address doesn’t look right — check it, or leave it empty.';
                        }
                        if (n === 4 && this.overrideProblem()) return 'Say why in the note — it’s needed when you choose Other.';
                        if (n === 4 && BOOT.canPay && this.depositChoice !== 'nothing') {
                            if (/^\s*\d+,\d{1,2}\s*$/.test(this.payAmount)) return 'Use a dot for cents, e.g. 150.50 — a comma reads as thousands.';
                            if (this.toMinor(this.payAmount) === null) return 'Enter the amount paid now, e.g. 150 or 150.00.';
                        }
                        return '';
                    },
                    // ⚠️ The message is STATE, set when a step refuses — not a live expression: a field Alpine does not watch
                    //    never re-rendered a computed message, and a refused "Next" said nothing. recheck() clears or
                    //    updates it as the operator fixes the field.
                    block(k) { this.step = k; this.blockedStep = k; this.blocked = this.problem(k); this.$nextTick(() => this.showMiss(k)); },
                    // ⚠️ two frames later: at $nextTick the message is not laid out yet (Alpine shows it a beat after), and a scroll to an
                    //    element with no box does nothing — measured in the browser on the walk, the message stayed 1,600 px below
                    showMiss(k) {
                        requestAnimationFrame(() => requestAnimationFrame(() => {
                            const m = this.$root.querySelector('[data-miss=\'' + k + '\']');
                            if (m) m.scrollIntoView({ behavior: this.calm() ? 'auto' : 'smooth', block: 'center' });
                        }));
                    },
                    recheck() { if (this.blockedStep !== 0) { this.blocked = this.problem(this.blockedStep); if (this.blocked === '') this.blockedStep = 0; } },
                    overrideProblem() { return this.overrideReason === 'other' && this.overrideNote.trim() === ''; },
                    goto(n) {
                        if (! ORDER.includes(n)) return;
                        const to = ORDER.indexOf(n);
                        for (let i = this.place(); i < to; i++) {
                            if (this.problem(ORDER[i]) !== '') { this.block(ORDER[i]); return; }
                        }
                        this.blocked = ''; this.blockedStep = 0;
                        this.step = n;
                    },
                    next() { const i = this.place(); if (i < ORDER.length - 1) this.goto(ORDER[i + 1]); },
                    prev() { const i = this.place(); if (i > 0) this.goto(ORDER[i - 1]); },
                    submit(e) {
                        // ⚠️ Enter in a text field submits a form. Before the last step it means "next", never "book now".
                        if (this.step !== LAST) { e.preventDefault(); this.next(); return; }
                        for (const k of ORDER) {
                            if (this.problem(k) !== '') { e.preventDefault(); this.block(k); return; }
                        }
                        // #28 — after a refusal the new guest's email and phone come back empty; say so once before booking without them.
                        if (BOOT.contactCleared && this.guestMode === 'new' && ! this.contactWarned
                            && this.newEmail.trim() === '' && this.$refs.phone.value.trim() === '') {
                            e.preventDefault(); this.contactWarned = true; return;
                        }
                        this.persist();
                    },

                    // ── who can be emailed — a new guest's typed address, or whether a returning guest has one (never the address) ──
                    canEmail() {
                        if (this.guestMode === 'existing') return this.existingGuestId !== '' && BOOT.guestEmails[this.existingGuestId] === true;
                        return this.newEmail.trim() !== '' && EMAIL.test(this.newEmail.trim());
                    },
                    mailWhy() {
                        if (! BOOT.mailSends) return 'Email isn’t being sent from Vyrobel yet, so nothing goes out for now.';
                        return this.guestMode === 'existing'
                            ? 'Needs an email address on the guest’s profile — someone who manages guests can add one.'
                            : 'Needs the guest’s email address — add one in the Guest step to send it.';
                    },

                    // ── unit, plan and price — from the server's figures, never a parsed label ───────────────────
                    pickUnit(rtId, unitId) {
                        const r = rt(rtId);
                        if (! r || ! r.units[unitId]) return;
                        this.roomTypeId = rtId; this.unitId = unitId;
                        this.ratePlanId = r.defaultPlan;
                        this.reprice();
                    },
                    plansFor() { const r = rt(this.roomTypeId); return r ? r.plans : []; },
                    planPrice(planId) {
                        const r = rt(this.roomTypeId);
                        if (! r) return 0;
                        const own = (r.unitPrices[planId] || {})[this.unitId];
                        if (own !== undefined) return own;   // the unit's own price — exactly what the folio will charge
                        const p = r.plans.find((x) => x.id === planId);
                        return p ? p.price : r.price;
                    },
                    planName() { const p = this.plansFor().find((x) => x.id === this.ratePlanId); return p ? p.name : ''; },
                    pickPlan(planId) { this.ratePlanId = planId; this.reprice(); },
                    reprice() { this.roomMinor = this.unitId ? this.planPrice(this.ratePlanId) : 0; },
                    pickGuest(id, name) { this.existingGuestId = id; this.existingGuestName = name; },
                    searchGuest() {
                        this.persist();
                        const q = (this.$refs.guestQuery.value || '').trim();
                        window.location = BOOT.createUrl + '?check_in=' + encodeURIComponent(BOOT.checkIn) + '&check_out=' + encodeURIComponent(BOOT.checkOut) + '&q=' + encodeURIComponent(q);
                    },
                    submitPrice(i) { const f = document.getElementById('vy-nr-price-' + i); if (f && this.priceBusy === -1) f.requestSubmit(); },
                    priceSubmit(e, i) {
                        const field = document.getElementById('vy-nr-rate-' + i);
                        const raw = field ? field.value : '';
                        if (/^\s*\d+,\d{1,2}\s*$/.test(raw)) { e.preventDefault(); this.priceErr[i] = 'Use a dot for cents, e.g. 120.50 — a comma reads as thousands.'; return; }
                        if (this.toMinor(raw) === null) { e.preventDefault(); this.priceErr[i] = 'Enter the price a night, e.g. 120 or 120.50.'; return; }
                        this.persist();
                        this.priceBusy = i;
                    },
                    // ⚠️ Opens the "Apply this season?" disclosure — the Rates page's own (one partial, one builder). Its form, outside
                    //    the booking form, is the only thing that applies (the review, H2).
                    openSeason(id) { this.confirmSeason = BOOT.seasons[id] || null; },

                    // ── money — the server's own rules: MoneyInput's format, FolioService's tax and fee formulas, over the
                    //    taxes and fees StayEstimator says the folio posts (filtered server-side) ─────────────────────
                    symbol() { const r = rt(this.roomTypeId); return r ? r.symbol : BOOT.symbol; },
                    fmt(m) {
                        const abs = (Math.abs(m) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        return (m < 0 ? '−' : '') + this.symbol() + abs;
                    },
                    // a calendar night's price, short: €200 for whole amounts, €200.50 otherwise (the property's currency)
                    fmtNight(m) { return BOOT.symbol + (m % 100 === 0 ? String(m / 100) : (m / 100).toFixed(2)); },
                    toMinor(s) {
                        const c = String(s || '').trim().replace(/[ ,]/g, '').replace(/^[€£$]+/, '');
                        if (! /^\d+(\.\d{1,2})?$/.test(c)) return null;
                        const parts = c.split('.');
                        const m = parseInt(parts[0], 10) * 100 + parseInt(((parts[1] || '') + '00').slice(0, 2), 10);
                        return m > 0 ? m : null;
                    },
                    stayCurrency() { const r = rt(this.roomTypeId); return r ? r.currency : BOOT.currency; },
                    persons() { return Math.max(1, this.adults + this.children); },
                    quantity(e) {
                        if (e.basis === 'per_guest_night') return this.persons() * BOOT.nights;
                        if (e.basis === 'per_night') return BOOT.nights;
                        if (e.basis === 'per_leg') return this.transferTrip === 'return' ? 2 : 1;
                        return 1;
                    },
                    chosenExtras() { return BOOT.extras.filter((e) => this.extras.includes(e.id) && e.currency === this.stayCurrency()); },
                    extrasMinor() { return this.chosenExtras().reduce((s, e) => s + e.price * this.quantity(e), 0); },
                    taxBase() { return this.roomMinor + this.chosenExtras().filter((e) => e.taxable).reduce((s, e) => s + e.price * this.quantity(e), 0); },
                    taxMinor() {
                        const base = this.taxBase(); const n = Math.max(1, BOOT.nights);
                        return (BOOT.taxes[this.stayCurrency()] || []).reduce((s, t) => s + (t.mode === 'percentage' ? Math.round(base * t.rate / 100)
                            : t.mode === 'per_night' ? t.amount * n : t.mode === 'per_person_night' ? t.amount * this.persons() * n : t.amount), 0);
                    },
                    feeMinor() {
                        const base = this.taxBase(); const n = Math.max(1, BOOT.nights);
                        return (BOOT.fees[this.stayCurrency()] || []).reduce((s, f) => s + (f.mode === 'percentage' ? Math.round(base * f.rate / 100)
                            : f.mode === 'per_night' ? f.amount * n : f.mode === 'per_person' ? f.amount * this.persons() : f.amount), 0);
                    },
                    totalMinor() { return this.roomMinor + this.extrasMinor() + this.taxMinor() + this.feeMinor(); },
                    paidNow() { return Math.min(this.toMinor(this.payAmount) || 0, this.totalMinor()); },
                    setDeposit(c) {
                        this.depositChoice = c;
                        if (c === 'nothing') { this.payAmount = ''; this.paySuggested = ''; return; }
                        if (this.payMethod !== 'cash' && this.payMethod !== 'bank') this.payMethod = 'cash';
                        const total = this.totalMinor();
                        this.payAmount = total > 0 ? ((c === 'full' ? total : Math.round(total * 0.3)) / 100).toFixed(2) : '';
                        this.paySuggested = this.payAmount;
                    },

                    // ── labels ───────────────────────────────────────────────────────────────────────────────────
                    partyLabel() {
                        const a = this.adults + (this.adults === 1 ? ' adult' : ' adults');
                        return this.children > 0 ? a + ', ' + this.children + (this.children === 1 ? ' child' : ' children') : a;
                    },
                    unitLabel() {
                        const r = rt(this.roomTypeId);
                        if (! r || ! this.unitId || ! r.units[this.unitId]) return 'Not chosen yet';
                        const plan = r.plans.length > 1 ? this.planName() : '';
                        return r.units[this.unitId].code + ' · ' + r.name + (plan !== '' ? ' · ' + plan : '');
                    },
                    guestLabel() {
                        if (this.guestMode === 'existing') return this.existingGuestName || 'Not chosen yet';
                        return (this.newFirst + ' ' + this.newLast).trim() || 'Not added yet';
                    },
                    extrasLabel() { const n = this.chosenExtras().length; return n === 0 ? 'None' : n + (n === 1 ? ' extra' : ' extras'); },

                    // ── the two-month calendar — dates ride a GET (the availability read is server-side, #20) ──
                    // Each night carries the server's figures (BOOT.nightly): its lowest price and the rooms left. "Today" is
                    // the PROPERTY's day, not this browser's.
                    calInit() { const base = this.cal.start ? new Date(this.cal.start + 'T00:00') : new Date(BOOT.today + 'T00:00'); this.cal.y = base.getFullYear(); this.cal.m = base.getMonth(); },
                    calYM(off) { const d = new Date(this.cal.y, this.cal.m + off, 1); return { y: d.getFullYear(), m: d.getMonth() }; },
                    calLabel(off) { const c = this.calYM(off); return new Date(c.y, c.m, 1).toLocaleDateString('en-GB', { month: 'long', year: 'numeric' }); },
                    calGrid(off) {
                        const c = this.calYM(off);
                        const lead = (new Date(c.y, c.m, 1).getDay() + 6) % 7;   // Monday first
                        const dim = new Date(c.y, c.m + 1, 0).getDate();
                        const cells = [];
                        for (let i = 0; i < lead; i++) cells.push({ date: '', day: '', tag: '' });
                        for (let dd = 1; dd <= dim; dd++) {
                            const dt = new Date(c.y, c.m, dd);
                            const iso = c.y + '-' + String(c.m + 1).padStart(2, '0') + '-' + String(dd).padStart(2, '0');
                            const past = iso < BOOT.today;
                            const n = past ? null : (BOOT.nightly[iso] || null);
                            cells.push({ date: iso, day: dd, past: past, today: iso === BOOT.today, weekend: dt.getDay() === 0 || dt.getDay() === 6,
                                full: !! n && n.f === 0, noprice: !! n && n.f > 0 && n.p === null, night: n,
                                tag: ! n ? '' : (n.f === 0 ? 'Full' : (n.p === null ? '—' : this.fmtNight(n.p))) });
                        }
                        return cells;
                    },
                    calClick(iso) {
                        if (! iso || this.loadingDates) return;
                        if (! this.cal.start || this.cal.end || iso <= this.cal.start) { this.cal.start = iso; this.cal.end = ''; return; }
                        this.cal.end = iso;
                        this.calApply();   // the stay is chosen — load what's free straight away
                    },
                    calState(iso) {
                        if (! iso) return '';
                        if (iso === this.cal.start) return 'start';
                        if (iso === this.cal.end) return 'end';
                        const e = this.cal.end || (this.cal.start ? this.cal.hover : '');
                        return this.cal.start && e && iso > this.cal.start && iso < e ? 'in' : '';
                    },
                    calNights() { return Math.round((new Date(this.cal.end + 'T00:00') - new Date(this.cal.start + 'T00:00')) / 86400000); },
                    calSelLabel() {
                        if (this.loadingDates) return 'Finding what’s free…';
                        if (! this.cal.start) return 'Pick the check-in date';
                        if (! this.cal.end) return 'Check-in ' + this.calFmt(this.cal.start) + ' — now pick check-out';
                        const n = this.calNights();
                        return this.calFmt(this.cal.start) + ' → ' + this.calFmt(this.cal.end) + ' · ' + n + (n === 1 ? ' night' : ' nights');
                    },
                    calAria(d) {
                        const s = this.calState(d.date);
                        const label = new Date(d.date + 'T00:00').toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                        const n = d.night;
                        const info = ! n ? '' : (n.f === 0 ? ', full' : (n.p === null ? ', ' + n.f + ' left, no price yet' : ', from ' + this.fmtNight(n.p) + ', ' + n.f + ' left'));
                        return label + (d.today ? ', today' : '') + info + (s === 'start' ? ', check-in' : s === 'end' ? ', check-out' : '');
                    },
                    calFmt(iso) { return new Date(iso + 'T00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }); },
                    calPrev() { const d = new Date(this.cal.y, this.cal.m - 1, 1); this.cal.y = d.getFullYear(); this.cal.m = d.getMonth(); },
                    calNext() { const d = new Date(this.cal.y, this.cal.m + 1, 1); this.cal.y = d.getFullYear(); this.cal.m = d.getMonth(); },
                    calClear() { this.cal.start = ''; this.cal.end = ''; },
                    calApply() {
                        if (! this.cal.start || ! this.cal.end) return;
                        this.persist();
                        this.loadingDates = true;
                        window.location = BOOT.createUrl + '?check_in=' + this.cal.start + '&check_out=' + this.cal.end;
                    },

                    // ── the draft (this browser only) and a refused booking's state (from the server) ─────────────
                    snapshot() {
                        const d = { v: 4, propertyId: BOOT.propertyId, checkIn: BOOT.checkIn, checkOut: BOOT.checkOut, savedAt: Date.now() };
                        KEPT.forEach((k) => { d[k] = this[k]; });
                        return d;
                    },
                    persist() { try { sessionStorage.setItem(DRAFT_KEY, JSON.stringify(this.snapshot())); } catch (e) { /* storage blocked — the wizard still works, it just forgets */ } },
                    clearDraft() { try { sessionStorage.removeItem(DRAFT_KEY); } catch (e) {} },
                    restoreDraft() {
                        let d = null;
                        try { d = JSON.parse(sessionStorage.getItem(DRAFT_KEY) || 'null'); } catch (e) { d = null; }
                        if (! d || d.v !== 4 || d.propertyId !== BOOT.propertyId || ! d.savedAt || Date.now() - d.savedAt > DRAFT_MAX_AGE) return;
                        // what the Stay step shows, whatever the dates — the calendar reloads the page for every new stay
                        this.adults = Math.min(30, Math.max(1, parseInt(d.adults, 10) || 2));
                        this.children = Math.min(30, Math.max(0, parseInt(d.children, 10) || 0));
                        // the rest only for the same stay, and the unit only while it is still free
                        const r = rt(d.roomTypeId);
                        if (d.checkIn !== BOOT.checkIn || d.checkOut !== BOOT.checkOut || ! r || ! r.units[d.unitId]) return;
                        this.roomTypeId = d.roomTypeId; this.unitId = d.unitId;
                        this.ratePlanId = r.plans.some((p) => p.id === d.ratePlanId) ? d.ratePlanId : r.defaultPlan;
                        this.reprice();
                        if (d.guestMode === 'existing' || d.guestMode === 'new') this.guestMode = d.guestMode;
                        this.extras = Array.isArray(d.extras) ? d.extras.filter((id) => BOOT.extras.some((e) => e.id === id)) : [];
                        this.transferTrip = d.transferTrip === 'return' ? 'return' : 'one';
                        this.arrivalWindow = typeof d.arrivalWindow === 'string' ? d.arrivalWindow.slice(0, 60) : '';
                        this.step = this.known(parseInt(d.step, 10) || 1);
                        this.draftRestored = true;
                    },
                    applyRestore(s) {
                        ['adults', 'children', 'guestMode', 'existingGuestId', 'existingGuestName', 'newFirst', 'newLast', 'extras', 'transferTrip', 'applyCredit', 'arrivalWindow', 'rulesAgreed']
                            .forEach((k) => { this[k] = s[k]; });
                        if (s.unitId && rt(s.roomTypeId)) { this.roomTypeId = s.roomTypeId; this.unitId = s.unitId; this.ratePlanId = s.ratePlanId; this.reprice(); }
                        if (BOOT.canPay && (s.payMethod === 'cash' || s.payMethod === 'bank')) {
                            this.payMethod = s.payMethod; this.payAmount = s.payAmount;   // the amount just sent — kept as typed
                            this.depositChoice = this.toMinor(s.payAmount) === this.totalMinor() ? 'full' : 'deposit';
                        }
                        this.step = this.known(s.step);
                    },
                    startOver() { this.clearDraft(); window.location = BOOT.createUrl; },
                };
            }
        </script>
    @endif
</div>
@endif
