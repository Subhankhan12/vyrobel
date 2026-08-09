<?php

declare(strict_types=1);

use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * REGRESSION — the WAVE 3 tail: the owner payout YEAR filter.
 *
 * `OwnerPayouts::distinctYears()` built a real list of the owner's payout years and exposed it as `years`;
 * NOTHING rendered it, and the component never filtered by year. The wireframe's filter did not exist.
 *
 * ⚠️ THE ASSERTION DELIBERATELY AVOIDS MONEY STRINGS. A first attempt at this test asserted on amounts and
 * could not discriminate: the page prints figures in the KPI band and the next-payout card as well as the
 * history rows, so `assertDontSee('111.00')` fails even when the history IS correctly filtered. These
 * assertions count HISTORY ROWS (`vy-row-month` renders exactly once per row) and check each payout's own
 * remittance link — both unique to the history table.
 *
 * ⚠️ HARNESS NOTES (each cost real time): `/owner/payouts` is a FULL-PAGE Livewire component, so
 * `$response->viewData()` throws "The response is not a view" — assert on rendered content via the real GET
 * route, which also runs the owner middleware that `Livewire::test` skips. And `ownerRoleUser()` creates its
 * OWN Owner, so the payouts must be built against THAT owner or they are invisible to the authenticated user.
 */
function payoutInYear(array $scenario, Owner $owner, string $paidAt, int $minor): Payout
{
    $statement = OwnerStatement::create([
        'organization_id' => $scenario['org']->id, 'owner_id' => $owner->id,
        'period_start' => $paidAt, 'period_end' => $paidAt,
        'currency' => 'EUR', 'source_currency' => 'EUR',
    ]);

    return Payout::create([
        'organization_id' => $scenario['org']->id, 'owner_id' => $owner->id,
        'owner_statement_id' => $statement->id, 'amount_minor' => $minor,
        'currency' => 'EUR', 'status' => 'paid', 'paid_at' => $paidAt,
    ]);
}

/**
 * How many payout HISTORY rows the page rendered.
 *
 * Counts the ATTRIBUTE, not the bare class name: the name also appears in the stylesheet's rule
 * definitions, so `substr_count($html, 'vy-row-month')` over-counts by a constant and would have made these
 * assertions wrong in a way that still "looked" like a real number.
 */
function historyRowCount(string $html): int
{
    return substr_count($html, 'class="vy-row-month"');
}

it('filters the payout history to a real year, offering only the owner’s real years', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s);
    $owner = Owner::query()->where('user_id', $user->id)->firstOrFail();

    $old = payoutInYear($s, $owner, '2025-04-14', 11100);
    payoutInYear($s, $owner, '2026-04-14', 22200);
    $recent = payoutInYear($s, $owner, '2026-08-23', 33300);

    // No filter → all three rows, and BOTH real years are offered.
    $all = $this->actingAs($user)->get('/owner/payouts')->assertOk();
    expect(historyRowCount($all->getContent()))->toBe(3);
    $all->assertSee('year=2026', false)->assertSee('year=2025', false);

    // ?year=2026 → two rows; the 2025 payout's OWN remittance link is gone, the 2026 one remains.
    $y2026 = $this->actingAs($user)->get('/owner/payouts?year=2026')->assertOk();
    expect(historyRowCount($y2026->getContent()))->toBe(2);
    $y2026->assertSee(route('owner.payouts.remittance', $recent->id), false)
        ->assertDontSee(route('owner.payouts.remittance', $old->id), false);

    // ?year=2025 → one row, the other way round.
    $y2025 = $this->actingAs($user)->get('/owner/payouts?year=2025')->assertOk();
    expect(historyRowCount($y2025->getContent()))->toBe(1);
    $y2025->assertSee(route('owner.payouts.remittance', $old->id), false)
        ->assertDontSee(route('owner.payouts.remittance', $recent->id), false);
});

it('widens to every payout on a malformed year rather than emptying the history', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s);
    $owner = Owner::query()->where('user_id', $user->id)->firstOrFail();

    payoutInYear($s, $owner, '2026-04-14', 5000);
    payoutInYear($s, $owner, '2026-08-23', 6000);

    // Junk is not a filter — it must show everything, never an empty screen.
    expect(historyRowCount($this->actingAs($user)->get('/owner/payouts?year=banana')->assertOk()->getContent()))->toBe(2);

    // A VALID year with no payouts legitimately shows none — a true empty, not a bug.
    expect(historyRowCount($this->actingAs($user)->get('/owner/payouts?year=1999')->assertOk()->getContent()))->toBe(0);
});

it('offers no year control when there is nothing to choose between', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s);
    $owner = Owner::query()->where('user_id', $user->id)->firstOrFail();

    // A single year — a filter with one option is a decorative control, so it must not render.
    payoutInYear($s, $owner, '2026-04-14', 5000);
    payoutInYear($s, $owner, '2026-08-23', 6000);

    $this->actingAs($user)->get('/owner/payouts')->assertOk()->assertDontSee('year=2026', false);
});

it('keeps the filter owner-scoped — another owner’s payouts never appear', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s);
    $owner = Owner::query()->where('user_id', $user->id)->firstOrFail();

    payoutInYear($s, $owner, '2026-04-14', 5000);

    // A DIFFERENT owner in the same org, with a payout in the same year.
    $other = ownUnit($s['units'][0]);
    $foreign = payoutInYear($s, $other, '2026-08-23', 99999);

    $filtered = $this->actingAs($user)->get('/owner/payouts?year=2026')->assertOk();

    // Filtering must not widen the owner scope (#38): one row, and never the other owner's payout.
    expect(historyRowCount($filtered->getContent()))->toBe(1);
    $filtered->assertDontSee(route('owner.payouts.remittance', $foreign->id), false);
});
