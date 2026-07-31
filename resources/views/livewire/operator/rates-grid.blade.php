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

        <div x-data="ratesEditor()">

            {{-- ── Header: legend + Bulk edit ───────────────────────────────────────────────────────── --}}
            @if (count($rows) > 0)
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
                    <div style="display: flex; gap: 16px; font-size: 12px; color: var(--vy-muted);">
                        <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 3px; background: rgba(120,70,40,.10);"></span> Open</span>
                        <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 3px; background: rgba(194,65,12,.20);"></span> Booked</span>
                        <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 3px; background: rgba(178,106,8,.14);"></span> Weekend</span>
                    </div>
                    @can('rates.manage')
                        @unless ($readOnly)
                            <button type="button" class="vy-btn vy-btn--ghost" style="padding: 8px 14px; font-size: 12.5px;" x-on:click="bulkOpen = true">Bulk edit</button>
                        @endunless
                    @endcan
                </div>
            @endif

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

        </div>

        {{-- ── Rate plans — REAL base + derived plans (#100). Derived = base × adjustment (reconciles). ──── --}}
        @if (count($ratePlans) > 0)
            <div class="vy-card vy-fade" style="margin-top: 18px;">
                <h2 class="vy-card-title" style="margin-bottom: 12px;">Rate plans</h2>
                @foreach ($ratePlans as $plan)
                    <div style="display: flex; align-items: center; gap: 12px; padding: 9px 0; border-top: 1px solid var(--vy-hairline);">
                        <div style="flex: 1;">
                            <div style="font-size: 13.5px; color: var(--vy-ink); font-weight: 600;">{{ $plan['name'] }} @if ($plan['is_derived'])<span style="color: var(--vy-accent); font-weight: 500;"> · derived</span>@endif</div>
                            <div style="font-size: 12px; color: var(--vy-muted);">{{ $plan['sub'] }}</div>
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
