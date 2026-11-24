<?php

declare(strict_types=1);

// VY-30 (audit of 2026-11-24, reproduced) — 22 footer links were under the 24 px touch-target size (WCAG 2.5.8): the 13 px line alone.
// VY-31 (seen live) — the example-scenarios page linked "/case-studies", a 404: it was the page's own CANONICAL (and og:url), and the three
// scenario pages pointed theirs at "/case-studies/…" too. Every canonical is now the address the router really serves.
// (The 24 px was MEASURED in a browser, 2026-11-25: all 24 footer links and both disclosure toggles ≥ 24 px. This pins the rules that make it so.)

it('⚠️ VY-30: the footer links and the two disclosure toggles keep a 24 px target', function () {
    $shell = (string) file_get_contents(resource_path('views/components/marketing/shell.blade.php'));
    expect($shell)->toMatch('/\.vy-mk-footcol a, \.vy-mk-footcol span\[data-vy-pending\] \{[^}]*min-height: 24px/');

    $home = $this->get('/')->assertOk()->getContent();
    expect(substr_count((string) $home, 'padding:5px 0;line-height:1.2'))->toBeGreaterThanOrEqual(2);
});

it('⚠️ VY-31: every marketing page names a canonical address the router really serves — no /case-studies', function () {
    foreach (['home', 'product', 'features', 'modules', 'how-it-works', 'pricing', 'switching', 'for-hotels', 'for-apartments', 'for-villas', 'case-studies', 'story-cyclades', 'story-lisbon', 'story-rotterdam', 'integrations', 'security', 'about', 'contact', 'privacy', 'terms', 'try-booking'] as $key) {
        $page = (string) $this->get(route('marketing.page.'.$key))->assertOk()->getContent();
        preg_match('/<link rel="canonical" href="([^"]+)">/', $page, $m);
        expect($m[1] ?? null)->toBe(route('marketing.page.'.$key), "the canonical of {$key}");
        expect($page)->not->toContain('/case-studies');
    }
});
