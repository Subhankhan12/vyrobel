{{-- Operator GROUP BOOKING (GATE 4 exact-match) — a standalone frosted 5-step WIZARD (Block · Units · Organiser
     · Terms · Review) over the CERTIFIED atomic block path (AssignmentService::createGroupBooking → all-or-none).
     The operator LAYOUT supplies the warm gradient + orbs + tokens; we drop the app shell for a focused create
     flow (the New-Reservation B.1 pattern). Availability is a REAL server read (dates ride a GET, invariant #20);
     steps 2–5 are client-side Alpine over the units this render scoped + loaded; the block is the guarded POST.
     MONEY: the pricing rail is a DISPLAY-only sum of INTEGER minor units — the authoritative folio is re-derived
     + posted server-side (GUARD 1). CAPACITY: real units.max_occupancy vs the real headcount — never fabricated.
     RAW-BLADE RULE: Alpine uses x-on:/x-model/x-bind (no `@click`); every `@` follows a space/newline/tag. --}}
@php $__gbReadOnly = app(\App\Support\Operator\OperatorWorkspace::class)->isReadOnly(); @endphp
@if ($done)
    {{-- ── DONE screen (the wireframe's success card). Landed here by PRG after a real atomic create. "View on
         the rack" keeps the rack one click away. Single Livewire root; self-contained <style> (the wizard's
         style block only renders in the wizard branch — the New-Reservation done-screen lesson). ── --}}
    <div class="vy-gb-standalone">
        <div class="vy-gb-topbar vy-fade">
            <div class="vy-gb-brand"><span class="vy-gb-logo">V</span><span class="vy-gb-brand-id"><h1 class="vy-gb-brand-title" style="margin:0;">{{ $done['title'] }}</h1><span class="vy-gb-brand-sub">{{ __('livewire/operator/group-booking.brand_sub', ['property' => $subtitle]) }}</span></span></div>
            <a class="vy-gb-close" href="{{ $done['rack_url'] }}" title="{{ __('common.close') }}" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg></a>
        </div>
        <div class="vy-gb-donewrap vy-fade">
            <div class="vy-gb-donecheck"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg></div>
            <div class="vy-gb-donetitle">{{ $done['title'] }}</div>
            <div class="vy-gb-doneref">{!! __('livewire/operator/group-booking.done_reference', ['reference' => e($done['reference']), 'sub' => e($done['sub'])]) !!}</div>
            <div class="vy-gb-donecard">
                <div class="vy-gb-drow"><span>{{ __('livewire/operator/group-booking.organiser') }}</span><strong>{{ $done['organiser'] }}</strong></div>
                <div class="vy-gb-drow"><span>{{ __('livewire/operator/group-booking.units_and_dates') }}</span><strong>{{ trans_choice('livewire/operator/group-booking.done_units_dates', $done['units'], ['count' => $done['units'], 'dates' => $done['dates']]) }}</strong></div>
                <div class="vy-gb-drow"><span>{{ __('livewire/operator/group-booking.block_total') }}</span><strong>{{ $done['total'] }}</strong></div>
                @if ($done['deposit'])
                    <div class="vy-gb-drow"><span>{{ __('livewire/operator/group-booking.deposit_taken') }}</span><strong>{{ $done['deposit'] }}</strong></div>
                @endif
            </div>
            <div class="vy-gb-doneactions">
                <a href="{{ $done['rack_url'] }}" class="vy-gb-btn vy-gb-btn--accent">{{ __('livewire/operator/group-booking.view_on_rack') }}</a>
                <a href="{{ $done['new_url'] }}" class="vy-gb-btn">{{ __('livewire/operator/group-booking.create_another') }}</a>
            </div>
            {{-- ⚠️ THE DRAFT DIES WITH THE BLOCK IT BECAME. Without this, "Create another block" would open the
                 wizard pre-filled with the group that was just created — and the second block would inherit the
                 first one's name silently. A draft is a recovery aid, never a default. --}}
            <script>try { sessionStorage.removeItem('vyrobel:group-booking:draft'); } catch (e) {}</script>
        </div>
        <style>
            /* done-screen chrome, self-contained (the wizard style block renders only in the wizard branch) */
            .vy-gb-standalone { position: relative; z-index: 2; max-width: 1180px; margin: 0 auto; padding: 24px 30px 60px; box-sizing: border-box; }
            .vy-gb-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
            .vy-gb-brand { display: flex; align-items: center; gap: 12px; }
            .vy-gb-logo { width: 36px; height: 36px; border-radius: 10px; background: var(--vy-grad); color: #fff; font-family: var(--vy-display); font-weight: 600; font-size: 18px; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); flex: none; }
            .vy-gb-brand-title { display: block; font-family: var(--vy-display); font-weight: 600; font-size: 18px; letter-spacing: -0.02em; line-height: 1.2; }
            .vy-gb-brand-sub { display: block; font-size: 12px; color: var(--vy-faint); line-height: 1.2; }
            .vy-gb-close { width: 36px; height: 36px; border-radius: 50%; background: rgba(255, 252, 248, 0.58); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15); border: 1px solid var(--vy-glass-border); color: var(--vy-muted); display: grid; place-items: center; text-decoration: none; flex: none; }
            .vy-gb-donewrap { max-width: 460px; margin: 26px auto 0; padding: 40px 34px 34px; text-align: center; border-radius: 22px; background: rgba(255, 252, 248, 0.72); backdrop-filter: blur(26px) saturate(1.2); -webkit-backdrop-filter: blur(26px) saturate(1.2); border: 1px solid var(--vy-glass-border); box-shadow: 0 22px 60px -24px rgba(60, 40, 24, 0.32); }
            .vy-gb-donecheck { width: 62px; height: 62px; margin: 0 auto 16px; border-radius: 50%; background: var(--vy-grad); color: #fff; display: grid; place-items: center; box-shadow: var(--vy-shadow-accent); }
            .vy-gb-donetitle { font-family: var(--vy-display); font-weight: 600; font-size: 22px; letter-spacing: -0.02em; color: var(--vy-ink); }
            .vy-gb-doneref { margin-top: 6px; font-size: 13.5px; color: var(--vy-muted); }
            .vy-gb-doneref .vy-u-code { font-weight: 600; color: var(--vy-ink); font-variant-numeric: tabular-nums; }
            .vy-gb-donecard { text-align: left; margin: 20px 0; border: 1px solid var(--vy-hairline); border-radius: 14px; padding: 4px 16px; }
            .vy-gb-drow { display: flex; justify-content: space-between; gap: 16px; padding: 11px 0; border-bottom: 1px solid var(--vy-hairline); font-size: 13px; color: var(--vy-muted); }
            .vy-gb-donecard .vy-gb-drow:last-child { border-bottom: none; }
            .vy-gb-drow strong { color: var(--vy-ink); font-weight: 600; text-align: right; }
            .vy-gb-doneactions { display: flex; gap: 10px; }
            .vy-gb-btn { flex: 1; padding: 12px 16px; border-radius: 12px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.6); color: var(--vy-ink); font-family: var(--vy-sans); font-size: 13.5px; font-weight: 600; text-decoration: none; text-align: center; }
            .vy-gb-btn--accent { background: var(--vy-grad); color: #fff; border-color: transparent; box-shadow: var(--vy-shadow-accent); }
        </style>
    </div>
@elseif (! $hasProperty)
    <div class="vy-gb-standalone">
        <div class="vy-gb-topbar vy-fade">
            <div class="vy-gb-brand"><span class="vy-gb-logo">V</span><span class="vy-gb-brand-id"><h1 class="vy-gb-brand-title" style="margin:0;">{{ __('livewire/operator/group-booking.heading') }}</h1><span class="vy-gb-brand-sub">{{ __('livewire/operator/group-booking.no_property') }}</span></span></div>
            <a class="vy-gb-close" href="{{ route('app.rack') }}" title="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg></a>
        </div>
        <div class="vy-gb-card vy-fade" style="text-align:center; padding: 40px 24px;">
            <strong style="display:block; font-size:15px; color:var(--vy-ink); margin-bottom:6px;">{{ __('livewire/operator/group-booking.no_property') }}</strong>
            <span style="font-size:13px; color:var(--vy-muted);">{{ __('livewire/operator/group-booking.add_property_first') }}</span>
        </div>
    </div>
@else
    @php $__gbMonths = array_map(fn (int $m) => \Carbon\CarbonImmutable::create(2000, $m, 1)->translatedFormat(\App\Support\I18n\LocalDate::pattern('M')), range(1, 12)); @endphp
    @php
        // I18N-01 rule 12 — every count the wizard shows gets its real plural here, never `n === 1 ?` in the script.
        // Guests: the headcount counter is clamped to 1 … 80 (and a restored draft is clamped to the same range), so 0 … 80.
        // Units: only the units rendered below can be ticked, so 0 … $freeUnitCount — exact, nothing can go past it.
        // Nights: the dates are the SERVER's (changing them is a page load), so the one count the script computes is
        // known here — the signed day difference between the two dates (what the script used to work out), 0 when either is missing.
        $__gbGuestMax = 80;
        $__gbCounts = ['headcountGuests' => [], 'sleepsOf' => [], 'headcountValue' => [], 'roomingList' => [], 'railUnits' => []];
        foreach (range(0, $__gbGuestMax) as $__n) {
            $__gbCounts['headcountGuests'][$__n] = trans_choice('livewire/operator/group-booking.headcount_guests', $__n, ['count' => $__n]);
            $__gbCounts['sleepsOf'][$__n] = trans_choice('livewire/operator/group-booking.sleeps_of_guests', $__n, ['count' => $__n, 'cap' => ':cap']);
            $__gbCounts['headcountValue'][$__n] = trans_choice('livewire/operator/group-booking.review_headcount_value', $__n, ['count' => $__n, 'cap' => ':cap']);
        }
        foreach (range(0, $freeUnitCount) as $__n) {
            $__gbCounts['roomingList'][$__n] = trans_choice('livewire/operator/group-booking.rooming_list', $__n, ['count' => $__n]);
            $__gbCounts['railUnits'][$__n] = trans_choice('livewire/operator/group-booking.rail_units', $__n, ['count' => $__n, 'cap' => ':cap']);
        }
        $__gbNightsN = ($checkIn !== null && $checkOut !== null)
            ? (int) round(\Carbon\CarbonImmutable::parse($checkIn)->diffInDays(\Carbon\CarbonImmutable::parse($checkOut), false))
            : 0;
        // trim(): dates typed into the URL back to front give a negative count, which no plural range matches, and
        // Laravel then answers with the form's leading space — the old script printed it without one.
        $__gbNightsText = trim(trans_choice('livewire/operator/group-booking.nights', $__gbNightsN, ['count' => $__gbNightsN]));
    @endphp
    <div class="vy-gb-standalone" x-data="groupBookingWizard(@js($currency), @js($roomTypes), @js((string) $checkIn), @js((string) $checkOut))" x-init="init()">
        {{-- Topbar --}}
        <div class="vy-gb-topbar vy-fade">
            <div class="vy-gb-brand">
                <span class="vy-gb-logo">V</span>
                <span class="vy-gb-brand-id">
                    <h1 class="vy-gb-brand-title" style="margin:0;">{{ __('livewire/operator/group-booking.heading') }}</h1>
                    <span class="vy-gb-brand-sub">{{ __('livewire/operator/group-booking.brand_sub', ['property' => $subtitle]) }}</span>
                </span>
            </div>
            <div class="vy-gb-topright">
                <span class="vy-gb-stepcount" x-text="t.stepOf.replace(':step', step)"></span>
                <a class="vy-gb-close" href="{{ route('app.rack') }}" title="{{ __('common.close') }}" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg></a>
            </div>
        </div>

        @if (session('group_notice'))
            <div class="vy-gb-flash vy-fade">{{ session('group_notice') }}</div>
        @endif
        @if ($__gbReadOnly)
            <div class="vy-gb-ro vy-fade" role="status">{{ __('livewire/operator/group-booking.read_only') }}</div>
        @endif

        {{-- Clickable 5-node stepper — the SHARED `.vy-wiz-*` stepper (see the operator layout), the same
             control New Reservation uses and the one both wizard prototypes draw: circular nodes joined by
             connector bars, with the labels beneath. This page previously had a compact variant that matched
             neither its own design nor its sibling wizard. --}}
        @php $__gbSteps = [__('livewire/operator/group-booking.step_block'), __('livewire/operator/group-booking.step_units'), __('livewire/operator/group-booking.step_organiser'), __('livewire/operator/group-booking.step_terms'), __('livewire/operator/group-booking.step_review')]; @endphp
        <div class="vy-wiz-stepper vy-fade">
            @foreach ($__gbSteps as $i => $label)
                @php $n = $i + 1; @endphp
                <div class="vy-wiz-node" x-bind:class="step === {{ $n }} ? 'is-on' : (step > {{ $n }} ? 'is-done' : '')">
                    <div class="vy-wiz-node-row">
                        <span class="vy-wiz-bar" @if ($i === 0) style="visibility:hidden" @endif></span>
                        <button type="button" class="vy-wiz-circle" x-on:click="goto({{ $n }})" aria-label="{{ __('livewire/operator/group-booking.go_to_step', ['step' => $label]) }}">
                            <span class="vy-wiz-circle-num">{{ $n }}</span>
                            <svg class="vy-wiz-circle-check" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                        </button>
                        <span class="vy-wiz-bar" @if ($i === count($__gbSteps) - 1) style="visibility:hidden" @endif></span>
                    </div>
                    <span class="vy-wiz-node-label">{{ $label }}</span>
                </div>
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
                <input type="hidden" name="discount_pct" x-bind:value="discountPct">
                <input type="hidden" name="deposit_pct" x-bind:value="billingMode === 'master' ? depositPct : 0">
                <input type="hidden" name="deposit_method" x-bind:value="depositMethod">
                {{-- selected units → real POST inputs --}}
                <template x-for="u in selected" x-bind:key="u.id"><input type="hidden" name="units[]" x-bind:value="u.id"></template>

                {{-- STEP 1 · Block --}}
                <section class="vy-gb-step" x-show="step === 1" x-cloak>
                    <h2 class="vy-gb-h">{{ __('livewire/operator/group-booking.group_heading') }}</h2>
                    <p class="vy-gb-sub">{{ __('livewire/operator/group-booking.group_sub') }}</p>

                    <div class="vy-gb-field"><label class="vy-gb-label">{{ __('livewire/operator/group-booking.group_name') }}</label>
                        <input id="vy-lbl-group-booking-e-g-okafor-bello-wedding" type="text" class="vy-gb-input" x-model="name" placeholder="{{ __('livewire/operator/group-booking.group_name_placeholder') }}" autocomplete="off">
                        {{-- ⚠️ THE MISSING THING IS NAMED BESIDE ITSELF. Continue used to be merely DISABLED, which
                             tells an operator that something is wrong and never which thing — on a five-step wizard
                             whose first step has three requirements, two of them further down the page. --}}
                        <p class="vy-gb-miss" x-show="attempted &amp;&amp; ! name.trim()" x-cloak>{{ __('livewire/operator/group-booking.missing_name') }}</p>
                    </div>
                    <div class="vy-gb-field"><label class="vy-gb-label">{{ __('livewire/operator/group-booking.occasion') }}</label>
                        <div class="vy-gb-chips">
                            @foreach ($occasions as $occ)
                                <button type="button" class="vy-gb-chip" x-bind:class="occasion === '{{ $occ['value'] }}' ? 'is-on' : ''" x-on:click="occasion = '{{ $occ['value'] }}'">{{ $occ['label'] }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="vy-gb-row2">
                        <div class="vy-gb-field"><label class="vy-gb-label">{{ __('livewire/operator/group-booking.stay_dates') }}</label>
                            <div class="vy-gb-dates">
                                {{-- ⚠️ F-8 — the visible "Stay dates" label above names the PAIR, not either
                                     input, and was never associated with either. These two hidden labels
                                     name each end of the range; the text is derived from the authored
                                     label plus the → between them, not invented. --}}
                                <label class="vy-sr-only" for="vy-lbl-group-booking-check-in">{{ __('livewire/operator/group-booking.stay_dates_check_in') }}</label>
                                <input type="date" id="vy-lbl-group-booking-check-in" class="vy-gb-input" x-model="ci">
                                <span class="vy-gb-dates-arrow" aria-hidden="true">→</span>
                                <label class="vy-sr-only" for="vy-lbl-group-booking-check-out">{{ __('livewire/operator/group-booking.stay_dates_check_out') }}</label>
                                <input type="date" id="vy-lbl-group-booking-check-out" class="vy-gb-input" x-model="co">
                                <button type="button" class="vy-gb-datesbtn" x-on:click="applyDates()" x-bind:disabled="! ci || ! co || co <= ci">{{ __('livewire/operator/group-booking.check_availability') }}</button>
                            </div>
                            <div class="vy-gb-hint" x-text="datesLabel"></div>
                            {{-- ⚠️⚠️ THE SUBTLE HALF, AND THE ONE THAT ACTUALLY STRANDED PEOPLE. `ci`/`co` are what
                                 the operator typed; `checkIn`/`checkOut` are what the SERVER has loaded availability
                                 for. Continue needs the applied pair, so picking dates and never pressing "Check
                                 availability" leaves the wizard refusing to advance with both date boxes visibly
                                 filled in. That is unexplainable from the screen, so it is explained here. --}}
                            <p class="vy-gb-miss" x-show="attempted &amp;&amp; (! checkIn || ! checkOut)" x-cloak
                               x-text="(ci &amp;&amp; co &amp;&amp; co &gt; ci) ? @js(__('livewire/operator/group-booking.missing_apply_dates')) : @js(__('livewire/operator/group-booking.missing_dates'))"></p>
                        </div>
                        <div class="vy-gb-field"><label class="vy-gb-label">{{ __('livewire/operator/group-booking.headcount_label') }}</label>
                            <div class="vy-gb-counter">
                                <button type="button" x-on:click="headcount = Math.max(1, headcount - 1)" aria-label="{{ __('livewire/operator/group-booking.fewer') }}">−</button>
                                <span x-text="tc(t.headcountGuests, headcount)"></span>
                                <button type="button" x-on:click="headcount = Math.min(@js($__gbGuestMax), headcount + 1)" aria-label="{{ __('livewire/operator/group-booking.more') }}">+</button>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- STEP 2 · Units + capacity meter --}}
                <section class="vy-gb-step" x-show="step === 2" x-cloak>
                    <h2 class="vy-gb-h">{{ __('livewire/operator/group-booking.build_heading') }}</h2>
                    <p class="vy-gb-sub" x-text="t.buildSub.replace(':dates', datesShort)"></p>

                    {{-- Capacity meter — REAL sleeps vs headcount --}}
                    <div class="vy-gb-cap">
                        <div class="vy-gb-cap-head">
                            <span x-text="tc(t.sleepsOf, headcount).replace(':cap', cap)"></span>
                            <span x-bind:style="'color:' + (capOk ? '#1F7A52' : '#8F3110')" x-text="capOk ? @js(__('livewire/operator/group-booking.enough_beds')) : @js(__('livewire/operator/group-booking.add_more_beds'))"></span>
                        </div>
                        <div class="vy-gb-cap-bar"><div class="vy-gb-cap-fill" x-bind:class="capOk ? 'is-ok' : ''" x-bind:style="'width:' + capPct + '%'"></div></div>
                    </div>

                    {{-- ⚠️ Step 2's own requirement, named where the operator is looking. The capacity meter above
                         reports beds-vs-heads and says nothing about the real blocker, which is that no unit is
                         ticked at all. --}}
                    <p class="vy-gb-miss" x-show="attempted &amp;&amp; count === 0" x-cloak>{{ __('livewire/operator/group-booking.missing_units') }}</p>

                    @if ($roomTypes === [])
                        <div class="vy-gb-empty">{{ __('livewire/operator/group-booking.no_units_available') }}</div>
                    @endif
                    @foreach ($roomTypes as $rt)
                        <div class="vy-gb-rt">
                            <div class="vy-gb-rt-head"><span class="vy-gb-rt-name">{{ $rt['name'] }}</span><span class="vy-gb-rt-price">{{ __('livewire/operator/group-booking.price_per_stay', ['price' => $rt['price']]) }}</span></div>
                            <div class="vy-gb-units">
                                @foreach ($rt['units'] as $unit)
                                    <label class="vy-gb-unit" x-bind:class="isSelected('{{ $unit['id'] }}') ? 'is-on' : ''">
                                        <input type="checkbox" x-bind:checked="isSelected('{{ $unit['id'] }}')"
                                            x-on:change="toggleUnit({ id: '{{ $unit['id'] }}', code: @js($unit['code']), rt: @js($rt['name']), priceMinor: {{ $rt['price_minor'] }}, sleeps: {{ $unit['sleeps'] }} }, $event.target.checked)">
                                        <span class="vy-gb-unit-code">{{ $unit['code'] }}</span>
                                        <span class="vy-gb-unit-sleeps">{{ __('livewire/operator/group-booking.unit_sleeps', ['sleeps' => $unit['sleeps']]) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </section>

                {{-- STEP 3 · Organiser (rooming list is added in 4.3) --}}
                <section class="vy-gb-step" x-show="step === 3" x-cloak>
                    <h2 class="vy-gb-h">{{ __('livewire/operator/group-booking.organiser') }}</h2>
                    <p class="vy-gb-sub">{{ __('livewire/operator/group-booking.organiser_sub') }}</p>
                    <div class="vy-gb-row2">
                        <div class="vy-gb-field"><label class="vy-gb-label">{{ __('livewire/operator/group-booking.first_name') }}</label><input type="text" name="organiser_first_name" class="vy-gb-input" x-model="orgFirst" autocomplete="off"></div>
                        <div class="vy-gb-field"><label class="vy-gb-label">{{ __('livewire/operator/group-booking.last_name') }}</label><input type="text" name="organiser_last_name" class="vy-gb-input" x-model="orgLast" autocomplete="off"></div>
                        <div class="vy-gb-field"><label class="vy-gb-label">{{ __('livewire/operator/group-booking.company_optional') }}</label><input type="text" name="organiser_company" class="vy-gb-input" autocomplete="off"></div>
                        <div class="vy-gb-field"><label class="vy-gb-label">{{ __('livewire/operator/group-booking.email_optional') }}</label><input type="email" name="organiser_email" class="vy-gb-input" autocomplete="off"></div>
                        <div class="vy-gb-field"><label class="vy-gb-label">{{ __('livewire/operator/group-booking.phone_optional') }}</label><input type="text" name="organiser_phone" class="vy-gb-input" autocomplete="off"></div>
                    </div>

                    {{-- Rooming list — a lead-guest NAME per selected unit (optional; blank → the organiser leads it).
                         Name-only (#28) — no contact captured here. Persists to each member reservation's lead_guest. --}}
                    <div class="vy-gb-field" x-show="count > 0" x-cloak style="margin-top: 6px;">
                        <label class="vy-gb-label"><span x-text="tc(t.roomingList, count)"></span></label>
                        <template x-for="u in selected" x-bind:key="u.id">
                            <div class="vy-gb-rooming">
                                <span class="vy-gb-rooming-unit"><span x-text="u.code"></span> · <span class="vy-gb-faint" x-text="t.unitSleeps.replace(':sleeps', u.sleeps)"></span></span>
                                <label class="vy-sr-only" for="vy-lbl-group-booking-rooming-u-id">{{ __('livewire/operator/group-booking.lead_guest_tbc') }}</label>
                                <input id="vy-lbl-group-booking-rooming-u-id" type="text" class="vy-gb-input vy-gb-rooming-input" x-model="rooming[u.id]" x-bind:name="'rooming[' + u.id + ']'" placeholder="{{ __('livewire/operator/group-booking.lead_guest_tbc') }}" autocomplete="off">
                            </div>
                        </template>
                    </div>
                </section>

                {{-- STEP 4 · Terms — hold + billing (discount/deposit added in 4.2) --}}
                <section class="vy-gb-step" x-show="step === 4" x-cloak>
                    <h2 class="vy-gb-h">{{ __('livewire/operator/group-booking.terms_heading') }}</h2>
                    <p class="vy-gb-sub">{{ __('livewire/operator/group-booking.terms_sub') }}</p>
                    <div class="vy-gb-row2">
                        <div>
                            <div class="vy-gb-label">{{ __('livewire/operator/group-booking.hold_type') }}</div>
                            <label class="vy-gb-opt" x-bind:class="holdType === 'confirmed' ? 'is-on' : ''"><input type="radio" x-model="holdType" value="confirmed"><span><strong>{{ __('livewire/operator/group-booking.hold_confirmed') }}</strong><br><span class="vy-gb-opt-sub">{{ __('livewire/operator/group-booking.hold_confirmed_sub') }}</span></span></label>
                            <label class="vy-gb-opt" x-bind:class="holdType === 'tentative' ? 'is-on' : ''"><input type="radio" x-model="holdType" value="tentative"><span><strong>{{ __('livewire/operator/group-booking.hold_tentative') }}</strong><br><span class="vy-gb-opt-sub">{{ __('livewire/operator/group-booking.hold_tentative_sub') }}</span></span></label>
                            <div class="vy-gb-field" x-show="holdType === 'tentative'" x-cloak style="margin-top:8px;"><label class="vy-gb-label" for="vy-lbl-group-booking-pickup-deadline">{{ __('livewire/operator/group-booking.option_held_until') }}</label><input type="date" id="vy-lbl-group-booking-pickup-deadline" class="vy-gb-input" x-model="pickupDeadline"></div>
                        </div>
                        <div>
                            <div class="vy-gb-label">{{ __('livewire/operator/group-booking.payment') }}</div>
                            <label class="vy-gb-opt" x-bind:class="billingMode === 'individual' ? 'is-on' : ''"><input type="radio" x-model="billingMode" value="individual"><span><strong>{{ __('livewire/operator/group-booking.billing_individual') }}</strong><br><span class="vy-gb-opt-sub">{{ __('livewire/operator/group-booking.billing_individual_sub') }}</span></span></label>
                            <label class="vy-gb-opt" x-bind:class="billingMode === 'master' ? 'is-on' : ''"><input type="radio" x-model="billingMode" value="master"><span><strong>{{ __('livewire/operator/group-booking.billing_master') }}</strong><br><span class="vy-gb-opt-sub">{{ __('livewire/operator/group-booking.billing_master_sub') }}</span></span></label>
                        </div>
                    </div>

                    {{-- Group discount — a REAL negative folio line (re-derived server-side from the fixed set). --}}
                    <div class="vy-gb-field" style="margin-top: 18px;">
                        <label class="vy-gb-label">{{ __('livewire/operator/group-booking.group_discount') }}</label>
                        <div class="vy-gb-chips">
                            <template x-for="p in [0, 5, 10, 15]" x-bind:key="p">
                                <button type="button" class="vy-gb-chip" x-bind:class="discountPct === p ? 'is-on' : ''" x-on:click="discountPct = p" x-text="p + '%'"></button>
                            </template>
                        </div>
                    </div>

                    {{-- Deposit-to-confirm — a REAL cash/bank payment on the master invoice; card is the deferred
                         Stripe seam. Only on a master invoice (an individual-pay block has no single folio). --}}
                    <div class="vy-gb-field" style="margin-top: 4px;">
                        <label class="vy-gb-label">{{ __('livewire/operator/group-booking.deposit_to_confirm') }}</label>
                        <template x-if="billingMode === 'master'">
                            <div>
                                <div class="vy-gb-chips">
                                    <button type="button" class="vy-gb-chip" x-bind:class="depositPct === 0 ? 'is-on' : ''" x-on:click="depositPct = 0">{{ __('common.none') }}</button>
                                    <template x-for="p in [25, 50, 100]" x-bind:key="p">
                                        <button type="button" class="vy-gb-chip" x-bind:class="depositPct === p ? 'is-on' : ''" x-on:click="depositPct = p" x-text="p + '%'"></button>
                                    </template>
                                </div>
                                <div x-show="depositPct > 0" x-cloak style="margin-top: 8px;">
                                    <div class="vy-gb-chips">
                                        <button type="button" class="vy-gb-chip" x-bind:class="depositMethod === 'cash' ? 'is-on' : ''" x-on:click="depositMethod = 'cash'">{{ __('livewire/operator/group-booking.deposit_cash') }}</button>
                                        <button type="button" class="vy-gb-chip" x-bind:class="depositMethod === 'bank' ? 'is-on' : ''" x-on:click="depositMethod = 'bank'">{{ __('livewire/operator/group-booking.deposit_bank') }}</button>
                                        <button type="button" class="vy-gb-chip" x-bind:class="depositMethod === 'card' ? 'is-on' : ''" x-on:click="depositMethod = 'card'">{!! __('livewire/operator/group-booking.deposit_card') !!}</button>
                                    </div>
                                    <div class="vy-gb-hint" x-show="depositMethod === 'card'">{{ __('livewire/operator/group-booking.card_deposit_hint') }}</div>
                                </div>
                            </div>
                        </template>
                        <template x-if="billingMode !== 'master'">
                            <div class="vy-gb-hint">{{ __('livewire/operator/group-booking.deposit_master_only_hint') }}</div>
                        </template>
                    </div>
                </section>

                {{-- STEP 5 · Review (fleshed out with agree + done in 4.4) --}}
                <section class="vy-gb-step" x-show="step === 5" x-cloak>
                    <h2 class="vy-gb-h">{{ __('livewire/operator/group-booking.review_heading') }}</h2>
                    <p class="vy-gb-sub">{{ __('livewire/operator/group-booking.review_sub') }}</p>
                    <div class="vy-gb-review">
                        <div class="vy-gb-rev-row"><span>{{ __('livewire/operator/group-booking.review_group') }}</span><strong x-text="(name || '—') + ' · ' + occasionLabel"></strong></div>
                        <div class="vy-gb-rev-row"><span>{{ __('livewire/operator/group-booking.review_dates') }}</span><strong x-text="datesShort + ' · ' + t.nightsText"></strong></div>
                        <div class="vy-gb-rev-row"><span>{{ __('livewire/operator/group-booking.review_headcount') }}</span><strong x-text="tc(t.headcountValue, headcount).replace(':cap', cap)"></strong></div>
                        <div class="vy-gb-rev-row"><span>{{ __('livewire/operator/group-booking.organiser') }}</span><strong x-text="(orgFirst + ' ' + orgLast).trim() || '—'"></strong></div>
                        <div class="vy-gb-rev-row"><span>{{ __('livewire/operator/group-booking.review_hold') }}</span><strong x-text="(holdType === 'tentative' ? @js(__('livewire/operator/group-booking.review_tentative')) : @js(__('livewire/operator/group-booking.hold_confirmed'))) + ' · ' + (billingMode === 'master' ? @js(__('livewire/operator/group-booking.review_master')) : @js(__('livewire/operator/group-booking.review_individual')))"></strong></div>
                        <div class="vy-gb-rev-row" x-show="discountPct > 0" x-cloak><span>{{ __('livewire/operator/group-booking.review_discount') }}</span><strong x-text="discountPct + '% · −' + fmt(discountMinor)"></strong></div>
                        <div class="vy-gb-rev-row" x-show="billingMode === 'master' && depositPct > 0" x-cloak><span>{{ __('livewire/operator/group-booking.review_deposit') }}</span><strong x-text="depositPct + '% · ' + (depositMethod === 'card' ? @js(__('livewire/operator/group-booking.method_card_soon')) : (t.methods[depositMethod] || depositMethod))"></strong></div>
                        <div class="vy-gb-rev-units">
                            <div class="vy-gb-rev-units-h" x-text="t.reviewUnits.replace(':units', count)"></div>
                            <template x-for="u in selected" x-bind:key="u.id">
                                <div class="vy-gb-rev-unit"><span x-text="u.code + ' · ' + u.rt + ((rooming[u.id] && rooming[u.id].trim()) ? ' · ' + rooming[u.id].trim() : '')"></span><span x-text="fmt(u.priceMinor)"></span></div>
                            </template>
                        </div>
                    </div>
                    <label class="vy-gb-agree"><input type="checkbox" x-model="agree"><span>{{ __('livewire/operator/group-booking.agree') }}</span></label>
                </section>

                {{-- Footer nav --}}
                <div class="vy-gb-nav">
                    <button type="button" class="vy-gb-btn vy-gb-btn--ghost" x-show="step > 1" x-on:click="prev()">{{ __('common.back') }}</button>
                    <span style="flex:1"></span>
                    <button type="button" class="vy-gb-btn vy-gb-btn--accent" x-show="step < 5" x-on:click="next()">{{ __('common.continue') }}</button>
                    @unless ($__gbReadOnly)
                        <button type="submit" class="vy-gb-btn vy-gb-btn--accent" x-show="step === 5" x-bind:disabled="count === 0 || ! name.trim() || ! agree">{{ __('livewire/operator/group-booking.confirm_block') }}</button>
                    @endunless
                </div>
            </form>

            {{-- ═══ Sticky live pricing rail (accommodation subtotal; discount/deposit/tax lines in 4.2) ═══ --}}
            <aside class="vy-gb-rail">
                <div class="vy-gb-rail-card">
                    <div class="vy-gb-rail-title">{{ __('livewire/operator/group-booking.summary_title') }}</div>
                    {{-- ⚠️ Until at least one unit is in the block there is NO priced stay, so no figure and no
                         COMPONENT of one is stated — "—", never "€0.00" (a zero price is a claim).
                         The discount and deposit lines are gated on `count` too, not just on their own
                         percentage: both are a % OF the accommodation, so a percentage chosen on a later step
                         and then revisited with an empty block would otherwise render "Group discount (10%)
                         −€0.00" against an Accommodation of "—". That is exactly the follow-on the New
                         Reservation gate uncovered (a date-derived tax rendering against an empty total),
                         applied here up front. --}}
                    <div class="vy-gb-rail-line"><span x-text="count > 0 ? tc(t.railUnits, count).replace(':cap', cap) : @js(__('livewire/operator/group-booking.no_units_selected'))"></span><span></span></div>
                    <div class="vy-gb-rail-line"><span>{{ __('livewire/operator/group-booking.accommodation') }}</span><span class="vy-u-code" x-text="count > 0 ? fmt(blockSubMinor) : '—'"></span></div>
                    <div class="vy-gb-rail-line" x-show="count > 0 && discountPct > 0" x-cloak style="color:#1F8A5B;"><span x-text="t.railDiscount.replace(':pct', discountPct)"></span><span class="vy-u-code" x-text="'−' + fmt(discountMinor)"></span></div>
                    <div class="vy-gb-rail-total"><span x-text="count > 0 && discountPct > 0 ? @js(__('livewire/operator/group-booking.after_discount')) : @js(__('livewire/operator/group-booking.block_total'))"></span><span class="vy-u-code" x-text="count > 0 ? fmt(afterDiscMinor) : '—'"></span></div>
                    <div class="vy-gb-rail-line" x-show="count > 0 && billingMode === 'master' && depositPct > 0" x-cloak style="color:var(--vy-accent-text);"><span x-text="t.railDeposit.replace(':pct', depositPct)"></span><span x-text="depositMethod === 'card' ? @js(__('livewire/operator/group-booking.rail_soon')) : (t.methods[depositMethod] || depositMethod)"></span></div>
                    <div class="vy-gb-rail-note">{{ __('livewire/operator/group-booking.rail_note') }}</div>
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
                    rooming: {},
                    holdType: 'confirmed', billingMode: 'individual', pickupDeadline: checkIn,
                    discountPct: 0, depositPct: 0, depositMethod: 'cash', agree: false,
                    attempted: false,
                    // I18N-01 — the texts this script shows. A count's texts are a map, one per count it can show, built on the
                    // server with each language's real plural (rule 12); tc() picks from it.
                    t: {
                        stepOf: @js(__('livewire/operator/group-booking.step_of', ['step' => ':step'])),
                        headcountGuests: @js($__gbCounts['headcountGuests']),
                        buildSub: @js(__('livewire/operator/group-booking.build_sub', ['dates' => ':dates'])),
                        sleepsOf: @js($__gbCounts['sleepsOf']),
                        roomingList: @js($__gbCounts['roomingList']),
                        unitSleeps: @js(__('livewire/operator/group-booking.unit_sleeps', ['sleeps' => ':sleeps'])),
                        headcountValue: @js($__gbCounts['headcountValue']),
                        reviewUnits: @js(__('livewire/operator/group-booking.review_units', ['units' => ':units'])),
                        railUnits: @js($__gbCounts['railUnits']),
                        railDiscount: @js(__('livewire/operator/group-booking.rail_discount', ['pct' => ':pct'])),
                        railDeposit: @js(__('livewire/operator/group-booking.rail_deposit', ['pct' => ':pct'])),
                        nightsText: @js($__gbNightsText),
                        pickDates: @js(__('livewire/operator/group-booking.pick_dates')),
                        methods: { cash: @js(__('livewire/operator/group-booking.method_cash')), bank: @js(__('livewire/operator/group-booking.method_bank')) },
                        occasions: @js(collect($occasions)->pluck('label', 'value')->all()),
                        months: @js($__gbMonths),
                    },
                    tc(texts, n) { return texts[n]; },
                    // ⚠️⚠️ THE DRAFT SURVIVES THE "CHECK AVAILABILITY" NAVIGATION. applyDates() is a real page
                    // load (invariant #20 — the availability read is scope-dependent, so the dates MUST ride a
                    // GET through the tenant middleware rather than a Livewire action). That is correct and
                    // stays; what was wrong is that everything the operator had typed died with the page —
                    // step 1 asks for the block name and occasion FIRST, then the very next control reloads.
                    //
                    // ⚠️ sessionStorage, NOT the query string, and the reason is PII. The placeholder for this
                    // field is "e.g. Okafor–Bello Wedding" — a real person's surname. Putting it in the URL
                    // writes it into browser history and the web server's ACCESS LOGS, which no GDPR erase
                    // touches (#28). The dates are safe there; a guest's name is not.
                    // ⚠️ sessionStorage, not localStorage, so a draft cannot outlive the browser session or
                    // surface for the next person on a shared front-desk machine.
                    // ⚠️ try/catch on BOTH sides: a browser set to block site data throws on access, and a
                    // wizard that crashes because it could not remember a draft is worse than one that forgets.
                    // ⚠️ THE KEY IS SPELLED TWICE IN THIS FILE, AND IT CANNOT BE SPELLED ONCE. The done screen
                    // is a SEPARATE render branch with no Alpine root (see the top of the file), so it clears
                    // the draft with its own one-line script and its own literal. Recorded here rather than
                    // left for someone to discover: if this key ever changes, BOTH places change.
                    draftKey: 'vyrobel:group-booking:draft',
                    init() {
                        try {
                            const raw = sessionStorage.getItem(this.draftKey);
                            if (raw) {
                                const d = JSON.parse(raw);
                                if (typeof d.name === 'string') this.name = d.name;
                                if (typeof d.occasion === 'string') this.occasion = d.occasion;
                                // the counter's own range (1 … @js($__gbGuestMax)), so the restored count is one the texts above cover
                                if (Number.isInteger(d.headcount)) this.headcount = Math.min(@js($__gbGuestMax), Math.max(1, d.headcount));
                            }
                        } catch (e) { /* storage unavailable — the wizard still works, it just forgets */ }
                    },
                    saveDraft() {
                        try {
                            sessionStorage.setItem(this.draftKey, JSON.stringify({ name: this.name, occasion: this.occasion, headcount: this.headcount }));
                        } catch (e) { /* see init() */ }
                    },
                    // The discount is computed PER UNIT (floor(price × pct / 100)) and summed — the EXACT same
                    // rule the server posts per member — so the rail figure and the real folio agree to the cent
                    // (no two-paths-compute-independently drift). Each member is one unit, so member-room = unit price.
                    get discountMinor() { return this.discountPct > 0 ? this.selected.reduce((s, u) => s + Math.floor(u.priceMinor * this.discountPct / 100), 0) : 0; },
                    get afterDiscMinor() { return this.blockSubMinor - this.discountMinor; },
                    // dates change → GET nav (re-reads availability server-side, invariant #20). LOCAL date strings
                    // straight from the inputs — never toISOString (which shifts the day for +UTC users).
                    applyDates() { if (this.ci && this.co && this.co > this.ci) { this.saveDraft(); window.location = '?check_in=' + this.ci + '&check_out=' + this.co; } },
                    get datesShort() { return this.checkIn && this.checkOut ? this.fmtDate(this.checkIn) + '–' + this.fmtDate(this.checkOut) : '—'; },
                    get datesLabel() { return this.checkIn && this.checkOut ? this.datesShort + ' · ' + this.t.nightsText : this.t.pickDates; },
                    fmtDate(s) { const d = new Date(s + 'T00:00:00'); return d.getDate() + ' ' + this.t.months[d.getMonth()]; },
                    get occasionLabel() { const m = this.t.occasions; return m[this.occasion] || m.other; },
                    toggleUnit(u, checked) {
                        if (checked) {
                            if (! this.selected.some(x => x.id === u.id)) this.selected.push(u);
                            if (this.rooming[u.id] === undefined) this.rooming[u.id] = '';   // a rooming slot for this unit
                        } else { this.selected = this.selected.filter(x => x.id !== u.id); }
                    },
                    isSelected(id) { return this.selected.some(u => u.id === id); },
                    get count() { return this.selected.length; },
                    get cap() { return this.selected.reduce((s, u) => s + u.sleeps, 0); },
                    get capOk() { return this.cap >= this.headcount; },
                    get capPct() { return this.headcount > 0 ? Math.min(100, Math.round(this.cap / this.headcount * 100)) : 0; },
                    get blockSubMinor() { return this.selected.reduce((s, u) => s + u.priceMinor, 0); },
                    fmt(minor) { const sym = this.currency === 'EUR' ? '€' : (this.currency === 'USD' ? '$' : this.currency + ' '); return sym + (minor / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                    goto(n) { this.step = n; },
                    // ⚠️ Continue is now always PRESSABLE, and a failed press EXPLAINS itself. A disabled button
                    // is the cheapest thing to build and the least useful thing to receive: it withholds both
                    // the action and the reason. The guard is unchanged — canLeave() still decides — but the
                    // refusal now has a voice.
                    next() {
                        if (this.step >= 5) return;
                        if (! this.canLeave(this.step)) { this.attempted = true; return; }
                        this.attempted = false;
                        this.step++;
                    },
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
            /* ⚠️ The "what's missing" line. Warm-red like every other refusal in the operator app (the cancel
               warning, the capacity shortfall) so it reads as the same kind of message, not a new dialect. */
            .vy-gb-miss { font-size: 12px; color: #8F3110; margin: 8px 0 0; font-weight: 600; }
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
            /* rooming */
            .vy-gb-rooming { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
            .vy-gb-rooming-unit { flex: 0 0 140px; font-size: 12.5px; font-weight: 600; color: var(--vy-ink); }
            .vy-gb-rooming-input { flex: 1; }
            .vy-gb-faint { font-weight: 400; color: var(--vy-faint); }
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
            .vy-gb-agree { display: flex; align-items: flex-start; gap: 10px; margin-top: 16px; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.5); font-size: 13px; color: var(--vy-ink); cursor: pointer; }
            .vy-gb-agree input { margin-top: 2px; accent-color: var(--vy-accent, #E8703A); width: 16px; height: 16px; flex: none; }
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
            @media (max-width: 900px) { .vy-gb-body { grid-template-columns: 1fr; } .vy-gb-rail { position: static; } .vy-gb-row2 { grid-template-columns: 1fr; } }
        </style>
    </div>
@endif
