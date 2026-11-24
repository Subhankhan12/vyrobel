{{-- ⚠️⚠️ VY-28 (audit of 2026-11-24, seen live) — THE PAGES' SMALL BEHAVIOURS, WITHOUT ONE INLINE EVENT HANDLER.

     The content security policy allowed inline scripts ('unsafe-inline') because 72 controls carried an `onclick` / `onchange` /
     `onsubmit` / `onmouseover` attribute, and an attribute handler can carry no nonce. Each is now a `data-` attribute, and this ONE
     delegated script (included once, from the select engine every shell already loads) does what the attribute says. With no inline
     handler left, the policy names a per-request NONCE instead of 'unsafe-inline' (SecurityHeaders), and every <script @nonce> carries it.
     ⚠️ 'unsafe-eval' stays: Alpine (bundled by Livewire) evaluates its x- expressions with `new Function`; dropping it needs Alpine's
        CSP build and a rewrite of every complex expression — said in SecurityHeaders, not hidden.

     The attributes (every one is read from the element, never evaluated):
       data-autosubmit            a select / input that submits its form when it changes ("request" → requestSubmit, so its submit
                                  handlers run; anything else → form.submit(), exactly the old `this.form.submit()`)
       data-confirm-text="…"      a form that asks before it submits; Cancel stops it before the double-submit guard can lock it
       data-nav-base="?x=…&y="    a select that navigates to base + its value (an empty value does nothing)
       data-lang-param            a select that sets ?lang= to its value and reloads the page in that language
       data-href="…"              a row that opens a page when clicked (a click on a link or a button inside it is that control's own)
       data-scroll-to="id"        a control that scrolls the element with that id into view
       data-stepper="up|down"     a −/+ button beside a number field (the field is its next/previous sibling)
       data-reveal-id="id"        a link that shows the element with that id and hides itself
       data-toggle-password="id"  a button that shows/hides that password field (data-show-text / data-hide-text are its labels)
       data-vy-click / data-vy-change / data-vy-input="fn"
                                  calls the page's own function window[fn] with data-vy-arg (a whole number becomes a number), or the
                                  element itself when there is no argument --}}
@once
<script @nonce>
    (function () {
        if (window.__vyBehaviours) return;
        window.__vyBehaviours = true;

        function call(el, attr) {
            var fn = el.getAttribute(attr);
            if (!fn || typeof window[fn] !== 'function') return;
            var arg = el.getAttribute('data-vy-arg');
            window[fn](arg === null ? el : (/^-?\d+$/.test(arg) ? parseInt(arg, 10) : arg));
        }

        // Cancel on a confirm stops the submit HERE, in the capture phase — before the double-submit guard (bubble phase) sees it.
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            var text = form.getAttribute('data-confirm-text');
            if (text !== null && !window.confirm(text)) { e.preventDefault(); e.stopImmediatePropagation(); }
        }, true);

        document.addEventListener('change', function (e) {
            var el = e.target;
            if (!(el instanceof Element)) return;
            if (el.hasAttribute('data-autosubmit') && el.form) {
                if (el.getAttribute('data-autosubmit') === 'request' && el.form.requestSubmit) { el.form.requestSubmit(); } else { el.form.submit(); }
                return;
            }
            if (el.hasAttribute('data-nav-base')) {
                if (el.value) { window.location.href = el.getAttribute('data-nav-base') + encodeURIComponent(el.value); }
                return;
            }
            if (el.hasAttribute('data-lang-param')) {
                if (el.value) { var u = new URL(window.location.href); u.searchParams.set('lang', el.value); window.location.href = u.toString(); }
                return;
            }
            if (el.hasAttribute('data-vy-change')) call(el, 'data-vy-change');
        });

        document.addEventListener('input', function (e) {
            var el = e.target;
            if (el instanceof Element && el.hasAttribute('data-vy-input')) call(el, 'data-vy-input');
        });

        document.addEventListener('click', function (e) {
            var t = e.target;
            if (!(t instanceof Element)) return;

            var c = t.closest('[data-vy-click]');
            if (c) { call(c, 'data-vy-click'); return; }

            var step = t.closest('[data-stepper]');
            if (step) {
                var up = step.getAttribute('data-stepper') === 'up';
                var field = up ? step.previousElementSibling : step.nextElementSibling;
                if (field && typeof field.stepUp === 'function') { if (up) { field.stepUp(); } else { field.stepDown(); } }
                return;
            }

            var scroll = t.closest('[data-scroll-to]');
            if (scroll) {
                var target = document.getElementById(scroll.getAttribute('data-scroll-to'));
                if (target) target.scrollIntoView({ behavior: 'smooth' });
                return;
            }

            var reveal = t.closest('[data-reveal-id]');
            if (reveal) {
                e.preventDefault();
                var shown = document.getElementById(reveal.getAttribute('data-reveal-id'));
                if (shown) shown.style.display = 'block';
                reveal.style.display = 'none';
                return;
            }

            var pw = t.closest('[data-toggle-password]');
            if (pw) {
                var input = document.getElementById(pw.getAttribute('data-toggle-password'));
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                var label = pw.getAttribute(show ? 'data-hide-text' : 'data-show-text');
                if (label !== null) pw.textContent = label;
                return;
            }

            var row = t.closest('[data-href]');
            if (row && !t.closest(['a', 'button', 'input', 'select', 'textarea', 'label'].join(','))) {
                window.location.href = row.getAttribute('data-href');
            }
        });
    })();
</script>
@endonce
