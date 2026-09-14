<?php

// ══ D1/D6 · PROGRESSIVE ENHANCEMENT — WHAT CAN AND CANNOT BE PROVEN HERE ══════════════════════════
//
// ⚠️⚠️ THERE IS NO BROWSER HARNESS IN THIS PROJECT, AND THIS FILE DOES NOT PRETEND OTHERWISE.
// The audit's accessibility findings were SOURCE-INFERRED for exactly this reason. So:
//
//   ✅ WHAT THESE TESTS PROVE
//      · every pane is PRESENT in the served HTML;
//      · no pane is hidden by an inline `display:none` in the served HTML — the no-JS state is COMPLETE;
//      · the script is referenced, deferred, and the file it points at exists;
//      · the static ARIA a no-JS reader meets is valid (exactly one selected tab per group);
//      · the handlers' hooks (`data-lensbtn` / `data-stagebtn` …) are in the output for the script to find.
//
//   ⛔ WHAT THEY DO NOT PROVE — AND NOTHING HERE SHOULD BE READ AS PROVING IT
//      · that clicking a tab actually switches a panel in a real browser;
//      · that arrow-key navigation moves focus;
//      · that the collapse happens at all. THAT REQUIRES A HEADLESS BROWSER. Its shape and cost are
//        reported to the founder rather than built inside this pass.
//
// ⚠️ An overstated test claim is the defect this project has spent months removing. The boundary above is
// the honest one, and it is written here so no later reader mistakes these greens for behavioural proof.

// ── ⚠️⚠️ THE RULE THAT MATTERS MOST: VISIBLE BY DEFAULT ───────────────────────────────────────────
//
// ⚠️ If JavaScript were what REVEALED these panes, a blocked bundle, a 404 after a deploy or any crawler
// would see seven-eighths of /product and two-thirds of the home hero missing. The converter strips the
// design's inline hides so the served HTML is complete; the script collapses them afterwards.
use PHPUnit\Framework\Assert;

it('⚠️ serves /product with EVERY stage panel present and none hidden', function () {
    $page = mkPage('product');

    $panels = [];
    preg_match_all('/data-stagepanel="(\d+)"/', $page, $panels);
    expect(count($panels[1]))->toBe(8);                  // ⚠️ the fixture bites: 8, not "some"

    // ⚠️ NOT ONE of them may carry an inline hide in the served HTML.
    preg_match_all('/<div data-stagepanel="\d+"[^>]*>/', $page, $tags);
    foreach ($tags[0] as $tag) {
        expect($tag)->not->toContain('display: none');
        expect($tag)->not->toContain('display:none');
    }
});

it('⚠️ serves / with every audience lens present and none hidden', function () {
    $page = mkPage('home');

    foreach (['operator', 'owner', 'guest'] as $lens) {
        expect($page)->toContain('data-lenscopy="'.$lens.'"');
        expect($page)->toContain('data-lensmock="'.$lens.'"');
    }

    preg_match_all('/<div data-lens(?:copy|mock)="[a-z]+"[^>]*>/', $page, $tags);
    expect(count($tags[0]))->toBe(6);                    // 3 lenses × copy + mock
    foreach ($tags[0] as $tag) {
        expect($tag)->not->toContain('display: none');
        expect($tag)->not->toContain('display:none');
    }
});

// ── ⚠️ THE STATIC ARIA A NO-JS READER MEETS ──────────────────────────────────────────────────────
//
// ⚠️ MEASURED before this pass: all three home audience tabs were `aria-selected="false"` — a tablist
// advertising that NOTHING is selected. That was wrong before any script existed and stays wrong for a
// reader whose JS never runs, so the default is now marked at build time.
it('⚠️ marks exactly one tab selected in each group, without JavaScript', function () {
    $home = mkPage('home');
    $product = mkPage('product');

    $selected = function (string $html, string $attr): int {
        preg_match_all('/<button[^>]*'.$attr.'="[^"]*"[^>]*>/', $html, $m);

        return count(array_filter($m[0], fn ($t) => str_contains($t, 'aria-selected="true"')));
    };

    expect($selected($home, 'data-lensbtn'))->toBe(1);
    expect($selected($product, 'data-stagebtn'))->toBe(1);

    // And the groups are real tablists the script can find.
    expect($home)->toContain('role="tablist"');
    expect($product)->toContain('role="tablist"');
});

// ── ⚠️ THE SCRIPT IS REFERENCED, DEFERRED, AND REALLY THERE ──────────────────────────────────────
it('⚠️ references the enhancement script without making the page depend on a build', function () {
    $page = mkPage('home');

    expect($page)->toContain('js/marketing.js');
    expect($page)->toContain('<script defer');           // never blocks the render it is not required for

    // ⚠️ NOT through @vite: the Vite helper throws on a missing manifest, which would turn a forgotten
    //    `npm run build` into a 500 on the public marketing site.
    expect($page)->not->toContain('@vite');
    expect($page)->not->toContain('/build/assets/');

    expect(is_file(public_path('js/marketing.js')))->toBeTrue();
});

// ── ⚠️ THE HOOKS THE SCRIPT BINDS TO ARE IN THE OUTPUT ───────────────────────────────────────────
//
// ⚠️ This asserts the CONTRACT between the generated markup and the script — if the converter ever stops
// emitting these, the script silently binds nothing and the controls go dead again, which is the exact
// state this pass was opened to end.
it('⚠️ emits the hooks the enhancement script binds to', function () {
    $home = mkPage('home');
    $product = mkPage('product');
    $js = file_get_contents(public_path('js/marketing.js'));

    foreach (['data-lensbtn', 'data-lenscopy', 'data-lensmock'] as $hook) {
        expect($home)->toContain($hook);
        expect($js)->toContain($hook);
    }
    foreach (['data-stagebtn', 'data-stagepanel'] as $hook) {
        expect($product)->toContain($hook);
        expect($js)->toContain($hook);
    }
});

// ── ⚠️ KEYBOARD OPERATION IS PRESENT IN THE SCRIPT (source-level, and said so) ────────────────────
//
// ⚠️ SOURCE-INFERRED, NOT BEHAVIOURAL. This proves the code path exists; it cannot prove a key press moves
// focus in a browser. Stated plainly rather than dressed up as a behavioural guarantee.
it('⚠️ ships keyboard navigation and roving tabindex in the script source', function () {
    $js = file_get_contents(public_path('js/marketing.js'));

    foreach (['ArrowRight', 'ArrowLeft', 'Home', 'End', 'keydown', 'tabIndex', 'aria-selected'] as $needle) {
        expect($js)->toContain($needle);
    }
    // The collapse must hide from the a11y tree too, not just from paint.
    expect($js)->toContain('el.hidden');
});

// ══ THE LOW-RISK GROUPS — AND HOW EACH DEGRADES WITH SCRIPTS OFF ══════════════════════════════════
//
// ⚠️ SCOPE CORRECTED AGAINST THE ROUTER: the brief named four groups, but /faq and /glossary are NOT
// ROUTED — like /changelog, /docs and /resources. Only the two PUBLISHED groups are built.

// ⚠️ A FILTER IS NOT A TABLIST, and its no-JS state is not "one pane". It is SHOW ALL: every module card
// ships visible and unfiltered, so a reader without JS sees the whole catalogue — more, never less.
it('⚠️ serves /modules with every card visible and "all" pressed — the correct no-JS state for a filter', function () {
    $page = mkPage('modules');

    $cards = substr_count($page, 'data-module="');
    expect($cards)->toBe(18);                       // ⚠️ the fixture bites: the real catalogue, not "some"

    // NOT ONE card may ship hidden — a filter must never subtract content from a scripts-off reader.
    preg_match_all('/<[a-z]+ [^>]*data-module="[a-z]+"[^>]*>/', $page, $tags);
    foreach ($tags[0] as $tag) {
        expect($tag)->not->toContain('display: none');
        expect($tag)->not->toContain('display:none');
    }

    // ⚠️ `aria-pressed`, NOT `aria-selected`: these are toggle buttons narrowing one list, not tabs.
    preg_match_all('/<button[^>]*data-modchip="([a-z]+)"[^>]*>/', $page, $chips, PREG_SET_ORDER);
    expect(count($chips))->toBe(4);
    $pressed = array_values(array_filter($chips, fn ($c) => str_contains($c[0], 'aria-pressed="true"')));
    expect(count($pressed))->toBe(1);
    expect($pressed[0][1])->toBe('all');            // the unfiltered view is the default
    foreach ($chips as $c) {
        expect($c[0])->not->toContain('role="tab"');
        expect($c[0])->not->toContain('aria-selected');
    }
});

// ── ⚠️⚠️ THE NINE-HOUR RAIL — the pane group that shipped hidden and unbound ─────────────────────
//
// ⚠️ FOUND BY CLICKING IT IN A BROWSER (BROWSER-AUDIT-2026-09-01 F-1), and NOT findable any other way at
// the time: the panels were present in the served HTML, so a presence test passed, while eight of the nine
// carried the master's inline `display:none` and NOTHING ever removed it. `_unhide` had three call sites
// and `data-hourpanel` was not one of them — the transform did not fail, it was never called.
it('⚠️ serves / with every hour panel present and none hidden', function () {
    $page = mkPage('home');

    preg_match_all('/<div [^>]*data-hourpanel="(\\d)"[^>]*>/', $page, $panels, PREG_SET_ORDER);
    expect(count($panels))->toBe(9);                 // the real rail, 07:00–23:00, not "some"

    foreach ($panels as $panel) {
        expect($panel[0])->not->toContain('display: none');
        expect($panel[0])->not->toContain('display:none');
    }

    // Nine tabs, each addressing a panel by the same value — otherwise the script binds to nothing.
    preg_match_all('/<button[^>]*data-hourbtn="(\\d)"[^>]*>/', $page, $btns, PREG_SET_ORDER);
    expect(count($btns))->toBe(9);
    expect(array_column($btns, 1))->toBe(array_column($panels, 1));

    // ⚠️ Exactly one selected, so the no-JS ARIA is valid and the script honours the served default.
    $selected = array_filter($btns, fn ($b) => str_contains($b[0], 'aria-selected="true"'));
    expect(count($selected))->toBe(1);
});

// ── ⚠️⚠️ THE GUARD THAT WOULD HAVE CAUGHT IT — every page, not a named few ───────────────────────
//
// ⚠️⚠️ THE ZERO-MATCH NEEDLE GUARD COULD NOT HAVE CAUGHT F-1, and that is the lesson worth keeping.
// `_NEEDLE_LOG` records whether a transform that RAN matched anything; it is silent about a transform that
// was never called. Only a check over the OUTPUT — asserting the rule for every pane on every page rather
// than for the panes somebody remembered — closes that gap. This is that check.
it('⚠️ hides no tab panel on ANY marketing page — the rule, not a list of remembered groups', function () {
    $views = ['home', 'product', 'features', 'modules', 'pricing', 'security', 'integrations', 'switching',
        'how-it-works', 'about', 'contact', 'try-booking', 'for-hotels', 'for-apartments', 'for-villas',
        'case-studies', 'story-lisbon', 'story-rotterdam', 'story-cyclades'];

    $offenders = [];
    foreach ($views as $view) {
        preg_match_all('/<[a-z]+ [^>]*role="tabpanel"[^>]*>/', mkPage($view), $tags);
        foreach ($tags[0] as $tag) {
            if (str_contains($tag, 'display: none') || str_contains($tag, 'display:none')) {
                $offenders[] = $view.': '.substr($tag, 0, 70);
            }
        }
    }

    expect($offenders)->toBe([]);
});

// ══ ⚠️⚠️ F-6 / F-7 / F-8 — THREE RULES OVER EVERY PAGE, NOT THREE REMEMBERED PAGES ════════════════
//
// ⚠️ All three defects were found by a BROWSER and had survived every source check, because each is only
// visible once the page is rendered: a double-escaped entity looks correct in the source, a heading level
// looks correct in isolation, and a broken anchor looks like a link. So these are written as RULES over the
// rendered output of ALL 19 pages — the F-1 lesson, where a transform nobody called slipped past a needle
// log that only reports on transforms that ran.

/** Every routed marketing view, by its VIEW name (which is not always its URL). */
function mkAllViews(): array
{
    return ['home', 'product', 'features', 'modules', 'pricing', 'security', 'integrations', 'switching',
        'how-it-works', 'about', 'contact', 'try-booking', 'for-hotels', 'for-apartments', 'for-villas',
        'case-studies', 'story-lisbon', 'story-rotterdam', 'story-cyclades'];
}

// ── ⚠️⚠️ F-6 · NO ENTITY MAY BE ESCAPED TWICE ────────────────────────────────────────────────────
//
// ⚠️ `/for-apartments` and `/for-villas` rendered `&amp;amp;`, so the browser tab and every search result
// showed a literal "&amp;". The source was already `&amp;` inside a Blade ATTRIBUTE, and `{{ }}` escaped it
// again. ⚠️ It was in the `<title>`, `og:title` AND `twitter:title` — three fields per page, where the audit
// (which read only `<title>`) saw one.
it('⚠️ escapes no entity twice, on any page', function () {
    $offenders = [];
    foreach (mkAllViews() as $view) {
        if (preg_match_all('/&amp;(amp|lt|gt|quot|#39|nbsp|apos|#x27);/', mkPage($view), $m)) {
            $offenders[] = $view.': '.count($m[0]).' × &amp;'.$m[1][0].';';
        }
    }

    expect($offenders)->toBe([]);
});

// ── ⚠️⚠️ F-7 · EXACTLY ONE H1, AND NO SKIPPED LEVEL ─────────────────────────────────────────────
//
// ⚠️ A screen-reader user navigating by heading met NO page title on /contact, TWO subjects on
// /try-booking, and a misreported outline on /features.
it('⚠️ gives every page exactly one h1 and an unbroken heading order', function () {
    // ⚠️ THE RULE MOVED TO `headingOutlineOffenders()` when the guest realm turned out to have the same
    //    class. One definition, two realms — see tests/Support/heading_helpers.php.
    $offenders = [];
    foreach (mkAllViews() as $view) {
        $offenders = [...$offenders, ...headingOutlineOffenders($view, mkPage($view))];
    }

    expect($offenders)->toBe([]);
});

// ── ⚠️⚠️ F-8 · EVERY IN-PAGE ANCHOR NAMES AN ID THAT EXISTS ─────────────────────────────────────
//
// ⚠️ `/pricing` offered "Request a formal quote" pointing at `/contact#form`, and no such id existed — the
// visitor landed at the top of the page with nothing highlighted. The contact form already carried
// `id="vy-contact-form"`, so the LINK was repointed rather than a second id minted for one element.
it('⚠️ resolves every in-page anchor to an element that exists', function () {
    // URL path → view name, because they differ (/example-scenarios is the `case-studies` view).
    $byPath = ['/' => 'home', '/product' => 'product', '/features' => 'features', '/modules' => 'modules',
        '/pricing' => 'pricing', '/security' => 'security', '/integrations' => 'integrations',
        '/switching' => 'switching', '/how-it-works' => 'how-it-works', '/about' => 'about',
        '/contact' => 'contact', '/try-booking' => 'try-booking', '/for-hotels' => 'for-hotels',
        '/for-apartments' => 'for-apartments', '/for-villas' => 'for-villas',
        '/example-scenarios' => 'case-studies', '/example-scenarios/city-hotel' => 'story-lisbon',
        '/example-scenarios/serviced-apartments' => 'story-rotterdam',
        '/example-scenarios/villa-portfolio' => 'story-cyclades'];

    $rendered = [];
    foreach ($byPath as $path => $view) {
        $rendered[$path] = mkPage($view);
    }

    $offenders = [];
    foreach ($rendered as $path => $page) {
        preg_match_all('/href="([^"]*#[^"]*)"/', $page, $m);
        foreach (array_unique($m[1]) as $href) {
            if (str_starts_with($href, 'http')) {
                continue;   // an absolute URL is not an in-page anchor
            }
            [$target, $fragment] = explode('#', $href, 2);
            // ⚠️ STRIP THE QUERY. A first sweep did not, and reported 58 working `?from=…#vy-sandbox-stay`
            //    links as broken — its own false positive, not a defect.
            $target = strtok($target, '?') ?: $path;
            if ($fragment === '' || ! isset($rendered[$target])) {
                continue;
            }
            if (! preg_match('/\sid="'.preg_quote($fragment, '/').'"/', $rendered[$target])) {
                $offenders[] = "{$path} → {$href} (no id=\"{$fragment}\" on {$target})";
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('⚠️ gives every published story a print hook that is a browser call, not a claim', function () {
    foreach (['story-lisbon', 'story-cyclades', 'story-rotterdam'] as $story) {
        $page = mkPage($story);
        expect($page)->toContain('data-printbtn');
        expect($page)->toContain('Print this story');
    }

    // ⚠️ It prints the page the reader is already on. Nothing is fetched, generated or promised.
    $js = file_get_contents(public_path('js/marketing.js'));
    expect($js)->toContain('window.print()');
    expect($js)->toContain('data-printbtn');
});

// ══ THE FALSE NUMBERS — REMOVED, NOT REPLACED, NOT NEGATED ════════════════════════════════════════
//
// ⚠️ VERIFIED against the `plans` rows: Essential 12 × €49 = €588 against €490, and Pro 12 × €99 = €1,188
// against €990 — both 16.67%, NOT 15%. And `MarketingPricing::isPlaceholder()` is TRUE, so even 16.67% is
// derived from prices nobody has set. The figure is REMOVED rather than corrected: a more precise number
// computed from placeholders is a worse claim wearing better arithmetic.
it('⚠️ /pricing names no annual discount figure, and does not negate one either', function () {
    $page = mkPage('pricing');

    expect($page)->not->toContain('15%');
    expect($page)->not->toContain('16.67');
    // ⚠️ NOT NEGATED: answering a false claim by quoting it leaves the phrase for a skimmer.
    expect(strtolower($page))->not->toContain('no longer');
    expect(strtolower($page))->not->toContain('used to offer');

    // ⚠️ The TRUE half stays: annual billing genuinely exists (the plans table carries a yearly interval).
    expect($page)->toContain('Annual');
    expect($page)->toContain('Annual billing is the cheaper of the two cycles');
});

// ⚠️ THE ESTIMATOR — the slider AND the sentence. No migration-duration model exists anywhere in app/, and
// Vyrobel has never migrated anyone, so a paragraph asserting "4–5 weeks" commits exactly as much as a
// control showing it. `data-estcost` was worse still: a commercial promise keyed to portfolio size.
it('⚠️ /switching estimates no duration and promises no included migration', function () {
    $page = mkPage('switching');

    foreach (['type="range"', 'data-estsrc', 'data-estweeks', 'data-estcost', 'data-estwrap'] as $gone) {
        expect($page)->not->toContain($gone);
    }
    expect($page)->not->toContain('4–5 weeks');
    expect($page)->not->toContain('Migration is included');

    // ⚠️ The question is still ANSWERED — honestly, and without a number.
    expect($page)->toContain('How long yours would take');
    expect($page)->toContain('has not migrated anyone yet');
    expect($page)->not->toContain('weeks to cutover');
});

// ══ ⚠️⚠️ THE BILLING-PERIOD SWITCHER — BOTH PRICES SHIP, THE SCRIPT ONLY COLLAPSES ONE ═══════════════
//
// ⚠️ A PRICE A VISITOR CANNOT SEE IS WORSE THAN A PANEL THEY CANNOT SEE. The pane rule above exists because
// eight hour panels once shipped `display:none` with no script to reveal them; a price is the one thing on
// this page a reader came for, so it gets its own assertion rather than relying on the general rule.
it('⚠️⚠️ ships BOTH billing periods visible, so /pricing is complete with no JavaScript', function () {
    $html = mkPage('pricing');

    // ⚠️ The served HTML **is** the no-JS state: `marketing.js` only ever hides.
    preg_match_all('/<(?:div|span) data-cycleprice="(monthly|yearly)"([^>]*)>/', $html, $m, PREG_SET_ORDER);

    Assert::assertGreaterThanOrEqual(6, count($m),
        'fewer cycle blocks than expected — the cards, the comparison headers and the price note all carry one');

    $seen = ['monthly' => 0, 'yearly' => 0];

    foreach ($m as [$whole, $cycle, $attrs]) {
        $seen[$cycle]++;
        // ⚠️ NEITHER `hidden` NOR an inline `display:none` may ship on a price.
        Assert::assertStringNotContainsString('hidden', $attrs, "a {$cycle} price ships with the hidden attribute");
        Assert::assertDoesNotMatchRegularExpression('/display:\s*none/', $attrs,
            "a {$cycle} price ships inline-hidden — with scripts off a visitor would never see it");
    }

    // ⚠️ AND BOTH PERIODS MUST BE PRESENT IN EQUAL NUMBER: one without the other is a page that shows a
    //    yearly price to nobody, or a monthly one that cannot be switched away from.
    Assert::assertSame($seen['monthly'], $seen['yearly'], 'the two billing periods are not rendered in pairs');
    Assert::assertGreaterThan(0, $seen['yearly'], 'no yearly price is rendered at all');

    // ⚠️ EVERY PERIOD-BEARING LABEL IS INSIDE ITS OWN CYCLE BLOCK, so nothing can lag behind the switch.
    //    Both suffixes and both "Billed …" lines must be in the served page.
    foreach (['/ mo', '/ yr', 'Billed monthly', 'Billed yearly', 'per month', 'per year'] as $label) {
        Assert::assertStringContainsString($label, $html, "the served page never renders \"{$label}\"");
    }
});

it('⚠️⚠️ quotes no saving of any kind for annual billing — derived or typed', function () {
    $html = mkPage('pricing');

    // ⚠️⚠️ <style> AND <script> COME OUT FIRST. `strip_tags()` removes the TAGS and keeps their CONTENTS,
    //    so the shell's stylesheet — full of `100%`, `translateX(-120%)`, `saturate(1.15)` — reads as page
    //    prose and this assertion fired on CSS. That is the fifth variant of the same trap in this project
    //    (a scan reading something that is not the visitor's text); the fix is always the same.
    $visible = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    $visible = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $visible);
    $text = strip_tags((string) preg_replace('/<!--.*?-->/s', '', $visible));

    // ⚠️ THE TRAP THIS GUARDS: the arithmetic is available (€490 against 12 × €49 is 16.67%) and it is a
    //    claim about two numbers NOBODY HAS SET. Deriving a percentage correctly does not make it true,
    //    and a "−15%" badge was already cut from this page for being false.
    Assert::assertDoesNotMatchRegularExpression('/\d+(\.\d+)?\s*%/', $text,
        '/pricing quotes a percentage — every price here is a placeholder, so any saving figure is a '.
        'claim about numbers nobody has set');

    foreach (['months free', 'month free', 'save ', 'discount', 'you save', 'cheaper by'] as $phrase) {
        Assert::assertStringNotContainsStringIgnoringCase($phrase, $text,
            "/pricing quantifies the annual saving with \"{$phrase}\"");
    }

    // ⚠️ A struck-through price is the same claim drawn instead of written.
    Assert::assertDoesNotMatchRegularExpression('/<(s|del|strike)\b/', $visible,
        '/pricing strikes through a price — that asserts a saving without naming it');

    // ⚠️ THE TRUE, UNQUANTIFIED LINE STAYS. It is the model for what may be said about annual billing.
    Assert::assertStringContainsString('Annual billing is the cheaper of the two cycles', $html);
});
