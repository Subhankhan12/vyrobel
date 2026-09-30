<?php

declare(strict_types=1);

use App\Models\Availability;
use App\Models\RatePlan;
use App\Models\RateSeason;
use App\Services\AvailabilityService;
use App\Services\RateAndRestrictionService;
use App\Services\SeasonalRateService;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE RATES PAGE — HOW IT LOOKS AND WHAT IT OFFERS (2026-10-01, the founder: "table design must be better, texts must look
// good, all texts and boxes well aligned … every single operation working"). What its writes DO is RatesPageFixesTest.
// ⚠️ These read the RENDERED page, not the source, except the last, which reads the stylesheet the page is drawn with. That a
// dialog opens centred, a drag selects nights and every box lines up was measured in a browser (docs/memory/HISTORY.md).

/** The rendered rates page, as a person holding the given role. */
function rrdPage($test, array $s, string $query = '?from=2026-09-01', string $role = 'Hotel Manager'): string
{
    return $test->actingAs(operatorUser($s, [], $role))->get('/app/rates'.$query)->assertOk()->getContent();
}

function rrdXPath(string $html): DOMXPath
{
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();

    return new DOMXPath($dom);
}

/** XPath: the element carries this class token. */
function rrdCls(string $class): string
{
    return "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";
}

/** @return list<string> */
function rrdTokens(DOMNode $n): array
{
    return $n instanceof DOMElement ? preg_split('/\s+/', trim($n->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) : [];
}

function rrdText(?DOMNode $n): string
{
    return $n === null ? '' : trim((string) preg_replace('/\s+/u', ' ', $n->textContent));
}

// ── Dialogs ──────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ opens every dialog as a centred panel on the shared backdrop — none placed by an inline style', function () {
    // 🖥 Before: every dialog on this page opened in the top-left corner (x-show erased the inline `display: flex` that centred it).
    $s = seedScenario(2);
    RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'name' => 'Saver', 'currency' => 'EUR', 'is_active' => false]);

    $xp = rrdXPath(rrdPage($this, $s));
    $dialogs = $xp->query('//*['.rrdCls('vy-rates').']//*[@role="dialog"]');

    // NON-VACUITY — the editor, bulk, per-unit (1 + 2 units), three season dialogs, add-plan and two per plan.
    expect($dialogs->length)->toBe(13);
    foreach ($dialogs as $dialog) {
        expect(rrdTokens($dialog->parentNode))->toContain('vy-modal-backdrop')
            ->and(rrdTokens($dialog))->toContain('vy-modal')->toContain('vy-modal-card')->toContain('vyr-dlg');
    }
    foreach (['vy-dlg-rates-ed-t', 'vy-dlg-bulk-t', 'vy-dlg-rg-unit-t', 'vy-dlg-season-t', 'vy-dlg-season-edit', 'vy-dlg-season-delete', 'vy-dlg-rg-plan-add'] as $id) {
        expect($xp->query('//*['.rrdCls('vy-rates').']//*[@role="dialog" and @aria-labelledby="'.$id.'"]')->length)->toBe(1, "no dialog is named by {$id}");
    }
    expect($xp->query('//*[contains(translate(@style, " ", ""), "position:fixed")]')->length)->toBe(0);
});

// ── The grid ─────────────────────────────────────────────────────────────────────────────────────

it('shows all fourteen nights, shades Friday and Saturday nights — the nights every price rule calls the weekend — and marks today', function () {
    $s = seedScenario(1);

    $xp = rrdXPath(rrdPage($this, $s));
    $heads = $xp->query('//th['.rrdCls('vyr-dhead').']');
    expect($heads->length)->toBe(14);

    $weekend = [];
    foreach ($heads as $h) {
        $day = rrdText($xp->query('.//*['.rrdCls('vyr-dweekday').']', $h)->item(0));
        if (in_array('is-weekend', rrdTokens($h), true)) {
            $weekend[] = $day;
        }
    }
    // 2026-09-01 is a Tuesday: the fortnight holds two Fridays and two Saturdays, and no Sunday is shaded.
    expect($weekend)->toBe(['Fri', 'Sat', 'Fri', 'Sat']);

    // today — only when the window holds it, and only once
    expect($xp->query('//th[@aria-current="date"]')->length)->toBe(0);
    $today = rrdXPath(rrdPage($this, $s, ''));
    $marked = $today->query('//th[@aria-current="date"]');
    expect($marked->length)->toBe(1)
        ->and(rrdTokens($marked->item(0)))->toContain('is-today')
        ->and(rrdText($today->query('.//*['.rrdCls('vyr-dday').']', $marked->item(0))->item(0)))->toBe(CarbonImmutable::today()->format('j'));
});

it('⚠️ draws open, last-one, full and closed nights each their own way — and the legend names each with the cell’s own class', function () {
    $s = seedScenario(3);
    $rt = $s['roomType']->id;
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-06', Money::of(12000, 'EUR'));
    Availability::query()->where('room_type_id', $rt)->whereDate('date', '2026-09-02')->update(['sold' => 3]);   // full
    Availability::query()->where('room_type_id', $rt)->whereDate('date', '2026-09-03')->update(['sold' => 2]);   // the last one
    app(RateAndRestrictionService::class)->setRestriction(['room_type_id' => $rt, 'stop_sell' => true], '2026-09-04', '2026-09-05');
    app(RateAndRestrictionService::class)->setRestriction(['room_type_id' => $rt, 'min_stay' => 2, 'max_stay' => 7,
        'closed_to_arrival' => true, 'closed_to_departure' => true], '2026-09-05', '2026-09-06');

    $xp = rrdXPath(rrdPage($this, $s));
    $cells = $xp->query('//tr['.rrdCls('vyr-typerow').']//button['.rrdCls('vyr-cellbtn').']');
    expect($cells->length)->toBe(14);

    $state = fn (int $i): array => array_values(array_intersect(rrdTokens($cells->item($i)), ['is-full', 'is-last', 'is-closed']));
    expect($state(0))->toBe([])
        ->and($state(1))->toBe(['is-full'])->and(rrdText($cells->item(1)))->toContain('Full')
        ->and($state(2))->toBe(['is-last'])->and(rrdText($cells->item(2)))->toContain('1 left')
        ->and($state(3))->toBe(['is-closed'])->and(rrdText($cells->item(3)))->toContain('Closed')
        // every rule the booking guard enforces is written on its night — otherwise a refused booking has no explanation
        ->and(rrdText($cells->item(4)))->toContain('Min 2')->toContain('Max 7')->toContain('No arr.')->toContain('No dep.');

    // every state a cell can take has a swatch with that class, and a word beside it (never colour alone)
    foreach (['is-open' => 'Open', 'is-last' => 'Last one', 'is-full' => 'Full', 'is-closed' => 'Closed', 'is-weekend' => 'Fri–Sat nights'] as $class => $word) {
        $sw = $xp->query('//*['.rrdCls('vyr-legend').']//i['.rrdCls('vyr-sw').' and '.rrdCls($class).']');
        expect($sw->length)->toBe(1, "the legend has no {$class} swatch")
            ->and(rrdText($sw->item(0)->parentNode))->toBe($word);
    }
});

it('steps a long price down a size so it stays inside its night', function () {
    // 🖥 "€1,250.00" at the grid's size is 68 px; a night holds about 60 px at 1440 px, and it ran into the next one.
    $s = seedScenario(1);
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-02', Money::of(12000, 'EUR'));
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-02', '2026-09-03', Money::of(125000, 'EUR'));
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-03', '2026-09-04', Money::of(1250000, 'EUR'));
    $xp = rrdXPath(rrdPage($this, $s));

    $size = fn (int $i): array => array_values(array_intersect(rrdTokens($xp->query('//tr['.rrdCls('vyr-typerow').']//*['.rrdCls('vyr-rate').']')->item($i)),
        ['is-long', 'is-longer', 'is-longest']));
    expect($size(0))->toBe([])            // €120.00
        ->and($size(1))->toBe(['is-long'])    // €1,250.00
        ->and($size(2))->toBe(['is-longer']); // €12,500.00
});

// ── The figures ──────────────────────────────────────────────────────────────────────────────────

it('⚠️ counts "Next 14 days" from today, whatever fortnight is on screen — the old pacing card counted the window', function () {
    $s = seedScenario(2);
    $rt = $s['roomType']->id;
    $today = CarbonImmutable::today();
    app(AvailabilityService::class)->recompute($rt, $today->toDateString(), $today->addDays(14)->toDateString());
    Availability::query()->where('room_type_id', $rt)->whereDate('date', '>=', $today->toDateString())
        ->whereDate('date', '<=', $today->addDays(13)->toDateString())->update(['sold' => 1]);   // 1 of 2 every night

    $html = rrdPage($this, $s, '?from='.$today->addDays(60)->toDateString());
    $xp = rrdXPath($html);
    $kpi = fn (string $label): ?DOMNode => $xp->query('//*['.rrdCls('vy-dash-kpi').'][.//*['.rrdCls('vy-dk-label').' and normalize-space(.)="'.$label.'"]]')->item(0);

    expect($xp->query('//*['.rrdCls('vy-dash-kpi').']')->length)->toBe(4)
        ->and(rrdText($xp->query('.//*['.rrdCls('vy-dk-val').']', $kpi('Next 14 days'))->item(0)))->toBe('50%')
        ->and(rrdText($xp->query('.//*['.rrdCls('vy-dk-sub').']', $kpi('Next 14 days'))->item(0)))->toContain('14 open unit-nights')
        // the window on screen is 60 days out, where nothing is published — its own figure says so
        ->and(rrdText($xp->query('.//*['.rrdCls('vy-dk-val').']', $kpi('Booked'))->item(0)))->toBe('—')
        ->and($html)->not->toContain('Pacing');
});

// ── The editor and bulk edit ─────────────────────────────────────────────────────────────────────

it('offers every rule the grid shows in the one editor, and opens it and bulk edit on the whole window', function () {
    $s = seedScenario(1);
    $xp = rrdXPath(rrdPage($this, $s));

    $editor = $xp->query('//form[@action="'.route('app.rates.update').'"]')->item(0);
    expect($editor)->not->toBeNull();
    $names = [];
    foreach ($xp->query('.//input|.//select', $editor) as $el) {
        $names[] = $el->getAttribute('name').($el->getAttribute('type') === 'radio' ? '='.$el->getAttribute('value') : '');
    }
    foreach (['room_type_id', 'start_date', 'end_date', 'set_rate', 'rate', 'set_min_stay', 'min_stay', 'set_max_stay', 'max_stay',
        'set_stop_sell', 'stop_sell=0', 'stop_sell=1', 'set_closed_to_arrival', 'closed_to_arrival=0', 'closed_to_arrival=1',
        'set_closed_to_departure', 'closed_to_departure=0', 'closed_to_departure=1'] as $name) {
        expect($names)->toContain($name);
    }

    $bulk = $xp->query('//form[@action="'.route('app.rates.bulk').'"]')->item(0);
    $bulkNames = [];
    foreach ($xp->query('.//input|.//select', $bulk) as $el) {
        $bulkNames[] = $el->getAttribute('name').($el->getAttribute('type') === 'radio' ? '='.$el->getAttribute('value') : '');
    }
    foreach (['room_type_id', 'start_date', 'end_date', 'amount', 'mode=increase_pct', 'mode=decrease_pct', 'mode=set_exact',
        'nights=whole', 'nights=weekends', 'nights=midweek', 'skip_booked'] as $name) {
        expect($bulkNames)->toContain($name);
    }
    // "All room types" is the room types on this page — the words say so (DEC-l, RatesPageFixesTest)
    expect(rrdText($xp->query('.//select[@name="room_type_id"]/option[@value="all"]', $bulk)->item(0)))->toBe('All room types on this page');

    // ⚠️ Both open on the WHOLE window: the bulk dialog used to post the editor's dates, which started as one night.
    // `Js::from()` writes JSON.parse('…') with \u0022 escapes — decode it the way the browser does
    preg_match("/JSON\\.parse\\('(.*)'\\)/s", $xp->query('//*['.rrdCls('vy-rates').']')->item(0)->getAttribute('x-data'), $m);
    $boot = json_decode((string) json_decode('"'.($m[1] ?? '').'"'), true);
    expect($boot['from'] ?? null)->toBe('2026-09-01')->and($boot['to'] ?? null)->toBe('2026-09-14');
});

it('⚠️ brings every form on the page back to the fortnight and filter it was started from', function () {
    $s = seedScenario(2);
    $xp = rrdXPath(rrdPage($this, $s));
    $forms = $xp->query('//*['.rrdCls('vy-rates').']//form');

    // editor, bulk, per-unit (1 + 2), season save / apply / edit / delete, add plan, rename and on/off sale
    expect($forms->length)->toBe(12);
    foreach ($forms as $form) {
        $label = $form->getAttribute('action') ?: $form->getAttribute('x-bind:action');
        expect($xp->query('.//input[@name="return" and @value="list"]', $form)->length)->toBe(1, "{$label} does not come back to the list")
            ->and($xp->query('.//input[@name="return_query"]', $form)->length)->toBe(1)
            ->and($form->getAttribute('x-on:submit'))->toContain('stamp(');
    }
});

// ── Who may change what ──────────────────────────────────────────────────────────────────────────

it('⚠️ shows a view-only role the grid with nothing to press, and a paused workspace nothing that would be refused', function () {
    $s = seedScenario(2);

    $view = rrdXPath(rrdPage($this, $s, '?from=2026-09-01', 'Reservation Agent'));
    expect($view->query('//*['.rrdCls('vyr-cellbtn').']')->length)->toBe(0)
        ->and($view->query('//*['.rrdCls('vyr-cell-static').']')->length)->toBeGreaterThan(0)
        ->and($view->query('//*['.rrdCls('vy-rates').']//*[@role="dialog"]')->length)->toBe(0)
        ->and($view->query('//*['.rrdCls('vyr-hint').']')->length)->toBe(0);

    pauseWorkspace($s);
    $html = rrdPage($this, $s);
    $paused = rrdXPath($html);
    expect($paused->query('//*['.rrdCls('vyr-cellbtn').']')->length)->toBe(0)
        ->and($paused->query('//*['.rrdCls('vyr-bar-tools').']')->length)->toBe(0)
        ->and($paused->query('//form[@action="'.route('app.seasons.store').'"]')->length)->toBe(0)
        ->and($html)->toContain('Workspace paused — seasons can be saved and applied again once it resumes.');
});

// ── Rate plans ───────────────────────────────────────────────────────────────────────────────────

it('says whether each plan is on sale, gives its controls real buttons, and adds a plan refundable unless switched off', function () {
    $s = seedScenario(1);
    RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'name' => 'Saver', 'currency' => 'EUR', 'is_active' => false]);
    $xp = rrdXPath(rrdPage($this, $s));

    $rows = $xp->query('//*['.rrdCls('vyr-plan').']');
    expect($rows->length)->toBe(2);
    $chip = fn (DOMNode $row): string => rrdText($xp->query('.//*['.rrdCls('vy-tag').']', $row)->item(0));
    $buttons = fn (DOMNode $row): array => array_map(fn ($b) => rrdText($b), iterator_to_array($xp->query('.//button['.rrdCls('vy-btn').']', $row)));
    expect($chip($rows->item(0)))->toBe('On sale')
        ->and($buttons($rows->item(0)))->toBe(['Set prices', 'Rename', 'Take off sale'])
        ->and($chip($rows->item(1)))->toBe('Off sale')
        ->and($buttons($rows->item(1)))->toBe(['Set prices', 'Rename', 'Put back on sale']);

    $refundable = $xp->query('//form[@action="'.route('app.inventory.rate-plans.store').'"]//input[@type="checkbox" and @name="is_refundable"]');
    expect($refundable->length)->toBe(1)
        ->and($refundable->item(0)->getAttribute('value'))->toBe('1')
        ->and($refundable->item(0)->hasAttribute('checked'))->toBeTrue();
});

it('⚠️ shows each plan’s prices for the fortnight and lets any base plan be priced — the grid shows only each main plan', function () {
    $s = seedScenario(1);
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-15', Money::of(12000, 'EUR'));
    $saver = RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'name' => 'Saver', 'currency' => 'EUR', 'is_refundable' => false]);
    app(RateAndRestrictionService::class)->setRate($saver, '2026-09-03', '2026-09-05', Money::of(9900, 'EUR'));
    $xp = rrdXPath(rrdPage($this, $s));

    $row = fn (string $name): DOMNode => $xp->query('//*['.rrdCls('vyr-plan').'][.//*['.rrdCls('vyr-plan-name').' and starts-with(normalize-space(.), "'.$name.'")]]')->item(0);
    $line = fn (string $name, string $class): string => rrdText($xp->query('.//*['.rrdCls($class).']', $row($name))->item(0));

    // the main plan is the one the grid prices, and says so; the second plan does not claim the grid
    expect($line('Flex', 'vyr-plan-sub'))->toBe('Std · Free cancellation · the grid above')
        ->and($line('Flex', 'vyr-plan-priced'))->toBe('Priced on 14 of 14 nights this fortnight · €120.00')
        ->and($line('Saver', 'vyr-plan-sub'))->toBe('Std · Non-refundable')
        ->and($line('Saver', 'vyr-plan-priced'))->toBe('Priced on 2 of 14 nights this fortnight · €99.00');

    // "Set prices" on the row opens the editor on that plan
    $set = $xp->query('.//button[normalize-space(.)="Set prices"]', $row('Saver'))->item(0);
    expect($set)->not->toBeNull()->and($set->getAttribute('x-on:click'))->toContain((string) $saver->id);

    // and the editor offers both plans of this room type, the main one first and chosen
    $options = $xp->query('//form[@action="'.route('app.rates.update').'"]//select[@name="rate_plan_id"]/option');
    expect(array_map(fn ($o) => $o->getAttribute('value'), iterator_to_array($options)))->toBe([(string) $s['ratePlan']->id, (string) $saver->id])
        ->and(rrdText($options->item(0)))->toBe('Flex — the plan the grid shows')
        ->and($options->item(0)->hasAttribute('selected'))->toBeTrue();
});

it('offers no plan choice where a room type has only one plan to price', function () {
    $s = seedScenario(1);
    $xp = rrdXPath(rrdPage($this, $s));

    expect($xp->query('//form[@action="'.route('app.rates.update').'"]//select[@name="rate_plan_id"]')->length)->toBe(0)
        ->and(rrdText($xp->query('//*['.rrdCls('vyr-plan-priced').']')->item(0)))->toBe('No prices in this fortnight');
});

// ── Seasons ──────────────────────────────────────────────────────────────────────────────────────

it('⚠️ shows every refusal a season save or edit can return, where the operator can read it', function () {
    // ⚠️ A REAL round trip, not a seeded bag: sessions are JSON-serialised here, and a ViewErrorBag put straight into the test
    //    session comes back EMPTY (Store::marshalErrorBag reads only its stored array shape) — the test would see nothing.
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $page = '/app/rates?from=2026-09-01';

    $html = $this->actingAs($manager)->from($page)->followingRedirects()->post(route('app.seasons.store'), [
        'rate_plan_id' => $s['ratePlan']->id, 'name' => '', 'starts_on' => '2026-09-05', 'ends_on' => '2026-09-02', 'price' => '120',
    ])->assertOk()->getContent();
    $formErrors = rrdText(rrdXPath($html)->query('//*['.rrdCls('vyr-form-errors').' and @role="alert"]')->item(0));
    expect($formErrors)->toContain('Give the season a name.')->toContain('The last night can’t be before the first night.');

    // an edit is refused at the top of the page — its dialog is closed again by the redirect
    $season = RateSeason::create(['rate_plan_id' => $s['ratePlan']->id, 'name' => 'High', 'starts_on' => '2026-09-03',
        'ends_on' => '2026-09-04', 'price_minor' => 30000, 'currency' => 'EUR']);
    $back = ['return' => 'list', 'return_query' => 'from=2026-09-01'];
    $alerts = fn (string $html): array => array_map(fn ($a) => rrdText($a), iterator_to_array(rrdXPath($html)->query('//*['.rrdCls('vyr-flash').' and @role="alert"]')));

    $html = $this->actingAs($manager)->followingRedirects()->put(route('app.seasons.update', $season->id), $back + [
        'name' => 'High', 'starts_on' => '2026-09-04', 'ends_on' => '2026-09-03', 'price' => '300',
    ])->assertOk()->getContent();
    expect($alerts($html))->toBe(['The last night can’t be before the first night.']);

    app(SeasonalRateService::class)->apply($season->fresh());
    $html = $this->actingAs($manager)->followingRedirects()->put(route('app.seasons.update', $season->id), $back + [
        'name' => 'Renamed', 'starts_on' => '2026-09-03', 'ends_on' => '2026-09-04', 'price' => '310',
    ])->assertOk()->getContent();
    expect($alerts($html))->toHaveCount(1)
        ->and($alerts($html)[0])->toContain('already been applied, so it cannot be edited');
});

it('offers no season form where there is no rate plan to set a season on — it could only be refused', function () {
    // 🖥 production, 2026-10-01: a property with no room types showed the season form with an EMPTY plan picker.
    $s = seedScenario(1);
    $s['ratePlan']->update(['is_active' => false]);   // the room type's only plan, off sale

    $html = rrdPage($this, $s);
    $xp = rrdXPath($html);
    expect($xp->query('//form[@action="'.route('app.seasons.store').'"]')->length)->toBe(0)
        ->and($html)->toContain('A season is set on a rate plan that is on sale');

    // …and with one on sale, the form is back, its picker offering exactly that plan
    $s['ratePlan']->update(['is_active' => true]);
    $options = rrdXPath(rrdPage($this, $s))->query('//form[@action="'.route('app.seasons.store').'"]//select[@name="rate_plan_id"]/option');
    expect($options->length)->toBe(1);
});

// ── The stylesheet ───────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ gives every class the Rates views use a rule in the stylesheet — an unstyled label is how this page broke', function () {
    // 🖥 Before: "Add a rate plan" printed its labels at 16px in the browser default, because they carried `vy-label`, a class no
    // rule defines. The page LOOKED built. This reads every class the three views write and finds each in the operator stylesheet.
    $css = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    $hooks = ['vyr-scope' => 'the season dialogs’ Alpine scope — a hook with no look of its own (SeasonEditDeleteTest)'];

    $missing = [];
    foreach (['livewire/operator/rates-grid.blade.php', 'livewire/operator/partials/rate-cell.blade.php', 'components/rates/unit-price-form.blade.php'] as $view) {
        $source = (string) file_get_contents(resource_path('views/'.$view));
        $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $source);
        preg_match_all('/\sclass="([^"]*)"/', $source, $m);
        foreach ($m[1] as $attr) {
            $attr = (string) preg_replace('/\{\{.*?\}\}|@[a-z]+\s*\(.*?\)|@end[a-z]+/s', ' ', $attr);
            foreach (preg_split('/\s+/', $attr, -1, PREG_SPLIT_NO_EMPTY) as $token) {
                if (! preg_match('/^vyr?-[a-z0-9-]+$/', $token) || isset($hooks[$token])) {
                    continue;
                }
                if (preg_match('/\.'.preg_quote($token, '/').'(?![a-zA-Z0-9_-])/', $css) !== 1) {
                    $missing[] = "{$view}: {$token}";
                }
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});

it('⚠️ keeps every positioned label inside the scroll box it belongs to', function () {
    // 🖥 production, 390 px: the season table's hidden "Actions" header (`vy-sr-only`, absolutely positioned) took <main> as its
    // containing block, escaped its scroll box and widened the page to 660 px (the body's clipping hid it). A scroll box must be
    // the containing block of what it scrolls.
    $css = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
    foreach (['.vyr-scroll', '.vyr-tablewrap'] as $box) {
        expect(preg_match('/'.preg_quote($box, '/').' \{[^}]*position: relative/', $css))->toBe(1, "{$box} is not a containing block");
    }
});
