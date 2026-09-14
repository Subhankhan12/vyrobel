<?php

declare(strict_types=1);

use App\Support\Marketing\MarketingPricing;
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

it('⚠️ claims no schema type this product cannot supply truthfully', function () {
    // ⚠️ Each of these needs a value that does not exist yet; see GO-LIVE-REQUIREMENTS.md for what each
    //    one is waiting on. Shipping an invalid schema is worse than none: it can be discarded whole
    //    while its other assertions are still read.
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
