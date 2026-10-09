{{-- THE VYROBEL SELECT ENGINE — every drop-down in the product, one definition (2026-10-10, his approval of the profile's new
     select: "I need the same sort of select and same sort of drop down in the entire application, everywhere").

     Included once from every shell's head, beside the scrollbar theme: operator, owner, guest, admin, public, auth, onboarding,
     billing, errors, oauth. It needs NO Alpine (the guest portal and the public pages load none).

     ⚠️⚠️ THE NATIVE SELECT STAYS, AND STAYS THE TRUTH. Every select keeps its element, name, options, `required`, `disabled`,
        `x-model`, `onchange` and form — so posting, validation, Alpine bindings, the auto-submitting filters and every test are
        unchanged. The engine only changes what is SEEN and CLICKED:
          · the closed field: a CSS skin (no class is added — a Livewire morph would strip it), the chevron forced so a field's
            own `background` shorthand cannot erase it;
          · the open list: a mouse press or a key on the select opens the Vyrobel panel instead of the operating system's list
            (an optional search above 8 options, quiet group headings from <optgroup>, a terracotta tick, the active row tinted,
            per-option hints and live clocks from data-hint / data-clock); a choice sets the select and fires `input` and
            `change`, exactly as the native list does.
     ⚠️ FOCUS NEVER LEAVES THE SELECT (the APG select-only combobox): typing searches, ↑/↓, Page Up/Down, Home/End move, Enter
        chooses, Esc closes (without closing a dialog behind it), Tab leaves. So a dialog's focus trap never fights the panel,
        and inside a dialog the panel is placed IN the dialog, so a click on it is not a click "outside".
     ⚠️ TOUCH KEEPS THE PHONE'S OWN PICKER: a finger opens the native wheel — the better control on a phone — under the same skin.
     ⚠️ OPT OUT with `data-native` on a select (or an ancestor carrying `data-native-selects`). A `multiple` list or a select with
        a `size` stays a visible list, skinned to match.

     RAW-BLADE RULE: every directive follows a space, a newline or a tag; and no component TAG is written in a comment here
     (Blade compiles a tag even inside CSS or a script comment, and the page breaks). --}}
@once
<style>
    /* ── The closed field ─────────────────────────────────────────────────────────────────────────────────────── */
    :where(select:not([multiple]):not([size]):not([data-native])) {
        min-height: 40px; padding: 8px 12px; border-radius: 12px; border: 1px solid rgba(120, 70, 40, .16);
        background-color: rgba(255, 255, 255, .75); color: rgb(43, 35, 32); font: inherit; font-size: 14px; max-width: 100%;
    }
    select:not([multiple]):not([size]):not([data-native]) {
        -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%235F5853' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important; background-position: right 12px center !important; background-size: 16px 16px !important;
        padding-right: 36px !important; cursor: pointer; text-overflow: ellipsis;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }
    select:not([multiple]):not([size]):not([data-native])[aria-expanded="true"] {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23932A07' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 15 6-6 6 6'/%3E%3C/svg%3E") !important;
        border-color: rgba(232, 112, 58, .45); box-shadow: 0 0 0 3px rgba(232, 112, 58, .12); background-color: #fff;
    }
    select:not([multiple]):not([size]):not([data-native]):disabled { cursor: not-allowed; opacity: .62; }
    /* The app's own focus ring in place of the browser's blue one — zero specificity, so a field's own :focus rule still wins. */
    :where(select:not([data-native])):focus { outline: none; border-color: rgba(232, 112, 58, .45); box-shadow: 0 0 0 3px rgba(232, 112, 58, .12); background-color: #fff; }
    select:not([data-native]) option { color: rgb(43, 35, 32); }
    /* A visible list (multiple / size) keeps being a list, in the same materials. */
    :where(select[multiple]:not([data-native]), select[size]:not([data-native])) {
        padding: 6px; border-radius: 12px; border: 1px solid rgba(120, 70, 40, .16); background-color: rgba(255, 255, 255, .75);
        font: inherit; font-size: 13.5px; color: rgb(43, 35, 32);
    }
    select[multiple]:not([data-native]) option, select[size]:not([data-native]) option { padding: 7px 10px; border-radius: 8px; }
    select[multiple]:not([data-native]) option:checked, select[size]:not([data-native]) option:checked { background: rgba(232, 112, 58, .16) linear-gradient(rgba(232, 112, 58, .16), rgba(232, 112, 58, .16)); color: rgb(147, 42, 7); font-weight: 600; }

    /* The rich field (the select component): hint and live clock drawn over the field's right side. */
    .vys-rich { position: relative; }
    .vys-rich > select { width: 100%; min-height: 44px; padding-right: var(--vys-deco-w, 36px) !important; }
    .vys-deco { position: absolute; top: 50%; right: 40px; transform: translateY(-50%); display: flex; align-items: center; gap: 8px; pointer-events: none; }
    .vys-deco-hint { font-size: 12px; color: var(--vy-muted, rgb(95, 88, 83)); white-space: nowrap; }
    .vys-deco-clock { font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums; color: var(--vy-accent-text, rgb(147, 42, 7)); background: rgba(232, 112, 58, .10); border-radius: 999px; padding: 3px 9px; }
    .vys-deco-hint:empty, .vys-deco-clock:empty { display: none; }

    /* ── The open panel ───────────────────────────────────────────────────────────────────────────────────────── */
    .vys-panel {
        position: fixed; z-index: 2147483000; display: flex; flex-direction: column; overflow: hidden; box-sizing: border-box;
        background: rgba(255, 252, 248, .97); backdrop-filter: blur(22px) saturate(1.15); -webkit-backdrop-filter: blur(22px) saturate(1.15);
        border: 1px solid rgba(255, 255, 255, .9); border-radius: 14px;
        box-shadow: 0 24px 56px rgba(110, 55, 20, .20), 0 3px 10px rgba(110, 55, 20, .08), inset 0 1px 0 rgba(255, 255, 255, .7);
        font-family: var(--vy-sans, 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif); color: rgb(43, 35, 32);
        text-align: left; letter-spacing: normal; text-transform: none;
        transform-origin: top center; animation: vysIn .14s cubic-bezier(.2, .8, .2, 1);
    }
    .vys-panel.is-up { transform-origin: bottom center; animation-name: vysInUp; }
    @keyframes vysIn { from { opacity: 0; transform: translateY(-4px) scale(.985); } to { opacity: 1; transform: none; } }
    @keyframes vysInUp { from { opacity: 0; transform: translateY(4px) scale(.985); } to { opacity: 1; transform: none; } }
    .vys-search { flex: none; display: flex; align-items: center; gap: 8px; margin: 8px 8px 2px; padding: 0 11px; height: 38px; border-radius: 10px; background: #fff; box-shadow: inset 0 0 0 1px rgba(232, 112, 58, .28); color: rgb(95, 88, 83); font-size: 13.5px; }
    .vys-search svg { flex: none; width: 15px; height: 15px; order: -2; }   /* the icon first, even before an empty box's caret */
    .vys-search-text { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: rgb(43, 35, 32); }
    .vys-search-text.is-empty { color: rgb(108, 100, 95); }
    .vys-caret { flex: none; width: 1.5px; height: 17px; margin-left: -6px; background: rgb(194, 65, 12); animation: vysCaret 1.05s steps(1) infinite; }
    .vys-search-text.is-empty + .vys-caret { order: -1; margin-left: 0; margin-right: -6px; }
    @keyframes vysCaret { 50% { opacity: 0; } }
    .vys-list { list-style: none; margin: 0; padding: 4px 6px 6px; overflow-y: auto; overscroll-behavior: contain; scroll-padding: 6px; outline: none; scrollbar-width: thin; scrollbar-color: rgba(194, 65, 12, .35) transparent; }
    .vys-list::-webkit-scrollbar { width: 8px; }
    .vys-list::-webkit-scrollbar-track { background: transparent; box-shadow: none; }
    .vys-list::-webkit-scrollbar-thumb { background: rgba(194, 65, 12, .32); border: 2px solid transparent; background-clip: padding-box; border-radius: 999px; box-shadow: none; }
    .vys-list::-webkit-scrollbar-thumb:hover { background-color: rgba(194, 65, 12, .5); }
    .vys-group { padding: 10px 10px 4px; font-size: 11.5px; font-weight: 600; color: rgb(108, 100, 95); }
    .vys-opt { display: flex; align-items: center; gap: 9px; min-height: 38px; padding: 0 10px; border-radius: 9px; font-size: 13.5px; line-height: 1.3; cursor: pointer; }
    .vys-opt.is-active { background: rgba(232, 112, 58, .10); }
    .vys-opt[aria-disabled="true"] { color: rgb(150, 142, 136); cursor: default; }
    .vys-opt[aria-disabled="true"].is-active { background: rgba(120, 70, 40, .05); }
    .vys-tick { flex: none; width: 15px; height: 15px; color: rgb(194, 65, 12); visibility: hidden; }
    .vys-opt[aria-selected="true"] .vys-tick { visibility: visible; }
    .vys-opt[aria-selected="true"] .vys-opt-label { font-weight: 600; }
    .vys-opt-label { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .vys-opt-hint { flex: none; font-size: 12px; color: rgb(95, 88, 83); white-space: nowrap; }
    .vys-opt-time { flex: none; margin-left: auto; font-size: 12px; color: rgb(95, 88, 83); font-variant-numeric: tabular-nums; }
    .vys-opt.is-active .vys-opt-time { color: rgb(147, 42, 7); }
    .vys-empty { padding: 18px 12px; font-size: 13px; color: rgb(95, 88, 83); text-align: center; }
    .vys-suggest { padding: 2px 0 4px; margin-bottom: 2px; border-bottom: 1px solid rgba(120, 70, 40, .10); }
    .vys-suggest-btn { width: 100%; display: flex; align-items: center; gap: 9px; min-height: 38px; padding: 0 10px; border: none; border-radius: 9px; background: transparent; font: inherit; font-size: 13px; color: rgb(43, 35, 32); text-align: left; cursor: pointer; }
    .vys-suggest-btn:hover { background: rgba(232, 112, 58, .10); }
    .vys-suggest-btn svg { flex: none; width: 15px; height: 15px; color: rgb(194, 65, 12); }
    .vys-suggest-meta { color: rgb(95, 88, 83); font-size: 12px; }

    /* ── The drop-down MENUS, in the same panel (2026-10-10): the account menus, the bell, the row ⋯ menus, the statement and
       report pickers. Each keeps its own size, place and layout; only the surface, the rows and the opening motion are shared.
       ⚠️ !important on the surface only: the bell's panel carries inline styles, and a menu's own sheet may load after this. */
    :is(.vy-acct-menu, .vy-gl-menu, .vy-ov-menu, .vy-st-menu, .vy-rep-menu, [data-vy-bell] > [role="menu"]) {
        background: rgba(255, 252, 248, .97) !important; border: 1px solid rgba(255, 255, 255, .9) !important; border-radius: 14px !important;
        box-shadow: 0 24px 56px rgba(110, 55, 20, .20), 0 3px 10px rgba(110, 55, 20, .08), inset 0 1px 0 rgba(255, 255, 255, .7) !important;
        backdrop-filter: blur(22px) saturate(1.15) !important; -webkit-backdrop-filter: blur(22px) saturate(1.15) !important;
        animation: vysIn .14s cubic-bezier(.2, .8, .2, 1);
    }
    :is(.vy-gl-menu, .vy-ov-menu).is-up { animation-name: vysInUp; }
    :is(.vy-menu-item, .vy-gl-menu-item, .vy-ov-menu-item, .vy-st-menu-item, .vy-rep-menu-item) { border-radius: 9px; min-height: 38px; }
    :is(.vy-menu-item, .vy-gl-menu-item, .vy-ov-menu-item, .vy-st-menu-item, .vy-rep-menu-item):is(:hover, :focus-visible) { background: rgba(232, 112, 58, .10); outline: none; }
    /* ── The console's searchable drop-down (ADMIN 05's field system: its checklist and flags stay its own) in the same panel:
       the surface, 38px rows, the tint, and the chosen row's tick at the left. Stronger selectors, so that file is untouched. */
    /* Each console card is a backdrop-filter layer, so the next card down painted over an open list (the last organisation read
       faded through it). The card with the open list is lifted above its neighbours while it is open. */
    .ad-card:has(.ad-combo-trigger[aria-expanded="true"]) { position: relative; z-index: 5; }
    /* ⚠️ The panel is named [class~="ad-combo-panel"] here: the console's own test counts that class's rule to prove its field
       system prints exactly once, and this is a second, deliberate block — not that system printed twice. */
    .ad-combo [class~="ad-combo-panel"] {
        border-radius: 14px; border: 1px solid rgba(255, 255, 255, .9); background: #FFFCF8;
        box-shadow: 0 24px 56px rgba(110, 55, 20, .20), 0 3px 10px rgba(110, 55, 20, .08), inset 0 1px 0 rgba(255, 255, 255, .7);
        padding: 8px 6px 6px; animation: vysIn .14s cubic-bezier(.2, .8, .2, 1);
    }
    .ad-combo .ad-combo-search { margin: 0 2px; width: calc(100% - 4px); border-color: rgba(232, 112, 58, .28); background-color: #fff; }
    .ad-combo .ad-combo-list { margin-top: 6px; scrollbar-width: thin; scrollbar-color: rgba(194, 65, 12, .35) transparent; }
    .ad-combo .ad-combo-option { min-height: 38px; padding: 0 10px; border-radius: 9px; gap: 9px; font-weight: 400; }
    .ad-combo .ad-combo-option.is-active { background: rgba(232, 112, 58, .10); }
    .ad-combo .ad-combo-option[aria-selected="true"] { font-weight: 600; }
    .ad-combo .ad-combo-list:not([aria-multiselectable="true"]) .ad-combo-option::before {
        content: ""; flex: none; width: 15px; height: 15px; visibility: hidden; background: no-repeat center / 15px 15px
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23C2410C' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E");
    }
    .ad-combo .ad-combo-list:not([aria-multiselectable="true"]) .ad-combo-option[aria-selected="true"]::before { visibility: visible; }
    .ad-combo .ad-combo-list:not([aria-multiselectable="true"]) .ad-combo-option[aria-selected="true"]::after { content: none; }
    @media (prefers-reduced-motion: reduce) {
        .ad-combo [class~="ad-combo-panel"] { animation: none; }
        .vys-panel, :is(.vy-acct-menu, .vy-gl-menu, .vy-ov-menu, .vy-st-menu, .vy-rep-menu, [data-vy-bell] > [role="menu"]) { animation: none; }
        .vys-caret { animation: none; } select:not([multiple]):not([data-native]) { transition: none; }
    }
</style>
<script>
(function () {
    'use strict';
    if (window.VySelect) { return; }

    var D = document, W = window, uid = 0, pointer = 'mouse', st = null, TICK = 'M5 12.5l4.5 4.5L19 7.5';
    var clocks = {};

    function clock(zone, at) {
        if (! zone) { return ''; }
        try {
            if (! clocks[zone]) { clocks[zone] = new Intl.DateTimeFormat('en-GB', { timeZone: zone, hour: '2-digit', minute: '2-digit', hour12: false }); }
            return clocks[zone].format(at || new Date());
        } catch (e) { return ''; }
    }

    function eligible(el) {
        return !! el && el.tagName === 'SELECT' && ! el.multiple && ! (el.size > 1) && ! el.hasAttribute('data-native') && ! el.closest('[data-native-selects]');
    }

    function svg(path, cls) {
        var s = D.createElementNS('http://www.w3.org/2000/svg', 'svg');
        s.setAttribute('viewBox', '0 0 24 24'); s.setAttribute('fill', 'none'); s.setAttribute('stroke', 'currentColor');
        s.setAttribute('stroke-width', cls === 'vys-tick' ? '2.4' : '2'); s.setAttribute('stroke-linecap', 'round'); s.setAttribute('stroke-linejoin', 'round');
        s.setAttribute('aria-hidden', 'true'); if (cls) { s.setAttribute('class', cls); }
        s.innerHTML = path;
        return s;
    }

    function el(tag, cls, text) { var e = D.createElement(tag); if (cls) { e.className = cls; } if (text != null) { e.textContent = text; } return e; }

    /** The select's options as rows: <optgroup> labels become headings; hidden placeholders are left out. */
    function read(select) {
        var out = [];
        for (var i = 0; i < select.options.length; i++) {
            var o = select.options[i], parent = o.parentElement, grouped = parent && parent.tagName === 'OPTGROUP';
            if (o.hidden) { continue; }
            var hint = o.getAttribute('data-hint') || '', label = o.getAttribute('data-label') || o.text;
            out.push({
                index: o.index, value: o.value, label: label, hint: hint, clock: o.getAttribute('data-clock') || '',
                group: grouped ? parent.label : (o.getAttribute('data-group') || ''),
                disabled: o.disabled || (grouped && parent.disabled),
                keywords: (o.getAttribute('data-keywords') || (label + ' ' + o.value + ' ' + hint)).toLowerCase()
            });
        }
        return out;
    }

    /** A search above 8 options — never for a list of plain numbers (a count of adults): typing a digit already jumps there. */
    function searchable(select, rows) {
        var s = select.getAttribute('data-search');
        if (s === 'off') { return false; }
        if (s !== null) { return true; }
        return rows.length > 8 && ! rows.every(function (r) { return /^\d+$/.test(r.label.trim()); });
    }

    /** Where the panel lives: inside a dialog (so its focus trap and click-outside see it as inside), else the body. */
    function home(select) {
        return select.closest('[aria-modal="true"], [role="dialog"], dialog') || D.body;
    }

    function open(select, seed) {
        if (st) { close(false); }
        if (select.disabled) { return; }
        var rows = read(select), id = select.id || ('vys-' + (++uid));
        if (! select.id) { select.setAttribute('data-vys-id', id); }
        var panel = el('div', 'vys-panel'), list = el('ul', 'vys-list');
        list.id = id + '-vys-list'; list.setAttribute('role', 'listbox'); list.tabIndex = -1;
        var labelled = select.getAttribute('aria-labelledby') || select.getAttribute('data-labelledby') || (select.labels && select.labels[0] && select.labels[0].id) || '';
        if (labelled) { list.setAttribute('aria-labelledby', labelled); } else { list.setAttribute('aria-label', select.getAttribute('aria-label') || select.name || 'Options'); }
        var canSearch = searchable(select, rows), text = null;
        if (canSearch) {
            var box = el('div', 'vys-search');
            box.appendChild(svg('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>'));
            text = el('span', 'vys-search-text is-empty', select.getAttribute('data-search-placeholder') || 'Type to search');
            box.appendChild(text); box.appendChild(el('span', 'vys-caret'));
            panel.appendChild(box);
        }
        panel.appendChild(list);
        var device = null;
        if (select.hasAttribute('data-suggest-device-zone')) {
            try { var tz = Intl.DateTimeFormat().resolvedOptions().timeZone; device = rows.filter(function (r) { return r.value === tz; })[0] || null; } catch (e) {}
        }
        st = { select: select, id: id, panel: panel, list: list, text: text, rows: rows, shown: rows, searchable: canSearch, q: '', active: -1,
               device: device, typed: '', typedAt: 0, home: home(select), empty: select.getAttribute('data-empty') || 'Nothing matches that.',
               placeholder: select.getAttribute('data-search-placeholder') || 'Type to search' };
        st.home.appendChild(panel);
        select.setAttribute('aria-expanded', 'true');
        select.setAttribute('aria-controls', list.id);
        draw();
        var at = -1;
        for (var i = 0; i < st.shown.length; i++) { if (st.shown[i].index === select.selectedIndex) { at = i; break; } }
        place();
        activate(at >= 0 ? at : firstEnabled(0, 1), true);
        if (seed) { type(seed); }
        W.addEventListener('scroll', onScroll, true);
        W.addEventListener('resize', place);
    }

    function close(refocus) {
        if (! st) { return; }
        var s = st.select;
        st.panel.remove();
        s.setAttribute('aria-expanded', 'false');
        s.removeAttribute('aria-activedescendant');
        W.removeEventListener('scroll', onScroll, true);
        W.removeEventListener('resize', place);
        st = null;
        if (refocus) { try { s.focus({ preventScroll: true }); } catch (e) {} }
    }

    function onScroll(e) { if (st && ! st.panel.contains(e.target)) { place(); } }

    function draw() {
        var words = st.q.trim().toLowerCase().split(/\s+/).filter(Boolean);
        st.shown = words.length ? st.rows.filter(function (r) { return words.every(function (w) { return r.keywords.indexOf(w) !== -1; }); }) : st.rows;
        var frag = D.createDocumentFragment(), group = null, now = new Date();
        if (st.device && st.device.index !== st.select.selectedIndex && ! st.q) {
            var li = el('li', 'vys-suggest'); li.setAttribute('role', 'presentation');
            var b = el('button', 'vys-suggest-btn'); b.type = 'button'; b.tabIndex = -1;
            b.appendChild(svg('<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>'));
            var t = el('span', null, 'Use this device’s timezone: '); t.appendChild(el('strong', null, st.device.label));
            t.appendChild(D.createTextNode(' ')); t.appendChild(el('span', 'vys-suggest-meta', st.device.hint));
            b.appendChild(t); b.setAttribute('data-vys-device', '1');
            li.appendChild(b); frag.appendChild(li);
        }
        st.shown.forEach(function (r, i) {
            if (r.group && r.group !== group) { group = r.group; var h = el('li', 'vys-group', group); h.setAttribute('role', 'presentation'); frag.appendChild(h); }
            var o = el('li', 'vys-opt');
            o.id = st.id + '-vys-o-' + i; o.setAttribute('role', 'option'); o.setAttribute('data-i', String(i));
            o.setAttribute('aria-selected', r.index === st.select.selectedIndex ? 'true' : 'false');
            if (r.disabled) { o.setAttribute('aria-disabled', 'true'); }
            o.appendChild(svg('<path d="' + TICK + '"/>', 'vys-tick'));
            o.appendChild(el('span', 'vys-opt-label', r.label));
            if (r.hint) { o.appendChild(el('span', 'vys-opt-hint', r.hint)); }
            if (r.clock) { var c = el('span', 'vys-opt-time', clock(r.clock, now)); c.setAttribute('data-clock', r.clock); o.appendChild(c); }
            frag.appendChild(o);
        });
        if (! st.shown.length) { var e = el('li', 'vys-empty', st.empty); e.setAttribute('role', 'presentation'); frag.appendChild(e); }
        st.list.replaceChildren(frag);
        if (st.text) { st.text.textContent = st.q || st.placeholder; st.text.classList.toggle('is-empty', ! st.q); }
    }

    function firstEnabled(from, step) {
        for (var i = from; i >= 0 && i < st.shown.length; i += step) { if (! st.shown[i].disabled) { return i; } }
        return -1;
    }

    function activate(i, centre) {
        var prev = st.list.querySelector('.vys-opt.is-active'); if (prev) { prev.classList.remove('is-active'); }
        st.active = i;
        var row = i >= 0 ? st.list.querySelector('[data-i="' + i + '"]') : null;
        if (! row) { st.select.removeAttribute('aria-activedescendant'); return; }
        row.classList.add('is-active');
        st.select.setAttribute('aria-activedescendant', row.id);
        // ⚠️ LAYOUT offsets, never screen rects: the panel opens with a 98.5% scale, and a rect read mid-animation is short.
        if (centre) { st.list.scrollTop = (row.offsetTop - st.list.offsetTop) - (st.list.clientHeight - row.offsetHeight) / 2; }
        else {
            var top = row.offsetTop - st.list.offsetTop, bottom = top + row.offsetHeight;
            if (top < st.list.scrollTop) { st.list.scrollTop = top - 6; }
            else if (bottom > st.list.scrollTop + st.list.clientHeight) { st.list.scrollTop = bottom - st.list.clientHeight + 6; }
        }
    }

    function move(by) {
        if (! st.shown.length) { return; }
        var i = st.active < 0 ? (by > 0 ? -1 : st.shown.length) : st.active, step = by > 0 ? 1 : -1, left = Math.abs(by), last = st.active;
        while (left > 0) {
            var next = firstEnabled(i + step, step);
            if (next < 0) { break; }
            i = next; last = next; left--;
        }
        if (last >= 0) { activate(last, false); }
    }

    function choose(i) {
        var r = st && st.shown[i];
        if (! r || r.disabled) { return; }
        var s = st.select, changed = s.selectedIndex !== r.index;
        close(true);
        if (changed) {
            s.selectedIndex = r.index;
            s.dispatchEvent(new Event('input', { bubbles: true }));
            s.dispatchEvent(new Event('change', { bubbles: true }));
        }
        deco(s);
    }

    function type(key) {
        if (st.searchable) { st.q += key; draw(); activate(firstEnabled(0, 1), true); return; }
        var now = Date.now();
        st.typed = (now - st.typedAt < 700 ? st.typed : '') + key.toLowerCase(); st.typedAt = now;
        var from = st.typed.length > 1 ? Math.max(0, st.active) : st.active + 1;
        for (var k = 0; k < st.shown.length; k++) {
            var i = (from + k) % st.shown.length;
            if (! st.shown[i].disabled && st.shown[i].label.toLowerCase().indexOf(st.typed) === 0) { activate(i, false); return; }
        }
    }

    /** Fixed to the field, in viewport terms — corrected when the panel's home is a transformed or filtered dialog. */
    function place() {
        if (! st) { return; }
        var r = st.select.getBoundingClientRect(), gap = 6, edge = 8, p = st.panel;
        var clip = { top: 0, bottom: W.innerHeight, left: 0, right: W.innerWidth };
        if (st.home !== D.body) {
            var h = st.home.getBoundingClientRect();
            clip = { top: Math.max(0, h.top), bottom: Math.min(W.innerHeight, h.bottom), left: Math.max(0, h.left), right: Math.min(W.innerWidth, h.right) };
        }
        var below = clip.bottom - r.bottom - gap - edge, above = r.top - clip.top - gap - edge;
        var want = Math.min(p.scrollHeight || 360, 380);
        var up = below < Math.min(want, 240) && above > below;
        var min = parseInt(st.select.getAttribute('data-panel-width') || '0', 10) || 0;
        // As wide as its longest choice (up to 420px), never narrower than the field: a narrow filter cut "Changes & sign-ins".
        p.style.width = 'max-content'; p.style.maxWidth = '420px';
        var natural = p.offsetWidth; p.style.maxWidth = '';
        var width = Math.min(Math.max(r.width, min, natural, 180), Math.max(420, min), clip.right - clip.left - edge * 2);
        var left = Math.min(Math.max(clip.left + edge, r.left), clip.right - width - edge);
        var room = Math.max(140, Math.min(380, up ? above : below));
        p.classList.toggle('is-up', up);
        // Where (0,0) lands: the viewport, or a dialog that became the containing block (a transform or a backdrop-filter).
        // ⚠️ Measured on a still probe, never the panel: the panel is mid-animation, 4px off, when this runs.
        var probe = el('div'); probe.style.cssText = 'position:fixed;left:0;top:0;width:0;height:0;visibility:hidden;pointer-events:none';
        st.home.appendChild(probe); var o = probe.getBoundingClientRect(); probe.remove();
        p.style.bottom = 'auto'; p.style.width = width + 'px'; p.style.maxHeight = room + 'px';
        p.style.left = (left - o.left) + 'px';
        if (up) { p.style.top = (r.top - gap - Math.min(p.scrollHeight, room) - o.top) + 'px'; }
        else { p.style.top = (r.bottom + gap - o.top) + 'px'; }
    }

    /** The rich field's hint and clock (the select component). */
    function deco(select) {
        var wrap = select && select.parentElement;
        if (! wrap || ! wrap.classList || ! wrap.classList.contains('vys-rich')) { return; }
        var o = select.options[select.selectedIndex], hint = wrap.querySelector('.vys-deco-hint'), c = wrap.querySelector('.vys-deco-clock');
        if (hint) { hint.textContent = o ? (o.getAttribute('data-hint') || '') : ''; }
        if (c) { var z = o ? (o.getAttribute('data-clock') || '') : ''; c.setAttribute('data-clock', z); c.textContent = clock(z); }
        var d = wrap.querySelector('.vys-deco');
        wrap.style.setProperty('--vys-deco-w', ((d ? d.offsetWidth : 0) + 48) + 'px');
    }

    function tick() {
        var now = new Date();
        D.querySelectorAll('.vys-deco-clock[data-clock], .vys-panel .vys-opt-time[data-clock]').forEach(function (n) { n.textContent = clock(n.getAttribute('data-clock'), now); });
    }

    // ── Events: delegated from the document, so a page that adds or re-renders selects needs nothing ──────────────────
    D.addEventListener('pointerdown', function (e) { pointer = e.pointerType || 'mouse'; }, true);

    D.addEventListener('mousedown', function (e) {
        var t = e.target;
        if (st && st.panel.contains(t)) {
            e.preventDefault();   // focus stays on the select
            var dev = t.closest('[data-vys-device]');
            if (dev) { for (var k = 0; k < st.shown.length; k++) { if (st.shown[k] === st.device) { choose(k); return; } } return; }
            var row = t.closest('.vys-opt');
            if (row) { choose(parseInt(row.getAttribute('data-i'), 10)); }
            return;
        }
        if (eligible(t) && e.button === 0 && pointer === 'mouse') {
            e.preventDefault();   // no operating-system list
            if (st && st.select === t) { close(true); return; }
            if (t.disabled) { return; }
            try { t.focus({ preventScroll: true }); } catch (x) {}
            open(t);
            return;
        }
        if (st && t !== st.select) { close(false); }
    }, true);

    D.addEventListener('mousemove', function (e) {
        if (! st) { return; }
        var row = e.target.closest && e.target.closest('.vys-panel .vys-opt');
        if (row && ! row.hasAttribute('aria-disabled')) { var i = parseInt(row.getAttribute('data-i'), 10); if (i !== st.active) { activate(i, false); } }
    });

    D.addEventListener('keydown', function (e) {
        var t = e.target;
        if (! eligible(t)) { return; }
        var printable = e.key.length === 1 && ! e.metaKey && ! e.ctrlKey && ! e.altKey;
        if (! st || st.select !== t) {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].indexOf(e.key) !== -1 || printable) {
                e.preventDefault();
                open(t, printable && e.key !== ' ' ? e.key : '');
            }
            return;
        }
        switch (e.key) {
            case 'ArrowDown': e.preventDefault(); move(1); break;
            case 'ArrowUp': e.preventDefault(); move(-1); break;
            case 'PageDown': e.preventDefault(); move(8); break;
            case 'PageUp': e.preventDefault(); move(-8); break;
            case 'Home': e.preventDefault(); activate(firstEnabled(0, 1), false); break;
            case 'End': e.preventDefault(); activate(firstEnabled(st.shown.length - 1, -1), false); break;
            case 'Enter': e.preventDefault(); choose(st.active); break;
            case 'Escape': e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); close(true); break;
            case 'Tab': close(false); break;
            case 'Backspace': if (st.searchable) { e.preventDefault(); st.q = st.q.slice(0, -1); draw(); activate(firstEnabled(0, 1), true); } break;
            default: if (printable) { e.preventDefault(); type(e.key); }
        }
    }, true);

    D.addEventListener('focusout', function (e) { if (st && e.target === st.select && ! st.panel.contains(e.relatedTarget)) { close(false); } }, true);
    D.addEventListener('change', function (e) { if (e.target && e.target.tagName === 'SELECT') { deco(e.target); } }, true);

    function boot() { D.querySelectorAll('.vys-rich > select').forEach(deco); }
    if (D.readyState === 'loading') { D.addEventListener('DOMContentLoaded', boot); } else { boot(); }
    setInterval(tick, 15000);
    // ⚠️ An x-model sets a select's value with no event (a dialog opening on a property, say), so a rich field's hint and clock
    //    would keep the old zone. The few rich fields on a page are checked here instead — cheap, and only on a change.
    var seen = new WeakMap();
    setInterval(function () {
        D.querySelectorAll('.vys-rich > select').forEach(function (s) { if (seen.get(s) !== s.selectedIndex) { seen.set(s, s.selectedIndex); deco(s); } });
    }, 400);

    window.VySelect = { open: open, close: close, refresh: boot };
})();
</script>
@endonce
