<?php

use App\Exceptions\Booking\BookingException;
use App\Models\DomainEvent;
use App\Models\Folio;
use App\Models\Group;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Services\AssignmentService;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OPERATOR GROUP BOOKING (Phase 1 · Sub-gate 1) — a block of rooms created ATOMICALLY over the EXISTING
// groups schema (extend, never parallel), through the single inventory write path
// (AssignmentService::createGroupBooking). The load-bearing guard is atomicity + NO phantom events: if any
// member is unavailable the WHOLE block rolls back and no reservation.confirmed event fires for the members
// processed before the failure (which would otherwise ARI-push + recompute availability for rows that vanish).

/** seedScenario + a real nightly rate over the seeded window, so every unit is genuinely bookable. */
function groupScenario(int $units = 3, int $nightlyMinor = 10000): array
{
    $s = seedScenario($units);
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-10', Money::of($nightlyMinor, 'EUR'));

    return $s;
}

/** Group header attributes for a direct AssignmentService::createGroupBooking() call. */
function groupAttrs(array $s, array $overrides = []): array
{
    return array_merge([
        'property_id' => $s['property']->id,
        'name' => 'Test Block',
        'occasion' => 'wedding',
        'billing_mode' => 'individual',
        'block_code' => 'GRP-TEST',
        'pickup_deadline' => null,
        'contact' => null,
    ], $overrides);
}

/**
 * Member specs (one reservation + line per unit index) over 2026-09-02→05 (3 nights), €300 each by default.
 *
 * @param  list<int>  $unitIndexes
 */
function groupMembers(array $s, array $unitIndexes, int $priceMinor = 30000, string $status = 'confirmed'): array
{
    return array_map(fn (int $i): array => [
        'reservation' => [
            'property_id' => $s['property']->id,
            'currency' => 'EUR',
            'check_in' => '2026-09-02',
            'check_out' => '2026-09-05',
            'adults' => 2,
            'children' => 0,
            'lead_guest_id' => $s['guest']->id,
            'status' => $status,
        ],
        'line' => [
            'room_type_id' => $s['roomType']->id,
            'unit_id' => $s['units'][$i]->id,
            'rate_plan_id' => $s['ratePlan']->id,
            'check_in' => '2026-09-02',
            'check_out' => '2026-09-05',
            'adults' => 2,
            'children' => 0,
            'price_minor' => $priceMinor,
            'currency' => 'EUR',
        ],
    ], $unitIndexes);
}

/** A valid group-booking POST payload (2 units, new organiser, confirmed, individual). */
function groupPayload(array $s, array $overrides = []): array
{
    return array_merge([
        'check_in' => '2026-09-02',
        'check_out' => '2026-09-05',
        'name' => 'Okafor–Bello Wedding',
        'occasion' => 'wedding',
        'units' => [$s['units'][0]->id, $s['units'][1]->id],
        'organiser_first_name' => 'Amara',
        'organiser_last_name' => 'Okafor',
        'organiser_company' => 'Okafor Events',
        'billing_mode' => 'individual',
        'hold_type' => 'confirmed',
        'adults' => '2',
        'children' => '0',
    ], $overrides);
}

// ── 1. THE WRITE IS REAL — a block creates the group + one member reservation per unit, atomically ──
it('creates a group + one member reservation per unit through the certified path', function () {
    $s = groupScenario(3);
    $frontDesk = operatorUser($s, ['name' => 'Fred Front-Desk'], 'Front Desk');

    $this->actingAs($frontDesk)
        ->post('/app/group-booking', groupPayload($s))
        ->assertRedirect();   // PRG to the in-wizard done screen (?created=<group>)

    $group = Group::query()->firstOrFail();
    expect($group->name)->toBe('Okafor–Bello Wedding')
        ->and($group->occasion)->toBe('wedding')
        ->and($group->billing_mode)->toBe('individual')
        ->and((string) $group->block_code)->toStartWith('GRP-');

    $members = Reservation::query()->where('group_id', $group->id)->get();
    expect($members)->toHaveCount(2)
        ->and($members->pluck('source')->unique()->all())->toBe(['group'])
        ->and($members->pluck('status')->unique()->all())->toBe(['confirmed']);

    // Two units seated → inventory really consumed (the no-double-booking core ran for each member).
    expect(soldOn($s['roomType']->id, '2026-09-02'))->toBe(2)
        ->and(ReservationUnit::query()->whereIn('reservation_id', $members->pluck('id'))->count())->toBe(2);

    // Individual billing → a folio + a €300 room charge per member (integer minor units).
    foreach ($members as $member) {
        expect((int) $member->refresh()->total_minor)->toBe(30000);
    }
    expect(Guest::query()->where('last_name', 'Okafor')->exists())->toBeTrue();
});

// ── 2. ⚠️ ATOMICITY — an unavailable member rolls the WHOLE block back, with NO phantom events ─────
it('rolls the whole block back with no phantom reservation.confirmed events when any member is unavailable', function () {
    $s = groupScenario(3);
    // Pre-book unit[1] over the block dates → it is now unavailable for the group.
    book($s, ['unit_id' => $s['units'][1]->id, 'check_in' => '2026-09-02', 'check_out' => '2026-09-05']);

    $confirmedBefore = DomainEvent::query()->where('event_type', 'reservation.confirmed')->count();
    $groupEventsBefore = DomainEvent::query()->where('event_type', 'group.created')->count();
    $soldBefore = soldOn($s['roomType']->id, '2026-09-02');   // 1 — only the pre-booked unit

    // The block: unit[0] (free) + unit[1] (TAKEN) + unit[2] (free). unit[1] must abort the ENTIRE block.
    expect(fn () => app(AssignmentService::class)->createGroupBooking(groupAttrs($s), groupMembers($s, [0, 1, 2])))
        ->toThrow(BookingException::class);

    // NOTHING persisted — no group, no member reservations. And CRITICALLY: the members processed before
    // unit[1] failed left NO reservation.confirmed event and NO group.created event. (Record events inside the
    // loop instead of post-commit and these counts jump — this is the phantom-event guard, mutation-proven.)
    expect(Group::query()->count())->toBe(0)
        ->and(Reservation::query()->where('source', 'group')->count())->toBe(0)
        ->and(DomainEvent::query()->where('event_type', 'reservation.confirmed')->count())->toBe($confirmedBefore)
        ->and(DomainEvent::query()->where('event_type', 'group.created')->count())->toBe($groupEventsBefore)
        ->and(soldOn($s['roomType']->id, '2026-09-02'))->toBe($soldBefore);   // no group unit consumed inventory
});

// ── 3. NO DOUBLE-BOOKING via the HTTP path — a taken unit is refused, nothing created ─────────────
it('refuses the block and creates nothing when a chosen unit was just taken', function () {
    $s = groupScenario(3);
    $frontDesk = operatorUser($s, [], 'Front Desk');
    book($s, ['unit_id' => $s['units'][1]->id, 'check_in' => '2026-09-02', 'check_out' => '2026-09-05']);

    $this->actingAs($frontDesk)
        ->from(route('app.group-booking'))
        ->post('/app/group-booking', groupPayload($s, ['units' => [$s['units'][0]->id, $s['units'][1]->id]]))
        ->assertRedirect(route('app.group-booking', ['check_in' => '2026-09-02', 'check_out' => '2026-09-05']))
        ->assertSessionHas('group_notice');   // back() preserves the dates so the availability view isn't lost

    expect(Group::query()->count())->toBe(0)
        ->and(Reservation::query()->where('source', 'group')->count())->toBe(0);
});

// ── 4. MASTER INVOICE — one folio on the first member carries every member's charge ──────────────
it('bills a master-invoice block onto ONE folio (individual mode already billed each member in test 1)', function () {
    $s = groupScenario(3);
    $frontDesk = operatorUser($s, [], 'Front Desk');

    $this->actingAs($frontDesk)->post('/app/group-booking', groupPayload($s, ['billing_mode' => 'master']))
        ->assertRedirect();

    $group = Group::query()->where('billing_mode', 'master')->firstOrFail();
    $members = Reservation::query()->where('group_id', $group->id)->orderBy('created_at')->get();
    expect($members)->toHaveCount(2);

    // Exactly ONE folio for the block; it carries BOTH €300 charges (€600 total) — the organiser's one bill.
    $folios = Folio::query()->whereIn('reservation_id', $members->pluck('id'))->get();
    expect($folios)->toHaveCount(1)
        ->and((int) $folios->first()->total_minor)->toBe(60000);
    expect((int) $members->first()->refresh()->total_minor)->toBe(60000)   // master carries the whole block
        ->and((int) $members->last()->refresh()->total_minor)->toBe(0);    // the other owes nothing directly
});

// ── 5. TENTATIVE HOLD — members are tentative, the option deadline is stored on the group ─────────
it('creates a tentative block with a pickup deadline', function () {
    $s = groupScenario(3);
    $frontDesk = operatorUser($s, [], 'Front Desk');

    $this->actingAs($frontDesk)->post('/app/group-booking', groupPayload($s, [
        'hold_type' => 'tentative',
        'pickup_deadline' => '2026-08-25',
    ]))->assertRedirect();

    $group = Group::query()->firstOrFail();
    expect($group->pickup_deadline?->toDateString())->toBe('2026-08-25');

    $members = Reservation::query()->where('group_id', $group->id)->get();
    expect($members->pluck('status')->unique()->all())->toBe(['tentative']);
});

// ── 6. PERMISSION — the block is gated on reservations.create (route + control) ──────────────────
it('refuses a group booking to a role without reservations.create', function () {
    $s = groupScenario(3);
    $housekeeper = operatorUser($s, ['name' => 'Hana House-Keeping'], 'Housekeeping');

    $this->actingAs($housekeeper)->get('/app/group-booking')->assertForbidden();
    $this->actingAs($housekeeper)->post('/app/group-booking', groupPayload($s))->assertForbidden();

    expect(Group::query()->count())->toBe(0);
});

// ── GATE 4.1 — the group wizard exposes each unit's REAL capacity (units.max_occupancy) for the meter ──
it('exposes each unit real capacity for the group capacity meter', function () {
    $s = groupScenario(2);
    $s['units'][0]->update(['max_occupancy' => 5]);   // a distinctive REAL capacity
    $frontDesk = operatorUser($s, [], 'Front Desk');

    $this->actingAs($frontDesk)
        ->get('/app/group-booking?check_in=2026-09-02&check_out=2026-09-05')
        ->assertOk()
        ->assertSee('sleeps 5');   // the capacity meter reads real max_occupancy — never a fabricated number
});

// ── GATE 4.3 — the rooming list assigns a per-unit lead guest (real), and the name anonymizes (#28) ──
it('assigns a per-unit lead guest from the rooming list and erases the name on anonymize', function () {
    $s = groupScenario(2);
    $frontDesk = operatorUser($s, [], 'Front Desk');

    $this->actingAs($frontDesk)->post('/app/group-booking', groupPayload($s, [
        'units' => [$s['units'][0]->id, $s['units'][1]->id],
        'rooming' => [
            $s['units'][0]->id => 'Chidi Roomingsson',   // a distinct lead guest for unit 0
            $s['units'][1]->id => '',                     // blank → the organiser leads unit 1
        ],
    ]))->assertRedirect();

    $group = Group::query()->latest('created_at')->firstOrFail();
    $members = Reservation::query()->where('group_id', $group->id)->with('reservationUnits')->get();
    $rooming = Guest::query()->where('first_name', 'Chidi')->where('last_name', 'Roomingsson')->firstOrFail();

    // Unit 0's member leads with the rooming guest; unit 1's member leads with someone else (the organiser).
    $m0 = $members->first(fn (Reservation $m): bool => $m->reservationUnits->contains('unit_id', $s['units'][0]->id));
    $m1 = $members->first(fn (Reservation $m): bool => $m->reservationUnits->contains('unit_id', $s['units'][1]->id));
    expect((string) $m0->lead_guest_id)->toBe((string) $rooming->id)
        ->and((string) $m1->lead_guest_id)->not->toBe((string) $rooming->id);

    // PII (#28): the rooming lead is a normal Guest — anonymize() clears the name (no new free-text store).
    $rooming->anonymize();
    expect(trim(((string) $rooming->fresh()->first_name).((string) $rooming->fresh()->last_name)))->toBe('');
});

// ── GATE 4.4 — the in-wizard DONE screen renders the created block; a bogus id shows the wizard ──
it('renders the done screen with the block reference after a create', function () {
    $s = groupScenario(2);
    $frontDesk = operatorUser($s, [], 'Front Desk');
    $this->actingAs($frontDesk)->post('/app/group-booking', groupPayload($s))->assertRedirect();
    $group = Group::query()->latest('created_at')->firstOrFail();

    $this->actingAs($frontDesk)
        ->get(route('app.group-booking', ['created' => $group->id]))
        ->assertOk()
        ->assertSee('Block confirmed')
        ->assertSee((string) $group->block_code)
        ->assertSee('View on the rack');

    // A bogus/foreign created id → the normal wizard, never a foreign done card.
    $this->actingAs($frontDesk)
        ->get(route('app.group-booking', ['created' => '01JUNKJUNKJUNKJUNKJUNKJUNK', 'check_in' => '2026-09-02', 'check_out' => '2026-09-05']))
        ->assertOk()
        ->assertDontSee('Block confirmed');
});
