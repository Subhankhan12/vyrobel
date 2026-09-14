<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ WHAT THIS FILE CAN AND CANNOT PROVE — READ THIS BEFORE TRUSTING A GREEN RUN.
 *
 * Four findings (F-5 overflow · F-9 border contrast · F-11 focus order · F-12 tap targets) were MEASURED
 * IN A REAL BROWSER on 2026-09-02, at 375×812, over CDP. This file guards the SOURCE-LEVEL FACTS those
 * fixes rest on. It does NOT re-measure the browser, and a green run here is NOT a runtime proof.
 *
 * ⚠️ PROVEN HERE (source / served HTML — a real fact, checkable in CI):
 *   · the skip link is the FIRST focusable element in the served DOM of every page, and there is exactly one
 *   · a `:focus` rule exists that brings it back on-screen
 *   · the footer grid's phone rule uses `minmax(0, …)` rather than a bare `1fr`
 *   · a selected-state rule exists for every control group the script drives
 *
 * ⚠️⚠️ NOT PROVEN HERE — a browser is the only instrument for these, and this file must never be read as
 * covering them:
 *   · that the page does not scroll sideways (🔬 measured 2026-09-02: 0px overflow on 19 pages, was 13px)
 *   · the computed contrast of any colour  (🔬 resting border 3.22:1, was 1.02:1 · selected fill 5.06:1 ·
 *     selected label on its own fill 4.89:1)
 *   · the real tab order after layout      (🔬 skip link is now stop 0; it was stop 2)
 *   · whether a tap target is big enough after layout
 * ⚠️ Those figures are RECORDED, not asserted. If someone changes a colour token, this file stays green and
 * the contrast silently moves — which is exactly why the numbers are written down here with their date
 * rather than encoded as a source assertion that could not really check them.
 */
function mlcPages(): array
{
    return collect(app('router')->getRoutes())
        ->filter(fn ($r): bool => str_starts_with((string) $r->getName(), 'marketing.page.'))
        ->map(fn ($r): string => '/'.ltrim($r->uri(), '/'))
        ->values()->all();
}

/** ⚠️ Everything a browser would not treat as markup: <style>, <script> and comments alike. */
function mlcStripNonMarkup(string $html): string
{
    $html = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    $html = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

    return (string) preg_replace('/<!--.*?-->/s', '', $html);
}

/** ⚠️ Comments are stripped: a CSS comment SHIPS, and a source scan once read prose as 19 real buttons. */
function mlcShellCss(): string
{
    $shell = (string) file_get_contents(resource_path('views/components/marketing/shell.blade.php'));
    preg_match('/<style\b[^>]*>(.*?)<\/style>/s', $shell, $m);

    return (string) preg_replace('#/\*.*?\*/#s', '', $m[1] ?? '');
}

it('⚠️ F-11 — makes the skip link the first focusable element on every page', function () {
    $pages = mlcPages();
    Assert::assertGreaterThanOrEqual(15, count($pages), 'the marketing page set shrank — this sweep covers less');

    foreach ($pages as $path) {
        // ⚠️⚠️ <style> AND <script> MUST GO FIRST, NOT ONLY COMMENTS. My first version stripped HTML
        //    comments only and reported the skip link "not first" on every page — because the shell's
        //    <style> block contains the sentence "The design drew these as <button>s whose selection
        //    lived in React state", and the scan read that prose as a real control. CSS comments SHIP
        //    where Blade's do not; this is the THIRD time that trap has fired in this project.
        $html = mlcStripNonMarkup($this->get($path)->assertOk()->getContent());

        // Every element a browser would put in the tab order, in DOM order.
        preg_match_all(
            '/<(a\b[^>]*\bhref=|button\b(?![^>]*\bdisabled\b)|input\b(?![^>]*\bdisabled\b)|select\b|textarea\b|summary\b)[^>]*>/i',
            $html, $m, PREG_OFFSET_CAPTURE
        );
        $stops = array_map(fn (array $x): string => $x[0], $m[0]);

        Assert::assertNotEmpty($stops, "[{$path}] no focusable element found — this sweep would be vacuous");

        // ⚠️ THE RULE. It was the THIRD stop (logo → menu burger → skip) because the design emitted it
        //    inside the page body, which renders inside <main>, after the whole navigation.
        Assert::assertStringContainsString('vy-mk-skip', $stops[0],
            "[{$path}] the skip link is not the first focusable element — it is what a keyboard user must ".
            'reach first, and anything ahead of it defeats the point');

        // ⚠️ EXACTLY ONE. The fix moved it into the shell AND cut it from 19 generated pages; if a page
        //    ever re-emits its own, a keyboard user meets two and the second skips nothing.
        Assert::assertSame(1, substr_count($html, 'vy-mk-skip'), "[{$path}] has more than one skip link");
    }
});

it('⚠️ F-11 — gives the skip link a focus rule that brings it back on screen', function () {
    $css = mlcShellCss();

    // ⚠️ THE DEFECT THIS PINS: it was parked at left:-9999px with NO :focus rule, so it was focusable and
    //    permanently invisible. 🔬 Measured before the fix: left=-9999 unfocused AND -9999 focused.
    Assert::assertMatchesRegularExpression('/\.vy-mk-skip:focus\s*\{[^}]*left:\s*(?!-)/', $css,
        'the skip link has no :focus rule moving it on screen — it can be focused and never seen');
});

it('⚠️ F-5 — leaves no bare 1fr in the footer grid, the track that overflowed 19 pages', function () {
    $css = mlcShellCss();

    preg_match('/@media \(max-width: 960px\) \{ \.vy-mk-footgrid \{([^}]*)\}/', $css, $m);
    Assert::assertNotEmpty($m, 'the footer grid phone rule is gone — the 13px overflow can return unnoticed');

    // ⚠️ WHY minmax(0,…): a bare `1fr` is `minmax(auto, 1fr)`, and that auto MINIMUM is a min-content floor
    //    the track cannot shrink below. The first footer column's min-content is ~243px, which is what put
    //    the page at 388 against a 375 viewport. 🔬 Neutralising this grid live took scrollWidth 388 → 375.
    Assert::assertStringContainsString('minmax(0', $m[1],
        'the footer grid track lost its minmax(0, …) floor — this is the exact line that caused 13px of '.
        'horizontal overflow on all 19 pages');
});

it('⚠️ F-9 — styles the selected state of every control group the script drives', function () {
    $css = mlcShellCss();
    $js = (string) file_get_contents(public_path('js/marketing.js'));

    // ⚠️ THE SET IS READ FROM THE SCRIPT, never typed here: a group the script drives but nothing styles is
    //    the defect (a working tab group whose selected tab looked identical to the others).
    preg_match_all('/\[(data-[a-z0-9-]+)\]/i', $js, $m);
    $driven = array_values(array_unique($m[1]));
    Assert::assertNotEmpty($driven, 'no driven controls found — this guard would be vacuous');

    // Only the ones that carry a selectable state; panels and targets are not controls.
    $stateful = array_values(array_filter($driven, fn (string $a): bool => str_ends_with($a, 'btn') || $a === 'data-modchip'));
    Assert::assertNotEmpty($stateful);

    foreach ($stateful as $attr) {
        if ($attr === 'data-printbtn') {
            continue;   // ⚠️ ALLOWED, WITH ITS REASON: a print button is momentary — it has no selected state.
        }

        // ⚠️⚠️ `aria-checked` IS IN THIS LIST BECAUSE THE GUARD FIRED AND WAS RIGHT. The billing-period
        //    switcher is a RADIOGROUP — two mutually exclusive presentations of the same plans, not two
        //    panels — so its state lives on `aria-checked`, and this pattern knew only the tab and toggle
        //    attributes. ⚠️ The rule it encodes ("CSS must paint what the script moves") always meant to
        //    cover EVERY stateful control; naming the third ARIA state attribute COMPLETES it rather than
        //    weakening it. The alternative — an allow-list entry for the new control — would have exempted
        //    exactly the thing the rule exists to check, which is why there is none.
        Assert::assertMatchesRegularExpression(
            '/\['.preg_quote($attr, '/').'\]\[aria-(selected|pressed|checked)="true"\]/',
            $css,
            "[{$attr}] the script moves its selected state but no CSS paints it — 🔬 measured 1.00:1 ".
            'between selected and unselected before this rule existed, so a sighted user could not tell '.
            'which control was active'
        );
    }
});

/**
 * ⚠️⚠️ THE PRINT ARTEFACT — WHAT IS AND IS NOT PROVEN BELOW.
 *
 * The printed page was broken in a way no static check could see: the design ships its print rules in a
 * `<style media="print">` block, `helmet()` consumes every <style> in the master before the body is even
 * extracted, and so "Print this story" produced a document carrying the navigation bar, the footer, the
 * decorative orbs and the cookie notice, and MISSING its letterhead — `[data-printhead]` and
 * `[data-printfoot]` ship `display:none` inline and only that stripped block ever revealed them.
 *
 * ⚠️ PROVEN HERE: that the rules reach every served page and name the right selectors.
 * ⚠️⚠️ NOT PROVEN HERE — a browser emulating print media is the only instrument, and it was used:
 *   🔬 2026-09-02, `Emulation.setEmulatedMedia({media:'print'})` on the three stories and /pricing —
 *   letterhead and print footer VISIBLE; header, footer, skip link, burger, cookie notice, orbs and
 *   `[data-noprint]` all ZERO visible. On screen, unchanged.
 * ⚠️ That measurement caught a defect this file cannot: the rules were present and correct, and the
 * letterhead still did not appear, because one selector in the hide list matched each page's ROOT
 * WRAPPER. A stylesheet that reads correctly can still print nothing.
 */
it('⚠️ carries the print artefact rules on every page', function () {
    $css = mlcShellCss();

    Assert::assertMatchesRegularExpression('/@media print\s*\{/', $css,
        'the print rules are gone — printing produces the screen, chrome and all');
    Assert::assertStringContainsString('@page', $css, 'no @page rule: the printed sheet has no size or margin');

    // ⚠️ The two elements that exist ONLY for print and are invisible without this rule.
    Assert::assertMatchesRegularExpression('/\[data-printhead\][^{]*,[^{]*\[data-printfoot\]\s*\{[^}]*display:\s*block/', $css,
        'the letterhead and print footer are no longer revealed — they are hidden inline and nothing else shows them');

    // ⚠️ And the chrome a printed document must not carry.
    foreach (['.vy-mk-header', '.vy-mk-skip', '[data-orb]', '[data-noprint]', 'vy-cookie-h-'] as $mustHide) {
        Assert::assertStringContainsString($mustHide, $css,
            "the print rules no longer hide {$mustHide} — it would appear on the printed page");
    }

    // ⚠️⚠️ AND THE OVER-BROAD SELECTOR THAT COST THE FIRST ATTEMPT. `[data-vy-rail]` is on each page's
    //    root wrapper, not on a scroll rail; hiding it printed a blank sheet.
    Assert::assertStringNotContainsString('[data-vy-rail]', $css,
        'the print rules hide [data-vy-rail], which is the page root — this prints an empty document');
});

it('⚠️ keeps every page carrying the print rules through generation', function () {
    // ⚠️ THE ACTUAL DEFECT WAS A GENERATION ONE, so the rule is checked over the SERVED output of every
    //    page rather than over the shell alone: a page that stopped using the shell would lose them
    //    silently, and the shell would still look right.
    foreach (mlcPages() as $path) {
        $html = $this->get($path)->assertOk()->getContent();

        Assert::assertStringContainsString('@media print', $html,
            "[{$path}] is served without the print rules");
        Assert::assertStringContainsString('data-printhead', $html,
            "[{$path}] no longer references the letterhead selector");
    }
});
