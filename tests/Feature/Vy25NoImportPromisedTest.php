<?php

declare(strict_types=1);

// ⚠️⚠️ VY-25 (audit of 2026-11-24, read in code) — THERE IS NO IMPORT OF ANY KIND, AND THE SITE PROMISED ONE.
//
// No file import exists for bookings, guests, units or statements. The founder chose (2026-11-25) to correct the pages rather than build
// one now: "Correct the pages (Recommended)". The Switching page already said "There is no file import yet" — beside a step that read
// "you send an export … or we pull it through their API … you get a reconciliation sheet", and a list that promised "up to 24 months of
// historical statements". Every such sentence is now the real, manual process.
//
// Guarded at the RENDERED layer (`mkPage()`), as VY-09 is: a correction in a design master or the converter says nothing about what a
// visitor is shown. ⚠️ NOT NEGATED, REMOVED — and where a sentence replaced the promise, it says the true thing.

/** @return array<string, array{0: string, 1: list<string>, 2: list<string>}> page → [key, gone, present] */
dataset('vy25 import promises', [
    'how it works — the step' => ['how-it-works', ['import what exists', 'from a CSV or a channel export', 'We import your existing reservations', 'Importing 128 bookings'], ['there is no file import yet']],
    'how it works — the FAQ (and its structured data)' => ['how-it-works', ['We import your existing reservations with their dates'], ['entered by hand: there is no file import yet']],
    'switching — what comes across' => ['switching', ['Reconciled line by line against your old system before cutover', 'past stays, notes and preferences', 'up to 24 months of historical statements'], ['there is no file import yet', 'there is no import of guest history yet', 'past statements stay in your old system']],
    'switching — week one' => ['switching', ['Export, import, and a reconciliation you can read', 'we pull it through their API', 'You get a reconciliation sheet', 'one export, one hour reviewing the sheet'], ['Your open bookings, entered and checked']],
    'switching — the owners question' => ['switching', ['We import two years of history'], ['Past statements are not brought across']],
    'for apartments' => ['for-apartments', ["We'll import the bookings"], ['there is no file import yet']],
    'home' => ['home', ['Import your existing bookings'], ['Enter your open bookings']],
    'the example scenarios' => ['case-studies', ['They imported the forward book'], ['entered the forward book by hand']],
    'the villa scenario' => ['story-cyclades', ['two years of owner statements imported', 'wrong in the import'], ['Open reservations were entered in the first week']],
    'the city-hotel scenario' => ['story-lisbon', ['the import had flattened', 'The group booking import'], ['had been entered as individual reservations']],
]);

it('⚠️⚠️ VY-25: the page no longer promises an import — and says how bookings really come across', function (string $key, array $gone, array $present) {
    $page = html_entity_decode(mkPage($key), ENT_QUOTES | ENT_HTML5);
    $page = str_replace(["\u{2019}", '\\\''], "'", $page);

    foreach ($gone as $words) {
        expect($page)->not->toContain($words);
    }
    foreach ($present as $words) {
        expect($page)->toContain($words);
    }
})->with('vy25 import promises');

it('VY-25: no marketing page says it imports, imported, or is importing bookings, guests, statements or reservations', function () {
    // A sweep over every page the router serves, beside the exact list above: a NEW sentence that promises an import fails here too.
    $offenders = [];
    foreach (['home', 'product', 'features', 'modules', 'how-it-works', 'pricing', 'switching', 'for-hotels', 'for-apartments', 'for-villas', 'case-studies', 'story-cyclades', 'story-lisbon', 'story-rotterdam', 'integrations', 'security', 'about', 'contact'] as $key) {
        $text = strip_tags(html_entity_decode(mkPage($key), ENT_QUOTES | ENT_HTML5));
        if (preg_match_all('/\b(?:we|they|you can)\s+import\b[^.]*|\bimport(?:ed|ing)?\s+(?:your\s+|the\s+|\d+\s+)?(?:existing\s+|open\s+)?(?:bookings|reservations|guests|statements|history)\b[^.]*/i', $text, $m)) {
            foreach ($m[0] as $hit) {
                $offenders[] = $key.': '.trim($hit);
            }
        }
    }

    expect($offenders)->toBe([]);
});
