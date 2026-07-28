<?php

use App\Exceptions\Booking\InsufficientAvailabilityException;
use App\Models\Membership;
use App\Models\RatePlan;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// OWNER UNIT-STATUS (Wave C · GATE 3 · ⚠️ affects availability) — the owner PAUSES / RESUMES their OWN unit. A
// pause removes the unit's capacity through the certified availability recompute, so a paused unit GENUINELY
// takes no new bookings (mutation-proven — not a label); resume restores it. Owner-scoped.

/** An owner (portal user) with an EXCLUSIVELY-owned single-unit room type + a recomputed booking window. */
function unitStatusOwner(array $s): array
{
    $property = $s['property'];
    $rt = RoomType::create(['property_id' => $property->id, 'name' => 'Solo Suite', 'kind' => 'room']);
    $unit = Unit::create(['property_id' => $property->id, 'room_type_id' => $rt->id, 'code' => 'U-'.Str::upper(Str::random(4)), 'is_active' => true]);
    $plan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $rt->id, 'name' => 'Flex', 'currency' => 'EUR']);
    $rt->update(['default_rate_plan_id' => $plan->id]);

    $owner = ownUnit($unit);
    $user = User::factory()->create(['name' => 'Olive Owner']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner->update(['user_id' => $user->id]);

    // Publish availability across a forward window so bookings can land.
    $today = Carbon::today();
    app(AvailabilityService::class)->recompute($rt->id, $today->toDateString(), $today->copy()->addDays(40)->toDateString());

    return compact('owner', 'user', 'rt', 'unit', 'plan');
}

/** Book the room type over explicit dates through the certified path. */
function bookUnit(array $s, array $c, string $checkIn, string $checkOut): void
{
    app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $checkIn, 'check_out' => $checkOut, 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $c['rt']->id, 'unit_id' => $c['unit']->id, 'rate_plan_id' => $c['plan']->id, 'check_in' => $checkIn, 'check_out' => $checkOut, 'price_minor' => 15000, 'currency' => 'EUR']],
    );
}

// ── ⚠️ Pause genuinely stops new bookings; resume restores; existing stays are kept ────────────────
it('pauses a unit so it stops taking new bookings, and resumes it', function () {
    $s = seedScenario(1);
    $c = unitStatusOwner($s);
    $today = Carbon::today();
    $a = [$today->copy()->addDays(2)->toDateString(), $today->copy()->addDays(4)->toDateString()];   // baseline stay
    $b = [$today->copy()->addDays(10)->toDateString(), $today->copy()->addDays(12)->toDateString()];  // a later stay

    // Baseline: a booking succeeds while the unit is open.
    bookUnit($s, $c, $a[0], $a[1]);
    expect($c['unit']->fresh()->is_active)->toBeTrue();

    // ⚠️ PAUSE → the certified recompute removes the unit's capacity; a NEW booking is now REFUSED.
    $this->actingAs($c['user'])->post(route('owner.units.toggle-status', $c['unit']->id))->assertRedirect();
    expect($c['unit']->fresh()->is_active)->toBeFalse();
    expect(fn () => bookUnit($s, $c, $b[0], $b[1]))->toThrow(InsufficientAvailabilityException::class);

    // The EXISTING booking is untouched (append-only #7).
    expect(ReservationUnit::query()->where('unit_id', $c['unit']->id)->where('status', 'active')->count())->toBe(1);

    // RESUME → capacity restored; the same booking now succeeds.
    $this->actingAs($c['user'])->post(route('owner.units.toggle-status', $c['unit']->id))->assertRedirect();
    expect($c['unit']->fresh()->is_active)->toBeTrue();
    bookUnit($s, $c, $b[0], $b[1]);
    expect(ReservationUnit::query()->where('unit_id', $c['unit']->id)->where('status', 'active')->count())->toBe(2);
});

// ── Owner isolation — one owner cannot pause another owner's unit ──────────────────────────────────
it('never lets one owner pause another owner\'s unit', function () {
    $s = seedScenario(1);
    $a = unitStatusOwner($s);
    $b = unitStatusOwner($s);

    // Owner B tries to pause A's unit → owner-scoped 404; A's unit stays active.
    $this->actingAs($b['user'])->post(route('owner.units.toggle-status', $a['unit']->id))->assertNotFound();
    expect($a['unit']->fresh()->is_active)->toBeTrue();
});
