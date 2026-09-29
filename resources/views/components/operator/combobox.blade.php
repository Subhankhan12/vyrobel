@props(['name', 'id', 'options' => [], 'selected' => null, 'search' => 'Search', 'empty' => 'Nothing matches'])
{{-- ⚠️ 2026-09-29 — A SEARCHABLE DROPDOWN for the operator app (first used for the time zone, ~420 choices). The same
     contract as the console's field system (ADMIN 05): PROGRESSIVE, never a replacement — the NATIVE <select> stays in the
     form and holds the value, so the posted field, validation and every test are unchanged, and it works with no script.
     The script reads the options FROM that select (no second copy of the list in the page), filters as you type
     (label and offset, "baku" or "utc+4"), and follows ↑/↓, Enter, Esc and Tab. The panel opens in the flow, under the
     field — never floating — so a dialog's focus trap and its scrolling both keep working.
     ⚠️ The native select is aria-hidden and out of the tab order: assistive tech meets ONE control, the button, which
     carries the label; the select needs no name of its own. Bind it from outside with x-model (x-modelable="value"). --}}
<div class="vy-combo" data-combobox
     x-data="{
        open: false, q: '', value: @js($selected), active: 0, all: [],
        init() { this.all = [...this.$refs.native.options].map(o => ({ value: o.value, label: o.text, hint: o.dataset.hint || '' })) },
        get shown() { const q = this.q.trim().toLowerCase(); return q === '' ? this.all : this.all.filter(o => (o.label + ' ' + o.value + ' ' + o.hint).toLowerCase().includes(q)) },
        get current() { return this.all.find(o => o.value === this.value) || null },
        show() { this.q = ''; this.open = true; this.active = Math.max(0, this.shown.findIndex(o => o.value === this.value)); this.$nextTick(() => { this.$refs.search.focus(); this.reveal() }) },
        hide(back = true) { this.open = false; if (back) this.$nextTick(() => this.$refs.trigger.focus()) },
        pick(o) { if (o) { this.value = o.value; this.hide() } },
        move(d) { const n = this.shown.length; if (n) { this.active = (this.active + d + n) % n; this.$nextTick(() => this.reveal()) } },
        reveal() { this.$refs.list.querySelector('[data-active=true]')?.scrollIntoView({ block: 'nearest' }) }
     }"
     x-modelable="value" {{ $attributes }}
     @keydown.escape="if (open) { $event.preventDefault(); $event.stopPropagation(); hide() }" @click.outside="if (open) hide(false)">
    <select name="{{ $name }}" x-ref="native" x-model="value" class="vy-combo-native" tabindex="-1" aria-hidden="true">
        @foreach ($options as $o)
            <option value="{{ $o['value'] }}" data-hint="{{ $o['hint'] ?? '' }}" @selected($o['value'] === $selected)>{{ $o['label'] }}</option>
        @endforeach
    </select>
    <button type="button" id="{{ $id }}" x-ref="trigger" class="vy-input vy-combo-trigger" aria-haspopup="listbox" aria-controls="{{ $id }}-list" :aria-expanded="open ? 'true' : 'false'" @click="open ? hide() : show()" @keydown.down.prevent="show()" @keydown.up.prevent="show()">
        <span class="vy-combo-value" x-text="current ? current.label : ''"></span>
        <span class="vy-combo-hint" x-text="current ? current.hint : ''"></span>
    </button>
    <div class="vy-combo-panel" x-show="open" x-cloak x-transition.opacity.duration.120ms>
        <input type="text" x-ref="search" x-model="q" class="vy-combo-search" placeholder="{{ $search }}" aria-label="{{ $search }}" autocomplete="off" spellcheck="false" role="combobox" aria-autocomplete="list" aria-controls="{{ $id }}-list" :aria-expanded="open ? 'true' : 'false'" :aria-activedescendant="shown.length ? '{{ $id }}-o-' + active : null" @input="active = 0" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="pick(shown[active])" @keydown.tab="hide(false)">
        <ul class="vy-combo-list" id="{{ $id }}-list" role="listbox" x-ref="list" aria-label="{{ $search }}">
            <template x-for="(o, i) in shown" :key="o.value">
                <li role="option" :id="'{{ $id }}-o-' + i" :aria-selected="o.value === value ? 'true' : 'false'" :data-active="i === active ? 'true' : 'false'" :class="{ 'is-active': i === active }" @click="pick(o)" @mousemove="active = i">
                    <span class="vy-combo-opt" x-text="o.label"></span><span class="vy-combo-hint" x-text="o.hint"></span>
                </li>
            </template>
        </ul>
        <p class="vy-combo-empty" x-show="shown.length === 0">{{ $empty }}</p>
    </div>
</div>
