<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Illuminate\Testing\TestResponse;

// ⚠️⚠️ VY-28 (audit of 2026-11-24, seen live) — THE CONTENT SECURITY POLICY ALLOWED INLINE SCRIPTS.
//
// `script-src 'self' 'unsafe-inline' 'unsafe-eval'`: any injected <script> or `onerror=` attribute would have run. 72 inline event-handler
// attributes made 'unsafe-inline' necessary — an attribute handler cannot carry a nonce. They are gone (data- attributes read by ONE
// delegated script, components/vy-behaviours), every inline <script> carries this request's nonce, and the policy names that nonce
// instead of 'unsafe-inline'. ⚠️ 'unsafe-eval' stays (Alpine evaluates x- expressions with `new Function`; its CSP build is a separate
// rewrite) — asserted here as still present, so the day it goes this test is updated deliberately rather than silently.

uses(DatabaseTransactions::class);

/** The Blade sources, with Blade comments removed (a comment may DESCRIBE an attribute without rendering one). */
function vy28Sources(): array
{
    $out = [];
    foreach (File::allFiles(resource_path('views')) as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }
        $out[$file->getRelativePathname()] = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($file->getPathname()));
    }

    return $out;
}

/** The nonce named in the response's policy, and the policy's script-src. */
function vy28Policy(TestResponse $response): array
{
    $csp = (string) $response->headers->get('Content-Security-Policy');
    preg_match('/script-src ([^;]+)/', $csp, $m);
    $scriptSrc = $m[1] ?? '';
    preg_match("/'nonce-([A-Za-z0-9+\/=_-]+)'/", $scriptSrc, $n);

    return [$scriptSrc, $n[1] ?? null];
}

it('⚠️⚠️ VY-28: no view carries an inline event handler — the reason the policy needed \'unsafe-inline\' is gone', function () {
    $offenders = [];
    foreach (vy28Sources() as $name => $source) {
        if (preg_match_all('/<[a-zA-Z][^>]*\son[a-z]+\s*=\s*["\']/s', $source, $m)) {
            foreach ($m[0] as $hit) {
                $offenders[] = $name.': '.substr(trim($hit), 0, 80);
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('⚠️⚠️ VY-28: every executable inline <script> in a view carries @nonce (a JSON data block needs none — it never runs)', function () {
    $offenders = [];
    foreach (vy28Sources() as $name => $source) {
        preg_match_all('/<script\b[^>]*>/i', $source, $m);
        foreach ($m[0] as $tag) {
            $type = preg_match('/type="([^"]+)"/', $tag, $t) ? $t[1] : 'text/javascript';
            $executes = in_array($type, ['text/javascript', 'module', 'application/javascript'], true);
            if ($executes && ! str_contains($tag, 'src=') && ! str_contains($tag, '@nonce')) {
                $offenders[] = $name.': '.$tag;
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('⚠️⚠️ VY-28: on a real page of every realm, the policy names a nonce and no \'unsafe-inline\' for scripts, and every inline script carries that nonce', function (Closure $visit, bool $hasInline = true) {
    /** @var TestResponse $response */
    $response = $visit();
    $response->assertOk();
    [$scriptSrc, $nonce] = vy28Policy($response);

    expect($nonce)->not->toBeNull()
        ->and(strlen((string) $nonce))->toBeGreaterThanOrEqual(16)
        ->and($scriptSrc)->not->toContain("'unsafe-inline'")
        ->and($scriptSrc)->toContain("'unsafe-eval'");   // ⚠️ still needed by Alpine — see the file's header

    $html = (string) $response->getContent();
    preg_match_all('/<script\b([^>]*)>/i', $html, $m);
    $inline = 0;
    foreach ($m[1] as $attrs) {
        $type = preg_match('/type="([^"]+)"/', $attrs, $t) ? $t[1] : 'text/javascript';
        if (str_contains($attrs, 'src=') || ! in_array($type, ['text/javascript', 'module', 'application/javascript'], true)) {
            continue;
        }
        $inline++;
        expect($attrs)->toContain('nonce="'.$nonce.'"');
    }
    if ($hasInline) {
        expect($inline)->toBeGreaterThan(0);   // a control: the page really has inline scripts to check
    }
    expect($html)->not->toMatch('/<[a-zA-Z][^>]*\son(click|change|submit|input|load|error|mouseover|mouseout)\s*=/');
})->with([
    'marketing home (its script is a file: no inline one to check)' => [fn () => test()->get('/'), false],
    'sign-in' => [fn () => test()->get('/login')],
    'guest sign-in' => [fn () => test()->get('/guest/login')],
    'storefront' => [function () {
        test()->travelTo('2026-09-01 10:00:00');
        $s = publicPropertyScenario(units: 1);

        return test()->get('/book/'.$s['property']->slug);
    }],
    'back office (Livewire)' => [function () {
        $s = seedScenario(1);

        return test()->actingAs(operatorUser($s, [], 'Hotel Manager'))->get(route('app.housekeeping'));
    }],
]);

it('VY-28: each request gets its own nonce', function () {
    [, $a] = vy28Policy($this->get('/login'));
    [, $b] = vy28Policy($this->get('/login'));

    expect($a)->not->toBeNull()->and($a)->not->toBe($b);
});

it('VY-28: the @nonce directive itself prints this request\'s nonce (compiled fresh — a cached compiled view would hide a broken directive)', function () {
    Vite::useCspNonce('vy28-fixed-nonce-value');

    expect(Blade::render('<script @nonce>var x = 1;</script>', [], deleteCachedView: true))
        ->toBe('<script nonce="vy28-fixed-nonce-value">var x = 1;</script>');
});
