{{-- Operator GROUP BOOKING (Phase 1 · Sub-gate 1) — build a block of rooms through the ATOMIC certified path
     (AssignmentService::createGroupBooking → all-or-nothing, no phantom events). READ view (availability via a
     GET on the dates — the scope-safe pattern, invariant #20); the block is the guarded POST form below.
     See App\Livewire\Operator\GroupBooking + GroupBookingController + GroupBookingService.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. Alpine uses x-on:/x-model/x-bind (no `@click`).
     MONEY: each unit price is server-formatted; the live block total is a DISPLAY-only sum of INTEGER minor
     units (never a float computation) — the authoritative folio charge is re-derived + posted server-side. --}}
<div>
    <x-operator.shell active="reservations" title="Group booking" :subtitle="$subtitle" :viewer="$viewer">

        @if (session('group_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('group_notice') }}</div>
            </div>
        @endif

        @unless ($hasProperty)
            <div class="vy-card vy-fade" style="text-align: center; padding: 40px 24px;">
                <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">No property configured</strong>
                <span style="font-size: 13px; color: var(--vy-muted);">Add a property before creating a block.</span>
            </div>
        @else

            {{-- ── Block dates (GET → drives the availability read) ──────────────────────────────────── --}}
            <form method="GET" action="{{ route('app.group-booking') }}" class="vy-card vy-fade" style="margin-bottom: 16px;">
                <div class="vyg-grid3" style="align-items: end;">
                    <div class="vy-field" style="margin: 0;">
                        <label class="vy-field-label" for="ci">Block check-in</label>
                        <input id="ci" type="date" name="check_in" class="vy-input" value="{{ $checkIn }}" required>
                    </div>
                    <div class="vy-field" style="margin: 0;">
                        <label class="vy-field-label" for="co">Block check-out</label>
                        <input id="co" type="date" name="check_out" class="vy-input" value="{{ $checkOut }}" required>
                    </div>
                    <div>
                        <button type="submit" class="vy-btn vy-btn--ghost" style="width: 100%; justify-content: center; padding: 11px;">Check availability</button>
                    </div>
                </div>
                @if ($datesValid)
                    <div style="font-size: 12px; color: var(--vy-muted); margin-top: 10px;">
                        {{ \Carbon\CarbonImmutable::parse($checkIn)->format('j M Y') }} → {{ \Carbon\CarbonImmutable::parse($checkOut)->format('j M Y') }} · {{ $nights }} {{ $nights === 1 ? 'night' : 'nights' }} · {{ $freeUnitCount }} {{ $freeUnitCount === 1 ? 'unit' : 'units' }} free
                    </div>
                @endif
            </form>

            @unless ($datesValid)
                <div class="vy-card vy-fade vyg-empty">Choose block dates above to see what’s free, then build the block.</div>
            @else

                <form method="POST" action="{{ route('app.group-booking') }}" x-data="groupBookingForm(@js($currency))">
                    @csrf
                    <input type="hidden" name="check_in" value="{{ $checkIn }}">
                    <input type="hidden" name="check_out" value="{{ $checkOut }}">

                    {{-- 1 · The group --}}
                    <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                        <h2 class="vy-card-title" style="margin-bottom: 12px;">1 · The group</h2>
                        <div class="vyg-grid3">
                            <div class="vy-field" style="margin: 0; grid-column: span 2;">
                                <label class="vy-field-label" for="gn">Group name</label>
                                <input id="gn" type="text" name="name" class="vy-input" placeholder="e.g. Okafor–Bello Wedding" required autocomplete="off">
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="oc">Occasion</label>
                                <select id="oc" name="occasion" class="vy-input">
                                    @foreach ($occasions as $occ)
                                        <option value="{{ $occ['value'] }}">{{ $occ['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="ad">Adults / unit</label>
                                <input id="ad" type="number" name="adults" class="vy-input" min="1" max="30" value="2">
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="ch">Children / unit</label>
                                <input id="ch" type="number" name="children" class="vy-input" min="0" max="30" value="0">
                            </div>
                        </div>
                    </div>

                    {{-- 2 · Build the block — only genuinely free + priced units are offered (GUARD 1) --}}
                    <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                            <h2 class="vy-card-title" style="margin: 0;">2 · Build the block</h2>
                            <span style="font-size: 12.5px; color: var(--vy-muted);">
                                <span x-text="count"></span> <span x-text="count === 1 ? 'room' : 'rooms'"></span> · block total <strong style="color: var(--vy-ink);" x-text="totalDisplay"></strong>
                            </span>
                        </div>
                        <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 12px;">Pick the units for the block. Each price is the stay total for that unit.</p>

                        @foreach ($roomTypes as $rt)
                            <div class="vyg-rt">
                                <div class="vyg-rt-head">
                                    <span class="vyg-rt-name">{{ $rt['name'] }}</span>
                                    <span class="vyg-rt-price">{{ $rt['price'] }} · stay</span>
                                </div>
                                <div class="vyg-units">
                                    @foreach ($rt['units'] as $unit)
                                        <label class="vyg-unit">
                                            <input type="checkbox" name="units[]" value="{{ $unit['id'] }}"
                                                x-on:change="toggleUnit('{{ $unit['id'] }}', {{ $rt['price_minor'] }}, $event.target.checked)">
                                            {{ $unit['code'] }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- 3 · Organiser --}}
                    <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                        <h2 class="vy-card-title" style="margin-bottom: 12px;">3 · Organiser</h2>
                        <div class="vyg-grid3">
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="of">First name</label>
                                <input id="of" type="text" name="organiser_first_name" class="vy-input" autocomplete="off">
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="ol">Last name</label>
                                <input id="ol" type="text" name="organiser_last_name" class="vy-input" autocomplete="off">
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="ocmp">Company <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                                <input id="ocmp" type="text" name="organiser_company" class="vy-input" autocomplete="off">
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="oe">Email <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                                <input id="oe" type="email" name="organiser_email" class="vy-input" autocomplete="off">
                            </div>
                            <div class="vy-field" style="margin: 0;">
                                <label class="vy-field-label" for="op">Phone <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· optional</span></label>
                                <input id="op" type="text" name="organiser_phone" class="vy-input" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    {{-- 4 · Terms — hold type + billing --}}
                    <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                        <h2 class="vy-card-title" style="margin-bottom: 12px;">4 · Terms</h2>
                        <div class="vyg-grid2">
                            <div>
                                <div class="vy-field-label" style="margin-bottom: 9px;">Hold type</div>
                                <label class="vyg-opt">
                                    <input type="radio" name="hold_type" value="confirmed" x-model="holdType" checked>
                                    <span><strong>Confirmed</strong><br><span class="vyg-opt-sub">Units held firm on the rack</span></span>
                                </label>
                                <label class="vyg-opt">
                                    <input type="radio" name="hold_type" value="tentative" x-model="holdType">
                                    <span><strong>Tentative hold</strong><br><span class="vyg-opt-sub">Option held until a date you set</span></span>
                                </label>
                                <div class="vy-field" style="margin: 8px 0 0;" x-show="holdType === 'tentative'">
                                    <label class="vy-field-label" for="pd">Option held until</label>
                                    <input id="pd" type="date" name="pickup_deadline" class="vy-input" value="{{ $checkIn }}" x-bind:disabled="holdType !== 'tentative'">
                                </div>
                            </div>
                            <div>
                                <div class="vy-field-label" style="margin-bottom: 9px;">Payment</div>
                                <label class="vyg-opt">
                                    <input type="radio" name="billing_mode" value="individual" x-model="billingMode" checked>
                                    <span><strong>Individual pay</strong><br><span class="vyg-opt-sub">Each unit gets its own folio</span></span>
                                </label>
                                <label class="vyg-opt">
                                    <input type="radio" name="billing_mode" value="master" x-model="billingMode">
                                    <span><strong>Master invoice</strong><br><span class="vyg-opt-sub">Organiser settles one bill for the block</span></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 12.5px; color: var(--vy-muted);">
                            <span x-text="count"></span> <span x-text="count === 1 ? 'room' : 'rooms'"></span> · <span x-text="totalDisplay"></span> · <span x-text="billingMode === 'master' ? 'one master invoice' : 'individual folios'"></span>
                        </span>
                        <button type="submit" class="vy-btn vy-btn--accent" style="padding: 12px 22px;" x-bind:disabled="count === 0">Create the block</button>
                    </div>
                </form>

            @endunless
        @endunless

    </x-operator.shell>

    <script>
        function groupBookingForm(currency) {
            return {
                currency: currency,
                selected: [],
                billingMode: 'individual',
                holdType: 'confirmed',
                toggleUnit(id, priceMinor, checked) {
                    if (checked) {
                        if (! this.selected.some(u => u.id === id)) this.selected.push({ id: id, priceMinor: priceMinor });
                    } else {
                        this.selected = this.selected.filter(u => u.id !== id);
                    }
                },
                get count() { return this.selected.length; },
                // DISPLAY only: sum of INTEGER minor units (exact), formatted for preview. The real folio
                // charge is re-derived + posted server-side — this never drives a money write.
                get totalMinor() { return this.selected.reduce((s, u) => s + u.priceMinor, 0); },
                get totalDisplay() {
                    const symbol = this.currency === 'EUR' ? '€' : (this.currency === 'USD' ? '$' : this.currency + ' ');
                    const value = (this.totalMinor / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return symbol + value;
                },
            };
        }
    </script>

    <style>
        .vyg-grid3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .vyg-grid2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .vyg-empty { text-align: center; padding: 32px 24px; color: var(--vy-muted); font-size: 13px; }
        .vyg-rt { border: 1px solid var(--vy-hairline); border-radius: var(--vy-r-md, 12px); padding: 12px 14px; margin-bottom: 10px; }
        .vyg-rt-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 9px; }
        .vyg-rt-name { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
        .vyg-rt-price { font-size: 12.5px; color: var(--vy-accent); font-weight: 600; }
        .vyg-units { display: flex; flex-wrap: wrap; gap: 8px; }
        .vyg-unit { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--vy-ink); background: rgba(255,255,255,0.7); border: 1px solid var(--vy-hairline); border-radius: 8px; padding: 6px 10px; cursor: pointer; }
        .vyg-opt { display: flex; align-items: flex-start; gap: 9px; padding: 10px 12px; border: 1px solid var(--vy-hairline); border-radius: var(--vy-r-md, 12px); margin-bottom: 8px; cursor: pointer; font-size: 13px; color: var(--vy-ink); }
        .vyg-opt-sub { font-size: 11.5px; color: var(--vy-muted); font-weight: 400; }
        @media (max-width: 720px) { .vyg-grid3, .vyg-grid2 { grid-template-columns: 1fr; } }
    </style>
</div>
