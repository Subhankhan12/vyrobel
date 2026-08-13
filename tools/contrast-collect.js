/*
 * THE CANDIDATE COLLECTOR — the browser half of the contrast probe (DO-A11Y-1, 2026-08-13).
 *
 * ⚠️ WHY THIS FILE EXISTS. `tools/contrast-sampler.php` reads plates out of a PNG; it is only ever as good as
 * the candidate list handed to it. The DO-EMAIL-5 Part-A sweep found TWO faults that live entirely on this
 * side — in which elements get collected and how the screenshot is taken — and BOTH invented defects rather
 * than hiding them, which is the more dangerous direction because the numbers looked ordinary:
 *
 *   1. A `fullPage` screenshot does not repaint a `position: fixed` sidebar below the fold, so every nav item
 *      past the fold landed on blank canvas and read white-on-white 1.00.
 *      → CAPTURE CONTRACT: size the VIEWPORT to the document and screenshot WITHOUT fullPage.
 *   2. An element clipped by an `overflow:auto` ancestor still reports a page rect. Payout-run's "Mark paid"
 *      lays out at x=870 while its scroll container ends at x=785, so the sampler measured the card painted
 *      there instead and reported 1.17 for a button not drawn at that point at all.
 *      → HIT-TEST: a candidate must be the element actually painted at its own sample point.
 *
 * ⚠️ Those corrections were, until this file, uncommitted — they existed only in one session's tool calls.
 * A correction that is not committed is a correction hoped. `ContrastCollectorValidationTest` pins both.
 *
 * USAGE (Playwright MCP): resize the viewport to the document height, then evaluate `vyCollectContrast()`
 * and screenshot at `scale: css` WITHOUT fullPage. Feed the JSON to tools/contrast-sampler.php.
 */
function vyCollectContrast() {
    window.scrollTo(0, 0);

    // ⚠️ Freeze by COMPLETING animations, never `animation: none` — the shell's entry animations have no
    // `fill: forwards`, so cancelling them reverts elements to `opacity: 0` and manufactures 1.00s (§N.8e).
    document.getAnimations().forEach((a) => {
        try { a.finish(); } catch (e) { a.cancel(); }
    });

    const out = [];
    let n = 0;
    let rejectedNotPainted = 0;
    let rejectedOffscreen = 0;

    document.querySelectorAll('*').forEach((el) => {
        // Only elements painting their OWN text: a wrapper's rect spans its children, so it would be measured
        // against a plate its text never sits on.
        const ownsText = [...el.childNodes].some((c) => c.nodeType === 3 && c.textContent.trim().length > 1);
        if (!ownsText) return;

        const cs = getComputedStyle(el);
        if (cs.visibility === 'hidden' || cs.display === 'none') return;
        if (parseFloat(cs.opacity) < 0.95) return;

        const r = el.getBoundingClientRect();
        if (r.width < 4 || r.height < 4) return;

        const m = cs.color.match(/[\d.]+/g);
        if (!m) return;
        if (m[3] !== undefined && parseFloat(m[3]) < 0.95) return;

        const cx = r.x + r.width / 2;
        const cy = r.y + r.height / 2;

        // FAULT 1's guard. With the viewport sized to the document nothing legitimate is out of view, so an
        // out-of-view centre means the capture contract was not honoured — refuse rather than sample blank.
        if (cx < 0 || cy < 0 || cx > window.innerWidth || cy > window.innerHeight) { rejectedOffscreen++; return; }

        // FAULT 2's guard, and it subsumes more than clipping: an element covered by a later layer, or hidden
        // behind a scroll container, fails this too. `contains` both ways because the hit is usually a child
        // text node's parent, and occasionally an ancestor when the element itself is not hit-testable.
        const hit = document.elementFromPoint(cx, cy);
        if (!hit || !(hit === el || el.contains(hit) || hit.contains(el))) { rejectedNotPainted++; return; }

        const size = parseFloat(cs.fontSize);
        const bold = parseInt(cs.fontWeight, 10) >= 700;

        out.push({
            t: 'e' + (n++),
            sel: el.tagName.toLowerCase() + (typeof el.className === 'string' && el.className.trim()
                ? '.' + el.className.trim().split(/\s+/).slice(0, 2).join('.') : ''),
            txt: el.textContent.trim().slice(0, 40),
            css: cs.color,
            fg: [+m[0], +m[1], +m[2]],
            x: Math.round(r.x + window.scrollX),
            y: Math.round(r.y + window.scrollY),
            w: Math.round(r.width),
            h: Math.round(r.height),
            // WCAG large text: >=24px, or >=18.66px when bold.
            need: (size >= 24 || (size >= 18.66 && bold)) ? 3.0 : 4.5,
        });
    });

    return {
        docWidth: Math.round(document.documentElement.scrollWidth),
        docHeight: Math.round(document.documentElement.scrollHeight),
        rejectedNotPainted,
        rejectedOffscreen,
        candidates: out,
    };
}

if (typeof module !== 'undefined' && module.exports) { module.exports = { vyCollectContrast }; }
