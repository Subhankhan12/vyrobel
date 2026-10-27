<?php

declare(strict_types=1);

use App\Models\MaintenanceTicket;
use App\Models\Note;
use App\Services\MaintenanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;

uses(DatabaseTransactions::class);

// ⚠️⚠️ MAINTENANCE, 2026-10-05 (the founder: "Text is not visible completely. And note progress part ordered buttons design is not
// cool"). 🔬 Measured locally: the board's table is 945 px — 213 px past its card at a 1,024 px window, 589 px at 390 — so the right
// columns slid out of sight behind a hidden scrollbar. 📖 The work order's Note / Progress / Part ordered were inline-styled pills at
// 11.5 px, chosen by colour alone, with no keyboard or screen-reader state; and "Part ordered" only wrote a note — the board's
// "Awaiting parts" never followed. Each new test failed first against the pages as they stood. Test clock: 1 Aug 2026.

function mt5Ticket(array $s, array $o = []): MaintenanceTicket
{
    return app(MaintenanceService::class)->open(array_merge([
        'unit_id' => $s['units'][0]->id, 'title' => 'Bathroom extractor fan rattling and the shower drain slow to empty',
        'category' => 'plumbing', 'priority' => 'high', 'description' => 'Rattle from about 22:00.',
    ], $o));
}

function mt5Board(array $s, string $query = '', string $role = 'Hotel Manager'): string
{
    return (string) test()->actingAs(operatorUser($s, [], $role))->get(route('app.maintenance').$query)->assertOk()->getContent();
}

function mt5Order(array $s, MaintenanceTicket $t, string $role = 'Hotel Manager'): string
{
    return (string) test()->actingAs(operatorUser($s, [], $role))->get(route('app.maintenance.show', $t->id))->assertOk()->getContent();
}

function mt5Css(): string
{
    return (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
}

// ── his report: text not visible completely (the board) ─────────────────────────────────────────────────────────

it('⚠️⚠️ fits the board inside its card at every width — a narrow card stacks each work order, every fact named', function () {
    $s = seedScenario(1);
    mt5Ticket($s);
    $html = mt5Board($s);

    // 🔬 before: a 945 px table (min-width 940) in a 734 px card at 1,024 px — Technician, Time to target and Reported out of sight
    expect($html)->toContain('data-label="Unit"')->toContain('data-label="Priority"')->toContain('data-label="Status"')
        ->toContain('data-label="Assigned to"')->toContain('data-label="Time to target"')->toContain('data-label="Reported"');
    expect(mt5Css())->toContain('.vy-mnt .vy-gl-card { container: vy-mt-list / inline-size; }')
        ->toContain('.vy-mnt .vy-mt-table { min-width: 0; }')
        ->toMatch('~@container vy-mt-list \(max-width: \d+px\) \{[^@]*?\.vy-mnt \.vy-gl-table tbody tr \{[^}]*display: flex; flex-wrap: wrap~');
});

it('⚠️ calls the column "Assigned to" — not everyone a work order goes to is a technician', function () {
    $s = seedScenario(1);
    mt5Ticket($s);

    // 📖 before: "Technician" over every assignee, whatever their role
    expect(mt5Board($s))->toContain('>Assigned to<')->not->toContain('<th>Technician</th>');
});

// ── honest words on the board ─────────────────────────────────────────────────────────────────────────────────

it('⚠️ says what assigning does — it shows on this board; there is no per-person task list', function () {
    $s = seedScenario(1);
    mt5Ticket($s);

    // 📖 before: "The work order appears in their task list." — no such list exists anywhere
    expect(mt5Board($s))->not->toContain('task list')->toContain('It shows under their name on this board. Nothing is sent to their phone.');
});

it('⚠️ never calls someone "Busy" or "Available" from a count — it says the count', function () {
    $s = seedScenario(1);
    $tech = operatorUser($s, [], 'Maintenance');
    mt5Ticket($s, ['assigned_to' => $tech->id]);

    // 📖 before: "Busy" at 3+ open orders and "Available" below — a guess about a person, not a fact the system holds
    expect(mt5Board($s))->not->toContain('>Busy<')->not->toContain('>Available<')->toContain('1 open');
});

it('⚠️ says the out-of-order figure is tonight’s rate for the blocked units — not "revenue on hold"', function () {
    $src = i18nEnglishSource(resource_path('views/livewire/maintenance-list.blade.php'));   // I18N-01: the words as read

    expect($src)->not->toContain('revenue on hold')->toContain('<span>tonight’s rate for the units blocked</span>');
});

it('⚠️ takes the photo types the server takes, and brings an added photo back to the board', function () {
    $src = (string) file_get_contents(resource_path('views/livewire/maintenance-list.blade.php'));

    // 📖 before: accept="image/*" (the server takes jpeg, png, webp up to 5 MB) and the upload landed on the full record
    // (form.submit() skips the submit event, so the field stamps the board's address itself before sending)
    expect($src)->toContain('<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" capture="environment" class="vy-mt-file" @change="stamp($el.form); $el.form.submit()">')
        ->not->toContain('accept="image/*"');

    $s = seedScenario(1);
    $t = mt5Ticket($s);
    test()->actingAs(operatorUser($s))->post(route('app.maintenance.photos.store', $t->id), [
        'photo' => UploadedFile::fake()->image('fan.jpg', 40, 40), 'return' => 'list', 'return_query' => 'filter=open',
    ])->assertRedirect(route('app.maintenance').'?filter=open');
});

// ── his report: the Note / Progress / Part ordered buttons (the work order page) ───────────────────────────────────

it('⚠️⚠️ makes Note / Progress / Part ordered one real choice — a radio group, readable, chosen not by colour alone', function () {
    $s = seedScenario(1);
    $html = mt5Order($s, mt5Ticket($s));

    // 🔬 before: three inline-styled pills at 11.5 px, the choice shown by colour only, no keyboard or screen-reader state
    expect($html)->toContain('<div class="vy-wo-kinds" role="radiogroup" aria-label="Kind of update">')
        ->toContain('role="radio"')->toContain(':aria-checked="kind === \'note\' ? \'true\' : \'false\'"')
        ->not->toContain("background: 'rgba(255,255,255,.9)', color: '#C2410C'");
    expect(mt5Css())->toMatch('~\.vy-wo-kind \{[^}]*font-size: 13px~')
        ->toMatch('~\.vy-wo-kind\.is-on \{~')
        ->toMatch('~\.vy-wo-kind:focus-visible \{~');
});

it('keeps Post off until there is something to post', function () {
    $s = seedScenario(1);

    // 📖 before: faded to half opacity when empty, but still a working submit
    expect(mt5Order($s, mt5Ticket($s)))->toContain(':disabled="! body.trim()"');
});

it('⚠️⚠️ makes "Part ordered" do what it says — the part is marked awaited, and the board’s tag and panel follow', function () {
    $s = seedScenario(1);
    $t = mt5Ticket($s);

    // 📖 before: kind=part ("Part ordered") wrote a note and nothing else — "Awaiting parts" never showed on the board
    test()->actingAs(operatorUser($s))->post(route('app.maintenance.notes.store', $t->id), [
        'kind' => 'part', 'body' => 'Ordered from the supplier.', 'part_name' => 'Extractor fan motor', 'part_eta' => '2026-08-05',
    ])->assertRedirect(route('app.maintenance.show', $t->id));

    $fresh = $t->fresh();
    expect((bool) $fresh->awaiting_parts)->toBeTrue()
        ->and((string) $fresh->part_name)->toBe('Extractor fan motor')
        ->and(Note::query()->where('notable_id', $t->id)->where('body', 'Ordered from the supplier.')->exists())->toBeTrue();
    expect(mt5Order($s, $t))->toContain('name="part_name"')->toContain('id="vy-wo-part"');
});

// ── the work order page's layout and words ──────────────────────────────────────────────────────────────────────

it('⚠️ lets the details wrap on a narrow screen — never four fixed columns', function () {
    $s = seedScenario(1);
    $html = mt5Order($s, mt5Ticket($s));

    // 📖 before: grid-template-columns: repeat(4,1fr) inline — the values ran past the card on a phone
    expect($html)->toContain('<div class="vy-wo-facts">')->not->toContain('grid-template-columns:repeat(4,1fr)');
    expect(mt5Css())->toMatch('~\.vy-wo-facts \{[^}]*grid-template-columns: repeat\(auto-fit, minmax\(\d+px, 1fr\)\)~');
});

it('⚠️ offers Mark resolved once, and for an open work order too — as the board and the service do', function () {
    $s = seedScenario(1);
    $t = mt5Ticket($s);   // open

    // 📖 before: only "in progress" could be resolved here (the board and MaintenanceService allow open too), and the button was
    //    printed twice — in the header and again in the status card
    $html = mt5Order($s, $t);
    expect(substr_count($html, '@click="mr = true"'))->toBe(1);
});

it('labels the part fields where they can be seen', function () {
    $s = seedScenario(1);

    // 📖 before: "Part needed (e.g. thermostat)" only as a placeholder, the date box with no visible name
    expect(mt5Order($s, mt5Ticket($s)))->toContain('<label class="vy-field-label" for="vy-wo-manage-part">Part needed</label>')
        ->toContain('<label class="vy-field-label" for="vy-wo-manage-eta">Expected</label>');
});

it('labels "What was done" in the Mark resolved dialog — a box named only by its placeholder (the review)', function () {
    $s = seedScenario(1);

    // 📖 before: a plain <div> heading the box, so a screen reader named it by its placeholder ("e.g. Replaced the trap…"); the
    //    same dialog's Time spent and Parts cost, and the board's own "What was done", were real labels
    expect(mt5Order($s, mt5Ticket($s)))->toMatch('~<label[^>]*for="vy-lbl-maintenance-ticket-detail-resolution"[^>]*>What was done</label>~');
});

it('⚠️ says "Assignee", and offers "Notify" only when someone reported it — in words that fit what happens', function () {
    $s = seedScenario(1);
    $tech = operatorUser($s, ['name' => 'Tomas Tech'], 'Maintenance');
    $t = mt5Ticket($s, ['assigned_to' => $tech->id]);   // assigned; no reporter (opened with nobody signed in)

    // 📖 before: "Technician" under every assignee; "Notify the front desk" with no reporter did nothing, and always said "back online"
    $html = mt5Order($s, $t);
    expect($html)->toMatch('~Tomas Tech</div>\s*<div[^>]*>Assignee</div>~')->not->toMatch('~>Technician</div>~')
        ->not->toContain('it’s fixed')->not->toContain('Notify the front desk');   // (MT-M21, MT-M22: the first version looked at nothing)

    $reporter = operatorUser($s, ['name' => 'Rita Reporter'], 'Front Desk');
    $t->forceFill(['reported_by' => $reporter->id])->save();
    expect(mt5Order($s, $t->fresh()))->toContain('Tell Rita Reporter it’s fixed');
});
