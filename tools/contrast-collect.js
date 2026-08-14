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
        // (no own-opacity refusal: GAP A composites it instead — see the alpha walk below)

        // ⚠️ SCOPE GAP 3 — RAISED in the Part-B data sweep, CLOSED here by DO-PROBE-3.
        //
        // The `visibility`/`display` checks above say nothing about opacity, and an element's OWN opacity is 1
        // even when an ANCESTOR fades the whole row, card or empty-state block. Such an element paints BELOW
        // its declared colour, so a ratio computed from `cs.color` is optimistic — a FALSE CLEAN BILL, the
        // worst output this tool can produce. DO-A11Y-1 hit exactly this: a locked nav item at opacity .55
        // painted its label at 2.89 while DECLARING a colour that measures 9.40.
        //
        // The interim fix flagged such elements unmeasurable. They are now MEASURED properly:
        // ⚠️ GAP A CLOSED (DO-PROBE-3). This block used to REFUSE such an element; it now measures it.
        // CSS opacity composites the whole subtree, so the effective alpha is the PRODUCT of this element's own
        // opacity and every ancestor's. The sampler composites the declared colour over the plate at that alpha
        // and measures what is actually PAINTED. `alpha` is 1 for the ordinary case, so nothing else changes.
        let alpha = parseFloat(cs.opacity);
        let dimmedBy = null;
        for (let a = el.parentElement; a && a !== document.documentElement; a = a.parentElement) {
            const ao = parseFloat(getComputedStyle(a).opacity);
            if (ao < 1) {
                alpha *= ao;
                if (!dimmedBy) {
                    dimmedBy = a.tagName.toLowerCase() + (typeof a.className === 'string' && a.className.trim()
                        ? '.' + a.className.trim().split(/\s+/).slice(0, 2).join('.') : '');
                }
            }
        }

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

        // ⚠️ GAP B RECOVERY (i) (DO-PROBE-3). When the painted background is a SOLID colour, the plate is not
        // something to go hunting for between glyphs — it IS that colour, exactly. Walking up to the nearest
        // ancestor with a non-transparent background and no gradient gives it directly, which both removes the
        // anti-alias fringe the clear-column scan can pick up AND rescues the dense-text case where no clear
        // column exists at all. It is only claimed when there is genuinely no image/gradient in play.
        let solidBg = null;
        for (let a = el; a && a !== document.documentElement; a = a.parentElement) {
            const acs = getComputedStyle(a);
            if (acs.backgroundImage && acs.backgroundImage !== 'none') break;   // a gradient/photo — not solid
            const m2 = (acs.backgroundColor || '').match(/[\d.]+/g);
            if (!m2) continue;
            const op = m2[3] === undefined ? 1 : parseFloat(m2[3]);
            if (op < 1) continue;                                              // translucent — composite unknown
            solidBg = [+m2[0], +m2[1], +m2[2]];
            break;
        }

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
            // GAP A: what the subtree actually paints at. 1 unless something above dims it.
            alpha: Math.round(alpha * 1000) / 1000,
            dimmedBy,
            solidBg,
        });
    });

    return {
        docWidth: Math.round(document.documentElement.scrollWidth),
        docHeight: Math.round(document.documentElement.scrollHeight),
        // ⚠️ THE CAPTURE CONTRACT, made checkable rather than assumed (Part B). Two violations produced
        // fabricated defects on the first populated page measured: the viewport was SHORTER than the document
        // (so the screenshot ended mid-page while rects kept pointing past it), and a Livewire redirect
        // re-rendered the DOM BETWEEN collection and capture, leaving every coordinate stale — nine avatars
        // "measured" 1.17–1.26 against a card they are not painted on. Both are now detectable downstream.
        viewportH: window.innerHeight,
        viewportW: window.innerWidth,
        url: location.pathname + location.search,
        rejectedNotPainted,
        rejectedOffscreen,
        candidates: out,
    };
}

if (typeof module !== 'undefined' && module.exports) { module.exports = { vyCollectContrast }; }
