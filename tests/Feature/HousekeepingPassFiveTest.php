<?php

declare(strict_types=1);

use App\Models\HousekeepingTask;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// ⚠️⚠️ HOUSEKEEPING, 2026-10-05 (the founder: "Text is not visible completely. Serach box design is not good and its symbol is not
// well-aligned. Board and list buttons design is not good"). 🔬/📖 The page never used the shared list kit: its search was its own
// pill whose icon lost a cascade fight and sat at the top of the row; Board and List were bare <button>s wearing the browser's own
// look; the list was a 720 px table in a card that is narrower than that between ~860 and ~1,010 px windows; "In progress" tags had
// no colour (the tone was never defined). And it said "Today’s cleaning" over a list of every day's tasks. Test clock: 1 Aug 2026.

function hk5Task(array $s, array $o = []): HousekeepingTask
{
    return HousekeepingTask::create(array_merge([
        'organization_id' => $s['org']->id, 'property_id' => $s['property']->id,
        'unit_id' => $s['units'][0]->id, 'date' => '2026-08-01',
        'type' => 'departure', 'status' => 'pending', 'priority' => 'normal',
    ], $o));
}

function hk5Page(array $s, string $query = '', string $role = 'Hotel Manager'): string
{
    return (string) test()->actingAs(operatorUser($s, [], $role))->get(route('app.housekeeping').$query)->assertOk()->getContent();
}

function hk5Css(): string
{
    return (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
}

// ── his report: the search box and its symbol ───────────────────────────────────────────────────────────────────

it('⚠️⚠️ searches with the shared search box — its icon centred in a 40 px field, not the page’s own pill', function () {
    $s = seedScenario(1);
    $html = hk5Page($s);

    // 🔬 before: `.vy-input-group--pill` lost to `.vy-input-group` later in the file — the 14 px icon sat at the top of the row
    expect($html)->toContain('<form class="vy-gl-search" method="GET" action="'.route('app.housekeeping').'" role="search">')
        ->toContain('class="vy-gl-search-in"')
        ->toContain('placeholder="Search room, type or housekeeper"');
    // (the view's own markup — the layout's stylesheet ships on every page and still defines the pill for other screens)
    expect((string) file_get_contents(resource_path('views/livewire/housekeeping-board.blade.php')))->not->toContain('vy-input-group--pill');
});

// ── his report: the Board and List buttons ──────────────────────────────────────────────────────────────────────

it('⚠️⚠️ switches Board and List with one real segmented control — pressed state, focus ring, never the browser’s button', function () {
    $s = seedScenario(1);
    $html = hk5Page($s);

    // 🔬 before: `.vy-seg-opt` on bare <button>s (no reset — the browser's outset border, grey fill and system font)
    expect($html)->toContain('<div class="vy-hk-views" role="group" aria-label="View">')
        ->toContain(':aria-pressed="view === \'list\' ? \'true\' : \'false\'"')
        ->toContain(':aria-pressed="view === \'board\' ? \'true\' : \'false\'"')
        ->not->toMatch('~<button type="button" class="vy-seg-opt"~');
    expect(hk5Css())->toMatch('~\.vy-hk-view \{[^}]*appearance: none~')
        ->toMatch('~\.vy-hk-view:focus-visible \{~');
});

it('drops the Cozy / Compact switch — it changed nothing', function () {
    $s = seedScenario(1);

    // 📖 `vy-hk-compact` had no CSS anywhere: a control that did nothing
    expect(hk5Page($s))->not->toContain('Compact')->not->toContain('Cozy');
});

// ── his report: text not visible completely ─────────────────────────────────────────────────────────────────────

it('⚠️⚠️ fits the list inside its card at every width — a narrow card stacks each room, every fact named', function () {
    $s = seedScenario(1);
    hk5Task($s);
    $html = hk5Page($s);

    // 🔬 before: a 720 px `.vy-utable` in a card narrower than that between ~860 and ~1,010 px windows — Ready and the action cut
    expect($html)->toContain('<table class="vy-gl-table vy-hk-table">')
        ->toContain('data-label="Day"')->toContain('data-label="Housekeeper"')->toContain('data-label="Stage"')->toContain('data-label="Ready"');
    expect(hk5Css())->toContain('.vy-hk .vy-gl-card { container: vy-hk-list / inline-size; }')
        ->toContain('.vy-hk .vy-gl-table { min-width: 0; }')
        ->toMatch('~@container vy-hk-list \(max-width: \d+px\) \{[^@]*?\.vy-hk \.vy-gl-table tbody tr \{[^}]*display: flex; flex-wrap: wrap~');
});

it('⚠️ gives "In progress" its colour — the tone was never defined, so the tag was blank', function () {
    expect(hk5Css())->toMatch('~\.vy-tag\.tone-in-progress \{[^}]*background~')
        ->toMatch('~\.vy-tag\.tone-in-progress \.vy-dot \{[^}]*var\(--vy-hk-progress\)~');
});

// ── honest words ──────────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ says which day each room is for — the page shows every day’s cleaning, not only today’s', function () {
    $s = seedScenario(2);   // the clock: 1 Aug 2026
    hk5Task($s, ['date' => '2026-08-01']);
    hk5Task($s, ['date' => '2026-08-02', 'unit_id' => $s['units'][1]->id]);
    hk5Task($s, ['date' => '2026-07-30', 'unit_id' => $s['units'][1]->id, 'type' => 'stayover']);
    $html = hk5Page($s);

    // 📖 before: "Today’s cleaning across your rooms" over a query with no date at all — tomorrow's rooms looked like today's
    expect($html)->toContain('Cleaning across your rooms, by day')->not->toContain('Today’s cleaning')
        ->toContain('>Today<')->toContain('>Tomorrow<')->toContain('Overdue · 30 Jul')
        // in BOTH views — the list row and the board card (HK-M24: the list alone answered for a card that had lost its day)
        ->and(substr_count($html, '<span class="vy-hk-day is-today">Today</span>'))->toBe(2);
});

it('⚠️ says only what the Assign dialogs really do — no "task list", no developer note, the load as open rooms', function () {
    $s = seedScenario(1);
    hk5Task($s);
    operatorUser($s, [], 'Housekeeping');   // someone on the roster, so the Assign dialogs are on the page
    $html = hk5Page($s);

    // 📖 before: "The room appears in their task list. (Device push isn’t wired — no fabricated notification.)" — there is no
    //    per-housekeeper task list; "Everyone sees their new rooms in their task list"; "{n} today" counted every open room
    expect($html)->not->toContain('task list')->not->toContain('Device push')->not->toContain('today</span>')
        ->toContain('It shows under their name on this board.');
});

it('says "Pending" in the detail’s stages as everywhere else, and asks for the photo it can take', function () {
    $s = seedScenario(1);
    hk5Task($s);
    $html = hk5Page($s);

    // 📖 before: the pipeline said "Dirty"; the empty photo row said "or flag an issue" — nothing here flags an issue
    expect($html)->not->toContain("'Dirty'")->not->toContain('flag an issue')
        ->toContain('Add a photo of the finished room.');
});

// ── the dialogs, and the way back after a photo ───────────────────────────────────────────────────────────────

it('⚠️⚠️ opens every dialog centred on the shared solid card — no hand-made backdrop with an inline display (A8)', function () {
    $s = seedScenario(1);
    hk5Task($s);
    $src = (string) file_get_contents(resource_path('views/livewire/housekeeping-board.blade.php'));

    // 🔬 before: Assign and Assign all had `display: flex` inline beside x-show — they opened as `block`, top-left
    expect(substr_count($src, 'class="vy-modal vy-modal-card'))->toBe(3)
        ->and(substr_count($src, 'class="vy-modal-backdrop"'))->toBe(3)
        ->and($src)->not->toContain('position: fixed; inset: 0');
});

it('⚠️ turns the lane’s arrow with a class, never a string :style that replaces its style (A10)', function () {
    $src = (string) file_get_contents(resource_path('views/livewire/housekeeping-board.blade.php'));

    expect($src)->not->toMatch('~:style="laneOpen\(~')
        ->toContain(':class="laneOpen(\'{{ $column[\'key\'] }}\') && \'is-open\'"');
});

it('⚠️ reopens a room’s detail after a photo is added or removed — the way back lands where it left', function () {
    $s = seedScenario(1);
    $task = hk5Task($s);

    // 📖 before: the photo routes redirect to ?task=… and the page built that task's detail — and never opened it
    expect(hk5Page($s, '?task='.$task->id))->toMatch('~detail: JSON\.parse\(\'\{[^\']*\\\\u0022id\\\\u0022:\\\\u0022'.$task->id.'~');
});

it('⚠️⚠️ offers no Assign while nobody is on the roster — says why once, and where to add one (🖥 the walk)', function () {
    $s = seedScenario(1);
    hk5Task($s);

    // 🖥 before: "Assign" on every room and "Assign all · N" — each opening a dialog with no one to pick and a button that failed
    $html = hk5Page($s, '', 'Property Owner');
    expect($html)->not->toContain('Assign all')->not->toContain('>Assign</button>')->not->toContain('+ Assign')
        ->toContain('No housekeepers on the roster yet, so rooms can’t be assigned.')
        ->toContain('href="'.route('app.roles').'"');
    // a manager without `users.manage` is told the same, without a link to a page that would refuse them
    expect(hk5Page($s, '', 'Hotel Manager'))->toContain('No housekeepers on the roster yet')->not->toContain('href="'.route('app.roles').'"');

    // with someone on the roster, the Assign controls are back
    operatorUser($s, [], 'Housekeeping');
    expect(hk5Page($s, '', 'Property Owner'))->toContain('Assign all')->not->toContain('so rooms can’t be assigned');
});

it('counts on each housekeeper chip exactly the rooms clicking it shows', function () {
    $s = seedScenario(2);
    $hk = operatorUser($s, [], 'Housekeeping');
    hk5Task($s, ['assigned_to' => $hk->id]);
    hk5Task($s, ['assigned_to' => $hk->id, 'status' => 'clean', 'unit_id' => $s['units'][1]->id]);
    hk5Task($s, ['unit_id' => $s['units'][1]->id, 'date' => '2026-08-02']);
    $html = hk5Page($s);

    // the chip counts both of their rooms (one open, one clean) — the rooms the filter shows — and the unassigned one
    expect($html)->toMatch('~data-gl-key="crew-'.$hk->id.'"[^>]*>[^<]*<span class="vy-gl-count">2</span>~')
        ->toMatch('~data-gl-key="crew-unassigned"[^>]*>[^<]*<span class="vy-gl-count">1</span>~');
});
