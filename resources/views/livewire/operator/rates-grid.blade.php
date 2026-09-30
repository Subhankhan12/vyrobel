{{-- Operator RATES & AVAILABILITY — rebuilt 2026-09-30 (the founder: "table design must be better, texts must look good, all
     texts and boxes must be well aligned … the kind of work done on the past pages … every single operation working";
     the 9-part plan approved, DEC-l included). See App\Livewire\Operator\RatesGrid for the REAL / DERIVED / OMITTED map.

     One Alpine scope for the whole page (a plain div — never a `.vy-fade`, whose transform would clamp a fixed dialog), and
     every dialog sits OUTSIDE every card (DialogNotClampedTest), centred by the shared `.vy-modal-backdrop` class — never by
     an inline display beside `x-show`, which Alpine erases on the first close (🖥 every dialog here opened in the top-left
     corner: Bulk edit centred at 280,242 in a 1440×900 window).

     PER-ROOM-TYPE, NOT per-unit (STRUCT-1): rate_calendar is keyed on rate_plan_id, so each row edits a room type's primary
     rate plan; the unit rows under it are READ-ONLY (D18-6) and a unit is priced apart only through the one per-unit form.

     RAW-BLADE RULE: never write `word@directive` — a directive whose `@` follows a word character ships raw source to the
     page. Alpine handlers use `x-on:` / `x-bind:` so no `@` reaches an attribute value. --}}
<div>
    <x-operator.shell active="rates" title="Rates & availability" :subtitle="$subtitle" :viewer="$viewer">
        @php
            $vyCanWrite = $canManage && ! $readOnly;
            // a season is set on a plan that is on sale — with none, the season form is not offered
            $vyActivePlans = collect($ratePlans)->where('is_active', true)->count();
            $vyBoot = [
                'names' => collect($roomTypeOptions)->mapWithKeys(fn (array $o): array => [$o['id'] => $o['name']])->all(),
                'from' => $formDefaults['from'],
                'to' => $formDefaults['to'],
                'firstRt' => $roomTypeOptions[0]['id'] ?? 'all',
                'currency' => $currencySymbol,
                'main' => $mainPlans,
                'planNames' => collect($ratePlans)->mapWithKeys(fn (array $p): array => [$p['id'] => $p['name']])->all(),
            ];
        @endphp

        {{-- ⚠️ A refusal is an ALERT (`rates_error`); a success is a notice. Until 2026-09-30 most refusals arrived as
             `rates_notice` and announced nothing. --}}
        @if (session('rates_error'))
            <div class="vy-card vy-fade vyr-flash is-error" role="alert">{{ session('rates_error') }}</div>
        @endif
        {{-- The one refusal that arrives as a validation error (SeasonEditDeleteTest pins the key): editing an APPLIED season.
             ⚠️ It was set and never shown — the page rendered only `price`. --}}
        @error('season')
            <div class="vy-card vy-fade vyr-flash is-error" role="alert">{{ $message }}</div>
        @enderror
        @if (session('rates_notice'))
            <div class="vy-card vy-fade vyr-flash" role="status">{{ session('rates_notice') }}</div>
        @endif

        {{-- ── D18-5 · step 2 of 2: THE PREVIEW IS THE APPROVAL GATE — nothing has been written yet. ⚠️ The digest is a hash
             of every (unit, night, from, to) tuple shown here; the apply route re-computes it inside the transaction and
             refuses if anything moved. Placed FIRST, so the operator sees it on arrival instead of below the grid. ──────── --}}
        @can('rates.manage')
            @if (session('unit_rate_preview'))
                {{-- ⚠️ A BLOCK, never the inline `@php(…)` form: Blade matches `@php … @endphp` across the file, so an inline
                     one above a later block swallows everything between them as raw PHP (it did, 2026-10-01). --}}
                @php
                    $vyPreview = session('unit_rate_preview');
                @endphp
                <div class="vy-card vy-fade vyr-unitpreview">
                    <h2 class="vy-card-title">Review before applying</h2>
                    <p class="vyr-panel-sub">
                        {{ $vyPreview['room_type_name'] }} · {{ count($vyPreview['units']) }} {{ count($vyPreview['units']) === 1 ? 'unit' : 'units' }} ·
                        {{ $vyPreview['nights'] }} {{ $vyPreview['nights'] === 1 ? 'night' : 'nights' }}, {{ \Carbon\CarbonImmutable::parse($vyPreview['start'])->format('D j M') }} – {{ \Carbon\CarbonImmutable::parse($vyPreview['end_inclusive'])->format('D j M Y') }} ·
                        <strong>nothing has been changed yet</strong>
                    </p>
                    <div class="vyr-tablewrap">
                        <table class="vy-table">
                            <thead><tr><th>Unit</th><th>Now</th><th>After</th><th>Nights</th></tr></thead>
                            <tbody>
                                @foreach ($vyPreview['units'] as $vyRow)
                                    <tr>
                                        <td>{{ $vyPreview['names'][$vyRow['unit_id']] ?? $vyRow['unit_id'] }}</td>
                                        <td>{{ $vyRow['from_minor'] === null ? '—' : \App\Support\Money\MoneyFormatter::display($vyRow['from_minor'], $vyPreview['currency']) }}</td>
                                        <td>
                                            <strong>{{ $vyRow['to_minor'] === null ? '—' : \App\Support\Money\MoneyFormatter::display($vyRow['to_minor'], $vyPreview['currency']) }}</strong>
                                            @if ($vyRow['state'] === 'cleared')
                                                <span class="vyr-muted"> · room-type rate</span>
                                            @elseif ($vyRow['state'] === 'pinned')
                                                <span class="vyr-muted"> · unchanged, but pinned to this unit</span>
                                            @endif
                                        </td>
                                        <td>{{ $vyRow['nights_changing'] }} of {{ $vyRow['nights'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($vyPreview['skipped_units'] > 0)
                        <p class="vyr-panel-sub">{{ $vyPreview['skipped_units'] }} {{ $vyPreview['skipped_units'] === 1 ? 'unit has' : 'units have' }} no per-unit price in this range and will be left alone.</p>
                    @endif
                    <form method="POST" action="{{ route('app.rates.units.apply') }}" class="vyr-actions-end" x-data x-on:submit="$event.target.querySelector('[name=return_query]').value = location.search.replace(/^\?/, '')">
                        @csrf
                        <input type="hidden" name="return" value="list">
                        <input type="hidden" name="return_query" value="">
                        <input type="hidden" name="digest" value="{{ $vyPreview['digest'] }}">
                        <input type="hidden" name="start_date" value="{{ $vyPreview['start'] }}">
                        <input type="hidden" name="end_date" value="{{ $vyPreview['end_inclusive'] }}">
                        <input type="hidden" name="room_type_id" value="{{ $vyPreview['room_type_id'] }}">
                        <input type="hidden" name="mode" value="{{ $vyPreview['mode'] }}">
                        <input type="hidden" name="amount" value="{{ $vyPreview['amount'] }}">
                        @foreach ($vyPreview['units'] as $vyRow)
                            <input type="hidden" name="unit_ids[]" value="{{ $vyRow['unit_id'] }}">
                        @endforeach
                        {{-- Cancel writes nothing: the page as it was (the preview is a one-read flash, so it is gone on arrival). --}}
                        <a class="vy-btn vy-btn--ghost" href="{{ \App\Support\Rates\RatesListParams::returnUrl(request()->getQueryString()) }}">Cancel</a>
                        <button type="submit" class="vy-btn vy-btn--accent">Apply to {{ count($vyPreview['units']) }} {{ count($vyPreview['units']) === 1 ? 'unit' : 'units' }}</button>
                    </form>
                </div>
            @endif
        @endcan

        <div class="vy-rates" x-data="vyRates({{ \Illuminate\Support\Js::from($vyBoot) }})" x-on:pointerup.window="dragEnd()">

            {{-- ── Toolbar: the fortnight, the filter, and what a manager can do ─────────────────────────────────────── --}}
            <div class="vyr-bar vy-fade">
                <div class="vyr-winnav">
                    <a class="vyr-navbtn" href="{{ $window['prev'] }}" aria-label="Previous two weeks" title="Previous two weeks">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </a>
                    <span class="vyr-winlabel">{{ $window['label'] }}</span>
                    <a class="vyr-navbtn" href="{{ $window['next'] }}" aria-label="Next two weeks" title="Next two weeks">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                    <a class="vy-btn vy-btn--ghost vyr-tool" href="{{ $window['today'] }}">Today</a>
                </div>
                @if (count($roomTypeOptions) > 1)
                    {{-- #104 GATE 2d — narrows the grid to one room type; keeps the window. --}}
                    <label class="vy-sr-only" for="vyr-filter">Show room type</label>
                    <select id="vyr-filter" class="vy-input vyr-filter" x-on:change="filterTo($event.target.value)">
                        <option value="" @selected($rtFilter === '')>All room types</option>
                        @foreach ($roomTypeOptions as $opt)
                            <option value="{{ $opt['id'] }}" @selected($rtFilter === $opt['id'])>{{ $opt['name'] }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($window['jump'])
                    <a class="vyr-jump" href="{{ $window['jump']['href'] }}">No prices here — go to the first priced night, {{ \Carbon\CarbonImmutable::parse($window['jump']['date'])->format('j M Y') }}</a>
                @endif
                @if ($vyCanWrite && count($rows) > 0)
                    <div class="vyr-bar-tools">
                        <button type="button" class="vy-btn vy-btn--ghost vyr-tool" x-on:click="openBulk()">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 12h10M4 17h6"/></svg>
                            Bulk edit
                        </button>
                        <button type="button" class="vy-btn vy-btn--ghost vyr-tool" x-on:click="unitOpen = true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                            Price units individually
                        </button>
                        <button type="button" class="vy-btn vy-btn--accent vyr-tool" x-on:click="openEditor(null)">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8zM7.5 7.5h.01"/></svg>
                            Set rates
                        </button>
                    </div>
                @endif
            </div>

            {{-- ── The figures — DERIVED, real aggregates. Avg PER CURRENCY (never summed across). No "+8% vs Aug": no
                 historical baseline is stored, so a trend would be fabricated. ─────────────────────────────────────── --}}
            <div class="vy-dash-kpis vy-fade">
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Average nightly rate</div>
                    @forelse ($tiles['avg'] as $line)
                        <div class="vy-dk-val @if (! $loop->first) vyr-dk-val-2 @endif">{{ $line }}</div>
                    @empty
                        <div class="vy-dk-val">—</div>
                    @endforelse
                    <div class="vy-dk-sub">mean of set rates in this window{{ $tiles['multi_currency'] ? ' · per currency, never summed across' : '' }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Booked</div>
                    <div class="vy-dk-val">{{ $tiles['booked_pct'] !== null ? $tiles['booked_pct'].'%' : '—' }}</div>
                    <div class="vy-dk-sub">sold ÷ published inventory in this window</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Open nights</div>
                    <div class="vy-dk-val">{{ $tiles['open_nights'] }}</div>
                    <div class="vy-dk-sub">bookable unit-nights left in this window</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Next 14 days</div>
                    <div class="vy-dk-val">{{ $ahead['booked_pct'] !== null ? $ahead['booked_pct'].'%' : '—' }}</div>
                    <div class="vy-dk-sub">booked from today · {{ $ahead['open_nights'] }} open unit-{{ $ahead['open_nights'] === 1 ? 'night' : 'nights' }}</div>
                </div>
            </div>

            {{-- ── The grid ──────────────────────────────────────────────────────────────────────────────────────── --}}
            <div class="vy-card vy-card--flush vy-fade">
                @if (count($rows) === 0)
                    <div class="vyr-empty">
                        <strong>No room types yet</strong>
                        <span>Add a room type on the Units page, then give it a rate plan to price it here.</span>
                    </div>
                @else
                    <div class="vyr-gridhead">
                        {{-- Each swatch is the cell style it names, and is paired with its WORD (never colour alone, WCAG 1.4.1). --}}
                        <div class="vyr-legend">
                            <span><i class="vyr-sw is-open" aria-hidden="true"></i>Open</span>
                            <span><i class="vyr-sw is-last" aria-hidden="true"></i>Last one</span>
                            <span><i class="vyr-sw is-full" aria-hidden="true"></i>Full</span>
                            <span><i class="vyr-sw is-closed" aria-hidden="true"></i>Closed</span>
                            <span><i class="vyr-sw is-weekend" aria-hidden="true"></i>Fri–Sat nights</span>
                        </div>
                        @if ($vyCanWrite)
                            <span class="vyr-hint">Click a night — or drag across nights — to set prices and rules.</span>
                        @endif
                    </div>
                    <div class="vyr-scroll">
                        <table class="vyr-grid">
                            <colgroup><col class="vyr-col-rt">@foreach ($dates as $d)<col>@endforeach</colgroup>
                            <thead>
                                <tr>
                                    <th class="vyr-corner" scope="col"><span class="vy-sr-only">Room type</span></th>
                                    @foreach ($dates as $d)
                                        <th scope="col" class="vyr-dhead {{ $d['is_weekend'] ? 'is-weekend' : '' }} {{ $d['is_today'] ? 'is-today' : '' }}" @if ($d['is_today']) aria-current="date" @endif>
                                            <span class="vyr-dweekday">{{ $d['weekday'] }}</span>
                                            <span class="vyr-dday">{{ $d['day'] }}</span>
                                            <span class="vyr-dmonth">{{ $d['month'] }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr class="vyr-typerow">
                                        <th scope="row" class="vyr-rt">
                                            <span class="vyr-rt-name">{{ $row['name'] }}</span>
                                            <span class="vyr-rt-sub">{{ $row['units_label'] }}{{ $row['plan_name'] ? ' · '.$row['plan_name'] : '' }}</span>
                                            @unless ($row['has_plan'])
                                                <span class="vyr-rt-sub is-warn">
                                                    No rate plan —
                                                    @if ($vyCanWrite)
                                                        <button type="button" class="vyr-linkbtn" x-on:click="openPlanAdd('{{ $row['id'] }}')">add one</button> to price it
                                                    @else
                                                        ask an administrator to add one
                                                    @endif
                                                </span>
                                            @endunless
                                        </th>
                                        @foreach ($row['cells'] as $cell)
                                            @php
                                                $vyState = $cell['stop_sell'] ? 'is-closed' : ($cell['sold_out'] ? 'is-full' : ($cell['last_one'] ? 'is-last' : ''));
                                                $vyDay = \Carbon\CarbonImmutable::parse($cell['date'])->format('D j M');
                                            @endphp
                                            <td class="vyr-cell {{ $dates[$loop->index]['is_weekend'] ? 'is-weekend' : '' }}">
                                                @if ($vyCanWrite && $row['has_plan'])
                                                    <button type="button" class="vyr-cellbtn {{ $vyState }}"
                                                        aria-label="{{ $row['name'] }}, {{ $vyDay }}: {{ $cell['rate'] ?? 'no price' }}{{ $cell['available'] !== null ? ', '.($cell['sold_out'] ? 'full' : $cell['available'].' left') : '' }} — set prices and rules"
                                                        x-bind:class="{ 'is-selecting': inDrag('{{ $row['id'] }}', '{{ $cell['date'] }}') }"
                                                        x-on:pointerdown="dragStart($event, '{{ $row['id'] }}', '{{ $cell['date'] }}')"
                                                        x-on:pointerenter="dragOver('{{ $row['id'] }}', '{{ $cell['date'] }}')"
                                                        x-on:click="cellClick('{{ $row['id'] }}', '{{ $cell['date'] }}', {{ \Illuminate\Support\Js::from(['rate' => $cell['rate_input'], 'min' => $cell['min_stay'], 'max' => $cell['max_stay'], 'stop' => $cell['stop_sell'], 'cta' => $cell['closed_to_arrival'], 'ctd' => $cell['closed_to_departure']]) }})">
                                                        @include('livewire.operator.partials.rate-cell', ['cell' => $cell])
                                                    </button>
                                                @else
                                                    <div class="vyr-cell-static {{ $vyState }}">
                                                        @include('livewire.operator.partials.rate-cell', ['cell' => $cell])
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- ── D18-6 · THE THIRD OPTION — the units under this type, READ-ONLY. ⚠️ THERE IS DELIBERATELY NO
                                         CELL EDITOR HERE: an editable per-unit grid would mint an override on every edit and hollow the
                                         "N units affected" count above. A unit is priced apart once, on purpose, through the one form. --}}
                                    @foreach ($row['unit_rows'] as $vyUnitRow)
                                        <tr class="vyr-unitrow">
                                            <th scope="row" class="vyr-rt">
                                                <span class="vyr-rt-name">{{ $vyUnitRow['label'] }}</span>
                                                @if ($vyCanWrite)
                                                    <button type="button" class="vyr-priceind" aria-label="Price {{ $vyUnitRow['label'] }} individually"
                                                            x-on:click="unitPriceFor = '{{ $vyUnitRow['id'] }}'">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8zM7.5 7.5h.01"/></svg>
                                                        Price individually
                                                    </button>
                                                @endif
                                            </th>
                                            @foreach ($vyUnitRow['cells'] as $vyCell)
                                                <td class="vyr-cell {{ $dates[$loop->index]['is_weekend'] ? 'is-weekend' : '' }}">
                                                    {{-- ⚠️ STATIC BY CONSTRUCTION — no button, no editor, nothing to type into. --}}
                                                    <div class="vyr-cell-static is-unit">
                                                        <span class="vyr-urate {{ $vyCell['rate_fit'] }}">{{ $vyCell['rate'] ?? '—' }}</span>
                                                        @if ($vyCell['overridden'])
                                                            <i class="vyr-own" title="Priced individually"><span class="vy-sr-only">Priced individually</span></i>
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

            {{-- ══ SEASONAL PRICING ═══════════════════════════════════════════════════════════════════════════════════
                 ⚠️⚠️ THIS PANEL WRITES WHAT A GUEST IS CHARGED. Saving a season writes no prices; a separate, deliberate second
                 action applies it. ⚠️ There is deliberately NO "save and apply" shortcut. Every figure in a row comes from
                 `SeasonalRateService::preview()` via the component — nothing is counted here. --}}
            @can('rates.manage')
                {{-- ⚠️⚠️ F-20 — THE SEASON STATE IS DECLARED HERE, ON A PLAIN DIV THAT ENCLOSES THE CARD *AND* ITS DIALOGS, never on
                     a `.vy-fade` (whose transform clamps a fixed dialog to the card). SeasonEditDeleteTest walks the ancestors. --}}
                <div class="vyr-scope" x-data="{ confirmSeason: null, editSeason: null, deleteSeason: null }">
                    <section class="vy-card vy-fade" aria-labelledby="vyr-seasons-t">
                        <div class="vyr-panel-head">
                            <div>
                                <h2 class="vy-card-title" id="vyr-seasons-t">Seasonal pricing</h2>
                                <p class="vyr-panel-sub">Save a season, review what it would change, then apply it. Saving writes no prices.
                                    {{-- ⚠️ THE RULE, from `SeasonPrecedence::SENTENCE` — the copy and the code are the same string. --}}
                                    {{ $seasonRule }}</p>
                            </div>
                        </div>

                        @if (count($seasons) > 0)
                            <div class="vyr-tablewrap">
                                <table class="vy-table vyr-seasons">
                                    <thead>
                                        <tr><th>Season</th><th>Nights</th><th>Now</th><th>After</th><th>Would change</th><th>Status</th><th><span class="vy-sr-only">Actions</span></th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($seasons as $vySeason)
                                            <tr>
                                                <td>
                                                    <div class="vyr-cell-title">{{ $vySeason['name'] }}</div>
                                                    <div class="vyr-muted">{{ $vySeason['plan_name'] }}{{ $vySeason['room_type'] !== '' ? ' · '.$vySeason['room_type'] : '' }}</div>
                                                </td>
                                                <td class="vyr-nowrap">{{ \Carbon\CarbonImmutable::parse($vySeason['starts_on'])->format('j M') }} – {{ \Carbon\CarbonImmutable::parse($vySeason['ends_on'])->format('j M Y') }}</td>
                                                <td class="vyr-nowrap">{{ $vySeason['from_display'] ?? '—' }}</td>
                                                <td class="vyr-nowrap"><strong>{{ $vySeason['to_display'] }}</strong></td>
                                                <td>
                                                    {{ $vySeason['changing'] }} of {{ $vySeason['nights'] }} {{ $vySeason['nights'] === 1 ? 'night' : 'nights' }}
                                                    · {{ $vySeason['units'] }} {{ $vySeason['units'] === 1 ? 'unit' : 'units' }}
                                                    @if ($vySeason['weekend_nights'] > 0)
                                                        <div class="vyr-muted">includes {{ $vySeason['weekend_nights'] }} Fri/Sat {{ $vySeason['weekend_nights'] === 1 ? 'night' : 'nights' }}, replacing any weekend uplift</div>
                                                    @endif
                                                    @if ($vySeason['losing_nights'] > 0)
                                                        <div class="vyr-accent">{{ $vySeason['losing_nights'] }} of these {{ $vySeason['losing_nights'] === 1 ? 'night is' : 'nights are' }} governed by {{ $vySeason['losing_to'] }}, which wins under the rule</div>
                                                    @endif
                                                    @if ($vySeason['agreed_nights'] > 0)
                                                        <div class="vyr-accent">{{ $vySeason['agreed_nights'] }} of these {{ $vySeason['agreed_nights'] === 1 ? 'night carries' : 'nights carry' }} a negotiated owner rate</div>
                                                    @endif
                                                    @if ($vySeason['rewritten_nights'] > 0)
                                                        <div class="vyr-muted">applying also re-writes {{ $vySeason['rewritten_nights'] }} {{ $vySeason['rewritten_nights'] === 1 ? 'night' : 'nights' }} from overlapping seasons already applied</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($vySeason['applied'])
                                                        <span class="vy-tag tone-ok">Applied {{ $vySeason['applied_on'] }}</span>
                                                    @else
                                                        {{-- ⚠️ An unapplied season has written NOTHING — say so. --}}
                                                        <span class="vy-tag tone-muted">Saved · no prices written</span>
                                                    @endif
                                                </td>
                                                <td class="vyr-row-actions">
                                                    @if ($vyCanWrite)
                                                        {{-- ⚠️⚠️ EDIT IS OFFERED ONLY WHILE THE SEASON IS A DRAFT — applying overwrote the nightly rates and
                                                             nothing recorded what they were, so an "edit" could not put them back. --}}
                                                        @if (! $vySeason['applied'])
                                                            <button type="button" class="vy-btn vy-btn--ghost vyr-sm" x-on:click="editSeason = {{ \Illuminate\Support\Js::from($vySeason) }}">Edit</button>
                                                        @endif
                                                        <button type="button" class="vy-btn vy-btn--ghost vyr-sm" x-on:click="confirmSeason = {{ \Illuminate\Support\Js::from($vySeason) }}">{{ $vySeason['applied'] ? 'Apply again' : 'Apply' }}</button>
                                                        <button type="button" class="vy-btn vy-btn--ghost vyr-sm is-danger" x-on:click="deleteSeason = {{ \Illuminate\Support\Js::from($vySeason) }}">Delete</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="vyr-empty-line">{{ $vyCanWrite && $vyActivePlans > 0 ? 'No seasons yet — save one below, then apply it when the figures look right.' : 'No seasons yet.' }}</p>
                        @endif

                        {{-- ⚠️ A season is set on an ACTIVE plan, so with none there is nothing to offer — the form would only be refused
                             (🖥 production, 2026-10-01: an empty plan picker on a property with no room types). --}}
                        @if ($vyCanWrite && $vyActivePlans === 0)
                            <p class="vyr-empty-line">A season is set on a rate plan that is on sale — add a room type and give it a plan first.</p>
                        @elseif ($vyCanWrite)
                            {{-- ── Save a season. This writes NO prices. ─────────────────────────────────────────────────── --}}
                            <form method="POST" action="{{ route('app.seasons.store') }}" class="vyr-season-form" x-on:submit="stamp($event.target)">
                                @csrf
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="return_query" value="">
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-season-plan">Rate plan</label>
                                    <select id="vy-season-plan" name="rate_plan_id" class="vy-input" required>
                                        {{-- ⚠️ ACTIVE PLANS ONLY — a season must never be defined against an off-sale plan. --}}
                                        @foreach ($ratePlans as $vyPlan)
                                            @continue (! $vyPlan['is_active'])
                                            <option value="{{ $vyPlan['id'] }}" @selected(old('rate_plan_id') === $vyPlan['id'])>{{ $vyPlan['name'] }}{{ $vyPlan['room_type'] !== '' ? ' — '.$vyPlan['room_type'] : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-season-name">Season name</label>
                                    <input id="vy-season-name" name="name" type="text" maxlength="80" class="vy-input" value="{{ old('name') }}" placeholder="e.g. Summer peak" required>
                                </div>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-season-from">First night</label>
                                    <input id="vy-season-from" name="starts_on" type="date" class="vy-input" value="{{ old('starts_on') }}" required>
                                </div>
                                <div class="vy-field">
                                    {{-- ⚠️ INCLUSIVE, and the label says so — the service converts to the exclusive bound once. --}}
                                    <label class="vy-field-label" for="vy-season-to">Last night (included)</label>
                                    <input id="vy-season-to" name="ends_on" type="date" class="vy-input" value="{{ old('ends_on') }}" required>
                                </div>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-season-price">Nightly price</label>
                                    <input id="vy-season-price" name="price" type="text" inputmode="decimal" class="vy-input" value="{{ old('price') }}" placeholder="e.g. 175.00" required>
                                </div>
                                <button type="submit" class="vy-btn vy-btn--accent vyr-season-save">Save season</button>
                            </form>
                            {{-- ⚠️ Every refusal the save can return is shown here — only `price` was, before. --}}
                            @if ($errors->hasAny(['rate_plan_id', 'name', 'starts_on', 'ends_on', 'price']))
                                <div class="vyr-form-errors" role="alert">
                                    @foreach (['rate_plan_id', 'name', 'starts_on', 'ends_on', 'price'] as $vyField)
                                        @error($vyField)
                                            <div>{{ $message }}</div>
                                        @enderror
                                    @endforeach
                                </div>
                            @endif
                        @elseif ($readOnly)
                            <p class="vyr-empty-line">Workspace paused — seasons can be saved and applied again once it resumes.</p>
                        @endif
                    </section>

                @if ($vyCanWrite)
                    {{-- ── SEASON · APPLY — ⚠️⚠️ THE MONEY STEP, a separate, deliberate second action. ─────────────────────── --}}
                    <div class="vy-modal-backdrop" x-cloak x-show="confirmSeason !== null" x-transition.opacity x-on:click.self="confirmSeason = null" x-on:keydown.escape.window="confirmSeason = null">
                        <div class="vy-modal vy-modal-card vy-modal--wide vyr-dlg" x-show="confirmSeason !== null" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-season-t" tabindex="-1" x-trap.noscroll="confirmSeason !== null">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico is-danger" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg></span>
                                <div class="vyr-dlg-head">
                                    <h3 class="vy-modal-title" id="vy-dlg-season-t">Apply this season?</h3>
                                    <p class="vyr-dlg-sub" x-text="confirmSeason?.name"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="confirmSeason = null" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body vyr-prose">
                                <p>
                                    <span x-text="confirmSeason?.name"></span> will overwrite
                                    <span x-text="confirmSeason?.changing"></span> of
                                    <span x-text="confirmSeason?.nights"></span> nights across
                                    <span x-text="confirmSeason?.units"></span> units, from
                                    <span x-text="confirmSeason?.from_display ?? '—'"></span> to
                                    <strong x-text="confirmSeason?.to_display"></strong> a night.
                                </p>
                                {{-- ⚠️⚠️ TRUE, AND VERIFIED BEFORE IT WAS WRITTEN: `setRate()` records an audit row whose `before` is
                                     null, so the prices it replaces are held nowhere. There is no undo to offer. --}}
                                <p>The prices it replaces are not stored anywhere. <strong>This cannot be undone.</strong></p>
                                {{-- ⚠️⚠️ WHAT IT WOULD REPLACE THAT SOMEBODY NEGOTIATED. This TELLS; it does not ask (R-RATE). --}}
                                <template x-if="confirmSeason?.agreed_nights > 0">
                                    <p class="vyr-callout">
                                        <strong><span x-text="confirmSeason?.agreed_nights"></span></strong>
                                        <span x-text="confirmSeason?.agreed_nights === 1 ? 'of those nights carries' : 'of those nights carry'"></span>
                                        a rate negotiated with
                                        <span x-text="confirmSeason?.agreed_owners === 1 ? 'an owner' : confirmSeason?.agreed_owners + ' owners'"></span>
                                        (<span x-text="confirmSeason?.agreed_from"></span> to <span x-text="confirmSeason?.agreed_to"></span>).
                                        Applying replaces those prices, and the owner is not told.
                                    </p>
                                </template>
                                <template x-if="confirmSeason?.losing_nights > 0">
                                    <p><span x-text="confirmSeason?.losing_nights"></span> of those nights are governed by <span x-text="confirmSeason?.losing_to"></span>, which wins under the rule and will be written last.</p>
                                </template>
                                <template x-if="confirmSeason?.rewritten_nights > 0">
                                    <p>Applying also re-writes <span x-text="confirmSeason?.rewritten_nights"></span> nights from overlapping seasons that are already applied.</p>
                                </template>
                                {{-- ⚠️⚠️ F-21 — THE DISCLOSURE STATES ITS OWN SCOPE, UNCONDITIONALLY (hidden at zero, "0 nights carry a
                                     negotiated rate" would read as "nothing is at risk"). --}}
                                <p class="vyr-muted">That count sees only rates agreed through a proposal, and only while the price still matches what was agreed. A rate an owner set themselves, or one that has since moved, is not counted here.</p>
                                <p class="vyr-muted">Stays already booked keep the price they were booked at — this changes what is offered from now on.</p>
                            </div>
                            <form method="POST" x-bind:action="confirmSeason?.apply_url ?? ''" class="vy-modal-foot" x-on:submit="stamp($event.target)">
                                @csrf
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="return_query" value="">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="confirmSeason = null">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Apply and overwrite those nights</button>
                            </form>
                        </div>
                    </div>

                    {{-- ── SEASON · EDIT a draft. ⚠️ It saves and returns to the preview; it does NOT apply. ──────────────────── --}}
                    <div class="vy-modal-backdrop" x-cloak x-show="editSeason !== null" x-transition.opacity x-on:click.self="editSeason = null" x-on:keydown.escape.window="editSeason = null">
                        <div class="vy-modal vy-modal-card vyr-dlg" x-show="editSeason !== null" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-season-edit" tabindex="-1" x-trap.noscroll="editSeason !== null">
                            <form method="POST" x-bind:action="editSeason?.update_url ?? ''" x-on:submit="stamp($event.target)">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="return_query" value="">
                                <div class="vy-modal-head">
                                    <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></span>
                                    <div class="vyr-dlg-head">
                                        <h3 class="vy-modal-title" id="vy-dlg-season-edit">Edit this season</h3>
                                        {{-- ⚠️⚠️ THE TWO-STEP IS PRESERVED, AND THIS SENTENCE IS THE PROMISE. --}}
                                        <p class="vyr-dlg-sub">Saving updates the season only. <strong>No prices are written</strong> until you apply it.</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" x-on:click="editSeason = null" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                                <div class="vy-modal-body">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-season-edit-name">Name</label>
                                        <input id="vy-season-edit-name" class="vy-input" type="text" name="name" x-bind:value="editSeason?.name" maxlength="80" required>
                                    </div>
                                    <div class="vyr-dlg-grid2">
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vy-season-edit-from">First night</label>
                                            <input id="vy-season-edit-from" class="vy-input" type="date" name="starts_on" x-bind:value="editSeason?.starts_on" required>
                                        </div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vy-season-edit-to">Last night (included)</label>
                                            <input id="vy-season-edit-to" class="vy-input" type="date" name="ends_on" x-bind:value="editSeason?.ends_on" required>
                                        </div>
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-season-edit-price">Nightly price</label>
                                        <input id="vy-season-edit-price" class="vy-input" type="text" inputmode="decimal" name="price" x-bind:value="editSeason?.price_input" required>
                                    </div>
                                </div>
                                <div class="vy-modal-foot">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="editSeason = null">Cancel</button>
                                    <button type="submit" class="vy-btn vy-btn--accent">Save changes</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- ── SEASON · DELETE. ⚠️⚠️ NOT AN UNDO, AND IT SAYS SO. ──────────────────────────────────────────────── --}}
                    <div class="vy-modal-backdrop" x-cloak x-show="deleteSeason !== null" x-transition.opacity x-on:click.self="deleteSeason = null" x-on:keydown.escape.window="deleteSeason = null">
                        <div class="vy-modal vy-modal-card vyr-dlg" x-show="deleteSeason !== null" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-season-delete" tabindex="-1" x-trap.noscroll="deleteSeason !== null">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico is-danger" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg></span>
                                <div class="vyr-dlg-head">
                                    <h3 class="vy-modal-title" id="vy-dlg-season-delete">Delete this season?</h3>
                                    <p class="vyr-dlg-sub" x-text="deleteSeason?.name"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="deleteSeason = null" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body vyr-prose">
                                {{-- ⚠️⚠️ A CONTROL MAY NOT IMPLY MORE THAN IT DOES — deleting an APPLIED season leaves every night it
                                     wrote exactly as it wrote them. --}}
                                <template x-if="deleteSeason?.applied">
                                    <p>This season has been applied. Deleting it removes the season only — <strong>the nightly prices it wrote stay as they are</strong>, and the prices they replaced are not stored anywhere. Set those nights yourself if they are wrong.</p>
                                </template>
                                <template x-if="! deleteSeason?.applied">
                                    <p>This season has not been applied, so <strong>no prices change</strong>.</p>
                                </template>
                            </div>
                            <form method="POST" x-bind:action="deleteSeason?.delete_url ?? ''" class="vy-modal-foot" x-on:submit="stamp($event.target)">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="return_query" value="">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="deleteSeason = null">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--danger">Delete season</button>
                            </form>
                        </div>
                    </div>
                @endif
                </div>{{-- the season scope --}}
            @endcan

            {{-- ── Rate plans — REAL base + derived plans (#100). Derived = base × adjustment (reconciles). ─────────────── --}}
            @if (count($ratePlans) > 0 || ($vyCanWrite && count($roomTypeOptions) > 0))
                <section class="vy-card vy-fade" id="vy-add-rate-plan" aria-labelledby="vyr-plans-t">
                    <div class="vyr-panel-head">
                        <div>
                            <h2 class="vy-card-title" id="vyr-plans-t">Rate plans</h2>
                            <p class="vyr-panel-sub">A rate plan is what nightly prices hang off. Each room type is priced through its main plan in the grid above; plans take the property's currency.</p>
                        </div>
                        @if ($vyCanWrite && count($roomTypeOptions) > 0)
                            <button type="button" class="vy-btn vy-btn--ghost vyr-tool" x-on:click="openPlanAdd('')">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                Add rate plan
                            </button>
                        @endif
                    </div>
                    @foreach ($ratePlans as $plan)
                        <div class="vyr-plan">
                            <div class="vyr-plan-main">
                                <div class="vyr-plan-name">{{ $plan['name'] }}@if ($plan['is_derived'])<span class="vyr-accent"> · derived</span>@endif</div>
                                {{-- ⚠️ ONE expression, not an @if around the text: Livewire's block markers would split the line. --}}
                                <div class="vyr-plan-sub">{{ ($plan['room_type'] !== '' ? $plan['room_type'].' · ' : '').$plan['sub'] }}</div>
                                {{-- What it charges in this fortnight — the grid shows only each room type's main plan. --}}
                                <div class="vyr-plan-priced">{{ $plan['priced'] }}</div>
                            </div>
                            <span class="vy-tag {{ $plan['is_active'] ? 'tone-ok' : 'tone-paused' }}">{{ $plan['is_active'] ? 'On sale' : 'Off sale' }}</span>
                            <span class="vyr-muted vyr-channel">{{ $plan['channel'] }}</span>
                            @if ($vyCanWrite)
                                <div class="vyr-plan-actions">
                                    @unless ($plan['is_derived'])
                                        <button type="button" class="vy-btn vy-btn--ghost vyr-sm" aria-label="Set prices for {{ $plan['name'] }}" x-on:click="openEditor({ rt: '{{ $plan['room_type_id'] }}', plan: '{{ $plan['id'] }}' })">Set prices</button>
                                    @endunless
                                    <button type="button" class="vy-btn vy-btn--ghost vyr-sm" aria-label="Rename {{ $plan['name'] }}" x-on:click="editPlanFor = '{{ $plan['id'] }}'">Rename</button>
                                    <button type="button" class="vy-btn vy-btn--ghost vyr-sm" aria-label="{{ $plan['is_active'] ? 'Take '.$plan['name'].' off sale' : 'Put '.$plan['name'].' back on sale' }}" x-on:click="activePlanFor = '{{ $plan['id'] }}'">{{ $plan['is_active'] ? 'Take off sale' : 'Put back on sale' }}</button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- ── Channels — HONEST (#85): Direct live; OTA sync needs setup, never a fabricated "Synced". ───────────────── --}}
            <section class="vy-card vy-fade" aria-labelledby="vyr-channels-t">
                <div class="vyr-panel-head">
                    <h2 class="vy-card-title" id="vyr-channels-t">Channels</h2>
                    <span class="vyr-muted">{{ $channels['summary'] }}</span>
                </div>
                @foreach ($channels['rows'] as $ch)
                    <div class="vyr-channel-row">
                        <span class="vyr-channel-ico" aria-hidden="true">{{ $ch['init'] }}</span>
                        <span class="vyr-channel-name">{{ $ch['name'] }}</span>
                        <span class="vy-tag {{ $ch['live'] ? 'tone-ok' : 'tone-muted' }}">{{ $ch['status'] }}</span>
                    </div>
                @endforeach
                <p class="vyr-note">Prices save to direct bookings now. Sending them to OTA channels starts once a channel connection is set up — nothing is sent to a channel until then.</p>
            </section>

            {{-- ══════════════════════════════════════ DIALOGS ══════════════════════════════════════════════════════════
                 Every one centred by `.vy-modal-backdrop` (a class survives x-show), with role, aria-modal, a resolving name,
                 tabindex, a trap bound to its OWN show expression, and Escape on the backdrop (DialogFocusContractTest). --}}
            @if ($vyCanWrite && count($rows) > 0)
                {{-- ── SET RATES & AVAILABILITY — one night, a dragged range, or anything typed. Posts `rates.update`. ──── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="edOpen" x-transition.opacity x-on:click.self="edOpen = false" x-on:keydown.escape.window="edOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide vyr-dlg" x-show="edOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-rates-ed-t" tabindex="-1" x-trap.noscroll="edOpen">
                        <form method="POST" action="{{ route('app.rates.update') }}" x-on:submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8zM7.5 7.5h.01"/></svg></span>
                                <div class="vyr-dlg-head">
                                    <h3 class="vy-modal-title" id="vy-dlg-rates-ed-t">Set rates &amp; availability</h3>
                                    <p class="vyr-dlg-sub" x-text="edSummary()"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="edOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vyr-dlg-range">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyr-ed-rt">Room type</label>
                                        <select id="vyr-ed-rt" name="room_type_id" class="vy-input" x-model="ed.rt" x-on:change="ed.plan = mainPlan($event.target.value)">
                                            <option value="all">All room types on this page</option>
                                            @foreach ($roomTypeOptions as $opt)
                                                <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{-- ⚠️ 2026-10-01 — a room type with more than one base plan: which one to price. Only the chosen room
                                         type's picker is enabled, so only it is posted; the server checks the plan belongs to it. --}}
                                    @foreach ($planChoices as $vyRtId => $vyChoices)
                                        <div class="vy-field vyr-planpick" x-show="ed.rt === '{{ $vyRtId }}'">
                                            <label class="vy-field-label" for="vyr-ed-plan-{{ $vyRtId }}">Rate plan</label>
                                            <select id="vyr-ed-plan-{{ $vyRtId }}" name="rate_plan_id" class="vy-input" x-model="ed.plan" x-bind:disabled="ed.rt !== '{{ $vyRtId }}'">
                                                @foreach ($vyChoices as $vyChoice)
                                                    <option value="{{ $vyChoice['id'] }}" @selected($loop->first)>{{ $vyChoice['label'] }}</option>
                                                @endforeach
                                            </select>
                                            <span class="vyr-fieldhint">The price goes on this plan. Stay rules and open or closed apply to the whole room type.</span>
                                        </div>
                                    @endforeach
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyr-ed-from">First night</label>
                                        <input id="vyr-ed-from" type="date" name="start_date" class="vy-input" x-model="ed.from" required>
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyr-ed-to">Last night</label>
                                        <input id="vyr-ed-to" type="date" name="end_date" class="vy-input" x-model="ed.to" required>
                                    </div>
                                </div>

                                <div class="vyr-rules">
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_rate" value="1" x-model="ed.setRate"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Nightly price</strong><span>What a guest pays for each night</span></span></label>
                                        <div class="vyr-money" x-bind:class="{ 'is-off': ! ed.setRate }">
                                            <span aria-hidden="true">{{ $currencySymbol }}</span>
                                            <input type="text" name="rate" class="vy-input" inputmode="decimal" placeholder="175.00" aria-label="Nightly price" x-model="ed.rate" x-bind:disabled="! ed.setRate">
                                        </div>
                                    </div>
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_min_stay" value="1" x-model="ed.setMin"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Minimum stay</strong><span>Blank removes it</span></span></label>
                                        <div class="vyr-nights" x-bind:class="{ 'is-off': ! ed.setMin }">
                                            <input type="number" name="min_stay" class="vy-input" min="1" max="365" placeholder="2" aria-label="Minimum stay, in nights" x-model="ed.min" x-bind:disabled="! ed.setMin">
                                            <span aria-hidden="true">nights</span>
                                        </div>
                                    </div>
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_max_stay" value="1" x-model="ed.setMax"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Maximum stay</strong><span>Blank removes it</span></span></label>
                                        <div class="vyr-nights" x-bind:class="{ 'is-off': ! ed.setMax }">
                                            <input type="number" name="max_stay" class="vy-input" min="1" max="365" placeholder="14" aria-label="Maximum stay, in nights" x-model="ed.max" x-bind:disabled="! ed.setMax">
                                            <span aria-hidden="true">nights</span>
                                        </div>
                                    </div>
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_stop_sell" value="1" x-model="ed.setStop"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Open or closed</strong><span>Closed nights can’t be booked</span></span></label>
                                        <fieldset class="vy-choices vy-choices--segmented vyr-seg" x-bind:disabled="! ed.setStop">
                                            <legend class="vy-sr-only">Open or closed</legend>
                                            <div class="vy-choice-row">
                                                <label class="vy-choice"><input type="radio" name="stop_sell" value="0" x-model="ed.stop"><span class="vy-choice-face"><span class="vy-choice-text">Open</span></span></label>
                                                <label class="vy-choice"><input type="radio" name="stop_sell" value="1" x-model="ed.stop"><span class="vy-choice-face"><span class="vy-choice-text">Closed</span></span></label>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_closed_to_arrival" value="1" x-model="ed.setCta"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Arrivals</strong><span>Whether a stay may start on these nights</span></span></label>
                                        <fieldset class="vy-choices vy-choices--segmented vyr-seg" x-bind:disabled="! ed.setCta">
                                            <legend class="vy-sr-only">Arrivals</legend>
                                            <div class="vy-choice-row">
                                                <label class="vy-choice"><input type="radio" name="closed_to_arrival" value="0" x-model="ed.cta"><span class="vy-choice-face"><span class="vy-choice-text">Allowed</span></span></label>
                                                <label class="vy-choice"><input type="radio" name="closed_to_arrival" value="1" x-model="ed.cta"><span class="vy-choice-face"><span class="vy-choice-text">No arrivals</span></span></label>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_closed_to_departure" value="1" x-model="ed.setCtd"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Departures</strong><span>Whether a stay may end on these nights</span></span></label>
                                        <fieldset class="vy-choices vy-choices--segmented vyr-seg" x-bind:disabled="! ed.setCtd">
                                            <legend class="vy-sr-only">Departures</legend>
                                            <div class="vy-choice-row">
                                                <label class="vy-choice"><input type="radio" name="closed_to_departure" value="0" x-model="ed.ctd"><span class="vy-choice-face"><span class="vy-choice-text">Allowed</span></span></label>
                                                <label class="vy-choice"><input type="radio" name="closed_to_departure" value="1" x-model="ed.ctd"><span class="vy-choice-face"><span class="vy-choice-text">No departures</span></span></label>
                                            </div>
                                        </fieldset>
                                    </div>
                                </div>

                                @if (count($derivedPlans) > 0)
                                    {{-- #100 — the REAL derived plans on screen (was a fixed "Non-refundable −10% and Weekly −15%"). --}}
                                    <label class="vy-gl-switch vyr-derived"><input type="checkbox" name="update_derived" value="1" x-model="ed.derived"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Also update derived plans</strong><span>{{ implode(' · ', $derivedPlans) }} — they follow the new price</span></span></label>
                                @endif
                                <p class="vyr-note">Bookings already taken keep the price they were sold at. This changes what is offered from now on.</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="edOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent" x-bind:disabled="! edAny()" x-text="edApplyLabel()">Apply</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── BULK EDIT — raise, lower or set, across a range. ⚠️ Its OWN dates (the whole window by default) —
                     it used to post the editor's, which started as ONE night. Posts `rates.bulk`. ──────────────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="bulkOpen" x-transition.opacity x-on:click.self="bulkOpen = false" x-on:keydown.escape.window="bulkOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide vyr-dlg" x-show="bulkOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-bulk-t" tabindex="-1" x-trap.noscroll="bulkOpen">
                        <form method="POST" action="{{ route('app.rates.bulk') }}" x-on:submit="stamp($event.target)">
                            @csrf
                            <input type="hidden" name="return" value="list">
                            <input type="hidden" name="return_query" value="">
                            <input type="hidden" name="skip_booked" value="1">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h10M4 17h6"/></svg></span>
                                <div class="vyr-dlg-head">
                                    <h3 class="vy-modal-title" id="vy-dlg-bulk-t">Bulk edit rates</h3>
                                    <p class="vyr-dlg-sub">Change many nights at once, on each room type’s main plan</p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="bulkOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vyr-dlg-range">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="bu_rt">Room types</label>
                                        <select id="bu_rt" name="room_type_id" class="vy-input" x-model="bu.rt">
                                            <option value="all">All room types on this page</option>
                                            @foreach ($roomTypeOptions as $opt)
                                                <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="bu_from">First night</label>
                                        <input id="bu_from" type="date" name="start_date" class="vy-input" x-model="bu.from" required>
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="bu_to">Last night</label>
                                        <input id="bu_to" type="date" name="end_date" class="vy-input" x-model="bu.to" required>
                                    </div>
                                </div>
                                <fieldset class="vy-choices vyr-tiles3">
                                    <legend class="vy-field-label">Change</legend>
                                    <div class="vy-choice-row">
                                        <label class="vy-choice"><input type="radio" name="mode" value="increase_pct" x-model="bu.mode"><span class="vy-choice-face"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17L17 7M9 7h8v8"/></svg><span class="vy-choice-text">Raise by %</span></span></label>
                                        <label class="vy-choice"><input type="radio" name="mode" value="decrease_pct" x-model="bu.mode"><span class="vy-choice-face"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 7l10 10M17 9v8H9"/></svg><span class="vy-choice-text">Lower by %</span></span></label>
                                        <label class="vy-choice"><input type="radio" name="mode" value="set_exact" x-model="bu.mode"><span class="vy-choice-face"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8zM7.5 7.5h.01"/></svg><span class="vy-choice-text">Set a price</span></span></label>
                                    </div>
                                </fieldset>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-lbl-rates-grid-amount" x-text="bu.mode === 'set_exact' ? 'Nightly price' : 'Percentage'">Percentage</label>
                                    <div class="vyr-money" x-bind:class="{ 'is-pct': bu.mode !== 'set_exact' }">
                                        <span aria-hidden="true" x-text="bu.mode === 'set_exact' ? @js($currencySymbol) : '%'">%</span>
                                        <input id="vy-lbl-rates-grid-amount" type="text" name="amount" class="vy-input" inputmode="decimal" x-model="bu.amount" x-bind:placeholder="bu.mode === 'set_exact' ? '175.00' : '10'" required>
                                    </div>
                                </div>
                                <fieldset class="vy-choices vy-choices--segmented vyr-seg">
                                    <legend class="vy-field-label">Nights</legend>
                                    <div class="vy-choice-row">
                                        <label class="vy-choice"><input type="radio" name="nights" value="whole" x-model="bu.nights"><span class="vy-choice-face"><span class="vy-choice-text">Every night</span></span></label>
                                        <label class="vy-choice"><input type="radio" name="nights" value="weekends" x-model="bu.nights"><span class="vy-choice-face"><span class="vy-choice-text">Fri–Sat nights</span></span></label>
                                        <label class="vy-choice"><input type="radio" name="nights" value="midweek" x-model="bu.nights"><span class="vy-choice-face"><span class="vy-choice-text">Sun–Thu nights</span></span></label>
                                    </div>
                                </fieldset>
                                @if (count($derivedPlans) > 0)
                                    <label class="vy-gl-switch vyr-derived"><input type="checkbox" name="update_derived" value="1"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Also update derived plans</strong><span>{{ implode(' · ', $derivedPlans) }} — they follow the new price</span></span></label>
                                @endif
                                <p class="vyr-summary" x-text="buSummary()"></p>
                                <p class="vyr-note">Fully booked and closed nights are left as they are, and so is any night a change would bring down to zero.</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="bulkOpen = false">Cancel</button>
                                <button type="submit" class="vy-btn vy-btn--accent">Apply bulk edit</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ── D18-5 · BULK PER-UNIT PRICING — step 1 of 2. ⚠️ Posts `preview`, which WRITES NOTHING. ─────────────── --}}
                <div class="vy-modal-backdrop" x-cloak x-show="unitOpen" x-transition.opacity x-on:click.self="unitOpen = false" x-on:keydown.escape.window="unitOpen = false">
                    <div class="vy-modal vy-modal-card vy-modal--wide vyr-dlg is-padded" x-show="unitOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-rg-unit-t" tabindex="-1" x-trap.noscroll="unitOpen">
                        {{-- ⚠️ THE ONE FORM (§N.8f) — the same component the Units list opens for a single unit. --}}
                        <x-rates.unit-price-form
                            :room-types="$roomTypeOptions"
                            :units="$unitOptions"
                            :from="$window['from']"
                            :to="$window['through']"
                            :return-to-list="true"
                            heading-id="vy-dlg-rg-unit-t"
                            subheading="A price for the units you choose — the rest of the room type is unchanged.">
                            <x-slot:actions>
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="unitOpen = false">Cancel</button>
                            </x-slot:actions>
                        </x-rates.unit-price-form>
                    </div>
                </div>

                {{-- D18-6 — the single-set dialogs the unit rows open. ⚠️ The SAME <x-rates.unit-price-form> the Units list uses. --}}
                @foreach ($rows as $vyRow)
                    @foreach ($vyRow['unit_rows'] as $vyU)
                        <div class="vy-modal-backdrop" x-cloak x-show="unitPriceFor === '{{ $vyU['id'] }}'" x-transition.opacity
                             x-on:click.self="unitPriceFor = null" x-on:keydown.escape.window="unitPriceFor = null">
                            <div class="vy-modal vy-modal-card vy-modal--wide vyr-dlg is-padded" x-show="unitPriceFor === '{{ $vyU['id'] }}'" x-transition
                                 x-data="{ unitMode: 'set', unitRt: '{{ $vyRow['id'] }}' }" role="dialog" aria-modal="true" tabindex="-1"
                                 x-trap.noscroll="unitPriceFor === '{{ $vyU['id'] }}'" aria-labelledby="vy-dlg-rg-unitprice-{{ $vyU['id'] }}">
                                <x-rates.unit-price-form
                                    heading-id="vy-dlg-rg-unitprice-{{ $vyU['id'] }}"
                                    :room-types="$roomTypeOptions"
                                    :units="$unitOptions"
                                    :preselect="$vyU['id']"
                                    :from="$window['from']"
                                    :to="$window['through']"
                                    :return-to-list="true"
                                    heading="Price this unit individually"
                                    subheading="A price for this unit only — the rest of its room type is unchanged.">
                                    <x-slot:actions>
                                        <button type="button" class="vy-btn vy-btn--ghost" x-on:click="unitPriceFor = null">Cancel</button>
                                    </x-slot:actions>
                                </x-rates.unit-price-form>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            @endif

            @if ($vyCanWrite)
                {{-- ── RATE PLANS · rename and on/off sale, one pair per plan ──────────────────────────────────────────── --}}
                @foreach ($ratePlans as $vyP)
                    <div class="vy-modal-backdrop" x-cloak x-show="editPlanFor === '{{ $vyP['id'] }}'" x-transition.opacity x-on:click.self="editPlanFor = null" x-on:keydown.escape.window="editPlanFor = null">
                        <div class="vy-modal vy-modal-card vyr-dlg" x-show="editPlanFor === '{{ $vyP['id'] }}'" x-transition role="dialog" aria-modal="true" tabindex="-1"
                             x-trap.noscroll="editPlanFor === '{{ $vyP['id'] }}'" aria-labelledby="vy-dlg-rg-plan-edit-{{ $vyP['id'] }}">
                            <form method="POST" action="{{ route('app.inventory.rate-plans.update', $vyP['id']) }}" x-on:submit="stamp($event.target)">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="return_query" value="">
                                <div class="vy-modal-head">
                                    <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></span>
                                    <div class="vyr-dlg-head">
                                        <h3 class="vy-modal-title" id="vy-dlg-rg-plan-edit-{{ $vyP['id'] }}">Rename rate plan {{ $vyP['name'] }}</h3>
                                        {{-- ⚠️ TRUE AND NARROW — `updateRatePlan()` accepts only `name` and `code`. --}}
                                        <p class="vyr-dlg-sub">Only the name and code change. No nightly price moves, and a stay already sold keeps the price and terms it was sold with.</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" x-on:click="editPlanFor = null" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                                <div class="vy-modal-body vyr-dlg-grid2">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyr-plan-name-{{ $vyP['id'] }}">Plan name</label>
                                        <input id="vyr-plan-name-{{ $vyP['id'] }}" class="vy-input" type="text" name="name" maxlength="255" value="{{ $vyP['name'] }}" required>
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyr-plan-code-{{ $vyP['id'] }}">Code <span class="vy-fl-note">(optional)</span></label>
                                        <input id="vyr-plan-code-{{ $vyP['id'] }}" class="vy-input" type="text" name="code" maxlength="60" value="{{ $vyP['code'] }}">
                                    </div>
                                </div>
                                <div class="vy-modal-foot">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="editPlanFor = null">Cancel</button>
                                    <button type="submit" class="vy-btn vy-btn--accent">Save plan name</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="vy-modal-backdrop" x-cloak x-show="activePlanFor === '{{ $vyP['id'] }}'" x-transition.opacity x-on:click.self="activePlanFor = null" x-on:keydown.escape.window="activePlanFor = null">
                        <div class="vy-modal vy-modal-card vyr-dlg" x-show="activePlanFor === '{{ $vyP['id'] }}'" x-transition role="dialog" aria-modal="true" tabindex="-1"
                             x-trap.noscroll="activePlanFor === '{{ $vyP['id'] }}'" aria-labelledby="vy-dlg-rg-plan-active-{{ $vyP['id'] }}">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico {{ $vyP['is_active'] ? 'is-danger' : '' }}" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="{{ $vyP['is_active'] ? 'M10 9v6M14 9v6' : 'M10 8.5l5 3.5-5 3.5z' }}"/></svg></span>
                                <div class="vyr-dlg-head">
                                    <h3 class="vy-modal-title" id="vy-dlg-rg-plan-active-{{ $vyP['id'] }}">{{ $vyP['is_active'] ? 'Take '.$vyP['name'].' off sale?' : 'Put '.$vyP['name'].' back on sale?' }}</h3>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="activePlanFor = null" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body vyr-prose">
                                @if ($vyP['is_active'])
                                    {{-- ⚠️ MEASURED, NOT ASSERTED — the storefront quotes only active plans; bookings snapshot their price. --}}
                                    <p>It stops being quoted to new guests straight away. Bookings already taken on it keep their price and terms, and its nightly prices are kept, so putting it back on sale restores them unchanged.</p>
                                @else
                                    <p>It can be quoted to new guests again, at the nightly prices it already has.</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('app.inventory.rate-plans.active', $vyP['id']) }}" class="vy-modal-foot" x-on:submit="stamp($event.target)">
                                @csrf
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="return_query" value="">
                                <input type="hidden" name="active" value="{{ $vyP['is_active'] ? '0' : '1' }}">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="activePlanFor = null">Cancel</button>
                                <button type="submit" class="vy-btn {{ $vyP['is_active'] ? 'vy-btn--danger' : 'vy-btn--accent' }}">{{ $vyP['is_active'] ? 'Take off sale' : 'Put back on sale' }}</button>
                            </form>
                        </div>
                    </div>
                @endforeach

                {{-- ── RATE PLANS · add. ⚠️ No price is set here — prices are rate_calendar rows, set in the grid. ─────────── --}}
                @if (count($roomTypeOptions) > 0)
                    <div class="vy-modal-backdrop" x-cloak x-show="planAddOpen" x-transition.opacity x-on:click.self="planAddOpen = false" x-on:keydown.escape.window="planAddOpen = false">
                        <div class="vy-modal vy-modal-card vyr-dlg" x-show="planAddOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-rg-plan-add" tabindex="-1" x-trap.noscroll="planAddOpen">
                            <form method="POST" action="{{ route('app.inventory.rate-plans.store') }}" x-on:submit="stamp($event.target)">
                                @csrf
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="return_query" value="">
                                <div class="vy-modal-head">
                                    <span class="vy-modal-ico" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></span>
                                    <div class="vyr-dlg-head">
                                        <h3 class="vy-modal-title" id="vy-dlg-rg-plan-add">Add a rate plan</h3>
                                        <p class="vyr-dlg-sub">It takes the property’s currency. Set its nightly prices in the grid once it exists.</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" x-on:click="planAddOpen = false" aria-label="Close"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                                <div class="vy-modal-body">
                                    <div class="vyr-dlg-grid2">
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyr-plan-add-rt">Room type</label>
                                            <select id="vyr-plan-add-rt" class="vy-input" name="room_type_id" x-model="planAddRt" required>
                                                @foreach ($roomTypeOptions as $rt)
                                                    <option value="{{ $rt['id'] }}">{{ $rt['name'] }}{{ $rt['has_plan'] ? '' : ' — no plan yet' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyr-plan-add-name">Plan name</label>
                                            <input id="vyr-plan-add-name" class="vy-input" name="name" required maxlength="255" placeholder="e.g. Flexible">
                                        </div>
                                    </div>
                                    {{-- ⚠️ A real switch — off sends nothing, which the server now reads as "not refundable" (it used to
                                         default an absent box to refundable, so this could never be switched off). --}}
                                    <label class="vy-gl-switch"><input type="checkbox" name="is_refundable" value="1" checked><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>Refundable</strong><span>Guests can cancel for free under this plan</span></span></label>
                                </div>
                                <div class="vy-modal-foot">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="planAddOpen = false">Cancel</button>
                                    <button type="submit" class="vy-btn vy-btn--accent">Add rate plan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </x-operator.shell>

    <script>
        window.vyRates = window.vyRates || function (boot) {
            const plural = (n, one, many) => n + ' ' + (n === 1 ? one : many);
            // "Wed 30 Sep" — the grid's own format. The browser's en-GB locale prints "Sept", so the words are spelt out here.
            const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const fmt = (d) => {
                if (! d) return '';
                const [y, m, day] = d.split('-').map(Number);
                const at = new Date(Date.UTC(y, m - 1, day));
                return DAYS[at.getUTCDay()] + ' ' + day + ' ' + MONTHS[m - 1];
            };
            const nights = (a, b) => {
                if (! a || ! b) return 0;
                const ms = Date.parse(b + 'T00:00:00Z') - Date.parse(a + 'T00:00:00Z');
                return ms < 0 ? 0 : Math.round(ms / 86400000) + 1;
            };
            const mainOf = (rt) => (boot.main && boot.main[rt]) || '';
            const blankEd = () => ({ rt: boot.firstRt, plan: mainOf(boot.firstRt), from: boot.from, to: boot.to, setRate: true, rate: '', setMin: false, min: '',
                setMax: false, max: '', setStop: false, stop: '0', setCta: false, cta: '0', setCtd: false, ctd: '0', derived: false });

            return {
                edOpen: false, ed: blankEd(),
                bulkOpen: false, bu: { rt: 'all', from: boot.from, to: boot.to, mode: 'increase_pct', amount: '', nights: 'whole' },
                unitOpen: false, unitPriceFor: null, unitMode: 'set', unitRt: boot.firstRt,
                editPlanFor: null, activePlanFor: null, planAddOpen: false, planAddRt: boot.firstRt,
                drag: null, suppress: false,

                // ── the grid: a click opens one night, a drag opens the range (mouse; touch keeps scrolling the grid)
                dragStart(e, rt, date) {
                    if (e.pointerType !== 'mouse' || e.button !== 0) return;
                    this.drag = { rt, from: date, to: date, moved: false };
                },
                dragOver(rt, date) {
                    if (! this.drag || this.drag.rt !== rt || date === this.drag.to) return;
                    this.drag.to = date;
                    this.drag.moved = true;
                },
                dragEnd() {
                    if (! this.drag) return;
                    const d = this.drag;
                    this.drag = null;
                    if (! d.moved) return;
                    // The click that ends a drag must not also open one night — but when the drag ends on ANOTHER night the
                    // browser sends that click to the row, not a night, so the flag is cleared once this event is done
                    // (🖥 2026-10-01: left set, it swallowed the operator's next real click).
                    this.suppress = true;
                    setTimeout(() => { this.suppress = false; }, 0);
                    const [a, b] = d.from <= d.to ? [d.from, d.to] : [d.to, d.from];
                    this.openEditor({ rt: d.rt, from: a, to: b });
                },
                inDrag(rt, date) {
                    if (! this.drag || ! this.drag.moved || this.drag.rt !== rt) return false;
                    const [a, b] = this.drag.from <= this.drag.to ? [this.drag.from, this.drag.to] : [this.drag.to, this.drag.from];
                    return date >= a && date <= b;
                },
                cellClick(rt, date, cell) {
                    if (this.suppress) { this.suppress = false; return; }
                    this.openEditor({ rt, from: date, to: date, cell });
                },

                // ── the one-range editor
                openEditor(pick) {
                    const ed = blankEd();
                    if (pick) {
                        ed.rt = pick.rt;
                        ed.plan = pick.plan || mainOf(pick.rt);
                        if (pick.from) { ed.from = pick.from; ed.to = pick.to; }
                        const c = pick.cell;
                        if (c) {
                            ed.rate = c.rate || '';
                            if (c.min !== null) { ed.setMin = true; ed.min = String(c.min); }
                            if (c.max !== null) { ed.setMax = true; ed.max = String(c.max); }
                            ed.stop = c.stop ? '1' : '0';
                            ed.cta = c.cta ? '1' : '0';
                            ed.ctd = c.ctd ? '1' : '0';
                        }
                    }
                    this.ed = ed;
                    this.edOpen = true;
                },
                mainPlan(rt) { return mainOf(rt); },
                edNights() { return nights(this.ed.from, this.ed.to); },
                edAny() { const e = this.ed; return e.setRate || e.setMin || e.setMax || e.setStop || e.setCta || e.setCtd; },
                edSummary() {
                    const plan = this.ed.rt !== 'all' && this.ed.plan && this.ed.plan !== mainOf(this.ed.rt) ? ' · ' + (boot.planNames[this.ed.plan] || '') : '';
                    const who = (this.ed.rt === 'all' ? 'All room types on this page' : (boot.names[this.ed.rt] || 'Room type')) + plan;
                    const n = this.edNights();
                    if (n === 0) return who + ' · choose the nights';
                    return who + ' · ' + plural(n, 'night', 'nights') + ' · ' + (n === 1 ? fmt(this.ed.from) : fmt(this.ed.from) + ' – ' + fmt(this.ed.to));
                },
                edApplyLabel() { const n = this.edNights(); return n > 0 ? 'Apply to ' + plural(n, 'night', 'nights') : 'Apply'; },

                // ── the many-nights dialog
                openBulk() {
                    this.bu = { rt: 'all', from: boot.from, to: boot.to, mode: 'increase_pct', amount: '', nights: 'whole' };
                    this.bulkOpen = true;
                },
                buSummary() {
                    const b = this.bu;
                    const who = b.rt === 'all' ? 'every room type on this page' : (boot.names[b.rt] || 'the room type');
                    const which = b.nights === 'weekends' ? 'Friday and Saturday nights' : (b.nights === 'midweek' ? 'Sunday to Thursday nights' : 'every night');
                    const span = b.from && b.to ? 'from ' + fmt(b.from) + ' to ' + fmt(b.to) : 'in the dates above';
                    const amount = (b.amount || '').trim();
                    const change = b.mode === 'set_exact'
                        ? 'Sets ' + which + ' to ' + (amount ? boot.currency + amount : 'the price you enter')
                        : (b.mode === 'increase_pct' ? 'Raises ' : 'Lowers ') + which + ' by ' + (amount ? amount + '%' : 'the percentage you enter');
                    return change + ' for ' + who + ', ' + span + '.';
                },

                // ── plans
                openPlanAdd(rt) { this.planAddRt = rt || boot.firstRt; this.planAddOpen = true; },

                // ── the room-type filter keeps the window
                filterTo(value) {
                    const url = new URL(window.location.href);
                    if (value) url.searchParams.set('rt', value); else url.searchParams.delete('rt');
                    window.location.assign(url.toString());
                },

                // every form started here comes back to the page as it is — the server rebuilds it from an allow-list
                stamp(form) {
                    const field = form.querySelector('[name="return_query"]');
                    if (field) field.value = window.location.search.replace(/^\?/, '');
                },
            };
        };
    </script>
</div>
