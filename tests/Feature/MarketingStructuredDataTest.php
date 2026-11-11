<?php

declare(strict_types=1);

use App\Support\Marketing\MarketingPricing;
use App\Support\Marketing\StructuredData;
use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ STRUCTURED DATA IS A CLAIMS SURFACE, AND THIS GUARDS THE CLAIM, NOT THE SYNTAX.
 *
 * A machine-readable value is REPRINTED by whatever reads it, so a false one reaches a person BEFORE the
 * page and its caveats do. Only ONE schema type is true of this product today — `FAQPage`, because it
 * needs nothing but questions and answers that are visible on the page. Every other type needs a value
 * this repository does not have: a real price (`isPlaceholder()` is true), a domain (`APP_URL` is
 * localhost), a logo file (public/ has none), or a rating (there are no reviews).
 *
 * ⚠️ WHAT IS PROVEN HERE: the block parses, it carries no placeholder, and it says exactly what the page
 * says. ⚠️ WHAT IS NOT: whether a search engine chooses to use it. That is not a property of this code.
 */
function msdLd(string $html): array
{
    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);

    return $m[1];
}

it('⚠️ emits FAQ structured data that PARSES — checked by decoding, never by eye', function () {
    $blocks = msdLd($this->get('/pricing')->assertOk()->getContent());

    Assert::assertCount(1, $blocks, '/pricing should carry exactly one JSON-LD block');

    // ⚠️ THE DECODE IS THE TEST. My first version of this feature emitted JSON built at BUILD time, which
    //    embedded a live Blade expression and produced `\A` — an invalid JSON escape. It looked perfectly
    //    fine in the file and could never have been parsed by a consumer.
    $ld = json_decode($blocks[0], true, 512, JSON_THROW_ON_ERROR);

    expect($ld['@context'])->toBe('https://schema.org');
    expect($ld['@type'])->toBe('FAQPage');
    expect($ld['mainEntity'])->toHaveCount(5);

    foreach ($ld['mainEntity'] as $q) {
        expect($q['@type'])->toBe('Question');
        expect($q['acceptedAnswer']['@type'])->toBe('Answer');
        Assert::assertNotSame('', trim($q['name']));
        Assert::assertNotSame('', trim($q['acceptedAnswer']['text']));
    }
});

it('⚠️⚠️ says in structured data exactly what the page says on screen', function () {
    $html = $this->get('/pricing')->assertOk()->getContent();
    $ld = json_decode(msdLd($html)[0], true, 512, JSON_THROW_ON_ERROR);

    // The visible Q&A, read from the rendered page.
    preg_match_all('/<details[^>]*>\s*<summary[^>]*>(.*?)<\/summary>\s*<p[^>]*>(.*?)<\/p>/s', $html, $m, PREG_SET_ORDER);
    $visible = array_map(
        fn (array $x): array => [
            html_entity_decode(trim(strip_tags($x[1])), ENT_QUOTES | ENT_HTML5),
            html_entity_decode(trim(strip_tags($x[2])), ENT_QUOTES | ENT_HTML5),
        ],
        $m
    );

    Assert::assertNotEmpty($visible, 'no visible Q&A found — this comparison would be vacuous');

    $structured = array_map(
        fn (array $q): array => [$q['name'], $q['acceptedAnswer']['text']],
        $ld['mainEntity']
    );

    // ⚠️ THE RULE: structured data that disagrees with the page is the thing search engines penalise, and
    //    the thing a reader cannot see. One of these answers is BOUND to the billing model, so this can
    //    only be checked against the SERVED page — the converter that writes the block cannot see it.
    Assert::assertSame($visible, $structured,
        'the structured FAQ and the visible FAQ have drifted apart');
});

it('⚠️⚠️ ships no machine-readable placeholder', function () {
    $html = $this->get('/pricing')->assertOk()->getContent();
    $blob = msdLd($html)[0];

    // ⚠️ THE PRICE IS NOT SET. Asserted from the source of truth, not assumed, so that if a real price is
    //    ever configured this line stops being the reason and somebody has to think again.
    expect(MarketingPricing::isPlaceholder())->toBeTrue();

    // ⚠️ VALUES that could only be a placeholder. A domain or a price would reach a reader through a
    //    search result before the page could qualify it.
    foreach (['localhost', '49.00', '99.00', '€49', '€99', '127.0.0.1'] as $forbidden) {
        Assert::assertStringNotContainsStringIgnoringCase($forbidden, $blob,
            "the JSON-LD carries \"{$forbidden}\" — a placeholder reaches a reader through a search ".
            'result before the page can qualify it');
    }

    // ⚠️⚠️ AND THE PRICE CHECK IS OVER THE STRUCTURE, NOT THE TEXT — my first version banned the SUBSTRING
    //    "price" and red on the answer "⚠️ It does not change your price", which is legitimate visible
    //    prose. A guard that reds for correct work is a wrong guard. What may not appear is a schema
    //    PROPERTY that asserts a price; the word in a sentence asserts nothing machine-readable.
    $keys = [];
    $ld = json_decode($blob, true, 512, JSON_THROW_ON_ERROR);
    $collect = function (array $node) use (&$collect, &$keys): void {
        foreach ($node as $k => $v) {
            $keys[] = (string) $k;
            if (is_array($v)) {
                $collect($v);
            }
        }
    };
    $collect($ld);

    foreach (['price', 'priceCurrency', 'offers', 'lowPrice', 'highPrice', 'priceSpecification'] as $prop) {
        Assert::assertNotContains($prop, $keys,
            "the JSON-LD declares the \"{$prop}\" property — every price in this repository is a ".
            'placeholder, so a machine-readable one is an unset number presented as fact');
    }
});

it('⚠️ claims no schema type this product cannot supply truthfully — on a developer or test address NOTHING but the FAQ ships', function () {
    // ⚠️ APP_URL is `http://localhost` here, so every type that needs the site's real address (Organization, WebSite, BreadcrumbList) has no value to
    //    carry; Product / Offer / SoftwareApplication / AggregateRating need a real price or a rating and stay refused EVERYWHERE (next test).
    $refused = ['"Product"', '"Offer"', '"Organization"', '"WebSite"', '"BreadcrumbList"', '"SoftwareApplication"', '"AggregateRating"'];

    foreach (['/pricing', '/', '/about', '/contact'] as $path) {
        foreach (msdLd($this->get($path)->assertOk()->getContent()) as $blob) {
            foreach ($refused as $type) {
                Assert::assertStringNotContainsString($type, $blob,
                    "[{$path}] emits {$type} structured data — it needs a value this repository does not have");
            }
        }
    }
});

// ═════ A8 (2026-11-11, his go): the types whose values are REAL ship once the site's address is a real public one ═════

it('⚠️ on a real public address the home page carries Organization + WebSite, with the site\'s own address and the brand mark that is the favicon', function () {
    config(['app.url' => 'https://vyrobel.com']);

    $blocks = msdLd($this->get('/')->assertOk()->getContent());
    Assert::assertCount(1, $blocks);
    $ld = json_decode($blocks[0], true, 512, JSON_THROW_ON_ERROR);
    expect($ld['@context'])->toBe('https://schema.org');

    $byType = collect($ld['@graph'])->keyBy('@type');
    expect($byType->keys()->all())->toBe(['Organization', 'WebSite'])
        ->and($byType['Organization']['name'])->toBe('Vyrobel')
        ->and($byType['Organization']['url'])->toBe('https://vyrobel.com/')
        ->and($byType['Organization']['logo'])->toBe('https://vyrobel.com/favicon.svg')
        ->and($byType['WebSite']['url'])->toBe('https://vyrobel.com/')
        ->and($byType['WebSite']['publisher'])->toBe(['@id' => $byType['Organization']['@id']]);
    expect(is_file(public_path('favicon.svg')))->toBeTrue();   // the logo points at a file that exists

    // nothing invented: no contact point, no social profile, no search action, no founding date
    foreach (['contactPoint', 'sameAs', 'potentialAction', 'foundingDate', 'telephone', 'email', 'address'] as $invented) {
        Assert::assertStringNotContainsString('"'.$invented.'"', $blocks[0], "structured data invents \"{$invented}\"");
    }
});

it('⚠️ on a real public address every other indexable marketing page carries a BreadcrumbList on that address — and /pricing still carries its FAQ besides', function () {
    config(['app.url' => 'https://vyrobel.com']);

    $blocks = msdLd($this->get('/pricing')->assertOk()->getContent());
    Assert::assertCount(2, $blocks, '/pricing: the FAQ and the breadcrumb');
    $types = array_map(fn (string $b): string => json_decode($b, true, 512, JSON_THROW_ON_ERROR)['@type'], $blocks);
    expect($types)->toContain('FAQPage')->toContain('BreadcrumbList');

    $crumbs = json_decode($blocks[array_search('BreadcrumbList', $types, true)], true, 512, JSON_THROW_ON_ERROR);
    expect($crumbs['itemListElement'])->toBe([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://vyrobel.com/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Pricing', 'item' => 'https://vyrobel.com/pricing'],
    ]);
});

it('⚠️⚠️ NO address that is not a public site ever reaches structured data — localhost, an IP, http, .test, a staging suffix', function () {
    foreach (['http://localhost', 'http://localhost:8000', 'https://127.0.0.1', 'https://10.1.2.3', 'http://vyrobel.com', 'https://vyrobel.test', 'https://stage.vyrobel.local', 'https://intranet', ''] as $bad) {
        config(['app.url' => $bad]);
        expect(StructuredData::siteUrl())->toBeNull($bad)
            ->and(StructuredData::graph('/'))->toBeNull()
            ->and(StructuredData::graph('/pricing'))->toBeNull();
    }
    config(['app.url' => 'https://vyrobel.com/']);   // a trailing slash is not part of the address
    expect(StructuredData::siteUrl())->toBe('https://vyrobel.com');
    // a page that is not indexed says nothing
    expect(StructuredData::graph('/pricing', index: false))->toBeNull();
});

it('⚠️⚠️ Product, Offer, SoftwareApplication and a rating stay REFUSED even on a real address — the price is a placeholder and there are no reviews', function () {
    config(['app.url' => 'https://vyrobel.com']);
    expect(MarketingPricing::isPlaceholder())->toBeTrue();   // when a real price is configured this stops being the reason and somebody has to think again

    foreach (['/', '/pricing', '/about', '/contact', '/product', '/features'] as $path) {
        $page = $this->get($path);
        if ($page->status() !== 200) {
            continue;
        }
        foreach (msdLd($page->getContent()) as $blob) {
            // judged over the STRUCTURE (a breadcrumb may be NAMED "Product" — the page is called that — without being a Product)
            $types = [];
            $keys = [];
            $walk = function (mixed $node) use (&$walk, &$types, &$keys): void {
                if (! is_array($node)) {
                    return;
                }
                foreach ($node as $k => $v) {
                    if ($k === '@type') {
                        array_push($types, ...(array) $v);
                    } elseif (is_string($k)) {
                        $keys[] = $k;
                    }
                    $walk($v);
                }
            };
            $walk(json_decode($blob, true, 512, JSON_THROW_ON_ERROR));

            foreach (['Product', 'Offer', 'SoftwareApplication', 'AggregateRating', 'Review', 'Rating'] as $type) {
                Assert::assertNotContains($type, $types, "[{$path}] declares a {$type} — there is no real price or rating to say");
            }
            foreach (['price', 'priceCurrency', 'offers', 'aggregateRating', 'review', 'ratingValue'] as $prop) {
                Assert::assertNotContains($prop, $keys, "[{$path}] declares \"{$prop}\" — there is no real price or rating to say");
            }
        }
    }
});
