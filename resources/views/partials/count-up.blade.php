{{-- Polish Batch 5 — the ONE shared KPI/hero count-up (consolidates the 4 duplicated owner @script blocks).
     Animates each KPI/hero number from 0 → its rendered value over 800ms (cubic ease-out, prototype-matched),
     then LANDS ON THE EXACT server-rendered content (captured up front and restored on the final frame) — so the
     figure is authoritative, never an approximation (#17 money lands on the cent; #39 no fabricated value).

     Parse-from-text: it reads the number out of the already-rendered string (handling €, %, thousands, decimals,
     a leading sign), animates a cosmetic in-between, and restores the original innerHTML at the end. It targets the
     shared KPI/hero value classes, so no per-page markup change is needed; it SKIPS deferred values (`.is-deferred`),
     non-numeric text ("Nothing due", "—"), and zeros. 🔒 GRACEFUL: with JS off or reduced-motion, the real number is
     already in the DOM — nothing is hidden behind the animation. Presentation-only. --}}
@once
<script>
    (function () {
        if (window.__vyCountUp) return;
        window.__vyCountUp = true;
        var DUR = 800;
        var SELECTOR = '.vy-dk-val, .vy-kpi-val, .vy-hero-figure, .vy-dark-figure, [data-countup]';

        function animate(el) {
            if (el.dataset.cuDone || el.classList.contains('is-deferred')) return;
            var text = (el.textContent || '').trim();
            var m = text.match(/^(\D*?)(-?[−]?[0-9][0-9,]*(?:\.[0-9]+)?)(\D*)$/);
            if (!m) return;                                     // no simple number → leave the real text untouched
            var prefix = m[1], numStr = m[2], suffix = m[3];
            var target = parseFloat(numStr.replace(/,/g, ''));
            if (isNaN(target) || target === 0) return;          // nothing to animate to
            el.dataset.cuDone = '1';
            var original = el.innerHTML;                         // the REAL rendered content — the authoritative landing
            var decimals = (numStr.split('.')[1] || '').length;
            var group = numStr.indexOf(',') !== -1;
            function fmt(v) { return prefix + v.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals, useGrouping: group }) + suffix; }
            var start = null;
            (function frame(ts) {
                if (start === null) start = ts;
                var p = Math.min((ts - start) / DUR, 1), eased = 1 - Math.pow(1 - p, 3);
                if (p < 1) { el.textContent = fmt(target * eased); requestAnimationFrame(frame); }
                else { el.innerHTML = original; }                // land on the EXACT real value
            })(performance.now());
        }

        function run() {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            document.querySelectorAll(SELECTOR).forEach(animate);
        }

        if (document.readyState !== 'loading') run(); else document.addEventListener('DOMContentLoaded', run);
        document.addEventListener('livewire:navigated', run);   // belt-and-braces (no wire:navigate today)
    })();
</script>
@endonce
