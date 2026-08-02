{{-- Operator NEW RESERVATION — the FULL 7-step wizard (#90 exact-match), on the certified createBooking path.
     Step 1's availability is a REAL server read (GET → UnitAvailabilityService — only genuinely-free, priced
     units appear; createBooking re-checks under a row lock). Steps 2–7 collect the guest, extras, transfer,
     house-rules and test-mode payment, submitted as ONE POST → ReservationCreateController, which posts REAL
     reconciling folio lines (room + extras + transfer − credit + tax, #17). The Booking-summary panel is a
     client ESTIMATE from the REAL prices; the authoritative total is the folio on the reservation.
     GUARDS: honest availability · guest NAME-only search (#28) · NO raw PAN (card = honest "Soon", #45).
     Draft-restore = client localStorage (matches the wireframe; cleared on confirm — no server-persistence claim).
     RAW-BLADE RULE: Alpine uses x-on:/x-model/x-bind (no `@click`). --}}
@php $__nrReadOnly = app(\App\Support\Operator\OperatorWorkspace::class)->isReadOnly(); @endphp
{{-- GATE 1 · Part B — the STANDALONE wizard chrome (the prototype is a centered gradient/glass wizard, NOT
     the app shell). The operator LAYOUT still supplies the warm gradient + ambient orbs + tokens; we drop the
     shell (sidebar/nav) for a focused create flow with a close-X → the reservations rack. Machinery preserved:
     read-only (#84) is shown here AND enforced server-side; property context (#38) is resolved by middleware
     and shown as the sublabel (cross-property writes are blocked in the controller); the route stays gated
     (#20/#26/#30). RAW-BLADE RULE: Alpine uses x-on:/x-model/x-bind (no `@click`). --}}
<div class="vy-nr-standalone" @if ($hasProperty) x-data="newReservationWizard()" x-init="init()" @endif>
    <div class="vy-nr-topbar vy-fade">
        <div class="vy-nr-brand">
            <span class="vy-nr-logo">V</span>
            <span class="vy-nr-brand-id">
                <span class="vy-nr-brand-title">New reservation</span>
                <span class="vy-nr-brand-sub">{{ $subtitle !== '' ? $subtitle : 'Direct entry' }} · direct entry</span>
            </span>
        </div>
        <div class="vy-nr-topright">
            @if ($hasProperty)
                <span class="vy-nr-draftpill" x-show="draftRestored" x-cloak>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                    Draft restored
                </span>
                <span class="vy-nr-stepcount" x-text="'Step ' + step + ' of 7'"></span>
            @endif
            <a class="vy-nr-close" href="{{ route('app.reservations') }}" title="Close" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
            </a>
        </div>
    </div>

    @if (session('new_reservation_notice'))
        <div class="vy-flash is-err vy-fade">{{ session('new_reservation_notice') }}</div>
    @endif

    @if ($__nrReadOnly)
        {{-- #84 — read-only workspace: creation is OFF (also enforced server-side at createBooking). --}}
        <div class="vy-nr-ro vy-fade" role="status">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            <span>Your workspace is paused — new bookings are off until you resume from Billing.</span>
        </div>
    @endif

    @unless ($hasProperty)
        <div class="vy-card vy-fade" style="text-align: center; padding: 40px 24px;">
            <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">No property configured</strong>
            <span style="font-size: 13px; color: var(--vy-muted);">Add a property before creating reservations.</span>
        </div>
    @else
        <div class="vy-nr">
                <input type="hidden" x-ref="checkIn" value="{{ $checkIn }}">
                <input type="hidden" x-ref="checkOut" value="{{ $checkOut }}">

                {{-- Draft-restored banner --}}
                <div class="vy-card vy-fade" x-show="draftRestored" x-cloak style="border-left: 3px solid var(--vy-accent); margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px;">Picked up where you left off — your draft was restored.</span>
                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="startOver()">Start over</button>
                </div>

                {{-- Progress stepper — circular numbered nodes joined by connector bars, CLICKABLE to jump
                     to any step (prototype-exact); gradient fill + check on done, ring on active. --}}
                @php $__nrSteps = ['When & unit', 'Who’s staying', 'Rate & extras', 'Arrival & transfer', 'House rules', 'Review', 'Payment']; @endphp
                <div class="vy-nr-stepper vy-fade">
                    @foreach ($__nrSteps as $i => $label)
                        @php $n = $i + 1; @endphp
                        <div class="vy-nr-node" x-bind:class="step === {{ $n }} ? 'is-on' : (step > {{ $n }} ? 'is-done' : '')">
                            <div class="vy-nr-node-row">
                                <span class="vy-nr-bar" @if ($i === 0) style="visibility:hidden" @endif></span>
                                <button type="button" class="vy-nr-circle" x-on:click="step = {{ $n }}" aria-label="Go to {{ $label }}">
                                    <span class="vy-nr-circle-num">{{ $n }}</span>
                                    <svg class="vy-nr-circle-check" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                                </button>
                                <span class="vy-nr-bar" @if ($i === count($__nrSteps) - 1) style="visibility:hidden" @endif></span>
                            </div>
                            <span class="vy-nr-node-label">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="vy-nr-body vy-fade">
                    {{-- ══ MAIN COLUMN ══ --}}
                    <form method="POST" action="{{ route('app.reservations.create') }}" class="vy-nr-main" x-ref="form">
                        @csrf
                        <input type="hidden" name="check_in" value="{{ $checkIn }}">
                        <input type="hidden" name="check_out" value="{{ $checkOut }}">
                        <input type="hidden" name="room_type_id" x-model="roomTypeId">
                        <input type="hidden" name="unit_id" x-model="unitId">
                        <input type="hidden" name="rate_plan_id" x-model="ratePlanId">
                        <input type="hidden" name="transfer_trip" x-model="transferTrip">
                        <input type="hidden" name="apply_credit" x-bind:value="applyCredit ? '1' : ''">

                        {{-- STEP 1 — When & which unit --}}
                        <section class="vy-card" x-show="step === 1">
                            <h2 class="vy-card-title">When &amp; which unit</h2>
                            <p class="vy-nr-hint">Pick the dates and party size — availability updates as you go.</p>

                            {{-- Dual-month drag-range calendar. The dates are a REAL server read: "Check availability"
                                 reloads with the picked range so the availability + the POST dates update together
                                 (same certified flow as before — the calendar just replaces the native inputs). --}}
                            <div class="vy-cal" style="margin-top: 12px;">
                                <div class="vy-cal-head">
                                    <button type="button" class="vy-cal-nav" x-on:click="calPrev()" aria-label="Previous month">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                                    </button>
                                    <div class="vy-cal-labels"><span x-text="calLabel(0)"></span><span x-text="calLabel(1)"></span></div>
                                    <button type="button" class="vy-cal-nav" x-on:click="calNext()" aria-label="Next month">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                                    </button>
                                </div>
                                <div class="vy-cal-grids">
                                    <template x-for="off in [0, 1]" :key="off">
                                        <div class="vy-cal-month">
                                            <div class="vy-cal-dow"><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span></div>
                                            <div class="vy-cal-days" x-on:mouseleave="cal.hover = ''">
                                                <template x-for="(d, i) in calGrid(off)" :key="off + '-' + i">
                                                    <button type="button" class="vy-cal-day"
                                                            x-bind:class="{ 'is-pad': !d.date, 'is-past': d.past, 'is-weekend': d.weekend, 'is-start': calState(d.date) === 'start', 'is-end': calState(d.date) === 'end', 'is-in': calState(d.date) === 'in' }"
                                                            x-bind:disabled="!d.date || d.past"
                                                            x-on:click="calClick(d.date)" x-on:mouseenter="if (d.date) cal.hover = d.date"
                                                            x-text="d.day"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="vy-cal-foot">
                                    <span class="vy-cal-sel" x-text="calSelLabel()"></span>
                                    <span style="flex: 1;"></span>
                                    <button type="button" class="vy-btn vy-btn--ghost" x-show="cal.start" x-on:click="calClear()">Clear</button>
                                    <button type="button" class="vy-btn vy-btn--accent" x-on:click="calApply()" x-bind:disabled="!cal.start || !cal.end" x-bind:style="(!cal.start || !cal.end) ? 'opacity:.5;pointer-events:none;' : ''">Check availability</button>
                                </div>
                            </div>
                            @if ($datesValid)
                                <div class="vy-nr-daterow">{{ \Carbon\CarbonImmutable::parse($checkIn)->format('j M Y') }} → {{ \Carbon\CarbonImmutable::parse($checkOut)->format('j M Y') }} · {{ $nights }} {{ $nights === 1 ? 'night' : 'nights' }}</div>
                            @endif

                            <div class="vyn-grid3" style="margin-top: 14px;">
                                <div class="vy-field" style="margin: 0;"><label class="vy-field-label">Adults</label>
                                    <div class="vy-nr-counter">
                                        <button type="button" x-on:click="adults = Math.max(1, adults - 1)" x-bind:disabled="adults <= 1" aria-label="Fewer adults">−</button>
                                        <span x-text="adults"></span>
                                        <button type="button" x-on:click="adults = Math.min(30, adults + 1)" aria-label="More adults">+</button>
                                    </div>
                                    <input type="hidden" name="adults" x-model.number="adults">
                                </div>
                                <div class="vy-field" style="margin: 0;"><label class="vy-field-label">Children</label>
                                    <div class="vy-nr-counter">
                                        <button type="button" x-on:click="children = Math.max(0, children - 1)" x-bind:disabled="children <= 0" aria-label="Fewer children">−</button>
                                        <span x-text="children"></span>
                                        <button type="button" x-on:click="children = Math.min(30, children + 1)" aria-label="More children">+</button>
                                    </div>
                                    <input type="hidden" name="children" x-model.number="children">
                                </div>
                            </div>

                            <div class="vy-nr-sub" style="margin-top: 16px;">Unit · availability for these dates</div>
                            @unless ($datesValid)
                                <div class="vyn-empty">Choose dates above to see what’s available.</div>
                            @else
                                @forelse ($roomTypes as $rt)
                                    <div class="vyn-rt">
                                        <div class="vyn-rt-head"><span class="vyn-rt-name">{{ $rt['name'] }}</span>@if ($rt['priced'])<span class="vyn-rt-price">{{ $rt['price'] }} · stay</span>@endif</div>
                                        @if ($rt['priced'])
                                            <div class="vyn-units">
                                                @foreach ($rt['units'] as $unit)
                                                    <label class="vyn-unit" x-bind:class="unitId === '{{ $unit['id'] }}' ? 'is-on' : ''">
                                                        <input type="radio" name="unit_pick" style="display:none;" x-on:change="pickUnit('{{ $rt['room_type_id'] }}', '{{ $unit['id'] }}', {{ (int) preg_replace('/[^0-9]/', '', (string) $rt['price']) }})">
                                                        {{ $unit['code'] }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="vyn-unpriced">{{ $rt['unit_count'] }} free — no rate set. <a href="{{ route('app.rates', ['from' => $checkIn]) }}" style="color: var(--vy-accent);">Set a rate first →</a></div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="vyn-empty">No units are free for those dates. Try a different range.</div>
                                @endforelse
                            @endunless
                        </section>

                        {{-- STEP 2 — Who's staying --}}
                        <section class="vy-card" x-show="step === 2" x-cloak>
                            <h2 class="vy-card-title">Who’s staying</h2>
                            <p class="vy-nr-hint">Look up a returning guest, or capture a new profile.</p>
                            <div class="vyn-tabs" style="margin-top: 10px;">
                                <button type="button" class="vyn-tab" x-bind:class="guestMode === 'existing' ? 'is-on' : ''" x-on:click="guestMode = 'existing'">Existing guest</button>
                                <button type="button" class="vyn-tab" x-bind:class="guestMode === 'new' ? 'is-on' : ''" x-on:click="guestMode = 'new'">New guest</button>
                            </div>
                            <div x-show="guestMode === 'existing'">
                                {{-- Guest name search — a GET NAVIGATION (not a nested <form>: a form-in-form is
                                     invalid HTML and would close the outer POST form early). Enter or the button
                                     reloads with ?q=; server-side search is NAME-only (#28). --}}
                                @php $__nrSearchBase = route('app.reservations.create').'?check_in='.urlencode((string) $checkIn).'&check_out='.urlencode((string) $checkOut).'&q='; @endphp
                                <div style="margin: 12px 0; display: flex; gap: 8px;">
                                    <input type="text" id="nr-guest-q" value="{{ $query }}" class="vy-input" placeholder="Search a returning guest by name" autocomplete="off"
                                           x-on:keydown.enter.prevent="window.location = '{{ $__nrSearchBase }}' + encodeURIComponent($event.target.value)">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="window.location = '{{ $__nrSearchBase }}' + encodeURIComponent(document.getElementById('nr-guest-q').value)">Search</button>
                                </div>
                                @if ($query === '')
                                    <div class="vyn-empty">Search a guest by name, then pick them here.</div>
                                @elseif ($guestMatches === [])
                                    <div class="vyn-empty">No guests match “{{ $query }}”. Switch to New guest to add them.</div>
                                @else
                                    @foreach ($guestMatches as $match)
                                        <label class="vyn-guest">
                                            <input type="radio" name="existing_guest_id" value="{{ $match['id'] }}" x-bind:disabled="guestMode !== 'existing'" x-on:change="existingGuestName = '{{ $match['name'] }}'">
                                            <span class="vyn-guest-av">{{ $match['initials'] }}</span>
                                            <span style="font-size: 13.5px;">{{ $match['name'] }}</span>
                                            <span style="margin-left: auto; font-size: 10px; font-weight: 600; color: #B4400F; background: rgba(180,64,15,.12); padding: 2px 8px; border-radius: 20px;">Returning</span>
                                        </label>
                                    @endforeach
                                @endif
                            </div>
                            <div x-show="guestMode === 'new'" class="vyn-grid3" style="margin-top: 12px;">
                                <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="gf">Full name — first</label><input id="gf" type="text" name="guest_first_name" class="vy-input" x-bind:disabled="guestMode !== 'new'" x-model="newFirst" autocomplete="off"></div>
                                <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="gl">Last name</label><input id="gl" type="text" name="guest_last_name" class="vy-input" x-bind:disabled="guestMode !== 'new'" x-model="newLast" autocomplete="off"></div>
                                <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="ge">Email · optional</label><input id="ge" type="email" name="guest_email" class="vy-input" x-bind:disabled="guestMode !== 'new'" autocomplete="off"></div>
                                <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="gp">Phone · optional</label><input id="gp" type="text" name="guest_phone" class="vy-input" x-bind:disabled="guestMode !== 'new'" autocomplete="off"></div>
                                <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="gc">Country · optional</label><input id="gc" type="text" name="guest_country" class="vy-input" maxlength="2" placeholder="PT" x-bind:disabled="guestMode !== 'new'" x-on:input="$event.target.value = $event.target.value.toUpperCase()" autocomplete="off"><span class="vy-field-hint">2-letter code (ISO), e.g. PT · ES · GB</span></div>
                            </div>
                            <div x-show="guestMode === 'new'" class="vy-field" style="margin-top: 12px;">
                                <label class="vy-field-label" for="gnote">Guest note · optional</label>
                                <textarea id="gnote" name="guest_note" class="vy-input" rows="2" placeholder="Preferences, allergies, access needs…" x-bind:disabled="guestMode !== 'new'" style="resize: vertical;"></textarea>
                            </div>
                            <div x-show="guestMode === 'new'" style="margin-top: 12px; display: flex; gap: 22px;">
                                <label class="vyn-check"><input type="checkbox" name="guest_vip" value="1" x-bind:disabled="guestMode !== 'new'"> Mark as VIP</label>
                                <label class="vyn-check"><input type="checkbox" name="guest_marketing_consent" value="1" x-bind:disabled="guestMode !== 'new'"> Marketing consent · guest agreed to offers &amp; news</label>
                            </div>
                        </section>

                        {{-- STEP 3 — Rate & extras --}}
                        <section class="vy-card" x-show="step === 3" x-cloak>
                            <h2 class="vy-card-title">Rate &amp; extras</h2>
                            <p class="vy-nr-hint">Pick the rate plan, then add anything the guest wants — each is a real line on the folio.</p>

                            {{-- Rate-plan selector — REAL active plans of the chosen room type (server-priced over the
                                 stay). >1 → a selector; exactly 1 → shown honestly. No fabricated discount plans (#39). --}}
                            <div x-show="plansFor().length > 1" style="margin-top: 12px;">
                                <div class="vy-nr-sub">Rate plan</div>
                                <div class="vy-nr-plans">
                                    <template x-for="p in plansFor()" :key="p.id">
                                        <button type="button" class="vy-nr-plan" x-bind:class="ratePlanId === p.id ? 'is-on' : ''" x-on:click="pickPlan(p.id, p.price_minor)">
                                            <span class="vy-nr-plan-name" x-text="p.name"></span>
                                            <span class="vy-nr-plan-price" x-text="fmt(p.price_minor) + ' · stay'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div x-show="roomTypeId && plansFor().length === 1" class="vy-nr-hint" style="margin-top: 12px;">Rate plan: <strong x-text="(plansFor()[0] &amp;&amp; plansFor()[0].name) || '—'"></strong> · <span x-text="fmt(roomMinor)"></span> for the stay.</div>

                            <div class="vy-nr-sub" style="margin-top: 14px;">Extras</div>
                            @forelse ($extras as $ex)
                                <label class="vyn-extra">
                                    <input type="checkbox" name="extras[]" value="{{ $ex['id'] }}" x-model="extras">
                                    <span style="flex: 1;"><strong>{{ $ex['name'] }}</strong><span class="vy-muted"> · {{ $ex['basis_label'] }}</span></span>
                                    <span class="vy-u-code">{{ \App\Support\Money\MoneyFormatter::display($ex['price_minor'], $ex['currency']) }}</span>
                                </label>
                                @if ($ex['is_transfer'])
                                    <div x-show="extras.includes('{{ $ex['id'] }}')" style="margin: -4px 0 8px 30px;">
                                        <label class="vyn-check"><input type="radio" name="_trip" value="one" x-model="transferTrip" x-bind:checked="transferTrip === 'one'"> One way</label>
                                        <label class="vyn-check" style="margin-left: 16px;"><input type="radio" name="_trip" value="return" x-model="transferTrip"> Return</label>
                                    </div>
                                @endif
                            @empty
                                <div class="vyn-empty">No extras configured for this property.</div>
                            @endforelse
                            <label class="vyn-extra" style="margin-top: 6px;" x-show="guestMode === 'existing'">
                                <input type="checkbox" x-model="applyCredit">
                                <span style="flex: 1;"><strong style="color: #1F6E4B;">Returning-guest credit</strong><span class="vy-muted"> · applies the guest’s available wallet balance</span></span>
                            </label>
                        </section>

                        {{-- STEP 4 — Arrival & transfer --}}
                        <section class="vy-card" x-show="step === 4" x-cloak>
                            <h2 class="vy-card-title">Arrival &amp; transfer</h2>
                            <p class="vy-nr-hint">When is the guest arriving? A pickup is added as a real transfer line in step 3.</p>
                            <div class="vy-field" style="margin-top: 12px;"><label class="vy-field-label" for="aw">Estimated arrival</label>
                                <input id="aw" type="text" name="arrival_window" class="vy-input" placeholder="e.g. 3–6 PM" x-model="arrivalWindow">
                            </div>
                            <div class="vy-nr-doorcode">Guest making their own way? We’ll include directions, parking and the <strong>door code</strong> in the confirmation — the code is guest-only and released in-window, never shown here.</div>
                        </section>

                        {{-- STEP 5 — House rules --}}
                        <section class="vy-card" x-show="step === 5" x-cloak>
                            <h2 class="vy-card-title">House rules</h2>
                            <p class="vy-nr-hint">How the property runs — these go out with the guest’s confirmation.</p>
                            <div class="vyn-rules" style="margin-top: 12px;">
                                <div><span>Check-in</span><strong>From {{ $houseRules['check_in'] ?? '15:00' }}</strong></div>
                                <div><span>Check-out</span><strong>By {{ $houseRules['check_out'] ?? '11:00' }}</strong></div>
                                <div><span>Pets</span><strong>{{ $houseRules['pet'] ?? 'Not permitted' }}</strong></div>
                                <div><span>Smoking</span><strong>{{ $houseRules['smoking'] ?? 'Not permitted' }}</strong></div>
                                <div><span>Minimum stay</span><strong>{{ $houseRules['min_stay'] ?? '—' }}</strong></div>
                                <div><span>Cancellation</span><strong>{{ $houseRules['cancellation'] ?? 'As per booking terms' }}</strong></div>
                            </div>
                            <label class="vyn-check" style="margin-top: 14px;"><input type="checkbox" name="rules_agreed" value="1" x-model="rulesAgreed"> Guest accepts the house rules <span class="vy-muted">· sent with the confirmation email</span></label>
                        </section>

                        {{-- STEP 6 — Review & confirm --}}
                        <section class="vy-card" x-show="step === 6" x-cloak>
                            <h2 class="vy-card-title">Review &amp; confirm</h2>
                            <p class="vy-nr-hint">One last look before you take payment.</p>
                            <div class="vyn-rules" style="margin-top: 12px;">
                                <div><span>Guest</span><strong x-text="guestLabel()"></strong></div>
                                <div><span>Stay</span><strong>{{ $datesValid ? \Carbon\CarbonImmutable::parse($checkIn)->format('j M').' – '.\Carbon\CarbonImmutable::parse($checkOut)->format('j M') : '—' }} · <span x-text="adults + children"></span> guests</strong></div>
                                <div><span>Unit</span><strong x-text="unitId ? unitCode : 'Pick a unit'"></strong></div>
                                <div><span>Extras</span><strong x-text="extras.length ? extras.length + ' selected' : 'None'"></strong></div>
                            </div>
                            <div class="vy-field" style="margin-top: 12px;"><label class="vy-field-label" for="src">Booking source</label>
                                <select id="src" name="source" class="vy-input">@foreach ($sources as $src)<option value="{{ $src['value'] }}">{{ $src['label'] }}</option>@endforeach</select>
                            </div>
                            <div class="vy-field" style="margin-top: 10px;"><label class="vy-field-label" for="note">Note for the team · optional</label><textarea id="note" name="team_note" rows="2" class="vy-input"></textarea></div>
                            {{-- Hidden "0" fallback so UNCHECKING genuinely suppresses this booking's confirmation
                                 (an unchecked checkbox submits nothing); the checked "1" overrides it. --}}
                            <input type="hidden" name="send_confirmation" value="0">
                            <label class="vyn-check" style="margin-top: 10px;"><input type="checkbox" name="send_confirmation" value="1" checked> Send confirmation email <span class="vy-muted">· guest gets the booking details right away</span></label>
                        </section>

                        {{-- STEP 7 — Payment (test-mode) --}}
                        <section class="vy-card" x-show="step === 7" x-cloak>
                            <h2 class="vy-card-title">Payment</h2>
                            <p class="vy-nr-hint">Take a deposit or the full amount now, or collect at check-in.</p>
                            @if ($canPay)
                                {{-- Deposit split: Nothing / Deposit (30%) / Full — a real payment-timing choice that
                                     drives the REAL manual payment. Card capture stays deferred (SAQ A). The effective
                                     payment_method is ONE hidden field (no duplicate names, the B.1 lesson). --}}
                                <div class="vy-nr-sub" style="margin-top: 12px;">How much to collect now?</div>
                                <input type="hidden" name="payment_method" x-bind:value="depositChoice === 'nothing' ? 'later' : payMethod">
                                <div class="vy-nr-deposit">
                                    <button type="button" class="vy-nr-dep" x-bind:class="depositChoice === 'nothing' ? 'is-on' : ''" x-on:click="setDeposit('nothing')"><strong>Nothing</strong><span>collect at check-in</span></button>
                                    <button type="button" class="vy-nr-dep" x-bind:class="depositChoice === 'deposit' ? 'is-on' : ''" x-on:click="setDeposit('deposit')"><strong>Deposit · 30%</strong><span x-text="fmt(Math.round(totalMinor() * 0.3))"></span></button>
                                    <button type="button" class="vy-nr-dep" x-bind:class="depositChoice === 'full' ? 'is-on' : ''" x-on:click="setDeposit('full')"><strong>Full</strong><span x-text="fmt(totalMinor())"></span></button>
                                </div>
                                <div x-show="depositChoice !== 'nothing'" x-cloak>
                                    <div class="vy-field" style="margin-top: 12px;"><label class="vy-field-label" for="pm">Method</label>
                                        <select id="pm" class="vy-input" x-model="payMethod">
                                            <option value="cash">Cash</option>
                                            <option value="bank">Bank transfer</option>
                                            <option value="card" disabled>Card — coming soon</option>
                                        </select>
                                    </div>
                                    <div class="vy-field" style="margin-top: 10px;"><label class="vy-field-label" for="pa">Amount charged now</label>
                                        <input id="pa" type="text" name="payment_amount" class="vy-input" inputmode="decimal" x-model="payAmount">
                                    </div>
                                    <div class="vy-nr-doorcode">Balance <strong x-text="fmt(Math.max(0, totalMinor() - Math.round((parseFloat(payAmount) || 0) * 100)))"></strong> due at check-in. Card capture arrives with secure Stripe checkout (test-mode until go-live) — <strong>no card number is entered or stored here</strong>.</div>
                                </div>
                            @else
                                <div class="vyn-empty">Payment is taken later from the reservation’s folio.</div>
                            @endif
                        </section>

                        {{-- Nav --}}
                        <div class="vy-nr-nav">
                            <button type="button" class="vy-btn vy-btn--ghost" x-show="step > 1" x-on:click="prev()">Back</button>
                            <span style="flex: 1;"></span>
                            <button type="button" class="vy-btn vy-btn--accent" x-show="step < 7" x-on:click="next()" x-bind:disabled="step === 1 && !unitId" x-bind:style="step === 1 && !unitId ? 'opacity:.5;pointer-events:none;' : ''">Next</button>
                            <button type="submit" class="vy-btn vy-btn--accent" x-show="step === 7" x-on:click="clearDraft()" x-bind:disabled="!unitId">Confirm reservation</button>
                        </div>
                    </form>

                    {{-- ══ BOOKING SUMMARY (client estimate from REAL prices) ══ --}}
                    <aside class="vy-card vy-nr-summary">
                        <h3 class="vy-card-title" style="font-size: 14px;">Booking summary</h3>
                        <div class="vy-nr-sumline"><span>Accommodation</span><span class="vy-u-code" x-text="fmt(roomMinor)"></span></div>
                        <div class="vy-nr-sumline" x-show="extrasMinor() > 0"><span>Extras</span><span class="vy-u-code" x-text="fmt(extrasMinor())"></span></div>
                        <div class="vy-nr-sumline" x-show="applyCredit && guestMode === 'existing'"><span>Returning-guest credit</span><span class="vy-u-code" style="color:#1F8A5B;">applied on confirm</span></div>
                        <div class="vy-nr-sumline" x-show="taxMinor() > 0"><span>Taxes</span><span class="vy-u-code" x-text="fmt(taxMinor())"></span></div>
                        <div class="vy-nr-sumline vy-nr-sumtotal"><span>Estimated total</span><span class="vy-u-code" x-text="fmt(totalMinor())"></span></div>
                        <p class="vy-nr-esthint">An estimate — the exact total (with any wallet credit) is on the reservation folio once created.</p>
                    </aside>
                </div>
            </div>
        @endunless

    <script>
        function newReservationWizard() {
            const EXTRAS = @js(collect($extras)->map(fn ($e) => ['id' => $e['id'], 'price' => $e['price_minor'], 'taxable' => $e['taxable'], 'basis' => $e['basis']])->all());
            const TAXES = @js(collect($taxes)->map(fn ($t) => ['mode' => $t['mode'], 'rate' => $t['rate'], 'amount' => $t['amount_minor']])->all());
            const NIGHTS = @js($nights);
            // room_type_id → its REAL active/priced rate plans (default first) — drives the plan selector.
            const PLANS = @js(collect($roomTypes)->mapWithKeys(fn ($rt) => [$rt['room_type_id'] => ['default' => $rt['rate_plan_id'], 'list' => $rt['plans']]])->all());
            return {
                step: 1,
                roomTypeId: '', unitId: '', unitCode: '', roomMinor: 0, ratePlanId: '',
                adults: 2, children: 0,
                guestMode: @js($guestMatches !== [] ? 'existing' : 'new'),
                existingGuestName: '', newFirst: '', newLast: '',
                extras: [], transferTrip: 'one', applyCredit: false,
                arrivalWindow: '', rulesAgreed: false, payMethod: 'later',
                depositChoice: 'nothing', payAmount: '',
                draftRestored: false,
                cal: { y: 2026, m: 0, start: @js((string) $checkIn), end: @js((string) $checkOut), hover: '' },
                init() { this.calInit(); this.restore(); this.$watch('step', () => this.persist()); },
                // ── Dual-month drag-range calendar ──────────────────────────────────────────────────────────
                calInit() { const base = this.cal.start ? new Date(this.cal.start + 'T00:00') : new Date(); this.cal.y = base.getFullYear(); this.cal.m = base.getMonth(); },
                calYM(off) { const d = new Date(this.cal.y, this.cal.m + off, 1); return { y: d.getFullYear(), m: d.getMonth() }; },
                calLabel(off) { const c = this.calYM(off); return new Date(c.y, c.m, 1).toLocaleDateString('en-GB', { month: 'long', year: 'numeric' }); },
                calGrid(off) {
                    const c = this.calYM(off);
                    const lead = (new Date(c.y, c.m, 1).getDay() + 6) % 7;   // Monday-first
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
                    if (!iso) return;
                    if (!this.cal.start || this.cal.end || iso <= this.cal.start) { this.cal.start = iso; this.cal.end = ''; }
                    else { this.cal.end = iso; }
                },
                calState(iso) {
                    if (!iso) return '';
                    if (iso === this.cal.start) return 'start';
                    if (iso === this.cal.end) return 'end';
                    const e = this.cal.end || (this.cal.start ? this.cal.hover : '');
                    if (this.cal.start && e && iso > this.cal.start && iso < e) return 'in';
                    return '';
                },
                calSelLabel() {
                    if (!this.cal.start) return 'Pick your check-in date';
                    if (!this.cal.end) return 'Now pick check-out';
                    const nights = Math.round((new Date(this.cal.end) - new Date(this.cal.start)) / 86400000);
                    return this.calFmt(this.cal.start) + ' → ' + this.calFmt(this.cal.end) + ' · ' + nights + (nights === 1 ? ' night' : ' nights');
                },
                calFmt(iso) { return new Date(iso + 'T00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }); },
                calPrev() { const d = new Date(this.cal.y, this.cal.m - 1, 1); this.cal.y = d.getFullYear(); this.cal.m = d.getMonth(); },
                calNext() { const d = new Date(this.cal.y, this.cal.m + 1, 1); this.cal.y = d.getFullYear(); this.cal.m = d.getMonth(); },
                calClear() { this.cal.start = ''; this.cal.end = ''; },
                calApply() { if (this.cal.start && this.cal.end) window.location = '{{ route('app.reservations.create') }}?check_in=' + this.cal.start + '&check_out=' + this.cal.end; },
                pickUnit(rt, id, priceMajor) { this.roomTypeId = rt; this.unitId = id; this.roomMinor = priceMajor; this.ratePlanId = (PLANS[rt] && PLANS[rt].default) || ''; this.unitCode = event.target.closest('label').textContent.trim(); this.persist(); },
                plansFor() { return (PLANS[this.roomTypeId] && PLANS[this.roomTypeId].list) || []; },
                pickPlan(planId, priceMinor) { this.ratePlanId = planId; this.roomMinor = priceMinor; this.persist(); },
                setDeposit(c) {
                    this.depositChoice = c;
                    if (c === 'nothing') { this.payMethod = 'later'; this.payAmount = ''; return; }
                    if (this.payMethod !== 'cash' && this.payMethod !== 'bank') this.payMethod = 'cash';
                    const total = this.totalMinor();
                    this.payAmount = ((c === 'full' ? total : Math.round(total * 0.3)) / 100).toFixed(2);
                },
                guests() { return Math.max(1, this.adults + this.children); },
                extrasMinor() {
                    let sum = 0;
                    for (const e of EXTRAS) {
                        if (!this.extras.includes(e.id)) continue;
                        const q = e.basis === 'per_guest_night' ? this.guests() * NIGHTS : e.basis === 'per_night' ? NIGHTS : e.basis === 'per_leg' ? (this.transferTrip === 'return' ? 2 : 1) : 1;
                        sum += e.price * q;
                    }
                    return sum;
                },
                taxableExtras() {
                    let sum = 0;
                    for (const e of EXTRAS) {
                        if (!this.extras.includes(e.id) || !e.taxable) continue;
                        const q = e.basis === 'per_guest_night' ? this.guests() * NIGHTS : e.basis === 'per_night' ? NIGHTS : e.basis === 'per_leg' ? (this.transferTrip === 'return' ? 2 : 1) : 1;
                        sum += e.price * q;
                    }
                    return sum;
                },
                taxMinor() {
                    const base = this.roomMinor + this.taxableExtras();
                    let sum = 0;
                    for (const t of TAXES) {
                        if (t.mode === 'percentage') sum += Math.round(base * t.rate / 100);
                        else if (t.mode === 'per_night') sum += t.amount * NIGHTS;
                        else if (t.mode === 'per_person_night') sum += t.amount * this.guests() * NIGHTS;
                        else sum += t.amount;
                    }
                    return sum;
                },
                totalMinor() { return this.roomMinor + this.extrasMinor() + this.taxMinor(); },
                fmt(m) { return '€' + (m / 100).toLocaleString('en-IE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                guestLabel() { return this.guestMode === 'existing' ? (this.existingGuestName || 'Pick a guest') : ((this.newFirst + ' ' + this.newLast).trim() || 'New guest'); },
                next() { if (this.step < 7) this.step++; },
                prev() { if (this.step > 1) this.step--; },
                persist() {
                    try { localStorage.setItem('vyrobel:new-reservation:draft', JSON.stringify({ step: this.step, roomTypeId: this.roomTypeId, unitId: this.unitId, unitCode: this.unitCode, roomMinor: this.roomMinor, extras: this.extras, transferTrip: this.transferTrip, applyCredit: this.applyCredit, arrivalWindow: this.arrivalWindow })); } catch (e) {}
                },
                restore() {
                    try {
                        const raw = localStorage.getItem('vyrobel:new-reservation:draft');
                        if (!raw) return;
                        const d = JSON.parse(raw);
                        Object.assign(this, d);
                        if (d.step && d.step > 1) this.draftRestored = true;
                    } catch (e) {}
                },
                startOver() { this.clearDraft(); window.location = '{{ route('app.reservations.create') }}'; },
                clearDraft() { try { localStorage.removeItem('vyrobel:new-reservation:draft'); } catch (e) {} },
            };
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        .vy-nr-steps { display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap; }
        .vy-nr-step { display: flex; align-items: center; gap: 7px; padding: 7px 12px; border-radius: 20px; background: var(--vy-glass, rgba(255,252,248,.6)); border: 1px solid var(--vy-hairline); font-size: 12px; color: var(--vy-muted); }
        .vy-nr-step.is-on { background: var(--vy-accent); color: #fff; border-color: var(--vy-accent); }
        .vy-nr-step.is-done { color: #1F6E4B; }
        .vy-nr-step-num { width: 18px; height: 18px; border-radius: 50%; background: rgba(0,0,0,.08); display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; }
        .vy-nr-step.is-on .vy-nr-step-num { background: rgba(255,255,255,.28); }
        .vy-nr-body { display: grid; grid-template-columns: 1fr 300px; gap: 16px; align-items: start; }
        .vy-nr-main { display: flex; flex-direction: column; gap: 14px; }
        .vy-nr-hint { font-size: 12.5px; color: var(--vy-muted); margin: 2px 0 0; }
        .vy-nr-sub { font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--vy-faint); }
        .vy-nr-daterow { font-size: 12px; color: var(--vy-muted); margin-top: 10px; }
        .vyn-rt { border: 1px solid var(--vy-hairline); border-radius: 12px; padding: 12px; margin-top: 10px; }
        .vyn-rt-head { display: flex; justify-content: space-between; font-size: 13.5px; }
        .vyn-rt-name { font-weight: 600; }
        .vyn-rt-price { color: var(--vy-accent); font-variant-numeric: tabular-nums; }
        .vyn-units { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .vyn-unit { padding: 7px 14px; border-radius: 10px; border: 1px solid var(--vy-hairline); font-size: 12.5px; cursor: pointer; }
        .vyn-unit.is-on { background: var(--vy-accent); color: #fff; border-color: var(--vy-accent); }
        .vyn-unpriced, .vyn-empty { font-size: 12.5px; color: var(--vy-faint); padding: 12px 0; }
        .vyn-tabs { display: inline-flex; background: var(--vy-hairline); border-radius: 10px; padding: 3px; }
        .vyn-tab { padding: 7px 16px; border-radius: 8px; border: none; background: none; font-size: 12.5px; cursor: pointer; color: var(--vy-muted); }
        .vyn-tab.is-on { background: #fff; color: var(--vy-ink); box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .vyn-grid3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
        .vyn-guest { display: flex; gap: 11px; align-items: center; padding: 9px 12px; border-radius: 10px; cursor: pointer; }
        .vyn-guest:hover { background: rgba(120,70,40,.05); }
        .vyn-guest-av { width: 30px; height: 30px; border-radius: 50%; background: var(--vy-accent-wash,#F6E4D6); color: var(--vy-accent); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .vyn-check { font-size: 12.5px; color: var(--vy-ink); display: inline-flex; gap: 7px; align-items: center; cursor: pointer; }
        .vyn-extra { display: flex; gap: 11px; align-items: center; padding: 11px 12px; border: 1px solid var(--vy-hairline); border-radius: 10px; margin-top: 8px; font-size: 13px; cursor: pointer; }
        /* GATE 1 B.2 — extras rendered as on/off TOGGLE SWITCHES (prototype-exact); the checkbox still submits. */
        .vyn-extra input[type="checkbox"] { appearance: none; -webkit-appearance: none; position: relative; width: 38px; height: 22px; border-radius: 22px; background: rgba(120, 70, 40, 0.18); border: none; cursor: pointer; flex: none; order: 3; margin: 0; transition: background 0.18s; }
        .vyn-extra input[type="checkbox"]::after { content: ""; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.28); transition: left 0.18s; }
        .vyn-extra input[type="checkbox"]:checked { background: var(--vy-grad); }
        .vyn-extra input[type="checkbox"]:checked::after { left: 19px; }
        /* rate-plan selector cards */
        .vy-nr-plans { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
        .vy-nr-plan { flex: 1; min-width: 150px; display: flex; flex-direction: column; gap: 3px; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.5); cursor: pointer; text-align: left; font-family: var(--vy-sans); transition: border-color 0.14s, background 0.14s; }
        .vy-nr-plan:hover { background: rgba(255, 255, 255, 0.78); }
        .vy-nr-plan.is-on { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.08); box-shadow: 0 0 0 1px var(--vy-accent-border); }
        .vy-nr-plan-name { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
        .vy-nr-plan-price { font-size: 12px; color: var(--vy-muted); font-variant-numeric: tabular-nums; }
        /* guest counters */
        .vy-nr-counter { display: inline-flex; align-items: center; gap: 14px; padding: 5px 8px; border: 1px solid var(--vy-hairline); border-radius: 12px; background: rgba(255, 255, 255, 0.5); }
        .vy-nr-counter button { width: 30px; height: 30px; border-radius: 50%; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.7); color: var(--vy-accent); font-size: 18px; line-height: 1; cursor: pointer; display: grid; place-items: center; transition: background 0.14s; }
        .vy-nr-counter button:hover:not(:disabled) { background: rgba(232, 112, 58, 0.1); }
        .vy-nr-counter button:disabled { opacity: 0.4; cursor: default; }
        .vy-nr-counter span { min-width: 20px; text-align: center; font-size: 15px; font-weight: 600; font-variant-numeric: tabular-nums; }
        /* dual-month drag-range calendar */
        .vy-cal { border: 1px solid var(--vy-hairline); border-radius: 16px; background: rgba(255, 255, 255, 0.45); padding: 14px 16px; }
        .vy-cal-head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
        .vy-cal-nav { width: 30px; height: 30px; border-radius: 50%; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.7); color: var(--vy-accent); display: grid; place-items: center; cursor: pointer; flex: none; transition: background 0.14s; }
        .vy-cal-nav:hover { background: rgba(232, 112, 58, 0.1); }
        .vy-cal-labels { flex: 1; display: grid; grid-template-columns: 1fr 1fr; }
        .vy-cal-labels span { text-align: center; font-family: var(--vy-display); font-weight: 600; font-size: 14px; }
        .vy-cal-grids { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
        .vy-cal-dow { display: grid; grid-template-columns: repeat(7, 1fr); margin-bottom: 4px; }
        .vy-cal-dow span { text-align: center; font-size: 10px; font-weight: 600; letter-spacing: 0.03em; text-transform: uppercase; color: var(--vy-faint); padding: 4px 0; }
        .vy-cal-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px 0; }
        .vy-cal-day { aspect-ratio: 1; border: none; background: transparent; font-family: var(--vy-sans); font-size: 12.5px; color: var(--vy-ink); cursor: pointer; border-radius: 9px; transition: background 0.12s; display: grid; place-items: center; }
        .vy-cal-day:hover:not(:disabled):not(.is-start):not(.is-end) { background: rgba(232, 112, 58, 0.12); }
        .vy-cal-day.is-pad { visibility: hidden; cursor: default; }
        .vy-cal-day.is-past { color: var(--vy-faint); opacity: 0.4; cursor: default; }
        .vy-cal-day.is-weekend:not(.is-start):not(.is-end):not(.is-in) { color: var(--vy-accent); }
        .vy-cal-day.is-in { background: rgba(232, 112, 58, 0.14); border-radius: 0; }
        .vy-cal-day.is-start, .vy-cal-day.is-end { background: var(--vy-grad); color: #fff; font-weight: 600; }
        .vy-cal-day.is-start { border-radius: 9px 0 0 9px; }
        .vy-cal-day.is-end { border-radius: 0 9px 9px 0; }
        .vy-cal-foot { display: flex; align-items: center; gap: 10px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--vy-hairline); }
        .vy-cal-sel { font-size: 12.5px; font-weight: 500; color: var(--vy-muted); }
        @media (max-width: 560px) { .vy-cal-grids { grid-template-columns: 1fr; } .vy-cal-labels { grid-template-columns: 1fr; } .vy-cal-labels span:last-child { display: none; } }
        /* deposit split */
        .vy-nr-deposit { display: flex; gap: 8px; margin-top: 8px; }
        .vy-nr-dep { flex: 1; display: flex; flex-direction: column; gap: 3px; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.5); cursor: pointer; text-align: left; font-family: var(--vy-sans); font-size: 13.5px; color: var(--vy-ink); transition: border-color 0.14s, background 0.14s; }
        .vy-nr-dep span { font-size: 12px; color: var(--vy-muted); font-variant-numeric: tabular-nums; }
        .vy-nr-dep:hover { background: rgba(255, 255, 255, 0.78); }
        .vy-nr-dep.is-on { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.08); box-shadow: 0 0 0 1px var(--vy-accent-border); }
        .vy-nr-doorcode { font-size: 12px; color: var(--vy-muted); background: rgba(31,110,75,.07); border-radius: 10px; padding: 11px 13px; margin-top: 14px; }
        .vy-nr-rules, .vyn-rules { display: grid; gap: 1px; }
        .vyn-rules > div { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; }
        .vyn-rules > div span { color: var(--vy-muted); }
        .vy-nr-nav { display: flex; gap: 10px; align-items: center; padding-top: 6px; }
        .vy-nr-summary { position: sticky; top: 16px; }
        .vy-nr-sumline { display: flex; justify-content: space-between; font-size: 13px; padding: 7px 0; }
        .vy-nr-sumtotal { border-top: 2px solid var(--vy-ink); font-weight: 700; margin-top: 4px; padding-top: 10px; }
        .vy-nr-esthint { font-size: 11px; color: var(--vy-faint); margin: 8px 0 0; }

        /* GATE 1 Part B — the STANDALONE wizard chrome (prototype-exact) */
        .vy-nr-standalone { position: relative; z-index: 2; max-width: 1180px; margin: 0 auto; padding: 24px 30px 60px; box-sizing: border-box; }
        .vy-nr-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .vy-nr-brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .vy-nr-logo { width: 36px; height: 36px; border-radius: 10px; background: var(--vy-grad); color: #fff; font-family: var(--vy-display); font-weight: 600; font-size: 18px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); flex: none; }
        .vy-nr-brand-title { display: block; font-family: var(--vy-display); font-weight: 600; font-size: 18px; letter-spacing: -0.02em; line-height: 1.2; }
        .vy-nr-brand-sub { display: block; font-size: 12px; color: var(--vy-faint); line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vy-nr-topright { display: flex; align-items: center; gap: 12px; flex: none; }
        .vy-nr-draftpill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: var(--vy-r-pill); background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid rgba(255, 255, 255, 0.7); font-size: 11.5px; font-weight: 500; color: #1F8A5B; white-space: nowrap; }
        .vy-nr-stepcount { font-size: 12px; font-weight: 500; color: var(--vy-muted); white-space: nowrap; }
        .vy-nr-close { width: 36px; height: 36px; border-radius: 50%; background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); color: var(--vy-muted); display: grid; place-items: center; text-decoration: none; transition: background 0.14s; flex: none; }
        .vy-nr-close:hover { background: rgba(255, 255, 255, 0.9); color: var(--vy-ink); }
        .vy-nr-ro { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 14px; background: rgba(217, 154, 61, 0.16); border: 1px solid rgba(217, 154, 61, 0.4); color: #8F6414; font-size: 13px; margin-bottom: 16px; }

        /* clickable circular stepper */
        .vy-nr-stepper { display: flex; background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); border-radius: 20px; box-shadow: 0 14px 40px rgba(150, 90, 50, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.65); padding: 18px 26px; margin-bottom: 20px; }
        .vy-nr-node { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 9px; min-width: 0; }
        .vy-nr-node-row { display: flex; align-items: center; width: 100%; }
        .vy-nr-bar { flex: 1; height: 2px; border-radius: 2px; background: var(--vy-hairline); }
        .vy-nr-node.is-done .vy-nr-bar { background: var(--vy-grad); }
        .vy-nr-node.is-on .vy-nr-bar:first-child { background: var(--vy-grad); }
        .vy-nr-circle { position: relative; width: 34px; height: 34px; border-radius: 50%; background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-glass-border); color: var(--vy-faint); cursor: pointer; flex: none; display: grid; place-items: center; font-family: var(--vy-display); font-weight: 600; font-size: 13px; transition: background 0.2s, box-shadow 0.2s, transform 0.12s; }
        .vy-nr-circle:hover { transform: translateY(-1px); }
        .vy-nr-node.is-on .vy-nr-circle { background: var(--vy-grad); color: #fff; border-color: transparent; box-shadow: 0 0 0 4px rgba(232, 112, 58, 0.18); }
        .vy-nr-node.is-done .vy-nr-circle { background: var(--vy-grad); color: #fff; border-color: transparent; }
        .vy-nr-circle-num { transition: opacity 0.15s; }
        .vy-nr-circle-check { position: absolute; opacity: 0; transition: opacity 0.15s; }
        .vy-nr-node.is-done .vy-nr-circle-num { opacity: 0; }
        .vy-nr-node.is-done .vy-nr-circle-check { opacity: 1; }
        .vy-nr-node-label { font-size: 12px; font-weight: 500; color: var(--vy-faint); white-space: nowrap; }
        .vy-nr-node.is-on .vy-nr-node-label, .vy-nr-node.is-done .vy-nr-node-label { color: var(--vy-ink); }

        @media (max-width: 900px) { .vy-nr-body { grid-template-columns: 1fr; } }
        @media (max-width: 760px) { .vy-nr-node-label { display: none; } .vy-nr-standalone { padding: 18px 16px 48px; } }
    </style>
</div>
