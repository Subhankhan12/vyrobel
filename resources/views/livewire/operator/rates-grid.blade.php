{{-- Operator RATES & AVAILABILITY grid (Wave 4 · Sub-gate 1) — a room-type × date calendar of nightly
     rates, availability and stay-rules, on the shared operator design system via <x-operator.shell>.
     READ view; the ONLY write is the guarded POST form below → RateActionController → the certified
     RateAndRestrictionService. See App\Livewire\Operator\RatesGrid for the REAL / DERIVED / OMITTED map.

     PER-ROOM-TYPE, NOT per-unit (STRUCT-1): rate_calendar is keyed on rate_plan_id, so each row edits a
     room type's primary rate plan; the sub-label states how many units sit under it.

     RAW-BLADE RULE: never write `word@directive` — a directive whose `@` follows a word character ships raw
     source to the page. Every `@` below follows a space, a newline or a tag. Alpine handlers use `x-on:` /
     `x-model` (never `@click`) so no `@` reaches an attribute value at all. --}}
<div>
    <x-operator.shell active="rates" title="Rates & availability" :subtitle="$subtitle" :viewer="$viewer">

        {{-- ⚠️ `rates_error` was set by nothing and rendered by nothing — the same F-1 shape FIX 10 found
             with `units_error`. Taking the LAST rate plan off sale is refused, and a refusal nobody sees is
             the defect this programme closed for five other doors.
             ⚠️ WHERE THIS LANDS: in-flow, inside the operator shell. At 375px that shell stacks 673px of
             navigation above the content (FIX 11 measured it and correctly changed nothing), so on a phone
             this is REACHABLE BY SCROLL AND NOT SEEN ON ARRIVAL. Stated, not claimed otherwise. --}}
        @if (session('rates_error'))
            <div class="vy-card vy-fade" role="alert" style="border-left: 3px solid var(--vy-amber); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('rates_error') }}</div>
            </div>
        @endif

        @if (session('rates_notice'))
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-accent); margin-bottom: 16px;">
                <div style="font-size: 13.5px; color: var(--vy-ink);">{{ session('rates_notice') }}</div>
            </div>
        @endif

        {{-- ⚠️ The Alpine scope starts HERE (it used to open below the stat tiles) so that the design's
             control row can carry Bulk edit — which is an Alpine action (`bulkOpen`) and must sit inside
             `ratesEditor()`. Nothing else about the editor changed; the KPI tiles simply fall inside the
             scope now, which is inert for them. --}}
        <div x-data="{ ...ratesEditor(), unitPriceFor: null }">

        {{-- ── Toolbar: the two-week window, pageable. Real dates only; no fabricated "sync" ribbon.
             The design puts the legend and the bulk-edit action in this control row, not on a separate
             line below the stat tiles. ─────────────────────────────────────────────────────────────── --}}
        <div class="vyr-toolbar vy-fade">
            <div class="vyr-winnav">
                <a class="vyr-navbtn" href="{{ $window['prev'] }}" aria-label="Previous two weeks" title="Previous two weeks">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                </a>
                <span class="vyr-winlabel">{{ $window['label'] }}</span>
                <a class="vyr-navbtn" href="{{ $window['next'] }}" aria-label="Next two weeks" title="Next two weeks">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                </a>
                <a class="vy-btn vy-btn--ghost" href="{{ $window['today'] }}" style="padding: 8px 13px; font-size: 12.5px;">Today</a>
            </div>
            {{-- #104 GATE 2d — read-only room-type filter (preserves the ?from= window); narrows the grid to one
                 room type. Only shown when there's more than one to choose from. --}}
            @if (count($roomTypeOptions) > 1)
                <select class="vy-input" style="font-size: 12.5px; max-width: 220px;" aria-label="Filter by room type"
                        onchange="window.location.href='{{ route('app.rates') }}?from={{ $formDefaults['from'] }}' + (this.value ? '&rt=' + encodeURIComponent(this.value) : '')">
                    <option value="" @selected($rtFilter === '')>All room types</option>
                    @foreach ($roomTypeOptions as $opt)
                        <option value="{{ $opt['id'] }}" @selected($rtFilter === $opt['id'])>{{ $opt['name'] }}</option>
                    @endforeach
                </select>
            @endif
            @if ($window['jump'])
                <a class="vyr-jump" href="{{ $window['jump']['href'] }}">Jump to rates from {{ \Carbon\CarbonImmutable::parse($window['jump']['date'])->format('j M Y') }} →</a>
            @endif

            {{-- The design's legend sits in the control row. ⚠️ Each swatch is paired with its WORD, so the
                 grid's cell states are never carried by colour alone (WCAG 1.4.1). --}}
            @if (count($rows) > 0)
                <div class="vyr-legend">
                    <span><span class="vyr-legend-sw" style="background: rgba(120,70,40,.10);"></span> Open</span>
                    <span><span class="vyr-legend-sw" style="background: rgba(194,65,12,.20);"></span> Booked</span>
                    <span><span class="vyr-legend-sw" style="background: rgba(178,106,8,.14);"></span> Weekend</span>
                </div>
                @can('rates.manage')
                    @unless ($readOnly)
                        <button type="button" class="vy-btn vy-btn--ghost vyr-bulk" style="padding: 8px 14px; font-size: 12.5px;" x-on:click="bulkOpen = true">Bulk edit</button>
                        <button type="button" class="vy-btn vy-btn--ghost vyr-unitbulk" style="padding: 8px 14px; font-size: 12.5px;" x-on:click="unitOpen = true">Price units individually</button>
                    @endunless
                @endcan
            @endif
        </div>

        {{-- ── Stat tiles — DERIVED, real aggregates over the window. Avg PER CURRENCY (never summed). No
             "+8% vs Aug" trend: no historical baseline is stored, so a trend would be fabricated. ──────── --}}
        <div class="vy-dash-kpis vy-dash-kpis--3 vy-fade">
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Average nightly rate</div>
                @forelse ($tiles['avg'] as $line)
                    <div class="vy-dk-val" @if (! $loop->first) style="font-size: 20px;" @endif>{{ $line }}</div>
                @empty
                    <div class="vy-dk-val">—</div>
                @endforelse
                <div class="vy-dk-sub">
                    mean of set rates across this window
                    {{ $tiles['multi_currency'] ? '· per currency (never summed across)' : '' }}
                </div>
            </div>

            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Booked</div>
                <div class="vy-dk-val">{{ $tiles['booked_pct'] !== null ? $tiles['booked_pct'].'%' : '—' }}</div>
                <div class="vy-dk-sub">sold ÷ published inventory across this window</div>
            </div>

            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Open nights</div>
                <div class="vy-dk-val">{{ $tiles['open_nights'] }}</div>
                <div class="vy-dk-sub">bookable unit-nights left in this window</div>
            </div>
        </div>

            {{-- ── The grid ─────────────────────────────────────────────────────────────────────────── --}}
            <div class="vy-card vy-card--flush vy-fade" style="margin-bottom: 18px;">
                @if (count($rows) === 0)
                    <div style="padding: 40px 24px; text-align: center;">
                        <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">No room types yet</strong>
                        <span style="font-size: 13px; color: var(--vy-muted);">Add a room type to this property to price it here.</span>
                    </div>
                @else
                    <div class="vyr-scroll">
                        <table class="vyr-grid">
                            <thead>
                                <tr>
                                    <th class="vyr-corner"></th>
                                    @foreach ($dates as $d)
                                        <th class="vyr-dhead @if ($d['is_weekend']) is-weekend @endif">
                                            <div class="vyr-dweekday">{{ $d['weekday'] }}</div>
                                            <div class="vyr-dday">{{ $d['day'] }}</div>
                                            <div class="vyr-dmonth">{{ $d['month'] }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td class="vyr-rt">
                                            <div class="vyr-rt-name">{{ $row['name'] }}</div>
                                            <div class="vyr-rt-sub">
                                                {{ $row['units_label'] }}@if ($row['plan_name']) · {{ $row['plan_name'] }}@endif
                                            </div>
                                            @unless ($row['has_plan'])
                                                {{-- ⚠️ P-6 · Gate 1 — this line used to say "add one to price it" while there was
                                                     NO way to add one anywhere in the application. It now points at the form that
                                                     exists. --}}
                                                <div class="vyr-rt-sub" style="color: var(--vy-accent);">
                                                    No rate plan —
                                                    @if ($canManage)<a href="#vy-add-rate-plan">add one</a> to price it @else ask an administrator to add one @endif
                                                </div>
                                            @endunless
                                        </td>
                                        @foreach ($row['cells'] as $cell)
                                            <td class="vyr-cell @if ($dates[$loop->index]['is_weekend']) is-weekend @endif">
                                                @if ($canManage && $row['has_plan'] && ! $readOnly)
                                                    <button type="button" class="vyr-cellbtn"
                                                        x-on:click="edit('{{ $row['id'] }}', '{{ $cell['date'] }}', '{{ $cell['rate_input'] }}', {{ $cell['min_stay'] ?? 'null' }}, {{ $cell['stop_sell'] ? 'true' : 'false' }})"
                                                        title="Edit {{ $row['name'] }} · {{ \Carbon\CarbonImmutable::parse($cell['date'])->format('j M') }}">
                                                        @include('livewire.operator.partials.rate-cell', ['cell' => $cell])
                                                    </button>
                                                @else
                                                    <div class="vyr-cell-static">
                                                        @include('livewire.operator.partials.rate-cell', ['cell' => $cell])
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- ── D18-6 · THE THIRD OPTION — the units under this type, READ-ONLY.
                                         ⚠️ THERE IS DELIBERATELY NO CELL EDITOR HERE. An editable per-unit
                                         grid would mint an override on every edit, detaching every unit from
                                         its type and leaving the "N units affected" warning above reading
                                         "0 of N" everywhere — useless by shape. The type row stays the
                                         price-mover; pricing a unit apart is invoked once, on purpose, via
                                         the action below, which opens the SAME single-set form the Units
                                         list opens. Every figure is NightlyRate's answer (D4-1). ────────── --}}
                                    @foreach ($row['unit_rows'] as $vyUnitRow)
                                        <tr class="vyr-unitrow">
                                            <td class="vyr-rt" style="padding-left: 22px;">
                                                <div class="vyr-rt-sub" style="color: var(--vy-ink);">{{ $vyUnitRow['label'] }}</div>
                                                @if ($canManage && ! $readOnly)
                                                    <button type="button" class="vyr-priceind"
                                                            style="padding:0;border:0;background:none;font-size:11px;color:var(--vy-accent);cursor:pointer;text-decoration:underline;"
                                                            x-on:click="unitPriceFor = '{{ $vyUnitRow['id'] }}'">Price individually</button>
                                                @endif
                                            </td>
                                            @foreach ($vyUnitRow['cells'] as $vyCell)
                                                <td class="vyr-cell @if ($dates[$loop->index]['is_weekend']) is-weekend @endif">
                                                    {{-- ⚠️ STATIC BY CONSTRUCTION — no button, no editor, nothing to type into. --}}
                                                    <div class="vyr-cell-static" style="opacity: .85;">
                                                        <span class="vy-u-code">{{ $vyCell['rate'] ?? '—' }}</span>
                                                        @if ($vyCell['overridden'])
                                                            <span title="Priced individually" style="color: var(--vy-accent); font-size: 10px;">●</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ── The writer — the ONLY write on this screen. Gated on rates.manage (route + this control),
                 so a Reservation Agent (rates.view only) sees the grid but never this form. A real POST →
                 controller → setRate/setRestriction → PRG; integer money is parsed server-side. ────────── --}}
            @can('rates.manage')
                @if (count($rows) > 0 && ! $readOnly)
                    <div class="vy-card vy-fade">
                        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-bottom: 14px;">
                            <h2 class="vy-card-title">Set rates &amp; availability</h2>
                            <span style="font-size: 12px; color: var(--vy-faint);">Click a cell to prefill · applies to a whole date range</span>
                        </div>

                        <form method="POST" action="{{ route('app.rates.update') }}">
                            @csrf

                            <div class="vyr-editor">
                                <div class="vy-field" style="margin: 0;">
                                    <label class="vy-field-label" for="rt">Room type</label>
                                    <select id="rt" name="room_type_id" class="vy-input" x-model="roomType">
                                        <option value="all">All room types</option>
                                        @foreach ($roomTypeOptions as $opt)
                                            <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="vy-field" style="margin: 0;">
                                    <label class="vy-field-label" for="from">From</label>
                                    <input id="from" type="date" name="start_date" class="vy-input" x-model="from" required>
                                </div>

                                <div class="vy-field" style="margin: 0;">
                                    <label class="vy-field-label" for="to">To (inclusive)</label>
                                    <input id="to" type="date" name="end_date" class="vy-input" x-model="to" required>
                                </div>
                            </div>

                            <div class="vyr-editor" style="margin-top: 16px;">
                                <div>
                                    <label class="vyr-check">
                                        <input type="checkbox" name="set_rate" value="1" x-model="setRate"> Set nightly rate
                                    </label>
                                    <input id="vy-lbl-rates-grid-rate" type="text" name="rate" class="vy-input" placeholder="e.g. 175 or 175.50"
                                        inputmode="decimal" x-model="rate" x-bind:disabled="! setRate">
                                </div>

                                <div>
                                    <label class="vyr-check">
                                        <input type="checkbox" name="set_min_stay" value="1" x-model="setMin"> Set minimum stay
                                    </label>
                                    <label class="vy-sr-only" for="vy-lbl-rates-grid-min-stay">nights (blank = none)</label>
                                    <input id="vy-lbl-rates-grid-min-stay" type="number" name="min_stay" class="vy-input" min="1" max="365" placeholder="nights (blank = none)"
                                        x-model="minStay" x-bind:disabled="! setMin">
                                </div>

                                <div>
                                    <label class="vyr-check">
                                        <input type="checkbox" name="set_stop_sell" value="1" x-model="setStop"> Set open / closed
                                    </label>
                                    <label class="vyr-check" style="margin: 0;">
                                        <input type="checkbox" name="stop_sell" value="1" x-model="stopSell" x-bind:disabled="! setStop">
                                        <span x-text="stopSell ? 'Closed (stop sell)' : 'Open for sale'">Open for sale</span>
                                    </label>
                                </div>

                                <div>
                                    <button type="submit" class="vy-btn vy-btn--accent" style="width: 100%; justify-content: center; padding: 12px;">Apply</button>
                                </div>
                            </div>

                            {{-- #100 — recompute Non-refundable / Weekly derived plans (base × adjustment) on this write. --}}
                            <label class="vyr-check" style="margin-top: 14px;">
                                <input type="checkbox" name="update_derived" value="1" x-model="updateDerived">
                                Update derived plans too <span style="color: var(--vy-faint);">· Non-refundable −10% and Weekly −15% follow the new base</span>
                            </label>
                        </form>

                        {{-- Honest deferral (#85) — the ARI push is real but the live Channex rail is account-gated. --}}
                        <div class="vyr-soon-note">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" style="flex: none; margin-top: 1px; color: var(--vy-faint);"><path d="M12 8v4l3 2M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"/></svg>
                            <span>Rates save to Direct now. Pushing them to OTA channels needs the channel connection set up first — nothing is sent to a channel until then.</span>
                        </div>
                    </div>
                @endif
            @endcan

            {{-- ── Bulk edit modal (Alpine) — increase% / decrease% / set-exact across units × nights, skip
                 booked, update derived. Real POST → app.rates.bulk. ──────────────────────────────────── --}}
            @can('rates.manage')
                @if (count($rows) > 0 && ! $readOnly)
                    {{-- ⚠️⚠️ D2 — THE DIALOG CONTRACT. `x-trap` gives all three focus properties at once: focus moves IN
     on open, is TRAPPED while open, and is RETURNED to the trigger on close. ⚠️ IT NEEDS NO NEW
     DEPENDENCY — Livewire already bundles Alpine's focus plugin (`directive("trap")` /
     `createFocusTrap` in livewire.esm.js); it was simply never used anywhere in 243 files.
     ⚠️ `.noscroll` also stops the page behind scrolling. ⚠️ It does NOT make the rest of the page
     INERT — without `inert` support or a dependency, focus is trapped and no more, and that is
     stated rather than implied. ⚠️ Focus lands on the panel itself (`tabindex="-1"`), never on the
     destructive button. --}}
                <div x-show="bulkOpen" x-cloak style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;" x-on:click.self="bulkOpen = false" @keydown.escape.window="bulkOpen = false">
                        <div class="vy-card" style="max-width: 520px; width: 100%; max-height: 90vh; overflow: auto;" role="dialog" aria-modal="true" aria-labelledby="vy-dlg-bulk-t" tabindex="-1" x-trap.noscroll="bulkOpen">
                            <h3 style="font-size: 16px; font-weight: 700; color: var(--vy-ink); margin: 0 0 2px;" id="vy-dlg-bulk-t">Bulk edit rates</h3>
                            <div style="font-size: 12.5px; color: var(--vy-muted); margin-bottom: 14px;">{{ $window['label'] }} · base plan</div>
                            <form method="POST" action="{{ route('app.rates.bulk') }}">
                                @csrf
                                <input type="hidden" name="start_date" x-model="from">
                                <input type="hidden" name="end_date" x-model="to">
                                <div class="vy-field" style="margin: 0 0 12px;">
                                    <label class="vy-field-label" for="bu_rt">Units</label>
                                    <select id="bu_rt" name="room_type_id" class="vy-input">
                                        <option value="all">All room types</option>
                                        @foreach ($roomTypeOptions as $opt)
                                            <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="vy-field" style="margin: 0 0 12px;">
                                    <label class="vy-field-label">Nights</label>
                                    <div style="display: flex; gap: 8px;">
                                        <label class="vyr-check" style="margin: 0;"><input type="radio" name="nights" value="whole" checked> Whole range</label>
                                        <label class="vyr-check" style="margin: 0;"><input type="radio" name="nights" value="weekends"> Weekends</label>
                                        <label class="vyr-check" style="margin: 0;"><input type="radio" name="nights" value="midweek"> Midweek</label>
                                    </div>
                                </div>
                                <div class="vy-field" style="margin: 0 0 12px;">
                                    <label class="vy-field-label">Adjustment</label>
                                    <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                        <label class="vyr-check" style="margin: 0;"><input type="radio" name="mode" value="increase_pct" x-model="buMode" checked> Increase %</label>
                                        <label class="vyr-check" style="margin: 0;"><input type="radio" name="mode" value="decrease_pct" x-model="buMode"> Decrease %</label>
                                        <label class="vyr-check" style="margin: 0;"><input type="radio" name="mode" value="set_exact" x-model="buMode"> Set exact {{ $currencySymbol }}</label>
                                    </div>
                                    <label class="vy-sr-only" for="vy-lbl-rates-grid-amount">buMode === 'set_exact' ? 'e.g. 175.00' : 'e.g. 10 (percent)'</label>
                                    <input id="vy-lbl-rates-grid-amount" type="text" name="amount" class="vy-input" inputmode="decimal" x-bind:placeholder="buMode === 'set_exact' ? 'e.g. 175.00' : 'e.g. 10 (percent)'" required>
                                </div>
                                <label class="vyr-check" style="margin: 0 0 10px;">
                                    <input type="checkbox" name="update_derived" value="1"> Update derived plans too
                                    <span style="color: var(--vy-faint);">· Non-refundable −10% and Weekly −15% follow the new base</span>
                                </label>
                                <div style="font-size: 11.5px; color: var(--vy-faint); margin-bottom: 14px;">Booked and closed nights are skipped · push when you’re ready.<input type="hidden" name="skip_booked" value="1"></div>
                                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                    <button type="button" class="vy-btn" x-on:click="bulkOpen = false" style="padding: 9px 16px;">Cancel</button>
                                    <button type="submit" class="vy-btn vy-btn--accent" style="padding: 9px 16px;">Apply bulk edit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @endcan

            {{-- ── D18-5 · BULK PER-UNIT PRICING — step 1 of 2: describe the operation. ⚠️ THIS FORM POSTS TO
                 `preview`, WHICH WRITES NOTHING. Money at scale earns a confirmation step: a bulk mistake is
                 many wrong prices at once and is silent, so the operator is shown the exact figures first and
                 the write happens only from the panel below. ───────────────────────────────────────────── --}}
            @can('rates.manage')
                @if (count($rows) > 0 && ! $readOnly)
                    <div x-show="unitOpen" x-cloak style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;" x-on:click.self="unitOpen = false" x-on:keydown.escape.window="unitOpen = false">
                        <div class="vy-card" style="max-width: 560px; width: 100%; max-height: 90vh; overflow: auto;" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="unitOpen" aria-labelledby="vy-dlg-rg-unit-t">
                            {{-- ⚠️ THE ONE FORM (§N.8f) — the same component the Units list opens for a single
                                 unit, so the two homes cannot drift. Dates bind to this grid's live window. --}}
                            <x-rates.unit-price-form
                                :room-types="$roomTypeOptions"
                                :units="$unitOptions"
                                :bind-dates="true"
                                heading-id="vy-dlg-rg-unit-t"
                                subheading="{{ $window['label'] }} · a price for one unit, not the whole room type">
                                <x-slot:actions>
                                    <button type="button" class="vy-btn" x-on:click="unitOpen = false" style="padding: 9px 16px;">Cancel</button>
                                </x-slot:actions>
                            </x-rates.unit-price-form>
                        </div>
                    </div>
                @endif
            @endcan

        {{-- D18-6 — the single-set dialogs the unit rows open. ⚠️ The SAME <x-rates.unit-price-form> the Units
                 list uses, so the grid adds no second form and no second writer — it is a new ENTRY to a proven
                 path, which is the whole point of the third option. --}}
            @can('rates.manage')
                @if (count($rows) > 0 && ! $readOnly)
                    @foreach ($rows as $vyRow)
                        @foreach ($vyRow['unit_rows'] as $vyU)
                            <div x-show="unitPriceFor === '{{ $vyU['id'] }}'" x-cloak
                                 style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 61; display: flex; align-items: center; justify-content: center; padding: 20px;"
                                 x-on:click.self="unitPriceFor = null" x-on:keydown.escape.window="unitPriceFor = null">
                                <div class="vy-card" style="max-width: 520px; width: 100%; max-height: 90vh; overflow: auto;"
                                     x-data="{ unitMode: 'set', unitRt: '{{ $vyRow['id'] }}' }" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="unitPriceFor === '{{ $vyU['id'] }}'" aria-labelledby="vy-dlg-rg-unitprice-{{ $vyU['id'] }}">
                                    <x-rates.unit-price-form
                          heading-id="vy-dlg-rg-unitprice-{{ $vyU['id'] }}"
                                        :room-types="$roomTypeOptions"
                                        :units="$unitOptions"
                                        :preselect="$vyU['id']"
                                        :from="$window['from']"
                                        :to="$window['through']"
                                        heading="Price this unit individually"
                                        subheading="a price for this unit only — the rest of its room type is unchanged">
                                        <x-slot:actions>
                                            <button type="button" class="vy-btn" x-on:click="unitPriceFor = null" style="padding: 9px 16px;">Cancel</button>
                                        </x-slot:actions>
                                    </x-rates.unit-price-form>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                @endif
            @endcan

        </div>

        {{-- ── D18-5 · step 2 of 2: THE PREVIEW IS THE APPROVAL GATE. Nothing has been written at this point.
             ⚠️ The digest below is a hash of every (unit, night, from, to) tuple shown here; the apply route
             re-computes it inside the transaction and refuses if anything moved, so what commits is exactly
             what is on this screen. ────────────────────────────────────────────────────────────────────── --}}
        @can('rates.manage')
            @if (session('unit_rate_preview'))
                @php($vyPreview = session('unit_rate_preview'))
                <div class="vy-card vy-fade vyr-unitpreview" style="margin-top: 18px; border: 1px solid rgba(194,65,12,.35);">
                    <h2 class="vy-card-title" style="margin-bottom: 2px;">Review before applying</h2>
                    <div style="font-size: 12.5px; color: var(--vy-muted); margin-bottom: 12px;">
                        {{ $vyPreview['room_type_name'] }} · {{ count($vyPreview['units']) }} {{ count($vyPreview['units']) === 1 ? 'unit' : 'units' }} ·
                        {{ $vyPreview['nights'] }} {{ $vyPreview['nights'] === 1 ? 'night' : 'nights' }} from {{ $vyPreview['start'] }} to {{ $vyPreview['end_inclusive'] }} ·
                        <strong>nothing has been changed yet</strong>
                    </div>

                    <table class="vy-table" style="margin-bottom: 12px;">
                        <thead><tr><th>Unit</th><th>Now</th><th>After</th><th>Nights</th></tr></thead>
                        <tbody>
                            @foreach ($vyPreview['units'] as $vyRow)
                                <tr>
                                    <td>{{ $vyPreview['names'][$vyRow['unit_id']] ?? $vyRow['unit_id'] }}</td>
                                    <td>{{ $vyRow['from_minor'] === null ? '—' : \App\Support\Money\MoneyFormatter::display($vyRow['from_minor'], $vyPreview['currency']) }}</td>
                                    <td>
                                        <strong>{{ $vyRow['to_minor'] === null ? '—' : \App\Support\Money\MoneyFormatter::display($vyRow['to_minor'], $vyPreview['currency']) }}</strong>
                                        @if ($vyRow['state'] === 'cleared')
                                            <span style="font-size: 11.5px; color: var(--vy-muted);"> · room-type rate</span>
                                        @elseif ($vyRow['state'] === 'pinned')
                                            <span style="font-size: 11.5px; color: var(--vy-muted);"> · unchanged, but pinned to this unit</span>
                                        @endif
                                    </td>
                                    <td>{{ $vyRow['nights_changing'] }} of {{ $vyRow['nights'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($vyPreview['skipped_units'] > 0)
                        <div style="font-size: 12px; color: var(--vy-muted); margin-bottom: 10px;">{{ $vyPreview['skipped_units'] }} {{ $vyPreview['skipped_units'] === 1 ? 'unit has' : 'units have' }} no per-unit price in this range and will be left alone.</div>
                    @endif

                    <form method="POST" action="{{ route('app.rates.units.apply') }}" style="display: flex; gap: 10px; justify-content: flex-end; align-items: center;">
                        @csrf
                        <input type="hidden" name="digest" value="{{ $vyPreview['digest'] }}">
                        <input type="hidden" name="start_date" value="{{ $vyPreview['start'] }}">
                        <input type="hidden" name="end_date" value="{{ $vyPreview['end_inclusive'] }}">
                        <input type="hidden" name="room_type_id" value="{{ $vyPreview['room_type_id'] }}">
                        <input type="hidden" name="mode" value="{{ $vyPreview['mode'] }}">
                        <input type="hidden" name="amount" value="{{ $vyPreview['amount'] }}">
                        @foreach ($vyPreview['units'] as $vyRow)
                            <input type="hidden" name="unit_ids[]" value="{{ $vyRow['unit_id'] }}">
                        @endforeach
                        <a class="vy-btn" style="padding: 9px 16px;" href="{{ route('app.rates', ['from' => $vyPreview['start']]) }}">Cancel</a>
                        <button type="submit" class="vy-btn vy-btn--accent" style="padding: 9px 16px;">Apply to {{ count($vyPreview['units']) }} {{ count($vyPreview['units']) === 1 ? 'unit' : 'units' }}</button>
                    </form>
                </div>
            @endif
        @endcan

        {{-- ══ SEASONAL PRICING ═══════════════════════════════════════════════════════════════════════════
             ⚠️⚠️ THIS PANEL WRITES WHAT A GUEST IS CHARGED. The back end deliberately separates SAVING a
             season from APPLYING it, and this panel keeps the two apart: the form below only records a
             season, and a second, separate control writes the nights. ⚠️ There is deliberately NO
             "save and apply" shortcut — it would look like a convenience and would remove the review step
             that is the only protection this feature has.
             ⚠️ Every figure in a season's row comes from `SeasonalRateService::preview()` via the component.
             Nothing is counted here. ────────────────────────────────────────────────────────────────── --}}
        @can('rates.manage')
        {{-- ⚠️⚠️ THE SCOPE WRAPPER, AND WHY IT IS NOT ON THE CARD (F-20). `confirmSeason`/`editSeason`/
             `deleteSeason` used to be declared on the `.vy-card .vy-fade` below. The Apply dialog lives inside
             that card and resolved; the Edit and Delete dialogs are SIBLINGS of it, so they fell outside the
             scope and threw `editSeason is not defined` / `deleteSeason is not defined` on EVERY load — neither
             could ever open, for any role.
             ⚠️ The scope is lifted HERE rather than the dialogs being moved INTO the card, because
             `.vy-fade` ends its animation on `transform: translateY(0)` — a transform, which makes the card a
             CONTAINING BLOCK for `position: fixed`. A dialog nested inside it is clamped to the card's box
             instead of the viewport (measured: a 1148×345 overlay, panel at y=1208 in a 900px viewport — open
             but off-screen). This wrapper is a plain div with no animation, so the dialogs stay full-viewport.
             ⚠️ It encloses the season card and those two dialogs and NOTHING else: `@endcan` follows the
             delete dialog immediately. --}}
        <div x-data="{ confirmSeason: null, editSeason: null, deleteSeason: null }">
            <div class="vy-card vy-fade" style="margin-top: 18px;">
                <h2 class="vy-card-title" style="margin-bottom: 2px;">Seasonal pricing</h2>
                <div style="font-size: 12.5px; color: var(--vy-muted); margin-bottom: 14px;">
                    Save a season, review what it would change, then apply it. Saving writes no prices.
                    {{-- ⚠️ THE RULE, from `SeasonPrecedence::SENTENCE` — the copy and the code are the same
                         string, so the page can never describe a rule the product does not follow. --}}
                    <div style="margin-top: 4px;">{{ $seasonRule }}</div>
                </div>

                @if (count($seasons) > 0)
                    <table class="vy-table" style="margin-bottom: 14px;">
                        <thead>
                            <tr><th>Season</th><th>Dates</th><th>Now</th><th>After</th><th>Would change</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($seasons as $vySeason)
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: var(--vy-ink);">{{ $vySeason['name'] }}</div>
                                        <div style="font-size: 11.5px; color: var(--vy-muted);">{{ $vySeason['plan_name'] }}@if ($vySeason['room_type'] !== '') · {{ $vySeason['room_type'] }}@endif</div>
                                    </td>
                                    <td>{{ $vySeason['starts_on'] }} → {{ $vySeason['ends_on'] }}</td>
                                    <td>{{ $vySeason['from_display'] ?? '—' }}</td>
                                    <td><strong>{{ $vySeason['to_display'] }}</strong></td>
                                    <td>
                                        {{ $vySeason['changing'] }} of {{ $vySeason['nights'] }} {{ $vySeason['nights'] === 1 ? 'night' : 'nights' }}
                                        · {{ $vySeason['units'] }} {{ $vySeason['units'] === 1 ? 'unit' : 'units' }}
                                        @if ($vySeason['weekend_nights'] > 0)
                                            <div style="font-size: 11.5px; color: var(--vy-muted);">includes {{ $vySeason['weekend_nights'] }} Fri/Sat {{ $vySeason['weekend_nights'] === 1 ? 'night' : 'nights' }}, replacing any weekend uplift</div>
                                        @endif
                                        {{-- ⚠️ THE OVERLAP, DERIVED. Every figure comes from preview(), which
                                             asks the precedence rule night by night. --}}
                                        @if ($vySeason['losing_nights'] > 0)
                                            <div style="font-size: 11.5px; color: var(--vy-accent);">{{ $vySeason['losing_nights'] }} of these {{ $vySeason['losing_nights'] === 1 ? 'night is' : 'nights are' }} governed by {{ $vySeason['losing_to'] }}, which wins under the rule</div>
                                        @endif
                                        {{-- ⚠️ The same disclosure at preview time, so it is seen BEFORE the
                                             dialog rather than only at the moment of confirming. --}}
                                        @if ($vySeason['agreed_nights'] > 0)
                                            <div style="font-size: 11.5px; color: var(--vy-accent);">{{ $vySeason['agreed_nights'] }} of these {{ $vySeason['agreed_nights'] === 1 ? 'night carries' : 'nights carry' }} a negotiated owner rate</div>
                                        @endif
                                        @if ($vySeason['rewritten_nights'] > 0)
                                            <div style="font-size: 11.5px; color: var(--vy-muted);">applying also re-writes {{ $vySeason['rewritten_nights'] }} {{ $vySeason['rewritten_nights'] === 1 ? 'night' : 'nights' }} from overlapping seasons already applied</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($vySeason['applied'])
                                            <span style="font-size: 12px; color: var(--vy-ink);">Applied {{ $vySeason['applied_on'] }}</span>
                                        @else
                                            {{-- ⚠️ An unapplied season has written NOTHING. Say that, rather than
                                                 letting the row imply those nights already carry the price. --}}
                                            <span style="font-size: 12px; color: var(--vy-muted);">Saved · no prices written</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 6px; align-items: center; flex-wrap: wrap; justify-content: flex-end;">
                                            {{-- ⚠️⚠️ EDIT IS OFFERED ONLY WHILE THE SEASON IS A DRAFT. Applying overwrote the
                                                 nightly rates and nothing recorded what they were, so an "edit" of an applied
                                                 season could not put them back — it would imply a revert that cannot happen.
                                                 An applied season is SUPERSEDED by authoring another, never edited. --}}
                                            @if (! $vySeason['applied'])
                                                <button type="button" class="vy-btn" style="padding: 7px 12px;"
                                                        x-on:click="editSeason = {{ Illuminate\Support\Js::from($vySeason) }}">Edit</button>
                                            @endif

                                            <button type="button" class="vy-btn" style="padding: 7px 12px;"
                                                    x-on:click="confirmSeason = {{ Illuminate\Support\Js::from($vySeason) }}">
                                                {{ $vySeason['applied'] ? 'Apply again' : 'Apply' }}
                                            </button>

                                            <button type="button" class="vy-btn" style="padding: 7px 12px;"
                                                    x-on:click="deleteSeason = {{ Illuminate\Support\Js::from($vySeason) }}">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="font-size: 12.5px; color: var(--vy-muted); margin-bottom: 14px;">No seasons yet.</div>
                @endif

                {{-- ── Save a season. This writes NO prices. ──────────────────────────────────────────── --}}
                <form method="POST" action="{{ route('app.seasons.store') }}" style="border-top: 1px solid var(--vy-hairline); padding-top: 14px;">
                    @csrf
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px;">
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="vy-season-plan">Rate plan</label>
                            <select id="vy-season-plan" name="rate_plan_id" class="vy-input" required>
                                {{-- ⚠️ ACTIVE PLANS ONLY. The list below this card now includes off-sale
                                     plans so they can be put back on sale (F-34 / FIX 15); a season must
                                     never be definable against one. --}}
                                @foreach ($ratePlans as $vyPlan)
                                    @continue (! $vyPlan['is_active'])
                                    <option value="{{ $vyPlan['id'] }}">{{ $vyPlan['name'] }}@if ($vyPlan['room_type'] !== '') — {{ $vyPlan['room_type'] }}@endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="vy-season-name">Season name</label>
                            <input id="vy-season-name" name="name" type="text" maxlength="80" class="vy-input" required>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="vy-season-from">First night</label>
                            <input id="vy-season-from" name="starts_on" type="date" class="vy-input" required>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            {{-- ⚠️ INCLUSIVE, and the label says so — the service converts to the exclusive
                                 bound once, on its own. --}}
                            <label class="vy-field-label" for="vy-season-to">Last night (included)</label>
                            <input id="vy-season-to" name="ends_on" type="date" class="vy-input" required>
                        </div>
                        <div class="vy-field" style="margin: 0;">
                            <label class="vy-field-label" for="vy-season-price">Nightly price</label>
                            <input id="vy-season-price" name="price" type="text" inputmode="decimal" class="vy-input" required>
                        </div>
                    </div>
                    @error('price')
                        <div style="font-size: 12px; color: var(--vy-danger, #b91c1c); margin-top: 8px;">{{ $message }}</div>
                    @enderror
                    <div style="display: flex; justify-content: flex-end; margin-top: 12px;">
                        <button type="submit" class="vy-btn">Save season</button>
                    </div>
                </form>

                {{-- ── ⚠️⚠️ THE MONEY STEP. A separate, deliberate second action. ─────────────────────────
                     ⚠️ The D2 dialog contract, unchanged: role, aria-modal, a name that resolves,
                     tabindex="-1", x-trap bound to the show expression, and Escape. --}}
            </div>

        {{-- ══ F-37 · APPLY — LIFTED OUT OF THE CARD (FIX 16, 2026-09-14) ═══════════════════════════════
             ⚠️⚠️ IT USED TO SIT INSIDE THE `.vy-card` ABOVE, AND THAT MADE IT A BROKEN MODAL. A card is a
             containing block for `position: fixed` — 🔬 MEASURED: every `.vy-card` in the product computes
             `backdrop-filter: blur(22px) saturate(1.15)`, and a backdrop-filter creates a containing block
             on its own, with or without a transform. So this overlay resolved against the CARD, not the
             viewport: 341×855 at (17,284) in a 375×812 window — the backdrop never covered the page, an
             outside click could not reach it, and at some scroll offsets the panel ran off the bottom
             (🔬 scrollY 2400: panel bottom 861 in an 812 viewport — 49px below the fold).

             ⚠️ WHY IT SURVIVED FIX 02, WHICH LIFTED ITS TWO SIBLINGS: `editSeason` and `deleteSeason` threw
             `ReferenceError` on every load, so something POINTED at them. `confirmSeason` threw nothing —
             it opened, the D2 contract held, the console was clean, and only the geometry was wrong.
             ⚠️ NO GUARD, NO CONSOLE SWEEP, NO MARKUP TEST AND NO ACCESSIBILITY CHECK CAN SEE THIS — only a
             measured rect can, and this repo has no re-runnable browser suite (the Playwright work is MCP-
             driven and not part of the regression net). So this placement is defended by a SOURCE test that
             asserts no `role="dialog"` overlay has a `.vy-card` ancestor — a proxy for the real property,
             and honest about being one.

             ⚠️⚠️ AND THE ANIMATION IS NOT THE CAUSE, THOUGH IT LOOKS LIKE IT. `vyFadeUp` does end on
             `transform: translateY(0)` (an identity matrix, which also clamps) — but 🔬 18 of the 25 cards
             on this screen carry NO transform and clamp anyway, via backdrop-filter. Changing the keyframes
             would have fixed nothing and touched ten layouts. Measured before it was attempted. --}}
                <div x-show="confirmSeason !== null" x-cloak
                     style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;"
                     x-on:click.self="confirmSeason = null" @keydown.escape.window="confirmSeason = null">
                    <div class="vy-card" style="max-width: 520px; width: 100%; max-height: 90vh; overflow: auto;"
                         role="dialog" aria-modal="true" aria-labelledby="vy-dlg-season-t" tabindex="-1" x-trap.noscroll="confirmSeason !== null">
                        <h3 style="font-size: 16px; font-weight: 700; color: var(--vy-ink); margin: 0 0 10px;" id="vy-dlg-season-t">Apply this season?</h3>
                        <p style="font-size: 13px; color: var(--vy-ink); margin: 0 0 10px;">
                            <span x-text="confirmSeason?.name"></span> will overwrite
                            <span x-text="confirmSeason?.changing"></span> of
                            <span x-text="confirmSeason?.nights"></span> nights across
                            <span x-text="confirmSeason?.units"></span> units, from
                            <span x-text="confirmSeason?.from_display ?? '—'"></span> to
                            <strong x-text="confirmSeason?.to_display"></strong> a night.
                        </p>
                        {{-- ⚠️⚠️ TRUE, AND VERIFIED BEFORE IT WAS WRITTEN. `setRate()` writes the new price and
                             records an audit row whose `before` is literally null, so the prices it replaces
                             are held nowhere. There is no undo to offer, so none is implied. --}}
                        <p style="font-size: 13px; color: var(--vy-ink); margin: 0 0 10px;">
                            The prices it replaces are not stored anywhere. <strong>This cannot be undone.</strong>
                        </p>
                        {{-- ⚠️⚠️ WHAT IT WOULD REPLACE THAT SOMEBODY NEGOTIATED. This TELLS; it does not ask.
                             Applying is still permitted and nothing here refuses it — whether a season
                             SHOULD be able to overwrite an agreed rate is an open decision, not this
                             dialog's to make. The button below is unchanged.
                             ⚠️ "Negotiated" is exact: it covers rates an owner agreed through a proposal,
                             which is the only kind that leaves a record. See AgreedRateDisclosure. --}}
                        <template x-if="confirmSeason?.agreed_nights > 0">
                            <p style="font-size: 13px; color: var(--vy-ink); margin: 0 0 10px; border-left: 3px solid var(--vy-accent); padding-left: 10px;">
                                <strong><span x-text="confirmSeason?.agreed_nights"></span></strong>
                                <span x-text="confirmSeason?.agreed_nights === 1 ? 'of those nights carries' : 'of those nights carry'"></span>
                                a rate negotiated with
                                <span x-text="confirmSeason?.agreed_owners === 1 ? 'an owner' : confirmSeason?.agreed_owners + ' owners'"></span>
                                (<span x-text="confirmSeason?.agreed_from"></span> to <span x-text="confirmSeason?.agreed_to"></span>).
                                Applying replaces those prices, and the owner is not told.
                            </p>
                        </template>
                        <template x-if="confirmSeason?.losing_nights > 0">
                            <p style="font-size: 13px; color: var(--vy-ink); margin: 0 0 10px;">
                                <span x-text="confirmSeason?.losing_nights"></span> of those nights are governed by
                                <span x-text="confirmSeason?.losing_to"></span>, which wins under the rule and will be written last.
                            </p>
                        </template>
                        <template x-if="confirmSeason?.rewritten_nights > 0">
                            <p style="font-size: 13px; color: var(--vy-ink); margin: 0 0 10px;">
                                Applying also re-writes <span x-text="confirmSeason?.rewritten_nights"></span> nights from overlapping seasons that are already applied.
                            </p>
                        </template>
                        <p style="font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px;">
                            Stays already booked keep the price they were booked at — this changes what is offered from now on.
                        </p>
                        <form method="POST" x-bind:action="confirmSeason?.apply_url ?? ''"
                              style="display: flex; gap: 10px; justify-content: flex-end;">
                            @csrf
                            <button type="button" class="vy-btn" style="padding: 9px 16px;" x-on:click="confirmSeason = null">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent" style="padding: 9px 16px;">Apply and overwrite those nights</button>
                        </form>
                    </div>
                </div>

        {{-- ── EDIT a DRAFT season. ⚠️ It saves and returns to the preview; it does NOT apply. ─────────── --}}
        {{-- ⚠️ THE DIALOG CONTRACT, MATCHED EXACTLY. The trap expression must be the SAME expression as
             the outer `x-show`, and Escape belongs on the outer element — the contract guard caught
             both when these dialogs were first written with the trap bound to a different form of
             the same condition. All thirty-seven dialogs share this shape; this makes two more. --}}
        <div x-show="editSeason !== null" x-cloak
             style="position: fixed; inset: 0; z-index: 60; background: rgba(43,35,32,.42); display: flex; align-items: center; justify-content: center; padding: 20px;"
             x-on:click.self="editSeason = null" @keydown.escape.window="editSeason = null">
            <div class="vy-card" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="editSeason !== null"
                 aria-labelledby="vy-dlg-season-edit" style="max-width: 460px; width: 100%;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--vy-ink); margin: 0 0 10px;" id="vy-dlg-season-edit">Edit this season</h3>

                {{-- ⚠️⚠️ THE TWO-STEP IS PRESERVED, AND THIS SENTENCE IS THE PROMISE. Saving records the new
                     shape; it writes no prices. The blast radius is reviewed again, then applied — exactly
                     as creating one does. An edit that applied immediately would destroy that. --}}
                <p style="font-size: 13px; color: var(--vy-ink); margin: 0 0 12px;">
                    Saving updates the season only. <strong>No prices are written</strong> until you apply it.
                </p>

                <form method="POST" x-bind:action="editSeason?.update_url ?? ''">
                    @csrf
                    @method('PUT')
                    <label class="vy-field-label" for="vy-season-edit-name">Name</label>
                    <input id="vy-season-edit-name" class="vy-input" type="text" name="name" x-bind:value="editSeason?.name" maxlength="80" required>

                    <label class="vy-field-label" for="vy-season-edit-from" style="margin-top: 10px;">From</label>
                    <input id="vy-season-edit-from" class="vy-input" type="date" name="starts_on" x-bind:value="editSeason?.starts_on" required>

                    <label class="vy-field-label" for="vy-season-edit-to" style="margin-top: 10px;">To</label>
                    <input id="vy-season-edit-to" class="vy-input" type="date" name="ends_on" x-bind:value="editSeason?.ends_on" required>

                    <label class="vy-field-label" for="vy-season-edit-price" style="margin-top: 10px;">Nightly price</label>
                    <input id="vy-season-edit-price" class="vy-input" type="text" name="price" x-bind:value="editSeason?.price_input" required>

                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 14px;">
                        <button type="button" class="vy-btn" style="padding: 9px 16px;" x-on:click="editSeason = null">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent" style="padding: 9px 16px;">Save changes</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── DELETE a season. ⚠️⚠️ NOT AN UNDO, AND IT SAYS SO. ─────────────────────────────────────── --}}
        {{-- ⚠️ THE DIALOG CONTRACT, MATCHED EXACTLY. The trap expression must be the SAME expression as
             the outer `x-show`, and Escape belongs on the outer element — the contract guard caught
             both when these dialogs were first written with the trap bound to a different form of
             the same condition. All thirty-seven dialogs share this shape; this makes two more. --}}
        <div x-show="deleteSeason !== null" x-cloak
             style="position: fixed; inset: 0; z-index: 60; background: rgba(43,35,32,.42); display: flex; align-items: center; justify-content: center; padding: 20px;"
             x-on:click.self="deleteSeason = null" @keydown.escape.window="deleteSeason = null">
            <div class="vy-card" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="deleteSeason !== null"
                 aria-labelledby="vy-dlg-season-delete" style="max-width: 460px; width: 100%;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--vy-ink); margin: 0 0 10px;" id="vy-dlg-season-delete">Delete this season?</h3>

                {{-- ⚠️⚠️ A CONTROL MAY NOT IMPLY MORE THAN IT DOES. Deleting an APPLIED season removes the
                     record and leaves every night it wrote exactly as it wrote them — the prices it replaced
                     are stored nowhere. Measured: applying moved a night to €333.00 and deleting the season
                     left it at €333.00. An operator must not infer a revert that cannot happen. --}}
                <template x-if="deleteSeason?.applied">
                    <p style="font-size: 13px; color: var(--vy-ink); margin: 0 0 12px;">
                        This season has been applied. Deleting it removes the season only —
                        <strong>the nightly prices it wrote stay as they are</strong>, and the prices they replaced are
                        not stored anywhere. Set those nights yourself if they are wrong.
                    </p>
                </template>
                <template x-if="! deleteSeason?.applied">
                    <p style="font-size: 13px; color: var(--vy-ink); margin: 0 0 12px;">
                        This season has not been applied, so <strong>no prices change</strong>.
                    </p>
                </template>

                <form method="POST" x-bind:action="deleteSeason?.delete_url ?? ''" style="display: flex; gap: 10px; justify-content: flex-end;">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="vy-btn" style="padding: 9px 16px;" x-on:click="deleteSeason = null">Cancel</button>
                    <button type="submit" class="vy-btn vy-btn--accent" style="padding: 9px 16px;">Delete season</button>
                </form>
            </div>
        </div>
        </div>{{-- closes the season scope wrapper opened above the card --}}

        @endcan

        {{-- ── Rate plans — REAL base + derived plans (#100). Derived = base × adjustment (reconciles). ──── --}}
        {{-- ⚠️⚠️ THIS WRAPPER IS LOAD-BEARING, AND IT IS FIX 02'S SHAPE, COPIED (F-34 / FIX 15).
             🔬 MEASURED IN THE BROWSER, NOT READ FROM THE SOURCE: the root `x-data` at the top of this file
             does NOT contain this card. Its 11 children are the toolbar and the grid; `Alpine.$data`-style
             ancestor-walking from this card's buttons found ZERO `x-data` ancestors. Declaring the state up
             there and putting controls down here reproduced F-20 EXACTLY — 16 console errors,
             "editPlanFor is not defined", with PHPStan, Pint and the Blade compile all green. A markup test
             would have passed.
             ⚠️ So the scope is SELF-CONTAINED, exactly like the season wrapper below: a PLAIN div, never a
             `.vy-fade`, enclosing the card AND its dialogs and nothing else. `.vy-fade` finishes on
             `transform: translateY(0)`, which makes a containing block and clamps a `position: fixed` panel
             to the card's box — the defect FIX 02's first attempt shipped, with the panel open off-screen. --}}
        @if (count($ratePlans) > 0)
            <div x-data="{ editPlanFor: null, activePlanFor: null }">
            <div class="vy-card vy-fade" style="margin-top: 18px;">
                <h2 class="vy-card-title" style="margin-bottom: 12px;">Rate plans</h2>
                @foreach ($ratePlans as $plan)
                    <div style="display: flex; align-items: center; gap: 12px; padding: 9px 0; border-top: 1px solid var(--vy-hairline);">
                        <div style="flex: 1;">
                            <div style="font-size: 13.5px; color: var(--vy-ink); font-weight: 600;">{{ $plan['name'] }} @if ($plan['is_derived'])<span style="color: var(--vy-accent); font-weight: 500;"> · derived</span>@endif</div>
                            {{-- D18-6 — the room type this plan prices, ahead of the plan's own meta. Several
                                 types carry a plan of the same name (the demo has three "Flexible"), and
                                 without this the rows were word-for-word identical. Omitted when absent
                                 rather than printed as an empty separator. --}}
                            {{-- ⚠️ ONE expression, not an @if wrapped around the text: Livewire injects
                                 `<!--[if BLOCK]-->` markers around every conditional, which would split this
                                 line in the delivered HTML (invisible on screen, but it breaks anything
                                 reading the rendered text — including a test). The room type is omitted
                                 when absent rather than printing a lone separator. --}}
                            <div style="font-size: 12px; color: var(--vy-muted);">{{ ($plan['room_type'] !== '' ? $plan['room_type'].' · ' : '').$plan['sub'] }}</div>
                        </div>
                        <span style="font-size: 11.5px; color: var(--vy-faint);">{{ $plan['channel'] }}</span>
                        {{-- ⚠️ F-34 / FIX 15 — THE CONTROLS FIX 12 DEFERRED. It built the routes, the service
                             and the error region and deliberately wired NO controls, because this screen
                             carries the season dialogs whose `x-data` scope was the one "correct code nobody
                             could reach" defect in fifteen audit phases. That left the capability real and
                             unreachable — F-20's exact shape, created by the fix programme itself.
                             ⚠️ These are BUTTONS, not forms: they only set state on the ROOT x-data (line 37,
                             a plain div — NOT a `.vy-fade`), whose dialogs live outside every card below. --}}
                        @if ($canManage)
                            @unless ($plan['is_active'])
                                <span style="font-size:11px;font-weight:600;color:var(--vy-accent);background:rgba(194,65,12,.10);border-radius:6px;padding:2px 7px;">Off sale</span>
                            @endunless
                            {{-- ⚠️ The units screen's exact control style (FIX 10), not a new one — and NOT
                                 `vy-btn--sm`, which does not exist in the stylesheet. --}}
                            <button type="button"
                                    style="padding:0;border:0;background:none;font-size:11.5px;color:var(--vy-accent);cursor:pointer;text-decoration:underline;"
                                    x-on:click="editPlanFor = '{{ $plan['id'] }}'">Rename {{ $plan['name'] }}</button>
                            <button type="button"
                                    style="padding:0;border:0;background:none;font-size:11.5px;color:var(--vy-muted);cursor:pointer;text-decoration:underline;"
                                    x-on:click="activePlanFor = '{{ $plan['id'] }}'">{{ $plan['is_active'] ? 'Take '.$plan['name'].' off sale' : 'Put '.$plan['name'].' back on sale' }}</button>
                        @endif
                    </div>
                @endforeach
            </div>

    {{-- ══ F-34 / FIX 15 · RATE-PLAN RENAME + ON/OFF-SALE DIALOGS ═══════════════════════════════════
         ⚠️ PLACED HERE ON PURPOSE, AND THE PLACEMENT IS THE WHOLE LESSON OF FIX 02. They sit inside the
         ROOT `x-data` (line 37 — a plain div that declares `editPlanFor`/`activePlanFor`) and OUTSIDE every
         `.vy-fade` card. `.vy-fade` finishes its animation on `transform: translateY(0)`; a transform makes
         the element a CONTAINING BLOCK for `position: fixed`, so a panel nested inside a card is clamped to
         the card's box instead of the viewport. FIX 02's first attempt did exactly that and the season
         dialogs opened OFF-SCREEN — measured then at a panel y of 1208 in a 900px viewport, open and unseen.
         A markup test passed throughout.
         ⚠️ The D2 contract is copied from the unit-price dialog on this same screen, not invented: role,
         aria-modal, a RESOLVING aria-labelledby, tabindex="-1", a trap bound to ITS OWN show expression,
         and Escape. --}}
        <div x-cloak>
            @foreach ($ratePlans as $vyP)
                {{-- RENAME --}}
                <div x-show="editPlanFor === '{{ $vyP['id'] }}'" x-cloak
                     style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;"
                     x-on:click.self="editPlanFor = null" x-on:keydown.escape.window="editPlanFor = null">
                    <div class="vy-card" style="max-width: 460px; width: 100%;"
                         role="dialog" aria-modal="true" tabindex="-1"
                         x-trap.noscroll="editPlanFor === '{{ $vyP['id'] }}'"
                         aria-labelledby="vy-dlg-rg-plan-edit-{{ $vyP['id'] }}">
                        <h2 id="vy-dlg-rg-plan-edit-{{ $vyP['id'] }}" style="margin:0 0 4px;font-size:17px;">Rename rate plan {{ $vyP['name'] }}</h2>
                        {{-- ⚠️ TRUE AND NARROW. `updateRatePlan()` accepts `name` and `code` and refuses
                             currency, room type, the derived basis and the cancellation terms — each for a
                             measured reason recorded on that method. So this dialog can promise that no
                             price moves, because the route cannot move one. --}}
                        <p class="vy-line-sub" style="margin:0 0 14px;">Only the name and code change. No nightly price moves, and a stay already sold keeps the price and terms it was sold with.</p>
                        <form method="POST" action="{{ route('app.inventory.rate-plans.update', $vyP['id']) }}">
                            @csrf @method('PUT')
                            <label class="vy-field"><span class="vy-label">Plan name</span>
                                <input class="vy-input" type="text" name="name" maxlength="255" value="{{ $vyP['name'] }}" required></label>
                            <label class="vy-field"><span class="vy-label">Code <span style="color:var(--vy-faint);font-weight:400;">(optional)</span></span>
                                <input class="vy-input" type="text" name="code" maxlength="60" value="{{ $vyP['code'] }}"></label>
                            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px;">
                                <button type="button" class="vy-btn" x-on:click="editPlanFor = null">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Save plan name</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ON / OFF SALE --}}
                <div x-show="activePlanFor === '{{ $vyP['id'] }}'" x-cloak
                     style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;"
                     x-on:click.self="activePlanFor = null" x-on:keydown.escape.window="activePlanFor = null">
                    <div class="vy-card" style="max-width: 480px; width: 100%;"
                         role="dialog" aria-modal="true" tabindex="-1"
                         x-trap.noscroll="activePlanFor === '{{ $vyP['id'] }}'"
                         aria-labelledby="vy-dlg-rg-plan-active-{{ $vyP['id'] }}">
                        <h2 id="vy-dlg-rg-plan-active-{{ $vyP['id'] }}" style="margin:0 0 4px;font-size:17px;">{{ $vyP['is_active'] ? 'Take '.$vyP['name'].' off sale?' : 'Put '.$vyP['name'].' back on sale?' }}</h2>
                        @if ($vyP['is_active'])
                            {{-- ⚠️ MEASURED, NOT ASSERTED. The storefront prices a stranger through
                                 `BookingEngineService::cheapestRatePlan()`, which reads only active plans, so
                                 taking one off sale really does stop it being quoted. Existing bookings are
                                 untouched: `reservation_units` snapshots `price_minor` at booking. And no
                                 `rate_calendar` row is written or deleted — the prices stay where they are. --}}
                            <p class="vy-line-sub" style="margin:0 0 14px;">It stops being quoted to new guests straight away. Bookings already taken on it keep their price and terms, and its nightly prices are kept, so putting it back on sale restores them unchanged.</p>
                        @else
                            <p class="vy-line-sub" style="margin:0 0 14px;">It can be quoted to new guests again, at the nightly prices it already has.</p>
                        @endif
                        <form method="POST" action="{{ route('app.inventory.rate-plans.active', $vyP['id']) }}">
                            @csrf
                            <input type="hidden" name="active" value="{{ $vyP['is_active'] ? '0' : '1' }}">
                            <div style="display:flex;gap:8px;justify-content:flex-end;">
                                <button type="button" class="vy-btn" x-on:click="activePlanFor = null">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">{{ $vyP['is_active'] ? 'Take off sale' : 'Put back on sale' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
            </div>{{-- closes the rate-plan scope opened above the card --}}
        @endif

        {{-- ── Channels — HONEST (#85): Direct live; OTA sync needs setup, never a fabricated "Synced". ──── --}}
        <div class="vy-card vy-fade" style="margin-top: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h2 class="vy-card-title" style="margin: 0;">Channels</h2>
                <span style="font-size: 12px; color: var(--vy-muted);">{{ $channels['summary'] }}</span>
            </div>
            @foreach ($channels['rows'] as $ch)
                <div style="display: flex; align-items: center; gap: 12px; padding: 9px 0; border-top: 1px solid var(--vy-hairline);">
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: rgba(194,65,12,.10); color: var(--vy-accent); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">{{ $ch['init'] }}</span>
                    <div style="flex: 1; font-size: 13.5px; color: var(--vy-ink);">{{ $ch['name'] }}</div>
                    <span style="font-size: 12px; color: {{ $ch['live'] ? 'var(--vy-accent)' : 'var(--vy-faint)' }};">{{ $ch['status'] }}</span>
                </div>
            @endforeach
            <p style="font-size: 11px; color: var(--vy-faint); margin: 10px 0 0;">Live OTA sync (push rates &amp; availability to channels) activates once your channel connection is set up.</p>
        </div>

        {{-- ── Pacing — a REAL derived signal (booked % + open nights); no fabricated "vs last year" trend. ── --}}
        <div class="vy-card vy-fade" style="margin-top: 18px;">
            <h2 class="vy-card-title" style="margin-bottom: 8px;">Pacing · next 14 days</h2>
            <div style="font-family: var(--vy-display); font-size: 26px; font-weight: 700; color: var(--vy-ink);">{{ $pacing['booked_pct'] !== null ? $pacing['booked_pct'].'%' : '—' }}</div>
            <div style="font-size: 12.5px; color: var(--vy-muted); margin-top: 4px;">{{ $pacing['insight'] }}</div>
        </div>


        {{-- ══ P-6 · Gate 1 — RATE PLAN CREATION ═══════════════════════════════════════════════════════
             ⚠️ Before this, RatePlan had NO creation path in the application. RateActionController could
             REPORT that a room type had no plan ("nowhere to store a price") but offered no remedy, and
             `search()` skips any room type whose plans cannot price the stay — so the room type was
             silently unsellable. Same POST → controller → PRG pattern as everything else here (§N.8f).
             ⚠️ No price is set here: prices are rate_calendar rows, written by the grid above through the
             existing certified RateAndRestrictionService path. --}}
        @if ($canManage && count($roomTypeOptions) > 0)
            <div class="vy-card vy-fade" id="vy-add-rate-plan" style="margin-top:18px">
                <h2 class="vy-card-title">Add a rate plan</h2>
                <p class="vy-line-sub" style="margin-bottom:14px">
                    A rate plan is what nightly prices hang off. Create one per room type, then set its
                    prices in the grid above. It inherits the property's currency.
                </p>
                <form method="POST" action="{{ route('app.inventory.rate-plans.store') }}"
                      style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end">
                    @csrf
                    <label class="vy-field"><span class="vy-label">Room type</span>
                        <select class="vy-input" name="room_type_id" required>
                            @foreach ($roomTypeOptions as $rt)
                                {{-- ⚠️ THE SPACE BEFORE @endunless IS LOAD-BEARING. Blade does not compile a directive whose
                                     `@` follows a word character (the \B rule that lets foo@bar.com render literally), so
                                     "yet@endunless" shipped as literal text, left the @unless unclosed, and the page 500'd
                                     on the next @endforeach. Tests, PHPStan and Pint were all green; only opening the page
                                     showed it. --}}
                                <option value="{{ $rt['id'] }}">{{ $rt['name'] }}@unless ($rt['has_plan']) — no plan yet @endunless</option>
                            @endforeach
                        </select></label>
                    <label class="vy-field"><span class="vy-label">Plan name</span>
                        <input class="vy-input" name="name" required maxlength="255" placeholder="Flexible"></label>
                    <label class="vy-field" style="flex-direction:row;align-items:center;gap:8px">
                        <input type="checkbox" name="is_refundable" value="1" checked>
                        <span class="vy-label" style="margin:0">Refundable</span></label>
                    <button type="submit" class="vy-btn">Add rate plan</button>
                </form>
            </div>
        @endif

    </x-operator.shell>

    <script>
        function ratesEditor() {
            return {
                roomType: @js($formDefaults['from'] ? ($roomTypeOptions[0]['id'] ?? 'all') : 'all'),
                from: @js($formDefaults['from']),
                to: @js($formDefaults['to']),
                rate: '',
                setRate: true,
                setMin: false,
                minStay: '',
                setStop: false,
                stopSell: false,
                updateDerived: false,
                bulkOpen: false,
                unitOpen: false,
                unitMode: 'set',
                unitRt: '{{ $roomTypeOptions[0]["id"] ?? "" }}',
                buMode: 'increase_pct',
                edit(roomType, date, rate, minStay, stopSell) {
                    this.roomType = roomType;
                    this.from = date;
                    this.to = date;
                    this.rate = rate;
                    this.setRate = true;
                    this.minStay = minStay === null ? '' : String(minStay);
                    this.setMin = minStay !== null;
                    this.stopSell = stopSell;
                    this.setStop = stopSell;
                    this.$el.scrollIntoView({ behavior: 'smooth', block: 'end' });
                },
            };
        }
    </script>
</div>
