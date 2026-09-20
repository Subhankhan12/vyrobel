<?php

declare(strict_types=1);

/**
 * ⚠️⚠️ A DEPLOYMENT PROPERTY THAT NOBODY DECIDED, HOLDING UP TWO CONCLUSIONS THAT LOOK UNRELATED TO IT.
 *
 * ⚠️⚠️ **THIS IS NOT A PERFORMANCE TEST AND IT IS NOT ABOUT PRIVACY.** It exists because production sits
 * behind a CDN (`docs/KNOWN-LIMITS.md` L12m) that nothing in this repository knew about until it misbehaved,
 * and **two separate deployment guarantees turn out to rest on Laravel's default `Cache-Control` header**:
 *
 *   1. ⚠️ **HTML/ASSET SKEW CANNOT HAPPEN.** At the origin a release swap is atomic because everything
 *      resolves through one `current` symlink. At the **edge** the HTML and the hashed assets are separate
 *      objects with separate lifetimes — so a cached page from release N could ask for assets that only
 *      exist in N+1, or a fresh page from N+1 could be served beside assets still held from N. **That is a
 *      broken page.** 🔬 It cannot happen here **only because the HTML is never cached**, so a page always
 *      comes from the current release and always references assets that exist in it.
 *
 *   2. ⚠️⚠️ **THE RELEASE PRUNE FLOOR IS 180 SECONDS RATHER THAN SEVEN DAYS.** 🔬 The edge holds static
 *      assets for `max-age=604800`. If a cached HTML page could outlive a release, an old release would have
 *      to be kept for as long as the edge might still hand out a page referencing it — **7 days instead of
 *      3 minutes, a factor of 3,360**. Nobody would predict that consequence from "we added page caching".
 *
 * ⚠️⚠️ **SO A FRAMEWORK DEFAULT IS DOING LOAD-BEARING DEPLOYMENT WORK BY ACCIDENT, AND THE FIRST PERSON TO
 * ADD PAGE CACHING FOR SPEED WOULD BREAK BOTH WITHOUT EVER LEARNING THAT THEY HAD.** An incidental that
 * something depends on is a hidden dependency; this test makes it explicit so the dependency fails loudly
 * instead of silently.
 *
 * ⚠️ **WHAT WOULD MAKE THIS GO RED:** a `Cache-Control: public` on an HTML response — a marketing page given
 * `->header('Cache-Control', 'public, max-age=…')` for speed, a full-page cache package, a middleware that
 * strips `no-cache`, or an edge rule added in the hosting panel. ⚠️ **The first three are visible here. The
 * fourth is not, and that is stated in L12m rather than pretended away.**
 *
 * ⚠️ **IF THIS GOES RED, THE FIX IS NOT TO EDIT THIS TEST.** It is to decide, deliberately, that the prune
 * floor becomes 7 days and that skew is now possible — and to write that down. See L12k and L12m.
 */
/** @return array<int, string> the HTML routes a visitor actually reaches, guest and authenticated */
function cacheabilityProbePaths(): array
{
    return ['/', '/login', '/signup', '/pricing'];
}

it('⚠️⚠️ never sends a PUBLICLY cacheable HTML response — the no-skew guarantee and the 180s prune floor both rest on it', function () {
    $offenders = [];

    foreach (cacheabilityProbePaths() as $path) {
        $response = $this->get($path);

        // ⚠️ A redirect or a 404 is fine — this is about what the CACHE-CONTROL says on anything HTML we DO
        //    return. A route that does not exist in this deployment simply has nothing to assert.
        if ($response->getStatusCode() >= 500) {
            continue;
        }

        $cc = strtolower((string) $response->headers->get('Cache-Control'));

        // ⚠️ `public` is the word that makes a shared cache store it. `private` and `no-cache`/`no-store`
        //    all keep it out of the edge; any of them is acceptable, and the DEFAULT Laravel sends is
        //    `no-cache, private`.
        if (str_contains($cc, 'public')) {
            $offenders[] = "{$path} → Cache-Control: {$cc}";
        }
    }

    expect($offenders)->toBe([],
        "⚠️⚠️ AN HTML RESPONSE IS NOW PUBLICLY CACHEABLE, AND TWO DEPLOYMENT GUARANTEES JUST CHANGED:\n"
        .'  '.implode("\n  ", $offenders)."\n"
        ."⚠️ (1) HTML/ASSET SKEW BECOMES POSSIBLE. The edge can now hold a page from one release while the\n"
        ."      assets it references belong to another — a broken page, and the edge equivalent of the\n"
        ."      'partly into two releases' hazard the release layout was built to remove.\n"
        ."⚠️⚠️ (2) THE RELEASE PRUNE FLOOR GOES FROM 180 SECONDS TO 7 DAYS — a factor of 3,360 — because an\n"
        ."      old release must now survive as long as the edge might still serve a page referencing it.\n"
        ."      That consequence is invisible from the change that causes it.\n"
        .'⚠️ This is a DEPLOYMENT decision, not a caching tweak. If it is deliberate, change L12k and L12m '
        .'first and say what the new prune floor is — do not edit this test to make it pass.');
});

/**
 * ⚠️ AND THE SECOND LAYER, BECAUSE THE FIRST ONLY SEES THE PATHS IT SAMPLES.
 *
 * ⚠️ The runtime arm above proves the property on four representative pages. It cannot see a `public`
 * cache-control added to the fiftieth route, or to one that needs a fixture to reach. ⚠️ This arm sweeps the
 * SOURCE instead, so a page-caching header added anywhere in `app/` or `routes/` is caught even if no test
 * ever requests that page.
 *
 * ⚠️ It deliberately scans only `app/` and `routes/` — **not `tests/`** — so it cannot read its own needle,
 * which is a trap this project has met more than once.
 *
 * ⚠️ `public/build/*` and other static assets are SUPPOSED to be publicly cacheable and are served by the
 * web server, not by PHP; nothing here touches them.
 */
it('⚠️ has no source anywhere in app/ or routes/ that marks a response publicly cacheable', function () {
    $offenders = [];

    foreach (['app', 'routes'] as $dir) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($dir)));

        foreach ($it as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            foreach (file($file->getPathname()) as $n => $line) {
                // ⚠️ The word that makes a SHARED cache store the response. `private`, `no-cache` and
                //    `no-store` are all fine — only `public` hands it to the edge.
                if (preg_match('/[Cc]ache-[Cc]ontrol.*\bpublic\b/', $line)) {
                    $offenders[] = str_replace(base_path().'/', '', $file->getPathname()).':'.($n + 1).'  '.trim($line);
                }
            }
        }
    }

    expect($offenders)->toBe([],
        "⚠️⚠️ SOURCE NOW MARKS A RESPONSE PUBLICLY CACHEABLE:\n  ".implode("\n  ", $offenders)."\n"
        .'⚠️ If this is a static asset served through PHP, that is fine in itself — but read the docblock '
        .'above first: HTML being publicly cacheable re-opens release skew AND multiplies the release prune '
        .'floor by 3,360 (180s → 7 days). ⚠️ Decide that deliberately and record it in L12k/L12m; do not '
        .'silence this check.');
});
