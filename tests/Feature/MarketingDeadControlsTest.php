<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ THE DEAD-CONTROL RULE: NOTHING ON A MARKETING PAGE MAY LOOK CLICKABLE AND DO NOTHING.
 *
 * ⚠️ THE CLASS, NOT A LIST. Four controls were found dead in a browser and removed — the pricing
 * Monthly/Annual toggle, "Download pricing as PDF", the three "Daylight" dusk toggles — and before them
 * the currency switcher (`3b155dc`) and the nine-hour rail (bound, not cut). Every one was the same
 * shape: the converter strips the design's dc-runtime and keeps its markup, so a prototype handler that
 * was never rebuilt leaves an enabled `<button>` with `cursor: pointer` behind.
 * ⚠️ A test naming those four would have caught none of them, because each was found by LOOKING. This
 * asserts the rule instead, so the NEXT one reds without anybody remembering it exists.
 *
 * ⚠️⚠️ THE BOUND SET IS READ FROM THE SCRIPT, NEVER TYPED HERE. `public/js/marketing.js` is the only
 * thing that makes a marketing control work; a hardcoded copy in this file would be a second definition
 * of "what is wired" and would drift from the first the day someone adds a handler (§N.8f). Binding a new
 * control therefore satisfies this guard automatically — the guard is satisfiable by doing the right
 * thing, which is why it will survive contact with the next person who needs a button.
 *
 * ⚠️ AND IT READS THE SERVED HTML, NOT THE BLADE SOURCE. Reading the source found `<button` inside a CSS
 * COMMENT in the shell's <style> block — prose about buttons, on all 19 pages, reported as 19 dead
 * controls. CSS comments ship where Blade's do not. Comments, <style> and <script> are stripped below for
 * exactly that reason: a guard must read what a browser gets, not what an author wrote about it.
 */

/** The 19 routed marketing pages, taken from the router rather than the file system. */
function mdcPages(): array
{
    return collect(app('router')->getRoutes())
        ->filter(fn ($r): bool => str_starts_with((string) $r->getName(), 'marketing.page.'))
        ->map(fn ($r): string => '/'.ltrim($r->uri(), '/'))
        ->values()->all();
}

/** ⚠️ What a browser is actually given: no <style>, no <script>, no comments. */
function mdcStrip(string $html): string
{
    $html = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    $html = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

    return (string) preg_replace('/<!--.*?-->/s', '', $html);
}

/**
 * The `data-` attributes `marketing.js` binds — READ FROM THE SCRIPT.
 *
 * @return list<string>
 */
function mdcBoundAttributes(): array
{
    $js = (string) file_get_contents(public_path('js/marketing.js'));
    preg_match_all('/\[(data-[a-z0-9-]+)\]/i', $js, $m);

    return array_values(array_unique($m[1]));
}

/**
 * ⚠️ THE ALLOW-LIST — EXPLICIT, WITH A REASON EACH, AND EMPTY TODAY.
 *
 * A control that legitimately needs no handler belongs here with the reason written down. It must never
 * be a silent skip: this project's rule is that a red row for correct work means the row is wrong, and the
 * way to fix such a row is to say WHY the control is correct, not to widen the pattern until it stops
 * noticing. Keyed by the visible label.
 *
 * @return array<string, string>
 */
function mdcAllowed(): array
{
    return [
        // (none today — every button on every page is a form submit, script-bound, or disabled)
    ];
}

it('⚠️ binds at least one control — the anchor that stops this guard going vacuous', function () {
    // ⚠️ IF THE SCRIPT WERE EMPTIED OR RENAMED, `mdcBoundAttributes()` would return [] and every control
    //    on the site would read as unbound — which is TRUE and would red the sweep below loudly. This
    //    line makes the opposite failure impossible to miss too: a guard whose input silently became
    //    empty is the shape that lets a whole check pass on nothing.
    expect(mdcBoundAttributes())->not->toBeEmpty();
    Assert::assertContains('data-hourbtn', mdcBoundAttributes(),
        'the hour rail lost its binding — nine controls on the home page go dead with it');
});

it('⚠️ leaves no button on any marketing page that looks clickable and does nothing', function () {
    $bound = mdcBoundAttributes();
    $allowed = mdcAllowed();
    $pages = mdcPages();

    Assert::assertGreaterThanOrEqual(15, count($pages), 'the marketing page set shrank — this sweep covers less');

    $dead = [];

    foreach ($pages as $path) {
        $html = mdcStrip($this->get($path)->assertOk()->getContent());

        // Form submits are live by construction: the browser posts, no script required.
        preg_match_all('/<form\b.*?<\/form>/is', $html, $fm, PREG_OFFSET_CAPTURE);
        $forms = array_map(fn (array $f): array => [$f[1], $f[1] + strlen($f[0])], $fm[0]);

        preg_match_all('/<button\b([^>]*)>(.*?)<\/button>/is', $html, $bm, PREG_OFFSET_CAPTURE);

        foreach ($bm[0] as $i => $whole) {
            $attrs = $bm[1][$i][0];
            $label = trim(preg_replace('/\s+/', ' ', strip_tags($bm[2][$i][0])) ?? '');
            $at = $whole[1];

            $inForm = collect($forms)->contains(fn (array $s): bool => $s[0] <= $at && $at < $s[1]);
            $isDisabled = (bool) preg_match('/\bdisabled\b/', $attrs);
            preg_match_all('/\b(data-[a-z0-9-]+)/i', $attrs, $dm);
            $isBound = array_intersect($dm[1], $bound) !== [];

            if ($inForm || $isDisabled || $isBound || array_key_exists($label, $allowed)) {
                continue;
            }

            $dead[] = "{$path} → \"{$label}\" (".($dm[1] === [] ? 'no data- attribute' : implode(',', $dm[1])).')';
        }
    }

    Assert::assertSame([], $dead,
        'a button is served that no form submits, no script binds and nothing disables — it is a control a '.
        "visitor can click and get nothing from:\n  ".implode("\n  ", $dead));
});

it('⚠️ leaves no ARIA control — tab, pressed or expanded — that no script can move', function () {
    $bound = mdcBoundAttributes();
    $orphans = [];

    foreach (mdcPages() as $path) {
        $html = mdcStrip($this->get($path)->assertOk()->getContent());

        // ⚠️ THE SECOND HALF OF THE CLASS. `role="tab"`/`aria-pressed`/`aria-selected` PROMISE a state that
        //    changes — to a screen reader they announce a control that can be switched. Static markup
        //    carrying them is a dead control that also lies to assistive technology, which is how the hour
        //    rail shipped nine `role="tab"` buttons that could never be selected.
        preg_match_all('/<(?:button|a|div|span|li)\b([^>]*(?:role="tab"|aria-pressed=|aria-selected=)[^>]*)>/i', $html, $m);

        foreach ($m[1] as $attrs) {
            preg_match_all('/\b(data-[a-z0-9-]+)/i', $attrs, $dm);

            if (array_intersect($dm[1], $bound) === []) {
                $orphans[] = $path.' → '.trim(preg_replace('/\s+/', ' ', substr($attrs, 0, 90)) ?? '');
            }
        }
    }

    Assert::assertSame([], $orphans,
        "an element announces a switchable state that no script can switch:\n  ".implode("\n  ", $orphans));
});
