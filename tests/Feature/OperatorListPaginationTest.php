<?php

declare(strict_types=1);

use App\Livewire\Operator\GuestsList;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * REGRESSION — the WAVE 3 pagination gate.
 *
 * The operator Guests and Reservations lists loaded UNBOUNDED sets — every guest / every reservation in the
 * tenant, on every render.
 *
 * ⚠️ THE DESIGN DECISION WORTH PINNING: pages ride GET links, NOT Livewire wire-actions.
 * `SetCurrentPropertyForWebRequest` is mounted on the `/app` ROUTE GROUP, not globally, so it does not run
 * on `/livewire/update` (invariant #20) — which is exactly why these lists already document that their
 * FILTERS ride GET links. A page is a scope-dependent read too, so a page change is an ordinary request
 * that re-runs every middleware, rather than a wire update that would inherit scope from a prop.
 */
it('bounds the guests list to one page instead of loading every guest', function () {
    $s = seedScenario(1);

    // ⚠️ GuestFactory defaults organization_id to a NEW Organization::factory(), so the org must be
    // passed explicitly or every guest lands in its own tenant and the list (correctly) shows none of them.
    Guest::factory()->count(GuestsList::PER_PAGE + 7)->create(['organization_id' => $s['org']->id]);

    $rows = Livewire::actingAs(operatorUser($s))->test(GuestsList::class)->viewData('rows');

    expect($rows)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($rows->perPage())->toBe(GuestsList::PER_PAGE)
        ->and($rows->count())->toBe(GuestsList::PER_PAGE)          // the PAGE, not the whole set
        ->and($rows->total())->toBeGreaterThan(GuestsList::PER_PAGE);
});

it('returns the NEXT set on page 2, not a repeat of page 1', function () {
    $s = seedScenario(1);
    // ⚠️ GuestFactory defaults organization_id to a NEW Organization::factory(), so the org must be
    // passed explicitly or every guest lands in its own tenant and the list (correctly) shows none of them.
    Guest::factory()->count(GuestsList::PER_PAGE + 7)->create(['organization_id' => $s['org']->id]);

    // Read the paginator directly for the ids — the rendered HTML is proven separately in the browser.
    $page1 = Livewire::actingAs(operatorUser($s))->withQueryParams(['page' => 1])->test(GuestsList::class)->viewData('rows');
    $page2 = Livewire::actingAs(operatorUser($s))->withQueryParams(['page' => 2])->test(GuestsList::class)->viewData('rows');

    $ids1 = collect($page1->items())->pluck('id')->all();
    $ids2 = collect($page2->items())->pluck('id')->all();

    expect($ids2)->not->toBeEmpty()
        ->and(array_intersect($ids1, $ids2))->toBe([])              // genuinely different rows
        ->and($page2->currentPage())->toBe(2);
});

it('keeps the search filter applied on page 2 rather than widening the set', function () {
    $s = seedScenario(1);

    // A searchable cohort larger than one page, plus noise that must never appear.
    Guest::factory()->count(GuestsList::PER_PAGE + 5)->create(['organization_id' => $s['org']->id, 'last_name' => 'Pageable']);
    Guest::factory()->count(4)->create(['organization_id' => $s['org']->id, 'last_name' => 'Excluded']);

    $page2 = Livewire::actingAs(operatorUser($s))
        ->withQueryParams(['q' => 'Pageable', 'page' => 2])
        ->test(GuestsList::class)
        ->viewData('rows');

    // Page 2 of a FILTERED set: the total is the filtered total, and no excluded row leaks in.
    expect($page2->total())->toBe(GuestsList::PER_PAGE + 5)
        ->and(collect($page2->items())->pluck('name')->filter(fn (string $n): bool => str_contains($n, 'Excluded'))->all())->toBe([]);
});

it('carries the active filters onto the page links, so paging cannot silently widen the results', function () {
    $s = seedScenario(1);
    Guest::factory()->count(GuestsList::PER_PAGE + 5)->create(['organization_id' => $s['org']->id, 'last_name' => 'Pageable']);

    $rows = Livewire::actingAs(operatorUser($s))
        ->withQueryParams(['q' => 'Pageable'])
        ->test(GuestsList::class)
        ->viewData('rows');

    // withQueryString() — the next-page URL must still carry ?q=, or page 2 would show everyone.
    expect(str_contains((string) $rows->nextPageUrl(), 'q=Pageable'))->toBeTrue();
});
