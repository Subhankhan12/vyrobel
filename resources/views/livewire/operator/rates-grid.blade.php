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
                                    <input type="text" name="rate" class="vy-input" placeholder="e.g. 175 or 175.50"
                                        inputmode="decimal" x-model="rate" x-bind:disabled="! setRate">
                                </div>

                                <div>
                                    <label class="vyr-check">
                                        <input type="checkbox" name="set_min_stay" value="1" x-model="setMin"> Set minimum stay
                                    </label>
                                    <input type="number" name="min_stay" class="vy-input" min="1" max="365" placeholder="nights (blank = none)"
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
                    <div x-show="bulkOpen" x-cloak style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;" x-on:click.self="bulkOpen = false">
                        <div class="vy-card" style="max-width: 520px; width: 100%; max-height: 90vh; overflow: auto;">
                            <h3 style="font-size: 16px; font-weight: 700; color: var(--vy-ink); margin: 0 0 2px;">Bulk edit rates</h3>
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
                                        <label class="vyr-check" style="margin: 0;"><input type="radio" name="mode" value="set_exact" x-model="buMode"> Set exact €</label>
                                    </div>
                                    <input type="text" name="amount" class="vy-input" inputmode="decimal" x-bind:placeholder="buMode === 'set_exact' ? 'e.g. 175.00' : 'e.g. 10 (percent)'" required>
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
                    <div x-show="unitOpen" x-cloak style="position: fixed; inset: 0; background: rgba(40,25,15,.45); z-index: 60; display: flex; align-items: center; justify-content: center; padding: 20px;" x-on:click.self="unitOpen = false">
                        <div class="vy-card" style="max-width: 560px; width: 100%; max-height: 90vh; overflow: auto;">
                            {{-- ⚠️ THE ONE FORM (§N.8f) — the same component the Units list opens for a single
                                 unit, so the two homes cannot drift. Dates bind to this grid's live window. --}}
                            <x-rates.unit-price-form
                                :room-types="$roomTypeOptions"
                                :units="$unitOptions"
                                :bind-dates="true"
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
                                 x-on:click.self="unitPriceFor = null">
                                <div class="vy-card" style="max-width: 520px; width: 100%; max-height: 90vh; overflow: auto;"
                                     x-data="{ unitMode: 'set', unitRt: '{{ $vyRow['id'] }}' }">
                                    <x-rates.unit-price-form
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

        {{-- ── Rate plans — REAL base + derived plans (#100). Derived = base × adjustment (reconciles). ──── --}}
        @if (count($ratePlans) > 0)
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
                    </div>
                @endforeach
            </div>
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
