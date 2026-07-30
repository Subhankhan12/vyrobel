{{-- Operator NEW RESERVATION — the FULL 7-step wizard (#90 exact-match), on the certified createBooking path.
     Step 1's availability is a REAL server read (GET → UnitAvailabilityService — only genuinely-free, priced
     units appear; createBooking re-checks under a row lock). Steps 2–7 collect the guest, extras, transfer,
     house-rules and test-mode payment, submitted as ONE POST → ReservationCreateController, which posts REAL
     reconciling folio lines (room + extras + transfer − credit + tax, #17). The Booking-summary panel is a
     client ESTIMATE from the REAL prices; the authoritative total is the folio on the reservation.
     GUARDS: honest availability · guest NAME-only search (#28) · NO raw PAN (card = honest "Soon", #45).
     Draft-restore = client localStorage (matches the wireframe; cleared on confirm — no server-persistence claim).
     RAW-BLADE RULE: Alpine uses x-on:/x-model/x-bind (no `@click`). --}}
<div>
    <x-operator.shell active="reservations" title="New reservation" :subtitle="$subtitle" :viewer="$viewer">

        @if (session('new_reservation_notice'))
            <div class="vy-flash is-err vy-fade">{{ session('new_reservation_notice') }}</div>
        @endif

        @unless ($hasProperty)
            <div class="vy-card vy-fade" style="text-align: center; padding: 40px 24px;">
                <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">No property configured</strong>
                <span style="font-size: 13px; color: var(--vy-muted);">Add a property before creating reservations.</span>
            </div>
        @else
            <div class="vy-nr" x-data="newReservationWizard()" x-init="init()">
                <input type="hidden" x-ref="checkIn" value="{{ $checkIn }}">
                <input type="hidden" x-ref="checkOut" value="{{ $checkOut }}">

                {{-- Draft-restored banner --}}
                <div class="vy-card vy-fade" x-show="draftRestored" x-cloak style="border-left: 3px solid var(--vy-accent); margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px;">Picked up where you left off — your draft was restored.</span>
                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="startOver()">Start over</button>
                </div>

                {{-- Step rail --}}
                <div class="vy-nr-steps vy-fade">
                    @foreach (['When & unit', 'Who’s staying', 'Rate & extras', 'Arrival & transfer', 'House rules', 'Review', 'Payment'] as $i => $label)
                        <div class="vy-nr-step" x-bind:class="step === {{ $i + 1 }} ? 'is-on' : (step > {{ $i + 1 }} ? 'is-done' : '')">
                            <span class="vy-nr-step-num">{{ $i + 1 }}</span>
                            <span class="vy-nr-step-label">{{ $label }}</span>
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
                        <input type="hidden" name="transfer_trip" x-model="transferTrip">
                        <input type="hidden" name="apply_credit" x-bind:value="applyCredit ? '1' : ''">

                        {{-- STEP 1 — When & which unit --}}
                        <section class="vy-card" x-show="step === 1">
                            <h2 class="vy-card-title">When &amp; which unit</h2>
                            <p class="vy-nr-hint">Pick the dates and party size — availability updates as you go.</p>

                            {{-- The dates are a REAL server read: changing them reloads with the availability. --}}
                            <div class="vyn-grid3" style="align-items: end; margin-top: 12px;">
                                <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="ci">Check-in</label><input id="ci" type="date" x-ref="ciInput" value="{{ $checkIn }}" class="vy-input"></div>
                                <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="co">Check-out</label><input id="co" type="date" x-ref="coInput" value="{{ $checkOut }}" class="vy-input"></div>
                                <div><button type="button" class="vy-btn vy-btn--ghost" style="width: 100%; justify-content: center; padding: 11px;" x-on:click="reloadDates()">Check availability</button></div>
                            </div>
                            @if ($datesValid)
                                <div class="vy-nr-daterow">{{ \Carbon\CarbonImmutable::parse($checkIn)->format('j M Y') }} → {{ \Carbon\CarbonImmutable::parse($checkOut)->format('j M Y') }} · {{ $nights }} {{ $nights === 1 ? 'night' : 'nights' }}</div>
                            @endif

                            <div class="vyn-grid3" style="margin-top: 14px;">
                                <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="ad">Adults</label><input id="ad" type="number" name="adults" class="vy-input" min="1" max="30" x-model.number="adults"></div>
                                <div class="vy-field" style="margin: 0;"><label class="vy-field-label" for="ch">Children</label><input id="ch" type="number" name="children" class="vy-input" min="0" max="30" x-model.number="children"></div>
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
                                <form method="GET" action="{{ route('app.reservations.create') }}" style="margin: 12px 0;">
                                    <input type="hidden" name="check_in" value="{{ $checkIn }}"><input type="hidden" name="check_out" value="{{ $checkOut }}">
                                    <input type="text" name="q" value="{{ $query }}" class="vy-input" placeholder="Search a returning guest by name" autocomplete="off">
                                </form>
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
                            </div>
                            <div x-show="guestMode === 'new'" style="margin-top: 12px; display: flex; gap: 22px;">
                                <label class="vyn-check"><input type="checkbox" name="guest_vip" value="1" x-bind:disabled="guestMode !== 'new'"> Mark as VIP</label>
                                <label class="vyn-check"><input type="checkbox" name="guest_marketing_consent" value="1" x-bind:disabled="guestMode !== 'new'"> Marketing consent · guest agreed to offers &amp; news</label>
                            </div>
                        </section>

                        {{-- STEP 3 — Rate & extras --}}
                        <section class="vy-card" x-show="step === 3" x-cloak>
                            <h2 class="vy-card-title">Rate &amp; extras</h2>
                            <p class="vy-nr-hint">The nightly rate is set; add anything the guest wants — each is a real line on the folio.</p>
                            <div class="vy-nr-sub" style="margin-top: 12px;">Extras</div>
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
                            <label class="vyn-check" style="margin-top: 10px;"><input type="checkbox" name="send_confirmation" value="1" checked> Send confirmation email <span class="vy-muted">· guest gets the booking details right away</span></label>
                        </section>

                        {{-- STEP 7 — Payment (test-mode) --}}
                        <section class="vy-card" x-show="step === 7" x-cloak>
                            <h2 class="vy-card-title">Payment</h2>
                            <p class="vy-nr-hint">Take a deposit or the full amount now, or collect at check-in.</p>
                            @if ($canPay)
                                <div class="vy-field" style="margin-top: 12px;"><label class="vy-field-label" for="pm">Method</label>
                                    <select id="pm" name="payment_method" class="vy-input" x-model="payMethod">
                                        <option value="later">Pay later (balance due)</option>
                                        <option value="cash">Cash</option>
                                        <option value="bank">Bank transfer</option>
                                        <option value="card" disabled>Card — coming soon</option>
                                    </select>
                                </div>
                                <div class="vy-field" style="margin-top: 10px;" x-show="payMethod === 'cash' || payMethod === 'bank'">
                                    <label class="vy-field-label" for="pa">Amount</label>
                                    <input id="pa" type="text" name="payment_amount" class="vy-input" inputmode="decimal" placeholder="e.g. 150 or 150.00" x-bind:disabled="payMethod !== 'cash' && payMethod !== 'bank'">
                                </div>
                                <div class="vy-nr-doorcode">Card payments arrive with secure Stripe checkout — <strong>no card number is ever entered or stored here</strong> (test-mode until go-live). Take cash or bank now, or record it later from the folio.</div>
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

    </x-operator.shell>

    <script>
        function newReservationWizard() {
            const EXTRAS = @js(collect($extras)->map(fn ($e) => ['id' => $e['id'], 'price' => $e['price_minor'], 'taxable' => $e['taxable'], 'basis' => $e['basis']])->all());
            const TAXES = @js(collect($taxes)->map(fn ($t) => ['mode' => $t['mode'], 'rate' => $t['rate'], 'amount' => $t['amount_minor']])->all());
            const NIGHTS = @js($nights);
            return {
                step: 1,
                roomTypeId: '', unitId: '', unitCode: '', roomMinor: 0,
                adults: 2, children: 0,
                guestMode: @js($guestMatches !== [] ? 'existing' : 'new'),
                existingGuestName: '', newFirst: '', newLast: '',
                extras: [], transferTrip: 'one', applyCredit: false,
                arrivalWindow: '', rulesAgreed: false, payMethod: 'later',
                draftRestored: false,
                init() { this.restore(); this.$watch('step', () => this.persist()); },
                reloadDates() {
                    const ci = this.$refs.ciInput.value, co = this.$refs.coInput.value;
                    if (ci && co) window.location = '{{ route('app.reservations.create') }}?check_in=' + ci + '&check_out=' + co;
                },
                pickUnit(rt, id, priceMajor) { this.roomTypeId = rt; this.unitId = id; this.roomMinor = priceMajor; this.unitCode = event.target.closest('label').textContent.trim(); this.persist(); },
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
        .vy-nr-doorcode { font-size: 12px; color: var(--vy-muted); background: rgba(31,110,75,.07); border-radius: 10px; padding: 11px 13px; margin-top: 14px; }
        .vy-nr-rules, .vyn-rules { display: grid; gap: 1px; }
        .vyn-rules > div { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; }
        .vyn-rules > div span { color: var(--vy-muted); }
        .vy-nr-nav { display: flex; gap: 10px; align-items: center; padding-top: 6px; }
        .vy-nr-summary { position: sticky; top: 16px; }
        .vy-nr-sumline { display: flex; justify-content: space-between; font-size: 13px; padding: 7px 0; }
        .vy-nr-sumtotal { border-top: 2px solid var(--vy-ink); font-weight: 700; margin-top: 4px; padding-top: 10px; }
        .vy-nr-esthint { font-size: 11px; color: var(--vy-faint); margin: 8px 0 0; }
        @media (max-width: 900px) { .vy-nr-body { grid-template-columns: 1fr; } }
    </style>
</div>
