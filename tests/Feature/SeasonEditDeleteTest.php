<?php

declare(strict_types=1);

use App\Livewire\Operator\RatesGrid;
use App\Models\AuditEvent;
use App\Models\RateCalendar;
use App\Models\RateSeason;
use App\Models\ReservationUnit;
use App\Services\RateAndRestrictionService;
use App\Services\SeasonalRateService;
use App\Support\Money\Money;
use App\Support\Property\PropertyContext;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

/**
 * ⚠️⚠️ SEASON EDIT, DELETE — AND WHY UNDO CANNOT EXIST (docs/SEASON-EDIT-DELETE.md).
 *
 * Applying a season WRITES nightly rows through `setRate()`'s `updateOrCreate`, and the price it replaces is
 * held nowhere — the audit row's `before` is literally `null`. So "undo" would have nothing to restore, and
 * a false undo is worse than none: the operator believes they are back where they started.
 *
 * ⚠️ Every assertion below ACTS — it applies, edits or deletes and then READS THE GRID — because reasoning
 * about a rate change is not proof.
 */
uses(DatabaseTransactions::class);

function sedFixture(): object
{
    $s = seedScenario(1);
    $plan = $s['ratePlan'];

    // A known grid price across the window, so "what was there before" is unambiguous.
    app(RateAndRestrictionService::class)->setRate($plan, '2026-09-01', '2026-09-06', Money::of(20000, 'EUR'));

    $season = RateSeason::create([
        'rate_plan_id' => $plan->getKey(),
        'name' => 'Probe '.Str::lower(Str::random(6)),
        'starts_on' => '2026-09-02', 'ends_on' => '2026-09-04',
        'price_minor' => 33300, 'currency' => 'EUR',
    ]);

    return (object) ['scenario' => $s, 'plan' => $plan, 'season' => $season];
}

function sedGrid(string $planId, string $date): ?int
{
    return RateCalendar::query()->where('rate_plan_id', $planId)->whereDate('date', $date)->value('price_minor');
}

function sedOperator(array $scenario, string $role = 'Hotel Manager')
{
    $user = operatorUser($scenario, [], $role);

    // ⚠️ TEAMS MODE: without the team id, `can()` fails SILENTLY TO FALSE and every gate below would
    //    "pass" by denying a user who actually holds the permission.
    setPermissionsTeamId($scenario['org']->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');

    return $user;
}

// ══ #1 · what applying writes — and #5, whether a booked stay moves ═════════════════════════════════

it('⚠️⚠️ applying WRITES nightly rows — and an existing reservation keeps the price it was booked at', function () {
    $f = sedFixture();
    $reservation = book($f->scenario, ['unit_id' => $f->scenario['units'][0]->id]);

    $bookedPrice = ReservationUnit::query()->where('reservation_id', $reservation->getKey())->value('price_minor');

    expect(sedGrid($f->plan->getKey(), '2026-09-03'))->toBe(20000);

    app(SeasonalRateService::class)->apply($f->season->fresh());

    // The GRID moved …
    expect(sedGrid($f->plan->getKey(), '2026-09-03'))->toBe(33300)
        // … and a night OUTSIDE the season did not.
        ->and(sedGrid($f->plan->getKey(), '2026-09-05'))->toBe(20000)
        // ⚠️⚠️ … and the booked stay did NOT. A price a guest agreed to cannot move under them, because
        //    `reservation_units.price_minor` snapshots it at booking. This is the answer to "can a season
        //    touch a booked date": it changes what is OFFERED, never what was AGREED.
        ->and(ReservationUnit::query()->where('reservation_id', $reservation->getKey())->value('price_minor'))
        ->toBe($bookedPrice);
});

// ══ #2 · ⚠️⚠️ THE SHARPEST TEST — undo is a lie, so none is offered ═════════════════════════════════

it('⚠️⚠️ DELETING an applied season does NOT restore the prices it replaced', function () {
    $f = sedFixture();
    app(SeasonalRateService::class)->apply($f->season->fresh());

    expect(sedGrid($f->plan->getKey(), '2026-09-03'))->toBe(33300);

    $operator = sedOperator($f->scenario);
    $this->actingAs($operator)->delete(route('app.seasons.destroy', $f->season->getKey()))->assertRedirect();

    // ⚠️ The season is gone …
    expect(RateSeason::query()->whereKey($f->season->getKey())->exists())->toBeFalse()
        // ⚠️⚠️ … and the night is STILL 333.00, not the 200.00 that was there before. Deleting a season is
        //    not an undo, and the interface says so rather than letting an operator infer otherwise.
        ->and(sedGrid($f->plan->getKey(), '2026-09-03'))->toBe(33300);
});

it('⚠️⚠️ nothing records the previous price — which is WHY no undo is offered', function () {
    $f = sedFixture();
    app(SeasonalRateService::class)->apply($f->season->fresh());

    $audit = AuditEvent::query()
        ->where('action', 'rate_calendar.updated')->latest('id')->first();

    expect($audit)->not->toBeNull()
        // ⚠️ The audit trail records WHAT WAS WRITTEN and not what it replaced. If this ever becomes
        //    non-null, an honest undo becomes possible and this file's conclusion should be revisited.
        ->and($audit->before)->toBeNull();
});

// ══ #3 · edit — the two-step is preserved, and an applied season is refused ═════════════════════════

it('⚠️⚠️ editing a DRAFT season saves WITHOUT applying — the two-step survives', function () {
    $f = sedFixture();
    $operator = sedOperator($f->scenario);

    $this->actingAs($operator)->put(route('app.seasons.update', $f->season->getKey()), [
        'name' => 'Renamed', 'starts_on' => '2026-09-02', 'ends_on' => '2026-09-03', 'price' => '444.00',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $fresh = $f->season->fresh();

    expect($fresh->name)->toBe('Renamed')
        ->and((int) $fresh->price_minor)->toBe(44400)
        // ⚠️⚠️ STILL UNAPPLIED, AND THE GRID IS UNTOUCHED. An edit that wrote immediately would destroy the
        //    safety the create path was built to have: the blast radius is shown BEFORE it lands.
        ->and($fresh->applied_at)->toBeNull()
        ->and(sedGrid($f->plan->getKey(), '2026-09-03'))->toBe(20000);
});

it('⚠️⚠️ editing an APPLIED season is REFUSED — it could not honestly put the old prices back', function () {
    $f = sedFixture();
    app(SeasonalRateService::class)->apply($f->season->fresh());

    $operator = sedOperator($f->scenario);

    $this->actingAs($operator)->put(route('app.seasons.update', $f->season->getKey()), [
        'name' => 'Should not land', 'starts_on' => '2026-09-02', 'ends_on' => '2026-09-04', 'price' => '111.00',
    ])->assertSessionHasErrors('season');

    // Nothing moved — not the season, not the grid.
    expect((int) $f->season->fresh()->price_minor)->toBe(33300)
        ->and(sedGrid($f->plan->getKey(), '2026-09-03'))->toBe(33300);
});

it('⚠️ deleting an UNAPPLIED season changes no prices at all', function () {
    $f = sedFixture();
    $operator = sedOperator($f->scenario);

    $this->actingAs($operator)->delete(route('app.seasons.destroy', $f->season->getKey()))->assertRedirect();

    expect(sedGrid($f->plan->getKey(), '2026-09-03'))->toBe(20000);
});

// ══ #4 · delete is hard, so a gone season is gone from every query ══════════════════════════════════

it('⚠️ a deleted season is gone even from a scope-dropping query — no soft-delete to lose', function () {
    // ⚠️ A soft-deleted PLAN once kept selling because `withoutGlobalScopes()` dropped the soft-delete scope
    //    along with the tenant one. `RateSeason` carries no SoftDeletes, so that shape cannot recur here.
    $f = sedFixture();
    $id = (string) $f->season->getKey();

    $f->season->delete();

    expect(RateSeason::withoutGlobalScopes()->whereKey($id)->exists())->toBeFalse()
        ->and(in_array(SoftDeletes::class, class_uses_recursive(RateSeason::class), true))
        ->toBeFalse('RateSeason gained SoftDeletes — re-check every scope-dropping query before keeping it');
});

// ══ #7 · the gate, and both directions ═════════════════════════════════════════════════════════════

it('⚠️⚠️ both new routes are gated by rates.manage — the DENIED role cannot, the PERMITTED role can', function () {
    $f = sedFixture();

    // ⚠️ A role that does NOT hold `rates.manage`.
    $denied = sedOperator($f->scenario, 'Housekeeping');
    expect($denied->getRoleNames()->all())->toBe(['Housekeeping'])
        ->and($denied->can('rates.manage'))->toBeFalse();

    $this->actingAs($denied)->put(route('app.seasons.update', $f->season->getKey()), [
        'name' => 'x', 'starts_on' => '2026-09-02', 'ends_on' => '2026-09-03', 'price' => '10.00',
    ])->assertForbidden();
    $this->actingAs($denied)->delete(route('app.seasons.destroy', $f->season->getKey()))->assertForbidden();

    // ⚠️ AND THE OTHER DIRECTION — a gate proven only in deny may be denying everyone.
    $permitted = sedOperator($f->scenario, 'Hotel Manager');
    expect($permitted->can('rates.manage'))->toBeTrue();

    $this->actingAs($permitted)->put(route('app.seasons.update', $f->season->getKey()), [
        'name' => 'Allowed', 'starts_on' => '2026-09-02', 'ends_on' => '2026-09-03', 'price' => '10.00',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($f->season->fresh()->name)->toBe('Allowed');
});

// ══ The interface must not imply more than it does ═════════════════════════════════════════════════

it('⚠️⚠️ the delete dialog SAYS that applied prices are not restored', function () {
    $view = i18nEnglishSource(base_path('resources/views/livewire/operator/rates-grid.blade.php'));

    expect($view)->toContain('the nightly prices it wrote stay as they are')
        ->and($view)->toContain('not stored anywhere')
        // And the edit dialog promises the two-step it actually performs.
        ->and($view)->toContain('<strong>No prices are written</strong>');
});

it('⚠️ Edit is offered ONLY on a draft — an applied season shows no edit control', function () {
    $view = i18nEnglishSource(base_path('resources/views/livewire/operator/rates-grid.blade.php'));

    expect($view)->toContain("@if (! \$vySeason['applied'])");
});

it('⚠️⚠️ the rendered season row carries the update and delete URLs the dialogs post to', function () {
    // ⚠️ THIS PROBE EXISTS BECAUSE A MUTATION FOUND ITS ABSENCE. Every test above POSTs to the route
    //    directly, so removing `update_url` from the row payload — which would leave the Edit dialog
    //    submitting to an empty action, i.e. the current page — came back GREEN. A control wired to
    //    nothing is exactly the class this project keeps finding.
    $f = sedFixture();
    $operator = sedOperator($f->scenario);

    setPermissionsTeamId($f->scenario['org']->id);

    // ⚠️ The grid is PROPERTY-SCOPED, so the context must name the fixture's property or the component
    //    renders a different property's seasons — and the probe would assert nothing.
    $propertyId = (string) $f->scenario['property']->getKey();
    app(PropertyContext::class)->activate([$propertyId], $propertyId);

    $rows = Livewire\Livewire::actingAs($operator)
        ->test(RatesGrid::class)
        ->viewData('seasons');

    // ⚠️ NON-VACUITY: an empty list would satisfy any per-row assertion below.
    expect($rows)->not->toBeEmpty();

    $row = collect($rows)->firstWhere('id', (string) $f->season->getKey());

    expect($row)->not->toBeNull()
        ->and($row['update_url'])->toBe(route('app.seasons.update', $f->season->getKey()))
        ->and($row['delete_url'])->toBe(route('app.seasons.destroy', $f->season->getKey()))
        // And the price the edit form pre-fills is the major-unit string `MoneyInput::toMinor()` parses back.
        ->and($row['price_input'])->toBe('333.00');
});

// ───────────────────── FIX 02 · F-20 — THE SCOPE THE DIALOGS READ FROM MUST ENCLOSE THEM ─────────────────────
// The Edit and Delete dialogs sat OUTSIDE the `x-data` that declares `editSeason` / `deleteSeason`, so Alpine
// threw `editSeason is not defined` on EVERY load and neither could ever open — for any role. The markup was
// entirely correct, which is why every existing guard stayed green: DialogFocusContractTest checks each
// dialog's OWN attributes (trap, role, tabindex), never whether the variable it reads is reachable.
//
// ⚠️ These assert REACHABILITY (an ancestor `x-data` declares the variable) and PLACEMENT (that ancestor is not
// an animated card). Asserting the markup merely EXISTS would have passed throughout the defect.

/** The rendered rates grid, as an operator who may manage rates. */
function sedRenderedGrid($test, object $f): string
{
    $operator = sedOperator($f->scenario);
    $pid = (string) $f->scenario['property']->id;
    app(PropertyContext::class)->activate([$pid], $pid);

    return $test->actingAs($operator)->get(route('app.rates'))->assertOk()->getContent();
}

/** Walk ancestors of the element whose `x-show` reads $var, looking for an `x-data` that declares it. */
function sedScopeFor(string $html, string $var): array
{
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();
    $xp = new DOMXPath($dom);

    $dialog = $xp->query('//*[contains(@x-show, "'.$var.'")]')->item(0);
    if (! $dialog instanceof DOMElement) {
        return ['dialogFound' => false];
    }

    for ($n = $dialog->parentNode; $n instanceof DOMElement; $n = $n->parentNode) {
        $xdata = $n->getAttribute('x-data');
        if ($xdata !== '' && str_contains($xdata, $var)) {
            return ['dialogFound' => true, 'declared' => true, 'scopeClass' => $n->getAttribute('class')];
        }
    }

    return ['dialogFound' => true, 'declared' => false, 'scopeClass' => null];
}

it('⚠️⚠️ every season dialog reads a variable an ANCESTOR x-data declares — or it can never open (F-20)', function () {
    $f = sedFixture();
    $html = sedRenderedGrid($this, $f);

    foreach (['confirmSeason', 'editSeason', 'deleteSeason'] as $var) {
        $scope = sedScopeFor($html, $var);

        expect($scope['dialogFound'])->toBeTrue("no dialog reads {$var}");
        // THE defect: the dialog exists, but no enclosing scope defines what it reads.
        expect($scope['declared'])->toBeTrue(
            "the {$var} dialog is OUTSIDE every x-data that declares {$var} — Alpine throws "
            ."'{$var} is not defined' on load and the dialog can never open"
        );
    }
});

it('⚠️ the season scope is not an animated card — a .vy-fade ancestor clamps a fixed dialog to its own box', function () {
    $f = sedFixture();
    $html = sedRenderedGrid($this, $f);

    // `.vy-fade` ends its animation on `transform: translateY(0)`, and ANY transform makes an element the
    // containing block for `position: fixed` descendants. A dialog declared on such a card opens clamped to
    // the card instead of the viewport — measured at 1148×345 with the panel at y=1208 in a 900px viewport.
    foreach (['editSeason', 'deleteSeason'] as $var) {
        $scope = sedScopeFor($html, $var);

        expect($scope['declared'])->toBeTrue();
        expect($scope['scopeClass'])->not->toContain('vy-fade');
    }
});
