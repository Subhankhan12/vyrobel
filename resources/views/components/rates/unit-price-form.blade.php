@props([
    // [['id' => …, 'name' => …], …] — the room types this form may target.
    'roomTypes' => [],
    // [roomTypeId => [['id' => …, 'name' => …], …]] — the units under each.
    'units' => [],
    // A single unit id to pre-select, or null for the "choose from the list" shape.
    'preselect' => null,
    // Y-m-d bounds. The rates grid binds these to its live window via Alpine (`bindDates`).
    'from' => null,
    'to' => null,
    // When true the date inputs are Alpine-bound to the host's `from`/`to` instead of typed.
    'bindDates' => false,
    // ⚠️ CALLER-SUPPLIED, and it must be, because this component renders MANY TIMES PER PAGE — four
    //    times in the units list (once per unit, inside a loop) and five in the rates grid. A fixed id
    //    here would be duplicated N times and every `aria-labelledby` pointing at it would resolve to
    //    whichever came first. The dialog that owns each instance passes its own.
    'headingId' => null,
    'heading' => 'Price units individually',
    'subheading' => 'a price for one unit, not the whole room type',
])

{{-- D18-5 — THE ONE per-unit pricing FORM, so its two homes cannot drift apart (§N.8f).
     ⚠️ IT WRITES NOTHING. It posts to `app.rates.units.preview`, which computes the change set and hands
     back a digest; the write happens only from the preview panel, whose Apply re-verifies that digest inside
     the transaction. That two-step contract lives in UnitRateOverrideService — the ONE writer — and neither
     this form nor its host may write `unit_rate_overrides` directly (D4-1).
     ⚠️ SINGLE-SET IS N=1, NOT A SECOND FEATURE. `$preselect` simply submits one `unit_ids[]` value instead of
     several; the route, the service call and the money path are identical. --}}
<form method="POST" action="{{ route('app.rates.units.preview') }}" data-vy-unit-price-form>
    @csrf
    <h3 @if ($headingId) id="{{ $headingId }}" @endif style="font-size: 16px; font-weight: 700; color: var(--vy-ink); margin: 0 0 2px;">{{ $heading }}</h3>
    <div style="font-size: 12.5px; color: var(--vy-muted); margin-bottom: 14px;">{{ $subheading }}</div>

    @if ($bindDates)
        <input type="hidden" name="start_date" x-model="from">
        <input type="hidden" name="end_date" x-model="to">
    @else
        <div style="display: flex; gap: 10px; margin: 0 0 12px;">
            <label class="vy-field" style="flex: 1; margin: 0;">
                <span class="vy-field-label">From</span>
                <input type="date" name="start_date" class="vy-input" value="{{ $from }}" required>
            </label>
            <label class="vy-field" style="flex: 1; margin: 0;">
                <span class="vy-field-label">To</span>
                <input type="date" name="end_date" class="vy-input" value="{{ $to }}" required>
            </label>
        </div>
    @endif

    @if ($preselect !== null)
        {{-- ⚠️ THE UNIT IS NAMED, NOT JUST CARRIED. A money form whose rows do not say which unit they price
             is the blank-label defect the bulk pass found by rendering — a hidden id alone would repeat it. --}}
        @php($vyRt = collect($roomTypes)->first(fn (array $rt): bool => collect($units[$rt['id']] ?? [])->contains(fn (array $u): bool => $u['id'] === $preselect)))
        @php($vyUnit = collect($units[$vyRt['id'] ?? ''] ?? [])->first(fn (array $u): bool => $u['id'] === $preselect))
        <input type="hidden" name="room_type_id" value="{{ $vyRt['id'] ?? '' }}">
        <input type="hidden" name="unit_ids[]" value="{{ $preselect }}">
        <div class="vy-field" style="margin: 0 0 12px;">
            <span class="vy-field-label">Unit</span>
            <div style="font-size: 13.5px; color: var(--vy-ink);"><strong>{{ $vyUnit['name'] ?? $preselect }}</strong>
                <span style="color: var(--vy-muted);">· {{ $vyRt['name'] ?? '' }}</span></div>
        </div>
    @else
        <div class="vy-field" style="margin: 0 0 12px;">
            <label class="vy-field-label" for="ub_rt">Room type</label>
            <select id="ub_rt" name="room_type_id" class="vy-input" x-model="unitRt">
                @foreach ($roomTypes as $opt)
                    <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="vy-field" style="margin: 0 0 12px;">
            <label class="vy-field-label">Units</label>
            <div style="font-size: 11.5px; color: var(--vy-faint); margin-bottom: 6px;">Leave all unticked to apply to every unit of that room type.</div>
            @foreach ($units as $rtId => $rtUnits)
                <div x-show="unitRt === '{{ $rtId }}'" style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach ($rtUnits as $u)
                        <label class="vyr-check" style="margin: 0;"><input type="checkbox" name="unit_ids[]" value="{{ $u['id'] }}"> {{ $u['name'] }}</label>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    <div class="vy-field" style="margin: 0 0 12px;">
        <label class="vy-field-label">What to do</label>
        <div style="display: flex; gap: 8px; margin-bottom: 8px;">
            <label class="vyr-check" style="margin: 0;"><input type="radio" name="mode" value="set" x-model="unitMode"> Set a per-unit rate</label>
            <label class="vyr-check" style="margin: 0;"><input type="radio" name="mode" value="clear" x-model="unitMode"> Clear per-unit rates</label>
        </div>
        <input type="text" name="amount" class="vy-input" inputmode="decimal" placeholder="e.g. 175.00" x-show="unitMode === 'set'">
        {{-- ⚠️ CLEAR REMOVES THE OVERRIDE — it does not set €0. The unit goes back to following its room
             type's rate, and the preview shows that real figure before anything is written. --}}
        <div x-show="unitMode === 'clear'" style="font-size: 12px; color: var(--vy-muted);">That unit goes back to following the room-type rate.</div>
    </div>

    <div style="display: flex; gap: 10px; justify-content: flex-end;">
        {{ $actions ?? '' }}
        <button type="submit" class="vy-btn vy-btn--accent" style="padding: 9px 16px;">Preview changes</button>
    </div>
</form>
