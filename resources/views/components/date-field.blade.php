{{-- VYROBEL DATE FIELD — a themed, progressively-enhanced date picker (2026-10-21).

     ⚠️ THE NATIVE <input type="date"> STAYS, AND STAYS THE TRUTH. It carries the name and value, posts with the
        form, and works with NO JavaScript (the Insights custom range is explicitly a no-JS GET, D3-1). Alpine only
        ENHANCES: it hides the native control and draws a colourful on-theme trigger + a custom calendar popover,
        then writes the chosen ISO date straight back onto the native input (and fires `change`), so every form,
        URL and server read is unchanged.

     Props: name · id · value (ISO yyyy-mm-dd or '') · label (accessible) · placeholder.
     Other attributes (min, max, required, …) land on the native input.

     RAW-BLADE RULE: every directive follows a space, a newline or a tag. --}}
@props([
    'name',
    'id' => null,
    'value' => '',
    'label' => 'Date',
    'placeholder' => 'Pick a date',
])
@php($fieldId = $id ?? ('vydf-'.\Illuminate\Support\Str::random(6)))
<div class="vy-df" x-data="vyDateField(@js((string) $value))" @keydown.escape.stop="close()" @click.outside="close()">
    {{-- Truth: the real native input. Hidden once Alpine is live, but still submitted. --}}
    <label class="vy-sr-only" for="{{ $fieldId }}">{{ $label }}</label>
    <input type="date" name="{{ $name }}" id="{{ $fieldId }}" value="{{ $value }}"
           class="vy-df-native vy-pill" x-ref="native" x-model="iso"
           @change="syncFromNative()" {{ $attributes }}>

    {{-- Enhancement: the colourful trigger. Shown only when Alpine runs (x-cloak hides it until then). --}}
    <button type="button" class="vy-df-trigger" x-cloak @click="toggle()"
            :class="{ 'is-set': iso, 'is-open': open }" :aria-expanded="open.toString()"
            aria-haspopup="dialog" :aria-label="iso ? ('{{ $label }}: ' + prettyFull) : '{{ $label }}'">
        <svg class="vy-df-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4.5" width="18" height="16" rx="3"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></svg>
        <span class="vy-df-value" x-text="iso ? pretty : '{{ $placeholder }}'"></span>
        <svg class="vy-df-caret" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    {{-- The custom calendar popover. --}}
    <div class="vy-df-pop" x-cloak x-show="open" x-transition.origin.top.left role="dialog" aria-label="{{ $label }} calendar">
        <div class="vy-df-head">
            <button type="button" class="vy-df-nav" @click="shiftMonth(-1)" aria-label="Previous month"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>
            <div class="vy-df-title">
                <span class="vy-df-month" x-text="monthName"></span>
                <span class="vy-df-year" x-text="viewYear"></span>
            </div>
            <button type="button" class="vy-df-nav" @click="shiftMonth(1)" aria-label="Next month"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>
        </div>
        <div class="vy-df-grid vy-df-dow">
            <template x-for="d in ['Mo','Tu','We','Th','Fr','Sa','Su']" :key="d"><span class="vy-df-dowcell" x-text="d"></span></template>
        </div>
        <div class="vy-df-grid">
            <template x-for="(cell, i) in cells" :key="i">
                <button type="button" class="vy-df-day"
                        :class="{ 'is-blank': !cell, 'is-today': cell && cell.iso === todayIso, 'is-sel': cell && cell.iso === iso }"
                        :disabled="!cell" :tabindex="cell ? 0 : -1"
                        @click="cell && pick(cell.iso)" x-text="cell ? cell.day : ''"></button>
            </template>
        </div>
        <div class="vy-df-foot">
            <button type="button" class="vy-df-foot-btn" @click="goToday()">Today</button>
            <button type="button" class="vy-df-foot-btn" x-show="iso" @click="clearDate()">Clear</button>
        </div>
    </div>
</div>
@once
    <script>
        function vyDateField(initial) {
            return {
                iso: initial || '',
                open: false,
                viewYear: 0,
                viewMonth: 0,
                todayIso: '',
                init() {
                    var t = new Date();
                    this.todayIso = this.toIso(t);
                    var base = this.iso ? this.parse(this.iso) : t;
                    this.viewYear = base.getFullYear();
                    this.viewMonth = base.getMonth();
                },
                parse(iso) { var p = iso.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); },
                toIso(d) {
                    var m = String(d.getMonth() + 1).padStart(2, '0');
                    var day = String(d.getDate()).padStart(2, '0');
                    return d.getFullYear() + '-' + m + '-' + day;
                },
                get monthName() {
                    return ['January','February','March','April','May','June','July','August','September','October','November','December'][this.viewMonth];
                },
                get pretty() {
                    if (! this.iso) { return ''; }
                    var d = this.parse(this.iso);
                    var mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getMonth()];
                    return d.getDate() + ' ' + mo + ' ' + d.getFullYear();
                },
                get prettyFull() {
                    if (! this.iso) { return ''; }
                    var d = this.parse(this.iso);
                    return d.toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                },
                get cells() {
                    var first = new Date(this.viewYear, this.viewMonth, 1);
                    var lead = (first.getDay() + 6) % 7;
                    var days = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
                    var out = [];
                    for (var i = 0; i < lead; i++) { out.push(null); }
                    for (var d = 1; d <= days; d++) {
                        out.push({ day: d, iso: this.toIso(new Date(this.viewYear, this.viewMonth, d)) });
                    }
                    while (out.length % 7 !== 0) { out.push(null); }
                    return out;
                },
                toggle() { this.open ? this.close() : this.openPicker(); },
                openPicker() {
                    if (this.iso) { var b = this.parse(this.iso); this.viewYear = b.getFullYear(); this.viewMonth = b.getMonth(); }
                    this.open = true;
                },
                close() { this.open = false; },
                shiftMonth(n) {
                    var m = this.viewMonth + n;
                    this.viewYear += Math.floor(m / 12);
                    this.viewMonth = ((m % 12) + 12) % 12;
                },
                pick(iso) { this.iso = iso; this.pushToNative(); this.close(); },
                goToday() { var t = new Date(); this.viewYear = t.getFullYear(); this.viewMonth = t.getMonth(); },
                clearDate() { this.iso = ''; this.pushToNative(); this.close(); },
                syncFromNative() { this.iso = this.$refs.native.value; },
                pushToNative() {
                    var el = this.$refs.native;
                    el.value = this.iso;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                },
            };
        }
    </script>
@endonce
