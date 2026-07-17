{{-- Operator NEW RESERVATION (Wave 4 · Sub-gate 2) — create one direct booking through the certified
     createBooking path. READ view (availability + guest name-search via GET); the booking is the guarded
     POST form below. See App\Livewire\Operator\NewReservation + ReservationCreateController for the three
     guards (honest availability · PII name-only · NO raw PAN).

     RAW-BLADE RULE: every `@` follows a space, newline or tag. Alpine uses x-on:/x-model/x-bind (no `@click`),
     so no `@` reaches an attribute value. No public props on the component → no prop/view-data shadowing. --}}
<div>
    <x-operator.shell active="reservations" title="New reservation" :subtitle="$subtitle" :viewer="$viewer">

        @if (session('new_reservation_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('new_reservation_notice') }}</div>
            </div>
        @endif

        @unless ($hasProperty)
            <div class="vy-card vy-fade" style="text-align: center; padding: 40px 24px;">
                <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">No property configured</strong>
                <span style="font-size: 13px; color: var(--vy-muted);">Add a property before creating reservations.</span>
            </div>
        @else

            {{-- ── Dates + guest search (GET → drives the availability read + the guest matches) ──────── --}}
            <form method="GET" action="{{ route('app.reservations.create') }}" class="vy-card vy-fade" style="margin-bottom: 16px;">
                <div class="vyn-grid3" style="align-items: end;">
                    <div class="vy-field" style="margin: 0;">
                        <label class="vy-field-label" for="ci">Check-in</label>
                        <input id="ci" type="date" name="check_in" class="vy-input" value="{{ $checkIn }}" required>
                    </div>
                    <div class="vy-field" style="margin: 0;">
                        <label class="vy-field-label" for="co">Check-out</label>
                        <input id="co" type="date" name="check_out" class="vy-input" value="{{ $checkOut }}" required>
                    </div>
                    <div class="vy-field" style="margin: 0;">
                        <label class="vy-field-label" for="q">Find a guest by name</label>
                        <input id="q" type="text" name="q" class="vy-input" value="{{ $query }}" placeholder="e.g. Ada Lovelace" autocomplete="off">
                    </div>
                    <div>
                        <button type="submit" class="vy-btn vy-btn--ghost" style="width: 100%; justify-content: center; padding: 11px;">Check availability</button>
                    </div>
                </div>
                @if ($datesValid)
                    <div style="font-size: 12px; color: var(--vy-muted); margin-top: 10px;">
                        {{ \Carbon\CarbonImmutable::parse($checkIn)->format('j M Y') }} → {{ \Carbon\CarbonImmutable::parse($checkOut)->format('j M Y') }} · {{ $nights }} {{ $nights === 1 ? 'night' : 'nights' }}
                    </div>
                @endif
            </form>

            {{-- ── The booking form (POST → ReservationCreateController). Availability + guest + party +
                 source + optional payment, one submit. The unit list is honest — only genuinely-bookable
                 units appear (GUARD 1); createBooking re-checks under a lock. ───────────────────────────── --}}
            <form method="POST" action="{{ route('app.reservations.create') }}" x-data="newReservationForm()">
                @csrf
                <input type="hidden" name="check_in" value="{{ $checkIn }}">
                <input type="hidden" name="check_out" value="{{ $checkOut }}">
                <input type="hidden" name="room_type_id" x-model="roomTypeId">
                <input type="hidden" name="unit_id" x-model="unitId">

                {{-- STEP 1 — pick a unit --}}
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 4px;">1 · Choose a unit</h2>
                    <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 12px;">Only units that are genuinely free and priced for these dates are shown.</p>

                    @unless ($datesValid)
                        <div class="vyn-empty">Choose a check-in and check-out date above to see what’s available.</div>
                    @else
                        @forelse ($roomTypes as $rt)
                            <div class="vyn-rt">
                                <div class="vyn-rt-head">
                                    <span class="vyn-rt-name">{{ $rt['name'] }}</span>
                                    @if ($rt['priced'])
                                        <span class="vyn-rt-price">{{ $rt['price'] }} · stay</span>
                                    @endif
                                </div>
                                @if ($rt['priced'])
                                    <div class="vyn-units">
                                        @foreach ($rt['units'] as $unit)
                                            <label class="vyn-unit">
                                                <input type="radio" name="unit_pick"
                                                    x-on:change="roomTypeId = '{{ $rt['room_type_id'] }}'; unitId = '{{ $unit['id'] }}'">
                                                {{ $unit['code'] }}
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="vyn-unpriced">
                                        {{ $rt['unit_count'] }} free {{ $rt['unit_count'] === 1 ? 'unit' : 'units' }} — but no rate is set for these dates.
                                        <a href="{{ route('app.rates', ['from' => $checkIn]) }}" style="color: var(--vy-accent);">Set a rate first →</a>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="vyn-empty">No units are free for those dates. Try a different range.</div>
                        @endforelse
                    @endunless
                </div>

                {{-- STEP 2 — the guest (existing NAME-only search, or a new guest) --}}
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">2 · Guest</h2>

                    <div class="vyn-tabs">
                        <button type="button" class="vyn-tab" x-bind:class="guestMode === 'existing' ? 'is-on' : ''" x-on:click="guestMode = 'existing'">Existing guest</button>
                        <button type="button" class="vyn-tab" x-bind:class="guestMode === 'new' ? 'is-on' : ''" x-on:click="guestMode = 'new'">New guest</button>
                    </div>

                    {{-- Existing — NAME ONLY. Email/phone are encrypted and never rendered (GUARD 2). --}}
                    <div x-show="guestMode === 'existing'">
                        @if ($query === '')
                            <div class="vyn-empty">Search a guest by name in the bar above, then pick them here.</div>
                        @elseif ($guestMatches === [])
                            <div class="vyn-empty">No guests match “{{ $query }}”. Switch to <strong>New guest</strong> to add them.</div>
                        @else
                            @foreach ($guestMatches as $match)
                                <label class="vyn-guest">
                                    <input type="radio" name="existing_guest_id" value="{{ $match['id'] }}" x-bind:disabled="guestMode !== 'existing'">
                                    <span class="vyn-guest-av">{{ $match['initials'] }}</span>
                                    <span style="font-size: 13.5px; color: var(--vy-ink);">{{ $match['name'] }}</span>
                                </label>
                            @endforeach
                        @endif
                    </div>

                    {{-- New — collected into the ENCRYPTED columns by the controller; never echoed back. --}}
                    <div x-show="guestMode === 'new'" class="vyn-grid3">
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="gf">First name</label>
                            <input id="gf" type="text" name="guest_first_name" class="vy-input" x-bind:disabled="guestMode !== 'new'" autocomplete="off">
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="gl">Last name</label>
                            <input id="gl" type="text" name="guest_last_name" class="vy-input" x-bind:disabled="guestMode !== 'new'" autocomplete="off">
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="ge">Email <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                            <input id="ge" type="email" name="guest_email" class="vy-input" x-bind:disabled="guestMode !== 'new'" autocomplete="off">
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="gp">Phone <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                            <input id="gp" type="text" name="guest_phone" class="vy-input" x-bind:disabled="guestMode !== 'new'" autocomplete="off">
                        </div>
                    </div>
                </div>

                {{-- STEP 3 — party, source, optional payment --}}
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">3 · Details</h2>

                    <div class="vyn-grid3">
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="ad">Adults</label>
                            <input id="ad" type="number" name="adults" class="vy-input" min="1" max="30" value="2">
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="ch">Children</label>
                            <input id="ch" type="number" name="children" class="vy-input" min="0" max="30" value="0">
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="src">Source</label>
                            <select id="src" name="source" class="vy-input">
                                @foreach ($sources as $src)
                                    <option value="{{ $src['value'] }}">{{ $src['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($canPay)
                        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--vy-hairline);">
                            <div class="vy-field-label" style="margin-bottom: 9px;">Take a payment now <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></div>
                            <div class="vyn-grid3">
                                <div class="vy-field" style="margin: 0;">
                                    <label class="vy-field-label" for="pm" style="font-weight: 500; text-transform: none; letter-spacing: 0;">Method</label>
                                    <select id="pm" name="payment_method" class="vy-input" x-model="payMethod">
                                        <option value="later">Pay later (balance due)</option>
                                        <option value="cash">Cash</option>
                                        <option value="bank">Bank transfer</option>
                                        <option value="card" disabled>Card — coming soon</option>
                                    </select>
                                </div>
                                <div class="vy-field" style="margin: 0;" x-show="payMethod === 'cash' || payMethod === 'bank'">
                                    <label class="vy-field-label" for="pa" style="font-weight: 500; text-transform: none; letter-spacing: 0;">Amount</label>
                                    <input id="pa" type="text" name="payment_amount" class="vy-input" inputmode="decimal" placeholder="e.g. 150 or 150.00" x-bind:disabled="payMethod !== 'cash' && payMethod !== 'bank'">
                                </div>
                            </div>
                            <div style="font-size: 11.5px; color: var(--vy-faint); margin-top: 8px;">
                                Card payments arrive with secure Stripe checkout — no card number is ever entered or stored here. Take cash or bank transfer now, or record any payment later from the folio.
                            </div>
                        </div>
                    @else
                        <div style="font-size: 11.5px; color: var(--vy-faint); margin-top: 14px;">Payment is taken later from the reservation’s folio.</div>
                    @endif
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="vy-btn vy-btn--accent" style="padding: 12px 22px;" x-bind:disabled="! unitId">Create reservation</button>
                </div>
            </form>

        @endunless

    </x-operator.shell>

    <script>
        function newReservationForm() {
            return {
                roomTypeId: '',
                unitId: '',
                guestMode: @js($guestMatches !== [] ? 'existing' : 'new'),
                payMethod: 'later',
            };
        }
    </script>
</div>
