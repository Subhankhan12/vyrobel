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

        {{-- ── Toolbar: the two-week window, pageable. Real dates only; no fabricated "sync" ribbon. ──── --}}
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
            @if ($window['jump'])
                <a class="vyr-jump" href="{{ $window['jump']['href'] }}">Jump to rates from {{ \Carbon\CarbonImmutable::parse($window['jump']['date'])->format('j M Y') }} →</a>
            @endif
        </div>

        {{-- ── Stat tiles — DERIVED, real aggregates over the window. Avg PER CURRENCY (never summed). No
             "+8% vs Aug" trend: no historical baseline is stored, so a trend would be fabricated. ──────── --}}
        <div class="vy-dash-kpis vy-fade">
            <div class="vy-dash-kpi is-feature">
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

        <div x-data="ratesEditor()">

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
                                                <div class="vyr-rt-sub" style="color: var(--vy-accent);">No rate plan — add one to price it</div>
                                            @endunless
                                        </td>
                                        @foreach ($row['cells'] as $cell)
                                            <td class="vyr-cell @if ($dates[$loop->index]['is_weekend']) is-weekend @endif">
                                                @if ($canManage && $row['has_plan'])
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
                @if (count($rows) > 0)
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
                        </form>

                        {{-- Honest deferrals — real capabilities gated on account/config work, never a fake button. --}}
                        <div class="vyr-soon-note">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" style="flex: none; margin-top: 1px; color: var(--vy-faint);"><path d="M12 8v4l3 2M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z"/></svg>
                            <span>Pushing rates to channels and propagating to derived plans are coming soon — the channel connection and derived-plan setup are not configured yet, so no changes are sent out from here.</span>
                        </div>
                    </div>
                @endif
            @endcan

        </div>

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
