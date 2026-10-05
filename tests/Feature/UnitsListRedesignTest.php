<?php

declare(strict_types=1);

use App\Livewire\UnitsList;
use App\Models\Availability;
use App\Models\Organization;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\ArrivalInfoService;
use App\Services\AvailabilityService;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ⚠️ UNITS — THE PAGE REBUILT (2026-09-30, the founder's report and request, design approved the same day).
//   · "In Add inventory the text and labels are a mess — `Kind` is down and its dropdown is small, `Add room type` is
//     down." 🖥 Measured: `.vy-label` has no style anywhere (the labels fell back to 16 px body text); every field carried
//     a 15 px bottom margin the buttons did not, so Add room type / Add unit sat exactly 15 px below the boxes; and the
//     Kind dropdown kept the browser's own look, so its height — and its label — moved (43 vs 41 px in Chrome, smaller
//     in Safari).
//   · "Work on this page in the same way" as Guests and Maintenance: one toolbar, filter chips with counts, a sortable
//     table with page sizes, a menu per row, centred dialogs, select mode — plus room types as cards and one name for one
//     thing (the switch that stops new bookings is Pause / Resume, as in the owner portal; "Out of service" is the
//     maintenance condition beside it).

uses(DatabaseTransactions::class);

/**
 * Five units whose orders differ by every sort: codes that sort differently naturally than as text (A2 before A10), two
 * room types, different sleeps, one paused, one dirty and out of order.
 *
 * @return array{deluxe: RoomType, units: array<string, Unit>}
 */
function ulCast(array $s): array
{
    $deluxe = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Deluxe', 'kind' => 'apartment', 'max_adults' => 4, 'max_children' => 1]);

    $s['units'][0]->update(['code' => 'A10', 'max_occupancy' => 2]);
    $s['units'][1]->update(['code' => 'A2', 'max_occupancy' => 3]);
    $s['units'][2]->update(['code' => 'B1', 'room_type_id' => $deluxe->id, 'max_occupancy' => 5]);
    $s['units'][3]->update(['code' => 'A1', 'max_occupancy' => 1, 'is_active' => false]);
    $s['units'][4]->update(['code' => 'C7', 'room_type_id' => $deluxe->id, 'max_occupancy' => 4,
        'housekeeping_status' => 'dirty', 'condition_status' => 'out_of_order']);

    $units = [];
    foreach ($s['units'] as $u) {
        $units[(string) $u->fresh()->code] = $u->fresh();
    }

    return ['deluxe' => $deluxe, 'units' => $units];
}

/** @return list<string> the unit codes on the page, in order, for these settings */
function ulCodes(object $tc, array $s, array $params = [], string $role = 'Hotel Manager'): array
{
    $html = (string) $tc->actingAs(operatorUser($s, [], $role))->get(route('app.units', $params))->assertOk()->getContent();
    preg_match_all('/<tr[^>]*data-gl-id="[^"]*"[^>]*>.*?<span class="vy-ul-code">([^<]+)</s', $html, $m);

    return array_map('trim', $m[1]);
}

/** @return array<string, string> each keyed control link (`data-gl-key`) → its decoded href */
function ulLinks(string $html): array
{
    $links = [];
    preg_match_all('/<a\b[^>]*>/', $html, $tags);
    foreach ($tags[0] as $tag) {
        if (preg_match('/data-gl-key="([^"]+)"/', $tag, $key) === 1 && preg_match('/href="([^"]*)"/', $tag, $href) === 1) {
            $links[$key[1]] = html_entity_decode($href[1]);
        }
    }

    return $links;
}

/** @return array<string, array<string, mixed>> the rows the page's script reads, by id */
function ulIsland(string $html): array
{
    preg_match('/<script type="application\/json" data-ul-rows>(.*?)<\/script>/s', $html, $m);

    return json_decode($m[1] ?? '[]', true) ?: [];
}

// ── The founder's report: Add inventory ────────────────────────────────────────────────────────────

it('⚠️⚠️ lays the add forms out with styled labels, one height of box, and buttons in a footer — never a 15 px drop', function () {
    $view = (string) file_get_contents(resource_path('views/livewire/units-list.blade.php'));

    // The classes that styled nothing are gone.
    expect($view)->not->toContain('class="vy-label"')
        ->and($view)->not->toContain('class="vy-inp"');
    // Kind is four tiles, not a native dropdown whose size each browser decides.
    expect($view)->not->toMatch('/<select[^>]*name="kind"/')
        ->and($view)->toContain('<x-operator.choices name="kind"');
    // Each add button sits in its dialog's footer, not in a row of fields whose margins it does not share.
    expect($view)->toMatch('/<div class="vy-modal-foot">\s*<button[^>]*>Cancel<\/button>\s*<button type="submit" class="vy-btn vy-btn--accent">Add room type<\/button>/')
        // (2026-10-05 — the Add units button says how many it adds: "Add 10 units", so it carries an x-text)
        ->and($view)->toMatch('/<div class="vy-modal-foot">\s*<button[^>]*>Cancel<\/button>\s*<button type="submit" class="vy-btn vy-btn--accent"[^>]*>Add unit<\/button>/');
});

it('⚠️⚠️ centres every dialog on the page by the shared class, each a near-opaque card', function () {
    $dialogs = array_values(array_filter(vyDialogCensus(), fn (array $d): bool => $d['file'] === 'livewire/units-list.blade.php'));

    // Price individually (one per unit on the page) · Add room type · Add unit · Edit unit · Pause / Resume · Arrival info —
    // and since 2026-10-05 Delete unit · Edit room type · Delete room type · Photos (one per room type)
    expect($dialogs)->toHaveCount(10);
    foreach ($dialogs as $d) {
        expect(str_contains($d['backdrop'], 'vy-modal-backdrop'))->toBeTrue('x-show="'.$d['show'].'" is not centred by the class')
            ->and(str_contains($d['panel'], 'vy-modal-card'))->toBeTrue('x-show="'.$d['show'].'" is the see-through panel');
    }
});

// ── The list ──────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ sorts by unit in natural order (A2 before A10), by room type and by sleeps', function () {
    $s = seedScenario(5);
    ulCast($s);

    expect(ulCodes($this, $s))->toBe(['A1', 'A2', 'A10', 'B1', 'C7'])
        ->and(ulCodes($this, $s, ['sort' => 'unit', 'dir' => 'desc']))->toBe(['C7', 'B1', 'A10', 'A2', 'A1'])
        ->and(ulCodes($this, $s, ['sort' => 'room_type']))->toBe(['B1', 'C7', 'A1', 'A2', 'A10'])
        ->and(ulCodes($this, $s, ['sort' => 'sleeps']))->toBe(['B1', 'C7', 'A2', 'A10', 'A1'])
        ->and(ulCodes($this, $s, ['sort' => 'sleeps', 'dir' => 'asc']))->toBe(['A1', 'A10', 'A2', 'C7', 'B1']);

    // a sort, direction or size it does not offer falls back — never an error
    expect(ulCodes($this, $s, ['sort' => 'code; drop table units']))->toBe(['A1', 'A2', 'A10', 'B1', 'C7']);
    $this->actingAs(operatorUser($s))->get('/app/units?sort[]=x&dir[]=y&per[]=z&filter[]=q&q[]=a')->assertOk();
});

it('⚠️ filters with chips that count what clicking them shows — inside the search — and searches code and room type', function () {
    $s = seedScenario(5);
    ulCast($s);

    $html = (string) $this->actingAs(operatorUser($s))->get('/app/units')->assertOk()->getContent();
    foreach (['all' => ['All', 5], 'bookable' => ['Bookable', 3], 'paused' => ['Paused', 1], 'dirty' => ['To clean', 1], 'ooo' => ['Out of order', 1]] as $key => [$label, $count]) {
        expect((bool) preg_match('/data-gl-key="chip-'.$key.'"[^>]*>\s*'.$label.'<span class="vy-gl-count">'.$count.'</', $html))->toBeTrue("chip {$key}");
    }
    // the dead "Filters · Soon" and "Export · Soon" are gone
    expect($html)->not->toContain('Filters <span class="vy-soon-tag">')
        ->and($html)->not->toContain('Export <span class="vy-soon-tag">');

    expect(ulCodes($this, $s, ['filter' => 'bookable']))->toBe(['A2', 'A10', 'B1'])
        ->and(ulCodes($this, $s, ['filter' => 'paused']))->toBe(['A1'])
        ->and(ulCodes($this, $s, ['filter' => 'dirty']))->toBe(['C7'])
        ->and(ulCodes($this, $s, ['filter' => 'ooo']))->toBe(['C7'])
        ->and(ulCodes($this, $s, ['q' => 'deluxe']))->toBe(['B1', 'C7'])
        ->and(ulCodes($this, $s, ['q' => 'a1']))->toBe(['A1', 'A10']);

    $searched = (string) $this->actingAs(operatorUser($s))->get('/app/units?q=deluxe')->assertOk()->getContent();
    expect((bool) preg_match('/data-gl-key="chip-all"[^>]*>\s*All<span class="vy-gl-count">2</', $searched))->toBeTrue()
        ->and((bool) preg_match('/data-gl-key="chip-bookable"[^>]*>\s*Bookable<span class="vy-gl-count">1</', $searched))->toBeTrue();
});

it('⚠️ pages 25, 50 or 100 at a time, with a footer outside the table', function () {
    $s = seedScenario(30);

    $page = $this->actingAs(operatorUser($s))->get('/app/units')->assertOk();
    $html = (string) $page->getContent();
    $page->assertSeeText('1–25 of 30 units');
    expect(substr_count($html, 'data-gl-id="'))->toBe(25);
    preg_match('/<tbody\b.*?<\/tbody>/s', $html, $tbody);
    expect($tbody)->not->toBeEmpty()->and(str_contains($tbody[0], 'data-gl-foot'))->toBeFalse();

    expect(substr_count((string) $this->actingAs(operatorUser($s))->get('/app/units?per=50')->getContent(), 'data-gl-id="'))->toBe(30)
        ->and(substr_count((string) $this->actingAs(operatorUser($s))->get('/app/units?page=2')->getContent(), 'data-gl-id="'))->toBe(5)
        ->and(substr_count((string) $this->actingAs(operatorUser($s))->get('/app/units?per=37')->getContent(), 'data-gl-id="'))->toBe(25);
    assertNoRawBlade($page);
});

it('⚠️ keeps every setting on every link', function () {
    $s = seedScenario(60);

    $links = ulLinks((string) $this->actingAs(operatorUser($s))->get('/app/units?filter=bookable&sort=sleeps&dir=asc&per=50&q=U')->assertOk()->getContent());

    expect(array_keys($links))->toContain('chip-all', 'chip-paused', 'sort-unit', 'sort-room_type', 'sort-sleeps', 'per-100', 'page-next');
    expect($links['chip-all'])->not->toContain('filter=')
        ->and($links['chip-all'])->toContain('sort=sleeps', 'dir=asc', 'per=50', 'q=U')
        ->and($links['sort-room_type'])->toContain('sort=room_type', 'filter=bookable', 'per=50', 'q=U')
        ->and($links['per-100'])->toContain('per=100', 'filter=bookable', 'sort=sleeps', 'dir=asc', 'q=U')
        ->and($links['page-next'])->toContain('page=2', 'filter=bookable', 'sort=sleeps', 'per=50', 'q=U');
});

// ── Row actions and the words on them ────────────────────────────────────────────────────────────

it('⚠️⚠️ offers each action only to a role allowed it, and none that a paused workspace refuses', function () {
    $s = seedScenario(5);
    $cast = ulCast($s);

    $actions = function (string $role, Unit $unit) use ($s): string {
        $html = (string) $this->actingAs(operatorUser($s, [], $role))->get('/app/units')->assertOk()->getContent();
        preg_match('/data-gl-id="'.$unit->id.'"[^>]*data-ul-actions="([^"]*)"/', $html, $m);

        return $m[1] ?? 'MISSING';
    };

    // (2026-10-05 — `delete` for a writer: the dialog deletes a unit nothing has recorded, or says what is on record)
    expect($actions('Hotel Manager', $cast['units']['A2']))->toBe('edit price arrival pause delete')
        ->and($actions('Hotel Manager', $cast['units']['A1']))->toBe('edit price arrival resume delete')
        ->and($actions('Revenue Manager', $cast['units']['A2']))->toBe('price')
        ->and($actions('Front Desk', $cast['units']['A2']))->toBe('');

    // A role without units.manage gets no write dialog on its page at all.
    $desk = (string) $this->actingAs(operatorUser($s, [], 'Front Desk'))->get('/app/units')->assertOk()->getContent();
    foreach (['vy-ul-edit-t', 'vy-ul-pause-t', 'vy-ul-rt-t', 'vy-ul-unit-t', 'vy-ul-arrival-t'] as $id) {
        expect(str_contains($desk, 'id="'.$id.'"'))->toBeFalse($id.' is offered to a role that cannot use it');
    }

    // A paused workspace: the writes that would be refused are not offered (arrival info keeps its own rules).
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($s['org']->id)->update(['paused_until' => now()->addDays(3)]));
    expect($actions('Hotel Manager', $cast['units']['A2']))->toBe('arrival');
})->group('security');

it('⚠️ calls the switch Pause / Resume — "Out of service" is the maintenance condition beside it', function () {
    $s = seedScenario(5);
    ulCast($s);

    $page = $this->actingAs(operatorUser($s))->get('/app/units')->assertOk();
    $page->assertDontSeeText('Take out of service')->assertDontSeeText('Return to service')
        ->assertSee('Pause this unit')->assertSee('Resume this unit');
    $html = (string) $page->getContent();
    // the paused unit's row says so
    // (Livewire wraps an @if's output in <!--[if BLOCK]--> markers, so the gap may hold one.)
    expect((bool) preg_match('/<span class="vy-ul-code">A1<\/span>(?:\s|<!--.*?-->)*<span class="vy-tag tone-paused">Paused<\/span>/', $html))->toBeTrue();
});

it('⚠️ links each unit to its work orders, and keeps every secret out of the rows the script reads', function () {
    $s = seedScenario(5);
    $cast = ulCast($s);
    app(ArrivalInfoService::class)->update($cast['units']['A2'], ['door_code' => '8841-SECRET', 'wifi_ssid' => 'Harbour-Guest', 'wifi_password' => 'pw-SECRET-77']);

    $html = (string) $this->actingAs(operatorUser($s))->get('/app/units')->assertOk()->getContent();
    $rows = ulIsland($html);
    $a2 = $rows[(string) $cast['units']['A2']->id] ?? null;

    expect($a2)->not->toBeNull()
        ->and($a2['work_orders'])->toBe(route('app.maintenance', ['q' => 'A2']))
        ->and($a2['arrival']['has_code'])->toBeTrue()
        ->and($a2['arrival']['ssid'])->toBe('Harbour-Guest');
    expect($html)->not->toContain('8841-SECRET')->and($html)->not->toContain('pw-SECRET-77')
        ->and($html)->toContain('code set');
})->group('security');

// ── Every write started here comes back here ─────────────────────────────────────────────────────

it('⚠️⚠️ brings every write started from the list back to the list as you had it — and leaves the old behaviour alone', function () {
    $s = seedScenario(5);
    $cast = ulCast($s);
    $manager = operatorUser($s);
    $list = route('app.units').'?filter=paused&sort=sleeps&page=2';
    $rq = 'filter=paused&sort=sleeps&page=2&evil=1&q[]=x';

    $this->actingAs($manager)->put(route('app.inventory.units.update', $cast['units']['A2']->id), [
        'code' => 'A2', 'name' => 'Garden twin', 'max_occupancy' => 3, 'return' => 'list', 'return_query' => $rq,
    ])->assertRedirect($list);
    $this->actingAs($manager)->post(route('app.inventory.units.active', $cast['units']['A2']->id), [
        'active' => '0', 'return' => 'list', 'return_query' => $rq,
    ])->assertRedirect($list);
    $this->actingAs($manager)->post(route('app.inventory.units.arrival-info', $cast['units']['A2']->id), [
        'wifi_ssid' => 'Net', 'return' => 'list', 'return_query' => $rq,
    ])->assertRedirect($list);
    $this->actingAs($manager)->post(route('app.inventory.units.arrival-info.reveal', $cast['units']['A2']->id), [
        'return' => 'list', 'return_query' => $rq,
    ])->assertRedirect($list);
    $this->actingAs($manager)->post(route('app.inventory.room-types.store'), [
        'name' => 'Loft', 'kind' => 'apartment', 'max_adults' => 2, 'max_children' => 1, 'return' => 'list', 'return_query' => $rq,
    ])->assertRedirect($list);
    $this->actingAs($manager)->post(route('app.inventory.units.store'), [
        'room_type_id' => $cast['deluxe']->id, 'code' => 'D9', 'return' => 'list', 'return_query' => $rq,
    ])->assertRedirect($list);

    // Without `return`, each keeps landing where it always did.
    $this->actingAs($manager)->put(route('app.inventory.units.update', $cast['units']['B1']->id), ['code' => 'B1', 'max_occupancy' => 5])
        ->assertRedirect(route('app.units'));

    // And the way back can only ever be this page.
    foreach (['//evil.example/x', 'https://evil.example/', '/app/billing', "filter=paused\r\nLocation: https://evil.example"] as $bad) {
        $target = (string) $this->actingAs($manager)->put(route('app.inventory.units.update', $cast['units']['B1']->id), [
            'code' => 'B1', 'max_occupancy' => 5, 'return' => 'list', 'return_query' => $bad,
        ])->headers->get('Location');
        expect(parse_url($target, PHP_URL_PATH))->toBe(parse_url(route('app.units'), PHP_URL_PATH), "return_query={$bad} sent you to {$target}")
            ->and($target)->not->toContain('evil');
    }
})->group('security');

it('⚠️⚠️ refuses a duplicate unit code with a message, not a server error — and reopens the dialog with what was typed', function () {
    $s = seedScenario(2);
    $cast = ['A' => $s['units'][0], 'B' => $s['units'][1]];
    $manager = operatorUser($s);
    $before = Unit::query()->count();

    // Adding: the database's unique index used to answer this with a 500.
    $this->actingAs($manager)->from('/app/units?sort=sleeps')->post(route('app.inventory.units.store'), [
        'room_type_id' => $s['roomType']->id, 'code' => 'U1', 'name' => 'Typed but refused', '_form' => 'unit',
    ])->assertRedirect('/app/units?sort=sleeps');
    expect(Unit::query()->count())->toBe($before);
    $html = (string) $this->actingAs($manager)->get('/app/units?sort=sleeps')->assertOk()->getContent();
    $at = strpos($html, 'id="vy-ul-unit-t"');
    $err = strpos($html, '<div class="vy-gl-errors" role="alert">', (int) $at);
    expect($at)->not->toBeFalse()->and($err)->not->toBeFalse()->and($html)->toContain('Typed but refused');

    // Editing: the same refusal comes back inside the Edit dialog.
    $this->actingAs($manager)->from('/app/units')->put(route('app.inventory.units.update', $cast['B']->id), [
        'code' => 'U1', 'name' => 'Also refused', 'max_occupancy' => 2, '_unit' => $cast['B']->id,
    ])->assertRedirect('/app/units');
    expect($cast['B']->fresh()->code)->toBe('U2');
    $html = (string) $this->actingAs($manager)->get('/app/units')->assertOk()->getContent();
    $at = strpos($html, 'id="vy-ul-edit-t"');
    expect($at)->not->toBeFalse()
        ->and(strpos($html, '<div class="vy-gl-errors" role="alert">', (int) $at))->not->toBeFalse()
        ->and($html)->toContain('Also refused');
});

// ── Pause or resume several at once ──────────────────────────────────────────────────────────────

it('⚠️⚠️ pauses and resumes several units at once, each through the audited service, skipping ones already there', function () {
    $s = seedScenario(5);
    $cast = ulCast($s);
    $manager = operatorUser($s);
    $ids = [$cast['units']['A2']->id, $cast['units']['A10']->id, $cast['units']['A1']->id];   // A1 is already paused

    $this->actingAs($manager)->post(route('app.inventory.units.bulk-active'), [
        'unit_ids' => $ids, 'active' => '0', 'return' => 'list', 'return_query' => 'filter=bookable',
    ])->assertRedirect(route('app.units').'?filter=bookable')->assertSessionHas('units_notice');

    expect($cast['units']['A2']->fresh()->is_active)->toBeFalse()
        ->and($cast['units']['A10']->fresh()->is_active)->toBeFalse()
        ->and(DB::table('audit_events')->where('action', 'unit.deactivated')->whereIn('subject_id', $ids)->count())->toBe(2);
    expect(session('units_notice'))->toContain('2')->toContain('1 already paused');

    $this->actingAs($manager)->post(route('app.inventory.units.bulk-active'), ['unit_ids' => $ids, 'active' => '1'])
        ->assertRedirect(route('app.units'));
    expect(DB::table('units')->whereIn('id', $ids)->where('is_active', true)->count())->toBe(3);
})->group('security');

it('⚠️⚠️ recomputes each room type once for a bulk pause, not once per unit — and changes nothing if any of it fails', function () {
    // 🔬 ONE recompute is a year of nights for the WHOLE room type (~0.5 s and 1,098 queries locally, 2026-09-30), and it
    // reads the units as they stand — so pausing N units of one type needs it once, after all N have moved. Per unit, a
    // page of 100 was ~100 identical recomputes: a request long enough to be cut off part-way on the shared host.
    $s = seedScenario(5);
    $cast = ulCast($s);
    $manager = operatorUser($s);
    $standard = (string) $cast['units']['A10']->room_type_id;   // A10, A2 (and A1, already paused)
    $deluxe = (string) $cast['deluxe']->id;                     // B1 (and C7, out of order but on sale)
    $ids = [$cast['units']['A10']->id, $cast['units']['A2']->id, $cast['units']['B1']->id];

    $calls = new ArrayObject;
    app()->instance(AvailabilityService::class, new class($calls) extends AvailabilityService
    {
        public function __construct(private ArrayObject $calls) {}

        public function recompute(string $roomTypeId, CarbonInterface|string $start, CarbonInterface|string $end): void
        {
            $this->calls[] = $roomTypeId;
            parent::recompute($roomTypeId, $start, $end);
        }
    });

    $this->actingAs($manager)->post(route('app.inventory.units.bulk-active'), ['unit_ids' => $ids, 'active' => '0'])
        ->assertRedirect(route('app.units'));

    $seen = $calls->getArrayCopy();
    sort($seen);
    $expected = [$standard, $deluxe];
    sort($expected);
    expect($seen)->toBe($expected);

    // …and it is the real recompute: tonight the standard type has no unit on sale, Deluxe has C7 alone.
    $total = fn (string $type): ?int => Availability::query()->where('room_type_id', $type)
        ->whereDate('date', now()->toDateString())->value('total');
    expect($total($standard))->toBe(0)->and($total($deluxe))->toBe(1);

    // ALL OR NOTHING. A recompute that fails leaves every unit — and the audit trail — as it was.
    app()->instance(AvailabilityService::class, new class extends AvailabilityService
    {
        public function recompute(string $roomTypeId, CarbonInterface|string $start, CarbonInterface|string $end): void
        {
            throw new RuntimeException('recompute failed');
        }
    });
    $this->actingAs($manager)->post(route('app.inventory.units.bulk-active'), ['unit_ids' => $ids, 'active' => '1'])
        ->assertServerError();
    expect(DB::table('units')->whereIn('id', $ids)->where('is_active', true)->count())->toBe(0)
        ->and(DB::table('audit_events')->where('action', 'unit.reactivated')->whereIn('subject_id', $ids)->count())->toBe(0);
})->group('security');

it('⚠️⚠️ gates bulk pause like the single one — the permission, the workspace, the properties you may access', function () {
    $s = seedScenario(2);
    $unit = $s['units'][0];

    $this->actingAs(operatorUser($s, [], 'Front Desk'))->post(route('app.inventory.units.bulk-active'), ['unit_ids' => [$unit->id], 'active' => '0'])
        ->assertForbidden();

    // a manager scoped to ONE property cannot pause a unit of another by its id
    $far = secondProperty($s);
    $farType = RoomType::create(['property_id' => $far->id, 'name' => 'Far', 'kind' => 'room']);
    $farUnit = Unit::create(['property_id' => $far->id, 'room_type_id' => $farType->id, 'code' => 'FAR1']);
    $scoped = operatorUser($s);
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $scoped->id,
        'property_id' => $s['property']->id, 'created_at' => now(), 'updated_at' => now()]);
    $this->actingAs($scoped)->post(route('app.inventory.units.bulk-active'), ['unit_ids' => [$farUnit->id], 'active' => '0']);
    expect($farUnit->fresh()->is_active)->toBeTrue();

    // a paused workspace changes nothing
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($s['org']->id)->update(['paused_until' => now()->addDays(3)]));
    $this->actingAs(operatorUser($s))->post(route('app.inventory.units.bulk-active'), ['unit_ids' => [$unit->id], 'active' => '0'])
        ->assertSessionHas('units_error');
    expect($unit->fresh()->is_active)->toBeTrue();
})->group('security');

it('⚠️⚠️ adds a unit only to a room type in the properties the person works in — as every other unit write (DEC-k)', function () {
    // DEC-k, ruled 2026-09-30 ("Fix this issue too"): Add unit found the room type through the TENANT scope only, so staff
    // limited to one property could add a unit to another property's room type by posting its id — while Edit, Pause /
    // Resume, bulk Pause / Resume, Arrival info and its reveal all resolve inside the person's properties (#38).
    $s = seedScenario(1);
    $far = secondProperty($s);
    $farType = RoomType::create(['property_id' => $far->id, 'name' => 'Far', 'kind' => 'room']);
    $scoped = operatorUser($s);
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $scoped->id,
        'property_id' => $s['property']->id, 'created_at' => now(), 'updated_at' => now()]);

    // another property's room type is not found — not merely hidden — and nothing is created there
    $this->actingAs($scoped)->post(route('app.inventory.units.store'), ['room_type_id' => $farType->id, 'code' => 'FAR9'])
        ->assertNotFound();
    expect(Unit::query()->where('room_type_id', $farType->id)->exists())->toBeFalse();

    // their own property's room type still takes a unit
    $this->actingAs($scoped)->post(route('app.inventory.units.store'), ['room_type_id' => $s['roomType']->id, 'code' => 'NEAR9'])
        ->assertRedirect();
    expect(Unit::query()->where('room_type_id', $s['roomType']->id)->where('code', 'NEAR9')->exists())->toBeTrue();

    // and a manager allowed everywhere, working across all properties, still may add to the far one — the rule narrows to
    // the person's properties; it does not lock anyone out of their own
    $this->actingAs(operatorUser($s))->withSession(['current_property_id' => 'all'])
        ->post(route('app.inventory.units.store'), ['room_type_id' => $farType->id, 'code' => 'FAR9'])
        ->assertRedirect();
    expect(Unit::query()->where('room_type_id', $farType->id)->where('code', 'FAR9')->exists())->toBeTrue();
})->group('security');

// ── Export ───────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ exports what the view shows, or what was selected — no door code, wifi password or note ever', function () {
    $s = seedScenario(5);
    $cast = ulCast($s);
    app(ArrivalInfoService::class)->update($cast['units']['A2'], ['door_code' => '8841-SECRET', 'wifi_password' => 'pw-SECRET-77', 'arrival_notes' => 'Key under the SECRET mat']);
    $manager = operatorUser($s);

    $csv = $this->actingAs($manager)->get(route('app.units.export', ['filter' => 'bookable']))->assertOk()->streamedContent();
    expect($csv)->toContain('Unit,Name,"Room type"')
        ->toContain('A2')->toContain('A10')->toContain('B1')
        ->and($csv)->not->toContain('A1,')->and($csv)->not->toContain('C7')
        ->and($csv)->not->toContain('SECRET');
    expect(DB::table('audit_events')->where('action', 'unit.exported')->count())->toBeGreaterThanOrEqual(1);

    // the selection: only this organisation's units in the properties you may access
    $other = seedScenario(1);
    $foreign = $other['units'][0];
    app(TenantContext::class)->set((string) $s['org']->id);
    $csv = $this->actingAs($manager)->post(route('app.units.export-selected'), ['unit_ids' => [$cast['units']['C7']->id, $foreign->id]])
        ->assertOk()->streamedContent();
    expect($csv)->toContain('C7')->and($csv)->not->toContain((string) $foreign->code.',');

    // a role without units.view is refused — and not offered the control
    $this->actingAs(operatorUser($s, [], 'Accountant'))->get(route('app.units.export'))->assertForbidden();
    $html = (string) $this->actingAs(operatorUser($s, [], 'Accountant'))->get('/app/units')->assertOk()->getContent();
    expect($html)->not->toContain(route('app.units.export'));
})->group('security');

// ── Room types and the setup guide ───────────────────────────────────────────────────────────────

it('⚠️ shows each room type as a card with its real unit counts and an Add unit shortcut', function () {
    $s = seedScenario(5);
    $cast = ulCast($s);

    $html = (string) $this->actingAs(operatorUser($s))->get('/app/units')->assertOk()->getContent();
    // Deluxe: B1 bookable, C7 out of order → 2 units, 1 bookable. Std: A10, A2 bookable, A1 paused → 3 units, 2 bookable.
    // (Read as the card's TEXT — the count is set in <strong>.)
    $card = function (string $id) use ($html): string {
        preg_match('/<article[^>]*data-rt="'.$id.'"[^>]*>(.*?)<\/article>/s', $html, $m);

        return (string) preg_replace('/\s+/', ' ', strip_tags($m[1] ?? ''));
    };
    expect($card((string) $cast['deluxe']->id))->toContain('2 units · 1 bookable')
        ->and($card((string) $s['roomType']->id))->toContain('3 units · 2 bookable')
        ->and($html)->toContain('@click="openAddUnit(\''.$cast['deluxe']->id.'\')"');
});

it('⚠️ guides a new property through setup — room type, then unit, then prices — and steps aside once both exist', function () {
    $org = newDemoOrg();
    Property::create(['name' => 'Empty House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $manager = operatorUser(['org' => $org]);

    $html = (string) $this->actingAs($manager)->get('/app/units')->assertOk()->getContent();
    expect($html)->toContain('data-ul-setup')
        ->and((bool) preg_match('/data-step="1"[^>]*class="[^"]*is-current/', $html))->toBeTrue()
        ->and($html)->not->toContain('needs the “manage units” permission');

    // Who cannot add is told why — and only when the reason IS a permission. A paused workspace is not one: the shell's
    // own banner says the workspace is paused, and a manager told to "ask an administrator" would be sent the wrong way.
    $desk = (string) $this->actingAs(operatorUser(['org' => $org], [], 'Front Desk'))->get('/app/units')->assertOk()->getContent();
    expect($desk)->toContain('data-ul-setup')->toContain('needs the “manage units” permission');
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($org->id)->update(['paused_until' => now()->addDays(3)]));
    $paused = (string) $this->actingAs($manager)->get('/app/units')->assertOk()->getContent();
    expect($paused)->toContain('data-ul-setup')->toContain('Workspace paused')
        ->not->toContain('needs the “manage units” permission')
        ->not->toContain('@click="openAddRoomType()"');
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($org->id)->update(['paused_until' => null]));

    $type = RoomType::create(['property_id' => Property::query()->value('id'), 'name' => 'Twin', 'kind' => 'room']);
    $html = (string) $this->actingAs($manager)->get('/app/units')->assertOk()->getContent();
    expect((bool) preg_match('/data-step="1"[^>]*class="[^"]*is-done/', $html))->toBeTrue()
        ->and((bool) preg_match('/data-step="2"[^>]*class="[^"]*is-current/', $html))->toBeTrue();

    Unit::create(['property_id' => $type->property_id, 'room_type_id' => $type->id, 'code' => '101']);
    // ⚠️ 2026-10-05 — it no longer steps aside here: with a room type and a unit but no price, nothing can be booked yet, and
    //    the guide's third step (which never showed as done) is now the current one. It leaves once there is a price.
    $html = (string) $this->actingAs($manager)->get('/app/units')->assertOk()->getContent();
    expect($html)->toContain('data-ul-setup')->and((bool) preg_match('/data-step="3"[^>]*class="[^"]*is-current/', $html))->toBeTrue();

    $plan = RatePlan::create(['property_id' => $type->property_id, 'room_type_id' => $type->id, 'name' => 'Flex', 'currency' => 'EUR']);
    RateCalendar::create(['rate_plan_id' => $plan->id, 'date' => now()->addDays(5)->toDateString(), 'price_minor' => 9000, 'currency' => 'EUR']);
    expect((string) $this->actingAs($manager)->get('/app/units')->assertOk()->getContent())->not->toContain('data-ul-setup');
});

it('keeps the page a read view: no public method but render on the component', function () {
    $publics = collect((new ReflectionClass(UnitsList::class))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn (ReflectionMethod $m): bool => $m->getDeclaringClass()->getName() === UnitsList::class)
        ->map(fn (ReflectionMethod $m): string => $m->getName())
        ->reject(fn (string $n): bool => str_starts_with($n, '__') || str_starts_with($n, 'mountResolves'))
        ->values()->all();

    expect($publics)->toBe(['render']);
});

it('⚠️ exports the view on screen — the Export link takes the address of a filter or sort changed in place (A12)', function () {
    $s = seedScenario(1);
    $html = (string) $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get(route('app.units', ['sort' => 'room_type']))
        ->assertOk()->getContent();
    preg_match('/<a\b[^>]*href="[^"]*\/units-export[^"]*"[^>]*>/s', $html, $tag);

    // without script the link the server wrote is this view's own; with it, the view's address at the moment of the click
    // (🖥 on Owners, 2026-10-01: sorting in place left the link on the address the page was loaded with — Units had the same shape)
    expect($tag[0] ?? '')->toContain('href="'.e(route('app.units.export', ['sort' => 'room_type'])).'"')
        ->toContain('data-export-base="'.e(route('app.units.export')).'"')
        ->toContain('location.search');
});
