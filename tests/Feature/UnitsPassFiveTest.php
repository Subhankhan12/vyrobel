<?php

declare(strict_types=1);

use App\Http\Controllers\Operator\InventoryActionController;
use App\Models\AuditEvent;
use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Models\HousekeepingTask;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(DatabaseTransactions::class);

// ⚠️⚠️ UNITS, 2026-10-05 (the founder: "on this page there must be an option to edit and delete the unit. And Add inventory section
// design and functionality is not good. It is so confusing"). 📖 Edit existed only inside the row's ⋯ menu; there was NO delete for a
// unit or a room type — and a hard delete cascades a unit's history and orphans money, so Delete here removes only what nothing has
// recorded, and says why for anything else. Room types could not be edited. Units were added one at a time. The setup guide's third
// step never completed, so it vanished while nothing could be booked. Under "All properties" a new room type silently went to the
// first property. A "Dorm" room type wrote a unit kind the database refuses. 🔬 The 900 px table ran 168 px past its 734 px card at
// a 1,024 px window, and a leftover `.vy-units { display: flex }` rule (for unit pills no page uses) matched the page's own wrapper.
// Each test failed first against the page as it stood. Test clock: 1 Aug 2026.

function ul5Page(array $s, string $role = 'Property Owner', array $session = []): string
{
    return (string) test()->actingAs(operatorUser($s, [], $role))->withSession($session)->get(route('app.units'))->assertOk()->getContent();
}

function ul5Unit(array $s, string $code = 'Z901'): Unit
{
    $unit = Unit::create([
        'property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'code' => $code, 'name' => $code,
        'unit_kind' => 'room', 'max_occupancy' => 2, 'is_active' => true,
    ]);
    app(AvailabilityService::class)->recompute((string) $s['roomType']->id, '2026-08-01', '2026-08-31');

    return $unit;
}

function ul5Total(string $roomTypeId, string $date = '2026-08-10'): int
{
    return (int) DB::table('availability')->where('room_type_id', $roomTypeId)->where('date', $date)->value('total');
}

/** @return array<string, array<string, mixed>> the unit rows the page's script reads, by id */
function ul5Island(string $html, string $island = 'data-ul-rows'): array
{
    preg_match('/<script type="application\/json" '.$island.'>(.*?)<\/script>/s', $html, $m);

    return json_decode($m[1] ?? '[]', true) ?: [];
}

/** A room type card's visible text. */
function ul5Card(string $html, string $roomTypeId): string
{
    preg_match('/<article[^>]*data-rt="'.$roomTypeId.'"[^>]*>(.*?)<\/article>/s', $html, $m);

    return (string) preg_replace('/\s+/', ' ', strip_tags($m[1] ?? ''));
}

function ul5Price(array $s, string $date = '2026-08-20'): void
{
    RateCalendar::create(['rate_plan_id' => $s['ratePlan']->id, 'date' => $date, 'price_minor' => 9000, 'currency' => 'EUR']);
}

// ── his report: delete a unit ───────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ deletes a unit nothing has recorded — gone, its room type sells one fewer, and the deletion is on the record', function () {
    $s = seedScenario(1);
    $unit = ul5Unit($s);
    $before = ul5Total((string) $s['roomType']->id);

    test()->actingAs(operatorUser($s, [], 'Property Owner'))->delete(route('app.inventory.units.destroy', $unit->id), [
        'return' => 'list', 'return_query' => 'sort=sleeps',
    ])->assertRedirect(route('app.units').'?sort=sleeps')->assertSessionHas('units_notice', 'Unit Z901 deleted.');

    // gone for good — its code free to use again (the unique index counts soft-deleted rows)
    expect(Unit::withTrashed()->find($unit->id))->toBeNull()
        ->and(ul5Total((string) $s['roomType']->id))->toBe($before - 1);
    $audit = AuditEvent::query()->where('action', 'unit.deleted')->where('subject_id', $unit->id)->firstOrFail();
    expect($audit->before['code'] ?? null)->toBe('Z901');
});

it('⚠️⚠️ refuses to delete a unit with history — names what is on record and offers Pause instead', function () {
    $s = seedScenario(1);
    $booked = $s['units'][0];
    book($s, ['unit_id' => $booked->id]);

    // 📖 reservation_units.unit_id is SET NULL on delete: a sold stay would lose its room
    test()->actingAs(operatorUser($s, [], 'Property Owner'))->delete(route('app.inventory.units.destroy', $booked->id))
        ->assertRedirect()->assertSessionHas('units_error', fn (string $m): bool => str_contains($m, 'Unit U1 can’t be deleted')
            && str_contains($m, '1 booking') && str_contains($m, 'Pause it instead'));
    expect(Unit::query()->find($booked->id))->not->toBeNull();

    // 📖 housekeeping_tasks.unit_id CASCADEs: the cleaning history would vanish with it
    $unit = ul5Unit($s);
    HousekeepingTask::create(['organization_id' => $s['org']->id, 'property_id' => $s['property']->id, 'unit_id' => $unit->id,
        'date' => '2026-08-02', 'type' => 'departure', 'status' => 'pending', 'priority' => 'normal']);
    test()->actingAs(operatorUser($s, [], 'Property Owner'))->delete(route('app.inventory.units.destroy', $unit->id))
        ->assertSessionHas('units_error', fn (string $m): bool => str_contains($m, '1 cleaning task'));
    expect(Unit::query()->find($unit->id))->not->toBeNull();

    // a paused unit is not told to pause
    $unit->update(['is_active' => false]);
    test()->actingAs(operatorUser($s, [], 'Property Owner'))->delete(route('app.inventory.units.destroy', $unit->id))
        ->assertSessionHas('units_error', fn (string $m): bool => ! str_contains($m, 'Pause it instead') && str_contains($m, 'already paused'));
});

it('⚠️⚠️ guards Delete like every unit write — the permission, a paused workspace, the person’s own properties (DEC-k)', function () {
    $s = seedScenario(1);
    $unit = ul5Unit($s);

    test()->actingAs(operatorUser($s, [], 'Front Desk'))->delete(route('app.inventory.units.destroy', $unit->id))->assertForbidden();

    $far = secondProperty($s);
    $farType = RoomType::create(['property_id' => $far->id, 'name' => 'Far', 'kind' => 'room']);
    $farUnit = Unit::create(['property_id' => $far->id, 'room_type_id' => $farType->id, 'code' => 'F1']);
    $scoped = operatorUser($s, [], 'Property Owner');
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $scoped->id,
        'property_id' => $s['property']->id, 'created_at' => now(), 'updated_at' => now()]);
    test()->actingAs($scoped)->delete(route('app.inventory.units.destroy', $farUnit->id))->assertNotFound();
    expect(Unit::query()->find($farUnit->id))->not->toBeNull();

    pauseWorkspace($s);
    test()->actingAs(operatorUser($s, [], 'Property Owner'))->delete(route('app.inventory.units.destroy', $unit->id))
        ->assertSessionHas('units_error', fn (string $m): bool => str_contains($m, 'Workspace paused'));
    expect(Unit::query()->find($unit->id))->not->toBeNull();
})->group('security');

it('⚠️⚠️ puts Edit in plain sight on every row and Delete in its menu — and says beforehand whether Delete can remove it', function () {
    $s = seedScenario(1);
    $unit = ul5Unit($s);
    book($s, ['unit_id' => $s['units'][0]->id]);
    $html = ul5Page($s);

    // 📖 before: Edit only inside the ⋯ menu, no Delete anywhere
    expect($html)->toContain('aria-label="Edit unit Z901" @click="act(\'edit\', row(\''.$unit->id.'\'))"')
        ->toContain('x-show="can(\'delete\')" @click="menuAct(\'delete\')"');
    preg_match('/data-gl-id="'.$unit->id.'"[^>]*data-ul-actions="([^"]*)"/', $html, $m);
    expect($m[1] ?? '')->toBe('edit price arrival pause delete');

    $rows = ul5Island($html);
    expect($rows[(string) $unit->id]['deletable'] ?? null)->toBeTrue()
        ->and($rows[(string) $unit->id]['blockers'] ?? null)->toBe([])
        ->and($rows[(string) $s['units'][0]->id]['deletable'] ?? null)->toBeFalse()
        ->and($rows[(string) $s['units'][0]->id]['blockers'] ?? null)->toBe(['1 booking']);
});

// ── his report: the room types ─────────────────────────────────────────────────────────────────────────────────

it('⚠️ edits a room type — its name and who it sleeps; its kind only while it has no units', function () {
    $s = seedScenario(1);
    $rt = $s['roomType'];
    $owner = operatorUser($s, [], 'Property Owner');

    test()->actingAs($owner)->put(route('app.inventory.room-types.update', $rt->id), ['name' => 'Harbour Double', 'max_adults' => 3, 'max_children' => 1])
        ->assertRedirect()->assertSessionHas('units_notice', 'Room type “Harbour Double” updated.');
    $fresh = $rt->fresh();
    expect((string) $fresh->name)->toBe('Harbour Double')->and((int) $fresh->max_adults)->toBe(3)
        ->and((int) $fresh->max_children)->toBe(1)
        // shown to guests as "Standard occupancy": it followed the adults when it was added, so it follows them now
        ->and((int) $fresh->std_occupancy)->toBe(3);
    expect(AuditEvent::query()->where('action', 'room_type.updated')->where('subject_id', $rt->id)->exists())->toBeTrue();

    // its units copied its kind when they were added — a change now would leave them saying another
    test()->actingAs($owner)->put(route('app.inventory.room-types.update', $rt->id), ['name' => 'Harbour Double', 'kind' => 'villa', 'max_adults' => 3, 'max_children' => 1])
        ->assertSessionHasErrors(['kind' => 'A room type’s kind can’t change while it has units — they were added as rooms.']);
    expect((string) $rt->fresh()->kind)->toBe('room');

    // an empty one may change kind
    $empty = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Loft', 'kind' => 'room']);
    test()->actingAs($owner)->put(route('app.inventory.room-types.update', $empty->id), ['name' => 'Loft', 'kind' => 'apartment', 'max_adults' => 2, 'max_children' => 0])
        ->assertSessionHasNoErrors();
    expect((string) $empty->fresh()->kind)->toBe('apartment');

    test()->actingAs(operatorUser($s, [], 'Front Desk'))->put(route('app.inventory.room-types.update', $rt->id), ['name' => 'Nope', 'max_adults' => 2, 'max_children' => 0])
        ->assertForbidden();
});

it('⚠️⚠️ deletes only an empty room type — its own prices go with it; one with units is refused with the reason', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, [], 'Property Owner');

    // ⚠️ a room type's delete CASCADEs into reservation_units — sold stays — so it may never reach one with history
    test()->actingAs($owner)->delete(route('app.inventory.room-types.destroy', $s['roomType']->id))
        ->assertSessionHas('units_error', fn (string $m): bool => str_contains($m, 'Std can’t be deleted') && str_contains($m, '1 unit'));
    expect(RoomType::query()->find($s['roomType']->id))->not->toBeNull();

    $empty = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Garden Studio', 'kind' => 'apartment', 'max_adults' => 2, 'max_children' => 0]);
    $plan = RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $empty->id, 'name' => 'Flex', 'currency' => 'EUR']);
    RateCalendar::create(['rate_plan_id' => $plan->id, 'date' => '2026-08-20', 'price_minor' => 9000, 'currency' => 'EUR']);

    test()->actingAs($owner)->delete(route('app.inventory.room-types.destroy', $empty->id), ['return' => 'list'])
        ->assertSessionHas('units_notice', 'Garden Studio deleted.');
    expect(RoomType::withTrashed()->find($empty->id))->toBeNull()
        ->and(DB::table('rate_plans')->where('id', $plan->id)->exists())->toBeFalse()
        ->and(DB::table('rate_calendar')->where('rate_plan_id', $plan->id)->exists())->toBeFalse()
        ->and(AuditEvent::query()->where('action', 'room_type.deleted')->where('subject_id', $empty->id)->exists())->toBeTrue();

    test()->actingAs(operatorUser($s, [], 'Front Desk'))->delete(route('app.inventory.room-types.destroy', $s['roomType']->id))->assertForbidden();
});

it('⚠️ keeps a room type whose rate plan is connected to a channel — the delete would cut the connection (the review)', function () {
    $s = seedScenario(1);
    $empty = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Sea Loft', 'kind' => 'apartment', 'max_adults' => 2, 'max_children' => 0]);
    $plan = RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $empty->id, 'name' => 'Flex', 'currency' => 'EUR']);
    ChannelMapping::factory()->ratePlan($plan)->create(['channel_id' => Channel::factory()->create(['property_id' => $s['property']->id])->id]);

    // 📖 before: only a mapping on the ROOM TYPE counted; one on its rate plan (channel_mappings.rate_plan_id CASCADEs) did not
    test()->actingAs(operatorUser($s, [], 'Property Owner'))->delete(route('app.inventory.room-types.destroy', $empty->id))
        ->assertSessionHas('units_error', fn (string $m): bool => str_contains($m, '1 channel connection'));
    expect(RoomType::query()->find($empty->id))->not->toBeNull();
});

it('⚠️ shows each room type as a compact card — Edit and Delete on it, and what keeps it from being booked', function () {
    $s = seedScenario(1);   // Std: one unit, no prices
    $empty = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Garden Studio', 'kind' => 'apartment', 'max_adults' => 2, 'max_children' => 0]);
    $html = ul5Page($s);

    expect($html)->toContain('@click="openEditRoomType(\''.$s['roomType']->id.'\')"')
        ->toContain('@click="openDeleteRoomType(\''.$s['roomType']->id.'\')"');
    // 📖 before: a 128 px gradient banner and the photo controls on every card; nothing said a room type could not be booked
    expect(ul5Card($html, (string) $s['roomType']->id))->toContain('1 unit · 1 bookable')->toContain('No prices yet')
        ->and(ul5Card($html, (string) $empty->id))->toContain('No units yet')
        ->and($html)->not->toContain('vy-ul-rt-cover');

    ul5Price($s);
    expect(ul5Card(ul5Page($s), (string) $s['roomType']->id))->toContain('Units and prices set');
});

it('⚠️ manages a room type’s photos in one dialog, and reopens it after each change', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $html = ul5Page($s);

    expect($html)->toContain('x-show="photosFor === \''.$s['roomType']->id.'\'"')
        ->toContain(route('app.room-types.photos.store', $s['roomType']->id));

    test()->actingAs(operatorUser($s, [], 'Property Owner'))->from(route('app.units'))
        ->post(route('app.room-types.photos.store', $s['roomType']->id), ['photo' => UploadedFile::fake()->image('room.jpg', 40, 40)])
        ->assertRedirect(route('app.units'))->assertSessionHas('units_photos', (string) $s['roomType']->id);
});

// ── his report: the "Add inventory" flow ───────────────────────────────────────────────────────────────────────

it('⚠️⚠️ adds several units at once — a range and a list in one go, each a real unit the room type can sell', function () {
    $s = seedScenario(1);   // U1 exists

    // 📖 before: one code per dialog — forty rooms meant forty dialogs
    test()->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.inventory.units.store'), [
        'room_type_id' => $s['roomType']->id, 'code' => '101-103, 105', 'max_occupancy' => 3,
    ])->assertRedirect()->assertSessionHas('units_notice', fn (string $m): bool => str_contains($m, '4 units added to Std: 101–103, 105.'));

    $added = Unit::query()->where('room_type_id', $s['roomType']->id)->whereIn('code', ['101', '102', '103', '105'])->get();
    expect($added)->toHaveCount(4)
        ->and($added->pluck('max_occupancy')->unique()->all())->toBe([3])
        ->and($added->pluck('name')->sort()->values()->all())->toBe(['101', '102', '103', '105'])
        ->and(ul5Total((string) $s['roomType']->id))->toBe(5);
});

it('⚠️⚠️ refuses a list with a code already used, listed twice, or too many — names them, and adds none', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, [], 'Property Owner');
    $count = Unit::query()->count();

    test()->actingAs($owner)->post(route('app.inventory.units.store'), ['room_type_id' => $s['roomType']->id, 'code' => '201, U1, 202'])
        ->assertSessionHasErrors(['code' => 'Unit code U1 is already used in this property — choose another.']);
    test()->actingAs($owner)->post(route('app.inventory.units.store'), ['room_type_id' => $s['roomType']->id, 'code' => '301, 301'])
        ->assertSessionHasErrors(['code' => '301 is listed twice.']);
    test()->actingAs($owner)->post(route('app.inventory.units.store'), ['room_type_id' => $s['roomType']->id, 'code' => '1-500'])
        ->assertSessionHasErrors(['code' => 'That is 500 units — add at most 100 at a time.']);
    expect(Unit::query()->count())->toBe($count);
});

it('⚠️ refuses two codes the database counts as one with a sentence, never an error page (the review)', function () {
    $s = seedScenario(1);
    $count = Unit::query()->count();

    // the parser compares case only; the column's collation also ignores accents, so "Cafe" and "Café" collide at the
    // unique index — 📖 before, the second insert ended in a server error page. (A code added elsewhere at the same moment
    // ends the same way.)
    test()->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.inventory.units.store'), ['room_type_id' => $s['roomType']->id, 'code' => 'Cafe, Café'])
        ->assertSessionHasErrors(['code' => 'One of these codes is already used in this property — codes that differ only by accents or capitals count as the same. Choose another.']);
    expect(Unit::query()->count())->toBe($count);
});

it('⚠️ says "already used" only for the unit-code index — any other collision is not a code problem (the second review)', function () {
    // The catch wraps the whole save, and the availability rebuild inside it has its own unique index (room type, date): a race
    // there must not be told to the person as "one of these codes is already used". 📖 Before, every unique violation was.
    $refuse = new ReflectionMethod(InventoryActionController::class, 'sameCodeRefused');
    $violation = fn (string $index): UniqueConstraintViolationException => (new UniqueConstraintViolationException(
        'mysql', 'insert …', [], new PDOException('Duplicate entry')))->setIndex($index);
    $controller = app(InventoryActionController::class);

    expect(fn () => $refuse->invoke($controller, 'code', fn () => throw $violation('availability_room_type_id_date_unique')))
        ->toThrow(UniqueConstraintViolationException::class);
    expect(fn () => $refuse->invoke($controller, 'code', fn () => throw $violation('units_property_id_code_unique')))
        ->toThrow(ValidationException::class);
});

it('⚠️⚠️ adds a room type and its units in one step', function () {
    $s = seedScenario(1);

    test()->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.inventory.room-types.store'), [
        'name' => 'Loft', 'kind' => 'apartment', 'max_adults' => 2, 'max_children' => 1, 'unit_codes' => 'L1-L3',
    ])->assertRedirect()->assertSessionHas('units_notice', fn (string $m): bool => str_contains($m, 'Loft added with 3 units: L1–L3.'));

    $loft = RoomType::query()->where('name', 'Loft')->firstOrFail();
    $units = Unit::query()->where('room_type_id', $loft->id)->get();
    expect($units)->toHaveCount(3)
        ->and($units->pluck('unit_kind')->unique()->all())->toBe(['apartment'])
        ->and($units->pluck('max_occupancy')->unique()->all())->toBe([3])
        ->and(ul5Total((string) $loft->id))->toBe(3);

    // a refused list adds nothing at all — not even the room type
    test()->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.inventory.room-types.store'), [
        'name' => 'Attic', 'kind' => 'room', 'max_adults' => 2, 'max_children' => 0, 'unit_codes' => 'U1, A1',
    ])->assertSessionHasErrors('unit_codes');
    expect(RoomType::query()->where('name', 'Attic')->exists())->toBeFalse();
});

it('⚠️⚠️ asks which property under “All properties” — never silently the first — and names it otherwise', function () {
    $s = seedScenario(1);
    $far = secondProperty($s, 'Far House');
    $owner = operatorUser($s, [], 'Property Owner');
    $all = ['current_property_id' => 'all'];

    $html = (string) test()->actingAs($owner)->withSession($all)->get(route('app.units'))->assertOk()->getContent();
    expect($html)->toContain('id="vy-ul-rt-prop"')->toContain(e($far->name));

    test()->actingAs($owner)->withSession($all)->post(route('app.inventory.room-types.store'), [
        'name' => 'Far Suite', 'kind' => 'room', 'max_adults' => 2, 'max_children' => 0, 'property_id' => $far->id,
    ])->assertSessionHasNoErrors();
    expect((string) RoomType::query()->where('name', 'Far Suite')->value('property_id'))->toBe((string) $far->id);

    // 📖 before: with none chosen it went to the first property, unasked
    test()->actingAs($owner)->withSession($all)->post(route('app.inventory.room-types.store'), [
        'name' => 'Nowhere', 'kind' => 'room', 'max_adults' => 2, 'max_children' => 0,
    ])->assertSessionHasErrors(['property_id' => 'Choose the property this room type belongs to.']);
    expect(RoomType::query()->where('name', 'Nowhere')->exists())->toBeFalse();

    $one = (string) test()->actingAs($owner)->withSession(['current_property_id' => $s['property']->id])->get(route('app.units'))->getContent();
    expect($one)->toContain('Adds to <strong>'.e($s['property']->name).'</strong>')->not->toContain('id="vy-ul-rt-prop"');

    // a property the person does not work in is refused, never used (they work in two of three here)
    $third = secondProperty($s, 'Third House');
    $two = operatorUser($s, [], 'Property Owner');
    foreach ([$s['property']->id, $far->id] as $pid) {
        DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $two->id,
            'property_id' => $pid, 'created_at' => now(), 'updated_at' => now()]);
    }
    test()->actingAs($two)->withSession($all)->post(route('app.inventory.room-types.store'), [
        'name' => 'Elsewhere', 'kind' => 'room', 'max_adults' => 2, 'max_children' => 0, 'property_id' => $third->id,
    ])->assertSessionHasErrors('property_id');
    expect(RoomType::query()->where('name', 'Elsewhere')->exists())->toBeFalse();
})->group('security');

it('⚠️⚠️ keeps the setup steps until the room type has prices — and says what a room type and a unit are', function () {
    $s = seedScenario(1);   // a room type and a unit, no prices

    // 📖 before: step 3 never completed, and the guide left once a room type and a unit existed — though nothing could be booked
    $html = ul5Page($s);
    expect($html)->toContain('data-ul-setup')
        ->toMatch('/data-step="3" class="vy-ul-step is-current"/')
        ->toMatch('/data-step="2" class="vy-ul-step is-done"/')
        ->toContain('A room type is what a guest books — “Double Room”; a unit is a real room of that type — “101”.');

    ul5Price($s);
    expect(ul5Page($s))->not->toContain('data-ul-setup');
});

it('⚠️ adds a unit to a Dorm room type without an error — its unit kind is a dorm bed', function () {
    $s = seedScenario(1);
    $dorm = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Shared dorm', 'kind' => 'dorm', 'max_adults' => 1, 'max_children' => 0, 'std_occupancy' => 1]);

    // 📖 before: unit_kind = 'dorm', which the units enum (room|apartment|villa|dorm_bed) refuses — an error under strict SQL
    test()->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.inventory.units.store'), ['room_type_id' => $dorm->id, 'code' => 'D1'])
        ->assertRedirect()->assertSessionHasNoErrors();
    expect((string) Unit::query()->where('code', 'D1')->value('unit_kind'))->toBe('dorm_bed');
});

// ── text visible at every width; a paused workspace ───────────────────────────────────────────────────────────

it('⚠️⚠️ fits the units table inside its card — a narrow card stacks each unit, every fact named', function () {
    $s = seedScenario(1);
    $html = ul5Page($s);

    // 🔬 before: a 900 px table — 168 px past its 734 px card at a 1,024 px window
    expect($html)->toContain('data-label="Room type"')->toContain('data-label="Sleeps"')->toContain('data-label="Housekeeping"')
        ->toContain('data-label="Condition"')->toContain('data-label="Owner"');
    $css = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    expect($css)->toContain('.vy-units .vy-gl-card { container: vy-ul-list / inline-size; }')
        ->toContain('.vy-units .vy-ul-table { min-width: 0; }')
        ->toMatch('~@container vy-ul-list \(max-width: \d+px\) \{[^@]*?\.vy-units \.vy-gl-table tbody tr \{[^}]*display: flex; flex-wrap: wrap~')
        // the dead pill rule that turned the page's wrapper into a flex row
        ->not->toContain('.vy-units { display: flex;');
});

it('⚠️ offers no arrival-info or photo writes while the workspace is paused — and the server refuses them too', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    pauseWorkspace($s);
    $html = ul5Page($s);

    // 📖 before: Arrival info's Save and the photo controls stayed offered while everything else was read-only, and neither route checked
    expect($html)->toContain('Workspace paused — arrival info can be read, not changed.')->not->toContain('Add photo')
        ->not->toContain('class="vy-btn vy-btn--accent">Save</button>');
    test()->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.inventory.units.arrival-info', $s['units'][0]->id), ['door_code' => '4821'])
        ->assertSessionHas('units_error');
    expect($s['units'][0]->fresh()->door_code)->toBeNull();

    test()->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.room-types.photos.store', $s['roomType']->id), [
        'photo' => UploadedFile::fake()->image('room.jpg', 40, 40),
    ])->assertSessionHas('units_error');
    expect($s['roomType']->photos()->count())->toBe(0);
})->group('security');

it('⚠️ manages photos only inside the person’s properties (DEC-k)', function () {
    Storage::fake('public');
    $s = seedScenario(1);
    $far = secondProperty($s);
    $farType = RoomType::create(['property_id' => $far->id, 'name' => 'Far', 'kind' => 'room']);
    $scoped = operatorUser($s, [], 'Property Owner');
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $scoped->id,
        'property_id' => $s['property']->id, 'created_at' => now(), 'updated_at' => now()]);

    // 📖 before: the photo routes found the room type through the tenant scope only
    test()->actingAs($scoped)->post(route('app.room-types.photos.store', $farType->id), ['photo' => UploadedFile::fake()->image('x.jpg', 40, 40)])
        ->assertNotFound();
    expect($farType->photos()->count())->toBe(0);
})->group('security');
