<?php

/**
 * Front-website test helpers — shared across the marketing gate suites.
 *
 * ⚠️ THE TWO SHAPES. D1's content gate filters unreal figures and unconsented people in PRODUCTION, so
 * every marketing page has two renderings and both must be proven. `mkProdPage()` is the one that matters
 * at launch: it is the shape a visitor actually gets.
 */

/** Render a marketing page in the CURRENT environment (locally: illustrative content present). */
function mkPage(string $view): string
{
    return view('marketing.'.$view)->render();
}

/**
 * Render a marketing page as PRODUCTION sees it — the FILTERED shape.
 *
 * ⚠️ The environment is restored in a `finally`, so a failing assertion inside a both-shapes test can never
 * leave the whole suite running as "production" (which would silently change what every later test sees).
 */
function mkProdPage(string $view): string
{
    $app = app();
    $original = $app->environment();
    $app->detectEnvironment(fn (): string => 'production');

    try {
        return view('marketing.'.$view)->render();
    } finally {
        $app->detectEnvironment(fn (): string => $original);
    }
}
