{{-- Operator NEW RESERVATION — a focused 7-step wizard on the certified createBooking path (no app shell: the operator
     LAYOUT supplies the gradient, orbs and tokens; this page draws its own top bar with a close-X to the rack).
     Step 1's availability is a REAL server read (GET → UnitAvailabilityService — only genuinely-free, priced units;
     createBooking re-checks under a row lock). Steps 2–7 are client-side over that render and post as ONE form →
     ReservationCreateController, which posts the real reconciling folio lines (#17). The summary is an ESTIMATE from
     the same real prices and the same tax and fee rules; the authoritative total is the folio once created.
     GUARDS: honest availability · guest NAME-only search (#28) · NO raw PAN (card = honest "coming soon", #45).

     ⚠️⚠️ 2026-10-04 (the founder: "it is not possible to do reservation"). A booking now (1) asks which property while
     "All properties" is selected, and offers a picker in the top bar; (2) says when a property has no rooms; (3) lets a
     person who manages rates set a missing price for exactly the stay's nights, here, through the Rates write's own rules,
     and comes back ready to book; (4) keeps everything entered when it is refused and reopens at the step to fix — never
     a new guest's email or phone (#28); (5) checks each step before moving on and says what is missing; (6) prices in the
     stay's own currency, with a unit's own price where one is set. RAW-BLADE RULE: Alpine uses x-on:/x-bind (no `@click`);
     every `@` follows a space, a newline or a tag. --}}
@php
    $__nrReadOnly = app(\App\Support\Operator\OperatorWorkspace::class)->isReadOnly();
    $__nrWizard = $hasProperty && $rooms['bookable'] > 0;   // the steps render (and their script) only when there is something to book
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
            <div class="vy-nr-qr">{!! $done['qr'] !!}</div>
            <div class="vy-nr-qrlabel">Self check-in code</div>
            {{-- ⚠️ The "from HH:MM" clause is OMITTED when the property has published no check-in time,
                 rather than inventing one (D8-2). One expression, not @if: Livewire wraps a directive in
                 [if BLOCK] markers, which splits composed text in the delivered HTML. --}}
            <div class="vy-nr-qrsub">{{ $done['guest'] }} scans to open online check-in{{ $done['check_in_time'] !== null ? ' from '.$done['check_in_time'] : '' }} — in the guest portal (they sign in first). No check-in code is stored here.</div>
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
                <span class="vy-nr-stepcount" x-text="'Step ' + step + ' of 7'">Step {{ $initialStep }} of 7</span>
            @endif
            <a class="vy-nr-close" href="{{ route('app.reservations') }}" title="Close" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
            </a>
        </div>
    </header>

    @if (session('new_reservation_ok'))
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
             those dates" — on production, the reason no booking could be made. It says what is missing instead. --}}
        <section class="vy-nr-card vy-nr-blank vy-fade" aria-labelledby="vy-nr-norooms">
            @if ($rooms['all'] === 0)
                <h2 id="vy-nr-norooms" class="vy-nr-h">{{ $subtitle }} has no rooms yet</h2>
                <p class="vy-nr-lede">A booking needs a unit to stay in. Add this property’s units first — then they can be booked here.</p>
            @else
                <h2 id="vy-nr-norooms" class="vy-nr-h">None of {{ $subtitle }}’s units can take a booking</h2>
                <p class="vy-nr-lede">They’re all paused or out of service. Reopen one on the Units page, then book it here.</p>
            @endif
            <div class="vy-nr-blank-actions">
                <a class="vy-btn vy-btn--accent" href="{{ route('app.units') }}">Go to Units</a>
                @if (count($properties) > 1)
                    <span class="vy-nr-fine">Or book into another property — choose it at the top.</span>
                @endif
            </div>
        </section>
    @else
        <div>
            <div class="vy-nr-draft" x-show="draftRestored" x-cloak role="status">
                <span>Picked up where you left off.</span>
                <button type="button" class="vy-nr-textbtn" x-on:click="startOver()">Start over</button>
            </div>

            @php $__nrSteps = ['When & unit', 'Who’s staying', 'Rate & extras', 'Arrival & transfer', 'House rules', 'Review', 'Payment']; @endphp
            <nav class="vy-wiz-stepper vy-fade" aria-label="Booking steps">
                @foreach ($__nrSteps as $__i => $__label)
                    <div class="vy-wiz-node" x-bind:class="step === {{ $__i + 1 }} ? 'is-on' : (step > {{ $__i + 1 }} ? 'is-done' : '')">
                        <div class="vy-wiz-node-row">
                            <span class="vy-wiz-bar{{ $__i === 0 ? ' is-edge' : '' }}"></span>
                            <button type="button" class="vy-wiz-circle" x-on:click="goto({{ $__i + 1 }})" x-bind:aria-current="step === {{ $__i + 1 }} ? 'step' : null" aria-label="Step {{ $__i + 1 }}: {{ $__label }}">
                                <span class="vy-wiz-circle-num">{{ $__i + 1 }}</span>
                                <svg class="vy-wiz-circle-check" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                            </button>
                            <span class="vy-wiz-bar{{ $__i === count($__nrSteps) - 1 ? ' is-edge' : '' }}"></span>
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
                                <p class="vy-nr-miss vy-nr-miss--tight" x-show="blockedStep === 7 &amp;&amp; blocked !== '' &amp;&amp; overrideProblem()" x-text="blocked" x-cloak role="alert"></p>
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

                    {{-- ── STEP 1 — When & which unit ─────────────────────────────────────────────────────────── --}}
                    <section class="vy-nr-card" x-show="step === 1" @if ($initialStep !== 1) x-cloak @endif aria-labelledby="vy-nr-h1">
                        <h2 id="vy-nr-h1" class="vy-nr-h">When &amp; which unit</h2>
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
                                                            x-bind:class="{ 'is-pad': !d.date, 'is-past': d.past, 'is-weekend': d.weekend, 'is-start': calState(d.date) === 'start', 'is-end': calState(d.date) === 'end', 'is-in': calState(d.date) === 'in' }"
                                                            x-bind:disabled="!d.date || d.past || loadingDates"
                                                            x-bind:aria-label="d.date ? calAria(d.date) : null"
                                                            x-bind:aria-pressed="d.date ? String(calState(d.date) === 'start' || calState(d.date) === 'end') : null"
                                                            x-on:click="calClick(d.date)" x-on:mouseenter="if (d.date) cal.hover = d.date"
                                                            x-text="d.day"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="vy-nr-cal-foot" aria-live="polite">
                                    <span class="vy-nr-cal-sel" x-text="calSelLabel()">{{ $datesValid ? $datesLabel : 'Pick the check-in date' }}</span>
                                    <button type="button" class="vy-nr-textbtn" x-show="cal.start &amp;&amp; ! loadingDates" @unless ($datesValid) x-cloak @endunless x-on:click="calClear()">Clear dates</button>
                                </div>
                            </div>

                            <div class="vy-nr-party">
                                <div class="vy-nr-count">
                                    <span class="vy-nr-label" id="vy-nr-adults-l">Adults</span>
                                    <div class="vy-nr-counter" role="group" aria-labelledby="vy-nr-adults-l">
                                        <button type="button" x-on:click="adults = Math.max(1, adults - 1)" x-bind:disabled="adults &lt;= 1" aria-label="Fewer adults">−</button>
                                        <output x-text="adults">{{ $restored['adults'] ?? 2 }}</output>
                                        <button type="button" x-on:click="adults = Math.min(30, adults + 1)" x-bind:disabled="adults &gt;= 30" aria-label="More adults">+</button>
                                    </div>
                                </div>
                                <div class="vy-nr-count">
                                    <span class="vy-nr-label" id="vy-nr-children-l">Children</span>
                                    <div class="vy-nr-counter" role="group" aria-labelledby="vy-nr-children-l">
                                        <button type="button" x-on:click="children = Math.max(0, children - 1)" x-bind:disabled="children &lt;= 0" aria-label="Fewer children">−</button>
                                        <output x-text="children">{{ $restored['children'] ?? 0 }}</output>
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
                                    <div class="vy-nr-rt">
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
                                        @elseif ($rt['has_plan'] && $canSetPrice)
                                            {{-- ⚠️⚠️ THE STAY'S MISSING PRICE, SET HERE. It used to be a link to the Rates page that threw the
                                                 booking away. These controls belong to a form OUTSIDE the booking form (a form inside a
                                                 form is not HTML), joined by the `form` attribute; it posts the Rates write with the
                                                 same rules, for exactly these nights, and comes back here. --}}
                                            <div class="vy-nr-setprice">
                                                <p class="vy-nr-setprice-why"><strong>No price for {{ $rt['unpriced_nights'] >= $nights ? 'these nights' : $rt['unpriced_nights'].' of these '.$nights.' nights' }} yet.</strong> Set one and {{ $rt['unit_count'] === 1 ? 'its free unit' : 'its '.$rt['unit_count'].' free units' }} can be booked.</p>
                                                <div class="vy-nr-setprice-row">
                                                    <label class="vy-nr-label" for="vy-nr-rate-{{ $loop->index }}">Price a night</label>
                                                    <span class="vy-nr-money">
                                                        <span class="vy-nr-money-sym" aria-hidden="true">{{ $rt['symbol'] }}</span>
                                                        <input id="vy-nr-rate-{{ $loop->index }}" form="vy-nr-price-{{ $loop->index }}" type="text" name="rate" class="vy-nr-input" inputmode="decimal" placeholder="e.g. 120" autocomplete="off"
                                                               x-on:input="priceErr[{{ $loop->index }}] = ''" x-on:keydown.enter.prevent="submitPrice({{ $loop->index }})">
                                                    </span>
                                                    {{-- ⚠️ type="button": a submit button here sits inside the booking form, and the shared double-submit
                                                         guard disabled IT, not Confirm, when the booking was sent (the independent review). --}}
                                                    <button type="button" class="vy-btn vy-btn--accent" x-on:click="submitPrice({{ $loop->index }})" x-bind:disabled="priceBusy === {{ $loop->index }}"
                                                            x-text="priceBusy === {{ $loop->index }} ? 'Setting…' : 'Set price'">Set price</button>
                                                </div>
                                                <p class="vy-nr-miss" x-show="priceErr[{{ $loop->index }}]" x-text="priceErr[{{ $loop->index }}]" x-cloak role="alert"></p>
                                                <p class="vy-nr-fine">The same price goes on each night from {{ \Carbon\CarbonImmutable::parse($checkIn)->format('j M') }} to {{ \Carbon\CarbonImmutable::parse($lastNight)->format('j M') }}{{ $rt['plan_name'] !== '' ? ' on the '.$rt['plan_name'].' plan' : '' }} — a price already on one of them is replaced. Change it any time on the Rates page.</p>
                                            </div>
                                        @elseif ($rt['has_plan'])
                                            <p class="vy-nr-noprice">No price is set for these nights, so {{ $rt['name'] }} can’t be booked yet. Ask someone who manages rates to set one — its free units appear here once they have.</p>
                                        @else
                                            <p class="vy-nr-noprice">{{ $rt['name'] }} has no rate plan yet, so it has no price.@if ($canSetPrice) <a class="vy-nr-link" href="{{ route('app.rates') }}" target="_blank" rel="noopener">Add one on the Rates page<span class="vy-sr-only"> (opens in a new tab)</span></a>@else Someone who manages rates can add one.@endif</p>
                                        @endif
                                    </div>
                                @empty
                                    <p class="vy-nr-empty">Nothing is free for {{ $datesLabel }}. Try other dates, or check the rack.</p>
                                @endforelse
                            @endunless
                        <p class="vy-nr-miss" x-show="blockedStep === 1 &amp;&amp; blocked !== ''" x-text="blocked" x-cloak role="alert"></p>
                    </section>

                    {{-- ── STEP 2 — Who's staying ──────────────────────────────────────────────────────────────── --}}
                    <section class="vy-nr-card" x-show="step === 2" @if ($initialStep !== 2) x-cloak @endif aria-labelledby="vy-nr-h2">
                        <h2 id="vy-nr-h2" class="vy-nr-h">Who’s staying</h2>
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
                                <div class="vy-nr-field"><label class="vy-nr-label" for="ge">Email <span class="vy-nr-opt">optional</span></label><input id="ge" type="email" name="guest_email" class="vy-nr-input" x-bind:disabled="guestMode !== 'new'" x-ref="email" x-on:input="recheck()" autocomplete="off"></div>
                                <div class="vy-nr-field"><label class="vy-nr-label" for="gp">Phone <span class="vy-nr-opt">optional</span></label><input id="gp" type="tel" name="guest_phone" class="vy-nr-input" x-bind:disabled="guestMode !== 'new'" x-ref="phone" autocomplete="off"></div>
                                <div class="vy-nr-field"><label class="vy-nr-label" for="gc">Country <span class="vy-nr-opt">optional · 2 letters, e.g. PT</span></label><input id="gc" type="text" name="guest_country" class="vy-nr-input vy-nr-input--short" maxlength="2" value="{{ $restored['country'] ?? '' }}" x-bind:disabled="guestMode !== 'new'" x-ref="country" x-on:input="$event.target.value = $event.target.value.toUpperCase(); recheck()" autocomplete="off"></div>
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
                        <p class="vy-nr-miss" x-show="blockedStep === 2 &amp;&amp; blocked !== ''" x-text="blocked" x-cloak role="alert"></p>
                    </section>

                    {{-- ── STEP 3 — Rate & extras ───────────────────────────────────────────────────────────────── --}}
                    <section class="vy-nr-card" x-show="step === 3" @if ($initialStep !== 3) x-cloak @endif aria-labelledby="vy-nr-h3">
                        <h2 id="vy-nr-h3" class="vy-nr-h">Rate &amp; extras</h2>
                        <p class="vy-nr-lede">Pick the rate plan, then anything the guest wants — each one is a real line on the folio.</p>

                        {{-- REAL active plans of the chosen room type, priced over the stay. >1 → a choice; 1 → named. --}}
                        <div x-show="plansFor().length &gt; 1" x-cloak>
                            <div class="vy-nr-label">Rate plan</div>
                            <div class="vy-nr-plans" role="radiogroup" aria-label="Rate plan">
                                <template x-for="p in plansFor()" :key="p.id">
                                    <label class="vy-nr-plan" x-bind:class="ratePlanId === p.id ? 'is-on' : ''">
                                        <input type="radio" class="vy-sr-only" name="plan_pick" x-bind:value="p.id" x-bind:checked="ratePlanId === p.id" x-on:change="pickPlan(p.id)">
                                        <span class="vy-nr-plan-name" x-text="p.name"></span>
                                        <span class="vy-nr-plan-price" x-text="fmt(planPrice(p.id)) + ' for the stay'"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                        <p class="vy-nr-planone" x-show="roomTypeId !== '' &amp;&amp; plansFor().length === 1" x-cloak>Rate plan <strong x-text="plansFor().length ? plansFor()[0].name : ''"></strong> · <span x-text="fmt(roomMinor) + ' for the stay'"></span></p>

                        <div class="vy-nr-label vy-nr-label--gap">Extras</div>
                        @forelse ($extras as $ex)
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
                        @empty
                            <p class="vy-nr-empty">This property has no extras set up.</p>
                        @endforelse
                        <label class="vy-nr-extra" x-show="guestMode === 'existing'" @if (($restored['guestMode'] ?? ($query !== '' ? 'existing' : 'new')) !== 'existing') x-cloak @endif>
                            <span class="vy-nr-extra-text"><strong>Returning-guest credit</strong><span class="vy-nr-extra-basis"> · uses the guest’s wallet balance, if they have one</span></span>
                            <input type="checkbox" class="vy-nr-switch" x-model="applyCredit">
                        </label>
                    </section>

                    {{-- ── STEP 4 — Arrival & transfer ──────────────────────────────────────────────────────────── --}}
                    <section class="vy-nr-card" x-show="step === 4" @if ($initialStep !== 4) x-cloak @endif aria-labelledby="vy-nr-h4">
                        <h2 id="vy-nr-h4" class="vy-nr-h">Arrival &amp; transfer</h2>
                        <p class="vy-nr-lede">When is the guest arriving? A pickup is a transfer extra in step 3.</p>
                        <div class="vy-nr-field">
                            <label class="vy-nr-label" for="aw">Estimated arrival <span class="vy-nr-opt">optional</span></label>
                            <input id="aw" type="text" name="arrival_window" class="vy-nr-input" placeholder="e.g. 3–6 PM" maxlength="60" x-model="arrivalWindow" autocomplete="off">
                        </div>
                        <p class="vy-nr-note is-calm">Making their own way? The confirmation includes directions and parking. The door code is the guest’s alone — released to them close to arrival, never shown here.</p>
                    </section>

                    {{-- ── STEP 5 — House rules ─────────────────────────────────────────────────────────────────── --}}
                    <section class="vy-nr-card" x-show="step === 5" @if ($initialStep !== 5) x-cloak @endif aria-labelledby="vy-nr-h5">
                        <h2 id="vy-nr-h5" class="vy-nr-h">House rules</h2>
                        <p class="vy-nr-lede">How the property runs — these go out with the guest’s confirmation.</p>
                        <div class="vy-nr-rules">
                            {{-- ⚠️ An unpublished time reads "—", the same absence idiom this panel uses for min_stay — never a
                                 fabricated hour, and never "00:00", which is just a different lie. --}}
                            <div><span>Check-in</span><strong>{{ $houseRules['check_in'] !== null ? 'From '.$houseRules['check_in'] : '—' }}</strong></div>
                            <div><span>Check-out</span><strong>{{ $houseRules['check_out'] !== null ? 'By '.$houseRules['check_out'] : '—' }}</strong></div>
                            <div><span>Pets</span><strong>{{ $houseRules['pet'] ?? 'Not permitted' }}</strong></div>
                            <div><span>Smoking</span><strong>{{ $houseRules['smoking'] ?? 'Not permitted' }}</strong></div>
                            <div><span>Minimum stay</span><strong>{{ $houseRules['min_stay'] ?? '—' }}</strong></div>
                            <div><span>Cancellation</span><strong>{{ $houseRules['cancellation'] ?? 'As per booking terms' }}</strong></div>
                        </div>
                        <label class="vy-nr-check vy-nr-check--gap"><input type="checkbox" name="rules_agreed" value="1" x-model="rulesAgreed"> The guest accepts the house rules</label>
                    </section>

                    {{-- ── STEP 6 — Review ──────────────────────────────────────────────────────────────────────── --}}
                    <section class="vy-nr-card" x-show="step === 6" @if ($initialStep !== 6) x-cloak @endif aria-labelledby="vy-nr-h6">
                        <h2 id="vy-nr-h6" class="vy-nr-h">Review</h2>
                        <p class="vy-nr-lede">One last look before payment.</p>
                        <dl class="vy-nr-review">
                            <div><dt>Guest</dt><dd x-text="guestLabel()">—</dd><button type="button" class="vy-nr-textbtn" x-on:click="goto(2)">Change</button></div>
                            <div><dt>Stay</dt><dd><span>{{ $datesValid ? $datesLabel : '—' }}</span> · <span x-text="partyLabel()"></span></dd><button type="button" class="vy-nr-textbtn" x-on:click="goto(1)">Change</button></div>
                            <div><dt>Unit</dt><dd x-text="unitLabel()">—</dd><button type="button" class="vy-nr-textbtn" x-on:click="goto(1)">Change</button></div>
                            <div><dt>Extras</dt><dd x-text="extrasLabel()">None</dd><button type="button" class="vy-nr-textbtn" x-on:click="goto(3)">Change</button></div>
                            <div><dt>Arrival</dt><dd x-text="arrivalWindow.trim() !== '' ? arrivalWindow : 'Not given'">Not given</dd><button type="button" class="vy-nr-textbtn" x-on:click="goto(4)">Change</button></div>
                        </dl>
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
                             submits nothing); the checked "1" overrides it. --}}
                        <input type="hidden" name="send_confirmation" value="0">
                        <label class="vy-nr-check"><input type="checkbox" name="send_confirmation" value="1" @checked($restored['sendConfirmation'] ?? true)> Email the confirmation to the guest</label>
                    </section>

                    {{-- ── STEP 7 — Payment ─────────────────────────────────────────────────────────────────────── --}}
                    <section class="vy-nr-card" x-show="step === 7" @if ($initialStep !== 7) x-cloak @endif aria-labelledby="vy-nr-h7">
                        <h2 id="vy-nr-h7" class="vy-nr-h">Payment</h2>
                        <p class="vy-nr-lede">Take a deposit or the full amount now, or collect it at check-in.</p>
                        @if ($canPay)
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
                                <p class="vy-nr-note is-calm">Left to pay at check-in: <strong x-text="fmt(Math.max(0, totalMinor() - (toMinor(payAmount) || 0)))"></strong>. Card payments arrive with secure Stripe checkout — <strong>no card number is entered or kept here</strong>.</p>
                            </div>
                        @else
                            <p class="vy-nr-empty">Payment is taken later, from the reservation’s folio.</p>
                        @endif
                        <p class="vy-nr-miss" x-show="blockedStep === 7 &amp;&amp; blocked !== '' &amp;&amp; ! overrideProblem()" x-text="blocked" x-cloak role="alert"></p>
                        <p class="vy-nr-miss" x-show="contactWarned" x-cloak role="alert">The guest’s email and phone are empty — they weren’t kept when the booking came back. Add them in step 2, or press Confirm again to book without them.</p>
                    </section>

                    <div class="vy-nr-nav">
                        <button type="button" class="vy-btn vy-btn--ghost" x-show="step &gt; 1" @if ($initialStep === 1) x-cloak @endif x-on:click="prev()">Back</button>
                        <span class="vy-nr-nav-gap"></span>
                        <button type="button" class="vy-btn vy-btn--accent" x-show="step &lt; 7" @if ($initialStep === 7) x-cloak @endif x-on:click="next()">Next</button>
                        <button type="submit" class="vy-btn vy-btn--accent" x-show="step === 7" @if ($initialStep !== 7) x-cloak @endif data-busy-label="Booking…" @disabled($__nrReadOnly)>Confirm reservation</button>
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
                        <div class="vy-nr-line"><span>Accommodation</span><span x-text="unitId ? fmt(roomMinor) : '—'">—</span></div>
                        <div class="vy-nr-line" x-show="unitId &amp;&amp; extrasMinor() &gt; 0" x-cloak><span>Extras</span><span x-text="fmt(extrasMinor())"></span></div>
                        <div class="vy-nr-line" x-show="unitId &amp;&amp; applyCredit &amp;&amp; guestMode === 'existing'" x-cloak><span>Returning-guest credit</span><span class="vy-nr-line-note">applied on confirm</span></div>
                        <div class="vy-nr-line" x-show="unitId &amp;&amp; taxMinor() &gt; 0" x-cloak><span>Taxes</span><span x-text="fmt(taxMinor())"></span></div>
                        <div class="vy-nr-line" x-show="unitId &amp;&amp; feeMinor() &gt; 0" x-cloak><span>Fees</span><span x-text="fmt(feeMinor())"></span></div>
                        <div class="vy-nr-line is-total"><span>Estimated total</span><span x-text="unitId ? fmt(totalMinor()) : '—'">—</span></div>
                    </div>
                    <p class="vy-nr-fine">An estimate — the exact total, with any wallet credit, is on the reservation’s folio once it’s created.</p>
                </aside>
            </div>

            {{-- The set-price forms — OUTSIDE the booking form; their visible controls above join them by `form=`. --}}
            @foreach ($roomTypes as $rt)
                @if (! $rt['priced'] && $rt['has_plan'] && $canSetPrice)
                    <form id="vy-nr-price-{{ $loop->index }}" method="POST" action="{{ route('app.rates.update') }}" class="vy-nr-priceform" data-guard-submit x-on:submit="priceSubmit($event, {{ $loop->index }})">
                        @csrf
                        <input type="hidden" name="room_type_id" value="{{ $rt['room_type_id'] }}">
                        <input type="hidden" name="start_date" value="{{ $checkIn }}">
                        <input type="hidden" name="end_date" value="{{ $lastNight }}">
                        <input type="hidden" name="set_rate" value="1">
                        <input type="hidden" name="return" value="new-reservation">
                        <input type="hidden" name="return_check_in" value="{{ $checkIn }}">
                        <input type="hidden" name="return_check_out" value="{{ $checkOut }}">
                    </form>
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
                //    ⚠️ What comes back across a date change is only what step 1 shows (party size); the rest only for the same
                //    stay, with the "Picked up where you left off" line. Payment, credit and the rules answer are never restored.
                const DRAFT_KEY = 'vyrobel:new-reservation:draft';
                const DRAFT_MAX_AGE = 12 * 3600 * 1000;
                const KEPT = ['step', 'adults', 'children', 'roomTypeId', 'unitId', 'ratePlanId', 'guestMode', 'extras', 'transferTrip', 'arrivalWindow'];
                const rt = (id) => BOOT.roomTypes[id] || null;
                const EMAIL = /^[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+(\.[A-Za-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@[A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?(\.[A-Za-z0-9]([A-Za-z0-9-]*[A-Za-z0-9])?)+$/;
                return {
                    step: 1, blocked: '', blockedStep: 0, contactWarned: false, loadingDates: false, draftRestored: false,
                    roomTypeId: '', unitId: '', ratePlanId: '', roomMinor: 0,
                    adults: 2, children: 0,
                    guestMode: BOOT.searched ? 'existing' : 'new',
                    existingGuestId: '', existingGuestName: '', newFirst: '', newLast: '',
                    extras: [], transferTrip: 'one', applyCredit: false,
                    arrivalWindow: '', rulesAgreed: false,
                    depositChoice: 'nothing', payMethod: 'cash', payAmount: '',
                    priceErr: {}, priceBusy: -1, overrideReason: '', overrideNote: '', paySuggested: '',
                    cal: { y: 2026, m: 0, start: BOOT.checkIn, end: BOOT.checkOut, hover: '' },
                    init() {
                        try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}   // a draft from before 2026-10-04 lived here — gone
                        this.calInit();
                        if (R) { this.applyRestore(R); } else { this.restoreDraft(); }
                        if (BOOT.searched) { this.guestMode = 'existing'; }
                        KEPT.forEach((k) => this.$watch(k, () => { this.persist(); this.recheck(); }));
                        ['newFirst', 'newLast', 'existingGuestId', 'overrideReason', 'overrideNote', 'payAmount', 'depositChoice'].forEach((k) => this.$watch(k, () => this.recheck()));
                        // ⚠️ A deposit or "full" amount follows the total while nobody has typed over it — it used to keep the
                        //    figure of the moment it was chosen, so "Full" could record less than the folio after a change.
                        this.$watch('totalMinor()', () => { if (this.depositChoice !== 'nothing' && this.payAmount === this.paySuggested) this.setDeposit(this.depositChoice); });
                        this.$watch('step', () => { this.contactWarned = false; this.draftRestored = false; this.$nextTick(() => this.$root.scrollIntoView({ behavior: this.calm() ? 'auto' : 'smooth', block: 'start' })); });
                    },
                    calm() { return !! (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches); },

                    // ── what each step still needs (the server re-checks all of it) ──────────────────────────────
                    problem(n) {
                        if (n === 1) {
                            if (! BOOT.datesValid) return 'Pick the check-in and check-out dates first.';
                            if (! this.unitId) return 'Pick a unit for these dates to continue.';
                        }
                        if (n === 2) {
                            if (this.guestMode === 'existing') return this.existingGuestId ? '' : 'Choose the guest from the list — or switch to New guest.';
                            if (! this.newFirst.trim() && ! this.newLast.trim()) return 'Enter the guest’s first or last name.';
                            const email = this.$refs.email;
                            // the server's own test (FILTER_VALIDATE_EMAIL) is stricter than the browser's: "jo@hotel" passes one, not the other
                            if (email && email.value.trim() !== '' && ! EMAIL.test(email.value.trim())) return 'That email address doesn’t look right — check it, or leave it empty.';
                            const country = this.$refs.country;
                            if (country && country.value.trim() !== '' && ! /^[A-Za-z]{2}$/.test(country.value.trim())) return 'Use a 2-letter country code, like PT or GB — or leave it empty.';
                        }
                        if (n === 7 && this.overrideProblem()) return 'Say why in the note — it’s needed when you choose Other.';
                        if (n === 7 && BOOT.canPay && this.depositChoice !== 'nothing') {
                            if (/^\s*\d+,\d{1,2}\s*$/.test(this.payAmount)) return 'Use a dot for cents, e.g. 150.50 — a comma reads as thousands.';
                            if (this.toMinor(this.payAmount) === null) return 'Enter the amount paid now, e.g. 150 or 150.00.';
                        }
                        return '';
                    },
                    // ⚠️ The message is STATE, set when a step refuses — not a live expression: an email box is not reactive,
                    //    so a computed message never re-rendered and a refused "Next" said nothing. recheck() clears or
                    //    updates it as the operator fixes the field.
                    block(k) { this.step = k; this.blockedStep = k; this.blocked = this.problem(k); },
                    recheck() { if (this.blockedStep !== 0) { this.blocked = this.problem(this.blockedStep); if (this.blocked === '') this.blockedStep = 0; } },
                    overrideProblem() { return this.overrideReason === 'other' && this.overrideNote.trim() === ''; },
                    goto(n) {
                        if (n < 1 || n > 7) return;
                        if (n > this.step) {
                            for (let k = this.step; k < n; k++) {
                                if (this.problem(k) !== '') { this.block(k); return; }
                            }
                        }
                        this.blocked = ''; this.blockedStep = 0;
                        this.step = n;
                    },
                    next() { this.goto(this.step + 1); },
                    prev() { this.goto(this.step - 1); },
                    submit(e) {
                        // ⚠️ Enter in a text field submits a form. Before step 7 it means "next", never "book now".
                        if (this.step < 7) { e.preventDefault(); this.next(); return; }
                        for (let k = 1; k <= 7; k++) {
                            if (this.problem(k) !== '') { e.preventDefault(); this.block(k); return; }
                        }
                        // #28 — after a refusal the new guest's email and phone come back empty; say so once before booking without them.
                        if (BOOT.contactCleared && this.guestMode === 'new' && ! this.contactWarned
                            && this.$refs.email.value.trim() === '' && this.$refs.phone.value.trim() === '') {
                            e.preventDefault(); this.contactWarned = true; return;
                        }
                        this.persist();
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

                    // ── money — the server's own rules: MoneyInput's format, FolioService's tax and fee formulas, over the
                    //    taxes and fees StayEstimator says the folio posts (filtered server-side) ─────────────────────
                    symbol() { const r = rt(this.roomTypeId); return r ? r.symbol : BOOT.symbol; },
                    fmt(m) {
                        const abs = (Math.abs(m) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        return (m < 0 ? '−' : '') + this.symbol() + abs;
                    },
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
                    unitLabel() { const r = rt(this.roomTypeId); return r && this.unitId && r.units[this.unitId] ? r.units[this.unitId].code + ' · ' + r.name : 'Not chosen yet'; },
                    guestLabel() {
                        if (this.guestMode === 'existing') return this.existingGuestName || 'Not chosen yet';
                        return (this.newFirst + ' ' + this.newLast).trim() || 'Not added yet';
                    },
                    extrasLabel() { const n = this.chosenExtras().length; return n === 0 ? 'None' : n + (n === 1 ? ' extra' : ' extras'); },

                    // ── the two-month calendar — dates ride a GET (the availability read is server-side, #20) ──
                    calInit() { const base = this.cal.start ? new Date(this.cal.start + 'T00:00') : new Date(); this.cal.y = base.getFullYear(); this.cal.m = base.getMonth(); },
                    calYM(off) { const d = new Date(this.cal.y, this.cal.m + off, 1); return { y: d.getFullYear(), m: d.getMonth() }; },
                    calLabel(off) { const c = this.calYM(off); return new Date(c.y, c.m, 1).toLocaleDateString('en-GB', { month: 'long', year: 'numeric' }); },
                    calGrid(off) {
                        const c = this.calYM(off);
                        const lead = (new Date(c.y, c.m, 1).getDay() + 6) % 7;   // Monday first
                        const dim = new Date(c.y, c.m + 1, 0).getDate();
                        const today = new Date(); today.setHours(0, 0, 0, 0);
                        const cells = [];
                        for (let i = 0; i < lead; i++) cells.push({ date: '', day: '' });
                        for (let dd = 1; dd <= dim; dd++) {
                            const dt = new Date(c.y, c.m, dd);
                            const iso = c.y + '-' + String(c.m + 1).padStart(2, '0') + '-' + String(dd).padStart(2, '0');
                            cells.push({ date: iso, day: dd, past: dt < today, weekend: dt.getDay() === 0 || dt.getDay() === 6 });
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
                    calAria(iso) {
                        const s = this.calState(iso);
                        const label = new Date(iso + 'T00:00').toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                        return label + (s === 'start' ? ', check-in' : s === 'end' ? ', check-out' : '');
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
                        const d = { v: 3, propertyId: BOOT.propertyId, checkIn: BOOT.checkIn, checkOut: BOOT.checkOut, savedAt: Date.now() };
                        KEPT.forEach((k) => { d[k] = this[k]; });
                        return d;
                    },
                    persist() { try { sessionStorage.setItem(DRAFT_KEY, JSON.stringify(this.snapshot())); } catch (e) { /* storage blocked — the wizard still works, it just forgets */ } },
                    clearDraft() { try { sessionStorage.removeItem(DRAFT_KEY); } catch (e) {} },
                    restoreDraft() {
                        let d = null;
                        try { d = JSON.parse(sessionStorage.getItem(DRAFT_KEY) || 'null'); } catch (e) { d = null; }
                        if (! d || d.v !== 3 || d.propertyId !== BOOT.propertyId || ! d.savedAt || Date.now() - d.savedAt > DRAFT_MAX_AGE) return;
                        // what step 1 shows, whatever the dates — the calendar reloads the page for every new stay
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
                        this.step = Math.min(7, Math.max(1, parseInt(d.step, 10) || 1));
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
                        this.step = s.step;
                    },
                    startOver() { this.clearDraft(); window.location = BOOT.createUrl; },
                };
            }
        </script>
    @endif
</div>
@endif
