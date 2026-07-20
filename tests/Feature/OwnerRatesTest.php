<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\Restriction;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// OWNER RATES (Phase 2 · Sub-gate 3) — the phase's HIGHEST-RISK gate: an owner-scoped MONEY write via the
// certified RateAndRestrictionService (setRate/setRestriction).
//   • DIRECT edit persists at INTEGER minor units (175.57 → 17557, no float drift);
//   • READ isolation — an owner sees ONLY their own EXCLUSIVE room types (never another owner's rate);
//   • WRITE guard (mutation-proven) — an owner CANNOT set a rate on a room type they don't own exclusively
//     (another owner's, or one SHARED with the operator) — resolved through ownership, never a request id;
//   • per-room-type; owner-portal-gated (fail-closed for a non-linked user).

/**
 * An owner with their OWN, EXCLUSIVELY-owned room type (one unit, one rate plan) + a linked portal user.
 * seedScenario puts every unit in ONE shared room type, so a per-owner room type must be built explicitly.
 *
 * @return array{user: User, owner: Owner, unit: Unit, roomType: RoomType, plan: RatePlan}
 */
if (! function_exists('exclusiveOwner')) {
    function exclusiveOwner(array $s, string $name): array
    {
        $property = $s['property'];
        $rt = RoomType::create(['property_id' => $property->id, 'name' => $name, 'kind' => 'room']);
        $unit = Unit::create(['property_id' => $property->id, 'room_type_id' => $rt->id, 'code' => 'U-'.Str::upper(Str::random(4))]);
        $plan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $rt->id, 'name' => 'Flex', 'currency' => 'EUR']);
        $rt->update(['default_rate_plan_id' => $plan->id]);

        $owner = ownUnit($unit);   // owner + agreement + unit->owner_id
        $user = User::factory()->create(['name' => $name.' Owner']);
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner->update(['user_id' => $user->id]);

        return ['user' => $user, 'owner' => $owner, 'unit' => $unit, 'roomType' => $rt, 'plan' => $plan];
    }
}

/** The nightly rate (minor units) stored for a plan on a date, or null. */
function storedRateMinor(string $planId, string $date): ?int
{
    $row = RateCalendar::query()->where('rate_plan_id', $planId)->whereDate('date', $date)->first();

    return $row === null ? null : (int) $row->price_minor;
}

// ── 1. DIRECT rate write persists at INTEGER minor units (the 175.57 → 17557 guarantee) ──────────
it('sets a rate directly and stores it as integer minor units', function () {
    $s = seedScenario(1);
    $a = exclusiveOwner($s, 'Azure');

    $this->actingAs($a['user'])->post('/owner/rates/apply', [
        'room_type_id' => $a['roomType']->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-01', 'rate' => '175.57',
    ])->assertRedirect()->assertSessionHas('rate_notice');

    // 175.57 * 100 in IEEE-754 float is 17556.9999… — the string parser must land EXACTLY on 17557.
    expect(storedRateMinor((string) $a['plan']->id, '2026-10-01'))->toBe(17557)
        ->and(storedRateMinor((string) $a['plan']->id, '2026-10-02'))->toBeNull();   // half-open — only the one night
});

// ── 2. READ isolation — an owner sees ONLY their own room type's rate ─────────────────────────────
it('shows the owner only their own exclusive room types', function () {
    $s = seedScenario(1);
    $a = exclusiveOwner($s, 'Azure');
    $b = exclusiveOwner($s, 'Coralis');

    $this->actingAs($a['user'])->get('/owner/rates')
        ->assertOk()
        ->assertSee('Azure')          // A's own room type…
        ->assertDontSee('Coralis');   // …never B's
});

// ── 3. THE WRITE GUARD — an owner CANNOT set a rate on another owner's room type (mutation-proven) ─
it('rejects setting a rate on another owner’s room type and writes nothing', function () {
    $s = seedScenario(1);
    $a = exclusiveOwner($s, 'Azure');
    $b = exclusiveOwner($s, 'Coralis');

    // A tampers with the request to target B's room type → 403, and B's rate is untouched.
    $this->actingAs($a['user'])->post('/owner/rates/apply', [
        'room_type_id' => $b['roomType']->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-01', 'rate' => '999',
    ])->assertForbidden();

    expect(storedRateMinor((string) $b['plan']->id, '2026-10-01'))->toBeNull();
});

// ── 4. A SHARED room type (owner + operator units) is NOT editable — read AND write ───────────────
it('does not let an owner edit a room type they share with the operator', function () {
    $s = seedScenario(1);
    $a = exclusiveOwner($s, 'Azure');

    // A room type holding A's unit AND an operator-owned unit (owner_id null) → not exclusively A's.
    $shared = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Shared Studio', 'kind' => 'room']);
    $sharedPlan = RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $shared->id, 'name' => 'Flex', 'currency' => 'EUR']);
    $shared->update(['default_rate_plan_id' => $sharedPlan->id]);
    $aUnit2 = Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $shared->id, 'code' => 'U-SHRA']);
    $aUnit2->update(['owner_id' => $a['owner']->id]);
    Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $shared->id, 'code' => 'U-SHOP']);   // operator-owned (owner_id null)

    // Not shown…
    $this->actingAs($a['user'])->get('/owner/rates')->assertOk()->assertDontSee('Shared Studio');

    // …and not writable (403, nothing stored) — editing it would move the operator's price.
    $this->actingAs($a['user'])->post('/owner/rates/apply', [
        'room_type_id' => $shared->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-01', 'rate' => '150',
    ])->assertForbidden();

    expect(storedRateMinor((string) $sharedPlan->id, '2026-10-01'))->toBeNull();
});

// ── 5. Optional minimum stay persists via setRestriction ──────────────────────────────────────────
it('stores an optional minimum stay alongside the rate', function () {
    $s = seedScenario(1);
    $a = exclusiveOwner($s, 'Azure');

    $this->actingAs($a['user'])->post('/owner/rates/apply', [
        'room_type_id' => $a['roomType']->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-01', 'rate' => '200', 'min_stay' => '3',
    ])->assertRedirect()->assertSessionHas('rate_notice');

    $restriction = Restriction::query()->where('room_type_id', $a['roomType']->id)->whereDate('date', '2026-10-01')->first();
    expect($restriction)->not->toBeNull()->and((int) $restriction->min_stay)->toBe(3);
});

// ── 6. An invalid rate is rejected cleanly, nothing written ───────────────────────────────────────
it('rejects an invalid rate without writing', function () {
    $s = seedScenario(1);
    $a = exclusiveOwner($s, 'Azure');

    $this->actingAs($a['user'])->post('/owner/rates/apply', [
        'room_type_id' => $a['roomType']->id, 'start_date' => '2026-10-01', 'end_date' => '2026-10-01', 'rate' => 'not-a-price',
    ])->assertRedirect()->assertSessionHas('rate_error');

    expect(storedRateMinor((string) $a['plan']->id, '2026-10-01'))->toBeNull();
});

// ── 7. Owner-portal-gated — a non-linked user fails closed (nothing to price) ─────────────────────
it('fails closed for a non-linked user', function () {
    $s = seedScenario(1);
    exclusiveOwner($s, 'Azure');

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get('/owner/rates')
        ->assertOk()->assertSee('don’t have any room types to price');
});
