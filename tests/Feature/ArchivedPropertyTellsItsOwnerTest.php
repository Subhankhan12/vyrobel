<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use App\Services\ChannelConnectionService;
use App\Services\PropertyRemovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ R-ARCHIVE (founder ruling, 2026-09-19) — F-24. AN OWNER LEARNS THAT THEIR PROPERTY WAS ARCHIVED.
 *
 * ⚠️⚠️ THE FIXTURE PROBLEM IS SHARPER HERE THAN ANYWHERE: NOTHING IS EVER UN-ARCHIVED. "Prove reversibility
 * before performing" has no escape hatch, so every property archived in this file is one this file BUILT, in a
 * `RefreshDatabase` transaction that never reaches a seeded tenant. Nothing in tenant one is touched.
 *
 * ⚠️ AND THE ASSERTIONS ARE ON THE ROW AND THE RECIPIENT, never on "a notification was sent" — a status tells
 * you nothing. Each case reads `notifications` back and resolves it to a named person.
 */

/** A property of our own, with one owned unit and a linked portal user. Nothing seeded is reused. */
function aptoProperty(array $s, string $name, string $ownerName = 'Olive Owner'): array
{
    $property = Property::factory()->create([
        'organization_id' => $s['org']->id,
        'name' => $name,
    ]);

    $user = User::factory()->create(['name' => $ownerName, 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'payout_currency' => 'GBP']);

    $unit = Unit::factory()->create([
        'organization_id' => $s['org']->id,
        'property_id' => $property->getKey(),
        'owner_id' => $owner->getKey(),
    ]);

    return ['property' => $property, 'owner' => $owner, 'user' => $user, 'unit' => $unit];
}

/** The owner-facing feed rows this user actually holds, decoded. */
function aptoFeed(User $user): array
{
    return DB::table('notifications')
        ->where('notifiable_id', $user->getKey())
        ->get()
        ->map(fn ($r): array => (array) json_decode((string) $r->data, true))
        ->all();
}

function aptoArchive(Property $property): void
{
    app(PropertyRemovalService::class)->archive($property, app(ChannelConnectionService::class));
}

// ── 1. ⚠️⚠️ THE OWNER IS TOLD, AND THE ARCHIVE REALLY HAPPENED ──────────────────────────────────────
/**
 * ⚠️⚠️ BOTH HALVES, BECAUSE ONE WITHOUT THE OTHER LOOKS IDENTICAL TO A TEST THAT NEVER REACHED THE ARCHIVE.
 * "The owner was notified" and "nothing ran" are distinguishable only if the archive is asserted too.
 */
it('⚠️⚠️ F-24 — tells the owner their property was archived, and archives it', function () {
    $s = seedScenario(1);
    $c = aptoProperty($s, 'Harbour View Apartments');

    expect(aptoFeed($c['user']))->toBe([], 'the fixture must start silent, or this proves nothing');

    aptoArchive($c['property']);

    // ⚠️ THE ARCHIVE LANDED — perturbed state, not an absence.
    expect(Property::query()->find($c['property']->getKey()))->toBeNull('the property was not archived');
    expect(Property::withTrashed()->find($c['property']->getKey())?->deleted_at)->not->toBeNull();

    // ⚠️ AND THE ROW EXISTS, RESOLVED TO A NAMED PERSON — not "a notification was sent".
    $rows = aptoFeed($c['user']);
    expect($rows)->toHaveCount(1, 'the owner holds no archive notice');
    expect($rows[0]['title'])->toContain('archived');
    expect($rows[0]['context'])->toContain('Harbour View Apartments');
});

// ── 2. ⚠️⚠️ THE NOTICE DOES NOT LIE ABOUT THE MONEY ─────────────────────────────────────────────────
/**
 * ⚠️⚠️ THE CLAIM IS ASSERTED, NOT TRUSTED. The notice tells the owner their earnings, statements and payouts
 * are unaffected. 🔬 That sentence is only honest if the rows really survive — so this archives a property
 * whose owner has a real ledger balance and a real unsettled statement, and reads them back afterwards.
 *
 * ⚠️ The schema is why it holds: `owner_statements` and `payouts` carry NO property or unit column, and
 * `owner_ledger`'s units survive because a soft delete fires no cascade.
 */
it('⚠️⚠️ and the money it promises is untouched really survives the archive', function () {
    $s = seedScenario(1);
    $c = aptoProperty($s, 'Quayside Lofts');

    $ledgerId = (string) Str::ulid();
    DB::table('owner_ledger')->insert([
        'id' => $ledgerId,
        'organization_id' => $s['org']->id,
        'owner_id' => $c['owner']->getKey(),
        'unit_id' => $c['unit']->getKey(),
        'entry_type' => 'room_revenue',
        'direction' => 'credit',
        'amount_minor' => 45000,
        'currency' => 'GBP',
        'description' => 'Stay revenue',
        'service_date' => '2026-09-01',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    aptoArchive($c['property']);

    // ⚠️ THE LEDGER ROW SURVIVES, WITH ITS UNIT STILL ATTACHED — the unit is not deleted by a soft delete.
    $row = DB::table('owner_ledger')->where('id', $ledgerId)->first();
    expect($row)->not->toBeNull('the archive destroyed an owner ledger row');
    expect((int) $row->amount_minor)->toBe(45000);
    expect($row->unit_id)->toBe((string) $c['unit']->getKey(), 'the ledger lost its unit');

    // ⚠️⚠️ AND THE UNIT ITSELF SURVIVES — ASSERTED ON THE RAW ROW, NOT THROUGH ELOQUENT.
    //    🔬 The first version of this read `Unit::withoutGlobalScopes()->find(...)` and was VACUOUS: `Unit`
    //    uses SoftDeletes, and `withoutGlobalScopes()` strips the SoftDeletingScope along with the tenant
    //    scope — so it returns a soft-deleted unit and the assertion could not fail. A mutation that
    //    soft-deleted every unit at the property passed it. A check must be independent of what it checks.
    $unitRow = DB::table('units')->where('id', $c['unit']->getKey())->first(['id', 'deleted_at']);
    expect($unitRow)->not->toBeNull('the archive destroyed the unit row');
    expect($unitRow->deleted_at)->toBeNull('the archive soft-deleted the unit; its earnings history would vanish');
});

// ── 3. ⚠️⚠️ SEVERAL OWNERS, EACH TOLD SEPARATELY — AND NEITHER LEARNS OF THE OTHER ──────────────────
/**
 * ⚠️⚠️ OWNERS ATTACH PER-UNIT, so one property can carry several. One message naming all of them would tell
 * each owner who the others are. This asserts the ABSENCE, which is the half a "did it send?" test misses.
 */
it('⚠️⚠️ notifies each owner separately, and neither notice names the other', function () {
    $s = seedScenario(1);
    $c = aptoProperty($s, 'Tolbooth Mews', 'Olive Owner');

    // A SECOND owner, with their own unit at the SAME property.
    $userB = User::factory()->create(['name' => 'Bruno Beneficiary', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $userB->id, 'status' => 'active']);
    $ownerB = Owner::factory()->create(['organization_id' => $s['org']->id, 'user_id' => $userB->id, 'payout_currency' => 'GBP']);
    Unit::factory()->create([
        'organization_id' => $s['org']->id,
        'property_id' => $c['property']->getKey(),
        'owner_id' => $ownerB->getKey(),
    ]);

    aptoArchive($c['property']);

    $a = aptoFeed($c['user']);
    $b = aptoFeed($userB);

    expect($a)->toHaveCount(1, 'owner A was not told');
    expect($b)->toHaveCount(1, 'owner B was not told');

    // ⚠️ THE LEAK TEST — neither notice carries the other owner's name, nor any internal identifier.
    foreach ([...$a, ...$b] as $row) {
        $blob = json_encode($row);
        expect(str_contains((string) $blob, 'Bruno Beneficiary'))->toBeFalse('a notice names another owner');
        expect(str_contains((string) $blob, 'Olive Owner'))->toBeFalse('a notice names another owner');
        expect(str_contains((string) $blob, (string) $c['property']->getKey()))->toBeFalse('a notice carries an internal id');
        expect(str_contains((string) $blob, (string) $c['owner']->getKey()))->toBeFalse('a notice carries an internal id');
    }
});

// ── 4. ⚠️⚠️ THE REAL ENTRANCE — processDue(), which is the ONLY path that archives in production ────
/**
 * ⚠️⚠️ "THE LIVE CALLER NOTIFIES CORRECTLY" WAS TRUE WHEN F-18 WAS FOUND AND THE GAP WAS STILL REAL. The notice
 * fires from `archive()`, the writer, so the scheduled pass inherits it — and this drives that pass rather than
 * the method directly, so a notification moved up into a caller would redden here.
 *
 * 🔬 In production this is reached ONLY by `properties:process-removals`, scheduled `dailyAt('02:00')` — so the
 * notice inherits that cron's dependency, recorded in the register.
 */
it('⚠️⚠️ fires from the scheduled pass too, not just a direct call', function () {
    $s = seedScenario(1);
    $c = aptoProperty($s, 'Lamplighter Court');

    // Due as of now — the state processDue() selects on.
    $c['property']->forceFill([
        'removal_scheduled_at' => now()->subDay(),
        'removal_effective_at' => now()->subHour(),
    ])->save();

    $archived = app(PropertyRemovalService::class)->processDue(app(ChannelConnectionService::class));

    expect($archived)->toBeGreaterThan(0, 'processDue archived nothing — this test proved nothing');
    expect(Property::query()->find($c['property']->getKey()))->toBeNull();
    expect(aptoFeed($c['user']))->toHaveCount(1, 'the scheduled pass archived without telling the owner');
});

// ── 5. ⚠️ AN OWNER WITH NO LINKED USER CANNOT BE TOLD — and must not break the archive ──────────────
/**
 * ⚠️ `owners.user_id` is nullable: an owner record can exist before anyone claims the portal. The archive must
 * still complete. 📄 That they cannot be reached is a residual, recorded in FIX 43's report — not something
 * this test papers over.
 */
it('⚠️ still archives when an owner has no portal user to notify', function () {
    $s = seedScenario(1);
    $property = Property::factory()->create(['organization_id' => $s['org']->id, 'name' => 'Unclaimed House']);
    $owner = Owner::factory()->create(['organization_id' => $s['org']->id, 'user_id' => null, 'payout_currency' => 'GBP']);
    Unit::factory()->create([
        'organization_id' => $s['org']->id,
        'property_id' => $property->getKey(),
        'owner_id' => $owner->getKey(),
    ]);

    aptoArchive($property);

    expect(Property::query()->find($property->getKey()))->toBeNull('the archive did not complete');
    expect(DB::table('notifications')->count())->toBe(0, 'somebody was notified who should not have been');
});

// ── 6. ⚠️ F-57's DEFECT-PIN LIVED HERE, AND WAS DELETED BY THE PASS THAT FIXED IT ─────────────────
//
// ⚠️⚠️ ITS OWN DOCBLOCK SAID SO: *"When it is fixed, this test goes RED, and whoever fixes it should delete
//    it rather than adjust it."* FIX 44 made `processDue()` re-derive the removal date at execution, the pin
//    went red for the right reason — "nothing was archived", because the property is now correctly DEFERRED —
//    and it was removed rather than inverted. Adjusting a defect-pin into a guarantee hides which of the two
//    a later reader is looking at.
//
// ⚠️ THE BEHAVIOUR IT DESCRIBED IS NOW PINNED THE OTHER WAY UP, in `RemovalDateIsReDerivedTest`.
