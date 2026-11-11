{{-- The operator LIST behaviour (2026-09-29) — ONE definition, shared by the Guests and Maintenance lists so the two
     cannot drift apart: the search that follows your typing, the filter/sort/page links that change the view in place,
     the one row menu, and select mode.

     ⚠️ EVERY LINK AND FORM ON THOSE PAGES WORKS WITHOUT IT. This only keeps the page in place: it fetches the SAME GET URL
     a click would load and swaps the two marked regions (`data-gl-region="chips"` and `"results"`), so every change is
     still an ordinary request through every middleware (invariant #20). Anything unexpected — a redirect elsewhere, an
     error, a missing region — falls back to loading the page normally.

     A page builds its Alpine component on it —
         Alpine.data('name', (boot) => Object.assign(vyListBehaviour(boot, { empty: 'No … in this view' }), { …its own
         state and actions…, start() { … } }))
     — and `start()` runs at the end of init(). What it reads from the page: `.vy-gl-menu` (the one row menu) and its
     `.vy-gl-menu-item`s, `[data-gl-id]` (a row), `a[data-gl-key]` (a control link, and where focus goes back after a
     change), `[data-gl-summary]` (the footer line, read out after a change) and `.vy-gl-search-in` (the search box). --}}
<script>
    window.vyListBehaviour = window.vyListBehaviour || function (boot, options) {
        const opts = Object.assign({ empty: @js(__('partials/operator-list-script.empty_view')) }, options || {});
        // ⚠️ DOM handles live HERE, captured once in init(), not behind `this.$root` / `this.$refs`. Those magics resolve
        //    from whichever element is evaluating, and a swap detaches the old rows while their bindings can still run once
        //    more — a detached element has no root, and the page threw (🖥 measured on the Guests page's first swap).
        let root = null;
        let menuEl = null;
        let trigger = null;

        return {
            selectMode: false, sel: {},
            urls: boot.urls,
            menu: { open: false, x: 0, y: 0, up: false, row: null }, menuFor: '',
            tip: false, q: boot.q, loading: false, ctrl: null, live: '',

            init() {
                root = this.$el;
                menuEl = root.querySelector('.vy-gl-menu');
                // The page's own links (filters, sorting, page size, pages) — ordinary links, made smooth here.
                root.addEventListener('click', (e) => {
                    const a = e.target.closest('a[data-gl-key]');
                    if (! a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                    e.preventDefault();
                    this.visit(a.href, 'push', a.dataset.glKey);
                });
                window.addEventListener('popstate', () => this.visit(location.href, 'none', null));
                // The open menu follows its button while anything scrolls — the page, or the table sideways on a phone
                // (that scroll does not bubble, hence `capture`). Closing on scroll instead shut it the moment it opened
                // whenever a smooth or coasting scroll was still settling (🖥 measured at 390 px).
                window.addEventListener('scroll', () => this.placeMenu(), { passive: true, capture: true });
                let frame = 0;
                window.addEventListener('resize', () => {
                    this.placeMenu();
                    cancelAnimationFrame(frame);
                    frame = requestAnimationFrame(() => this.fit());
                });
                this.fit();
                if (document.fonts) document.fonts.ready.then(() => this.fit());
                if (typeof this.start === 'function') this.start();
            },

            find(selector) { return root ? root.querySelector(selector) : null; },
            // The table's frame scrolls sideways unless the table fits it; only a table that fits gets the sticky header
            // (`is-roomy`, in the layout). Measured on load, after every swap and on resize — a table's width depends on
            // what is in it, so a window breakpoint cannot know.
            fit() {
                if (! root) return;
                root.querySelectorAll('.vy-gl-scroll').forEach((box) => {
                    const table = box.querySelector('table');
                    box.classList.toggle('is-roomy', !! table && table.offsetWidth <= box.clientWidth + 1);
                });
            },
            link(template, id) { return template.replace('__ID__', encodeURIComponent(id || '')); },

            // ── selection
            ids() { return Object.keys(this.sel).filter((k) => this.sel[k]); },
            count() { return this.ids().length; },
            clear() { this.sel = {}; },
            toggleMode() { this.selectMode = ! this.selectMode; if (! this.selectMode) this.clear(); },
            pageIds() { return root ? Array.from(root.querySelectorAll('[data-gl-id]'), (el) => el.dataset.glId) : []; },
            allOnPage() { const ids = this.pageIds(); return ids.length !== 0 && ids.every((id) => this.sel[id]); },
            someOnPage() { const ids = this.pageIds(); return ids.some((id) => this.sel[id]) && ! this.allOnPage(); },
            selectPage(on) { const next = { ...this.sel }; this.pageIds().forEach((id) => { next[id] = on; }); this.sel = next; },

            // ── the row menu
            openMenu(event, row) {
                const button = event.currentTarget;
                if (! row) return;
                // ⚠️ D-GL-FLAKE (2026-11-11, his "do all"; seen ONCE, not reproduced — a HARDENING of the one race I could find, not a proven
                // fix): a ⋯ click while a search/filter/page response is on its way opened a menu that the arriving response then closed
                // (`closeMenu(false)` before the rows are swapped), leaving the page scrolled with the menu shut. The rows are about to be
                // replaced; there is nothing to open a menu on until they are.
                if (this.loading) return;
                if (this.menu.open && this.menuFor === row.id) { this.closeMenu(true); return; }
                trigger = button;
                this.menuFor = row.id;
                this.menu = { open: true, row, up: false, x: 0, y: 0 };
                this.placeMenu();
                // Once it is on screen: placed again at its real height (a menu's length depends on the row), then focused.
                this.$nextTick(() => {
                    this.placeMenu();
                    // the first item THIS row shows — an item hidden for this row or this role cannot take focus
                    const first = Array.from(menuEl.querySelectorAll('.vy-gl-menu-item')).find((el) => el.offsetParent !== null);
                    if (first) first.focus({ preventScroll: true });
                });
            },
            // Beside its button: below it, or above when there is no room below; closed once the button has left the screen.
            placeMenu() {
                if (! this.menu.open) return;
                if (! trigger || ! trigger.isConnected) { this.closeMenu(false); return; }
                const box = trigger.getBoundingClientRect();
                if (box.bottom < 0 || box.top > window.innerHeight) { this.closeMenu(false); return; }
                const width = menuEl.offsetWidth || 224;
                const room = (menuEl.offsetHeight || 164) + 12;
                const up = box.bottom + room > window.innerHeight && box.top > room;
                this.menu = { ...this.menu, up,
                    x: Math.max(8, Math.min(box.right - width, window.innerWidth - width - 8)),
                    y: up ? box.top - 6 : box.bottom + 6 };
            },
            closeMenu(refocus) {
                if (! this.menu.open) return;
                this.menu = { ...this.menu, open: false };
                this.menuFor = '';
                if (refocus && trigger && trigger.isConnected) trigger.focus();
            },
            leaveMenu(event) { if (! menuEl.contains(event.relatedTarget)) this.closeMenu(false); },

            // ── search and the smooth links
            search() {
                const url = new URL(location.href);
                const q = (this.q || '').trim();
                if ((url.searchParams.get('q') || '') === q) return;
                if (q === '') url.searchParams.delete('q'); else url.searchParams.set('q', q);
                url.searchParams.delete('page');
                this.visit(url.toString(), 'replace', null);
            },
            clearSearch() { this.q = ''; this.search(); const box = this.find('.vy-gl-search-in'); if (box) box.focus(); },
            async visit(url, mode, key) {
                if (this.ctrl) this.ctrl.abort();
                const ctrl = new AbortController();
                this.ctrl = ctrl;
                this.loading = true;
                try {
                    const response = await fetch(url, { signal: ctrl.signal, credentials: 'same-origin', headers: { Accept: 'text/html' } });
                    const html = await response.text();
                    if (ctrl !== this.ctrl) return;
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const parts = ['chips', 'results'].map((name) => [
                        root.querySelector('[data-gl-region="' + name + '"]'),
                        doc.querySelector('[data-gl-region="' + name + '"]'),
                    ]);
                    if (! response.ok || new URL(response.url).pathname !== location.pathname || parts.some(([here, there]) => ! here || ! there)) {
                        window.location.assign(url);
                        return;
                    }
                    this.closeMenu(false);
                    parts.forEach(([here, there]) => {
                        // The page-load entrance is for the first view; a filter or a page change just appears.
                        there.querySelectorAll('.vy-fade').forEach((node) => node.classList.remove('vy-fade'));
                        here.replaceWith(document.importNode(there, true));
                    });
                    // Only AFTER the old rows are gone: a selection is of the rows you could see.
                    this.sel = {};
                    this.fit();
                    if (mode === 'push') history.pushState(null, '', url);
                    if (mode === 'replace') history.replaceState(null, '', url);
                    if (mode !== 'replace') this.q = new URL(url, location.href).searchParams.get('q') || '';
                    const summary = root.querySelector('[data-gl-summary]');
                    this.live = summary ? summary.textContent.trim() : opts.empty;
                    if (key) {
                        this.$nextTick(() => {
                            const target = root.querySelector('[data-gl-key="' + key + '"]') || root.querySelector('[data-gl-summary]');
                            if (target) target.focus({ preventScroll: true });
                            // A new page, or a new page size, starts at its first row — the pager sits below the table.
                            const results = root.querySelector('[data-gl-region="results"]');
                            if ((key.startsWith('page-') || key.startsWith('per-')) && results && results.getBoundingClientRect().top < 0) {
                                results.scrollIntoView({ block: 'start' });
                            }
                        });
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') window.location.assign(url);
                } finally {
                    if (ctrl === this.ctrl) { this.loading = false; this.ctrl = null; }
                }
            },
        };
    };
</script>
