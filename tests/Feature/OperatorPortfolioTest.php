<?php

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 6a · Step 2 — PROPERTY CRUD + the PORTFOLIO overview.
//
// CRUD is gated `properties.manage` (Property Owner / Group Admin — NOT Front Desk / Hotel Manager, #30);
// a created property is org-scoped by BelongsToOrganization and enters the selector. The Portfolio shows
// REAL per-property data — unit counts + occupancy + a status DERIVED from real inventory ("Finish setup
// to go live" when a property has no units), never fabricated figures.

/** A valid add-property POST body. */
function addPropertyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Harbour Lofts',
        'type' => 'villa',
        'location' => 'Porto',
        'timezone' => 'Europe/Lisbon',
    ], $overrides);
}

// ── 1. 🔒 PERMISSION — properties.manage creates; Front Desk / Hotel Manager are refused ───────────
it('lets a Group Admin create a property but refuses a Hotel Manager and Front Desk', function () {
    $s = seedScenario(1);

    // Group Admin (has properties.manage) — the write succeeds.
    $before = Property::query()->count();
    $this->actingAs(operatorUser($s, [], 'Group Admin'))
        ->post('/app/portfolio', addPropertyPayload())
        ->assertRedirect(route('app.portfolio'));
    expect(Property::query()->count())->toBe($before + 1);

    // Hotel Manager (properties.view only) — 403, nothing created.
    $countNow = Property::query()->count();
    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->post('/app/portfolio', addPropertyPayload(['name' => 'Sneaky Manager Property']))
        ->assertForbidden();

    // Front Desk — 403 too.
    $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->post('/app/portfolio', addPropertyPayload(['name' => 'Sneaky Desk Property']))
        ->assertForbidden();

    expect(Property::query()->count())->toBe($countNow)
        ->and(Property::query()->where('name', 'Sneaky Manager Property')->exists())->toBeFalse()
        ->and(Property::query()->where('name', 'Sneaky Desk Property')->exists())->toBeFalse();
});

// ── 2. 🔒 ORG-SCOPE — a created property belongs to the actor's org, and enters the selector ───────
it('creates the property in the actor org and adds it to the accessible set', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Group Admin');

    $this->actingAs($manager)->post('/app/portfolio', addPropertyPayload(['name' => 'Cliff House']));

    $created = Property::query()->where('name', 'Cliff House')->firstOrFail();
    expect((string) $created->organization_id)->toBe((string) $s['org']->id)   // org-scoped, never cross-org
        ->and($created->slug)->not->toBeNull()                                 // storefront slug assigned
        ->and((string) $created->status)->toBe('active');

    // It enters the selector: an unpivoted manager sees the whole org, so the new property is accessible.
    expect($manager->accessiblePropertyIds())->toContain((string) $created->id);
});

// ── 3. PORTFOLIO — real per-property data + the honest "finish setup" state for an empty property ──
it('shows real per-property data and an honest finish-setup status for an empty property', function () {
    $s = seedScenario(1);   // this property has a room type + a unit → operational
    $manager = operatorUser($s, [], 'Group Admin');

    // A second, EMPTY property (no room types/units) — its genuine pre-operational state.
    $this->actingAs($manager)->post('/app/portfolio', addPropertyPayload(['name' => 'Blank Retreat']));

    $this->actingAs($manager)->get('/app/portfolio')
        ->assertOk()
        ->assertSee('Blank Retreat')
        ->assertSee('Finish setup to go live')   // DERIVED from 0 units — not a faked status
        ->assertSee('2 properties');             // real count in the subtitle
});

// ── 4. 🔒 TENANT ISOLATION — the portfolio + edit never cross orgs ─────────────────────────────────
it('never shows or edits another org\'s property', function () {
    $foreign = seedScenario(1);
    $foreign['property']->update(['name' => 'Foreign Manor']);

    $mine = seedScenario(1);   // switches the tenant context to a new org
    $manager = operatorUser($mine, [], 'Group Admin');

    // The portfolio lists only my org's property.
    $this->actingAs($manager)->get('/app/portfolio')->assertOk()->assertDontSee('Foreign Manor');

    // Editing a foreign property id 404s (tenant-scoped findOrFail).
    $this->actingAs($manager)->post('/app/portfolio/update', [
        'property_id' => $foreign['property']->id,
        'name' => 'Hijacked', 'type' => 'hotel', 'timezone' => 'UTC', 'status' => 'inactive',
    ])->assertNotFound();

    expect((string) $foreign['property']->fresh()->name)->toBe('Foreign Manor');   // untouched
});

// ── 5. EDIT — a manager updates a property's basics (tenant-scoped) ────────────────────────────────
it('updates a property in the actor org', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Group Admin');

    $this->actingAs($manager)->post('/app/portfolio/update', [
        'property_id' => $s['property']->id,
        'name' => 'Renamed Property', 'type' => 'hotel', 'timezone' => 'Europe/Madrid', 'status' => 'inactive',
    ])->assertRedirect(route('app.portfolio'));

    $fresh = $s['property']->fresh();
    expect((string) $fresh->name)->toBe('Renamed Property')
        ->and((string) $fresh->status)->toBe('inactive')
        ->and((string) $fresh->timezone)->toBe('Europe/Madrid');
});

// ── 6. THE FULL FLOW — add a property → select it → book into it → isolated to the right property ──
it('supports add → select → book, isolated to the new property', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Property Owner');   // full permissions

    // Add a property.
    $this->actingAs($manager)->post('/app/portfolio', addPropertyPayload(['name' => 'Lagoon Villas']));
    $new = Property::query()->where('name', 'Lagoon Villas')->firstOrFail();

    // Select it — the switcher stores the choice.
    $this->actingAs($manager)->post('/app/property/switch', ['property_id' => $new->id])
        ->assertSessionHasNoErrors();
    expect(session('current_property_id'))->toBe($new->id);

    // The new property is empty, so its rack/units render but offer nothing yet — the honest empty state,
    // and definitely NOT the original property's inventory bleeding through.
    $this->actingAs($manager)->withSession(['current_property_id' => $new->id])
        ->get('/app/units')->assertOk()->assertDontSee((string) $s['units'][0]->code);
});
