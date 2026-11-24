<?php

declare(strict_types=1);

use App\Support\Marketing\MarketingSitemap;
use App\Support\Privacy\CookieConsent;

// ⚠️⚠️ VY-29 (audit of 2026-11-24, seen live) — THE MARKETING PAGES WERE HARD TO FIND AND COULD NOT BE CACHED.
//
// No sitemap for the marketing pages and none named in robots.txt; no social preview image; and every anonymous visit started a session
// (a database row on production, two Set-Cookie headers) on pages that read nothing from one. Fixed as the audit asked: a sitemap (named
// in robots.txt with the storefront's), a 1200×630 preview image, and marketing pages that start no session — Contact excepted (its form).
//
// ⚠️ NOT DONE HERE, AND WHY: (1) the CDN may still not cache these pages — they stay `private` on purpose (HtmlIsNeverPubliclyCacheableTest:
//    no HTML/asset skew, the 180-second prune floor); making them public is the founder's deployment decision, and a session-free page is
//    its precondition. (2) "All 27 languages share one address": the marketing pages are English only (the language switch was deferred
//    with D7), so one address per page is correct today; addresses per language come with translated marketing copy.

it('⚠️⚠️ VY-29: /sitemap.xml lists every indexable marketing page — and every page it lists says "index", every page it leaves out says "noindex"', function () {
    $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
    preg_match_all('#<loc>([^<]+)</loc>#', (string) $xml, $m);
    $listed = $m[1];

    expect($listed)->toContain(route('marketing.page.home'))
        ->toContain(route('marketing.page.pricing'))
        ->toContain(route('marketing.page.switching'))
        ->and(count($listed))->toBe(count(array_unique($listed)));

    foreach ($listed as $url) {
        $page = $this->get(parse_url($url, PHP_URL_PATH) ?: '/')->assertOk()->getContent();
        expect($page)->toContain('<meta name="robots" content="index, follow">');
    }
    foreach (MarketingSitemap::NOINDEX as $key) {
        expect($listed)->not->toContain(route('marketing.page.'.$key));
        expect($this->get(route('marketing.page.'.$key))->assertOk()->getContent())->toContain('noindex, nofollow');
    }
});

it('VY-29: robots.txt names both sitemaps', function () {
    $robots = (string) file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Sitemap: https://vyrobel.com/sitemap.xml')
        ->toContain('Sitemap: https://vyrobel.com/sitemap-storefront.xml');
});

it('⚠️⚠️ VY-29: a marketing page starts no session — no session cookie, no XSRF cookie — and Contact (its form) still does', function (string $path) {
    $response = $this->get($path)->assertOk();

    $cookies = collect($response->headers->getCookies())->map(fn ($c) => $c->getName())->all();
    expect($cookies)->not->toContain(config('session.cookie'))->not->toContain('XSRF-TOKEN');
})->with(['/', '/pricing', '/features', '/switching', '/example-scenarios', '/sitemap.xml']);

it('VY-29 CONTROL: Contact keeps its session — its form carries a CSRF token and posts', function () {
    $response = $this->get('/contact')->assertOk();

    expect(collect($response->headers->getCookies())->map(fn ($c) => $c->getName())->all())->toContain('XSRF-TOKEN');
    $response->assertSee('name="_token"', false);
});

it('⚠️ VY-29: with no session to hold a token, the cookie choice is accepted only from this site — another site\'s post is refused', function () {
    $home = (string) config('app.url');

    $this->withHeader('Origin', $home)->post(route('cookies.choice'), ['choice' => CookieConsent::DECLINED, 'return' => '/pricing'])
        ->assertRedirect('/pricing#vy-cookie-notice')->assertCookie(CookieConsent::COOKIE);

    $this->withHeader('Origin', 'https://evil.example')->post(route('cookies.choice'), ['choice' => CookieConsent::ACCEPTED, 'return' => '/'])
        ->assertForbidden();
    $this->withHeaders(['Origin' => '', 'Referer' => ''])->post(route('cookies.choice'), ['choice' => CookieConsent::ACCEPTED, 'return' => '/'])
        ->assertForbidden();
    // a browser that sends no Origin but a Referer from this site is fine
    $this->withHeaders(['Origin' => '', 'Referer' => $home.'/features'])->post(route('cookies.choice'), ['choice' => CookieConsent::ACCEPTED, 'return' => '/features'])
        ->assertRedirect('/features#vy-cookie-notice');
});

it('⚠️ VY-29: every marketing page names the social preview image, and the image is a 1200×630 PNG', function () {
    $file = public_path('images/og-cover.png');
    expect(is_file($file))->toBeTrue();

    [$width, $height, $type] = getimagesize($file) ?: [0, 0, 0];
    expect([$width, $height, $type])->toBe([1200, 630, IMAGETYPE_PNG]);

    $this->get('/')->assertOk()->assertSee('<meta property="og:image" content="'.url('/images/og-cover.png').'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    $this->get('/pricing')->assertOk()->assertSee(url('/images/og-cover.png'), false);
});
