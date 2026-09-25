<?php

use App\Exceptions\Owner\OwnerBlockException;
use App\Exceptions\Owner\OwnerRateException;
use App\Exceptions\Owner\OwnerUnitPropertyRemovedException;
use App\Http\Controllers\Api\OwnerApiController;
use App\Models\InventoryBlock;
use App\Models\OwnerLedgerEntry;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\ChannelConnectionService;
use App\Services\OwnerBlockService;
use App\Services\OwnerPortalService;
use App\Services\OwnerRateService;
use App\Services\PropertyRemovalService;
use App\Support\Owner\OwnerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ══ GHOST UNITS — TWO CONCEPTS, TWO ACCESSORS ═════════════════════════════════════════════════════
//
// ⚠️⚠️ THE MEASUREMENT THAT SET THE DESIGN. Archiving a property soft-deletes ONE ROW and leaves every
// unit untouched, so an owner kept seeing units at a property their operator had removed. The obvious fix —
// filter `OwnerPortalService::units()` by a live property — was measured and REJECTED: that method is the
// ATTRIBUTION SET behind `OwnerBookingsReader::ledgerRows()`, Reports, Insights and the occupancy trend, and
// filtering it removed €300.00 of real earned owner revenue and a real completed stay from the owner's own
// Bookings page. The property was archived; the money was not.
//
// ⚠️ SO: `units()` keeps the ghosts (history is CORRECT), and `unitsAtLiveProperties()` serves listing and
// management. The first test below is the design guard — mutating the two accessors back into one turns it
// red, so nobody can "tidy" them together and quietly erase someone's earnings on a green suite.
//
//   ⛔ NOT CLAIMED HERE
//      · that search or the owner's subject-access export were changed. BOTH WERE DELIBERATELY LEFT — see
//        the report; an operator may legitimately need to find an archived unit, and hiding rows from a
//        data-protection response is the opposite of its purpose.
//      · that the owner is TOLD their property was archived. Nothing notifies them — reported, still open.

/** An owner with one unit on a LIVE property and one on a property that gets archived, with real money on it. */
function ghostWorld(): array
{
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $user = ownerActor($owner, $s);

    $gone = Property::create([
        'name' => 'Gone', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC',
        'slug' => 'gone-'.Str::lower(Str::random(6)), 'status' => 'active',
    ]);
    $rt = RoomType::create(['property_id' => $gone->id, 'name' => 'Gone Std', 'kind' => 'room']);
    RatePlan::create(['property_id' => $gone->id, 'room_type_id' => $rt->id, 'name' => 'Flex', 'currency' => 'EUR']);
    $ghost = Unit::create([
        'property_id' => $gone->id, 'room_type_id' => $rt->id, 'code' => 'GHOST',
        'is_active' => true, 'owner_id' => $owner->id,
    ]);
    app(AvailabilityService::class)->recompute($rt->id, '2026-10-01', '2026-10-20');

    // ⚠️ REAL HISTORY ON THE GHOST: a completed stay and €300.00 of earned owner revenue.
    $res = Reservation::create([
        'property_id' => $gone->id, 'currency' => 'EUR', 'status' => 'checked_out',
        'check_in' => '2026-06-01', 'check_out' => '2026-06-04',
        'lead_guest_id' => $s['guest']->id, 'source' => 'direct', 'reference' => 'GH-1',
    ]);
    ReservationUnit::create([
        'reservation_id' => $res->id, 'unit_id' => $ghost->id, 'room_type_id' => $rt->id,
        'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-06-01', 'check_out' => '2026-06-04',
        'status' => 'active', 'currency' => 'EUR', 'price_minor' => 10000,
    ]);
    OwnerLedgerEntry::create([
        'owner_id' => $owner->id, 'unit_id' => $ghost->id, 'reservation_id' => $res->id,
        'entry_type' => 'room_revenue', 'direction' => 'credit', 'amount_minor' => 30000,
        'currency' => 'EUR', 'description' => 'Ghost stay', 'service_date' => '2026-06-04',
    ]);

    app(PropertyRemovalService::class)->archive($gone->fresh(), app(ChannelConnectionService::class));

    return compact('s', 'owner', 'user', 'gone', 'rt', 'ghost');
}

/** Run a closure inside the owner's own portal scope. */
function asOwner(array $w, Closure $fn): mixed
{
    return app(OwnerContext::class)->forOwners([(string) $w['owner']->id], $fn);
}

// ── ⚠️⚠️ THE €300 TEST — THE DESIGN GUARD, WRITTEN FIRST ─────────────────────────────────────────
//
// ⚠️ This is the test that must go RED if anyone applies the live-property filter to `units()`. It is not
// about ghosts at all — it is about the owner's money surviving the fix for them.
it('⚠️ keeps the owner’s EARNED money and past stay after their property is archived', function () {
    $w = ghostWorld();

    asOwner($w, function () use ($w) {
        $attributed = array_map('strval', app(OwnerPortalService::class)->units()->pluck('id')->all());

        // ⚠️ The archived property's unit is STILL in the attribution set. That is correct, not a bug.
        expect($attributed)->toContain((string) $w['ghost']->id);

        // ⚠️⚠️ €300.00, to the cent, and the real completed stay — both still reachable.
        expect((int) OwnerLedgerEntry::query()->whereIn('unit_id', $attributed)->sum('amount_minor'))->toBe(30000);
        expect(ReservationUnit::query()->whereIn('unit_id', $attributed)->count())->toBe(1);
    });
});

// ── ⚠️ AND THE OTHER HALF: the ghost is GONE from live inventory ─────────────────────────────────
it('⚠️ drops the archived property’s unit from live inventory, keeping the live one', function () {
    $w = ghostWorld();

    asOwner($w, function () {
        $live = app(OwnerPortalService::class)->unitsAtLiveProperties()->pluck('code')->all();

        expect($live)->not->toContain('GHOST');
        expect($live)->toContain('U1');
    });
});

// ⚠️ AND THE ACCESSORS ARE NOT THE SAME THING — if they ever return the same set here, one of them is wrong.
it('⚠️ keeps the two accessors genuinely distinct', function () {
    $w = ghostWorld();

    asOwner($w, function () {
        $portal = app(OwnerPortalService::class);
        expect($portal->units()->count())->toBe(2);
        expect($portal->unitsAtLiveProperties()->count())->toBe(1);
    });
});

// ⚠️ AN OWNER-PAUSED UNIT IS NOT A GHOST. `is_active` means the OWNER took it off sale; the live-inventory
// accessor says nothing about it, or they could never resume a unit they had paused.
it('⚠️ still returns an owner-PAUSED unit at a live property', function () {
    $w = ghostWorld();
    Unit::withoutGlobalScopes()->where('code', 'U1')->update(['is_active' => false]);

    asOwner($w, function () {
        expect(app(OwnerPortalService::class)->unitsAtLiveProperties()->pluck('code')->all())->toContain('U1');
    });
});

// ── ⚠️⚠️ WRITE PATH 1 — BLOCKS. Measured landing before the fix. ─────────────────────────────────
it('⚠️ refuses to block dates on a unit whose property was removed — at the SERVICE', function () {
    $w = ghostWorld();

    asOwner($w, function () use ($w) {
        expect(fn () => app(OwnerBlockService::class)->block(
            $w['user'], (string) $w['ghost']->id, '2026-10-05', '2026-10-07', 'Personal stay',
        ))->toThrow(OwnerUnitPropertyRemovedException::class);
    });

    // ⚠️ AND NOTHING WAS WRITTEN. Before the fix this was one real `inventory_blocks` row.
    expect(InventoryBlock::withoutGlobalScopes()->where('unit_id', $w['ghost']->id)->count())->toBe(0);
});

// ⚠️ THE REFUSAL MUST STILL DISTINGUISH TAMPERING. A genuinely foreign unit is a different answer.
it('⚠️ still calls a foreign unit a foreign unit, not a removed property', function () {
    $w = ghostWorld();
    $foreign = Unit::withoutGlobalScopes()->where('code', 'U1')->firstOrFail();
    Unit::withoutGlobalScopes()->whereKey($foreign->id)->update(['owner_id' => null]);

    asOwner($w, function () use ($w, $foreign) {
        expect(fn () => app(OwnerBlockService::class)->block(
            $w['user'], (string) $foreign->id, '2026-10-05', '2026-10-07', 'x',
        ))->toThrow(OwnerBlockException::class);

        // ⚠️ …and NOT as the removed-property outcome, which would be a false statement about their unit.
        try {
            app(OwnerBlockService::class)->block($w['user'], (string) $foreign->id, '2026-10-05', '2026-10-07', 'x');
        } catch (Throwable $e) {
            expect($e)->not->toBeInstanceOf(OwnerUnitPropertyRemovedException::class);
        }
    });
});

// ── ⚠️⚠️ WRITE PATH 2 — RATES. Measured writing 3 real rate_calendar rows before the fix. ────────
it('⚠️ refuses to price a room type at a removed property — and writes no rate rows', function () {
    $w = ghostWorld();

    $before = DB::table('rate_calendar')->where('price_minor', 12300)->count();
    expect($before)->toBe(0);

    asOwner($w, function () use ($w) {
        expect(fn () => app(OwnerRateService::class)->apply(
            (string) $w['rt']->id, '2026-10-05', '2026-10-08', 12300, null, null,
        ))->toThrow(OwnerRateException::class);
    });

    // ⚠️ Before the fix: 3 rows at €123.00 against a property nobody can reach.
    expect(DB::table('rate_calendar')->where('price_minor', 12300)->count())->toBe(0);
});

// ── ⚠️ DATA LEAVING THE PRODUCT — proven SEPARATELY from the screens ─────────────────────────────
//
// ⚠️ The CSV plucks its ids from the portal itself, so it inherits the accessor rather than needing its own
// filter — but "inherits" is exactly the kind of claim that is assumed and wrong, so it is asserted.
it('⚠️ keeps the ghost out of the units CSV export — via the TOTAL-UNITS denominator', function () {
    $w = ghostWorld();

    $csv = asOwner($w, fn () => test()->actingAs($w['user'])
        ->get(route('owner.units.export'))->streamedContent());

    // ⚠️⚠️ ASSERTING `not->toContain('GHOST')` HERE WAS VACUOUS AND THE MUTATION CAUGHT IT. This export
    //    writes one row per DATE — Date / Units booked / Total units / Booked % — so a unit CODE never
    //    appears in it at all and that assertion passed with the fix REMOVED. What the filter really moves is
    //    the occupancy DENOMINATOR: two attributed units become one live one. So that is what is asserted.
    $rows = array_values(array_filter(
        array_map('str_getcsv', explode("\n", trim($csv))),
        fn (array $r): bool => isset($r[0]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r[0]) === 1,
    ));

    expect($rows)->not->toBeEmpty();
    foreach ($rows as $row) {
        expect((int) $row[2])->toBe(1);   // total units = the LIVE one only, never 2
    }
});

it('⚠️ keeps the ghost out of the OAuth API units endpoint', function () {
    $w = ghostWorld();

    $payload = asOwner($w, fn () => app(OwnerApiController::class)
        ->units(app(OwnerPortalService::class))->getData(true));

    $codes = array_column($payload['data'] ?? $payload, 'code');
    expect($codes)->not->toContain('GHOST');
    expect($codes)->toContain('U1');
});
