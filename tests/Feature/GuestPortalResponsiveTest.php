<?php

use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * THE GUEST PORTAL MUST FIT A PHONE (audit 2026-08-07 — the responsiveness finding).
 *
 * The guest shell shipped with ZERO viewport breakpoints: its only @media block was
 * `prefers-reduced-motion`. `.vy-top-in` is a space-between flex and `.vy-nav` a non-wrapping
 * 476px flex row, so at 390px the header forced the document to 615px — a 225px horizontal
 * overflow on EVERY one of the 14 guest screens. Measured in a real 390px Chromium viewport.
 *
 * ⚠️ THIS IS THE GUEST REALM'S ONLY CHROME. One shell, 14 screens — which is why a single
 * missing breakpoint broke all of them at once, and why fixing it there fixes all of them.
 *
 * A CSS rule cannot be asserted by rendering alone, so this pins the three properties that
 * actually carry the fix. The measured proof (0px overflow on all 14 pages at 390px, 44px tap
 * targets) is recorded in the gate; this test is the guard that stops it silently coming back.
 */

it('ships a phone breakpoint and the overflow guard in the guest shell', function () {
    $css = file_get_contents(resource_path('views/components/guest/shell.blade.php'));

    // The breakpoint itself — its absence WAS the bug.
    expect($css)->toContain('@media (max-width: 640px)');

    // The belt-and-braces guard: no element may push the document wider than the screen.
    expect($css)->toMatch('/html,\s*body\s*\{[^}]*overflow-x:\s*hidden/');

    // The nav scrolls inside itself instead of widening the page — the specific 476px offender.
    expect($css)->toMatch('/\.vy-nav\s*\{[^}]*overflow-x:\s*auto/');

    // ⚠️ The reduced-motion block must SURVIVE the new breakpoint (it is an accessibility
    // guarantee, and adding a second @media is exactly when one gets clobbered).
    expect($css)->toContain('prefers-reduced-motion');
});

it('renders a real guest page inside the responsive shell', function () {
    $s = seedScenario(1);

    /** @var Guest $guest */
    $guest = $s['guest'];
    $guest->password = 'guest-password-1234';
    $guest->email_verified_at = now();
    $guest->save();

    // The rendered page must actually CARRY the breakpoint — a stale compiled view would not.
    $this->actingAs($guest->fresh(), 'guest')
        ->get('/guest/trips')
        ->assertOk()
        ->assertSee('max-width: 640px', escape: false)
        ->assertSee('width=device-width', escape: false);
});
