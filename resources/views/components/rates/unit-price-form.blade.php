@props([
    // [['id' => …, 'name' => …], …] — the room types this form may target.
    'roomTypes' => [],
    // [roomTypeId => [['id' => …, 'name' => …], …]] — the units under each.
    'units' => [],
    // A single unit id to pre-select, or null for the "choose from the list" shape.
    'preselect' => null,
    // Y-m-d bounds the date fields open on — the host's window.
    'from' => null,
    'to' => null,
    // ⚠️ CALLER-SUPPLIED, and it must be, because this component renders MANY TIMES PER PAGE — once per unit in the units
    //    list and in the rates grid. A fixed id here would be duplicated N times and every `aria-labelledby` pointing at it
    //    would resolve to whichever came first. The dialog that owns each instance passes its own.
    'headingId' => null,
    'heading' => __('components/rates/unit-price-form.heading'),
    'subheading' => __('components/rates/unit-price-form.subheading'),
    // The Rates page brings the operator back to its window and filter after the preview (RatesListParams); the Units
    // page keeps its own destination, so it leaves this off.
    'returnToList' => false,
])

{{-- D18-5 — THE ONE per-unit pricing FORM, so its two homes cannot drift apart (§N.8f).
     ⚠️ IT WRITES NOTHING. It posts to `app.rates.units.preview`, which computes the change set and hands back a digest;
     the write happens only from the preview panel, whose Apply re-verifies that digest inside the transaction. That
     two-step contract lives in UnitRateOverrideService — the ONE writer — and neither this form nor its host may write
     `unit_rate_overrides` directly (D4-1).
     ⚠️ SINGLE-SET IS N=1, NOT A SECOND FEATURE. `$preselect` simply submits one `unit_ids[]` value instead of several;
     the route, the service call and the money path are identical.
     ⚠️ 2026-09-30 — the dates are always shown. The rates grid used to hide them and post its editor's dates instead,
     which started as ONE night while the dialog was headed with the whole fortnight. --}}
<form method="POST" action="{{ route('app.rates.units.preview') }}" data-vy-unit-price-form class="vyr-upf"
      @if ($returnToList) x-on:submit="stamp($event.target)" @endif>
    @csrf
    @if ($returnToList)
        <input type="hidden" name="return" value="list">
        <input type="hidden" name="return_query" value="">
    @endif
    <h3 @if ($headingId) id="{{ $headingId }}" @endif class="vyr-upf-title">{{ $heading }}</h3>
    <p class="vyr-upf-sub">{{ $subheading }}</p>

    <div class="vyr-upf-dates">
        <div class="vy-field">
            <label class="vy-field-label" for="{{ $headingId ?? 'upf' }}-from">{{ __('components/rates/unit-price-form.first_night') }}</label>
            <input id="{{ $headingId ?? 'upf' }}-from" type="date" name="start_date" class="vy-input" value="{{ $from }}" required>
        </div>
        <div class="vy-field">
            <label class="vy-field-label" for="{{ $headingId ?? 'upf' }}-to">{{ __('components/rates/unit-price-form.last_night') }}</label>
            <input id="{{ $headingId ?? 'upf' }}-to" type="date" name="end_date" class="vy-input" value="{{ $to }}" required>
        </div>
    </div>

    @if ($preselect !== null)
        {{-- ⚠️ THE UNIT IS NAMED, NOT JUST CARRIED. A money form whose rows do not say which unit they price is the
             blank-label defect the bulk pass found by rendering — a hidden id alone would repeat it. --}}
        @php($vyRt = collect($roomTypes)->first(fn (array $rt): bool => collect($units[$rt['id']] ?? [])->contains(fn (array $u): bool => $u['id'] === $preselect)))
        @php($vyUnit = collect($units[$vyRt['id'] ?? ''] ?? [])->first(fn (array $u): bool => $u['id'] === $preselect))
        <input type="hidden" name="room_type_id" value="{{ $vyRt['id'] ?? '' }}">
        <input type="hidden" name="unit_ids[]" value="{{ $preselect }}">
        <div class="vy-field">
            <span class="vy-field-label">{{ __('components/rates/unit-price-form.unit') }}</span>
            <div class="vyr-upf-unit"><strong>{{ $vyUnit['name'] ?? $preselect }}</strong> <span>· {{ $vyRt['name'] ?? '' }}</span></div>
        </div>
    @else
        <div class="vy-field">
            <label class="vy-field-label" for="{{ $headingId ?? 'upf' }}-rt">{{ __('components/rates/unit-price-form.room_type') }}</label>
            <select id="{{ $headingId ?? 'upf' }}-rt" name="room_type_id" class="vy-input" x-model="unitRt">
                @foreach ($roomTypes as $opt)
                    <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                @endforeach
            </select>
        </div>
        <fieldset class="vy-field vyr-upf-units-field">
            <legend class="vy-field-label">{{ __('components/rates/unit-price-form.units') }}</legend>
            <p class="vyr-upf-hint">{{ __('components/rates/unit-price-form.units_hint') }}</p>
            @foreach ($units as $rtId => $rtUnits)
                {{-- a class carries the layout — x-show erases an inline display (XShowInlineDisplayContractTest) --}}
                <div class="vyr-upf-units" x-show="unitRt === '{{ $rtId }}'">
                    @foreach ($rtUnits as $u)
                        <label class="vyr-upf-chip"><input type="checkbox" name="unit_ids[]" value="{{ $u['id'] }}"> <span>{{ $u['name'] }}</span></label>
                    @endforeach
                </div>
            @endforeach
        </fieldset>
    @endif

    <fieldset class="vy-choices vy-choices--segmented vyr-upf-mode">
        <legend class="vy-field-label">{{ __('components/rates/unit-price-form.what_to_do') }}</legend>
        <div class="vy-choice-row">
            <label class="vy-choice"><input type="radio" name="mode" value="set" x-model="unitMode"><span class="vy-choice-face"><span class="vy-choice-text">{{ __('components/rates/unit-price-form.mode_set') }}</span></span></label>
            <label class="vy-choice"><input type="radio" name="mode" value="clear" x-model="unitMode"><span class="vy-choice-face"><span class="vy-choice-text">{{ __('components/rates/unit-price-form.mode_clear') }}</span></span></label>
        </div>
    </fieldset>
    <div class="vy-field" x-show="unitMode === 'set'">
        <label class="vy-field-label" for="{{ $headingId ?? 'upf' }}-amount">{{ __('components/rates/unit-price-form.nightly_price') }}</label>
        <input id="{{ $headingId ?? 'upf' }}-amount" type="text" name="amount" class="vy-input" inputmode="decimal" placeholder="{{ __('components/rates/unit-price-form.amount_placeholder') }}">
    </div>
    {{-- ⚠️ CLEAR REMOVES THE OVERRIDE — it does not set €0. The unit goes back to following its room type's rate, and the
         preview shows that real figure before anything is written. --}}
    <p class="vyr-upf-hint" x-show="unitMode === 'clear'">{{ __('components/rates/unit-price-form.clear_hint') }}</p>

    <p class="vyr-upf-hint">{{ __('components/rates/unit-price-form.preview_hint') }}</p>
    <div class="vyr-upf-foot">
        {{ $actions ?? '' }}
        <button type="submit" class="vy-btn vy-btn--accent">{{ __('components/rates/unit-price-form.preview_button') }}</button>
    </div>
</form>
