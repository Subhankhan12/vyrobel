{{-- The sidebar stays where it was — ONE definition, placed right after each shell's sidebar: the operator back office,
     the owner portal and the admin console (2026-10-09, his report: "if I open the owners or the reports … upon the page
     refresh the sidebar is going up again … it should stay there, it shouldn't be moving").

     ⚠️ WHY IT RESET: each sidebar is its own scroll box (sticky, 100vh, overflow-y: auto — in the owner portal the menu inside
     it, `.vy-nav`, so `for` names the box that actually scrolls) and every menu link loads a whole
     new page, so the new sidebar always started at the top — an item low in the list (Owners, Reports, Settings) was off
     screen the moment it opened. A browser restores the WINDOW's scroll on a reload, never an inner box's on a new page.

     It runs inline, straight after the sidebar is parsed and before the page is first drawn, so the position is back
     before anything shows — no jump. Per browser tab (sessionStorage), per realm (`key`); every storage call guarded, so a
     private window or blocked storage falls back to rule 2 alone. On a phone the sidebar is not a scroll box and this does
     nothing.

     RAW-BLADE RULE: every directive here follows a non-word character. --}}
@props(['for', 'key'])
<script>
    (function () {
        var side = document.querySelector(@js($for));
        if (!side) { return; }
        var KEY = 'vy.sidebar.' + @js($key);
        var store = null;
        try { store = window.sessionStorage; } catch (e) { store = null; }

        // ⚠️ No scroll anchoring in the sidebar. The Messages badge appears after load and the box grows a pixel; Chrome then
        //    shifts the position to compensate, that shift is remembered, and the next page repeats it — half a pixel a page,
        //    measured in the walk (520 → 520.5 → 521). With anchoring off the position stays exactly as it was left.
        side.style.overflowAnchor = 'none';

        // 1. Put it back where it was on the last page of this tab.
        var saved = null;
        try { saved = store ? store.getItem(KEY) : null; } catch (e) { saved = null; }
        var top = saved === null ? NaN : parseFloat(saved);
        if (top > 0) { side.scrollTop = top; }

        // 2. The page you are on is always in view: a link from an email or a bookmark has no remembered position, and a
        //    remembered one can leave the current item off screen in a shorter window than last time.
        var current = side.querySelector('[aria-current="page"]');
        if (current && side.scrollHeight > side.clientHeight) {
            var c = current.getBoundingClientRect();
            var s = side.getBoundingClientRect();
            if (c.top < s.top || c.bottom > s.bottom) {
                side.scrollTop += (c.top - s.top) - Math.max(0, (side.clientHeight - c.height) / 2);
            }
        }

        // 3. Remember it: on every scroll (one write per frame), on a click in the sidebar, and as the page is left.
        var pending = false;
        var remember = function () {
            pending = false;
            // ⚠️ The exact value, never rounded: on a high-density screen the box rests on half pixels, and rounding
            //    520.5 up to 521 crept the sidebar down a pixel on every page (measured in the walk, 2026-10-09).
            try { if (store) { store.setItem(KEY, String(side.scrollTop)); } } catch (e) {}
        };
        side.addEventListener('scroll', function () {
            if (!pending) { pending = true; window.requestAnimationFrame(remember); }
        }, { passive: true });
        side.addEventListener('click', remember, true);
        window.addEventListener('pagehide', remember);
    })();
</script>
