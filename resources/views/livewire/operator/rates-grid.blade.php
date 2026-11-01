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
    <x-operator.shell active="rates" :title="__('livewire/operator/rates-grid.page_title')" :subtitle="$subtitle" :viewer="$viewer">
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
                // I18N-01 — the words the script shows. Day and month names from the app's language (2023-01-01 is a Sunday);
                // the two counted texts are built here for every count a range can have (the server refuses more than 370
                // nights), and the `many` form takes over past that.
                'i18n' => [
                    'days' => collect(range(0, 6))->map(fn (int $i): string => \Carbon\CarbonImmutable::create(2023, 1, 1)->addDays($i)->translatedFormat(\App\Support\I18n\LocalDate::pattern('D')))->all(),
                    'months' => collect(range(1, 12))->map(fn (int $m): string => \Carbon\CarbonImmutable::create(2023, $m, 1)->translatedFormat(\App\Support\I18n\LocalDate::pattern('M')))->all(),
                    'allPage' => __('livewire/operator/rates-grid.all_room_types_page'),
                    'roomType' => __('livewire/operator/rates-grid.room_type'),
                    'whoPlan' => __('livewire/operator/rates-grid.ed_who_plan'),
                    'noNights' => __('livewire/operator/rates-grid.ed_summary_no_nights'),
                    'summary' => collect(range(1, 370))->mapWithKeys(fn (int $n): array => [$n => trans_choice('livewire/operator/rates-grid.ed_summary', $n, ['count' => $n])])->all(),
                    'summaryMany' => trans_choice('livewire/operator/rates-grid.ed_summary', 370, ['count' => ':count']),
                    'apply' => __('common.apply'),
                    'applyTo' => collect(range(1, 370))->mapWithKeys(fn (int $n): array => [$n => trans_choice('livewire/operator/rates-grid.apply_to_nights', $n, ['count' => $n])])->all(),
                    'applyToMany' => trans_choice('livewire/operator/rates-grid.apply_to_nights', 370, ['count' => ':count']),
                    'buWhoAll' => __('livewire/operator/rates-grid.bu_who_all'),
                    'buWhoFallback' => __('livewire/operator/rates-grid.bu_who_fallback'),
                    'buWeekends' => __('livewire/operator/rates-grid.bu_which_weekends'),
                    'buMidweek' => __('livewire/operator/rates-grid.bu_which_midweek'),
                    'buEvery' => __('livewire/operator/rates-grid.bu_which_every'),
                    'buSpan' => __('livewire/operator/rates-grid.bu_span'),
                    'buSpanBlank' => __('livewire/operator/rates-grid.bu_span_blank'),
                    'buSet' => __('livewire/operator/rates-grid.bu_set'),
                    'buSetBlank' => __('livewire/operator/rates-grid.bu_set_blank'),
                    'buRaise' => __('livewire/operator/rates-grid.bu_raise'),
                    'buRaiseBlank' => __('livewire/operator/rates-grid.bu_raise_blank'),
                    'buLower' => __('livewire/operator/rates-grid.bu_lower'),
                    'buLowerBlank' => __('livewire/operator/rates-grid.bu_lower_blank'),
                ],
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
                    $vyPreviewUnits = trans_choice('livewire/operator/rates-grid.units_count', count($vyPreview['units']), ['count' => count($vyPreview['units'])]);
                    $vyPreviewNights = trans_choice('livewire/operator/rates-grid.nights_count', $vyPreview['nights'], ['count' => $vyPreview['nights']]);
                @endphp
                <div class="vy-card vy-fade vyr-unitpreview">
                    <h2 class="vy-card-title">{{ __('livewire/operator/rates-grid.preview_heading') }}</h2>
                    <p class="vyr-panel-sub">
                        {!! __('livewire/operator/rates-grid.preview_summary', [
                            'room_type' => e($vyPreview['room_type_name']),
                            'units' => e($vyPreviewUnits),
                            'nights' => e($vyPreviewNights),
                            'from' => e(\Carbon\CarbonImmutable::parse($vyPreview['start'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('D j M'))),
                            'to' => e(\Carbon\CarbonImmutable::parse($vyPreview['end_inclusive'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('D j M Y'))),
                        ]) !!}
                    </p>
                    <div class="vyr-tablewrap">
                        <table class="vy-table">
                            <thead><tr><th>{{ __('livewire/operator/rates-grid.col_unit') }}</th><th>{{ __('livewire/operator/rates-grid.col_now') }}</th><th>{{ __('livewire/operator/rates-grid.col_after') }}</th><th>{{ __('livewire/operator/rates-grid.col_nights') }}</th></tr></thead>
                            <tbody>
                                @foreach ($vyPreview['units'] as $vyRow)
                                    <tr>
                                        <td>{{ $vyPreview['names'][$vyRow['unit_id']] ?? $vyRow['unit_id'] }}</td>
                                        <td>{{ $vyRow['from_minor'] === null ? '—' : \App\Support\Money\MoneyFormatter::display($vyRow['from_minor'], $vyPreview['currency']) }}</td>
                                        <td>
                                            <strong>{{ $vyRow['to_minor'] === null ? '—' : \App\Support\Money\MoneyFormatter::display($vyRow['to_minor'], $vyPreview['currency']) }}</strong>
                                            @if ($vyRow['state'] === 'cleared')
                                                <span class="vyr-muted"> {{ __('livewire/operator/rates-grid.room_type_rate') }}</span>
                                            @elseif ($vyRow['state'] === 'pinned')
                                                <span class="vyr-muted"> {{ __('livewire/operator/rates-grid.pinned_to_unit') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ __('livewire/operator/rates-grid.nights_of', ['changing' => $vyRow['nights_changing'], 'nights' => $vyRow['nights']]) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($vyPreview['skipped_units'] > 0)
                        <p class="vyr-panel-sub">{{ trans_choice('livewire/operator/rates-grid.skipped_units', $vyPreview['skipped_units'], ['count' => $vyPreview['skipped_units']]) }}</p>
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
                        <a class="vy-btn vy-btn--ghost" href="{{ \App\Support\Rates\RatesListParams::returnUrl(request()->getQueryString()) }}">{{ __('common.cancel') }}</a>
                        <button type="submit" class="vy-btn vy-btn--accent">{{ trans_choice('livewire/operator/rates-grid.apply_to_units', count($vyPreview['units']), ['count' => count($vyPreview['units'])]) }}</button>
                    </form>
                </div>
            @endif
        @endcan

        <div class="vy-rates" x-data="vyRates({{ \Illuminate\Support\Js::from($vyBoot) }})" x-on:pointerup.window="dragEnd()">

            {{-- ── Toolbar: the fortnight, the filter, and what a manager can do ─────────────────────────────────────── --}}
            <div class="vyr-bar vy-fade">
                <div class="vyr-winnav">
                    <a class="vyr-navbtn" href="{{ $window['prev'] }}" aria-label="{{ __('livewire/operator/rates-grid.previous_weeks') }}" title="{{ __('livewire/operator/rates-grid.previous_weeks') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                    </a>
                    <span class="vyr-winlabel">{{ $window['label'] }}</span>
                    <a class="vyr-navbtn" href="{{ $window['next'] }}" aria-label="{{ __('livewire/operator/rates-grid.next_weeks') }}" title="{{ __('livewire/operator/rates-grid.next_weeks') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                    </a>
                    <a class="vy-btn vy-btn--ghost vyr-tool" href="{{ $window['today'] }}">{{ __('common.today') }}</a>
                </div>
                @if (count($roomTypeOptions) > 1)
                    {{-- #104 GATE 2d — narrows the grid to one room type; keeps the window. --}}
                    <label class="vy-sr-only" for="vyr-filter">{{ __('livewire/operator/rates-grid.show_room_type') }}</label>
                    <select id="vyr-filter" class="vy-input vyr-filter" x-on:change="filterTo($event.target.value)">
                        <option value="" @selected($rtFilter === '')>{{ __('livewire/operator/rates-grid.all_room_types') }}</option>
                        @foreach ($roomTypeOptions as $opt)
                            <option value="{{ $opt['id'] }}" @selected($rtFilter === $opt['id'])>{{ $opt['name'] }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($window['jump'])
                    <a class="vyr-jump" href="{{ $window['jump']['href'] }}">{{ __('livewire/operator/rates-grid.jump_to_priced', ['date' => \Carbon\CarbonImmutable::parse($window['jump']['date'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M Y'))]) }}</a>
                @endif
                @if ($vyCanWrite && count($rows) > 0)
                    <div class="vyr-bar-tools">
                        <button type="button" class="vy-btn vy-btn--ghost vyr-tool" x-on:click="openBulk()">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 12h10M4 17h6"/></svg>
                            {{ __('livewire/operator/rates-grid.bulk_edit') }}
                        </button>
                        <button type="button" class="vy-btn vy-btn--ghost vyr-tool" x-on:click="unitOpen = true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                            {{ __('livewire/operator/rates-grid.price_units_individually') }}
                        </button>
                        <button type="button" class="vy-btn vy-btn--accent vyr-tool" x-on:click="openEditor(null)">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8zM7.5 7.5h.01"/></svg>
                            {{ __('livewire/operator/rates-grid.set_rates') }}
                        </button>
                    </div>
                @endif
            </div>

            {{-- ── The figures — DERIVED, real aggregates. Avg PER CURRENCY (never summed across). No "+8% vs Aug": no
                 historical baseline is stored, so a trend would be fabricated. ─────────────────────────────────────── --}}
            <div class="vy-dash-kpis vy-fade">
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/rates-grid.tile_avg_label') }}</div>
                    @forelse ($tiles['avg'] as $line)
                        <div class="vy-dk-val @if (! $loop->first) vyr-dk-val-2 @endif">{{ $line }}</div>
                    @empty
                        <div class="vy-dk-val">—</div>
                    @endforelse
                    <div class="vy-dk-sub">{{ $tiles['multi_currency'] ? __('livewire/operator/rates-grid.tile_avg_sub_multi') : __('livewire/operator/rates-grid.tile_avg_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/rates-grid.tile_booked_label') }}</div>
                    <div class="vy-dk-val">{{ $tiles['booked_pct'] !== null ? $tiles['booked_pct'].'%' : '—' }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/operator/rates-grid.tile_booked_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/rates-grid.tile_open_label') }}</div>
                    <div class="vy-dk-val">{{ $tiles['open_nights'] }}</div>
                    <div class="vy-dk-sub">{{ __('livewire/operator/rates-grid.tile_open_sub') }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/rates-grid.tile_ahead_label') }}</div>
                    <div class="vy-dk-val">{{ $ahead['booked_pct'] !== null ? $ahead['booked_pct'].'%' : '—' }}</div>
                    <div class="vy-dk-sub">{{ trans_choice('livewire/operator/rates-grid.tile_ahead_sub', $ahead['open_nights'], ['count' => $ahead['open_nights']]) }}</div>
                </div>
            </div>

            {{-- ── The grid ──────────────────────────────────────────────────────────────────────────────────────── --}}
            <div class="vy-card vy-card--flush vy-fade">
                @if (count($rows) === 0)
                    <div class="vyr-empty">
                        <strong>{{ __('livewire/operator/rates-grid.empty_heading') }}</strong>
                        <span>{{ __('livewire/operator/rates-grid.empty_body') }}</span>
                    </div>
                @else
                    <div class="vyr-gridhead">
                        {{-- Each swatch is the cell style it names, and is paired with its WORD (never colour alone, WCAG 1.4.1). --}}
                        <div class="vyr-legend">
                            <span><i class="vyr-sw is-open" aria-hidden="true"></i>{{ __('livewire/operator/rates-grid.state_open') }}</span>
                            <span><i class="vyr-sw is-last" aria-hidden="true"></i>{{ __('livewire/operator/rates-grid.legend_last_one') }}</span>
                            <span><i class="vyr-sw is-full" aria-hidden="true"></i>{{ __('livewire/operator/rates-grid.legend_full') }}</span>
                            <span><i class="vyr-sw is-closed" aria-hidden="true"></i>{{ __('livewire/operator/rates-grid.state_closed') }}</span>
                            <span><i class="vyr-sw is-weekend" aria-hidden="true"></i>{{ __('livewire/operator/rates-grid.fri_sat_nights') }}</span>
                        </div>
                        @if ($vyCanWrite)
                            <span class="vyr-hint">{{ __('livewire/operator/rates-grid.grid_hint') }}</span>
                        @endif
                    </div>
                    <div class="vyr-scroll">
                        <table class="vyr-grid">
                            <colgroup><col class="vyr-col-rt">@foreach ($dates as $d)<col>@endforeach</colgroup>
                            <thead>
                                <tr>
                                    <th class="vyr-corner" scope="col"><span class="vy-sr-only">{{ __('livewire/operator/rates-grid.room_type') }}</span></th>
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
                                                    @if ($vyCanWrite)
                                                        {{-- the button is built from static markup and escaped parts, then placed in the sentence --}}
                                                        @php
                                                            $vyAddPlanHtml = '<button type="button" class="vyr-linkbtn" x-on:click="openPlanAdd(\''.e($row['id']).'\')">'.e(__('livewire/operator/rates-grid.add_one')).'</button>';
                                                        @endphp
                                                        {!! __('livewire/operator/rates-grid.no_plan_add', ['add_button' => $vyAddPlanHtml]) !!}
                                                    @else
                                                        {{ __('livewire/operator/rates-grid.no_plan_ask') }}
                                                    @endif
                                                </span>
                                            @endunless
                                        </th>
                                        @foreach ($row['cells'] as $cell)
                                            @php
                                                $vyState = $cell['stop_sell'] ? 'is-closed' : ($cell['sold_out'] ? 'is-full' : ($cell['last_one'] ? 'is-last' : ''));
                                                $vyDay = \Carbon\CarbonImmutable::parse($cell['date'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('D j M'));
                                                $vyCellLabel = $cell['available'] !== null
                                                    ? __('livewire/operator/rates-grid.cell_label_avail', [
                                                        'room_type' => $row['name'],
                                                        'day' => $vyDay,
                                                        'rate' => $cell['rate'] ?? __('livewire/operator/rates-grid.no_price'),
                                                        'availability' => $cell['sold_out'] ? __('livewire/operator/rates-grid.avail_full') : trans_choice('livewire/operator/rates-grid.avail_left', $cell['available'], ['count' => $cell['available']]),
                                                    ])
                                                    : __('livewire/operator/rates-grid.cell_label', [
                                                        'room_type' => $row['name'],
                                                        'day' => $vyDay,
                                                        'rate' => $cell['rate'] ?? __('livewire/operator/rates-grid.no_price'),
                                                    ]);
                                            @endphp
                                            <td class="vyr-cell {{ $dates[$loop->index]['is_weekend'] ? 'is-weekend' : '' }}">
                                                @if ($vyCanWrite && $row['has_plan'])
                                                    <button type="button" class="vyr-cellbtn {{ $vyState }}"
                                                        aria-label="{{ $vyCellLabel }}"
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
                                                    <button type="button" class="vyr-priceind" aria-label="{{ __('livewire/operator/rates-grid.price_unit_individually_label', ['unit' => $vyUnitRow['label']]) }}"
                                                            x-on:click="unitPriceFor = '{{ $vyUnitRow['id'] }}'">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8zM7.5 7.5h.01"/></svg>
                                                        {{ __('livewire/operator/rates-grid.price_individually') }}
                                                    </button>
                                                @endif
                                            </th>
                                            @foreach ($vyUnitRow['cells'] as $vyCell)
                                                <td class="vyr-cell {{ $dates[$loop->index]['is_weekend'] ? 'is-weekend' : '' }}">
                                                    {{-- ⚠️ STATIC BY CONSTRUCTION — no button, no editor, nothing to type into. --}}
                                                    <div class="vyr-cell-static is-unit">
                                                        <span class="vyr-urate {{ $vyCell['rate_fit'] }}">{{ $vyCell['rate'] ?? '—' }}</span>
                                                        @if ($vyCell['overridden'])
                                                            <i class="vyr-own" title="{{ __('livewire/operator/rates-grid.priced_individually') }}"><span class="vy-sr-only">{{ __('livewire/operator/rates-grid.priced_individually') }}</span></i>
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
                                <h2 class="vy-card-title" id="vyr-seasons-t">{{ __('livewire/operator/rates-grid.seasons_heading') }}</h2>
                                <p class="vyr-panel-sub">{{ __('livewire/operator/rates-grid.seasons_sub') }}
                                    {{-- ⚠️ THE RULE, from `SeasonPrecedence::SENTENCE` — the copy and the code are the same string. --}}
                                    {{ $seasonRule }}</p>
                            </div>
                        </div>

                        @if (count($seasons) > 0)
                            <div class="vyr-tablewrap">
                                <table class="vy-table vyr-seasons">
                                    <thead>
                                        <tr><th>{{ __('livewire/operator/rates-grid.col_season') }}</th><th>{{ __('livewire/operator/rates-grid.col_season_nights') }}</th><th>{{ __('livewire/operator/rates-grid.col_now') }}</th><th>{{ __('livewire/operator/rates-grid.col_after') }}</th><th>{{ __('livewire/operator/rates-grid.col_would_change') }}</th><th>{{ __('common.status') }}</th><th><span class="vy-sr-only">{{ __('common.actions') }}</span></th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($seasons as $vySeason)
                                            <tr>
                                                <td>
                                                    <div class="vyr-cell-title">{{ $vySeason['name'] }}</div>
                                                    <div class="vyr-muted">{{ $vySeason['plan_name'] }}{{ $vySeason['room_type'] !== '' ? ' · '.$vySeason['room_type'] : '' }}</div>
                                                </td>
                                                <td class="vyr-nowrap">{{ \Carbon\CarbonImmutable::parse($vySeason['starts_on'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M')) }} – {{ \Carbon\CarbonImmutable::parse($vySeason['ends_on'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j M Y')) }}</td>
                                                <td class="vyr-nowrap">{{ $vySeason['from_display'] ?? '—' }}</td>
                                                <td class="vyr-nowrap"><strong>{{ $vySeason['to_display'] }}</strong></td>
                                                <td>
                                                    {{ __('livewire/operator/rates-grid.season_change', [
                                                        'changing' => $vySeason['changing'],
                                                        'nights' => trans_choice('livewire/operator/rates-grid.nights_count', $vySeason['nights'], ['count' => $vySeason['nights']]),
                                                        'units' => trans_choice('livewire/operator/rates-grid.units_count', $vySeason['units'], ['count' => $vySeason['units']]),
                                                    ]) }}
                                                    @if ($vySeason['weekend_nights'] > 0)
                                                        <div class="vyr-muted">{{ trans_choice('livewire/operator/rates-grid.season_weekend', $vySeason['weekend_nights'], ['count' => $vySeason['weekend_nights']]) }}</div>
                                                    @endif
                                                    @if ($vySeason['losing_nights'] > 0)
                                                        <div class="vyr-accent">{{ trans_choice('livewire/operator/rates-grid.season_losing', $vySeason['losing_nights'], ['count' => $vySeason['losing_nights'], 'season' => $vySeason['losing_to']]) }}</div>
                                                    @endif
                                                    @if ($vySeason['agreed_nights'] > 0)
                                                        <div class="vyr-accent">{{ trans_choice('livewire/operator/rates-grid.season_agreed', $vySeason['agreed_nights'], ['count' => $vySeason['agreed_nights']]) }}</div>
                                                    @endif
                                                    @if ($vySeason['rewritten_nights'] > 0)
                                                        <div class="vyr-muted">{{ trans_choice('livewire/operator/rates-grid.season_rewritten', $vySeason['rewritten_nights'], ['count' => $vySeason['rewritten_nights']]) }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($vySeason['applied'])
                                                        <span class="vy-tag tone-ok">{{ __('livewire/operator/rates-grid.season_applied', ['date' => $vySeason['applied_on']]) }}</span>
                                                    @else
                                                        {{-- ⚠️ An unapplied season has written NOTHING — say so. --}}
                                                        <span class="vy-tag tone-muted">{{ __('livewire/operator/rates-grid.season_saved') }}</span>
                                                    @endif
                                                </td>
                                                <td class="vyr-row-actions">
                                                    @if ($vyCanWrite)
                                                        {{-- ⚠️⚠️ EDIT IS OFFERED ONLY WHILE THE SEASON IS A DRAFT — applying overwrote the nightly rates and
                                                             nothing recorded what they were, so an "edit" could not put them back. --}}
                                                        @if (! $vySeason['applied'])
                                                            <button type="button" class="vy-btn vy-btn--ghost vyr-sm" x-on:click="editSeason = {{ \Illuminate\Support\Js::from($vySeason) }}">{{ __('common.edit') }}</button>
                                                        @endif
                                                        <button type="button" class="vy-btn vy-btn--ghost vyr-sm" x-on:click="confirmSeason = {{ \Illuminate\Support\Js::from($vySeason) }}">{{ $vySeason['applied'] ? __('livewire/operator/rates-grid.apply_again') : __('common.apply') }}</button>
                                                        <button type="button" class="vy-btn vy-btn--ghost vyr-sm is-danger" x-on:click="deleteSeason = {{ \Illuminate\Support\Js::from($vySeason) }}">{{ __('common.delete') }}</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="vyr-empty-line">{{ $vyCanWrite && $vyActivePlans > 0 ? __('livewire/operator/rates-grid.seasons_empty_cta') : __('livewire/operator/rates-grid.seasons_empty') }}</p>
                        @endif

                        {{-- ⚠️ A season is set on an ACTIVE plan, so with none there is nothing to offer — the form would only be refused
                             (🖥 production, 2026-10-01: an empty plan picker on a property with no room types). --}}
                        @if ($vyCanWrite && $vyActivePlans === 0)
                            <p class="vyr-empty-line">{{ __('livewire/operator/rates-grid.seasons_no_active_plan') }}</p>
                        @elseif ($vyCanWrite)
                            {{-- ── Save a season. This writes NO prices. ─────────────────────────────────────────────────── --}}
                            <form method="POST" action="{{ route('app.seasons.store') }}" class="vyr-season-form" x-on:submit="stamp($event.target)">
                                @csrf
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="return_query" value="">
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-season-plan">{{ __('livewire/operator/rates-grid.rate_plan') }}</label>
                                    <select id="vy-season-plan" name="rate_plan_id" class="vy-input" required>
                                        {{-- ⚠️ ACTIVE PLANS ONLY — a season must never be defined against an off-sale plan. --}}
                                        @foreach ($ratePlans as $vyPlan)
                                            @continue (! $vyPlan['is_active'])
                                            <option value="{{ $vyPlan['id'] }}" @selected(old('rate_plan_id') === $vyPlan['id'])>{{ $vyPlan['name'] }}{{ $vyPlan['room_type'] !== '' ? ' — '.$vyPlan['room_type'] : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-season-name">{{ __('livewire/operator/rates-grid.season_name') }}</label>
                                    <input id="vy-season-name" name="name" type="text" maxlength="80" class="vy-input" value="{{ old('name') }}" placeholder="{{ __('livewire/operator/rates-grid.season_name_placeholder') }}" required>
                                </div>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-season-from">{{ __('livewire/operator/rates-grid.first_night') }}</label>
                                    <input id="vy-season-from" name="starts_on" type="date" class="vy-input" value="{{ old('starts_on') }}" required>
                                </div>
                                <div class="vy-field">
                                    {{-- ⚠️ INCLUSIVE, and the label says so — the service converts to the exclusive bound once. --}}
                                    <label class="vy-field-label" for="vy-season-to">{{ __('livewire/operator/rates-grid.last_night_included') }}</label>
                                    <input id="vy-season-to" name="ends_on" type="date" class="vy-input" value="{{ old('ends_on') }}" required>
                                </div>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-season-price">{{ __('livewire/operator/rates-grid.nightly_price') }}</label>
                                    <input id="vy-season-price" name="price" type="text" inputmode="decimal" class="vy-input" value="{{ old('price') }}" placeholder="{{ __('livewire/operator/rates-grid.price_placeholder') }}" required>
                                </div>
                                <button type="submit" class="vy-btn vy-btn--accent vyr-season-save">{{ __('livewire/operator/rates-grid.save_season') }}</button>
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
                            <p class="vyr-empty-line">{{ __('livewire/operator/rates-grid.workspace_paused') }}</p>
                        @endif
                    </section>

                @if ($vyCanWrite)
                    {{-- ── SEASON · APPLY — ⚠️⚠️ THE MONEY STEP, a separate, deliberate second action. ─────────────────────── --}}
                    <div class="vy-modal-backdrop" x-cloak x-show="confirmSeason !== null" x-transition.opacity x-on:click.self="confirmSeason = null" x-on:keydown.escape.window="confirmSeason = null">
                        <div class="vy-modal vy-modal-card vy-modal--wide vyr-dlg" x-show="confirmSeason !== null" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-dlg-season-t" tabindex="-1" x-trap.noscroll="confirmSeason !== null">
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico is-danger" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg></span>
                                <div class="vyr-dlg-head">
                                    <h3 class="vy-modal-title" id="vy-dlg-season-t">{{ __('livewire/operator/rates-grid.apply_season_title') }}</h3>
                                    <p class="vyr-dlg-sub" x-text="confirmSeason?.name"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="confirmSeason = null" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body vyr-prose">
                                @include('livewire.operator.partials.season-apply-disclosure')
                            </div>
                            <form method="POST" x-bind:action="confirmSeason?.apply_url ?? ''" class="vy-modal-foot" x-on:submit="stamp($event.target)">
                                @csrf
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="return_query" value="">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="confirmSeason = null">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/rates-grid.apply_and_overwrite') }}</button>
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
                                        <h3 class="vy-modal-title" id="vy-dlg-season-edit">{{ __('livewire/operator/rates-grid.edit_season_title') }}</h3>
                                        {{-- ⚠️⚠️ THE TWO-STEP IS PRESERVED, AND THIS SENTENCE IS THE PROMISE. --}}
                                        <p class="vyr-dlg-sub">{!! __('livewire/operator/rates-grid.edit_season_sub') !!}</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" x-on:click="editSeason = null" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                                <div class="vy-modal-body">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-season-edit-name">{{ __('common.name') }}</label>
                                        <input id="vy-season-edit-name" class="vy-input" type="text" name="name" x-bind:value="editSeason?.name" maxlength="80" required>
                                    </div>
                                    <div class="vyr-dlg-grid2">
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vy-season-edit-from">{{ __('livewire/operator/rates-grid.first_night') }}</label>
                                            <input id="vy-season-edit-from" class="vy-input" type="date" name="starts_on" x-bind:value="editSeason?.starts_on" required>
                                        </div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vy-season-edit-to">{{ __('livewire/operator/rates-grid.last_night_included') }}</label>
                                            <input id="vy-season-edit-to" class="vy-input" type="date" name="ends_on" x-bind:value="editSeason?.ends_on" required>
                                        </div>
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vy-season-edit-price">{{ __('livewire/operator/rates-grid.nightly_price') }}</label>
                                        <input id="vy-season-edit-price" class="vy-input" type="text" inputmode="decimal" name="price" x-bind:value="editSeason?.price_input" required>
                                    </div>
                                </div>
                                <div class="vy-modal-foot">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="editSeason = null">{{ __('common.cancel') }}</button>
                                    <button type="submit" class="vy-btn vy-btn--accent">{{ __('common.save_changes') }}</button>
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
                                    <h3 class="vy-modal-title" id="vy-dlg-season-delete">{{ __('livewire/operator/rates-grid.delete_season_title') }}</h3>
                                    <p class="vyr-dlg-sub" x-text="deleteSeason?.name"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="deleteSeason = null" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body vyr-prose">
                                {{-- ⚠️⚠️ A CONTROL MAY NOT IMPLY MORE THAN IT DOES — deleting an APPLIED season leaves every night it
                                     wrote exactly as it wrote them. --}}
                                <template x-if="deleteSeason?.applied">
                                    <p>{!! __('livewire/operator/rates-grid.delete_applied_body') !!}</p>
                                </template>
                                <template x-if="! deleteSeason?.applied">
                                    <p>{!! __('livewire/operator/rates-grid.delete_draft_body') !!}</p>
                                </template>
                            </div>
                            <form method="POST" x-bind:action="deleteSeason?.delete_url ?? ''" class="vy-modal-foot" x-on:submit="stamp($event.target)">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="return_query" value="">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="deleteSeason = null">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--danger">{{ __('livewire/operator/rates-grid.delete_season') }}</button>
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
                            <h2 class="vy-card-title" id="vyr-plans-t">{{ __('livewire/operator/rates-grid.plans_heading') }}</h2>
                            <p class="vyr-panel-sub">{{ __('livewire/operator/rates-grid.plans_sub') }}</p>
                        </div>
                        @if ($vyCanWrite && count($roomTypeOptions) > 0)
                            <button type="button" class="vy-btn vy-btn--ghost vyr-tool" x-on:click="openPlanAdd('')">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                {{ __('livewire/operator/rates-grid.add_rate_plan') }}
                            </button>
                        @endif
                    </div>
                    @foreach ($ratePlans as $plan)
                        <div class="vyr-plan">
                            <div class="vyr-plan-main">
                                <div class="vyr-plan-name">{{ $plan['name'] }}@if ($plan['is_derived'])<span class="vyr-accent"> {{ __('livewire/operator/rates-grid.derived_tag') }}</span>@endif</div>
                                {{-- ⚠️ ONE expression, not an @if around the text: Livewire's block markers would split the line. --}}
                                <div class="vyr-plan-sub">{{ ($plan['room_type'] !== '' ? $plan['room_type'].' · ' : '').$plan['sub'] }}</div>
                                {{-- What it charges in this fortnight — the grid shows only each room type's main plan. --}}
                                <div class="vyr-plan-priced">{{ $plan['priced'] }}</div>
                            </div>
                            <span class="vy-tag {{ $plan['is_active'] ? 'tone-ok' : 'tone-paused' }}">{{ $plan['is_active'] ? __('livewire/operator/rates-grid.on_sale') : __('livewire/operator/rates-grid.off_sale') }}</span>
                            <span class="vyr-muted vyr-channel">{{ $plan['channel'] }}</span>
                            @if ($vyCanWrite)
                                <div class="vyr-plan-actions">
                                    @unless ($plan['is_derived'])
                                        <button type="button" class="vy-btn vy-btn--ghost vyr-sm" aria-label="{{ __('livewire/operator/rates-grid.set_prices_for', ['plan' => $plan['name']]) }}" x-on:click="openEditor({ rt: '{{ $plan['room_type_id'] }}', plan: '{{ $plan['id'] }}' })">{{ __('livewire/operator/rates-grid.set_prices') }}</button>
                                    @endunless
                                    <button type="button" class="vy-btn vy-btn--ghost vyr-sm" aria-label="{{ __('livewire/operator/rates-grid.rename_plan_label', ['plan' => $plan['name']]) }}" x-on:click="editPlanFor = '{{ $plan['id'] }}'">{{ __('livewire/operator/rates-grid.rename') }}</button>
                                    <button type="button" class="vy-btn vy-btn--ghost vyr-sm" aria-label="{{ $plan['is_active'] ? __('livewire/operator/rates-grid.take_off_sale_label', ['plan' => $plan['name']]) : __('livewire/operator/rates-grid.put_back_on_sale_label', ['plan' => $plan['name']]) }}" x-on:click="activePlanFor = '{{ $plan['id'] }}'">{{ $plan['is_active'] ? __('livewire/operator/rates-grid.take_off_sale') : __('livewire/operator/rates-grid.put_back_on_sale') }}</button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- ── Channels — HONEST (#85): Direct live; OTA sync needs setup, never a fabricated "Synced". ───────────────── --}}
            <section class="vy-card vy-fade" aria-labelledby="vyr-channels-t">
                <div class="vyr-panel-head">
                    <h2 class="vy-card-title" id="vyr-channels-t">{{ __('livewire/operator/rates-grid.channels_heading') }}</h2>
                    <span class="vyr-muted">{{ $channels['summary'] }}</span>
                </div>
                @foreach ($channels['rows'] as $ch)
                    <div class="vyr-channel-row">
                        <span class="vyr-channel-ico" aria-hidden="true">{{ $ch['init'] }}</span>
                        <span class="vyr-channel-name">{{ $ch['name'] }}</span>
                        <span class="vy-tag {{ $ch['live'] ? 'tone-ok' : 'tone-muted' }}">{{ $ch['status'] }}</span>
                    </div>
                @endforeach
                <p class="vyr-note">{{ __('livewire/operator/rates-grid.channels_note') }}</p>
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
                                    <h3 class="vy-modal-title" id="vy-dlg-rates-ed-t">{{ __('livewire/operator/rates-grid.editor_title') }}</h3>
                                    <p class="vyr-dlg-sub" x-text="edSummary()"></p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="edOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vyr-dlg-range">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyr-ed-rt">{{ __('livewire/operator/rates-grid.room_type') }}</label>
                                        <select id="vyr-ed-rt" name="room_type_id" class="vy-input" x-model="ed.rt" x-on:change="ed.plan = mainPlan($event.target.value)">
                                            <option value="all">{{ __('livewire/operator/rates-grid.all_room_types_page') }}</option>
                                            @foreach ($roomTypeOptions as $opt)
                                                <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{-- ⚠️ 2026-10-01 — a room type with more than one base plan: which one to price. Only the chosen room
                                         type's picker is enabled, so only it is posted; the server checks the plan belongs to it. --}}
                                    @foreach ($planChoices as $vyRtId => $vyChoices)
                                        <div class="vy-field vyr-planpick" x-show="ed.rt === '{{ $vyRtId }}'">
                                            <label class="vy-field-label" for="vyr-ed-plan-{{ $vyRtId }}">{{ __('livewire/operator/rates-grid.rate_plan') }}</label>
                                            <select id="vyr-ed-plan-{{ $vyRtId }}" name="rate_plan_id" class="vy-input" x-model="ed.plan" x-bind:disabled="ed.rt !== '{{ $vyRtId }}'">
                                                @foreach ($vyChoices as $vyChoice)
                                                    <option value="{{ $vyChoice['id'] }}" @selected($loop->first)>{{ $vyChoice['label'] }}</option>
                                                @endforeach
                                            </select>
                                            <span class="vyr-fieldhint">{{ __('livewire/operator/rates-grid.plan_pick_hint') }}</span>
                                        </div>
                                    @endforeach
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyr-ed-from">{{ __('livewire/operator/rates-grid.first_night') }}</label>
                                        <input id="vyr-ed-from" type="date" name="start_date" class="vy-input" x-model="ed.from" required>
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyr-ed-to">{{ __('livewire/operator/rates-grid.last_night') }}</label>
                                        <input id="vyr-ed-to" type="date" name="end_date" class="vy-input" x-model="ed.to" required>
                                    </div>
                                </div>

                                <div class="vyr-rules">
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_rate" value="1" x-model="ed.setRate"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/rates-grid.nightly_price') }}</strong><span>{{ __('livewire/operator/rates-grid.nightly_price_help') }}</span></span></label>
                                        <div class="vyr-money" x-bind:class="{ 'is-off': ! ed.setRate }">
                                            <span aria-hidden="true">{{ $currencySymbol }}</span>
                                            <input type="text" name="rate" class="vy-input" inputmode="decimal" placeholder="175.00" aria-label="{{ __('livewire/operator/rates-grid.nightly_price') }}" x-model="ed.rate" x-bind:disabled="! ed.setRate">
                                        </div>
                                    </div>
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_min_stay" value="1" x-model="ed.setMin"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/rates-grid.min_stay') }}</strong><span>{{ __('livewire/operator/rates-grid.blank_removes') }}</span></span></label>
                                        <div class="vyr-nights" x-bind:class="{ 'is-off': ! ed.setMin }">
                                            <input type="number" name="min_stay" class="vy-input" min="1" max="365" placeholder="2" aria-label="{{ __('livewire/operator/rates-grid.min_stay_aria') }}" x-model="ed.min" x-bind:disabled="! ed.setMin">
                                            <span aria-hidden="true">{{ __('livewire/operator/rates-grid.nights_unit') }}</span>
                                        </div>
                                    </div>
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_max_stay" value="1" x-model="ed.setMax"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/rates-grid.max_stay') }}</strong><span>{{ __('livewire/operator/rates-grid.blank_removes') }}</span></span></label>
                                        <div class="vyr-nights" x-bind:class="{ 'is-off': ! ed.setMax }">
                                            <input type="number" name="max_stay" class="vy-input" min="1" max="365" placeholder="14" aria-label="{{ __('livewire/operator/rates-grid.max_stay_aria') }}" x-model="ed.max" x-bind:disabled="! ed.setMax">
                                            <span aria-hidden="true">{{ __('livewire/operator/rates-grid.nights_unit') }}</span>
                                        </div>
                                    </div>
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_stop_sell" value="1" x-model="ed.setStop"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/rates-grid.open_or_closed') }}</strong><span>{{ __('livewire/operator/rates-grid.closed_help') }}</span></span></label>
                                        <fieldset class="vy-choices vy-choices--segmented vyr-seg" x-bind:disabled="! ed.setStop">
                                            <legend class="vy-sr-only">{{ __('livewire/operator/rates-grid.open_or_closed') }}</legend>
                                            <div class="vy-choice-row">
                                                <label class="vy-choice"><input type="radio" name="stop_sell" value="0" x-model="ed.stop"><span class="vy-choice-face"><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.state_open') }}</span></span></label>
                                                <label class="vy-choice"><input type="radio" name="stop_sell" value="1" x-model="ed.stop"><span class="vy-choice-face"><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.state_closed') }}</span></span></label>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_closed_to_arrival" value="1" x-model="ed.setCta"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/rates-grid.arrivals') }}</strong><span>{{ __('livewire/operator/rates-grid.arrivals_help') }}</span></span></label>
                                        <fieldset class="vy-choices vy-choices--segmented vyr-seg" x-bind:disabled="! ed.setCta">
                                            <legend class="vy-sr-only">{{ __('livewire/operator/rates-grid.arrivals') }}</legend>
                                            <div class="vy-choice-row">
                                                <label class="vy-choice"><input type="radio" name="closed_to_arrival" value="0" x-model="ed.cta"><span class="vy-choice-face"><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.allowed') }}</span></span></label>
                                                <label class="vy-choice"><input type="radio" name="closed_to_arrival" value="1" x-model="ed.cta"><span class="vy-choice-face"><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.no_arrivals') }}</span></span></label>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div class="vyr-rule">
                                        <label class="vy-gl-switch"><input type="checkbox" name="set_closed_to_departure" value="1" x-model="ed.setCtd"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/rates-grid.departures') }}</strong><span>{{ __('livewire/operator/rates-grid.departures_help') }}</span></span></label>
                                        <fieldset class="vy-choices vy-choices--segmented vyr-seg" x-bind:disabled="! ed.setCtd">
                                            <legend class="vy-sr-only">{{ __('livewire/operator/rates-grid.departures') }}</legend>
                                            <div class="vy-choice-row">
                                                <label class="vy-choice"><input type="radio" name="closed_to_departure" value="0" x-model="ed.ctd"><span class="vy-choice-face"><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.allowed') }}</span></span></label>
                                                <label class="vy-choice"><input type="radio" name="closed_to_departure" value="1" x-model="ed.ctd"><span class="vy-choice-face"><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.no_departures') }}</span></span></label>
                                            </div>
                                        </fieldset>
                                    </div>
                                </div>

                                @if (count($derivedPlans) > 0)
                                    {{-- #100 — the REAL derived plans on screen (was a fixed "Non-refundable −10% and Weekly −15%"). --}}
                                    <label class="vy-gl-switch vyr-derived"><input type="checkbox" name="update_derived" value="1" x-model="ed.derived"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/rates-grid.update_derived') }}</strong><span>{{ __('livewire/operator/rates-grid.update_derived_help', ['plans' => implode(' · ', $derivedPlans)]) }}</span></span></label>
                                @endif
                                <p class="vyr-note">{{ __('livewire/operator/rates-grid.editor_note') }}</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="edOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent" x-bind:disabled="! edAny()" x-text="edApplyLabel()">{{ __('common.apply') }}</button>
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
                                    <h3 class="vy-modal-title" id="vy-dlg-bulk-t">{{ __('livewire/operator/rates-grid.bulk_title') }}</h3>
                                    <p class="vyr-dlg-sub">{{ __('livewire/operator/rates-grid.bulk_sub') }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="bulkOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body">
                                <div class="vyr-dlg-range">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="bu_rt">{{ __('livewire/operator/rates-grid.room_types') }}</label>
                                        <select id="bu_rt" name="room_type_id" class="vy-input" x-model="bu.rt">
                                            <option value="all">{{ __('livewire/operator/rates-grid.all_room_types_page') }}</option>
                                            @foreach ($roomTypeOptions as $opt)
                                                <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="bu_from">{{ __('livewire/operator/rates-grid.first_night') }}</label>
                                        <input id="bu_from" type="date" name="start_date" class="vy-input" x-model="bu.from" required>
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="bu_to">{{ __('livewire/operator/rates-grid.last_night') }}</label>
                                        <input id="bu_to" type="date" name="end_date" class="vy-input" x-model="bu.to" required>
                                    </div>
                                </div>
                                <fieldset class="vy-choices vyr-tiles3">
                                    <legend class="vy-field-label">{{ __('livewire/operator/rates-grid.bulk_change_legend') }}</legend>
                                    <div class="vy-choice-row">
                                        <label class="vy-choice"><input type="radio" name="mode" value="increase_pct" x-model="bu.mode"><span class="vy-choice-face"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17L17 7M9 7h8v8"/></svg><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.raise_by_pct') }}</span></span></label>
                                        <label class="vy-choice"><input type="radio" name="mode" value="decrease_pct" x-model="bu.mode"><span class="vy-choice-face"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 7l10 10M17 9v8H9"/></svg><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.lower_by_pct') }}</span></span></label>
                                        <label class="vy-choice"><input type="radio" name="mode" value="set_exact" x-model="bu.mode"><span class="vy-choice-face"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8zM7.5 7.5h.01"/></svg><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.set_a_price') }}</span></span></label>
                                    </div>
                                </fieldset>
                                <div class="vy-field">
                                    <label class="vy-field-label" for="vy-lbl-rates-grid-amount" x-text="bu.mode === 'set_exact' ? @js(__('livewire/operator/rates-grid.nightly_price')) : @js(__('livewire/operator/rates-grid.percentage'))">{{ __('livewire/operator/rates-grid.percentage') }}</label>
                                    <div class="vyr-money" x-bind:class="{ 'is-pct': bu.mode !== 'set_exact' }">
                                        <span aria-hidden="true" x-text="bu.mode === 'set_exact' ? @js($currencySymbol) : '%'">%</span>
                                        <input id="vy-lbl-rates-grid-amount" type="text" name="amount" class="vy-input" inputmode="decimal" x-model="bu.amount" x-bind:placeholder="bu.mode === 'set_exact' ? '175.00' : '10'" required>
                                    </div>
                                </div>
                                <fieldset class="vy-choices vy-choices--segmented vyr-seg">
                                    <legend class="vy-field-label">{{ __('livewire/operator/rates-grid.bulk_nights_legend') }}</legend>
                                    <div class="vy-choice-row">
                                        <label class="vy-choice"><input type="radio" name="nights" value="whole" x-model="bu.nights"><span class="vy-choice-face"><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.every_night') }}</span></span></label>
                                        <label class="vy-choice"><input type="radio" name="nights" value="weekends" x-model="bu.nights"><span class="vy-choice-face"><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.fri_sat_nights') }}</span></span></label>
                                        <label class="vy-choice"><input type="radio" name="nights" value="midweek" x-model="bu.nights"><span class="vy-choice-face"><span class="vy-choice-text">{{ __('livewire/operator/rates-grid.sun_thu_nights') }}</span></span></label>
                                    </div>
                                </fieldset>
                                @if (count($derivedPlans) > 0)
                                    <label class="vy-gl-switch vyr-derived"><input type="checkbox" name="update_derived" value="1"><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/rates-grid.update_derived') }}</strong><span>{{ __('livewire/operator/rates-grid.update_derived_help', ['plans' => implode(' · ', $derivedPlans)]) }}</span></span></label>
                                @endif
                                <p class="vyr-summary" x-text="buSummary()"></p>
                                <p class="vyr-note">{{ __('livewire/operator/rates-grid.bulk_note') }}</p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="bulkOpen = false">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/rates-grid.apply_bulk_edit') }}</button>
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
                            :subheading="__('livewire/operator/rates-grid.unit_form_sub')">
                            <x-slot:actions>
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="unitOpen = false">{{ __('common.cancel') }}</button>
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
                                    :heading="__('livewire/operator/rates-grid.unit_price_heading')"
                                    :subheading="__('livewire/operator/rates-grid.unit_price_sub')">
                                    <x-slot:actions>
                                        <button type="button" class="vy-btn vy-btn--ghost" x-on:click="unitPriceFor = null">{{ __('common.cancel') }}</button>
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
                                        <h3 class="vy-modal-title" id="vy-dlg-rg-plan-edit-{{ $vyP['id'] }}">{{ __('livewire/operator/rates-grid.rename_title', ['plan' => $vyP['name']]) }}</h3>
                                        {{-- ⚠️ TRUE AND NARROW — `updateRatePlan()` accepts only `name` and `code`. --}}
                                        <p class="vyr-dlg-sub">{{ __('livewire/operator/rates-grid.rename_sub') }}</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" x-on:click="editPlanFor = null" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                                <div class="vy-modal-body vyr-dlg-grid2">
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyr-plan-name-{{ $vyP['id'] }}">{{ __('livewire/operator/rates-grid.plan_name') }}</label>
                                        <input id="vyr-plan-name-{{ $vyP['id'] }}" class="vy-input" type="text" name="name" maxlength="255" value="{{ $vyP['name'] }}" required>
                                    </div>
                                    <div class="vy-field">
                                        <label class="vy-field-label" for="vyr-plan-code-{{ $vyP['id'] }}">{{ __('livewire/operator/rates-grid.code') }} <span class="vy-fl-note">{{ __('livewire/operator/rates-grid.optional_note') }}</span></label>
                                        <input id="vyr-plan-code-{{ $vyP['id'] }}" class="vy-input" type="text" name="code" maxlength="60" value="{{ $vyP['code'] }}">
                                    </div>
                                </div>
                                <div class="vy-modal-foot">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="editPlanFor = null">{{ __('common.cancel') }}</button>
                                    <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/rates-grid.save_plan_name') }}</button>
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
                                    <h3 class="vy-modal-title" id="vy-dlg-rg-plan-active-{{ $vyP['id'] }}">{{ $vyP['is_active'] ? __('livewire/operator/rates-grid.take_off_sale_title', ['plan' => $vyP['name']]) : __('livewire/operator/rates-grid.put_back_on_sale_title', ['plan' => $vyP['name']]) }}</h3>
                                </div>
                                <button type="button" class="vy-modal-x" x-on:click="activePlanFor = null" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                            </div>
                            <div class="vy-modal-body vyr-prose">
                                @if ($vyP['is_active'])
                                    {{-- ⚠️ MEASURED, NOT ASSERTED — the storefront quotes only active plans; bookings snapshot their price. --}}
                                    <p>{{ __('livewire/operator/rates-grid.take_off_sale_body') }}</p>
                                @else
                                    <p>{{ __('livewire/operator/rates-grid.put_back_on_sale_body') }}</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('app.inventory.rate-plans.active', $vyP['id']) }}" class="vy-modal-foot" x-on:submit="stamp($event.target)">
                                @csrf
                                <input type="hidden" name="return" value="list">
                                <input type="hidden" name="return_query" value="">
                                <input type="hidden" name="active" value="{{ $vyP['is_active'] ? '0' : '1' }}">
                                <button type="button" class="vy-btn vy-btn--ghost" x-on:click="activePlanFor = null">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-btn {{ $vyP['is_active'] ? 'vy-btn--danger' : 'vy-btn--accent' }}">{{ $vyP['is_active'] ? __('livewire/operator/rates-grid.take_off_sale') : __('livewire/operator/rates-grid.put_back_on_sale') }}</button>
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
                                        <h3 class="vy-modal-title" id="vy-dlg-rg-plan-add">{{ __('livewire/operator/rates-grid.add_plan_title') }}</h3>
                                        <p class="vyr-dlg-sub">{{ __('livewire/operator/rates-grid.add_plan_sub') }}</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" x-on:click="planAddOpen = false" aria-label="{{ __('common.close') }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                </div>
                                <div class="vy-modal-body">
                                    <div class="vyr-dlg-grid2">
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyr-plan-add-rt">{{ __('livewire/operator/rates-grid.room_type') }}</label>
                                            <select id="vyr-plan-add-rt" class="vy-input" name="room_type_id" x-model="planAddRt" required>
                                                @foreach ($roomTypeOptions as $rt)
                                                    <option value="{{ $rt['id'] }}">{{ $rt['has_plan'] ? $rt['name'] : __('livewire/operator/rates-grid.option_no_plan', ['name' => $rt['name']]) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="vy-field">
                                            <label class="vy-field-label" for="vyr-plan-add-name">{{ __('livewire/operator/rates-grid.plan_name') }}</label>
                                            <input id="vyr-plan-add-name" class="vy-input" name="name" required maxlength="255" placeholder="{{ __('livewire/operator/rates-grid.plan_name_placeholder') }}">
                                        </div>
                                    </div>
                                    {{-- ⚠️ A real switch — off sends nothing, which the server now reads as "not refundable" (it used to
                                         default an absent box to refundable, so this could never be switched off). --}}
                                    <label class="vy-gl-switch"><input type="checkbox" name="is_refundable" value="1" checked><span class="vy-gl-track" aria-hidden="true"></span><span class="vy-gl-switch-text"><strong>{{ __('livewire/operator/rates-grid.refundable') }}</strong><span>{{ __('livewire/operator/rates-grid.refundable_help') }}</span></span></label>
                                </div>
                                <div class="vy-modal-foot">
                                    <button type="button" class="vy-btn vy-btn--ghost" x-on:click="planAddOpen = false">{{ __('common.cancel') }}</button>
                                    <button type="submit" class="vy-btn vy-btn--accent">{{ __('livewire/operator/rates-grid.add_rate_plan') }}</button>
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
            // I18N-01 — every word the script shows arrives in `boot.i18n` (the page's language). `fill` puts the values in a
            // text's :placeholders (split/join, so a `$` in a name is never read as a pattern); `byCount` picks the text the
            // server built for that count.
            const T = boot.i18n;
            const fill = (s, vals) => Object.keys(vals).reduce((t, k) => t.split(':' + k).join(String(vals[k])), s);
            const byCount = (map, many, n) => (map[n] !== undefined ? map[n] : fill(many, { count: n }));
            // "Wed 30 Sep" — the grid's own format. The browser's en-GB locale prints "Sept", so the words come from the server.
            const DAYS = T.days;
            const MONTHS = T.months;
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
                    const base = this.ed.rt === 'all' ? T.allPage : (boot.names[this.ed.rt] || T.roomType);
                    const who = this.ed.rt !== 'all' && this.ed.plan && this.ed.plan !== mainOf(this.ed.rt)
                        ? fill(T.whoPlan, { who: base, plan: boot.planNames[this.ed.plan] || '' })
                        : base;
                    const n = this.edNights();
                    if (n === 0) return fill(T.noNights, { who });
                    return fill(byCount(T.summary, T.summaryMany, n), { who, dates: n === 1 ? fmt(this.ed.from) : fmt(this.ed.from) + ' – ' + fmt(this.ed.to) });
                },
                edApplyLabel() { const n = this.edNights(); return n > 0 ? byCount(T.applyTo, T.applyToMany, n) : T.apply; },

                // ── the many-nights dialog
                openBulk() {
                    this.bu = { rt: 'all', from: boot.from, to: boot.to, mode: 'increase_pct', amount: '', nights: 'whole' };
                    this.bulkOpen = true;
                },
                buSummary() {
                    const b = this.bu;
                    const who = b.rt === 'all' ? T.buWhoAll : (boot.names[b.rt] || T.buWhoFallback);
                    const which = b.nights === 'weekends' ? T.buWeekends : (b.nights === 'midweek' ? T.buMidweek : T.buEvery);
                    const span = b.from && b.to ? fill(T.buSpan, { from: fmt(b.from), to: fmt(b.to) }) : T.buSpanBlank;
                    const amount = (b.amount || '').trim();
                    // the whole sentence is one text per mode, with or without an amount typed
                    const text = b.mode === 'set_exact'
                        ? (amount ? T.buSet : T.buSetBlank)
                        : (b.mode === 'increase_pct' ? (amount ? T.buRaise : T.buRaiseBlank) : (amount ? T.buLower : T.buLowerBlank));
                    return fill(text, { which, price: boot.currency + amount, amount, who, span });
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
