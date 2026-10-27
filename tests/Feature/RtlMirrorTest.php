<?php

declare(strict_types=1);

use App\Support\I18n\Locales;
use App\Support\I18n\RtlCss;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;

uses(DatabaseTransactions::class);

// ⚠️⚠️ I18N-01 — Arabic and Persian read right to left (the founder's ruling: "Arabic and Persian should be RTL, and the design
// should be completely fine"). The page's own CSS is mirrored on the way out; an English page is never touched.

it('⚠️⚠️ mirrors sides the way rtlcss does — names, values, four-value shorthands, radii, gradients', function () {
    expect(RtlCss::flipDeclarations('margin-left:8px; padding:1px 2px 3px 4px'))->toBe('margin-right:8px; padding:1px 4px 3px 2px')
        ->and(RtlCss::flipDeclarations('text-align:left;float:right'))->toBe('text-align:right;float:left')
        ->and(RtlCss::flipDeclarations('border-radius:1px 2px 3px 4px'))->toBe('border-radius:2px 1px 4px 3px')
        ->and(RtlCss::flipDeclarations('left:0;right:auto'))->toBe('right:0;left:auto')
        ->and(RtlCss::flipDeclarations('border-left:1px solid red;border-top-left-radius:4px'))->toBe('border-right:1px solid red;border-top-right-radius:4px')
        ->and(RtlCss::flipDeclarations('background:linear-gradient(to right,#fff,#000)'))->toBe('background:linear-gradient(to left,#fff,#000)')
        ->and(RtlCss::flipDeclarations('inset:0 12px auto 4px !important'))->toBe('inset:0 4px auto 12px !important');
});

it('⚠️ leaves what has no side alone — symmetric shorthands, design tokens, selectors and media queries', function () {
    expect(RtlCss::flipDeclarations('margin: 0 auto'))->toBe('margin: 0 auto')
        ->and(RtlCss::flipDeclarations('--vy-side:left; color:red'))->toBe('--vy-side:left; color:red')
        ->and(RtlCss::flipStylesheet('@media (max-width:600px){.a:hover{margin-right:4px}} .left-rail{left:0}'))
        ->toBe('@media (max-width:600px){.a:hover{margin-left:4px}} .left-rail{right:0}');
});

it('⚠️⚠️ mirrors an Arabic page and leaves an English page byte for byte', function () {
    // Livewire auto-injects its styles into any HTML page once ANY test in the process has rendered a component (a static
    // it never resets) — that is Livewire, not the mirroring; off here so the byte-for-byte claim measures the mirroring only.
    config(['livewire.inject_assets' => false]);
    $html = '<html><head><style>.x{margin-left:4px}</style></head><body><div style="text-align:left">نص</div></body></html>';
    Route::middleware('web')->get('/__i18n/rtl-probe', fn () => response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']));

    $en = (string) test()->get('/__i18n/rtl-probe')->getContent();
    expect($en)->toBe($html, 'English page changed: '.json_encode(['app_locale' => app()->getLocale(), 'got' => mb_substr($en, 0, 400)]));   // English: untouched

    config(['app.available_locales' => ['en', 'ar']]);
    $ar = (string) test()->get('/__i18n/rtl-probe?lang=ar')->getContent();
    expect($ar)->toContain('.x{margin-right:4px}')->toContain('style="text-align:right"')
        ->and(Locales::htmlAttributes('ar'))->toBe('lang="ar" dir="rtl"')
        ->and(Locales::htmlAttributes('en'))->toBe('lang="en"');
});
