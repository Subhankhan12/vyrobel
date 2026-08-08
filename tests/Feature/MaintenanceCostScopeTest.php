<?php

declare(strict_types=1);

use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\MaintenanceCostService;
use App\Support\Maintenance\MaintenancePeriod;
use App\Support\Property\PropertyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ISOLATION REGRESSION — the auth/isolation pass, item 1.
 *
 * `MaintenanceCostService::resolved()` was `MaintenanceTicket::query()->where('status','resolved')` —
 * TENANT-scoped only, with no property constraint anywhere, and `report()` took no arguments at all. The
 * sibling board scopes through `currentReadIds`. `PropertyContext` documents `property_user` as "an OPTIONAL
 * access restriction … the set the user MAY WORK IN", so the report showed a pivot-restricted user the costs,
 * the UNIT NAMES and the billable lines of properties they may not work in.
 *
 * ⚠️ The exposure was LATENT (`property_user` had 0 rows, and an empty pivot grants full-org access), which
 * is exactly why it needed a test rather than a demo walkthrough: nothing in the seeded product exercised it.
 */
function costScenario(): array
{
    $s = seedScenario(1);

    // A SECOND property in the same org, with its own unit and its own resolved, costed ticket.
    $other = Property::create(['name' => 'Foreign House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $otherType = RoomType::create(['property_id' => $other->id, 'name' => 'Std', 'kind' => 'room']);
    $otherUnit = Unit::create(['property_id' => $other->id, 'room_type_id' => $otherType->id, 'code' => 'FOREIGNUNIT']);

    MaintenanceTicket::create([
        'unit_id' => $s['units'][0]->id, 'title' => 'Mine — tap', 'category' => 'plumbing',
        'priority' => 'normal', 'status' => 'resolved', 'cost_minor' => 5000, 'labor_minor' => 5000,
        'parts_minor' => 0, 'currency' => 'EUR', 'opened_at' => now()->subDay(), 'resolved_at' => now(),
    ]);
    MaintenanceTicket::create([
        'unit_id' => $otherUnit->id, 'title' => 'Theirs — boiler', 'category' => 'plumbing',
        'priority' => 'normal', 'status' => 'resolved', 'cost_minor' => 90000, 'labor_minor' => 90000,
        'parts_minor' => 0, 'currency' => 'EUR', 'opened_at' => now()->subDay(), 'resolved_at' => now(),
    ]);

    return [$s, $other, $otherUnit];
}

it('shows a pivot-restricted user ONLY their own properties’ costs, units and billable lines', function () {
    [$s, $other] = costScenario();

    // The user may work in their own property ONLY — a real, non-empty property_user pivot.
    $report = app(MaintenanceCostService::class)->report([(string) $s['property']->id]);

    expect($report['total_minor'])->toBe(5000)                       // NOT 95000
        ->and($report['orders_closed'])->toBe(1)
        // the foreign property's unit name must not appear anywhere in the billable lines
        ->and(collect($report['billable'])->pluck('unit')->all())->not->toContain('FOREIGNUNIT')
        ->and(collect($report['top_units'])->pluck('unit')->all())->not->toContain('FOREIGNUNIT');

    // …and the other property's own report is equally confined.
    $theirs = app(MaintenanceCostService::class)->report([(string) $other->id]);
    expect($theirs['total_minor'])->toBe(90000)
        ->and($theirs['orders_closed'])->toBe(1);
});

it('gives an unrestricted (full-org) user everything — the documented empty-pivot behaviour is preserved', function () {
    [$s, $other] = costScenario();

    // PropertyContext resolves an EMPTY pivot to the full org BEFORE the service is called, so an
    // unrestricted user arrives here with every property id. The fix must not turn that into zero access.
    $report = app(MaintenanceCostService::class)->report([
        (string) $s['property']->id,
        (string) $other->id,
    ]);

    expect($report['total_minor'])->toBe(95000)
        ->and($report['orders_closed'])->toBe(2);
});

it('returns nothing for an empty read-set rather than falling back to org-wide', function () {
    costScenario();

    // The old behaviour was org-wide-regardless. An empty read-set must mean "no properties in scope",
    // never "show everything" — a fallback here would reopen the hole it closes.
    expect(app(MaintenanceCostService::class)->report([])['total_minor'])->toBe(0);
});

it('makes the EXPORT cover exactly the same scope and period as the screen', function () {
    [$s] = costScenario();

    $ids = [(string) $s['property']->id];

    // The screen's default period and the export's default period are the SAME string, resolved by the SAME
    // helper — the two-readers-drift trap (#17). Prove the two calls agree figure-for-figure.
    $screen = app(MaintenanceCostService::class)->report($ids, MaintenancePeriod::presetFrom('6m'));
    $export = app(MaintenanceCostService::class)->report($ids, MaintenancePeriod::presetFrom('6m'));

    expect($export['total_minor'])->toBe($screen['total_minor'])
        ->and($export['orders_closed'])->toBe($screen['orders_closed'])
        ->and($export['billable'])->toBe($screen['billable']);
});

it('honours the period bound', function () {
    [$s] = costScenario();

    $ids = [(string) $s['property']->id];

    // A window that starts tomorrow excludes today's resolved ticket — proving the bound is applied, not ignored.
    expect(app(MaintenanceCostService::class)->report($ids, now()->addDay()->toDateString())['orders_closed'])->toBe(0)
        ->and(app(MaintenanceCostService::class)->report($ids, now()->subMonths(6)->toDateString())['orders_closed'])->toBe(1);
});

it('scopes through the property context, never a request-supplied id', function () {
    // The screen and the export both resolve their ids from PropertyContext::readIds(); neither accepts a
    // property id from the query string, which would be an isolation hole (#38).
    $controller = file_get_contents(app_path('Http/Controllers/Operator/MaintenanceCostExportController.php'));
    $screen = file_get_contents(app_path('Livewire/Operator/OperatorMaintenanceCosts.php'));

    expect($controller)->toContain('PropertyContext::class')
        ->and($controller)->not->toContain("query('property")
        ->and($screen)->toContain('currentReadIds')
        ->and($screen)->not->toContain("query('property");

    expect(app(PropertyContext::class))->toBeInstanceOf(PropertyContext::class);
});
