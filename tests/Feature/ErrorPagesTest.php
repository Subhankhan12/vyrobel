<?php

use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * BRANDED ERROR PAGES (audit 2026-08-07 · F6-8).
 *
 * `resources/views/errors/` did not exist, so every realm — operator, owner, guest, storefront, admin —
 * served Laravel's stock unbranded error page. Six views now share one self-contained branded layout.
 *
 * ⚠️ THE LAYOUT NEVER PRINTS THE EXCEPTION — no message, no trace, no SQL, no file path. This file pins that,
 * because "no stack trace in production" is exactly the kind of property that quietly regresses.
 */

it('renders a branded page for every error code, and leaks nothing', function (int $code) {
    $html = view("errors.{$code}")->render();

    expect($html)->toContain("Error {$code}");
    expect($html)->toContain('Vyrobel');

    // No debug artefact of any kind.
    foreach (['Stack trace', 'vendor/laravel', 'SQLSTATE', 'Whoops', '.php:'] as $leak) {
        expect($html)->not->toContain($leak);
    }

    // Self-contained: an error page must render when the asset pipeline or the network is the broken thing.
    expect($html)->not->toContain('fonts.googleapis.com');
    expect($html)->not->toContain('@vite');
})->with([404, 403, 419, 429, 500, 503]);

it('serves the branded 404 on a real missing route', function () {
    $response = $this->get('/no-such-page-here');

    $response->assertStatus(404);
    $response->assertSee('We couldn’t find that', false);
    $response->assertDontSee('Stack trace');
});

it('serves the branded 403 when a permission gate refuses', function () {
    // Reuses the GATE 3 gate: Housekeeping holds no guests.view, so the CRM is refused.
    $s = seedScenario(1);
    $housekeeper = operatorUser($s, ['name' => 'Rosa Housekeeper'], 'Housekeeping');

    $response = $this->actingAs($housekeeper)->get('/app/guests');

    $response->assertStatus(403);
    $response->assertSee('You don’t have access to this', false);
});

it('points the user back to their OWN realm', function () {
    // A guest sent to the operator dashboard would be a dead end — and a realm confusion.
    //
    // ⚠️⚠️ AND "their own realm" NOW MEANS A DESTINATION THAT WORKS (F-47, FIX 29). This asserted
    // "Back to your trips" unconditionally — correct for a SIGNED-IN guest, and itself a dead end for the
    // signed-out one, who has no account to return to. That is the exact failure this test's own comment
    // warns about, encoded in its expected value. The destination now follows the SESSION, so both branches
    // are pinned here rather than one.
    $this->get('/guest/nope')->assertSee('Access your bookings', false);   // signed out → the claim door
    $this->get('/no-such-page-here')->assertSee('Back to safety', false);
});

it('sends a SIGNED-IN guest back to their trips', function () {
    // ⚠️ THE CONTROL FOR THE BRANCH ABOVE — without it, a fix that sent everyone to the claim door would
    // pass while breaking the realm's real home.
    $s = seedScenario(1);
    $guest = Guest::withoutGlobalScopes()->whereKey($s['guest']->id)->first();
    $guest->forceFill(['password' => 'claimed-Passw0rd!', 'email_verified_at' => now()])->save();

    $this->actingAs($guest, 'guest')->get('/guest/nope')->assertSee('Back to your trips', false);
});
