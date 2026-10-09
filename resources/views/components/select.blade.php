{{-- The Vyrobel SELECT — one definition for a choice from a list (2026-10-09, his request on /app/profile: "the drop-downs
     are looking very basic … make me very cool drop-downs … unique, minimal and advanced … first work on this page").

     Closed, it is a field like every other `.vy-input` (same height, glass, radius, focus ring). Open, a floating glass
     panel: an optional search, options grouped under quiet headings, a terracotta tick on the chosen one, the row under the
     pointer or the keyboard tinted. An option can carry a `hint` (shown muted beside its label) and a `clock` (an IANA zone:
     the row shows the live time there, and so does the closed field when it is chosen).

     ⚠️ THE FORM VALUE IS A HIDDEN INPUT rendered with the current value on the server, so the form posts the right value
        before Alpine boots and if it never does; the closed field is server-rendered with the chosen label for the same reason.
     ⚠️ THE PANEL IS TELEPORTED TO <body> AND FIXED to the field: every card is a backdrop-filter layer, so a panel left inside
        one is painted UNDER the next card down (measured on the profile: Personal details over Preferences). It opens upward
        when there is no room below, and follows the field on scroll and resize.
     ⚠️ KEYBOARD AND SCREEN READERS (the APG combobox + listbox pattern): the field is `role=combobox`; ↓/↑/Enter/Space open,
        typing opens it (and searches when it has a search); in the list ↓/↑, Page Down/Up, Home/End, Enter chooses, Esc
        closes, Tab leaves. `aria-activedescendant` names the active row; each row is `role=option` with `aria-selected`.

     Props: name · id · options (list of [value, label, hint?, group?, clock?, keywords?], or [value => label]) · value ·
     labelledby (the id of the field's visible label) · searchable (null = automatic above 8 options) · placeholder ·
     searchPlaceholder · emptyText · deviceTimezone (offer this device's zone when it differs — timezone lists only) ·
     minWidth (the panel's least width in px).

     RAW-BLADE RULE: every directive follows a space, a newline or a tag. --}}
@props([
    'name',
    'id',
    'options' => [],
    'value' => null,
    'labelledby' => null,
    'searchable' => null,
    'placeholder' => 'Choose…',
    'searchPlaceholder' => 'Search',
    'emptyText' => 'Nothing matches that.',
    'deviceTimezone' => false,
    'minWidth' => 0,
])
@php
    $items = [];
    foreach ($options as $key => $option) {
        $option = is_array($option) ? $option : ['value' => (string) $key, 'label' => (string) $option];
        $label = (string) $option['label'];
        $hint = (string) ($option['hint'] ?? '');
        $items[] = [
            'value' => (string) $option['value'],
            'label' => $label,
            'hint' => $hint,
            'group' => (string) ($option['group'] ?? ''),
            'clock' => (string) ($option['clock'] ?? ''),
            'keywords' => mb_strtolower((string) ($option['keywords'] ?? ($label.' '.$option['value'].' '.$hint))),
        ];
    }
    $current = (string) ($value ?? '');
    $chosen = collect($items)->firstWhere('value', $current);
    $searchable ??= count($items) > 8;
    $listId = $id.'-list';
@endphp
<div class="vys" data-vy-select
     x-data="{
        open: false, up: false, pos: '', q: '', active: -1, now: Date.now(), tick: null, typed: '', typedAt: 0, device: null,
        value: @js($current),
        items: @js($items),
        placeholder: @js($placeholder),
        get filtered() {
            const words = this.q.trim().toLowerCase().split(/\s+/).filter(Boolean);
            return words.length ? this.items.filter((o) => words.every((w) => o.keywords.includes(w))) : this.items;
        },
        get rows() {
            const rows = []; let group = null;
            this.filtered.forEach((o, i) => {
                if (o.group && o.group !== group) { group = o.group; rows.push({ key: 'g:' + group, group }); }
                rows.push({ key: 'o:' + o.value, opt: o, i });
            });
            return rows;
        },
        get chosen() { return this.items.find((o) => o.value === this.value) || null; },
        init() {
            if (@js($deviceTimezone)) {
                try { const tz = Intl.DateTimeFormat().resolvedOptions().timeZone; this.device = this.items.find((o) => o.value === tz) || null; } catch (e) {}
            }
            if (this.items.some((o) => o.clock)) { this.tick = setInterval(() => { this.now = Date.now(); }, 15000); }
            this.$watch('q', () => { this.active = this.filtered.length ? 0 : -1; this.reveal(); });
        },
        destroy() { clearInterval(this.tick); this.unfollow(); },
        time(zone) {
            if (! zone) { return ''; }
            const at = new Date(this.now);   // read here, so every clock follows the 15-second tick
            const cache = (window.__vysClock = window.__vysClock || {});   // outside Alpine's state: filling it must not re-render
            if (! cache[zone]) {
                try { cache[zone] = new Intl.DateTimeFormat('en-GB', { timeZone: zone, hour: '2-digit', minute: '2-digit', hour12: false }); } catch (e) { return ''; }
            }
            return cache[zone].format(at);
        },
        show(seed) {
            if (this.open) { return; }
            this.q = @js($searchable) ? (seed || '') : '';
            this.open = true;
            const at = this.filtered.findIndex((o) => o.value === this.value);
            this.active = at >= 0 ? at : (this.filtered.length ? 0 : -1);
            if (seed && ! @js($searchable)) { this.jump(seed); }
            // ⚠️ One frame after the tick: the teleported panel is on screen only then, and a scroll asked of a hidden
            //    list does nothing — the walk saw it open at Africa with Lisbon chosen.
            this.$nextTick(() => requestAnimationFrame(() => {
                this.place();
                (this.$refs.search || this.$refs.list).focus({ preventScroll: true });
                this.reveal(true);
                this.follow();
            }));
        },
        hide(refocus = true) {
            if (! this.open) { return; }
            this.open = false; this.q = ''; this.unfollow();
            if (refocus) { this.$refs.field.focus({ preventScroll: true }); }
        },
        choose(o) {
            if (! o) { return; }
            const changed = o.value !== this.value;
            this.value = o.value;
            this.hide();
            if (changed) { this.$nextTick(() => this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }))); }
        },
        move(by) {
            const n = this.filtered.length; if (! n) { return; }
            this.active = Math.min(n - 1, Math.max(0, (this.active < 0 ? 0 : this.active + by)));
            this.reveal();
        },
        edge(last) { if (this.filtered.length) { this.active = last ? this.filtered.length - 1 : 0; this.reveal(); } },
        jump(key) {
            const at = Date.now();
            this.typed = (at - this.typedAt < 700 ? this.typed : '') + key.toLowerCase();
            this.typedAt = at;
            const list = this.filtered;
            const from = this.typed.length > 1 ? Math.max(0, this.active) : this.active + 1;
            for (let k = 0; k < list.length; k++) {
                const i = (from + k) % list.length;
                if (list[i].label.toLowerCase().startsWith(this.typed)) { this.active = i; this.reveal(); return; }
            }
        },
        reveal(centre = false) {
            this.$nextTick(() => {
                const list = this.$refs.list;
                const row = list && list.querySelector('[data-i=&quot;' + this.active + '&quot;]');
                if (! row) { return; }
                if (centre) {
                    // ⚠️ LAYOUT offsets, not screen rects: the panel opens with a 98.5% scale, and a rect measured mid-animation
                    //    is 1.5% short — about 195px for a zone 13,000px down the list (measured: Lisbon opened near the bottom).
                    list.scrollTop = (row.offsetTop - list.offsetTop) - (list.clientHeight - row.offsetHeight) / 2;
                } else {
                    row.scrollIntoView({ block: 'nearest' });
                }
            });
        },
        place() {
            const r = this.$refs.field.getBoundingClientRect();
            const gap = 6, edge = 8;
            const below = window.innerHeight - r.bottom - gap - edge, above = r.top - gap - edge;
            const want = Math.min(this.$refs.panel.scrollHeight || 360, 380);
            this.up = below < Math.min(want, 260) && above > below;
            const width = Math.min(Math.max(r.width, {{ (int) $minWidth }}), window.innerWidth - edge * 2);
            const left = Math.min(Math.max(edge, r.left), window.innerWidth - width - edge);
            const room = Math.max(160, Math.min(380, this.up ? above : below));
            this.pos = 'left:' + left + 'px;width:' + width + 'px;max-height:' + room + 'px;'
                + (this.up ? 'bottom:' + (window.innerHeight - r.top + gap) + 'px;' : 'top:' + (r.bottom + gap) + 'px;');
        },
        follow() {
            this._place = (e) => { if (! (e && e.target && this.$refs.panel.contains(e.target))) { this.place(); } };
            window.addEventListener('scroll', this._place, true);
            window.addEventListener('resize', this._place);
        },
        unfollow() {
            if (this._place) { window.removeEventListener('scroll', this._place, true); window.removeEventListener('resize', this._place); this._place = null; }
        },
        fieldKey(e) {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) { e.preventDefault(); this.show(); return; }
            if (e.key.length === 1 && ! e.metaKey && ! e.ctrlKey && ! e.altKey) { e.preventDefault(); this.show(e.key); }
        },
        listKey(e) {
            const typing = e.target === this.$refs.search;
            switch (e.key) {
                case 'ArrowDown': e.preventDefault(); this.move(1); break;
                case 'ArrowUp': e.preventDefault(); this.move(-1); break;
                case 'PageDown': e.preventDefault(); this.move(8); break;
                case 'PageUp': e.preventDefault(); this.move(-8); break;
                case 'Home': if (! typing) { e.preventDefault(); this.edge(false); } break;
                case 'End': if (! typing) { e.preventDefault(); this.edge(true); } break;
                case 'Enter': e.preventDefault(); this.choose(this.filtered[this.active]); break;
                case 'Escape': e.preventDefault(); e.stopPropagation(); this.hide(); break;
                case 'Tab': this.hide(false); break;
                default:
                    if (! typing && e.key.length === 1 && ! e.metaKey && ! e.ctrlKey && ! e.altKey) { e.preventDefault(); this.jump(e.key); }
            }
        },
     }"
     x-on:keydown.escape.window="open && hide()">
    <input type="hidden" name="{{ $name }}" value="{{ $current }}" x-ref="input" :value="value">

    <button type="button" id="{{ $id }}" x-ref="field" class="vys-field" :class="open && 'is-open'"
            role="combobox" aria-haspopup="listbox" aria-controls="{{ $listId }}" aria-expanded="false" :aria-expanded="open ? 'true' : 'false'"
            @if ($labelledby) aria-labelledby="{{ $labelledby }} {{ $id }}" @endif
            x-on:click="open ? hide() : show()" x-on:keydown="fieldKey($event)">
        <span class="vys-value">
            <span class="vys-label" x-text="chosen ? chosen.label : placeholder">{{ $chosen['label'] ?? $placeholder }}</span>
            <span class="vys-hint" x-show="chosen && chosen.hint" x-text="chosen ? chosen.hint : ''" @if (($chosen['hint'] ?? '') === '') style="display: none" @endif>{{ $chosen['hint'] ?? '' }}</span>
        </span>
        <span class="vys-clock" x-show="chosen && chosen.clock" x-text="chosen ? time(chosen.clock) : ''" style="display: none" aria-hidden="true"></span>
        <svg class="vys-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <template x-teleport="body">
        <div x-ref="panel" class="vys-panel" :class="up && 'is-up'" :style="pos" x-show="open" x-cloak
             x-on:click.outside="if (! $refs.field.contains($event.target)) hide(false)" x-on:keydown="listKey($event)">
            @if ($searchable)
                <div class="vys-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="text" x-ref="search" x-model="q" placeholder="{{ $searchPlaceholder }}" autocomplete="off" spellcheck="false"
                           role="combobox" aria-autocomplete="list" aria-expanded="true" aria-controls="{{ $listId }}" aria-label="{{ $searchPlaceholder }}"
                           :aria-activedescendant="active >= 0 ? '{{ $id }}-o-' + active : null">
                </div>
            @endif
            <ul x-ref="list" id="{{ $listId }}" class="vys-list" role="listbox" tabindex="-1"
                @if ($labelledby) aria-labelledby="{{ $labelledby }}" @endif
                @unless ($searchable) :aria-activedescendant="active >= 0 ? '{{ $id }}-o-' + active : null" @endunless>
                <template x-if="device && device.value !== value && ! q">
                    <li class="vys-suggest" role="presentation">
                        <button type="button" class="vys-suggest-btn" x-on:mousedown.prevent x-on:click="choose(device)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
                            <span>Use this device’s timezone: <strong x-text="device ? device.label : ''"></strong> <span class="vys-suggest-meta" x-text="device ? device.hint : ''"></span></span>
                        </button>
                    </li>
                </template>
                <template x-for="row in rows" :key="row.key">
                    <li :role="row.group ? 'presentation' : 'option'"
                        :class="row.group ? 'vys-group' : ('vys-opt' + (row.i === active ? ' is-active' : ''))"
                        :id="row.group ? null : '{{ $id }}-o-' + row.i"
                        :data-i="row.group ? null : row.i"
                        :aria-selected="row.group ? null : (row.opt.value === value ? 'true' : 'false')"
                        x-on:mousemove="row.group || (active = row.i)"
                        x-on:mousedown.prevent
                        x-on:click="row.group || choose(row.opt)">
                        <template x-if="row.group"><span x-text="row.group"></span></template>
                        <template x-if="! row.group">
                            <span class="vys-opt-in">
                                <svg class="vys-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                                <span class="vys-opt-label" x-text="row.opt.label"></span>
                                <span class="vys-opt-hint" x-show="row.opt.hint" x-text="row.opt.hint"></span>
                                <span class="vys-opt-time" x-show="row.opt.clock" x-text="time(row.opt.clock)"></span>
                            </span>
                        </template>
                    </li>
                </template>
                <li class="vys-empty" role="presentation" x-show="! filtered.length">{{ $emptyText }}</li>
            </ul>
        </div>
    </template>
</div>

@once
    <style>
        /* ── The Vyrobel select (components/select.blade.php) ─────────────────────────────────────────────── */
        .vys { position: relative; }
        .vys-field {
            width: 100%; display: flex; align-items: center; gap: 10px; min-height: 44px; padding: 0 12px 0 14px;
            border-radius: var(--vy-r-md, 12px); background: rgba(255, 255, 255, 0.7); border: 1px solid var(--vy-glass-border, rgba(255, 255, 255, .75));
            color: var(--vy-ink); font: inherit; font-size: 14px; text-align: left; cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        .vys-field:hover { background: rgba(255, 255, 255, 0.86); }
        .vys-field:focus-visible, .vys-field.is-open { outline: none; border-color: var(--vy-accent-border); box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.12); background: #fff; }
        .vys-value { flex: 1; min-width: 0; display: flex; align-items: baseline; gap: 8px; }
        .vys-label { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vys-hint { flex: none; font-size: 12px; color: var(--vy-muted); white-space: nowrap; }
        .vys-clock {
            flex: none; font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums; letter-spacing: .01em;
            color: var(--vy-accent-text); background: rgba(232, 112, 58, 0.10); border-radius: 999px; padding: 3px 9px;
        }
        .vys-chev { flex: none; width: 16px; height: 16px; color: var(--vy-muted); transition: transform .18s ease; }
        .vys-field.is-open .vys-chev { transform: rotate(180deg); color: var(--vy-accent-text); }

        .vys-panel {
            position: fixed; z-index: 1000; display: flex; flex-direction: column; overflow: hidden;
            background: rgba(255, 252, 248, 0.97); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15);
            border: 1px solid rgba(255, 255, 255, .9); border-radius: 14px;
            box-shadow: 0 24px 56px rgba(110, 55, 20, .20), 0 3px 10px rgba(110, 55, 20, .08), inset 0 1px 0 rgba(255, 255, 255, .7);
            font-family: var(--vy-sans, inherit); color: var(--vy-ink);
            transform-origin: top center; animation: vysIn .14s cubic-bezier(.2, .8, .2, 1);
        }
        .vys-panel.is-up { transform-origin: bottom center; animation-name: vysInUp; }
        @keyframes vysIn { from { opacity: 0; transform: translateY(-4px) scale(.985); } to { opacity: 1; transform: none; } }
        @keyframes vysInUp { from { opacity: 0; transform: translateY(4px) scale(.985); } to { opacity: 1; transform: none; } }
        .vys-search { flex: none; display: flex; align-items: center; gap: 8px; margin: 8px 8px 2px; padding: 0 11px; height: 38px; border-radius: 10px; background: rgba(120, 70, 40, .06); color: var(--vy-muted); }
        .vys-search:focus-within { background: #fff; box-shadow: inset 0 0 0 1px var(--vy-accent-border); }
        .vys-search svg { flex: none; width: 15px; height: 15px; }
        .vys-search input { flex: 1; min-width: 0; border: none; background: transparent; font: inherit; font-size: 13.5px; color: var(--vy-ink); outline: none; }
        .vys-search input::placeholder { color: var(--vy-faint); }
        .vys-list { list-style: none; margin: 0; padding: 4px 6px 6px; overflow-y: auto; overscroll-behavior: contain; scroll-padding: 6px; outline: none; }
        .vys-list { scrollbar-width: thin; scrollbar-color: rgba(194, 65, 12, .35) transparent; }
        .vys-list::-webkit-scrollbar { width: 8px; }
        .vys-list::-webkit-scrollbar-track { background: transparent; box-shadow: none; }
        .vys-list::-webkit-scrollbar-thumb { background: rgba(194, 65, 12, .32); border: 2px solid transparent; background-clip: padding-box; border-radius: 999px; box-shadow: none; }
        .vys-list::-webkit-scrollbar-thumb:hover { background-color: rgba(194, 65, 12, .5); }
        .vys-group { padding: 10px 10px 4px; font-size: 11.5px; font-weight: 600; color: var(--vy-faint); }
        .vys-opt { border-radius: 9px; cursor: pointer; }
        .vys-opt-in { display: flex; align-items: center; gap: 9px; min-height: 38px; padding: 0 10px; font-size: 13.5px; }
        .vys-opt.is-active { background: rgba(232, 112, 58, 0.10); }
        .vys-tick { flex: none; width: 15px; height: 15px; color: var(--vy-accent); visibility: hidden; }
        .vys-opt[aria-selected="true"] .vys-tick { visibility: visible; }
        .vys-opt[aria-selected="true"] .vys-opt-label { font-weight: 600; }
        .vys-opt-label { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vys-opt-hint { flex: none; font-size: 12px; color: var(--vy-muted); white-space: nowrap; }
        .vys-opt-time { flex: none; margin-left: auto; font-size: 12px; color: var(--vy-muted); font-variant-numeric: tabular-nums; }
        .vys-opt.is-active .vys-opt-time { color: var(--vy-accent-text); }
        .vys-empty { padding: 18px 12px; font-size: 13px; color: var(--vy-muted); text-align: center; }
        .vys-suggest { padding: 2px 0 4px; margin-bottom: 2px; border-bottom: 1px solid var(--vy-hairline); }
        .vys-suggest-btn { width: 100%; display: flex; align-items: center; gap: 9px; min-height: 38px; padding: 0 10px; border: none; border-radius: 9px; background: transparent; font: inherit; font-size: 13px; color: var(--vy-ink); text-align: left; cursor: pointer; }
        .vys-suggest-btn:hover, .vys-suggest-btn:focus-visible { background: rgba(232, 112, 58, 0.10); outline: none; }
        .vys-suggest-btn svg { flex: none; width: 15px; height: 15px; color: var(--vy-accent); }
        .vys-suggest-meta { color: var(--vy-muted); font-size: 12px; }
        @media (prefers-reduced-motion: reduce) { .vys-panel { animation: none; } .vys-chev, .vys-field { transition: none; } }
    </style>
@endonce
