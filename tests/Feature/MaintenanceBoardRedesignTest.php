<?php

declare(strict_types=1);

use App\Livewire\MaintenanceList;
use App\Models\MaintenanceTicket;
use App\Models\Organization;
use App\Models\RoomType;
use App\Models\Unit;
use App\Notifications\Operator\MaintenanceResolvedNotification;
use App\Services\MaintenanceService;
use App\Support\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

// ⚠️ MAINTENANCE — THE BOARD REBUILT (2026-09-29, the founder's request "the same sort of things on the same page";
// design approved the same day).
//   · Every dialog opened off-centre and the detail drawer on the LEFT: each centred itself with an INLINE display
//     beside x-show, which Alpine erases (🖥 New work order at 260,330 in 1280×900; the drawer at x 0–440).
//   · The list is a table that sorts by priority, time to target, reported and unit; pages 25 / 50 / 100; filters are
//     chips with counts; the footer says what is shown.
//   · Every row has a menu, each action only where the ticket's state and the viewer's permission allow it — and a
//     real Edit (open or in progress only) and Cancel (the honest delete: the history stays, the room goes back on sale).

uses(DatabaseTransactions::class);

/** Open a ticket through the certified service, at a set minute after 09:00 (so reported/target orders are fixed). */
function mbrTicket(array $s, int $unit, string $title, string $priority, int $minute, array $extra = []): MaintenanceTicket
{
    Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00')->addMinutes($minute));

    return app(MaintenanceService::class)->open(array_merge([
        'unit_id' => $s['units'][$unit]->id, 'title' => $title, 'category' => 'plumbing', 'priority' => $priority,
    ], $extra));
}

/**
 * The cast of the sorting tests — five tickets whose orders differ by every sort. ⚠️ Priorities and ages are chosen so
 * that "time to target" (opened + the priority's target hours) is NOT the same order as priority or as age.
 *
 * @return array<string, MaintenanceTicket>
 */
function mbrCast(array $s): array
{
    foreach (['A-101', 'C-301', 'B-201', 'A-102', 'B-202'] as $i => $code) {
        $s['units'][$i]->update(['code' => $code]);
    }

    $alpha = mbrTicket($s, 0, 'Alpha', 'low', 0);        // target +168h
    $bravo = mbrTicket($s, 1, 'Bravo', 'urgent', 10);    // target +4h
    $charlie = mbrTicket($s, 2, 'Charlie', 'normal', 20); // target +48h
    $delta = mbrTicket($s, 3, 'Delta', 'high', 30);      // target +8h
    $echo = mbrTicket($s, 4, 'Echo', 'normal', 40);      // resolved below → no target
    app(MaintenanceService::class)->resolve($echo);

    Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

    return compact('alpha', 'bravo', 'charlie', 'delta', 'echo');
}

/** @return list<string> the titles on the board, in order, for these settings */
function mbrTitles(object $tc, array $s, array $params, string $role = 'Hotel Manager'): array
{
    $html = (string) $tc->actingAs(operatorUser($s, [], $role))->get(route('app.maintenance', $params))->assertOk()->getContent();
    preg_match_all('/<tr[^>]*data-gl-id="[^"]*"[^>]*>.*?class="vy-gl-name vy-rowopen"[^>]*>([^<]+)</s', $html, $m);

    return array_map('trim', $m[1]);
}

/** @return array<string, string> each keyed control link (`data-gl-key`) → its decoded href */
function mbrLinks(string $html): array
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

// ── The dialogs and the drawer ─────────────────────────────────────────────────────────────────────

it('⚠️⚠️ centres every board dialog and the work order page\'s two, by the shared class — and puts the drawer on the right', function () {
    $dialogs = array_values(array_filter(vyDialogCensus(), fn (array $d): bool => in_array($d['file'], [
        'livewire/maintenance-list.blade.php', 'livewire/maintenance-ticket-detail.blade.php',
    ], true)));

    $byFile = array_count_values(array_column($dialogs, 'file'));
    // New work order · Assign · Edit · Mark resolved · Cancel · the detail drawer — and the page's Reassign · Mark resolved
    expect($byFile['livewire/maintenance-list.blade.php'] ?? 0)->toBe(6)
        ->and($byFile['livewire/maintenance-ticket-detail.blade.php'] ?? 0)->toBe(2);

    foreach ($dialogs as $d) {
        expect(str_contains($d['backdrop'], 'vy-modal-backdrop'))->toBeTrue($d['file'].' x-show="'.$d['show'].'" is not centred by the class')
            ->and(str_contains($d['panel'], 'vy-modal-card'))->toBeTrue($d['file'].' x-show="'.$d['show'].'" is the see-through panel');
    }

    // The drawer is a dialog on the RIGHT: its backdrop carries the drawer modifier, and the layout aligns it to the end.
    $list = (string) file_get_contents(resource_path('views/livewire/maintenance-list.blade.php'));
    $shell = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    expect($list)->toContain('class="vy-modal-backdrop vy-drawer-backdrop"')
        ->and($shell)->toMatch('/\.vy-modal-backdrop\.vy-drawer-backdrop \{[^}]*justify-items: end/');
});

// ── Sorting ─────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ sorts by priority, time to target, reported and unit — time to target by the deadline the column shows', function () {
    $s = seedScenario(5);
    mbrCast($s);

    // Priority (the default, and the order before this pass): urgent → low, ties newest first.
    expect(mbrTitles($this, $s, []))->toBe(['Bravo', 'Delta', 'Echo', 'Charlie', 'Alpha'])
        ->and(mbrTitles($this, $s, ['sort' => 'priority', 'dir' => 'desc']))->toBe(['Alpha', 'Echo', 'Charlie', 'Delta', 'Bravo']);

    // Time to target: soonest deadline first (opened + the priority's target); a closed ticket has none and goes last.
    expect(mbrTitles($this, $s, ['sort' => 'due']))->toBe(['Bravo', 'Delta', 'Charlie', 'Alpha', 'Echo'])
        ->and(mbrTitles($this, $s, ['sort' => 'due', 'dir' => 'desc']))->toBe(['Alpha', 'Charlie', 'Delta', 'Bravo', 'Echo']);

    // Reported: newest first by default.
    expect(mbrTitles($this, $s, ['sort' => 'reported']))->toBe(['Echo', 'Delta', 'Charlie', 'Bravo', 'Alpha'])
        ->and(mbrTitles($this, $s, ['sort' => 'reported', 'dir' => 'asc']))->toBe(['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo']);

    // Unit: A–Z by the unit's code.
    expect(mbrTitles($this, $s, ['sort' => 'unit']))->toBe(['Alpha', 'Delta', 'Charlie', 'Echo', 'Bravo']);
});

it('⚠️ refuses a sort, direction or page size it does not offer — never an error', function () {
    $s = seedScenario(5);
    mbrCast($s);
    $default = ['Bravo', 'Delta', 'Echo', 'Charlie', 'Alpha'];

    foreach (['title', 'cost_minor', 'id; drop table maintenance_tickets'] as $sort) {
        expect(mbrTitles($this, $s, ['sort' => $sort]))->toBe($default);
    }
    expect(mbrTitles($this, $s, ['sort' => 'due', 'dir' => 'sideways']))->toBe(mbrTitles($this, $s, ['sort' => 'due']));

    $this->actingAs(operatorUser($s))->get('/app/maintenance?sort[]=x&dir[]=y&per[]=z&filter[]=q&q[]=a')->assertOk();
});

// ── Paging and the footer ──────────────────────────────────────────────────────────────────────────

it('⚠️ pages 25, 50 or 100 at a time, with a footer outside the table that says what is shown', function () {
    $s = seedScenario(1);
    foreach (range(1, 30) as $i) {
        mbrTicket($s, 0, 'Job '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'normal', $i);
    }
    $manager = operatorUser($s);

    $page = $this->actingAs($manager)->get('/app/maintenance')->assertOk();
    $html = (string) $page->getContent();
    $page->assertSeeText('1–25 of 30 work orders');
    expect(substr_count($html, 'data-gl-id="'))->toBe(25);

    preg_match('/<tbody\b.*?<\/tbody>/s', $html, $tbody);
    expect($tbody)->not->toBeEmpty();
    expect(str_contains($tbody[0], 'data-gl-foot'))->toBeFalse();
    expect($html)->toContain('data-gl-foot');

    // ⚠️ The old footer said "from the last 7 days" — while listing open work orders of ANY age.
    $page->assertDontSeeText('from the last 7 days')->assertSeeText('open, and resolved in the last 7 days');

    expect(substr_count((string) $this->actingAs($manager)->get('/app/maintenance?per=50')->getContent(), 'data-gl-id="'))->toBe(30)
        ->and(substr_count((string) $this->actingAs($manager)->get('/app/maintenance?page=2')->getContent(), 'data-gl-id="'))->toBe(5)
        ->and(substr_count((string) $this->actingAs($manager)->get('/app/maintenance?per=37')->getContent(), 'data-gl-id="'))->toBe(25);
    assertNoRawBlade($page);
});

it('⚠️ keeps every setting on every link — filter, sort, page size and search travel together', function () {
    $s = seedScenario(1);
    foreach (range(1, 60) as $i) {
        mbrTicket($s, 0, 'Pump '.$i, 'urgent', $i);
    }

    $links = mbrLinks((string) $this->actingAs(operatorUser($s))
        ->get('/app/maintenance?filter=urgent&sort=due&dir=desc&per=50&q=Pump')->assertOk()->getContent());

    expect(array_keys($links))->toContain('chip-all', 'chip-urgent', 'sort-priority', 'sort-due', 'sort-reported', 'sort-unit', 'per-100', 'page-next');

    expect($links['chip-all'])->not->toContain('filter=');
    expect($links['chip-all'])->toContain('sort=due', 'dir=desc', 'per=50', 'q=Pump');
    expect($links['sort-reported'])->toContain('sort=reported', 'filter=urgent', 'per=50', 'q=Pump');
    expect($links['sort-reported'])->not->toContain('page=');
    expect($links['per-100'])->toContain('per=100', 'filter=urgent', 'sort=due', 'dir=desc', 'q=Pump');
    expect($links['page-next'])->toContain('page=2', 'filter=urgent', 'sort=due', 'dir=desc', 'per=50', 'q=Pump');
});

it('⚠️ filters with chips that count what clicking them shows — the bare status dropdown is gone', function () {
    $s = seedScenario(5);
    mbrCast($s);

    $html = (string) $this->actingAs(operatorUser($s))->get('/app/maintenance')->assertOk()->getContent();
    expect($html)->not->toContain('<select name="filter"');
    // All 5 · Active 4 · Urgent 1 · Resolved 1 (Echo)
    expect((bool) preg_match('/data-gl-key="chip-all"[^>]*>\s*All<span class="vy-gl-count">5</', $html))->toBeTrue()
        ->and((bool) preg_match('/data-gl-key="chip-active"[^>]*>\s*Active<span class="vy-gl-count">4</', $html))->toBeTrue()
        ->and((bool) preg_match('/data-gl-key="chip-urgent"[^>]*>\s*Urgent<span class="vy-gl-count">1</', $html))->toBeTrue()
        ->and((bool) preg_match('/data-gl-key="chip-done"[^>]*>\s*Resolved<span class="vy-gl-count">1</', $html))->toBeTrue();

    expect(mbrTitles($this, $s, ['filter' => 'done']))->toBe(['Echo'])
        ->and(mbrTitles($this, $s, ['filter' => 'urgent']))->toBe(['Bravo']);

    // …and they count inside the search you typed: clicking a chip then shows exactly that many.
    $searched = (string) $this->actingAs(operatorUser($s))->get('/app/maintenance?q=Bravo')->assertOk()->getContent();
    expect((bool) preg_match('/data-gl-key="chip-all"[^>]*>\s*All<span class="vy-gl-count">1</', $searched))->toBeTrue()
        ->and((bool) preg_match('/data-gl-key="chip-urgent"[^>]*>\s*Urgent<span class="vy-gl-count">1</', $searched))->toBeTrue()
        ->and((bool) preg_match('/data-gl-key="chip-done"[^>]*>\s*Resolved<span class="vy-gl-count">0</', $searched))->toBeTrue();
});

it('⚠️ lets the table frame stop scrolling sideways only where the table was measured to fit', function () {
    // 🖥 A window breakpoint alone clipped the table's last two columns at 1180 px — 995 px of table in an 877 px card,
    // with sideways scrolling switched off above 1100 px and the card clipping. So scrolling is the default, and the
    // sticky header (which needs the frame NOT to scroll) is turned on only where the list script measured a fit.
    $shell = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    $script = (string) file_get_contents(resource_path('views/partials/operator-list-script.blade.php'));

    expect($shell)->toContain('.vy-gl-scroll.is-roomy { overflow: visible; }')
        ->and($shell)->not->toContain('.vy-gl-scroll { overflow: visible; }')
        ->and($script)->toContain("box.classList.toggle('is-roomy', !! table && table.offsetWidth <= box.clientWidth + 1)");
});

it('⚠️ says "1 unit blocked", not "1 units blocked"', function () {
    $s = seedScenario(1);
    mbrTicket($s, 0, 'Flooded bathroom', 'urgent', 0, ['takes_out_of_service' => true]);

    $this->actingAs(operatorUser($s))->get('/app/maintenance')->assertOk()
        ->assertSeeText('1 unit blocked')->assertDontSeeText('1 units blocked');
});

// ── Row actions: who is offered what ────────────────────────────────────────────────────────────────

it('⚠️⚠️ offers each action only where the ticket\'s state and the viewer\'s permission allow it', function () {
    $s = seedScenario(5);
    $cast = mbrCast($s);

    $actions = function (string $role, MaintenanceTicket $ticket) use ($s): string {
        $html = (string) $this->actingAs(operatorUser($s, [], $role))->get('/app/maintenance')->assertOk()->getContent();
        preg_match('/data-gl-id="'.$ticket->id.'"[^>]*data-mt-actions="([^"]*)"/', $html, $m);

        return $m[1] ?? 'MISSING';
    };

    // A manager, on an open ticket: everything that applies.
    expect($actions('Hotel Manager', $cast['bravo']))->toBe('edit assign start resolve cancel');
    // …on a RESOLVED one: nothing that writes — the old board offered Assign there, which the server refuses.
    expect($actions('Hotel Manager', $cast['echo']))->toBe('');

    // A role without maintenance.manage reads the board and is offered no write, and no write dialog is on its page.
    expect($actions('Front Desk', $cast['bravo']))->toBe('');
    $frontDesk = (string) $this->actingAs(operatorUser($s, [], 'Front Desk'))->get('/app/maintenance')->assertOk()->getContent();
    foreach (['vy-mt-edit-t', 'vy-mt-cancel-t', 'vy-mt-resolve-t', 'vy-mt-assign-t', 'vy-mt-new-t'] as $id) {
        expect(str_contains($frontDesk, 'id="'.$id.'"'))->toBeFalse($id.' is offered to a role that cannot use it');
    }
})->group('security');

// ── Edit: a real write, only while the work is open ─────────────────────────────────────────────────

it('⚠️⚠️ edits an open work order from the list — audited by field — and comes back to the list, where you were', function () {
    $s = seedScenario(1);
    $ticket = mbrTicket($s, 0, 'Tap drips', 'normal', 0, ['description' => 'Kitchen tap']);
    Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));

    $this->actingAs(operatorUser($s))->post(route('app.maintenance.update', $ticket->id), [
        'title' => 'Tap drips constantly', 'description' => 'Kitchen mixer tap, cold side', 'category' => 'plumbing', 'priority' => 'urgent',
        'return' => 'list', 'return_query' => 'filter=urgent&sort=due&page=2&evil=1&q[]=x',
    ])->assertRedirect(route('app.maintenance').'?filter=urgent&sort=due&page=2')->assertSessionHas('mt_notice');

    $fresh = $ticket->fresh();
    expect($fresh->title)->toBe('Tap drips constantly')->and($fresh->priority)->toBe('urgent')
        ->and($fresh->description)->toBe('Kitchen mixer tap, cold side');

    $audit = DB::table('audit_events')->where('subject_id', $ticket->id)->where('action', 'maintenance_ticket.updated')->first();
    expect($audit)->not->toBeNull();
    $after = json_decode((string) $audit->after, true);
    expect($after['fields'])->toBe(['title', 'description', 'priority'])
        // the priority's value is recorded; free text is named, never copied
        ->and($after['priority'] ?? null)->toBe('urgent')
        ->and(str_contains((string) $audit->after, 'constantly'))->toBeFalse();

    // Without return=list, the work order page keeps its own behaviour.
    $this->actingAs(operatorUser($s))->post(route('app.maintenance.update', $ticket->id), [
        'title' => 'Tap drips constantly', 'category' => 'plumbing', 'priority' => 'high',
    ])->assertRedirect(route('app.maintenance.show', $ticket->id));
})->group('security');

it('⚠️⚠️ refuses to edit a resolved or cancelled work order — its title is already on the owner\'s expense line', function () {
    $s = seedScenario(2);
    $resolved = mbrTicket($s, 0, 'Blind snapped', 'normal', 0);
    app(MaintenanceService::class)->resolve($resolved);
    $cancelled = mbrTicket($s, 1, 'Door sticks', 'low', 5);
    app(MaintenanceService::class)->cancel($cancelled);

    foreach ([$resolved, $cancelled] as $ticket) {
        $this->actingAs(operatorUser($s))->post(route('app.maintenance.update', $ticket->id), [
            'title' => 'Renamed', 'category' => 'other', 'priority' => 'urgent', 'return' => 'list',
        ])->assertRedirect(route('app.maintenance'))->assertSessionHas('mt_error');
        expect($ticket->fresh()->title)->not->toBe('Renamed');
    }
})->group('security');

it('⚠️ refuses a category or priority it does not know — only the real lists reach the work order', function () {
    $s = seedScenario(1);
    $ticket = mbrTicket($s, 0, 'Sink blocked', 'normal', 0);

    $this->actingAs(operatorUser($s))->post(route('app.maintenance.update', $ticket->id), [
        'title' => 'Sink blocked', 'category' => 'magic', 'priority' => 'high',
    ])->assertSessionHasErrors('category');
    $this->actingAs(operatorUser($s))->post(route('app.maintenance.update', $ticket->id), [
        'title' => 'Sink blocked', 'category' => 'plumbing', 'priority' => 'whenever',
    ])->assertSessionHasErrors('priority');

    $fresh = $ticket->fresh();
    expect($fresh->category)->toBe('plumbing')->and($fresh->priority)->toBe('normal');
})->group('security');

it('⚠️⚠️ gates Edit like every maintenance write — the permission, the organisation, the properties you may access', function () {
    $s = seedScenario(1);
    $ticket = mbrTicket($s, 0, 'Fan rattles', 'normal', 0);

    // no maintenance.manage → 403
    $this->actingAs(operatorUser($s, [], 'Front Desk'))->post(route('app.maintenance.update', $ticket->id), [
        'title' => 'X', 'category' => 'other', 'priority' => 'low',
    ])->assertForbidden();

    // another organisation's ticket → 404
    $other = seedScenario(1);
    $foreign = mbrTicket($other, 0, 'Their ticket', 'normal', 0);
    app(TenantContext::class)->set((string) $s['org']->id);
    $this->actingAs(operatorUser($s))->post(route('app.maintenance.update', $foreign->id), [
        'title' => 'Hijacked', 'category' => 'other', 'priority' => 'low',
    ])->assertNotFound();

    // a manager scoped to ONE property (the property_user allow-list) cannot edit a ticket on another
    $second = secondProperty($s);
    $roomType = RoomType::create(['property_id' => $second->id, 'name' => 'Std', 'kind' => 'room']);
    $farUnit = Unit::create(['property_id' => $second->id, 'room_type_id' => $roomType->id, 'code' => 'FAR1']);
    $far = app(MaintenanceService::class)->open(['unit_id' => $farUnit->id, 'title' => 'Far away', 'category' => 'other', 'priority' => 'low']);
    $scoped = operatorUser($s);
    DB::table('property_user')->insert(['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id, 'user_id' => $scoped->id,
        'property_id' => $s['property']->id, 'created_at' => now(), 'updated_at' => now()]);
    $this->actingAs($scoped)->post(route('app.maintenance.update', $far->id), [
        'title' => 'Renamed from afar', 'category' => 'other', 'priority' => 'low',
    ])->assertNotFound();

    // a paused (read-only) workspace changes nothing
    app(TenantContext::class)->runAsSuperAdmin(fn () => Organization::query()->whereKey($s['org']->id)->update(['paused_until' => now()->addDays(3)]));
    $this->actingAs(operatorUser($s))->post(route('app.maintenance.update', $ticket->id), [
        'title' => 'Paused edit', 'category' => 'other', 'priority' => 'low', 'return' => 'list',
    ])->assertSessionHas('mt_error');

    expect($ticket->fresh()->title)->toBe('Fan rattles')
        ->and(app(TenantContext::class)->runAsSuperAdmin(fn () => MaintenanceTicket::query()->whereKey($foreign->id)->value('title')))->toBe('Their ticket')
        ->and(app(TenantContext::class)->runAsSuperAdmin(fn () => MaintenanceTicket::query()->whereKey($far->id)->value('title')))->toBe('Far away');
})->group('security');

// ── Cancel from the list: the honest delete ─────────────────────────────────────────────────────────

it('⚠️⚠️ cancels from the list — the history stays, the room goes back on sale, and you come back to the list', function () {
    $s = seedScenario(1);
    $ticket = mbrTicket($s, 0, 'Ceiling leak', 'urgent', 0, ['takes_out_of_service' => true]);
    expect($s['units'][0]->refresh()->condition_status)->toBe('out_of_service');

    $this->actingAs(operatorUser($s))->post(route('app.maintenance.cancel', $ticket->id), [
        'return' => 'list', 'return_query' => 'filter=ooo',
    ])->assertRedirect(route('app.maintenance').'?filter=ooo')->assertSessionHas('mt_notice');

    $fresh = $ticket->fresh();
    expect($fresh)->not->toBeNull()                                   // never deleted
        ->and($fresh->status)->toBe('cancelled')
        ->and($s['units'][0]->refresh()->condition_status)->toBe('in_service');   // back on sale

    // and from the work order page, cancel still lands on the work order page
    $again = mbrTicket($s, 0, 'Second leak', 'normal', 5);
    $this->actingAs(operatorUser($s))->post(route('app.maintenance.cancel', $again->id))
        ->assertRedirect(route('app.maintenance.show', $again->id));
})->group('security');

it('⚠️⚠️ never sends you anywhere but the Maintenance board on the way back — the return is rebuilt from an allow-list', function () {
    $s = seedScenario(1);
    $ticket = mbrTicket($s, 0, 'Loose tile', 'normal', 0);
    $board = route('app.maintenance');

    foreach (['//evil.example/x', 'https://evil.example/', '/app/billing', "filter=ooo\r\nLocation: https://evil.example"] as $rq) {
        $target = (string) $this->actingAs(operatorUser($s))->post(route('app.maintenance.update', $ticket->id), [
            'title' => 'Loose tile', 'category' => 'other', 'priority' => 'low', 'return' => 'list', 'return_query' => $rq,
        ])->headers->get('Location');

        expect(parse_url($target, PHP_URL_PATH))->toBe(parse_url($board, PHP_URL_PATH), "return_query={$rq} sent you to {$target}")
            ->and(parse_url($target, PHP_URL_HOST))->toBe(parse_url($board, PHP_URL_HOST))
            ->and($target)->not->toContain('evil');
    }
})->group('security');

it('⚠️ tells whoever reported it from the board\'s Mark resolved, as the work order page does — on unless turned off', function () {
    // The review of this pass found it: the board's dialog never sent `notify`, and the controller reads a missing key as
    // "do not notify" — so a work order resolved from the board never told its reporter, silently, while the same action on
    // the work order page does by default. (The old board's inline form never sent it either.)
    Notification::fake();
    $s = seedScenario(1);
    $reporter = operatorUser($s, ['name' => 'Front Deskington'], 'Front Desk');
    $ticket = mbrTicket($s, 0, 'Lamp flickers', 'normal', 0, ['reported_by' => $reporter->id]);
    Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));
    $manager = operatorUser($s);

    $html = (string) $this->actingAs($manager)->get('/app/maintenance')->assertOk()->getContent();
    $from = strpos($html, 'id="vy-mt-resolve-t"');
    $to = strpos($html, 'id="vy-mt-cancel-t"');
    expect($from)->not->toBeFalse()->and($to)->toBeGreaterThan($from);
    $dialog = substr($html, (int) $from, (int) $to - (int) $from);
    expect($dialog)->toContain('name="notify"')
        ->and($dialog)->toContain('Notify the front desk')
        ->and($html)->toContain('"reporter":"Front Deskington"');

    // What the board posts: the reporter is told, and you land back on the board as you had it.
    $this->actingAs($manager)->post(route('app.maintenance.resolve', $ticket->id), [
        'notify' => '1', 'return' => 'list', 'return_query' => 'filter=active',
    ])->assertRedirect(route('app.maintenance').'?filter=active');
    Notification::assertSentTo($reporter, MaintenanceResolvedNotification::class);
});

it('⚠️ an edit the server refuses reopens with what was typed, and says why inside the dialog', function () {
    $s = seedScenario(1);
    $ticket = mbrTicket($s, 0, 'Shower cold', 'normal', 0);
    $manager = operatorUser($s);

    $this->actingAs($manager)->from('/app/maintenance?filter=active')->post(route('app.maintenance.update', $ticket->id), [
        'title' => '', 'description' => 'Typed but refused', 'category' => 'plumbing', 'priority' => 'high',
        'return' => 'list', '_ticket' => $ticket->id,
    ])->assertRedirect('/app/maintenance?filter=active');

    $html = (string) $this->actingAs($manager)->get('/app/maintenance?filter=active')->assertOk()->getContent();
    $at = strpos($html, '<div class="vy-gl-errors" role="alert">');
    expect($at)->not->toBeFalse();
    expect(strpos($html, 'id="vy-mt-edit-t"'))->toBeLessThan($at);
    expect($html)->toContain('Typed but refused');
    expect($ticket->fresh()->title)->toBe('Shower cold');
});

it('keeps the board a read view: no public method but render on the component', function () {
    $publics = collect((new ReflectionClass(MaintenanceList::class))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn (ReflectionMethod $m): bool => $m->getDeclaringClass()->getName() === MaintenanceList::class)
        ->map(fn (ReflectionMethod $m): string => $m->getName())
        ->reject(fn (string $n): bool => str_starts_with($n, '__') || str_starts_with($n, 'mountResolves'))
        ->values()->all();

    expect($publics)->toBe(['render']);
});
