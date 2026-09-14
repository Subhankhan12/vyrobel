/*
 * Vyrobel marketing — progressive enhancement.
 *
 * ⚠️⚠️ THE ONE RULE THIS FILE EXISTS TO OBEY: THE PAGE SHIPS VISIBLE, AND THIS SCRIPT DOES THE HIDING.
 *
 * The design hid inactive panes with an inline `display: none`. If a script were the thing that REVEALED
 * them, then a blocked bundle, a 404 after a deploy, a slow network, a reader with JS off, or any crawler
 * would see seven-eighths of /product and two-thirds of the home hero simply missing. The converter now
 * strips those inline hides at build time (`_unhide` in tools/marketing/convert.py), so the served HTML
 * contains every pane. This script collapses the inactive ones AFTER it has loaded.
 * ⚠️ Consequence, stated deliberately: if this file never runs, the pages degrade to a long scrolling
 * document with all content present and indexable. That is a worse LAYOUT, not a missing PRODUCT.
 *
 * ⚠️ NO FRAMEWORK, AND NO NEW DEPENDENCY. The operator and owner shells get Alpine only because Livewire
 * bundles it behind `@livewireScripts`; the marketing shell loads no JS at all and has no Livewire
 * components. Pulling the Livewire runtime (or adding Alpine, ~15KB gz) onto a static marketing site to
 * drive tab groups would be a large amount of machinery for a small amount of behaviour.
 * ⚠️ AND IT IS SERVED AS A PLAIN STATIC FILE, NOT THROUGH @vite, ON PURPOSE: `@vite` throws when the
 * manifest is missing, which would turn a forgotten `npm run build` into a 500 on the marketing site —
 * strictly worse than the degraded-but-readable failure this whole file is designed around.
 *
 * ⚠️ ACCESSIBILITY IS BUILT IN, NOT ADDED LATER. Wiring clicks without roles and keys would only convert
 * dead controls into inaccessible ones. Every group below gets: a correct `aria-selected`, roving
 * `tabindex`, arrow/Home/End key navigation, and `hidden` on collapsed panels so assistive technology and
 * find-in-page agree with what is painted.
 */
(function () {
    'use strict';

    /** Collapse a panel in a way that hides it from EVERYTHING — paint, a11y tree and find-in-page alike. */
    function collapse(el, isHidden) {
        if (!el) return;
        el.hidden = isHidden;
        el.style.display = isHidden ? 'none' : '';
    }

    /**
     * Wire one tab group.
     *
     * @param {string} btnSel   selector for the tabs, in DOM order
     * @param {string[]} panelSels  selector templates for the panes this group controls; `%s` is the value
     * @param {string} attr     the data attribute carrying each tab's value
     */
    function tabGroup(btnSel, panelSels, attr, stateAttr) {
        stateAttr = stateAttr || 'aria-selected';
        var tabs = Array.prototype.slice.call(document.querySelectorAll(btnSel));
        if (tabs.length < 2) return;   // nothing to switch between — leave the page exactly as served

        function panelsFor(value) {
            var out = [];
            panelSels.forEach(function (tpl) {
                out = out.concat(Array.prototype.slice.call(
                    document.querySelectorAll(tpl.replace('%s', value))
                ));
            });
            return out;
        }

        function select(value, moveFocus) {
            tabs.forEach(function (tab) {
                var v = tab.getAttribute(attr);
                var on = v === value;
                tab.setAttribute(stateAttr, on ? 'true' : 'false');
                tab.tabIndex = on ? 0 : -1;          // roving tabindex: one stop for the whole group
                panelsFor(v).forEach(function (p) { collapse(p, !on); });
                if (on && moveFocus) tab.focus();
            });
        }

        // The served page marks the default tab; honour it rather than assuming the first.
        var initial = (tabs.filter(function (t) { return t.getAttribute(stateAttr) === 'true'; })[0]
            || tabs[0]).getAttribute(attr);

        tabs.forEach(function (tab, i) {
            tab.addEventListener('click', function () { select(tab.getAttribute(attr), false); });
            tab.addEventListener('keydown', function (e) {
                var k = e.key, next = null;
                if (k === 'ArrowRight' || k === 'ArrowDown') next = tabs[(i + 1) % tabs.length];
                else if (k === 'ArrowLeft' || k === 'ArrowUp') next = tabs[(i - 1 + tabs.length) % tabs.length];
                else if (k === 'Home') next = tabs[0];
                else if (k === 'End') next = tabs[tabs.length - 1];
                else return;
                e.preventDefault();
                select(next.getAttribute(attr), true);
            });
        });

        select(initial, false);   // ⚠️ the FIRST hiding this page ever does happens here, after load
    }

    /**
     * ⚠️⚠️ A FILTER IS NOT A TABLIST — a deliberately DIFFERENT pattern, not the tab one copied across.
     *
     * These chips do not select one of several panes; they narrow a single list. So they are toggle
     * BUTTONS carrying `aria-pressed`, never `role="tab"`/`aria-selected`. The design already had this
     * right (`data-modchip` ships with `aria-pressed`, and "all" pressed), so this only adds the behaviour.
     *
     * ⚠️ AND "SHOW ALL" IS THE CORRECT NO-JS STATE. Every module card ships visible and unfiltered, so a
     * reader without JS sees the complete catalogue — more than a filtered view, never less. Nothing here
     * reveals; it only narrows, and only after a click.
     */
    function chipFilter(chipSel, itemSel, attr, emptySel) {
        var chips = Array.prototype.slice.call(document.querySelectorAll(chipSel));
        var items = Array.prototype.slice.call(document.querySelectorAll(itemSel));
        if (!chips.length || !items.length) return;
        var empty = emptySel ? document.querySelector(emptySel) : null;

        function apply(value) {
            var shown = 0;
            items.forEach(function (item) {
                var on = value === 'all' || item.getAttribute(attr) === value;
                collapse(item, !on);
                if (on) shown++;
            });
            chips.forEach(function (c) {
                c.setAttribute('aria-pressed', c.getAttribute(chipAttr(chipSel)) === value ? 'true' : 'false');
            });
            // ⚠️ The empty state is the ONE thing JS may reveal — with no JS no filter can be applied, so
            //    "nothing matched" is never a true state of the served page.
            if (empty) collapse(empty, shown !== 0);
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () { apply(chip.getAttribute(chipAttr(chipSel))); });
        });
    }

    function chipAttr(sel) { return sel.replace(/^\[|\]$/g, ''); }

    function init() {
        // /  — the three audience lenses. Intent recovered from the masters' stripped prototype handlers
        //      (`lensOperator` / `lensOwner` / `lensGuest`), read for INTENT ONLY.
        tabGroup('[data-lensbtn]', ['[data-lenscopy="%s"]', '[data-lensmock="%s"]'], 'data-lensbtn');

        // /  — the nine-hour "day in the life" rail (07:00…23:00). ⚠️ SHIPPED WITH NINE `role="tab"`
        //      BUTTONS AND NO BINDING AT ALL: the attributes came straight through from the master and
        //      nobody wired them, so clicking any hour did nothing and eight of the nine panels carried an
        //      inline `display:none` that no script ever removed — served, paid for in bytes, unreadable by
        //      anyone. Found by clicking it in a browser (BROWSER-AUDIT-2026-09-01 F-1); no served-HTML test
        //      could have seen it. ⚠️ THE SAME `tabGroup` as the lenses, not a second tab pattern (§N.8f) —
        //      `data-hourbtn` values are 0–8 and match `data-hourpanel` exactly.
        tabGroup('[data-hourbtn]', ['[data-hourpanel="%s"]'], 'data-hourbtn');

        // /product — the eight-stage stepper (`stage0`…`stage7`).
        tabGroup('[data-stagebtn]', ['[data-stagepanel="%s"]'], 'data-stagebtn');

        // /modules — the audience filter chips (`fall` / `foperator` / `fowner` / `fguest` in the masters).
        chipFilter('[data-modchip]', '[data-module]', 'data-module', '[data-modempty]');

        // /pricing — the BILLING-PERIOD switcher. ⚠️ A RADIOGROUP, NOT A TABLIST: these two controls do
        //      not reveal different panels of content, they choose which PRESENTATION of the same two
        //      plans is shown, which is a choice between mutually exclusive options. So the state
        //      attribute is `aria-checked`, and `tabGroup` takes it as a PARAMETER rather than this file
        //      growing a near-identical second implementation (§N.8f) — the keyboard contract (roving
        //      tabindex, arrow/Home/End) is identical either way.
        // ⚠️⚠️ AND THE PAGE SHIPS BOTH PRICES. Everything below only COLLAPSES one: with this file blocked
        //      or absent, /pricing shows the monthly AND the annual figure for each tier, each with its
        //      own "Billed monthly" / "Billed yearly" line. A price a visitor cannot see would be worse
        //      than a panel they cannot see, so no price depends on this script running.
        tabGroup('[data-cyclebtn]', ['[data-cycleprice="%s"]'], 'data-cyclebtn', 'aria-checked');

        // /example-scenarios/* — "Print this story" is a BROWSER CALL, not a product claim: it asks the
        // browser to print the page it is already on. Nothing is promised and nothing is fetched.
        Array.prototype.forEach.call(document.querySelectorAll('[data-printbtn]'), function (b) {
            b.addEventListener('click', function () { window.print(); });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
