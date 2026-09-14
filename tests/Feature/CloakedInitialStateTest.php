<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;
use Symfony\Component\Finder\Finder;

/**
 * ⚠️⚠️ NEVER CLOAK WHAT IS VISIBLE IN THE INITIAL STATE.
 *
 * `x-cloak` exists to hide un-initialised Alpine markup so a user never sees raw template state — a real
 * purpose, and removing it naively would reintroduce a real problem. But an element whose `x-show` is TRUE
 * at init is not un-initialised markup: it is THE CONTENT. Cloaking it means that when Alpine does not
 * arrive — a bundle 404 after a deploy, a CSP refusal (this project has had one), a dropped network — the
 * content is `display: none` forever, with no error and nothing to see.
 *
 * ⚠️ THE MEASURED INSTANCE. `housekeeping-board.blade.php` cloaked BOTH branches of a mutually-exclusive
 * toggle whose default is `list`, so with the script blocked the main region fell from 961 characters and
 * **7 visible submit buttons to 574 and ZERO** — while all 13 `<form method="POST">` stayed in the DOM.
 * Those are server-rendered posts to certified gated routes: they would have worked. The cloak threw away
 * functionality that was already there.
 *
 * ⚠️ THIS IS NOT THE MARKETING SITE'S RULE TRANSPLANTED. That site ships no framework and degrades to a
 * complete document; this app needs JavaScript to function and always did. The rule here is narrower and
 * defensible on its own: do not hide the branch you have chosen to show.
 */
it('⚠️⚠️ never cloaks an element that its own initial state makes visible', function () {
    $offenders = [];
    $checked = 0;

    /** @var SplFileInfo $file */
    foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
        $raw = (string) file_get_contents($file->getRealPath());

        // ⚠️⚠️ COMMENTS OUT FIRST, AND THIS IS NOT DEFENSIVE WRITING. The fix's own comment explains why the
        //    branch is NOT cloaked — and contains the word `x-cloak` while doing so. A scan that read its own
        //    documentation would report the fix as the defect. That trap has fired nine times in this
        //    project; stripping Blade and HTML comments is the whole answer here.
        $src = (string) preg_replace(['/\{\{--.*?--\}\}/s', '/<!--.*?-->/s'], '', $raw);

        // The initial values an `x-data` object literal declares, for the simple shapes that actually occur:
        //   key: 'literal'            and     key: localStorage.getItem('…') || 'literal'
        $initial = [];
        preg_match_all('/x-data="\{(.*?)\}"/s', $src, $blocks);
        foreach ($blocks[1] as $block) {
            preg_match_all('/(\w+)\s*:\s*localStorage\.getItem\([^)]*\)\s*\|\|\s*\'([^\']*)\'/', $block, $m, PREG_SET_ORDER);
            foreach ($m as $pair) {
                $initial[$pair[1]] = $pair[2];
            }
            preg_match_all('/(\w+)\s*:\s*\'([^\']*)\'\s*[,\n]/', $block, $m2, PREG_SET_ORDER);
            foreach ($m2 as $pair) {
                $initial[$pair[1]] ??= $pair[2];
            }
        }

        if ($initial === []) {
            continue;
        }

        // Every opening tag, attribute values respected so a `>` inside one cannot split it.
        preg_match_all('/<[a-zA-Z][\w-]*((?:"[^"]*"|\'[^\']*\'|[^>])*)>/', $src, $tags);

        foreach ($tags[1] as $attrs) {
            if (! str_contains($attrs, 'x-cloak') || ! preg_match('/x-show="([^"]*)"/', $attrs, $show)) {
                continue;
            }
            $checked++;

            // `key === 'literal'` — the shape a view-toggle branch uses.
            if (! preg_match('/^\s*(\w+)\s*===?\s*\'([^\']*)\'\s*$/', $show[1], $cond)) {
                continue;   // anything more complex is not decided here; see the limitation note below
            }

            if (($initial[$cond[1]] ?? null) === $cond[2]) {
                $offenders[] = str_replace(base_path().'/', '', $file->getRealPath())
                    .' — x-show="'.$show[1].'" is TRUE at init ('.$cond[1].' starts \''.$cond[2].'\') yet carries x-cloak';
            }
        }
    }

    // ⚠️ NON-VACUITY: the scan must actually be reaching cloaked `x-show` branches. "No offenders" is worth
    //    nothing if the parser matched nothing at all — which is exactly how a guard rots unnoticed.
    Assert::assertGreaterThan(5, $checked,
        "only {$checked} cloaked x-show elements were examined; the scanner is not finding them");

    Assert::assertSame([], $offenders,
        'an element is cloaked even though its own `x-data` makes it visible at init, so it is hidden '
        .'forever if Alpine never arrives. ⚠️ Cloak the branches that start HIDDEN; never the one that '
        .'starts shown. ⚠️ LIMITATION, stated rather than implied: this decides only the '
        .'`key === \'literal\'` shape against a literal initialiser — the shape a view toggle uses, and the '
        .'one that produced the measured defect. A more complex condition is not judged here.');
});

it('⚠️ the housekeeping list — the measured instance — ships REACHABLE without Alpine', function () {
    $src = (string) file_get_contents(resource_path('views/livewire/housekeeping-board.blade.php'));
    $src = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $src);

    // The default branch is visible; the non-default one is still cloaked, so nothing flashes both at once.
    Assert::assertStringContainsString('<div x-show="view === \'list\'">', $src,
        'the default view is cloaked again — with the script blocked the board renders 574 characters and '
        .'ZERO of its 7 submit buttons, while all 13 forms sit in the DOM unreachable');

    Assert::assertStringContainsString('<div x-show="view === \'board\'" x-cloak>', $src,
        'the NON-default branch lost its cloak — both views would render at once before Alpine boots, which '
        .'is the flash the cloak legitimately exists to prevent');
});
