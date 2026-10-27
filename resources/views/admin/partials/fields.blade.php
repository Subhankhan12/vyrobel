{{-- ⚠️⚠️ ADMIN 05 (2026-09-26) — THE CONSOLE'S FIELD SYSTEM: one look for every input, dropdown, choice and switch in
     the platform console (the founder's ruling: every console form, not only the organisation screens).

     ⚠️ PROGRESSIVE, NEVER A REPLACEMENT. Every control stays the NATIVE element the form submits — the posted names are
     the behaviour contract the tests read. The script only ENHANCES:
       · <select data-searchable> — a searchable dropdown (type to filter, ↑/↓/Home/End, Enter, Esc), for the lists that
         grow without bound (organisation pickers) and the country list (data-flags adds each country's flag). The native
         select stays in the form, holds the value, and fires `change`; with no script it simply works as before.
       · <select multiple data-searchable> — the same dropdown as a CHECKLIST: each click ticks or unticks one row and the
         list stays open. A native multi-select drops every earlier choice on a plain click (only Ctrl/⌘-click adds),
         which is how a chosen audience quietly shrinks to one.
     Choice chips (.ad-choices) are plain radio buttons and the switch (.ad-switch) a plain checkbox — CSS only.
     ⚠️ No new dependency, no build step: the console has no Vite, no Alpine; the CSP allows this inline script. --}}
@once
<style>
    /* ── Fields ─────────────────────────────────────────────────────────────────────────────────────────────── */
    .ad-input {
        width: 100%; min-height: 46px; padding: 12px 14px; border-radius: 14px;
        border: 1px solid rgba(120, 86, 64, .16);
        background: linear-gradient(180deg, rgba(255, 255, 255, .95), rgba(255, 255, 255, .78));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .9), 0 1px 2px rgba(80, 50, 30, .06);
        color: var(--ad-ink); font-family: var(--ad-sans); font-size: 14px; font-weight: 500; line-height: 1.35;
        transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
    }
    .ad-input::placeholder { color: var(--ad-faint); font-weight: 400; }
    .ad-input:hover { border-color: rgba(232, 112, 58, .38); background: #fff; }
    .ad-input:focus { outline: none; border-color: var(--ad-accent); background: #fff;
        box-shadow: 0 0 0 4px rgba(232, 112, 58, .16), 0 8px 22px -12px rgba(232, 112, 58, .55); }
    .ad-input[aria-invalid="true"], .ad-input:user-invalid {
        border-color: rgba(185, 56, 21, .6); background: #FFF9F6; box-shadow: 0 0 0 4px rgba(185, 56, 21, .10); }
    .ad-input:disabled { opacity: .55; cursor: not-allowed; }
    textarea.ad-input { min-height: 108px; resize: vertical; line-height: 1.55; }
    select.ad-input:not([multiple]) { cursor: pointer; }
    /* the shell's chevron is a background-IMAGE, which replaces the gradient above — so every plain dropdown was see-through
       beside the white text fields in the same form. The surface comes back as a colour, under the chevron. */
    select.ad-input { background-color: rgba(255, 255, 255, .86); }
    select.ad-input[multiple] { background-image: none; padding: 8px; min-height: 150px; }
    select.ad-input[multiple] option { padding: 8px 10px; border-radius: 9px; margin: 1px 0; }
    select.ad-input[multiple] option:checked { background: rgba(232, 112, 58, .16) linear-gradient(0deg, rgba(232, 112, 58, .16), rgba(232, 112, 58, .16)); color: var(--ad-ink); }
    .ad-label { font-weight: 700; letter-spacing: .08em; }
    :is(.ad-input, .ad-choices, .ad-combo, .ad-switch-row) + .ad-label { margin-top: 16px; }
    :is(.ad-reason-hint, .ad-field-error) + .ad-label { margin-top: 14px; }
    .ad-field { margin-bottom: 16px; }
    .ad-field-error { margin: 6px 0 0; font-size: 12px; font-weight: 600; color: #9A3412; }
    /* the name to type in a type-to-confirm label, exactly as stored: the label is uppercase and the check is exact, so an
       uppercased name invites a typed one that is then refused */
    .ad-label .ad-confirm-name { text-transform: none; letter-spacing: 0; font-size: 12.5px; font-weight: 700; color: var(--ad-ink);
        padding: 1px 7px; margin: 0 2px; border-radius: 6px; background: rgba(255, 255, 255, .8); border: 1px solid rgba(120, 86, 64, .16); }

    /* ── Checkboxes and the switch ──────────────────────────────────────────────────────────────────────────── */
    input[type=checkbox] {
        appearance: none; -webkit-appearance: none; flex: none; width: 20px; height: 20px; margin: 0;
        border-radius: 6px; border: 1.5px solid rgba(120, 86, 64, .38); background: rgba(255, 255, 255, .92);
        display: inline-grid; place-content: center; cursor: pointer;
        transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    input[type=checkbox]::before {
        content: ""; width: 11px; height: 11px; background: #fff; transform: scale(0); transition: transform .14s ease;
        clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
    }
    input[type=checkbox]:hover { border-color: var(--ad-accent); }
    input[type=checkbox]:checked { background: var(--ad-accent); border-color: transparent; box-shadow: 0 4px 12px -4px rgba(232, 112, 58, .7); }
    input[type=checkbox]:checked::before { transform: scale(1); }
    input[type=checkbox].ad-switch { width: 42px; height: 24px; border-radius: 999px; border: 0; background: rgba(120, 86, 64, .22); place-content: center start; padding: 3px; }
    input[type=checkbox].ad-switch::before { width: 18px; height: 18px; border-radius: 50%; clip-path: none; transform: none; box-shadow: 0 1px 3px rgba(0, 0, 0, .25); transition: transform .18s ease; }
    input[type=checkbox].ad-switch:checked { background: var(--ad-accent); }
    input[type=checkbox].ad-switch:checked::before { transform: translateX(18px); }
    .ad-switch-row { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 14px;
        border: 1px solid rgba(120, 86, 64, .14); background: rgba(255, 255, 255, .6); cursor: pointer; }
    .ad-switch-row small { display: block; color: var(--ad-muted); font-size: 12px; font-weight: 400; margin-top: 2px; }

    /* ── Choice chips — radio buttons, for a handful of options ───────────────────────────────────────────── */
    .ad-choices { display: flex; flex-wrap: wrap; gap: 8px; border: 0; padding: 0; margin: 0; min-width: 0; }
    .ad-choices legend { padding: 0; }
    .ad-choice { position: relative; }
    .ad-choice input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .ad-choice span {
        display: inline-flex; align-items: center; gap: 7px; min-height: 40px; padding: 9px 16px; border-radius: 999px;
        border: 1px solid rgba(120, 86, 64, .18); background: rgba(255, 255, 255, .78); color: var(--ad-ink);
        font-size: 13px; font-weight: 600; cursor: pointer; user-select: none;
        transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease, color .15s ease;
    }
    .ad-choice span small { font-weight: 500; color: var(--ad-muted); }
    .ad-choice span:hover { border-color: rgba(232, 112, 58, .45); background: #fff; }
    .ad-choice input:checked + span { background: var(--ad-grad); color: #fff; border-color: transparent; box-shadow: 0 8px 18px -10px rgba(232, 112, 58, .9); }
    .ad-choice input:checked + span small { color: rgba(255, 255, 255, .85); }
    .ad-choice input:focus-visible + span { outline: 2px solid var(--ad-accent); outline-offset: 2px; }

    /* ── The searchable dropdown ───────────────────────────────────────────────────────────────────────────── */
    .ad-combo { position: relative; }
    .ad-combo-native { position: absolute !important; inset: auto !important; width: 1px !important; height: 1px !important; opacity: 0 !important; pointer-events: none !important; }
    /* the SAME chevron glyph every native select in the shell draws — recoloured on focus exactly as theirs is — which
       turns over while the list is open, so a searchable dropdown never looks like a different control beside a plain one */
    .ad-combo-trigger { display: flex; align-items: center; gap: 10px; text-align: left; cursor: pointer; padding-right: 38px; position: relative; }
    .ad-combo-trigger::after { content: ""; position: absolute; right: 13px; top: 50%; width: 14px; height: 14px; margin-top: -7px;
        background: no-repeat center / 14px 14px url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%235F5853' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); transition: transform .18s ease; }
    .ad-combo-trigger:focus::after, .ad-combo-trigger[aria-expanded="true"]::after { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23932A07' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); }
    .ad-combo-trigger[aria-expanded="true"]::after { transform: rotate(180deg); }
    .ad-combo-flag:empty { display: none; }
    .ad-combo-value { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ad-combo-value.is-placeholder { color: var(--ad-faint); font-weight: 400; }
    .ad-combo-flag { font-size: 18px; line-height: 1; }
    .ad-combo-panel {
        position: absolute; left: 0; right: 0; top: calc(100% + 6px); z-index: 60; padding: 8px; border-radius: 16px;
        /* fully opaque: at 97% the labels beneath read through the list as ghost text */
        border: 1px solid rgba(120, 86, 64, .14); background: #FFFDFB;
        box-shadow: 0 24px 48px -18px rgba(80, 45, 25, .38), 0 2px 6px rgba(80, 45, 25, .08);
        animation: ad-combo-in .14s ease-out;
    }
    @keyframes ad-combo-in { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
    .ad-combo .ad-combo-search { width: 100%; min-height: 40px; padding: 9px 12px 9px 36px; border-radius: 11px; border: 1px solid rgba(120, 86, 64, .16);
        font: 500 13.5px var(--ad-sans); color: var(--ad-ink); background: #FFFBF8 no-repeat 12px center / 15px
        url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239A8478' stroke-width='2.2' stroke-linecap='round'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='m20 20-3.5-3.5'/%3E%3C/svg%3E"); }
    .ad-combo .ad-combo-search:focus { outline: none; border-color: var(--ad-accent); box-shadow: 0 0 0 3px rgba(232, 112, 58, .14); }
    .ad-combo-list { list-style: none; margin: 8px 0 0; padding: 0; max-height: 264px; overflow-y: auto; }
    .ad-combo-option { display: flex; align-items: center; gap: 10px; padding: 9px 11px; border-radius: 10px; font-size: 13.5px; cursor: pointer; color: var(--ad-ink); }
    .ad-combo-option[aria-selected="true"] { font-weight: 700; }
    .ad-combo-option[aria-selected="true"]::after { content: ""; margin-left: auto; width: 12px; height: 12px; background: var(--ad-accent);
        clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%); }
    .ad-combo-option.is-active { background: rgba(232, 112, 58, .12); }
    /* a checklist (multi-select): a tick box leads each row instead of the trailing check */
    .ad-combo-list[aria-multiselectable="true"] .ad-combo-option[aria-selected="true"]::after { content: none; }
    .ad-combo-list[aria-multiselectable="true"] .ad-combo-option::before { content: ""; flex: none; width: 18px; height: 18px; border-radius: 6px;
        border: 1.5px solid rgba(120, 86, 64, .38); background: #fff no-repeat center / 12px 12px; transition: background-color .12s ease, border-color .12s ease; }
    .ad-combo-list[aria-multiselectable="true"] .ad-combo-option[aria-selected="true"]::before { border-color: transparent; background-color: var(--ad-accent);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23fff' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E"); }
    .ad-combo-count { flex: none; font-size: 11.5px; font-weight: 700; color: #fff; background: var(--ad-accent); border-radius: 999px; padding: 2px 8px; }
    .ad-combo-count:empty { display: none; }
    .ad-combo-empty { padding: 12px; font-size: 13px; color: var(--ad-muted); }
    /* ── The filter bar's own compact size, and the irreversible action ────────────────────────────────────── */
    /* in a filter bar the trigger takes the bar's own select look (the shell's `.ad-filters select`), and "Any …" is a real
       choice there, not a placeholder */
    .ad-filters .ad-combo { min-width: 220px; }
    .ad-filters .ad-combo-trigger { min-height: 0; padding: 9px 38px 9px 12px; border-radius: var(--ad-r-sm); border-color: var(--ad-border);
        background: rgba(255, 255, 255, .75); box-shadow: none; font-size: 13.5px; font-weight: 400; }
    .ad-filters .ad-combo-trigger:hover { background: rgba(255, 255, 255, .92); border-color: var(--ad-border-strong); }
    .ad-filters .ad-combo-trigger:focus { background: rgba(255, 255, 255, .92); border-color: var(--vy-accent-3); box-shadow: 0 0 0 3px var(--ad-accent-soft); }
    .ad-filters .ad-combo-value.is-placeholder { color: var(--ad-ink); }
    .ad-danger-zone { border: 1px solid rgba(180, 35, 24, .26); border-radius: 16px; padding: 18px;
        background: linear-gradient(180deg, rgba(255, 243, 239, .92), rgba(255, 250, 248, .72)); }
    .ad-btn--destroy { background: #B42318; color: #fff; border-color: transparent; box-shadow: 0 10px 22px -12px rgba(180, 35, 24, .8); }
    .ad-btn--destroy:hover { background: #912018; }
</style>
<script>
(() => {
    if (window.__vyAdminFields) return;
    window.__vyAdminFields = true;
    const flagOf = (code) => /^[A-Z]{2}$/.test(code) ? String.fromCodePoint(...[...code].map((c) => 127397 + c.charCodeAt(0))) : '';
    const fold = (s) => (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const painters = [];
    // the dropdown's own words, in the page's language; a count's plural form is worked out on the server (0…199, then by
    // the last two digits — what plural rules read) so no language's plural is guessed in a script
    const t = {
        choose: @js(__('admin/partials/fields.choose')),
        search: @js(__('admin/partials/fields.search_placeholder')),
        searchNamed: @js(__('admin/partials/fields.search_named')),
        noneChosen: @js(__('admin/partials/fields.none_chosen')),
        trigger: @js(__('admin/partials/fields.trigger_label')),
        chosen: @js(collect(range(0, 199))->map(fn ($n) => trans_choice('admin/partials/fields.chosen_count', $n, ['count' => ':count', 'list' => ':list']))->all()),
        nothing: @js(__('admin/partials/fields.nothing_matches')),
        chooseOption: @js(__('admin/partials/fields.choose_an_option')),
    };
    // one pass, so a value that itself contains ":name" is never replaced again
    const fill = (text, values) => text.replace(/:([a-z_]+)/g, (m, k) => (k in values ? values[k] : m));
    const chosenText = (n, list) => fill(t.chosen[n < 200 ? n : 100 + (n % 100)], { count: String(n), list: list });
    let uid = 0;

    // ⚠️ Everything is built OFF the page and swapped in as the LAST step — so an error anywhere leaves the native select
    //    exactly as it was (visible, working, posting), never an invisible field behind a half-built control.
    function enhance(select) {
        if (select.dataset.enhanced) return;
        const id = 'adc' + (++uid);
        const multi = select.multiple;
        const flags = select.hasAttribute('data-flags');
        const label = select.id ? document.querySelector('label[for="' + CSS.escape(select.id) + '"]') : null;
        const name = select.getAttribute('aria-label') || (label ? label.textContent.trim() : t.choose);

        const wrap = document.createElement('div');
        wrap.className = 'ad-combo';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.id = id + '-trigger';
        trigger.className = 'ad-input ad-combo-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-controls', id + '-list');
        const flag = document.createElement('span');
        flag.className = 'ad-combo-flag';
        flag.setAttribute('aria-hidden', 'true');
        const value = document.createElement('span');
        value.className = 'ad-combo-value';
        const count = document.createElement('span');
        count.className = 'ad-combo-count';
        count.setAttribute('aria-hidden', 'true');
        trigger.append(flag, value, count);

        const panel = document.createElement('div');
        panel.className = 'ad-combo-panel';
        panel.hidden = true;
        const search = document.createElement('input');
        search.type = 'search';
        search.className = 'ad-combo-search';
        search.placeholder = t.search;
        search.autocomplete = 'off';
        search.setAttribute('aria-label', fill(t.searchNamed, { name: name.toLowerCase() }));
        search.setAttribute('aria-controls', id + '-list');
        const list = document.createElement('ul');
        list.id = id + '-list';
        list.className = 'ad-combo-list';
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', name);
        if (multi) list.setAttribute('aria-multiselectable', 'true');
        panel.append(search, list);
        // a required field left empty says so HERE — the browser's own bubble would point at the hidden native select
        const error = document.createElement('p');
        error.className = 'ad-field-error';
        error.id = id + '-error';
        error.setAttribute('role', 'alert');
        error.hidden = true;
        wrap.append(trigger, panel, error);

        let active = -1;
        const options = () => [...select.options].filter((o) => !o.disabled);
        const visible = () => [...list.querySelectorAll('.ad-combo-option')];

        function paintTrigger() {
            trigger.disabled = select.disabled;
            if (!error.hidden && select.validity.valid) {
                error.hidden = true;
                trigger.removeAttribute('aria-invalid');
                trigger.removeAttribute('aria-describedby');
            }
            if (multi) {
                const chosen = [...select.selectedOptions].map((c) => c.textContent.trim());
                value.textContent = chosen.length ? chosen.join(', ') : (select.dataset.placeholder || t.noneChosen);
                value.classList.toggle('is-placeholder', !chosen.length);
                count.textContent = chosen.length > 1 ? String(chosen.length) : '';
                trigger.setAttribute('aria-label', fill(t.trigger, { name: name, value: chosen.length ? chosenText(chosen.length, chosen.join(', ')) : value.textContent }));
                return;
            }
            const o = select.options[select.selectedIndex];
            const empty = !o || o.value === '';
            value.textContent = o ? o.textContent.trim() : '';
            value.classList.toggle('is-placeholder', empty);
            trigger.setAttribute('aria-label', fill(t.trigger, { name: name, value: value.textContent }));
            flag.textContent = flags && o ? flagOf(o.value) : '';
        }
        function render(filter) {
            list.textContent = '';
            const q = fold(filter);
            options().filter((o) => !q || fold(o.textContent).includes(q) || fold(o.value).includes(q)).forEach((o) => {
                const li = document.createElement('li');
                li.className = 'ad-combo-option';
                li.id = id + '-o' + o.index;
                li.setAttribute('role', 'option');
                li.dataset.index = String(o.index);
                li.setAttribute('aria-selected', o.selected ? 'true' : 'false');
                if (flags && flagOf(o.value)) {
                    const f = document.createElement('span');
                    f.className = 'ad-combo-flag';
                    f.setAttribute('aria-hidden', 'true');
                    f.textContent = flagOf(o.value);
                    li.appendChild(f);
                }
                li.appendChild(document.createTextNode(o.textContent.trim()));
                // chosen on CLICK (what every pointer and assistive technology sends); mousedown only keeps the focus
                li.addEventListener('mousedown', (e) => e.preventDefault());
                li.addEventListener('click', () => choose(o.index));
                list.appendChild(li);
            });
            if (!list.children.length) {
                const none = document.createElement('li');
                none.className = 'ad-combo-empty';
                none.textContent = fill(t.nothing, { filter: filter });
                list.appendChild(none);
            }
            const items = visible();
            const sel = items.findIndex((li) => li.getAttribute('aria-selected') === 'true');
            move(sel >= 0 ? sel : (items.length ? 0 : -1));
        }
        function move(i) {
            const items = visible();
            items.forEach((li) => li.classList.remove('is-active'));
            active = Math.max(-1, Math.min(i, items.length - 1));
            if (active >= 0) {
                items[active].classList.add('is-active');
                items[active].scrollIntoView({ block: 'nearest' });
                search.setAttribute('aria-activedescendant', items[active].id);
            } else {
                search.removeAttribute('aria-activedescendant');
            }
        }
        function open() {
            if (!panel.hidden || select.disabled) return;
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            search.value = '';
            render('');
            search.focus();
        }
        function close(refocus) {
            if (panel.hidden) return;
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            if (refocus) trigger.focus();
        }
        function choose(index) {
            if (multi) {   // a checklist toggles one row and stays open
                const o = select.options[index];
                o.selected = !o.selected;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                const li = document.getElementById(id + '-o' + index);
                if (li) li.setAttribute('aria-selected', o.selected ? 'true' : 'false');
                paintTrigger();
                return;
            }
            if (select.selectedIndex !== index) {
                select.selectedIndex = index;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            paintTrigger();
            close(true);
        }

        trigger.addEventListener('click', () => (panel.hidden ? open() : close(true)));
        trigger.addEventListener('keydown', (e) => {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) { e.preventDefault(); open(); }
        });
        search.addEventListener('input', () => render(search.value));
        search.addEventListener('keydown', (e) => {
            const items = visible();
            if (e.key === 'ArrowDown') { e.preventDefault(); move(active + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); move(active - 1); }
            else if (e.key === 'Home') { e.preventDefault(); move(0); }
            else if (e.key === 'End') { e.preventDefault(); move(items.length - 1); }
            else if (e.key === 'Enter') { e.preventDefault(); if (items[active]) choose(Number(items[active].dataset.index)); }
            else if (e.key === 'Escape') { e.preventDefault(); close(true); }
            else if (e.key === 'Tab') { close(false); }
        });
        document.addEventListener('mousedown', (e) => { if (!wrap.contains(e.target)) close(false); });
        select.addEventListener('change', paintTrigger);
        select.addEventListener('invalid', (e) => {
            e.preventDefault();
            error.textContent = select.validationMessage || t.chooseOption;
            error.hidden = false;
            trigger.setAttribute('aria-invalid', 'true');
            trigger.setAttribute('aria-describedby', error.id);
            const first = select.form ? select.form.querySelector('input:invalid, select:invalid, textarea:invalid') : select;
            if (first === select) trigger.focus();
        });
        if (select.form) select.form.addEventListener('reset', () => setTimeout(paintTrigger));

        // ── the swap: the native select moves inside, hidden, and stays the field the form posts ──
        const home = select.parentNode;
        try {
            home.insertBefore(wrap, select);
            wrap.insertBefore(select, trigger);
            select.classList.add('ad-combo-native');
            select.tabIndex = -1;
            select.setAttribute('aria-hidden', 'true');
            if (label) label.htmlFor = trigger.id;
            paintTrigger();
        } catch (e) {
            if (wrap.parentNode) { wrap.parentNode.insertBefore(select, wrap); wrap.remove(); }
            select.classList.remove('ad-combo-native');
            select.removeAttribute('tabindex');
            select.removeAttribute('aria-hidden');
            if (label) label.htmlFor = select.id;
            throw e;
        }
        select.dataset.enhanced = '1';
        painters.push(paintTrigger);
    }

    const init = () => document.querySelectorAll('select[data-searchable]').forEach((select) => {
        try { enhance(select); } catch (e) { console.error('A searchable field could not be built — the plain one is kept.', e); }
    });
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
    // back/forward cache: the browser may restore a different value than the one painted
    window.addEventListener('pageshow', (e) => { if (e.persisted) painters.forEach((paint) => paint()); });
})();
</script>
@endonce
