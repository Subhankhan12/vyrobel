<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Batch-A · GATE 1 — the operator sidebar now mirrors <x-owner.shell>'s filter: a role-gated nav item
// whose route resolves to null (the acting role lacks the permission) is DROPPED, not shown as a
// misleading "Coming soon" for a page that already works for the org admin. This locks the NAV-VISIBILITY
// guarantee — the pages still exist and still 403 at the route (nav visibility ≠ authorization).

it('hides role-gated nav items from a role lacking the permission, but keeps them for the org admin', function () {
    $s = seedScenario(1);

    // The org admin (Property Owner — every permission) SEES the sensitive pages in the sidebar…
    $this->actingAs(operatorUser($s, ['name' => 'Ada Admin'], 'Property Owner'))
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Roles & access')      // users.manage — the org admin holds it
        ->assertSee('Dashboard');

    // …while a Hotel Manager (no users.manage / properties.manage / billing.manage) does NOT: the item is
    // filtered out entirely, never rendered as a dead "Coming soon" for a page that works for an admin.
    $this->actingAs(operatorUser($s, ['name' => 'Max Manager'], 'Hotel Manager'))
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Dashboard')            // a page the role CAN reach still renders
        ->assertDontSee('Roles & access');  // the gated page is absent from the nav (not "Coming soon")
});

it('still 403s the gated page at the route — the page exists, the nav just hides it', function () {
    $s = seedScenario(1);

    // The admin can reach the real, built page…
    $this->actingAs(operatorUser($s, ['name' => 'Ada Admin'], 'Property Owner'))
        ->get('/app/roles')->assertOk();

    // …and the Hotel Manager is correctly refused at the route (the nav change removed a misleading label,
    // it did NOT change authorization — the route gate still holds).
    $this->actingAs(operatorUser($s, ['name' => 'Max Manager'], 'Hotel Manager'))
        ->get('/app/roles')->assertForbidden();
})->group('security');
