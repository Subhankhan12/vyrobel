<?php

declare(strict_types=1);

use App\Models\RoomType;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\UnitRateOverride;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE CALENDAR'S "+ NEW RESERVATION" DIALOG (2026-10-04, the founder: "the design is messed, guest first name, last name,
// and their boxes are not well aligned … also do additional works the way you did for previous pages"). 🔬 Measured locally
// before the change: the guest row was a three-column grid holding FIVE pieces (two labels, two boxes, the adults stepper), so
// "Last name" sat on the first line and its box on the second. Each test below failed first against the dialog as it stood.
// Test clock: 1 Aug 2026; prices are set for September.

function crnScenario(int $units = 2, ?int $nightlyMinor = 10000): array
{
    $s = seedScenario($units);
    if ($nightlyMinor !== null) {
        app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-30', Money::of($nightlyMinor, 'EUR'));
    }

    return $s;
}

function crnPage(User $user, string $query, array $session = []): string
{
    return (string) test()->actingAs($user)->withSession($session)->get(route('app.calendar').$query)->assertOk()->getContent();
}

const CRN_STAY = '?start=2026-09-01&days=7&new=1&ci=2026-09-02&co=2026-09-05';

// ── The founder's report: the guest's names and their boxes ─────────────────────────────────────

it('⚠️⚠️ puts each name with its own box — first name and last name side by side, the adults on their own', function () {
    $s = crnScenario();
    $html = crnPage(operatorUser($s, [], 'Front Desk'), CRN_STAY);

    // 🔬 before: label, box, label on the first line; box and stepper on the second
    expect($html)->toMatch('~<div class="vyo-pair">\s*<div class="vy-field">\s*<label class="vy-field-label" for="vy-rnr-gf">First name</label>\s*<input id="vy-rnr-gf"~')
        ->toMatch('~<div class="vy-field">\s*<label class="vy-field-label" for="vy-rnr-gl">Last name</label>\s*<input id="vy-rnr-gl"~')
        ->toContain('<span class="vy-field-label" id="vy-rnr-adults-l">Adults</span>')
        ->toContain('<label class="vy-field-label" for="vy-rnr-ci">Check-in</label>')
        ->toContain('<label class="vy-field-label" for="vy-rnr-co">Check-out</label>');
});

it('⚠️ is the shared solid dialog — a card, its own trap, Escape closing it cleanly', function () {
    $s = crnScenario();
    $html = crnPage(operatorUser($s, [], 'Front Desk'), CRN_STAY);

    // 🔬 before: the see-through glass panel (A9), Escape left ?new=1 in the address
    expect($html)->toContain('class="vy-modal vy-modal-card vy-modal--wide vyo-dlg vy-rnr"')
        ->toContain('x-on:keydown.escape.window="close()"')
        ->toContain('x-trap.noscroll="open"');
});

// ── The guest's name never travels in the address ───────────────────────────────────────────────

it('⚠️⚠️ never puts the guest’s name in the address — "Check rack" keeps it in this tab', function () {
    $s = crnScenario();
    $html = crnPage(operatorUser($s, [], 'Front Desk'), CRN_STAY.'&gf=Zelda&gl=Quillfeather');

    // 🔬 before: the GET form sent gf= and gl= — names in browser history and the server's access logs
    expect($html)->not->toContain('name="gf"')->not->toContain('name="gl"')
        ->not->toContain('Zelda')->not->toContain('Quillfeather')
        ->toContain('vyrobel:rack-new-reservation:guest');
});

// ── Honest words ─────────────────────────────────────────────────────────────────────────────────

it('says nothing it does not do — no matching of earlier guests, no email it cannot send', function () {
    $s = crnScenario();
    $html = crnPage(operatorUser($s, [], 'Front Desk'), CRN_STAY);

    // 🔬 before: both sentences on the dialog; every booking here creates a new guest, and no email is asked for
    expect($html)->not->toContain('Matches an existing profile')
        ->not->toContain('the guest gets a confirmation email')
        ->toContain('Open the full form');
});

// ── No dead ends ─────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ says a room type has no price yet, and where to set it, instead of "no units are free"', function () {
    $s = crnScenario(2, null);
    $html = crnPage(operatorUser($s, [], 'Hotel Manager'), CRN_STAY);

    expect($html)->toContain('No price for these nights yet')
        ->toContain('href="'.e(route('app.reservations.create', ['check_in' => '2026-09-02', 'check_out' => '2026-09-05'])).'">Set a price in the full form')
        ->not->toContain('No units are free (and priced)');

    $desk = crnPage(operatorUser($s, [], 'Front Desk'), CRN_STAY);
    expect($desk)->toContain('No price for these nights yet')->toContain('someone who manages rates')
        ->not->toContain('Set a price in the full form');
});

it('says when the property has no rooms', function () {
    $s = crnScenario();
    $far = secondProperty($s, 'Empty House');

    $html = crnPage(operatorUser($s, [], 'Hotel Manager'), '?new=1', ['current_property_id' => (string) $far->id]);

    expect($html)->toContain(e($far->name).' has no rooms yet');
});

// ── The money it shows is what is charged ────────────────────────────────────────────────────────

it('⚠️ shows a unit’s own price — the one the booking charges', function () {
    $s = crnScenario();   // €100 a night
    UnitRateOverride::create(['organization_id' => $s['org']->id, 'rate_plan_id' => $s['ratePlan']->id, 'unit_id' => $s['units'][0]->id,
        'date' => '2026-09-03', 'price_minor' => 15000, 'currency' => 'EUR']);

    $html = crnPage(operatorUser($s, [], 'Front Desk'), CRN_STAY);

    // 🔬 before: both units showed the room type's €300.00; the folio charges unit 1 its own €350.00
    expect($html)->toContain('data-total="€350.00"')->toContain('data-total="€300.00"');
});

it('says when taxes or fees will be added, and only then', function () {
    $s = crnScenario();
    $desk = operatorUser($s, [], 'Front Desk');
    expect(crnPage($desk, CRN_STAY))->not->toContain('taxes and fees are added on the folio');

    Tax::create(['property_id' => $s['property']->id, 'name' => 'City tax', 'mode' => 'per_night', 'amount_minor' => 200, 'currency' => 'EUR']);
    expect(crnPage($desk, CRN_STAY))->toContain('taxes and fees are added on the folio');
});

// ── Dates ────────────────────────────────────────────────────────────────────────────────────────

it('never starts a booking in the past — today when the calendar shows an earlier week', function () {
    $s = crnScenario();
    $html = crnPage(operatorUser($s, [], 'Front Desk'), '?start=2026-07-20&days=14&new=1');

    // 🔬 before: check-in defaulted to the window's first day, 12 days ago
    expect($html)->toMatch('~<input id="vy-rnr-ci" [^>]*value="2026-08-01" min="2026-08-01"~')
        ->not->toMatch('~<input id="vy-rnr-ci" [^>]*value="2026-07-20"~');   // the hidden `start` keeps the calendar's week, rightly
});

it('names a check-out before check-in, and a stay over the limit', function () {
    $s = crnScenario();
    $desk = operatorUser($s, [], 'Front Desk');

    expect(crnPage($desk, '?start=2026-09-01&days=7&new=1&ci=2026-09-05&co=2026-09-02'))->toContain('Check-out must be after check-in.');
    expect(crnPage($desk, '?start=2026-09-01&days=7&new=1&ci=2026-09-01&co=2026-12-15'))->toContain('A booking can be at most 90 nights');
});

// ── Click an empty night to book it ──────────────────────────────────────────────────────────────

it('⚠️ lets an empty night start a booking for that unit — never a booked or past night, never without the permission', function () {
    $s = crnScenario();
    book($s, ['unit_id' => $s['units'][0]->id]);   // unit 1 taken 1–3 Sep
    $u1 = (string) $s['units'][0]->id;
    $u2 = (string) $s['units'][1]->id;

    $html = crnPage(operatorUser($s, [], 'Front Desk'), '?start=2026-09-01&days=7');
    expect($html)->toContain('?start=2026-09-01&days=7&new=1&ci=2026-09-03&co=2026-09-04&unit='.$u1)   // free again from the 3rd
        ->toContain('?start=2026-09-01&days=7&new=1&ci=2026-09-01&co=2026-09-02&unit='.$u2)
        ->not->toContain('&ci=2026-09-01&co=2026-09-02&unit='.$u1)                                     // taken
        ->not->toContain('&ci=2026-09-02&co=2026-09-03&unit='.$u1);

    app(AvailabilityService::class)->recompute((string) $s['roomType']->id, '2026-07-25', '2026-08-10');   // rooms to sell around today
    $past = crnPage(operatorUser($s, [], 'Front Desk'), '?start=2026-07-28&days=7');
    expect($past)->not->toContain('&ci=2026-07-31&')->toContain('&ci=2026-08-01&');

    // ⚠️ a role that cannot book gets no booking link at all (OperatorControlGateTest's `new=1` absence)
    expect(crnPage(operatorUser($s, [], 'Housekeeping'), '?start=2026-09-01&days=7'))->not->toContain('new=1');
    expect(crnPage(operatorUser($s, [], 'Housekeeping'), CRN_STAY))->not->toContain('aria-labelledby="vy-rnr-title"');   // nor a typed-in new=1
});

it('picks the clicked unit when it is free for those dates', function () {
    $s = crnScenario();
    $u2 = (string) $s['units'][1]->id;

    expect(crnPage(operatorUser($s, [], 'Front Desk'), CRN_STAY.'&unit='.$u2))->toContain("sel: '".$u2."'");

    book($s, ['unit_id' => $s['units'][1]->id, 'check_in' => '2026-09-02', 'check_out' => '2026-09-05']);
    expect(crnPage(operatorUser($s, [], 'Front Desk'), CRN_STAY.'&unit='.$u2))->toContain("sel: ''");
});

// ── Smaller things ───────────────────────────────────────────────────────────────────────────────

it('names the travel agent source as the full form does', function () {
    $s = crnScenario();

    expect(crnPage(operatorUser($s, [], 'Front Desk'), CRN_STAY))->toContain('>Travel agent</button>');
});

it('⚠️ uses no inline style in the dialog, and every class it uses has a rule', function () {
    $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents(resource_path('views/livewire/operator/partials/rack-new-reservation.blade.php')));
    expect(substr_count($source, 'style="'))->toBe(0);

    $css = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    preg_match_all('/\sclass="([^"]*)"/', $source, $m);
    $missing = [];
    foreach ($m[1] as $attr) {
        $attr = (string) preg_replace('/\{\{.*?\}\}|@[a-z]+\s*\(.*?\)|@end[a-z]+/s', ' ', $attr);
        foreach (preg_split('/\s+/', $attr, -1, PREG_SPLIT_NO_EMPTY) as $token) {
            if (! str_ends_with($token, '-') && preg_match('/^vy[a-z]*-[a-z0-9-]+$/', $token)
                && preg_match('/\.'.preg_quote($token, '/').'(?![a-zA-Z0-9_-])/', $css) !== 1) {
                $missing[] = $token;
            }
        }
    }
    expect(array_values(array_unique($missing)))->toBe([]);
});

it('keeps the drag hint in one sentence', function () {
    $s = crnScenario();

    // 🔬 before: "Drag a booking" in one column, the rest of its sentence in another
    expect(crnPage(operatorUser($s, [], 'Front Desk'), '?start=2026-09-01&days=7'))
        ->toContain('<p class="vy-rack-hint"><strong>Drag a booking</strong> to a new date or unit');
});

it('offers no booking link and no dialog while the workspace is paused', function () {
    $s = crnScenario();
    pauseWorkspace($s);

    // the toolbar's button already hides itself; the night links and the dialog follow the same rule
    $html = crnPage(operatorUser($s, [], 'Hotel Manager'), '?start=2026-09-01&days=7&new=1');
    expect($html)->not->toContain('new=1')->not->toContain('aria-labelledby="vy-rnr-title"');   // the panel, not the page's stylesheet
});

// ── The independent review (2026-10-04) ──────────────────────────────────────────────────────────

it('⚠️ offers a night only where the room type has a room to sell', function () {
    $s = crnScenario(2);   // availability is published for 1–10 September only
    $u1 = (string) $s['units'][0]->id;

    // 🔬 before: a "+" on every night the unit's own row was empty — the dialog then dropped the whole room type, unexplained
    $html = crnPage(operatorUser($s, [], 'Front Desk'), '?start=2026-09-08&days=7');
    expect($html)->toContain('&ci=2026-09-09&co=2026-09-10&unit='.$u1)
        ->not->toContain('&ci=2026-09-12&co=2026-09-13&unit='.$u1);
});

it('⚠️ says a room type has no rate plan yet — and sends a rates person to Rates, not to the full form', function () {
    $s = crnScenario(2);
    $bare = RoomType::create(['property_id' => $s['property']->id, 'name' => 'Loft', 'kind' => 'room']);
    Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $bare->id, 'code' => 'L1']);
    app(AvailabilityService::class)->recompute((string) $bare->id, '2026-09-01', '2026-09-10');

    // 🔬 before: "Set a price in the full form" — where the full form says "add a rate plan on the Rates page"
    $html = crnPage(operatorUser($s, [], 'Hotel Manager'), CRN_STAY);
    expect($html)->toContain('Loft')->toContain('has no rate plan yet')
        ->toContain('href="'.e(route('app.rates')).'">Add a rate plan on the Rates page');

    expect(crnPage(operatorUser($s, [], 'Front Desk'), CRN_STAY))->toContain('Someone who manages rates can add one.');
});

it('turns a hand-made array in the address into nothing, never an error page', function () {
    $s = crnScenario();

    test()->actingAs(operatorUser($s, [], 'Front Desk'))
        ->get(route('app.calendar').'?new=1&unit[]=x&rt[]=y&ci[]=z')->assertOk();
});

it('says a check-in in the past cannot be booked from here', function () {
    $s = crnScenario();

    // 🔬 before: `min` stopped the GET only; Confirm posted its own hidden form and booked yesterday
    expect(crnPage(operatorUser($s, [], 'Front Desk'), '?start=2026-07-28&days=7&new=1&ci=2026-07-30&co=2026-08-02'))
        ->toContain('Check-in can’t be in the past');
});

it('says a property’s rooms are all closed, not that it has none', function () {
    $s = crnScenario();
    $far = secondProperty($s, 'Shut House');
    $rt = RoomType::create(['property_id' => $far->id, 'name' => 'Shut', 'kind' => 'room']);
    Unit::create(['property_id' => $far->id, 'room_type_id' => $rt->id, 'code' => 'S1', 'is_active' => false]);

    $html = crnPage(operatorUser($s, [], 'Hotel Manager'), '?new=1', ['current_property_id' => (string) $far->id]);
    expect($html)->toContain('None of '.e($far->name).'’s units can take a booking')->not->toContain('has no rooms yet');
});

it('marks today in the property’s own timezone — the column and the first bookable night agree', function () {
    $s = crnScenario();
    $s['property']->update(['timezone' => 'Pacific/Honolulu']);   // 09:00 UTC on 1 Aug is 23:00 on 31 Jul there
    app(AvailabilityService::class)->recompute((string) $s['roomType']->id, '2026-07-25', '2026-08-10');

    // 🔬 before: the column was marked by the app's UTC date (1 Aug) while the first bookable night was the property's (31 Jul)
    $html = crnPage(operatorUser($s, [], 'Front Desk'), '?start=2026-07-30&days=7');
    expect($html)->toMatch('~is-today\s*"\s*>\s*<div class="vy-day-dow">Fri</div>\s*<div class="vy-day-num">31</div>~')
        ->toContain('&ci=2026-07-31&co=2026-08-01&unit=');
});

it('keeps the guest’s name only across its own "Check rack" — never into the next booking', function () {
    $s = crnScenario();
    $user = operatorUser($s, [], 'Front Desk');
    $html = crnPage($user, CRN_STAY);

    // the GET carries a marker (never the name); the dialog restores the name only when it sees it, and forgets it otherwise
    expect($html)->toContain('<input type="hidden" name="kept" value="1">')
        ->toContain("new URLSearchParams(location.search).get('kept') === '1'");

    // …and only for the person who typed it: sign-out leaves the tab as it is, so the next person's page carries another mark
    $mark = fn (User $u): string => "who: '".substr(hash('sha256', (string) $u->getKey()), 0, 16)."'";
    $other = operatorUser($s, [], 'Front Desk');
    expect($html)->toContain($mark($user))->toContain('g.who === this.who');
    expect(crnPage($other, CRN_STAY))->toContain($mark($other))->not->toContain($mark($user));
});

it('⚠️ keeps the dialog’s whole script inside its attribute — no quote cuts it short', function () {
    $source = (string) file_get_contents(resource_path('views/livewire/operator/partials/rack-new-reservation.blade.php'));

    // 🔬 found on the walk: a comment inside x-data said "Check rack" — the first " ended the attribute, Alpine got half an
    //    object, and the dialog's every binding failed (38 console errors) while every server-side test stayed green
    preg_match('~<div x-data="(\{.*?)">~s', $source, $m);
    expect($m[1] ?? '')->toEndWith('}')->not->toContain('"');
});
