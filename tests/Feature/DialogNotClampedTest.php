<?php

use App\Livewire\Operator\RatesGrid;
use App\Models\RateSeason;
use App\Support\Property\PropertyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * F-37 — A DIALOG NESTED INSIDE A CARD IS A BROKEN MODAL.
 *
 * 🔬 MEASURED IN THE BROWSER (FIX 16): every `.vy-card` in the product computes
 * `backdrop-filter: blur(22px) saturate(1.15)`, and a backdrop-filter creates a CONTAINING BLOCK for
 * `position: fixed`. So a dialog overlay nested inside a card resolves against the CARD, not the viewport:
 * the backdrop stops covering the page, an outside click cannot reach it, and at some scroll offsets the
 * panel lands off-screen (measured: panel bottom 861 in an 812px viewport — 49px below the fold).
 *
 * ⚠️⚠️ THIS TEST IS A PROXY AND IS HONEST ABOUT BEING ONE. The real property is geometric — "no fixed
 * overlay has a containing-block ancestor" — and that is a RUNTIME fact about computed styles, which no
 * PHP test can see. This repo has no re-runnable browser suite (the Playwright work is MCP-driven and is
 * not in the regression net), so the geometry itself is proven per-pass in the browser and recorded.
 *
 * What this CAN check, on REAL RENDERED HTML rather than Blade source, is the structural shape that
 * produced every instance found: a dialog overlay with a `.vy-card` ancestor.
 *
 * ⚠️ WHAT IT WOULD MISS: a dialog clamped by something that is not a card — a `filter`, a `perspective`,
 * a `will-change`, a `contain: paint`. 🔬 The FIX 16 sweep across 17 screens and 66 rendered dialog
 * instances found NO such case: `.vy-card` was the only clamping container in the product. That is why
 * the proxy is worth having; it is not why it is sufficient.
 */

/** Every dialog in $html whose OVERLAY sits inside a `.vy-card`. */
function clampedDialogsIn(string $html): array
{
    $doc = new DOMDocument;
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();

    $bad = [];
    foreach ((new DOMXPath($doc))->query('//*[@role="dialog"]') as $panel) {
        // The overlay is the panel's parent (the `position: fixed` element). Walk up from THERE:
        // the panel itself carries `vy-card`, so starting at the panel would match every dialog.
        $node = $panel->parentNode?->parentNode;
        while ($node instanceof DOMElement) {
            if (str_contains((string) $node->getAttribute('class'), 'vy-card')) {
                $bad[] = (string) $panel->getAttribute('aria-labelledby');
                break;
            }
            $node = $node->parentNode;
        }
    }

    return $bad;
}

it('⚠️ no dialog on the rates screen sits inside a card — F-37 (the apply dialog) is the instance', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, [], 'Hotel Manager');
    setPermissionsTeamId($s['org']->id);
    $propertyId = (string) $s['property']->getKey();
    app(PropertyContext::class)->activate([$propertyId], $propertyId);

    // A DRAFT season, so the Apply/Edit/Delete dialogs actually render. Writes no prices.
    RateSeason::create([
        'property_id' => $propertyId,
        'rate_plan_id' => (string) $s['ratePlan']->getKey(),
        'name' => 'Guard probe', 'starts_on' => '2026-10-05', 'ends_on' => '2026-10-09',
        'price_minor' => 14000, 'currency' => 'EUR',
    ]);

    $html = Livewire\Livewire::actingAs($operator)
        ->test(RatesGrid::class)
        ->html();

    // NON-VACUITY: if the screen rendered no dialogs at all, "none are clamped" would be meaningless.
    $doc = new DOMDocument;
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    $total = (new DOMXPath($doc))->query('//*[@role="dialog"]')->length;

    expect($total)->toBeGreaterThan(3);           // apply + edit + delete + the bulk/unit dialogs
    expect(clampedDialogsIn($html))->toBe([]);
});

it('⚠️ the check is not vacuous — a dialog wrapped in a card IS caught', function () {
    // The exact shape F-37 had: overlay inside `.vy-card`, panel inside the overlay.
    $bad = '<div class="vy-card vy-fade"><div style="position:fixed"><div class="vy-card" role="dialog" aria-labelledby="x"><h2 id="x">t</h2></div></div></div>';
    expect(clampedDialogsIn($bad))->toBe(['x']);

    // …and the corrected shape is not flagged.
    $good = '<div><div class="vy-card vy-fade">card</div><div style="position:fixed"><div class="vy-card" role="dialog" aria-labelledby="y"><h2 id="y">t</h2></div></div></div>';
    expect(clampedDialogsIn($good))->toBe([]);
});
