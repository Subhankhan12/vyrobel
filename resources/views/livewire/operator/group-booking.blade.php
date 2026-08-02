{{-- Operator GROUP BOOKING (GATE 4 exact-match) — a standalone frosted 5-step WIZARD (Block · Units · Organiser
     · Terms · Review) over the CERTIFIED atomic block path (AssignmentService::createGroupBooking → all-or-none).
     The operator LAYOUT supplies the warm gradient + orbs + tokens; we drop the app shell for a focused create
     flow (the New-Reservation B.1 pattern). Availability is a REAL server read (dates ride a GET, invariant #20);
     steps 2–5 are client-side Alpine over the units this render scoped + loaded; the block is the guarded POST.
     MONEY: the pricing rail is a DISPLAY-only sum of INTEGER minor units — the authoritative folio is re-derived
     + posted server-side (GUARD 1). CAPACITY: real units.max_occupancy vs the real headcount — never fabricated.
     RAW-BLADE RULE: Alpine uses x-on:/x-model/x-bind (no `@click`); every `@` follows a space/newline/tag. --}}
@php $__gbReadOnly = app(\App\Support\Operator\OperatorWorkspace::class)->isReadOnly(); @endphp
@if (! $hasProperty)
    <div class="vy-gb-standalone">
        <div class="vy-gb-topbar vy-fade">
            <div class="vy-gb-brand"><span class="vy-gb-logo">V</span><span class="vy-gb-brand-id"><span class="vy-gb-brand-title">Group booking</span><span class="vy-gb-brand-sub">No property configured</span></span></div>
            <a class="vy-gb-close" href="{{ route('app.rack') }}" title="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg></a>
        </div>
        <div class="vy-gb-card vy-fade" style="text-align:center; padding: 40px 24px;">
            <strong style="display:block; font-size:15px; color:var(--vy-ink); margin-bottom:6px;">No property configured</strong>
            <span style="font-size:13px; color:var(--vy-muted);">Add a property before creating a block.</span>
        </div>
    </div>
@else
    <div class="vy-gb-standalone" x-data="groupBookingWizard(@js($currency), @js($roomTypes), @js((string) $checkIn), @js((string) $checkOut))" x-init="init()">
        {{-- Topbar --}}
        <div class="vy-gb-topbar vy-fade">
            <div class="vy-gb-brand">
                <span class="vy-gb-logo">V</span>
                <span class="vy-gb-brand-id">
                    <span class="vy-gb-brand-title">Group booking</span>
                    <span class="vy-gb-brand-sub">{{ $subtitle }} · block reservation</span>
                </span>
            </div>
            <div class="vy-gb-topright">
                <span class="vy-gb-stepcount" x-text="'Step ' + step + ' of 5'"></span>
                <a class="vy-gb-close" href="{{ route('app.rack') }}" title="Close" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg></a>
            </div>
        </div>

        @if (session('group_notice'))
            <div class="vy-gb-flash vy-fade">{{ session('group_notice') }}</div>
        @endif
        @if ($__gbReadOnly)
            <div class="vy-gb-ro vy-fade" role="status">Your workspace is paused — new blocks are off until you resume from Billing.</div>
        @endif

        {{-- Clickable 5-node stepper --}}
        <div class="vy-gb-stepper vy-fade">
            @foreach (['Block', 'Units', 'Organiser', 'Terms', 'Review'] as $i => $label)
                <button type="button" class="vy-gb-node" x-bind:class="{ 'is-on': step === {{ $i + 1 }}, 'is-done': step > {{ $i + 1 }} }" x-on:click="goto({{ $i + 1 }})">
                    <span class="vy-gb-circle"><span class="vy-gb-circle-num">{{ $i + 1 }}</span><svg class="vy-gb-circle-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg></span>
                    <span class="vy-gb-node-label">{{ $label }}</span>
                </button>
            @endforeach
        </div>

        <div class="vy-gb-body vy-fade">
            {{-- ═══ The steps (a single POST form wraps them; the dates change via a GET nav, never a nested form) ═══ --}}
            <form method="POST" action="{{ route('app.group-booking') }}" class="vy-gb-steps">
                @csrf
                <input type="hidden" name="check_in" x-bind:value="checkIn">
                <input type="hidden" name="check_out" x-bind:value="checkOut">
                <input type="hidden" name="name" x-bind:value="name">
                <input type="hidden" name="occasion" x-bind:value="occasion">
                <input type="hidden" name="adults" value="2">
                <input type="hidden" name="children" value="0">
                <input type="hidden" name="hold_type" x-bind:value="holdType">
                <input type="hidden" name="pickup_deadline" x-bind:value="pickupDeadline">
                <input type="hidden" name="billing_mode" x-bind:value="billingMode">
                {{-- selected units → real POST inputs --}}
                <template x-for="u in selected" x-bind:key="u.id"><input type="hidden" name="units[]" x-bind:value="u.id"></template>

                {{-- STEP 1 · Block --}}
                <section class="vy-gb-step" x-show="step === 1" x-cloak>
                    <h2 class="vy-gb-h">The group</h2>
                    <p class="vy-gb-sub">Name the block, the occasion, and roughly how many guests.</p>

                    <div class="vy-gb-field"><label class="vy-gb-label">Group name</label>
                        <input type="text" class="vy-gb-input" x-model="name" placeholder="e.g. Okafor–Bello Wedding" autocomplete="off">
                    </div>
                    <div class="vy-gb-field"><label class="vy-gb-label">Occasion</label>
                        <div class="vy-gb-chips">
                            @foreach ($occasions as $occ)
                                <button type="button" class="vy-gb-chip" x-bind:class="occasion === '{{ $occ['value'] }}' ? 'is-on' : ''" x-on:click="occasion = '{{ $occ['value'] }}'">{{ $occ['label'] }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="vy-gb-row2">
                        <div class="vy-gb-field"><label class="vy-gb-label">Stay dates</label>
                            <div class="vy-gb-dates">
                                <input type="date" class="vy-gb-input" x-model="ci">
                                <span class="vy-gb-dates-arrow">→</span>
                                <input type="date" class="vy-gb-input" x-model="co">
                                <button type="button" class="vy-gb-datesbtn" x-on:click="applyDates()" x-bind:disabled="! ci || ! co || co <= ci">Check availability</button>
                            </div>
                            <div class="vy-gb-hint" x-text="datesLabel"></div>
                        </div>
                        <div class="vy-gb-field"><label class="vy-gb-label">Approx. headcount</label>
                            <div class="vy-gb-counter">
                                <button type="button" x-on:click="headcount = Math.max(1, headcount - 1)" aria-label="Fewer">−</button>
                                <span x-text="headcount + ' guests'"></span>
                                <button type="button" x-on:click="headcount = Math.min(80, headcount + 1)" aria-label="More">+</button>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- STEP 2 · Units + capacity meter --}}
                <section class="vy-gb-step" x-show="step === 2" x-cloak>
                    <h2 class="vy-gb-h">Build the block</h2>
                    <p class="vy-gb-sub" x-text="'Select the units to hold for ' + datesShort + '. Rates are per stay.'"></p>

                    {{-- Capacity meter — REAL sleeps vs headcount --}}
                    <div class="vy-gb-cap">
                        <div class="vy-gb-cap-head">
                            <span x-text="'Sleeps ' + cap + ' of ' + headcount + ' guests'"></span>
                            <span x-bind:style="'color:' + (capOk ? '#1F7A52' : '#8F3110')" x-text="capOk ? 'Enough beds' : 'Add more beds'"></span>
                        </div>
                        <div class="vy-gb-cap-bar"><div class="vy-gb-cap-fill" x-bind:class="capOk ? 'is-ok' : ''" x-bind:style="'width:' + capPct + '%'"></div></div>
                    </div>

                    @if ($roomTypes === [])
                        <div class="vy-gb-empty">No free, priced units for these dates. Change the dates in step 1.</div>
                    @endif
                    @foreach ($roomTypes as $rt)
                        <div class="vy-gb-rt">
                            <div class="vy-gb-rt-head"><span class="vy-gb-rt-name">{{ $rt['name'] }}</span><span class="vy-gb-rt-price">{{ $rt['price'] }} · stay</span></div>
                            <div class="vy-gb-units">
                                @foreach ($rt['units'] as $unit)
                                    <label class="vy-gb-unit" x-bind:class="isSelected('{{ $unit['id'] }}') ? 'is-on' : ''">
                                        <input type="checkbox" x-bind:checked="isSelected('{{ $unit['id'] }}')"
                                            x-on:change="toggleUnit({ id: '{{ $unit['id'] }}', code: @js($unit['code']), rt: @js($rt['name']), priceMinor: {{ $rt['price_minor'] }}, sleeps: {{ $unit['sleeps'] }} }, $event.target.checked)">
                                        <span class="vy-gb-unit-code">{{ $unit['code'] }}</span>
                                        <span class="vy-gb-unit-sleeps">sleeps {{ $unit['sleeps'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </section>

                {{-- STEP 3 · Organiser (rooming list is added in 4.3) --}}
                <section class="vy-gb-step" x-show="step === 3" x-cloak>
                    <h2 class="vy-gb-h">Organiser</h2>
                    <p class="vy-gb-sub">Who's booking the block? Contact details stay private (encrypted).</p>
                    <div class="vy-gb-row2">
                        <div class="vy-gb-field"><label class="vy-gb-label">First name</label><input type="text" name="organiser_first_name" class="vy-gb-input" x-model="orgFirst" autocomplete="off"></div>
                        <div class="vy-gb-field"><label class="vy-gb-label">Last name</label><input type="text" name="organiser_last_name" class="vy-gb-input" x-model="orgLast" autocomplete="off"></div>
                        <div class="vy-gb-field"><label class="vy-gb-label">Company · optional</label><input type="text" name="organiser_company" class="vy-gb-input" autocomplete="off"></div>
                        <div class="vy-gb-field"><label class="vy-gb-label">Email · optional</label><input type="email" name="organiser_email" class="vy-gb-input" autocomplete="off"></div>
                        <div class="vy-gb-field"><label class="vy-gb-label">Phone · optional</label><input type="text" name="organiser_phone" class="vy-gb-input" autocomplete="off"></div>
                    </div>
                </section>

                {{-- STEP 4 · Terms — hold + billing (discount/deposit added in 4.2) --}}
                <section class="vy-gb-step" x-show="step === 4" x-cloak>
                    <h2 class="vy-gb-h">Rate &amp; hold</h2>
                    <p class="vy-gb-sub">How you're holding the block, and who settles the bill.</p>
                    <div class="vy-gb-row2">
                        <div>
                            <div class="vy-gb-label">Hold type</div>
                            <label class="vy-gb-opt" x-bind:class="holdType === 'confirmed' ? 'is-on' : ''"><input type="radio" x-model="holdType" value="confirmed"><span><strong>Confirmed</strong><br><span class="vy-gb-opt-sub">Units held firm on the rack</span></span></label>
                            <label class="vy-gb-opt" x-bind:class="holdType === 'tentative' ? 'is-on' : ''"><input type="radio" x-model="holdType" value="tentative"><span><strong>Tentative hold</strong><br><span class="vy-gb-opt-sub">Option expires if not confirmed</span></span></label>
                            <div class="vy-gb-field" x-show="holdType === 'tentative'" x-cloak style="margin-top:8px;"><label class="vy-gb-label">Option held until</label><input type="date" class="vy-gb-input" x-model="pickupDeadline"></div>
                        </div>
                        <div>
                            <div class="vy-gb-label">Payment</div>
                            <label class="vy-gb-opt" x-bind:class="billingMode === 'individual' ? 'is-on' : ''"><input type="radio" x-model="billingMode" value="individual"><span><strong>Individual pay</strong><br><span class="vy-gb-opt-sub">Each unit gets its own folio</span></span></label>
                            <label class="vy-gb-opt" x-bind:class="billingMode === 'master' ? 'is-on' : ''"><input type="radio" x-model="billingMode" value="master"><span><strong>Master invoice</strong><br><span class="vy-gb-opt-sub">Organiser settles one bill for the block</span></span></label>
                        </div>
                    </div>
                </section>

                {{-- STEP 5 · Review (fleshed out with agree + done in 4.4) --}}
                <section class="vy-gb-step" x-show="step === 5" x-cloak>
                    <h2 class="vy-gb-h">Review the block</h2>
                    <p class="vy-gb-sub">Check the details, then confirm. Every unit is held together — all or none.</p>
                    <div class="vy-gb-review">
                        <div class="vy-gb-rev-row"><span>Group</span><strong x-text="(name || '—') + ' · ' + occasionLabel"></strong></div>
                        <div class="vy-gb-rev-row"><span>Dates</span><strong x-text="datesShort + ' · ' + nights + (nights === 1 ? ' night' : ' nights')"></strong></div>
                        <div class="vy-gb-rev-row"><span>Headcount</span><strong x-text="headcount + ' guests · sleeps ' + cap"></strong></div>
                        <div class="vy-gb-rev-row"><span>Organiser</span><strong x-text="(orgFirst + ' ' + orgLast).trim() || '—'"></strong></div>
                        <div class="vy-gb-rev-row"><span>Hold</span><strong x-text="(holdType === 'tentative' ? 'Tentative' : 'Confirmed') + ' · ' + (billingMode === 'master' ? 'master invoice' : 'individual folios')"></strong></div>
                        <div class="vy-gb-rev-units">
                            <div class="vy-gb-rev-units-h" x-text="'Units · ' + count"></div>
                            <template x-for="u in selected" x-bind:key="u.id">
                                <div class="vy-gb-rev-unit"><span x-text="u.code + ' · ' + u.rt"></span><span x-text="fmt(u.priceMinor)"></span></div>
                            </template>
                        </div>
                    </div>
                </section>

                {{-- Footer nav --}}
                <div class="vy-gb-nav">
                    <button type="button" class="vy-gb-btn vy-gb-btn--ghost" x-show="step > 1" x-on:click="prev()">Back</button>
                    <span style="flex:1"></span>
                    <button type="button" class="vy-gb-btn vy-gb-btn--accent" x-show="step < 5" x-on:click="next()" x-bind:disabled="! canLeave(step)">Continue</button>
                    @unless ($__gbReadOnly)
                        <button type="submit" class="vy-gb-btn vy-gb-btn--accent" x-show="step === 5" x-bind:disabled="count === 0 || ! name.trim()">Confirm block</button>
                    @endunless
                </div>
            </form>

            {{-- ═══ Sticky live pricing rail (accommodation subtotal; discount/deposit/tax lines in 4.2) ═══ --}}
            <aside class="vy-gb-rail">
                <div class="vy-gb-rail-card">
                    <div class="vy-gb-rail-title">Block summary</div>
                    <div class="vy-gb-rail-line"><span x-text="count + (count === 1 ? ' unit' : ' units') + ' · sleeps ' + cap"></span><span></span></div>
                    <div class="vy-gb-rail-line"><span>Accommodation</span><span class="vy-u-code" x-text="fmt(blockSubMinor)"></span></div>
                    <div class="vy-gb-rail-total"><span>Block total</span><span class="vy-u-code" x-text="fmt(blockSubMinor)"></span></div>
                    <div class="vy-gb-rail-note">Taxes &amp; fees are posted to the real folio on confirm — the authoritative total is the folio.</div>
                </div>
            </aside>
        </div>

        <script>
            function groupBookingWizard(currency, roomTypes, checkIn, checkOut) {
                return {
                    currency, roomTypes, checkIn, checkOut,
                    ci: checkIn, co: checkOut,
                    step: 1,
                    name: '', occasion: 'wedding', headcount: 10,
                    orgFirst: '', orgLast: '',
                    selected: [],
                    holdType: 'confirmed', billingMode: 'individual', pickupDeadline: checkIn,
                    init() {},
                    // dates change → GET nav (re-reads availability server-side, invariant #20). LOCAL date strings
                    // straight from the inputs — never toISOString (which shifts the day for +UTC users).
                    applyDates() { if (this.ci && this.co && this.co > this.ci) { window.location = '?check_in=' + this.ci + '&check_out=' + this.co; } },
                    get nights() { if (! this.checkIn || ! this.checkOut) return 0; return Math.round((new Date(this.checkOut + 'T00:00:00') - new Date(this.checkIn + 'T00:00:00')) / 86400000); },
                    get datesShort() { return this.checkIn && this.checkOut ? this.fmtDate(this.checkIn) + '–' + this.fmtDate(this.checkOut) : '—'; },
                    get datesLabel() { return this.checkIn && this.checkOut ? this.datesShort + ' · ' + this.nights + (this.nights === 1 ? ' night' : ' nights') : 'Pick the block dates'; },
                    fmtDate(s) { const d = new Date(s + 'T00:00:00'); return d.getDate() + ' ' + ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getMonth()]; },
                    get occasionLabel() { const m = { wedding: 'Wedding', corporate: 'Corporate', family: 'Family', sports: 'Sports', other: 'Other' }; return m[this.occasion] || 'Other'; },
                    toggleUnit(u, checked) {
                        if (checked) { if (! this.selected.some(x => x.id === u.id)) this.selected.push(u); }
                        else { this.selected = this.selected.filter(x => x.id !== u.id); }
                    },
                    isSelected(id) { return this.selected.some(u => u.id === id); },
                    get count() { return this.selected.length; },
                    get cap() { return this.selected.reduce((s, u) => s + u.sleeps, 0); },
                    get capOk() { return this.cap >= this.headcount; },
                    get capPct() { return this.headcount > 0 ? Math.min(100, Math.round(this.cap / this.headcount * 100)) : 0; },
                    get blockSubMinor() { return this.selected.reduce((s, u) => s + u.priceMinor, 0); },
                    fmt(minor) { const sym = this.currency === 'EUR' ? '€' : (this.currency === 'USD' ? '$' : this.currency + ' '); return sym + (minor / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                    goto(n) { this.step = n; },
                    next() { if (this.step < 5 && this.canLeave(this.step)) this.step++; },
                    prev() { if (this.step > 1) this.step--; },
                    canLeave(n) {
                        if (n === 1) return this.name.trim() !== '' && !! this.checkIn && !! this.checkOut;
                        if (n === 2) return this.count > 0;
                        return true;   // organiser/terms optional-ish; the server re-validates the real block
                    },
                };
            }
        </script>

        <style>
            [x-cloak] { display: none !important; }
            .vy-gb-standalone { position: relative; z-index: 2; max-width: 1180px; margin: 0 auto; padding: 24px 30px 60px; box-sizing: border-box; }
            .vy-gb-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
            .vy-gb-brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
            .vy-gb-logo { width: 36px; height: 36px; border-radius: 10px; background: var(--vy-grad); color: #fff; font-family: var(--vy-display); font-weight: 600; font-size: 18px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); flex: none; }
            .vy-gb-brand-title { display: block; font-family: var(--vy-display); font-weight: 600; font-size: 18px; letter-spacing: -0.02em; line-height: 1.2; }
            .vy-gb-brand-sub { display: block; font-size: 12px; color: var(--vy-faint); line-height: 1.2; }
            .vy-gb-topright { display: flex; align-items: center; gap: 12px; }
            .vy-gb-stepcount { font-size: 12px; color: var(--vy-faint); font-variant-numeric: tabular-nums; }
            .vy-gb-close { width: 36px; height: 36px; border-radius: 50%; background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); color: var(--vy-muted); display: grid; place-items: center; text-decoration: none; transition: background 0.14s; flex: none; }
            .vy-gb-close:hover { background: rgba(255, 255, 255, 0.9); color: var(--vy-ink); }
            .vy-gb-flash { padding: 11px 16px; border-radius: 12px; background: rgba(232, 112, 58, 0.1); border: 1px solid var(--vy-accent-border); color: var(--vy-ink); font-size: 13px; margin-bottom: 14px; }
            .vy-gb-ro { padding: 11px 16px; border-radius: 12px; background: rgba(217, 158, 61, 0.1); border: 1px solid rgba(217, 158, 61, 0.3); color: #7A5A1E; font-size: 13px; margin-bottom: 14px; }
            /* stepper */
            .vy-gb-stepper { display: flex; align-items: center; gap: 4px; margin-bottom: 20px; flex-wrap: wrap; }
            .vy-gb-node { display: inline-flex; align-items: center; gap: 8px; padding: 6px 10px; border: none; background: none; cursor: pointer; }
            .vy-gb-circle { position: relative; width: 26px; height: 26px; border-radius: 50%; border: 1px solid var(--vy-hairline); display: grid; place-items: center; font-size: 12px; font-weight: 700; color: var(--vy-faint); background: rgba(255, 255, 255, 0.5); transition: all 0.15s; }
            .vy-gb-node.is-on .vy-gb-circle { border-color: transparent; background: var(--vy-grad); color: #fff; box-shadow: var(--vy-shadow-accent); }
            .vy-gb-node.is-done .vy-gb-circle { border-color: transparent; background: rgba(31, 122, 82, 0.15); color: #1F7A52; }
            .vy-gb-circle-check { position: absolute; opacity: 0; }
            .vy-gb-node.is-done .vy-gb-circle-num { opacity: 0; }
            .vy-gb-node.is-done .vy-gb-circle-check { opacity: 1; }
            .vy-gb-node-label { font-size: 12.5px; font-weight: 500; color: var(--vy-faint); }
            .vy-gb-node.is-on .vy-gb-node-label, .vy-gb-node.is-done .vy-gb-node-label { color: var(--vy-ink); }
            /* body: steps + rail */
            .vy-gb-body { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; }
            .vy-gb-steps { background: rgba(255, 252, 248, 0.72); backdrop-filter: blur(26px) saturate(1.2); -webkit-backdrop-filter: blur(26px) saturate(1.2); border: 1px solid var(--vy-glass-border); border-radius: 20px; padding: 26px 28px; }
            .vy-gb-h { font-family: var(--vy-display); font-weight: 600; font-size: 20px; letter-spacing: -0.02em; color: var(--vy-ink); margin: 0; }
            .vy-gb-sub { font-size: 13px; color: var(--vy-muted); margin: 4px 0 20px; }
            .vy-gb-field { margin-bottom: 16px; }
            .vy-gb-label { display: block; font-size: 12px; font-weight: 600; color: var(--vy-ink); margin-bottom: 7px; }
            .vy-gb-input { width: 100%; box-sizing: border-box; padding: 10px 13px; border-radius: 11px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.6); font-family: var(--vy-sans); font-size: 13.5px; color: var(--vy-ink); }
            .vy-gb-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
            .vy-gb-chips, .vy-gb-dates { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
            .vy-gb-chip { padding: 8px 14px; border-radius: 20px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.5); font-size: 13px; color: var(--vy-ink); cursor: pointer; }
            .vy-gb-chip.is-on { background: var(--vy-grad); color: #fff; border-color: transparent; box-shadow: var(--vy-shadow-accent); }
            .vy-gb-dates .vy-gb-input { width: auto; flex: 1; min-width: 0; }
            .vy-gb-dates-arrow { color: var(--vy-faint); }
            .vy-gb-datesbtn { padding: 10px 14px; border-radius: 11px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.7); color: var(--vy-ink); font-size: 12.5px; font-weight: 600; cursor: pointer; white-space: nowrap; }
            .vy-gb-hint { font-size: 12px; color: var(--vy-faint); margin-top: 8px; }
            .vy-gb-counter { display: inline-flex; align-items: center; gap: 14px; padding: 8px 14px; border-radius: 11px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.5); font-size: 13.5px; font-weight: 600; }
            .vy-gb-counter button { width: 26px; height: 26px; border-radius: 8px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.7); color: var(--vy-ink); font-size: 16px; line-height: 1; cursor: pointer; }
            /* capacity */
            .vy-gb-cap { padding: 12px 14px; border-radius: 12px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.45); margin-bottom: 16px; }
            .vy-gb-cap-head { display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: var(--vy-ink); margin-bottom: 8px; }
            .vy-gb-cap-bar { height: 7px; border-radius: 20px; background: rgba(60, 40, 24, 0.08); overflow: hidden; }
            .vy-gb-cap-fill { height: 100%; border-radius: 20px; background: linear-gradient(90deg, #E8703A, #C2410C); transition: width 0.2s; }
            .vy-gb-cap-fill.is-ok { background: linear-gradient(90deg, #47A06E, #1F8A5B); }
            /* units */
            .vy-gb-rt { border: 1px solid var(--vy-hairline); border-radius: 12px; padding: 12px 14px; margin-bottom: 10px; }
            .vy-gb-rt-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 9px; }
            .vy-gb-rt-name { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
            .vy-gb-rt-price { font-size: 12.5px; color: var(--vy-accent); font-weight: 600; }
            .vy-gb-units { display: flex; flex-wrap: wrap; gap: 8px; }
            .vy-gb-unit { display: inline-flex; flex-direction: column; gap: 1px; align-items: flex-start; font-size: 12.5px; color: var(--vy-ink); background: rgba(255, 255, 255, 0.6); border: 1px solid var(--vy-hairline); border-radius: 10px; padding: 8px 12px; cursor: pointer; transition: border-color 0.14s, background 0.14s; }
            .vy-gb-unit input { position: absolute; opacity: 0; width: 0; height: 0; }
            .vy-gb-unit.is-on { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.08); }
            .vy-gb-unit-code { font-weight: 600; }
            .vy-gb-unit-sleeps { font-size: 10.5px; color: var(--vy-faint); }
            .vy-gb-empty { padding: 24px; text-align: center; color: var(--vy-muted); font-size: 13px; }
            /* options */
            .vy-gb-opt { display: flex; align-items: flex-start; gap: 9px; padding: 11px 13px; border: 1px solid var(--vy-hairline); border-radius: 12px; margin-bottom: 8px; cursor: pointer; font-size: 13px; color: var(--vy-ink); }
            .vy-gb-opt.is-on { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.06); }
            .vy-gb-opt-sub { font-size: 11.5px; color: var(--vy-muted); }
            /* review */
            .vy-gb-review { border: 1px solid var(--vy-hairline); border-radius: 14px; padding: 6px 16px; }
            .vy-gb-rev-row { display: flex; justify-content: space-between; gap: 16px; padding: 11px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; color: var(--vy-muted); }
            .vy-gb-rev-row strong { color: var(--vy-ink); font-weight: 600; text-align: right; }
            .vy-gb-rev-units { padding: 12px 0; }
            .vy-gb-rev-units-h { font-size: 12px; font-weight: 600; color: var(--vy-ink); margin-bottom: 8px; }
            .vy-gb-rev-unit { display: flex; justify-content: space-between; font-size: 12.5px; color: var(--vy-muted); padding: 4px 0; }
            /* nav */
            .vy-gb-nav { display: flex; align-items: center; gap: 10px; margin-top: 22px; }
            .vy-gb-btn { padding: 11px 20px; border-radius: 12px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.6); color: var(--vy-ink); font-family: var(--vy-sans); font-size: 13.5px; font-weight: 600; cursor: pointer; }
            .vy-gb-btn--accent { background: var(--vy-grad); color: #fff; border-color: transparent; box-shadow: var(--vy-shadow-accent); }
            .vy-gb-btn[disabled] { opacity: 0.5; pointer-events: none; }
            /* rail */
            .vy-gb-rail { position: sticky; top: 20px; }
            .vy-gb-rail-card { background: rgba(255, 252, 248, 0.72); backdrop-filter: blur(26px) saturate(1.2); -webkit-backdrop-filter: blur(26px) saturate(1.2); border: 1px solid var(--vy-glass-border); border-radius: 18px; padding: 18px 20px; }
            .vy-gb-rail-title { font-family: var(--vy-display); font-weight: 600; font-size: 14px; color: var(--vy-ink); margin-bottom: 12px; }
            .vy-gb-rail-line { display: flex; justify-content: space-between; font-size: 12.5px; color: var(--vy-muted); padding: 6px 0; }
            .vy-gb-rail-total { display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; color: var(--vy-ink); padding: 10px 0 6px; border-top: 1px solid var(--vy-hairline); margin-top: 4px; }
            .vy-u-code { font-variant-numeric: tabular-nums; }
            .vy-gb-rail-note { font-size: 11px; color: var(--vy-faint); line-height: 1.45; margin-top: 8px; }
            @media (max-width: 900px) { .vy-gb-body { grid-template-columns: 1fr; } .vy-gb-rail { position: static; } .vy-gb-row2 { grid-template-columns: 1fr; } .vy-gb-node-label { display: none; } }
        </style>
    </div>
@endif
