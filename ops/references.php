<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;

/**
 * ops/references.php — ⚠️⚠️ IS THIS STRING REALLY REFERENCED, OR ONLY MENTIONED?
 *
 * ⚠️⚠️ WHAT A GREP GETS WRONG HERE, IN ONE LINE: **a grep finds literals, not facts.** 🔬 Asked whether six
 * permissions were still used, `grep -r` returned NINE hits across `app/`, `routes/` and `resources/` — and
 * **every single one was a comment or a docblock.** A pass that trusted it would have concluded they were
 * live; a pass that trusted the raw zero elsewhere would have deleted something real. Both directions wrong,
 * from the same instrument.
 *
 * ⚠️ SO THIS DOES NOT PATTERN-MATCH TEXT. It asks the application and the parser:
 *
 *   1. **ROUTES** — walks the BOOTED router's `gatherMiddleware()` and reads `can:` gates. 🔬 Necessary
 *      because `php artisan route:list --json` rewrites `can:` to `Authorize:`, which once produced a
 *      confident, wrong "55 of 55 ungated".
 *   2. **CODE** — `token_get_all()` over every PHP file, inspecting only `T_CONSTANT_ENCAPSED_STRING`.
 *      Comments and docblocks are a different token class, so they are invisible **by construction** rather
 *      than by a pattern that tries to skip them — the distinction that matters, because comment-stripping
 *      regexes have failed in this project repeatedly.
 *   3. **VIEWS** — Blade templates are COMPILED first, then tokenised, so `@can('x')` is seen as the PHP it
 *      becomes while `{{-- x --}}` is not seen at all.
 *
 * ⚠️⚠️ WHAT IT CANNOT SEE, SAID PLAINLY SO A ZERO IS NOT OVER-READ: a name built at runtime
 * (`'perm.'.$verb`), a name stored in a DATABASE row, a name in a `.sh`/`.md`/`.yml` file, and a name reached
 * by reflection. **A zero from this tool means "no PHP or Blade code names this literal" — not "nothing uses
 * it".** Pair it with a second, different method before deleting anything.
 *
 * ⚠️ EXIT CODES — the obvious composition must be the correct one (the lesson `ops/proc-check.sh` was
 * inverted for on the same day):
 *
 *     0 = at least one real reference found   → `ops/references.php x && echo "in use"`
 *     1 = NO real reference found anywhere    → safe-to-consider-dead, pending a second method
 *     2 = usage error / could not run
 *
 * USAGE:
 *   ops/references.php 'availability.view'
 *   ops/references.php --routes              # ⚠️ every NAMED ROUTE no VIEW links — see the mode's own note
 *   ops/references.php 'a.view,b.view'        # several at once; exit 0 if ANY is referenced
 *   ops/references.php --all 'x'              # list every site, not just the first few
 */
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

$args = array_slice($argv, 1);
$all = in_array('--all', $args, true);
$args = array_values(array_filter($args, static fn (string $a): bool => $a !== '--all'));

if ($args === [] && ! in_array('--routes', $argv, true)) {
    fwrite(STDERR, "usage: ops/references.php [--all] '<literal>[,<literal>…]'  |  ops/references.php --routes\n");
    exit(2);
}

// ── ⚠️ --routes: WHICH NAMED ROUTES DOES NO VIEW LINK? ──────────────────────────────────────────────
//
// ⚠️⚠️ THE QUESTION THIS ANSWERS IS NARROW ON PURPOSE: "no Blade template names this route". ⚠️ THAT IS NOT
// "unreachable" and must never be read as it. A route is legitimately unlinked when it is an API endpoint, a
// webhook, a redirect target, a POST target whose form action is built in JavaScript, or a signed/emailed URL.
// 🔬 Measured here: the front-desk board builds its check-in URL as `'/app/front-desk/' + row.id + '/check-in'`
// in Alpine, so `route()` never appears and the tokeniser cannot see it — a real capability with a real
// control, invisible to this mode. ⚠️ **The finding worth acting on is narrower still: an OPERATOR capability
// with no operator path.** Read the list, do not act on its length.
if (in_array('--routes', $args, true)) {
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    $named = [];
    foreach (app('router')->getRoutes() as $route) {
        $name = $route->getName();
        if (is_string($name) && $name !== '') {
            $named[$name] = $route->methods()[0].' '.$route->uri();
        }
    }

    // Every string literal that appears in a COMPILED Blade template — comments invisible by construction.
    $inViews = [];
    $blade = app('blade.compiler');
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/views', FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (! str_ends_with($f->getPathname(), '.blade.php')) {
            continue;
        }
        foreach (token_get_all($blade->compileString((string) file_get_contents($f->getPathname()))) as $t) {
            if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
                $inViews[trim($t[1], "'\"")] = true;
            }
        }
    }

    $unlinked = array_diff_key($named, $inViews);
    ksort($unlinked);

    printf("  %d of %d named routes are linked by no Blade view.\n\n", count($unlinked), count($named));
    foreach ($unlinked as $name => $sig) {
        printf("  %-52s %s\n", $name, $sig);
    }
    echo "\n  ⚠️ Legitimately unlinked: api/* · webhooks · redirect targets · JS-built form actions · signed URLs.\n";
    echo "  ⚠️⚠️ The finding is an OPERATOR CAPABILITY WITH NO OPERATOR PATH — not the length of this list.\n";

    exit($unlinked === [] ? 1 : 0);
}

$needles = array_values(array_filter(array_map('trim', explode(',', $args[0])), static fn ($n) => $n !== ''));
if ($needles === []) {
    fwrite(STDERR, "usage: ops/references.php [--all] '<literal>[,<literal>…]'\n");
    exit(2);
}

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/** @var array<string, list<string>> */
$hits = array_fill_keys($needles, []);

// ── 1. ROUTES — from the booted router, never from `route:list` output ──────────────────────────────
foreach (app('router')->getRoutes() as $route) {
    foreach ($route->gatherMiddleware() as $mw) {
        if (! is_string($mw) || ! str_starts_with($mw, 'can:')) {
            continue;
        }
        foreach (explode(',', substr($mw, 4)) as $perm) {
            $perm = trim($perm);
            if (array_key_exists($perm, $hits)) {
                $hits[$perm][] = 'ROUTE '.($route->getName() ?: $route->uri());
            }
        }
    }
}

// ── 2/3. CODE + VIEWS — tokenised, so comments are invisible by construction ────────────────────────
$scan = static function (string $code, string $label) use (&$hits): void {
    foreach (token_get_all($code) as $t) {
        if (! is_array($t) || $t[0] !== T_CONSTANT_ENCAPSED_STRING) {
            continue;   // T_COMMENT / T_DOC_COMMENT / T_INLINE_HTML never reach here
        }
        $lit = trim($t[1], "'\"");
        if (array_key_exists($lit, $hits)) {
            $hits[$lit][] = $label;
        }
    }
};

$blade = app('blade.compiler');
$skip = ['/vendor/', '/node_modules/', '/storage/', '/bootstrap/cache/'];

$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iter as $file) {
    $path = $file->getPathname();
    foreach ($skip as $s) {
        if (str_contains($path, $s)) {
            continue 2;
        }
    }
    $rel = str_replace($root.'/', '', $path);

    // ⚠️ The DECLARATION is not a USE. A catalogue listing a permission, or a seeder granting it, says
    //    nothing about whether anything ever CHECKS it — counting those would make every entry look live.
    if ($rel === 'config/authorization.php' || str_starts_with($rel, 'database/seeders/')) {
        continue;
    }

    // ⚠️⚠️ A REFERENCE FROM `tests/` IS NOT A PRODUCTION USE, AND CONFLATING THEM HIDES THE ANSWER. 🔬 Asked
    //    about `availability.view`, the raw tool reports "referenced" — and both sites are the phantom-
    //    permission REGISTER and its helper, i.e. tests that exist precisely to record that nothing uses it.
    //    A tool that answered "referenced" there would be technically true and practically backwards.
    $tag = str_starts_with($rel, 'tests/') ? 'TEST ' : ($rel[0] === 'a' && str_starts_with($rel, 'app/') ? 'PHP ' : 'PHP ');
    if (str_ends_with($path, '.blade.php')) {
        $scan($blade->compileString((string) file_get_contents($path)), (str_starts_with($rel, 'tests/') ? 'TEST ' : 'VIEW ').$rel);
    } elseif (str_ends_with($path, '.php')) {
        $scan((string) file_get_contents($path), $tag.$rel);
    }
}

$anyProduction = false;
$anyAtAll = false;

printf("  %-28s %-6s %-6s %s\n", 'LITERAL', 'PROD', 'TESTS', 'WHERE');
printf("  %-28s %-6s %-6s %s\n", str_repeat('-', 28), '------', '------', str_repeat('-', 40));

foreach ($hits as $needle => $where) {
    $where = array_values(array_unique($where));
    $tests = array_values(array_filter($where, static fn (string $w): bool => str_starts_with($w, 'TEST ')));
    $prod = array_values(array_filter($where, static fn (string $w): bool => ! str_starts_with($w, 'TEST ')));

    $anyProduction = $anyProduction || $prod !== [];
    $anyAtAll = $anyAtAll || $where !== [];

    $shown = $all ? $where : array_slice($prod !== [] ? $prod : $tests, 0, 4);

    printf("  %-28s %-6d %-6d %s\n", $needle, count($prod), count($tests),
        $where === []
            ? '— NOTHING IN PHP OR BLADE NAMES IT'
            : implode(' · ', $shown).(count($where) > count($shown) ? ' … +'.(count($where) - count($shown)).' more (--all)' : ''));
}

echo "\n";
if ($anyProduction) {
    echo "  RESULT: referenced by PRODUCTION code. ⚠️ A hit is a fact; still read each one.\n";
} elseif ($anyAtAll) {
    echo "  ⚠️⚠️ RESULT: NAMED ONLY BY TESTS. ⚠️ No route, no app/ code and no view names it — the only sites\n"
        ."     are tests, which may exist precisely to RECORD that it is unused. Read them before concluding.\n";
} else {
    echo "  ⚠️ RESULT: no PHP or Blade code names it at all.\n";
}

if (! $anyProduction) {
    echo "  ⚠️⚠️ AND THAT IS NOT \"UNUSED\" — see the header for what this cannot see (runtime-built names,\n"
        ."     database rows, .sh/.md/.yml, reflection). GET A SECOND, DIFFERENT METHOD BEFORE DELETING.\n";
}

// ⚠️ 0 = referenced by production code (the composable question: "is this really in use?").
exit($anyProduction ? 0 : 1);
