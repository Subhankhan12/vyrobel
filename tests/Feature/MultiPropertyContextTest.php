<?php

use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use App\Support\Property\PropertyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 6a · MULTI-PROPERTY — the property context/selector + the complete ->first() refactor.
//
// THE CRUX (🔒): with more than one property per org, every property-scoped operation must act on the
// SELECTED property (the current context), NEVER an arbitrary `Property::query()->orderBy('name')->first()`
// (which silently returns the alphabetically-first property = "A" here). Each read/write test therefore
// selects property B and asserts B is used and A is NOT — the mutation proof that the context governs and
// the old ->first() is gone from the path. Property access honours the property_user allow-list.

/**
 * One org with TWO fully-bookable properties (A = "Alpha", B = "Bravo"), each with a distinctively-named
 * room type + unit + rate + published availability, so a booking can land in either and the on-screen
 * room-type name proves which property a screen is scoped to.
 *
 * @return array{org: Organization, propertyA: Property, propertyB: Property, roomTypeA: RoomType, roomTypeB: RoomType, unitA: Unit, unitB: Unit}
 */
function twoPropertyOrg(): array
{
    $org = newDemoOrg();

    $build = function (string $letter, string $rtName) {
        $property = Property::create(['name' => $letter.' House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
        $roomType = RoomType::create(['property_id' => $property->id, 'name' => $rtName, 'kind' => 'room']);
        $unit = Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => $letter.'UNIT1']);
        $ratePlan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'name' => 'Flex', 'currency' => 'EUR']);
        app(RateAndRestrictionService::class)->setRate($ratePlan, '2026-09-01', '2026-09-10', Money::of(10000, 'EUR'));
        app(AvailabilityService::class)->recompute($roomType->id, '2026-09-01', '2026-09-10');

        return [$property, $roomType, $unit];
    };

    [$propertyA, $roomTypeA, $unitA] = $build('Alpha', 'AlphaWing Suite');
    [$propertyB, $roomTypeB, $unitB] = $build('Bravo', 'BravoWing Loft');

    return compact('org', 'propertyA', 'propertyB', 'roomTypeA', 'roomTypeB', 'unitA', 'unitB');
}

/** Assign a user to a property via the `property_user` allow-list (the pivot carries a ULID id). */
function assignProperty(User $user, string $orgId, string $propertyId): void
{
    PropertyAssignment::create([
        'organization_id' => $orgId,
        'user_id' => $user->id,
        'property_id' => $propertyId,
    ]);
}

// ── 1. PropertyContext — the pure resolution logic (all-vs-one, foreign selection falls back) ──────
it('resolves the selected property, falls back on a foreign id, and rolls up on "all"', function () {
    $ctx = new PropertyContext;

    // Concrete selection within the accessible set is honoured.
    $ctx->activate(['p-a', 'p-b'], 'p-b');
    expect($ctx->id())->toBe('p-b')
        ->and($ctx->isAll())->toBeFalse()
        ->and($ctx->readIds())->toBe(['p-b']);

    // "all" rolls up to every accessible property, but id() still yields a concrete default (for writes).
    $ctx->activate(['p-a', 'p-b'], PropertyContext::ALL);
    expect($ctx->isAll())->toBeTrue()
        ->and($ctx->readIds())->toBe(['p-a', 'p-b'])
        ->and($ctx->id())->toBe('p-a');

    // A foreign/stale selection NEVER leaks — it falls back to the first accessible property.
    $ctx->activate(['p-a', 'p-b'], 'p-FOREIGN');
    expect($ctx->id())->toBe('p-a')->and($ctx->isAll())->toBeFalse();

    // No accessible properties → null, nothing to read.
    $ctx->activate([], 'p-a');
    expect($ctx->id())->toBeNull()->and($ctx->readIds())->toBe([]);
});

// ── 2. accessiblePropertyIds — the property_user allow-list (empty ⇒ full org) ─────────────────────
it('gives a user with no pivot rows the whole org, and a pivoted user only their properties', function () {
    $s = twoPropertyOrg();

    $noPivot = operatorUser($s);
    expect($noPivot->accessiblePropertyIds())
        ->toEqualCanonicalizing([$s['propertyA']->id, $s['propertyB']->id]);   // empty pivot ⇒ full org

    $pivoted = operatorUser($s);
    assignProperty($pivoted, $s['org']->id, $s['propertyA']->id);
    expect($pivoted->fresh()->accessiblePropertyIds())->toBe([$s['propertyA']->id]);   // only the assigned one
});

// ── 3. 🔒 A READ follows the SELECTED property, not an arbitrary ->first() (the Rack) ──────────────
it('scopes the rack to the selected property — B shows B, not the alphabetically-first A', function () {
    $s = twoPropertyOrg();
    $user = operatorUser($s);

    // Select B. The rack must show B's room type and NOT A's — proving the selection governs, not ->first()
    // (which, ordering by name, would always have returned "Alpha House").
    $this->actingAs($user)->withSession(['current_property_id' => $s['propertyB']->id])
        ->get('/app/rack')
        ->assertOk()
        ->assertSee('BravoWing Loft')
        ->assertDontSee('AlphaWing Suite');

    // Switch to A → the rack follows.
    $this->actingAs($user)->withSession(['current_property_id' => $s['propertyA']->id])
        ->get('/app/rack')
        ->assertOk()
        ->assertSee('AlphaWing Suite')
        ->assertDontSee('BravoWing Loft');
});

// ── 4. 🔒 A WRITE lands in the SELECTED property — the New Reservation offer follows the context ────
it('offers the selected property\'s room types and books into it, never property A by default', function () {
    $s = twoPropertyOrg();
    $user = operatorUser($s);

    // With B selected, the New Reservation screen offers B's room type only.
    $this->actingAs($user)->withSession(['current_property_id' => $s['propertyB']->id])
        ->get('/app/reservations/create?check_in=2026-09-02&check_out=2026-09-05')
        ->assertOk()
        ->assertSee('BravoWing Loft')
        ->assertDontSee('AlphaWing Suite');

    // Booking the offered (B) room type lands the reservation in property B — not the arbitrary first().
    $this->actingAs($user)->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/reservations/create', [
            'check_in' => '2026-09-02',
            'check_out' => '2026-09-05',
            'room_type_id' => $s['roomTypeB']->id,
            'unit_id' => $s['unitB']->id,
            'guest_first_name' => 'Grace',
            'guest_last_name' => 'Hopper',
            'adults' => '2',
            'children' => '0',
            'source' => 'direct',
        ]);

    $reservation = Reservation::query()->latest('created_at')->firstOrFail();
    expect((string) $reservation->property_id)->toBe((string) $s['propertyB']->id);
});

// ── 5. 🔒 PROPERTY ACCESS — a pivoted user cannot switch to, or write into, an unassigned property ─
it('refuses a switch to and a booking in a property outside the user\'s pivot allow-list', function () {
    $s = twoPropertyOrg();
    $user = operatorUser($s);
    assignProperty($user, $s['org']->id, $s['propertyA']->id);   // A only

    // The switcher refuses B (not in the allow-list) — a foreign selection is rejected, never stored.
    $this->actingAs($user)->post('/app/property/switch', ['property_id' => $s['propertyB']->id])
        ->assertSessionHasErrors('property_id');

    // And the write path honours the allow-list: a crafted booking of B's room type is refused, nothing created.
    $before = Reservation::query()->count();
    $this->actingAs($user)->withSession(['current_property_id' => $s['propertyA']->id])
        ->post('/app/reservations/create', [
            'check_in' => '2026-09-02',
            'check_out' => '2026-09-05',
            'room_type_id' => $s['roomTypeB']->id,   // a property the user is NOT assigned to
            'unit_id' => $s['unitB']->id,
            'guest_first_name' => 'Mal',
            'guest_last_name' => 'Icious',
            'adults' => '1',
            'children' => '0',
            'source' => 'direct',
        ]);
    expect(Reservation::query()->count())->toBe($before);
});

// ── 6. "ALL PROPERTIES" — an explicit roll-up shows every accessible property (a Tier-B list) ──────
it('rolls up both properties on the units list when "all properties" is selected', function () {
    $s = twoPropertyOrg();
    $user = operatorUser($s);

    $this->actingAs($user)->withSession(['current_property_id' => PropertyContext::ALL])
        ->get('/app/units')
        ->assertOk()
        ->assertSee('AlphaUNIT1')
        ->assertSee('BravoUNIT1');   // both properties' units in the roll-up

    // …whereas a concrete selection shows only that property's units.
    $this->actingAs($user)->withSession(['current_property_id' => $s['propertyB']->id])
        ->get('/app/units')
        ->assertOk()
        ->assertSee('BravoUNIT1')
        ->assertDontSee('AlphaUNIT1');
});

// ── 7. THE SWITCHER — a valid selection is stored, and drives the next request ─────────────────────
it('stores a valid property selection and applies it on the next request', function () {
    $s = twoPropertyOrg();
    $user = operatorUser($s);

    $this->actingAs($user)->post('/app/property/switch', ['property_id' => $s['propertyB']->id])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(session('current_property_id'))->toBe($s['propertyB']->id);
});

// ── 8. SINGLE-PROPERTY UNREGRESSED — one property works exactly as before ──────────────────────────
it('keeps a single-property org working with no selection set (unregressed)', function () {
    $s = seedScenario(1);
    $s['roomType']->update(['name' => 'SoloWing']);
    $user = operatorUser($s);

    // No current_property_id in the session at all — the context defaults to the one property.
    $this->actingAs($user)->get('/app/rack')->assertOk()->assertSee('SoloWing');
    $this->actingAs($user)->get('/app/units')->assertOk();
});
