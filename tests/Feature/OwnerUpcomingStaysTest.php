<?php

use App\Models\Guest;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\OwnerAttributionService;
use App\Support\Owner\OwnerUpcomingStays;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// Owner Overview · "Upcoming stays" (Owner-Overview exact-match · GATE 2). Locks the shared feed's guarantees:
// only the owner's OWN next-30-days future arrivals; guest NAME only (#28 — never email/phone); the owner NET
// reconciles through the shared attribution; real source/status; fail-closed on an empty/foreign unit set.

/** A future arrival on the given unit, at $checkIn (+2 nights), source $source, with a distinctive guest. */
function upcomingBooking(array $s, Unit $unit, string $checkIn, string $source, string $first, string $last, array $guestExtra = []): Reservation
{
    $checkOut = Carbon::parse($checkIn)->addDays(2)->toDateString();
    $guest = Guest::create(['first_name' => $first, 'last_name' => $last, ...$guestExtra]);

    return app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $checkIn, 'check_out' => $checkOut, 'lead_guest_id' => $guest->id, 'source' => $source],
        [['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'unit_id' => (string) $unit->id, 'check_in' => $checkIn, 'check_out' => $checkOut, 'price_minor' => 20000, 'currency' => 'EUR']],
    );
}

it('shows only the owner’s own next-30-days arrivals — name-only, net reconciled, real source/status', function () {
    Carbon::setTestNow('2026-08-20 12:00:00');   // horizon = 2026-09-19

    $s = seedScenario(1);
    $unit = $s['units'][0];

    // The owner + agreement (15% commission) — the unit belongs to them.
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-08-01', '2026-10-31');

    // Two IN-window future arrivals (one attributed, one not), + one beyond 30 days + one in the past.
    $b1 = upcomingBooking($s, $unit, '2026-09-01', 'direct', 'Lars', 'Andersen', ['email' => 'l.andersen@icloud.test', 'phone' => '+4520551804']);
    upcomingBooking($s, $unit, '2026-09-10', 'corporate', 'Sofia', 'Marchetti');
    upcomingBooking($s, $unit, '2026-10-05', 'direct', 'Owen', 'October');      // beyond the 30-day horizon
    upcomingBooking($s, $unit, '2026-08-01', 'direct', 'Petra', 'Past');        // already in the past

    // Attribute only b1 → a real owner net (€200 room · 15% commission → €170.00 net).
    postRoom($b1, 20000);
    app(OwnerAttributionService::class)->attributeReservation($b1);

    $feed = OwnerUpcomingStays::forOwnerUnits([(string) $unit->id], 'EUR');

    // 30-day clip + non-terminal only: exactly the two September arrivals, earliest first.
    expect($feed['count'])->toBe(2);
    expect($feed['rows'][0]['guest'])->toBe('Lars Andersen');       // NAME (name-only, #28)
    expect($feed['rows'][0]['value_display'])->toBe('€170.00');     // real owner NET (attributed)
    expect($feed['rows'][0]['source_key'])->toBe('direct');
    expect($feed['rows'][0]['status_key'])->toBe('confirmed');
    expect($feed['rows'][1]['guest'])->toBe('Sofia Marchetti');
    expect($feed['rows'][1]['value_display'])->toBe('Not yet attributed');   // no ledger yet → honest, not €0 (#39)

    // The October + past guests are absent.
    $blob = json_encode($feed);
    expect($blob)->not->toContain('October')->and($blob)->not->toContain('Petra');

    // #28 — guest email/phone are NEVER in the feed (name-only).
    expect($blob)->not->toContain('l.andersen@icloud.test')->and($blob)->not->toContain('4520551804');

    // Real source/status option counts (present-only, sorted).
    expect(collect($feed['sources'])->pluck('count', 'key')->all())->toBe(['corporate' => 1, 'direct' => 1]);
    expect(collect($feed['statuses'])->pluck('count', 'key')->all())->toBe(['confirmed' => 2]);

    // Net total reconciles: Σ attributed net over the shown stays == b1's €170.00.
    expect($feed['net_total_display'])->toBe('€170.00');

    Carbon::setTestNow();
});

it('fails closed for an empty or foreign unit set (owner isolation)', function () {
    Carbon::setTestNow('2026-08-20 12:00:00');

    $s = seedScenario(1);
    $unit = $s['units'][0];
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-08-01', '2026-10-31');
    upcomingBooking($s, $unit, '2026-09-01', 'direct', 'Lars', 'Andersen');

    // No units → nothing (a non-linked owner). A foreign/unknown unit id → nothing (the feed only ever reads
    // the ids it is given, which upstream come from the owner-scoped units()).
    expect(OwnerUpcomingStays::forOwnerUnits([], 'EUR')['count'])->toBe(0);
    expect(OwnerUpcomingStays::forOwnerUnits(['01JZZZZZZZZZZZZZZZZZZZZZZZ'], 'EUR')['count'])->toBe(0);

    Carbon::setTestNow();
});
